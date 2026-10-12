<?php

namespace App\Services\Attachments;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AttachmentStorage
{
    public function __construct(
        protected UniqueFilename $filenames,
    ) {}

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    public function assertExists(string $disk, string $path): void
    {
        if (! $this->exists($disk, $path)) {
            throw new RuntimeException("El archivo no existe: {$path}");
        }
    }

    public function uniqueName(string $disk, string $directory, string $originalName): string
    {
        return $this->filenames->resolve(Storage::disk($disk), $directory, $originalName);
    }

    /** Mueve un temporal al directorio final y devuelve el path resultante. */
    public function moveIn(string $disk, string $temporaryPath, string $directory, string $originalName): string
    {
        $this->assertExists($disk, $temporaryPath);

        $finalPath = $directory . '/' . $this->uniqueName($disk, $directory, $originalName);

        Storage::disk($disk)->move($temporaryPath, $finalPath);

        return $finalPath;
    }

    public function putUploaded(string $disk, UploadedFile $file, string $directory, string $originalName): string
    {
        return $file->storeAs(
            $directory,
            $this->uniqueName($disk, $directory, $originalName),
            $disk,
        );
    }

    public function delete(string $disk, string $path): void
    {
        $storage = Storage::disk($disk);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    /** @return array{mime_type: string|false, size: int} */
    public function metadata(string $disk, string $path): array
    {
        $storage = Storage::disk($disk);

        return [
            'mime_type' => $storage->mimeType($path),
            'size' => $storage->size($path),
        ];
    }

    /**
     * Borra la carpeta y sus padres si quedaron sin archivos.
     * Nunca toca la carpeta de primer nivel (la del modelo) ni la raíz del disco.
     */
    public function pruneEmptyDirectories(string $disk, string $directory): void
    {
        $storage = Storage::disk($disk);

        while (str_contains($directory, '/')) {
            if ($storage->allFiles($directory) !== []) {
                return;
            }

            $storage->deleteDirectory($directory);
            $directory = dirname($directory);
        }
    }
}