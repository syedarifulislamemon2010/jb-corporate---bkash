<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;
use App\Traits\UUID;

class BkashTransaction extends Model
{
    use UUID;
    use SoftDeletes; 

    protected $table = 'bkash_transactions';
    protected $primaryKey = 'id';

    // Status Constants
    public const STATUS_PENDING_CHECKER = 1000;
    public const STATUS_CHECKED = 1001;
    public const STATUS_AUTH_1_APPROVED = 1002;
    public const STATUS_FINAL_AUTHORIZED = 1003;
    public const STATUS_CBS_SUCCESS = 1004;
    public const STATUS_REJECTED = 9000;

    protected $fillable = [
        'batch_id',
        'file_name',
        'row_sequence',
        'transaction_type',
        'reference_id',
        'bb_reference_number',
        'txn_id',
        'create_date',
        'value_date',
        'return_date',
        'debit_account_no',
        'debit_account_title',
        'debit_routing',
        'credit_account_no',
        'credit_account_title',
        'credit_routing',
        'credit_bank',
        'amount',
        'status_id',
        'reject_reason',
        'reason',
        
        // Workflow Users & Timestamps
        'created_by',
        'created_by_id',
        'checked_by',
        'checked_by_id',
        'checked_at',
        'approved_by_1',
        'approved_by_1_id',
        'approved_at_1',
        'approved_by_2',
        'approved_by_2_id',
        'approved_at_2',
        'admin_approved_by',
        'admin_approved_at',
        'cbs_success_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'status_id'         => 'integer',
            'created_by_id'     => 'integer',
            'checked_by_id'     => 'integer',
            'approved_by_1_id'  => 'integer',
            'approved_by_2_id'  => 'integer',
            'create_date'       => 'datetime',
            'return_date'       => 'datetime',
            'checked_at'        => 'datetime',
            'approved_at_1'     => 'datetime',
            'approved_at_2'     => 'datetime',
            'admin_approved_at' => 'datetime',
            'cbs_success_at'    => 'datetime',
        ];
    }

    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BkashTransactionBatch::class, 'batch_id', 'id');
    }

    public function creatorUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function checkedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_id', 'id');
    }

    public function approvedBy1User(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_1_id', 'id');
    }

    public function approvedBy2User(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_2_id', 'id');
    }

    /**
     * BDT Lakh / Crore Comma Formatted Amount Helper
     * e.g. 156100.82 -> 1,56,100.82
     */
    public function getFormattedAmountAttribute(): string
    {
        return static::formatBdtAmount((float)$this->amount);
    }

    public static function formatBdtAmount(float $amount): string
    {
        $amountStr = number_format($amount, 2, '.', '');
        [$integerPart, $decimalPart] = explode('.', $amountStr);

        $lastThree = substr($integerPart, -3);
        $otherNumbers = substr($integerPart, 0, -3);

        if ($otherNumbers !== '') {
            $lastThree = ',' . $lastThree;
        }

        $formattedInteger = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $otherNumbers) . $lastThree;

        return $formattedInteger . '.' . $decimalPart;
    }
}