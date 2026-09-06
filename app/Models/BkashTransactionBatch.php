<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\User;
use App\Traits\UUID;

class BkashTransactionBatch extends Model
{
    use UUID;
    use SoftDeletes; 

    protected $table = 'bkash_transaction_batch';
    protected $primaryKey = 'id';

    protected $fillable = [
        'file_name',
        'transaction_type',
        'sha256',
        'total_data',
        'total_amount',
        'status_id',
        'created_by',
        'create_date',
    ];

    protected function casts(): array
    {
        return [
            'total_data'   => 'integer',
            'total_amount' => 'decimal:2',
            'status_id'    => 'integer',
            'create_date'  => 'datetime',
        ];
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BkashTransaction::class, 'batch_id', 'id');
    }

    public function failedTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BkashFailedTransaction::class, 'batch_id', 'id');
    }

    
    public function getBatchTransactions()
    {
        $txns = $this->transactions()->get();
        if ($txns->isEmpty() && filled($this->file_name)) {
            $txns = BkashTransaction::where('file_name', $this->file_name)->get();
        }
        return $txns;
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'name');
    }

    /**
     * Determine if this batch file has any failed transactions.
     */
    public function hasFailedTransactions(): bool
    {
        $hasFailedTableRecords = BkashFailedTransaction::where(function ($q) {
            $q->where('batch_id', $this->id);
            if (filled($this->file_name)) {
                $q->orWhere('file_name', $this->file_name);
            }
        })->exists();

        if ($hasFailedTableRecords) {
            return true;
        }

        return $this->transactions()->whereIn('status_id', [
            BkashTransaction::STATUS_REJECTED,
            BkashTransaction::STATUS_CBS_RESPONSE_FAILED,
        ])->exists();
    }

    /**
     * Count failed transactions for this batch.
     */
    public function failedTransactionsCount(): int
    {
        return BkashFailedTransaction::where(function ($q) {
            $q->where('batch_id', $this->id);
            if (filled($this->file_name)) {
                $q->orWhere('file_name', $this->file_name);
            }
        })->count();
    }

    /**
     * Automatically revert any failed batch files back to "Checker - Verify Files" (STATUS_PENDING_CHECKER).
     * Failed files can never remain in Authorized or Checked state.
     *
     * @param string|null $targetBatchId
     * @param string|null $targetFileName
     * @return int Number of reverted batches
     */
    public static function revertFailedBatchesToChecker(?string $targetBatchId = null, ?string $targetFileName = null): int
    {
        // 1. Identify all failed batch IDs and file names
        $failedQuery = BkashFailedTransaction::query();
        if ($targetBatchId) {
            $failedQuery->where('batch_id', $targetBatchId);
        }
        if ($targetFileName) {
            $failedQuery->orWhere('file_name', $targetFileName);
        }

        $failedBatchIds = (clone $failedQuery)->whereNotNull('batch_id')->pluck('batch_id')->unique()->filter()->all();
        $failedFileNames = (clone $failedQuery)->whereNotNull('file_name')->pluck('file_name')->unique()->filter()->all();

        // Also identify batches with rejected transactions
        $rejectedTxnBatchQuery = BkashTransaction::whereIn('status_id', [
            BkashTransaction::STATUS_REJECTED,
            BkashTransaction::STATUS_CBS_RESPONSE_FAILED,
        ]);
        if ($targetBatchId) {
            $rejectedTxnBatchQuery->where('batch_id', $targetBatchId);
        }
        if ($targetFileName) {
            $rejectedTxnBatchQuery->orWhere('file_name', $targetFileName);
        }

        $failedBatchIds = array_unique(array_merge(
            $failedBatchIds,
            (clone $rejectedTxnBatchQuery)->whereNotNull('batch_id')->pluck('batch_id')->filter()->all()
        ));

        $failedFileNames = array_unique(array_merge(
            $failedFileNames,
            (clone $rejectedTxnBatchQuery)->whereNotNull('file_name')->pluck('file_name')->filter()->all()
        ));

        if (empty($failedBatchIds) && empty($failedFileNames)) {
            return 0;
        }

        // 2. Find batches in authorization or checked pipeline (status_id > STATUS_PENDING_CHECKER)
        $batchesToRevert = static::where(function ($q) use ($failedBatchIds, $failedFileNames) {
            if (!empty($failedBatchIds)) {
                $q->whereIn('id', $failedBatchIds);
            }
            if (!empty($failedFileNames)) {
                if (!empty($failedBatchIds)) {
                    $q->orWhereIn('file_name', $failedFileNames);
                } else {
                    $q->whereIn('file_name', $failedFileNames);
                }
            }
        })
        ->whereIn('status_id', [
            BkashTransaction::STATUS_CHECKED,
            BkashTransaction::STATUS_AUTH_1_APPROVED,
            BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ])
        ->get();

        $revertedCount = 0;

        foreach ($batchesToRevert as $batch) {
            $batch->update([
                'status_id' => BkashTransaction::STATUS_PENDING_CHECKER,
            ]);

            // Revert all non-finalized transactions in this batch back to STATUS_PENDING_CHECKER and reset workflow stamps
            BkashTransaction::where(function ($q) use ($batch) {
                $q->where('batch_id', $batch->id);
                if (filled($batch->file_name)) {
                    $q->orWhere('file_name', $batch->file_name);
                }
            })
            ->whereNotIn('status_id', [
                BkashTransaction::STATUS_REJECTED,
                BkashTransaction::STATUS_CBS_RESPONSE_FAILED,
                BkashTransaction::STATUS_CBS_SUCCESS,
                BkashTransaction::STATUS_CBS_RESPONSE_SUCCESS,
            ])
            ->update([
                'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
                'checked_by'       => null,
                'checked_by_id'    => null,
                'checked_at'       => null,
                'approved_by_1'    => null,
                'approved_by_1_id' => null,
                'approved_at_1'    => null,
                'approved_by_2'    => null,
                'approved_by_2_id' => null,
                'approved_at_2'    => null,
                'confirmed_by'     => null,
                'confirmed_at'     => null,
            ]);

            $revertedCount++;
        }

        // Also catch any orphan transactions that don't have a batch record but have failed records for that file name
        if (!empty($failedFileNames)) {
            BkashTransaction::whereIn('file_name', $failedFileNames)
                ->whereIn('status_id', [
                    BkashTransaction::STATUS_CHECKED,
                    BkashTransaction::STATUS_AUTH_1_APPROVED,
                    BkashTransaction::STATUS_FINAL_AUTHORIZED,
                ])
                ->update([
                    'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
                    'checked_by'       => null,
                    'checked_by_id'    => null,
                    'checked_at'       => null,
                    'approved_by_1'    => null,
                    'approved_by_1_id' => null,
                    'approved_at_1'    => null,
                    'approved_by_2'    => null,
                    'approved_by_2_id' => null,
                    'approved_at_2'    => null,
                    'confirmed_by'     => null,
                    'confirmed_at'     => null,
                ]);
        }

        return $revertedCount;
    }

    /**
     * Refresh the batch status_id based on the collective status of its transactions.
     */
    public function refreshStatusFromTransactions(): void
    {
        // If batch has any failed transactions, it must remain in/revert to Pending Checker
        if ($this->hasFailedTransactions()) {
            $this->update(['status_id' => BkashTransaction::STATUS_PENDING_CHECKER]);
            return;
        }

        $transactions = $this->transactions()->get();
        if ($transactions->isEmpty()) {
            return;
        }

        $distinctStatuses = $transactions->pluck('status_id')->unique()->values()->all();

        if (count($distinctStatuses) === 1) {
            $this->update(['status_id' => $distinctStatuses[0]]);
            return;
        }

        if (in_array(BkashTransaction::STATUS_PENDING_CHECKER, $distinctStatuses)) {
            $this->update(['status_id' => BkashTransaction::STATUS_PENDING_CHECKER]);
            return;
        }

        if (in_array(BkashTransaction::STATUS_CHECKED, $distinctStatuses)) {
            $this->update(['status_id' => BkashTransaction::STATUS_CHECKED]);
            return;
        }

        if (in_array(BkashTransaction::STATUS_AUTH_1_APPROVED, $distinctStatuses)) {
            $this->update(['status_id' => BkashTransaction::STATUS_AUTH_1_APPROVED]);
            return;
        }

        $this->update(['status_id' => min($distinctStatuses)]);
    }
}