<?php

namespace App\Services\Attachments;

use Illuminate\Contracts\Filesystem\Filesystem;

class UniqueFilename
{
    public function resolve(Filesystem $storage, string $directory, string $originalName): string
    {
        $originalName = basename($originalName);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        $filename = $originalName;
        $counter = 1;

        while ($storage->exists($directory . '/' . $filename)) {
            $filename = $name . '-' . $counter . ($extension ? '.' . $extension : '');
            $counter++;
        }

        return $filename;
    }
}