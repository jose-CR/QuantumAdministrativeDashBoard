<?php

namespace App\Policies;

use App\Models\ClientReference;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ClientReferencePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ClientReference');
    }

    public function view(AuthUser $authUser, ClientReference $clientReference): bool
    {
        return $authUser->can('View:ClientReference');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ClientReference');
    }

    public function update(AuthUser $authUser, ClientReference $clientReference): bool
    {
        return $authUser->can('Update:ClientReference');
    }

    public function delete(AuthUser $authUser, ClientReference $clientReference): bool
    {
        return $authUser->can('Delete:ClientReference');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ClientReference');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ClientReference');
    }
}