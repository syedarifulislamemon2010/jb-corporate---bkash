<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostingAttempt extends Model
{
    protected $table = 'posting_attempts';
    protected $primaryKey = 'txn_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'txn_id',
        'instruction_id',
        'channel',
        'outcome',
        'external_ref',
    ];
}
