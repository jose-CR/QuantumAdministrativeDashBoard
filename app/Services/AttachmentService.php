<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public function store(
        Model $record,
        UploadedFile $file,
        string $collection = 'default',
        string $disk = 'public',
    ): Attachment {
        $directory = 'attachments/' . Str::snake(class_basename($record));

        $path = $file->store($directory, $disk);

        return $record->attachments()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function replace(
        Attachment $attachment,
        UploadedFile $file,
    ): Attachment {
        $disk = Storage::disk($attachment->disk);

        if ($disk->exists($attachment->path)) {
            $disk->delete($attachment->path);
        }

        $directory = dirname($attachment->path);

        $path = $file->store($directory, $attachment->disk);

        $attachment->update([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return $attachment->refresh();
    }

    public function attachFromPath(
    Model $record,
    string $path,
    string $disk = 'public',
    string $collection = 'default',
    ): Attachment {
        $diskInstance = Storage::disk($disk);

        return $record->attachments()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => $diskInstance->mimeType($path),
            'size' => $diskInstance->size($path),
        ]);
}

    public function delete(Attachment $attachment): void
    {
        $disk = Storage::disk($attachment->disk);

        if ($disk->exists($attachment->path)) {
            $disk->delete($attachment->path);
        }

        $attachment->delete();
    }
}