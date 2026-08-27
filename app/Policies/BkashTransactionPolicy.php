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

    /**
     * Determine if the user can perform Checker verification on the transaction.
     */
    public function check(AuthUser $authUser, BkashTransaction $bkashTransaction): \Illuminate\Auth\Access\Response
    {
        if ($bkashTransaction->status_id !== BkashTransaction::STATUS_PENDING_CHECKER) {
            return \Illuminate\Auth\Access\Response::deny('Transaction is not in Pending Checker state.');
        }

        return \Illuminate\Auth\Access\Response::allow();
    }

    /**
     * Determine if the user can perform 1st-level authorization on the transaction.
     * Segregation of Duties: User who checked the file cannot authorize it.
     */
    public function authorize(AuthUser $authUser, BkashTransaction $bkashTransaction): \Illuminate\Auth\Access\Response
    {
        if ($bkashTransaction->status_id !== BkashTransaction::STATUS_CHECKED) {
            return \Illuminate\Auth\Access\Response::deny('Transaction is not in Checked state awaiting 1st authorization.');
        }

        // Self-Approval Prevention: Checker != 1st Authorizer
        $isChecker = ($authUser->id && $bkashTransaction->checked_by_id && (int) $authUser->id === (int) $bkashTransaction->checked_by_id) ||
                     ($authUser->name && $bkashTransaction->checked_by && $authUser->name === $bkashTransaction->checked_by);

        if ($isChecker) {
            return \Illuminate\Auth\Access\Response::deny('You checked this file; 1st authorization must come from a different user.');
        }

        return \Illuminate\Auth\Access\Response::allow();
    }

    /**
     * Determine if the user can perform 2nd-level / final confirmation on the transaction.
     * Segregation of Duties: 2nd Authorizer cannot be the Checker or 1st Authorizer (3 distinct individuals required).
     */
    public function confirm(AuthUser $authUser, BkashTransaction $bkashTransaction): \Illuminate\Auth\Access\Response
    {
        if ($bkashTransaction->status_id !== BkashTransaction::STATUS_AUTH_1_APPROVED) {
            return \Illuminate\Auth\Access\Response::deny('Transaction is not in 1st-Authorized state awaiting final confirmation.');
        }

        // Self-Approval Prevention: Confirmer != Checker
        $isChecker = ($authUser->id && $bkashTransaction->checked_by_id && (int) $authUser->id === (int) $bkashTransaction->checked_by_id) ||
                     ($authUser->name && $bkashTransaction->checked_by && $authUser->name === $bkashTransaction->checked_by);

        if ($isChecker) {
            return \Illuminate\Auth\Access\Response::deny('You checked this file; final confirmation must come from a third distinct user.');
        }

        // Self-Approval Prevention: Confirmer != 1st Authorizer
        $isAuth1 = ($authUser->id && $bkashTransaction->approved_by_1_id && (int) $authUser->id === (int) $bkashTransaction->approved_by_1_id) ||
                   ($authUser->name && $bkashTransaction->approved_by_1 && $authUser->name === $bkashTransaction->approved_by_1);

        if ($isAuth1) {
            return \Illuminate\Auth\Access\Response::deny('You 1st-authorized this file; final confirmation must come from a third distinct user.');
        }

        return \Illuminate\Auth\Access\Response::allow();
    }
}