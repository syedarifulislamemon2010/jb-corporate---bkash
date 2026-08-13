<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BkashTransactionBatch;
use Illuminate\Auth\Access\HandlesAuthorization;

class BkashTransactionBatchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BkashTransactionBatch');
    }

    public function view(AuthUser $authUser, BkashTransactionBatch $bkashTransactionBatch): bool
    {
        return $authUser->can('View:BkashTransactionBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BkashTransactionBatch');
    }

    public function update(AuthUser $authUser, BkashTransactionBatch $bkashTransactionBatch): bool
    {
        return $authUser->can('Update:BkashTransactionBatch');
    }

    public function delete(AuthUser $authUser, BkashTransactionBatch $bkashTransactionBatch): bool
    {
        return $authUser->can('Delete:BkashTransactionBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BkashTransactionBatch');
    }

    public function restore(AuthUser $authUser, BkashTransactionBatch $bkashTransactionBatch): bool
    {
        return $authUser->can('Restore:BkashTransactionBatch');
    }

    public function forceDelete(AuthUser $authUser, BkashTransactionBatch $bkashTransactionBatch): bool
    {
        return $authUser->can('ForceDelete:BkashTransactionBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BkashTransactionBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BkashTransactionBatch');
    }

    public function replicate(AuthUser $authUser, BkashTransactionBatch $bkashTransactionBatch): bool
    {
        return $authUser->can('Replicate:BkashTransactionBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BkashTransactionBatch');
    }

}