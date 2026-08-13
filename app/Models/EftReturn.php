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
        'beneficiary_account',
        'amount',
        'return_code',
        'return_reason',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'returned_at' => 'datetime',
        ];
    }
}
