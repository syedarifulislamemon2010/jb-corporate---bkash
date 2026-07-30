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
    
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'transaction_type',
        'reference_id',
        'txn_id',
        'debit_account_no',
        'debit_account_title',
        'credit_account_no',
        'credit_account_title',
        'amount',
        'credit_routing',
        'credit_bank',
        'status_id',
        'reject_reason',
        
        // Workflow User & Timestamps
        'created_by',
        'checked_by',
        'checked_at',
        'approved_by_1',
        'approved_at_1',
        'approved_by_2',
        'approved_at_2',
        'confirmed_by',
        'confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'create_date'   => 'datetime',
            'return_date'   => 'datetime',
            'checked_at'    => 'datetime',
            'approved_at_1' => 'datetime',
            'approved_at_2' => 'datetime',
            'confirmed_at'  => 'datetime',
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