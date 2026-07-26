<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $connection = 'oracle_settings';
    protected $table = 'branches';
    protected $primaryKey = 'branchid';
}
