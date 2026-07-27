<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\TranslationLoader\LanguageLine;
use Illuminate\Auth\Access\HandlesAuthorization;

class LanguageLinePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LanguageLine');
    }

    public function view(AuthUser $authUser, LanguageLine $languageLine): bool
    {
        return $authUser->can('View:LanguageLine');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LanguageLine');
    }

    public function update(AuthUser $authUser, LanguageLine $languageLine): bool
    {
        return $authUser->can('Update:LanguageLine');
    }

    public function delete(AuthUser $authUser, LanguageLine $languageLine): bool
    {
        return $authUser->can('Delete:LanguageLine');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LanguageLine');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LanguageLine');
    }

}