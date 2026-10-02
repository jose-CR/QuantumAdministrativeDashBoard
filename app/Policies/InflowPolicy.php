<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Inflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class InflowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Inflow');
    }

    public function view(AuthUser $authUser, Inflow $inflow): bool
    {
        return $authUser->can('View:Inflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Inflow');
    }

    public function update(AuthUser $authUser, Inflow $inflow): bool
    {
        return $authUser->can('Update:Inflow');
    }

    public function delete(AuthUser $authUser, Inflow $inflow): bool
    {
        return $authUser->can('Delete:Inflow');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Inflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Inflow');
    }

}