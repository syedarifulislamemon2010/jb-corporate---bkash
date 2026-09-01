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

    // Status Constants (3-Tier: Checker -> 1st Authorizer -> 2nd Authorizer)
    public const STATUS_PENDING_CHECKER      = 1000; // File uploaded, awaiting Checker
    public const STATUS_CHECKED              = 1001; // Checker approved, awaiting 1st Authorizer
    public const STATUS_AUTH_1_APPROVED      = 1002; // 1st Authorizer approved, awaiting 2nd Authorizer
    public const STATUS_FINAL_AUTHORIZED     = 1003; // 2nd Authorizer approved, awaiting/triggering settlement
    public const STATUS_CBS_SUCCESS          = 1004;
    public const STATUS_CBS_RESPONSE_SUCCESS = 1006;
    public const STATUS_CBS_RESPONSE_FAILED  = 1007;
    public const STATUS_REJECTED             = 9000;

    public static function statusLabel(int $statusId): string
    {
        return match ($statusId) {
            1000 => 'Pending Checker',
            1001 => 'Checked',
            1002 => '1st Authorized',
            1003 => 'Final Authorized',
            1004 => 'CBS Settled',
            1006 => 'CBS Confirmed (Async)',
            1007 => 'CBS Failed (Async)',
            9000 => 'Rejected',
            default => 'Unknown',
        };
    }

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

        'source_account_no',
        'beneficiary_account_no',
        'debit_account_title',
        'debit_routing',
        'credit_account_title',
        'credit_routing',
        'credit_bank',
        'amount',
        'status_id',
        'reject_reason',
        'reason',
        'response_id',
        'confirmed_by',
        'confirmed_at',
        
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
            'confirmed_at'      => 'datetime',
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