<?php

namespace App\Services;

use App\Models\Attachment;
use App\Services\Attachments\AttachmentPathResolver;
use App\Services\Attachments\AttachmentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    public function __construct(
        protected AttachmentPathResolver $paths,
        protected AttachmentStorage $files,
    ) {}

    public function store(
        Model $record,
        UploadedFile $file,
        string $collection = 'default',
        string $disk = 'attachments',
    ): Attachment {
        $originalName = $file->getClientOriginalName();
        $path = $this->files->putUploaded($disk, $file, $this->paths->directory($record), $originalName);

        return $this->register($record, $path, $originalName, $collection, $disk);
    }

    public function storeFromPath(
        Model $record,
        string $temporaryPath,
        ?string $originalName = null,
        string $collection = 'default',
        string $disk = 'attachments',
    ): Attachment {
        $originalName ??= basename($temporaryPath);
        $path = $this->files->moveIn($disk, $temporaryPath, $this->paths->directory($record), $originalName);

        return $this->register($record, $path, $originalName, $collection, $disk);
    }

    public function attachFromPath(
        Model $record,
        string $path,
        string $disk = 'attachments',
        string $collection = 'default',
    ): Attachment {
        $this->files->assertExists($disk, $path);

        return $this->register($record, $path, basename($path), $collection, $disk);
    }

    public function replace(Attachment $attachment, UploadedFile $file): Attachment
    {
        $originalName = $file->getClientOriginalName();

        $this->files->delete($attachment->disk, $attachment->path);

        $path = $this->files->putUploaded($attachment->disk, $file, dirname($attachment->path), $originalName);

        return $this->updateFile($attachment, $path, $originalName);
    }

    /**
     * Reemplaza el archivo físico a partir de un temporal (flujo FileUpload de Filament).
     */
    public function replaceFromPath(
        Attachment $attachment,
        string $temporaryPath,
        ?string $originalName = null,
    ): Attachment {
        // Se valida antes de borrar el archivo actual para no perderlo si el temporal falta.
        $this->files->assertExists($attachment->disk, $temporaryPath);

        $originalName ??= basename($temporaryPath);

        $this->files->delete($attachment->disk, $attachment->path);

        $path = $this->files->moveIn($attachment->disk, $temporaryPath, dirname($attachment->path), $originalName);

        return $this->updateFile($attachment, $path, $originalName);
    }

    public function delete(Attachment $attachment): void
    {
        $this->files->delete($attachment->disk, $attachment->path);
        $attachment->delete();
    }

    protected function register(
        Model $record,
        string $path,
        string $originalName,
        string $collection,
        string $disk,
    ): Attachment {
        return $record->attachments()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            ...$this->files->metadata($disk, $path),
        ]);
    }

    protected function updateFile(Attachment $attachment, string $path, string $originalName): Attachment
    {
        $attachment->update([
            'path' => $path,
            'original_name' => $originalName,
            ...$this->files->metadata($attachment->disk, $path),
        ]);

        return $attachment->refresh();
    }
}