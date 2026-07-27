<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Credit;
use Illuminate\Auth\Access\HandlesAuthorization;

class CreditPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Credit');
    }

    public function view(AuthUser $authUser, Credit $credit): bool
    {
        return $authUser->can('View:Credit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Credit');
    }

    public function update(AuthUser $authUser, Credit $credit): bool
    {
        return $authUser->can('Update:Credit');
    }

    public function delete(AuthUser $authUser, Credit $credit): bool
    {
        return $authUser->can('Delete:Credit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Credit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Credit');
    }

}