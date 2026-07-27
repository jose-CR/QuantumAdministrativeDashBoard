<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ArticleUnit;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticleUnitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ArticleUnit');
    }

    public function view(AuthUser $authUser, ArticleUnit $articleUnit): bool
    {
        return $authUser->can('View:ArticleUnit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ArticleUnit');
    }

    public function update(AuthUser $authUser, ArticleUnit $articleUnit): bool
    {
        return $authUser->can('Update:ArticleUnit');
    }

    public function delete(AuthUser $authUser, ArticleUnit $articleUnit): bool
    {
        return $authUser->can('Delete:ArticleUnit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ArticleUnit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ArticleUnit');
    }

}