<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PayrollPeriod;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPeriodPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PayrollPeriod');
    }

    public function view(AuthUser $authUser, PayrollPeriod $payrollPeriod): bool
    {
        return $authUser->can('View:PayrollPeriod');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PayrollPeriod');
    }

    public function update(AuthUser $authUser, PayrollPeriod $payrollPeriod): bool
    {
        return $authUser->can('Update:PayrollPeriod');
    }

    public function delete(AuthUser $authUser, PayrollPeriod $payrollPeriod): bool
    {
        return $authUser->can('Delete:PayrollPeriod');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PayrollPeriod');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PayrollPeriod');
    }

}