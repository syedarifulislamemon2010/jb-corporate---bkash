<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $connection = 'oracle_settings';
    protected $table = 'banks';
    protected $primaryKey = 'bankid';
}
