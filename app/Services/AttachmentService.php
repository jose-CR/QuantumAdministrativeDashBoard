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
        string $disk = 'attachments',
    ): Attachment {
        $directory = $this->getDirectory($record);
        $originalName = $file->getClientOriginalName();
        $storage = Storage::disk($disk);
        $filename = $this->getUniqueFilename($storage, $directory, $originalName);
        $path = $file->storeAs($directory, $filename, $disk);

        return $record->attachments()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function storeFromPath(
        Model $record,
        string $temporaryPath,
        ?string $originalName = null,
        string $collection = 'default',
        string $disk = 'attachments',
    ): Attachment {
        $storage = Storage::disk($disk);

        if (! $storage->exists($temporaryPath)) {
            throw new \RuntimeException("El archivo no existe: {$temporaryPath}");
        }

        $directory = $this->getDirectory($record);
        $originalName ??= basename($temporaryPath);
        $filename = $this->getUniqueFilename($storage, $directory, $originalName);
        $finalPath = $directory . '/' . $filename;

        $storage->move($temporaryPath, $finalPath);

        return $record->attachments()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $finalPath,
            'original_name' => $originalName,
            'mime_type' => $storage->mimeType($finalPath),
            'size' => $storage->size($finalPath),
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
        $originalName = $file->getClientOriginalName();
        $filename = $this->getUniqueFilename($disk, $directory, $originalName);
        $path = $file->storeAs($directory, $filename, $attachment->disk);

        $attachment->update([
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return $attachment->refresh();
    }

    /**
     * Reemplaza el archivo físico de un adjunto existente,
     * a partir de un path temporal (flujo FileUpload de Filament).
     */
    public function replaceFromPath(
        Attachment $attachment,
        string $temporaryPath,
        ?string $originalName = null,
    ): Attachment {
        $storage = Storage::disk($attachment->disk);

        if (! $storage->exists($temporaryPath)) {
            throw new \RuntimeException("El archivo no existe: {$temporaryPath}");
        }

        if ($storage->exists($attachment->path)) {
            $storage->delete($attachment->path);
        }

        $directory = dirname($attachment->path);
        $originalName ??= basename($temporaryPath);
        $filename = $this->getUniqueFilename($storage, $directory, $originalName);
        $finalPath = $directory . '/' . $filename;

        $storage->move($temporaryPath, $finalPath);

        $attachment->update([
            'path' => $finalPath,
            'original_name' => $originalName,
            'mime_type' => $storage->mimeType($finalPath),
            'size' => $storage->size($finalPath),
        ]);

        return $attachment->refresh();
    }

    public function attachFromPath(
        Model $record,
        string $path,
        string $disk = 'attachments',
        string $collection = 'default',
    ): Attachment {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new \RuntimeException("El archivo no existe: {$path}");
        }

        return $record->attachments()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => $storage->mimeType($path),
            'size' => $storage->size($path),
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

    protected function getDirectory(Model $record): string
    {
        $model = Str::snake(class_basename($record));
        $identifier = $this->getRecordIdentifier($record);

        return $model . '/' . $record->getKey() . '-' . $identifier;
    }

    protected function getRecordIdentifier(Model $record): string
    {
        $attributes = ['full_name', 'name', 'title', 'description', 'code'];

        foreach ($attributes as $attribute) {
            if (isset($record->{$attribute}) && filled($record->{$attribute})) {
                return Str::slug($record->{$attribute});
            }
        }

        return 'record';
    }

    protected function getUniqueFilename($storage, string $directory, string $originalName): string
    {
        $originalName = basename($originalName);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = $originalName;
        $counter = 1;

        while ($storage->exists($directory . '/' . $filename)) {
            $filename = $name . '-' . $counter;

            if ($extension) {
                $filename .= '.' . $extension;
            }

            $counter++;
        }

        return $filename;
    }
}