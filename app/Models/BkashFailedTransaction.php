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
     * Accessor for reference_id supporting legacy reference column.
     */
    public function getReferenceIdAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['reference'] ?? null;
    }

    /**
     * Backward-compatibility accessor for credit_account.
     */
    public function getCreditAccountAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['source_account_no'] ?? null;
    }

    /**
     * Accessor for source_account_no supporting legacy credit_account column.
     */
    public function getSourceAccountNoAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['credit_account'] ?? null;
    }

    /**
     * Backward-compatibility accessor for debit_account.
     */
    public function getDebitAccountAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['beneficiary_account_no'] ?? null;
    }

    /**
     * Accessor for beneficiary_account_no supporting legacy debit_account column.
     */
    public function getBeneficiaryAccountNoAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['debit_account'] ?? null;
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
