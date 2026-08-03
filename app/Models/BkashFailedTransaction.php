<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\UUID;

class BkashFailedTransaction extends Model
{
    use UUID;

    protected $table = 'bkash_failed_transactions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'batch_id',
        'file_name',
        'row_number',
        'transaction_type',
        'reference_id',
        'debit_account_no',
        'credit_account_no',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::orderedUuid();
            }
        });
    }
}
