<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Installment;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Installment');
    }

    public function view(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('View:Installment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Installment');
    }

    public function update(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('Update:Installment');
    }

    public function delete(AuthUser $authUser, Installment $installment): bool
    {
        return $authUser->can('Delete:Installment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Installment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Installment');
    }

}