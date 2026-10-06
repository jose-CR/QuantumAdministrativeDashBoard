<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AttachmentSyncService
{
    public function __construct(
        protected AttachmentService $attachments,
    ) {}

    /**
     * Sincroniza los adjuntos de un registro con el estado de un FileUpload:
     * elimina los que ya no están y registra los archivos nuevos.
     */
    public function sync(
        Model $record,
        mixed $paths,
        array $fileNames = [],
        ?string $collection = null,
        string $disk = 'attachments',
    ): void {
        $paths = $this->normalizePaths($paths);
        $collection ??= (string) str($record->getTable())->snake();
        $storage = Storage::disk($disk);

        $current = $record->attachments()->get();
        $retained = $this->retained($current, $paths, $storage);

        $this->deleteRemoved($current, $retained);
        $this->storeNew($record, $paths, $retained, $fileNames, $collection, $disk, $storage);
    }

    protected function normalizePaths(mixed $paths): Collection
    {
        return collect(is_array($paths) ? $paths : [$paths])
            ->filter(fn ($path) => filled($path))
            ->values();
    }

    /**
     * Un path corresponde a un adjunto ya registrado si coincide exacto, o si
     * Filament devolvió solo el basename Y el path no existe físicamente en el
     * disco (si existe, es un temporal nuevo aunque comparta nombre).
     */
    protected function isSameFile(Filesystem $storage, string $path, Attachment $attachment): bool
    {
        return $path === $attachment->path
            || (
                basename($path) === basename($attachment->path)
                && ! $storage->exists($path)
            );
    }

    protected function retained(Collection $current, Collection $paths, Filesystem $storage): Collection
    {
        return $current->filter(
            fn (Attachment $attachment) => $paths->contains(
                fn (string $path) => $this->isSameFile($storage, $path, $attachment)
            )
        );
    }

    protected function deleteRemoved(Collection $current, Collection $retained): void
    {
        $current
            ->reject(fn (Attachment $attachment) => $retained->contains('id', $attachment->id))
            ->each(fn (Attachment $attachment) => $this->attachments->delete($attachment));
    }

    protected function storeNew(
        Model $record,
        Collection $paths,
        Collection $retained,
        array $fileNames,
        string $collection,
        string $disk,
        Filesystem $storage,
    ): void {
        foreach ($paths as $path) {
            $exists = $retained->contains(
                fn (Attachment $attachment) => $this->isSameFile($storage, $path, $attachment)
            );

            if ($exists) {
                continue;
            }

            $this->attachments->storeFromPath(
                record: $record,
                temporaryPath: $path,
                originalName: $fileNames[$path] ?? basename($path),
                collection: $collection,
                disk: $disk,
            );
        }
    }
}