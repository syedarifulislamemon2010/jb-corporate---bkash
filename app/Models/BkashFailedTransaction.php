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
        'reference_id',
        'source_account_no',
        'beneficiary_account_no',
        'amount',
        'failure_code',
        'reject_reason',
    ];

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
}
