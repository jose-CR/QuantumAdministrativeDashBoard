<?php

namespace App\Services\Attachments;

use App\Models\Attachment;

class AttachmentRelocator
{
    public function __construct(
        protected AttachmentPathResolver $paths,
        protected AttachmentStorage $files,
    ) {}

    /** Devuelve el nuevo path, o null si no hay nada que mover. */
    public function relocate(Attachment $attachment, bool $dryRun = false): ?string
    {
        $record = $attachment->attachable;

        if (! $record) {
            return null;
        }

        $directory = $this->paths->directory($record);
        $oldPath = $attachment->path;

        if (dirname($oldPath) === $directory || ! $this->files->exists($attachment->disk, $oldPath)) {
            return null;
        }

        if ($dryRun) {
            return $directory . '/' . $this->files->uniqueName($attachment->disk, $directory, basename($oldPath));
        }

        $newPath = $this->files->moveIn($attachment->disk, $oldPath, $directory, basename($oldPath));

        $attachment->update(['path' => $newPath]);
        $this->files->pruneEmptyDirectories($attachment->disk, dirname($oldPath));

        return $newPath;
    }
}