<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Transportation;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransportationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Transportation');
    }

    public function view(AuthUser $authUser, Transportation $transportation): bool
    {
        return $authUser->can('View:Transportation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Transportation');
    }

    public function update(AuthUser $authUser, Transportation $transportation): bool
    {
        return $authUser->can('Update:Transportation');
    }

    public function delete(AuthUser $authUser, Transportation $transportation): bool
    {
        return $authUser->can('Delete:Transportation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Transportation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Transportation');
    }

}