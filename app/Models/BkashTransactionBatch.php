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
     * Refresh the batch status_id based on the collective status of its transactions.
     */
    public function refreshStatusFromTransactions(): void
    {
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