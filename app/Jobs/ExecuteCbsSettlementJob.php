<?php

namespace App\Jobs;

use App\Models\BkashTransaction;
use App\Models\PostingAttempt;
use App\Services\CbsApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExecuteCbsSettlementJob implements ShouldQueue
{
    use Queueable;

    public array $transactionIds;

    public function __construct(array $transactionIds)
    {
        $this->transactionIds = $transactionIds;
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

                // Call the CBS API Service
                $result = $cbsApiService->settleTransaction($txn);

                if ($result['success']) {
                    $attempt->update([
                        'outcome'       => 'SUCCESS',
                        'response_code' => (string) $result['status_code'],
                        'response_body' => is_array($result['response']) ? json_encode($result['response']) : (string)$result['response'],
                    ]);

                    $txn->update([
                        'status_id'      => BkashTransaction::STATUS_CBS_SUCCESS,
                        'cbs_success_at' => Carbon::now(),
                    ]);

                    Log::info("Successfully settled transaction via CBS API: {$txn->reference_id} [{$txn->amount} BDT]");
                } else {
                    $attempt->update([
                        'outcome'       => 'FAILED',
                        'response_code' => (string) $result['status_code'],
                        'response_body' => is_array($result['response']) ? json_encode($result['response']) : (string)$result['response'],
                        'error_message' => $result['message'],
                    ]);

                    Log::error("CBS API posting failed for Txn {$txn->reference_id}: {$result['message']}");
                }

            } catch (\Exception $e) {
                Log::error("Failed settling transaction {$txn->id}: " . $e->getMessage());
            }
        }
    }
}
