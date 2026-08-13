<?php

namespace App\Models;

use App\Models\User;
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

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }
}
