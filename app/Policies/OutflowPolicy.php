<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Outflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutflowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Outflow');
    }

    public function view(AuthUser $authUser, Outflow $outflow): bool
    {
        return $authUser->can('View:Outflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Outflow');
    }

    public function update(AuthUser $authUser, Outflow $outflow): bool
    {
        return $authUser->can('Update:Outflow');
    }

    public function delete(AuthUser $authUser, Outflow $outflow): bool
    {
        return $authUser->can('Delete:Outflow');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Outflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Outflow');
    }

}