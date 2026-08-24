<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\UUID;

class EftReturn extends Model
{
    use UUID;

    protected $table = 'eft_returns';
    protected $primaryKey = 'id';

    protected $fillable = [
        'txn_id',
        'reference_id',
        'original_file_name',
        'execution_date',
        'return_date',
        'service_type',
        'bene_bank_name',
        'bene_branch_name',
        'bene_routing_no',
        'beneficiary_account',
        'bene_name',
        'amount',
        'return_code',
        'return_reason',
        'particular',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'execution_date' => 'date',
            'return_date'    => 'date',
            'returned_at'    => 'datetime',
        ];
    }
}
