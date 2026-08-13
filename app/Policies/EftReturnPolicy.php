<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EftReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

class EftReturnPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EftReturn');
    }

    public function view(AuthUser $authUser, EftReturn $eftReturn): bool
    {
        return $authUser->can('View:EftReturn');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EftReturn');
    }

    public function update(AuthUser $authUser, EftReturn $eftReturn): bool
    {
        return $authUser->can('Update:EftReturn');
    }

    public function delete(AuthUser $authUser, EftReturn $eftReturn): bool
    {
        return $authUser->can('Delete:EftReturn');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EftReturn');
    }

    public function restore(AuthUser $authUser, EftReturn $eftReturn): bool
    {
        return $authUser->can('Restore:EftReturn');
    }

    public function forceDelete(AuthUser $authUser, EftReturn $eftReturn): bool
    {
        return $authUser->can('ForceDelete:EftReturn');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EftReturn');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EftReturn');
    }

    public function replicate(AuthUser $authUser, EftReturn $eftReturn): bool
    {
        return $authUser->can('Replicate:EftReturn');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EftReturn');
    }

}