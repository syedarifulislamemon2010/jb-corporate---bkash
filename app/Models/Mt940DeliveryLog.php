<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mt940DeliveryLog extends Model
{
    protected $table = 'mt940_delivery_logs';

    protected $fillable = [
        'account_no',
        'statement_date',
        'file_name',
        'status',
        'is_ok',
        'delivered_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_ok'          => 'boolean',
            'statement_date' => 'date',
            'delivered_at'   => 'datetime',
        ];
    }
}
