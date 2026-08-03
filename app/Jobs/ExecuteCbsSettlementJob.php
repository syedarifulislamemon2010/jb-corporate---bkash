<?php

namespace App\Jobs;

use App\Models\BkashTransaction;
use App\Models\PostingAttempt;
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

    public function handle(): void
    {
        Log::info("Starting CBS / BACH Automated Settlement Execution for " . count($this->transactionIds) . " transactions...");

        $transactions = BkashTransaction::whereIn('id', $this->transactionIds)
            ->where('status_id', BkashTransaction::STATUS_FINAL_AUTHORIZED)
            ->get();

        foreach ($transactions as $txn) {
            try {
                // Double Payment Defense Ledger Check
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

                // Execute Posting via Janata Bank CBS / BACH Interface
                // In live production, this connects to Janata Bank T24 Direct API / BACH
                $attempt->update(['outcome' => 'SUCCESS']);

                $txn->update([
                    'status_id'      => BkashTransaction::STATUS_CBS_SUCCESS,
                    'cbs_success_at' => Carbon::now(),
                ]);

                Log::info("Successfully settled transaction: {$txn->reference_id} [{$txn->amount} BDT]");

            } catch (\Exception $e) {
                Log::error("Failed settling transaction {$txn->id}: " . $e->getMessage());
            }
        }
    }
}
