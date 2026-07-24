<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PaymentHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentHistoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PaymentHistory');
    }

    public function view(AuthUser $authUser, PaymentHistory $paymentHistory): bool
    {
        return $authUser->can('View:PaymentHistory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PaymentHistory');
    }

    public function update(AuthUser $authUser, PaymentHistory $paymentHistory): bool
    {
        return $authUser->can('Update:PaymentHistory');
    }

    public function delete(AuthUser $authUser, PaymentHistory $paymentHistory): bool
    {
        return $authUser->can('Delete:PaymentHistory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PaymentHistory');
    }

    public function restore(AuthUser $authUser, PaymentHistory $paymentHistory): bool
    {
        return $authUser->can('Restore:PaymentHistory');
    }

    public function forceDelete(AuthUser $authUser, PaymentHistory $paymentHistory): bool
    {
        return $authUser->can('ForceDelete:PaymentHistory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PaymentHistory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PaymentHistory');
    }

    public function replicate(AuthUser $authUser, PaymentHistory $paymentHistory): bool
    {
        return $authUser->can('Replicate:PaymentHistory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PaymentHistory');
    }

}