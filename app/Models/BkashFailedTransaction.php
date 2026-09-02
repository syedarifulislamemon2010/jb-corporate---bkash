<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\BkashTransactionBatch;
use App\Traits\UUID;

class BkashFailedTransaction extends Model
{
    use UUID;

    protected $table = 'bkash_failed_transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'batch_id',
        'file_name',
        'row_number',
        'transaction_type',
        'txn_id',
        'reference_id',
        'source_account_no',
        'beneficiary_account_no',
        'amount',
        'failure_code',
        'reject_reason',
    ];

    /**
     * Backward-compatibility accessor for reference column.
     */
    public function getReferenceAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['reference_id'] ?? null;
    }

    /**
     * Backward-compatibility mutator for reference column.
     */
    public function setReferenceAttribute(?string $value): void
    {
        $this->attributes['reference_id'] = $value;
    }

    /**
     * Backward-compatibility accessor for credit_account.
     */
    public function getCreditAccountAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['source_account_no'] ?? null;
    }

    /**
     * Backward-compatibility accessor for debit_account.
     */
    public function getDebitAccountAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['beneficiary_account_no'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'row_number' => 'integer',
        ];
    }

    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BkashTransactionBatch::class, 'batch_id');
    }

    protected static function booted(): void
    {
        static::created(function (BkashFailedTransaction $failed) {
            BkashTransactionBatch::revertFailedBatchesToChecker($failed->batch_id, $failed->file_name);
        });
    }
}
