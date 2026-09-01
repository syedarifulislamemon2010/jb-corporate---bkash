<?php

namespace App\Jobs;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\PostingAttempt;
use App\Services\CbsApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExecuteCbsSettlementJob implements ShouldQueue
{
    use Queueable;

    public array $transactionIds;

    public int $tries = 3;
    public int $timeout = 120; // 2 minutes per job execution
    public int $maxExceptions = 3;

    public function __construct(array $transactionIds)
    {
        $this->transactionIds = $transactionIds;
    }

    public function backoff(): array
    {
        return [10, 30, 60]; // seconds — retry with increasing delay
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("ExecuteCbsSettlementJob permanently failed after all retries for transaction IDs: " . 
            implode(',', $this->transactionIds) . " — Error: " . $exception->getMessage());

        try {
            Log::channel('critical_financial')->critical("ExecuteCbsSettlementJob permanently failed after all retries for transaction IDs: " . 
                implode(',', $this->transactionIds) . " — Error: " . $exception->getMessage());
        } catch (\Throwable $e) {
            // Fallback if log channel fails
        }

        // Mark affected transactions as needing manual review instead of leaving 
        // them silently stuck at STATUS_FINAL_AUTHORIZED
        BkashTransaction::whereIn('id', $this->transactionIds)
            ->where('status_id', BkashTransaction::STATUS_FINAL_AUTHORIZED)
            ->update([
                'reject_reason' => 'CBS settlement job failed after maximum retries — requires manual review. Error: ' . 
                    substr($exception->getMessage(), 0, 500),
            ]);

        // TODO (future): dispatch an admin alert notification here via 
        // NotificationService when an admin-alert channel is defined.
    }

    public function handle(CbsApiService $cbsApiService): void
    {
        Log::info("ExecuteCbsSettlementJob: Starting CBS / BACH automated settlement execution for " . count($this->transactionIds) . " transactions...");

        $transactions = BkashTransaction::whereIn('id', $this->transactionIds)
            ->where('status_id', BkashTransaction::STATUS_FINAL_AUTHORIZED)
            ->orderBy('row_sequence', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($transactions as $txn) {
            try {
                $attempt = PostingAttempt::firstOrCreate(
                    ['txn_id' => $txn->txn_id ?: $txn->id],
                    [
                        'instruction_id' => $txn->id,
                        'channel'        => $txn->transaction_type,
                        'outcome'        => 'PENDING',
                        'external_ref'   => $txn->reference_id,
                    ]
                );

                if ($attempt->outcome === 'SUCCESS') {
                    Log::warning("Posting already executed for Txn ID: {$txn->txn_id}");
                    continue;
                }

                // Call the CBS API Service (external HTTP outside DB transaction)
                $result = $cbsApiService->settleTransaction($txn);

                // Atomic database update of PostingAttempt, BkashTransaction, and BkashFailedTransaction
                DB::transaction(function () use ($txn, $attempt, $result) {
                    if ($result['success']) {
                        $attempt->update([
                            'outcome' => 'SUCCESS',
                        ]);

                        $txn->update([
                            'status_id'      => BkashTransaction::STATUS_CBS_SUCCESS,
                            'cbs_success_at' => Carbon::now(),
                        ]);

                        Log::info("Successfully settled transaction via CBS API: {$txn->reference_id} [{$txn->amount} BDT]");
                    } else {
                        $failureCode  = $result['failure_code'] ?? 'CBS_REJECTED';
                        $rejectReason = $result['reject_reason'] ?? $result['message'] ?? 'CBS rejected transaction.';

                        $attempt->update([
                            'outcome' => 'FAILED',
                        ]);

                        // 1. Mark transaction status as STATUS_REJECTED (9000)
                        $txn->update([
                            'status_id'     => BkashTransaction::STATUS_REJECTED,
                            'reject_reason' => $rejectReason,
                        ]);

                        // 2. Record in BkashFailedTransaction table for reporting
                        BkashFailedTransaction::create([
                            'batch_id'          => $txn->batch_id,
                            'file_name'         => $txn->file_name ?? 'bKash_File.xlsx',
                            'row_number'        => ($txn->row_sequence !== null ? $txn->row_sequence + 1 : 1),
                            'transaction_type'  => $txn->transaction_type,
                            'reference_id'           => $txn->reference_id ?? 'N/A',
                            'source_account_no'      => $txn->source_account_no,      // Sender / Source account
                            'beneficiary_account_no' => $txn->beneficiary_account_no, // Beneficiary account
                            'amount'                 => $txn->amount,
                            'failure_code'      => $failureCode,
                            'reject_reason'     => $rejectReason,
                        ]);

                        Log::warning("CBS API posting failed for Txn {$txn->reference_id} [Code: {$failureCode}]: {$rejectReason}");
                    }
                });

            } catch (\Throwable $e) {
                Log::error("Failed settling transaction {$txn->id}: " . $e->getMessage());
                try {
                    Log::channel('critical_financial')->error("ExecuteCbsSettlementJob: Exception while settling transaction {$txn->id}: " . $e->getMessage());
                } catch (\Throwable $ignored) {
                }
                throw $e;
            }
        }
    }
}
