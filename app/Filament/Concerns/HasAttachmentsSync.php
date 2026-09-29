<?php

namespace App\Filament\Concerns;

use App\Services\AttachmentService;

trait HasAttachmentsSync
{
    protected function syncAttachments(
        string $field = 'attachment_uploads',
        ?string $collection = null
    ): void {
        $state = $this->form->getRawState();

        $paths = $state[$field] ?? [];
        $fileNames = $state['file_names'] ?? [];

        if (! is_array($paths)) {
            $paths = [$paths];
        }

        $paths = array_values(
            array_filter($paths, fn ($path) => filled($path))
        );

        $collection ??= str($this->record->getTable())->snake();

        $service = app(AttachmentService::class);

        /*
         * Adjuntos actualmente registrados en la base de datos.
         */
        $attachments = $this->record
            ->attachments()
            ->get();

        /*
         * Determinar cuáles adjuntos existentes siguen presentes
         * en el FileUpload.
         *
         * Filament puede devolver el path completo o solamente
         * el nombre del archivo, por eso comprobamos ambos casos.
         */
        $retainedAttachments = $attachments->filter(
            function ($attachment) use ($paths) {
                foreach ($paths as $path) {
                    if (
                        $path === $attachment->path ||
                        basename($path) === basename($attachment->path)
                    ) {
                        return true;
                    }
                }

                return false;
            }
        );

        /*
         * Eliminar los adjuntos que ya no están seleccionados.
         */
        $attachments
            ->reject(
                fn ($attachment) =>
                    $retainedAttachments->contains(
                        'id',
                        $attachment->id
                    )
            )
            ->each(
                fn ($attachment) =>
                    $service->delete($attachment)
            );

        /*
         * Paths físicos de los archivos que ya existen.
         */
        $existingPaths = $retainedAttachments
            ->pluck('path')
            ->toArray();

        /*
         * Procesar solamente archivos nuevos.
         */
        foreach ($paths as $path) {
            $isExisting = $retainedAttachments->contains(
                function ($attachment) use ($path) {
                    return (
                        $path === $attachment->path ||
                        basename($path) === basename($attachment->path)
                    );
                }
            );

            if ($isExisting) {
                continue;
            }

            $originalName = $fileNames[$path]
                ?? basename($path);

            $service->storeFromPath(
                record: $this->record,
                temporaryPath: $path,
                originalName: $originalName,
                collection: $collection,
                disk: 'attachments',
            );
        }
    }
}