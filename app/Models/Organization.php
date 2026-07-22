<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $connection = 'oracle';
    protected $table = 'organizations';
    protected $primaryKey = 'id';
    protected $softDelete = true;

    protected $fillable = [
        'name','mobile_no','address','organization_type','ip_address',
        'status_id','created_by'
    ];
}
