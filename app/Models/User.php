<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;

class User extends Authenticatable implements FilamentUser
{
    use HasRoles;
    use HasPanelShield; // handles canAccessPanel() automatically
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_no',
        'organization',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship with Organization Model
     */
    public function organizationRelation(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization', 'label');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization', 'label');
    }

//    public function permissions()
//    {
//        return $this->belongsToMany(
//            Permission::class,
//            'organization_permissions'
//        );
//    }
//
//    public function hasOrganizationPermission(string $permission): bool
//    {
//        return $this->organization
//            ->permissions()
//            ->where('name', $permission)
//            ->exists();
//    }
}
