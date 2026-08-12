<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BkashTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class BkashTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BkashTransaction');
    }

    public function view(AuthUser $authUser, BkashTransaction $bkashTransaction): bool
    {
        return $authUser->can('View:BkashTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BkashTransaction');
    }

    public function update(AuthUser $authUser, BkashTransaction $bkashTransaction): bool
    {
        return $authUser->can('Update:BkashTransaction');
    }

    public function delete(AuthUser $authUser, BkashTransaction $bkashTransaction): bool
    {
        return $authUser->can('Delete:BkashTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BkashTransaction');
    }

    public function restore(AuthUser $authUser, BkashTransaction $bkashTransaction): bool
    {
        return $authUser->can('Restore:BkashTransaction');
    }

    public function forceDelete(AuthUser $authUser, BkashTransaction $bkashTransaction): bool
    {
        return $authUser->can('ForceDelete:BkashTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BkashTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BkashTransaction');
    }

    public function replicate(AuthUser $authUser, BkashTransaction $bkashTransaction): bool
    {
        return $authUser->can('Replicate:BkashTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BkashTransaction');
    }

}