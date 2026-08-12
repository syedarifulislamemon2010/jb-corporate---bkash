<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BkashFailedTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class BkashFailedTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BkashFailedTransaction');
    }

    public function view(AuthUser $authUser, BkashFailedTransaction $bkashFailedTransaction): bool
    {
        return $authUser->can('View:BkashFailedTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BkashFailedTransaction');
    }

    public function update(AuthUser $authUser, BkashFailedTransaction $bkashFailedTransaction): bool
    {
        return $authUser->can('Update:BkashFailedTransaction');
    }

    public function delete(AuthUser $authUser, BkashFailedTransaction $bkashFailedTransaction): bool
    {
        return $authUser->can('Delete:BkashFailedTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BkashFailedTransaction');
    }

    public function restore(AuthUser $authUser, BkashFailedTransaction $bkashFailedTransaction): bool
    {
        return $authUser->can('Restore:BkashFailedTransaction');
    }

    public function forceDelete(AuthUser $authUser, BkashFailedTransaction $bkashFailedTransaction): bool
    {
        return $authUser->can('ForceDelete:BkashFailedTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BkashFailedTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BkashFailedTransaction');
    }

    public function replicate(AuthUser $authUser, BkashFailedTransaction $bkashFailedTransaction): bool
    {
        return $authUser->can('Replicate:BkashFailedTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BkashFailedTransaction');
    }

}