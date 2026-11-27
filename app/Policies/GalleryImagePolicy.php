<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GalleryImage;
use Illuminate\Auth\Access\HandlesAuthorization;

class GalleryImagePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GalleryImage');
    }

    public function view(AuthUser $authUser, GalleryImage $galleryImage): bool
    {
        return $authUser->can('View:GalleryImage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GalleryImage');
    }

    public function update(AuthUser $authUser, GalleryImage $galleryImage): bool
    {
        return $authUser->can('Update:GalleryImage');
    }

    public function delete(AuthUser $authUser, GalleryImage $galleryImage): bool
    {
        return $authUser->can('Delete:GalleryImage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GalleryImage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GalleryImage');
    }

}