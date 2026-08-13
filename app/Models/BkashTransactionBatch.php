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

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'name');
    }
}