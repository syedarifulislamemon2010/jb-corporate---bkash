<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\UUID;

class BkashTransaction extends Model
{
    use UUID;
    use SoftDeletes; 

    protected $connection = 'oracle';
    protected $table = 'bkash_transactions';
    protected $primaryKey = 'id';
    
    // UUID-এর জন্য প্রাইমারি কি অটো-ইনক্রিমেন্ট নয়
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_type',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'create_date'       => 'datetime',
            'return_date'       => 'datetime',
            'approved_at'        => 'datetime',
            'confirmed_at'       => 'datetime',
            'admin_approved_at' => 'datetime',
            'cbs_success_at'    => 'datetime',
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