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

    protected $table = 'bkash_transaction_batch';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

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

    public function transactions()
    {
        return $this->hasMany(BkashTransaction::class, 'batch_id', 'id');
    }

    public function failedTransactions()
    {
        return $this->hasMany(BkashFailedTransaction::class, 'batch_id', 'id');
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