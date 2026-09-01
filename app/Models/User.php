<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasRoles;
    use HasPanelShield;
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        if ($this->hasRole(Utils::getPanelUserRoleName())) {
            return true;
        }

        // Allow any user with an assigned banking role (e.g. bkash_checker, bkash_authorizer_1, bkash_authorizer_2)
        if ($this->roles()->exists()) {
            return true;
        }

        // In local and testing environments (e.g. fresh clone), allow seeded/development users
        return app()->environment('local', 'testing');
    }

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

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization', 'label');
    }
}
