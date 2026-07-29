<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\UUID;

class BkashTransactionBatch extends Model
{
    use UUID;
    use SoftDeletes; 

    protected $connection = 'oracle';
    protected $table = 'bkash_transaction_batch';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'transaction_type',
        'total_data',
        'reference_id',
        'create_date',
        'return_date',
        'debit_account_title',
        'debit_account_no',
        'amount',
        'debit_routing',
        'credit_routing',
        'credit_bank',
        'credit_account_no',
        'credit_account_title',
        'txn_id',
        'reject_reason',
        'status_id',
        'created_by',
        'approved_by',
        'confirmed_by',
        'admin_approved',
        'approved_at',
        'confirmed_at',
        'admin_approved_at',
        'cbs_success_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), (string)Str::orderedUuid());
        });
}