<?php

namespace App\Filament\Concerns;

use App\Services\AttachmentSyncService;

/**
 * Adjuntos de un documento con el FileUpload de Filament.
 *
 * El campo tiene que estar `dehydrated(true)`: Filament guarda los archivos en
 * el disco al deshidratar y deja el path en el estado. Con `dehydrated(false)`
 * el archivo nunca se escribe y el adjunto queda en nada.
 *
 * Los paths se copian antes de guardar (mutateFormDataBefore*) porque la clave
 * no es una columna del modelo, y se registran después, cuando el registro ya
 * existe.
 */
trait HasAttachmentsSync
{
    /** @var array<int, string> */
    protected array $attachmentPaths = [];

    /** @var array<string, string> */
    protected array $attachmentFileNames = [];

    protected function captureAttachments(array $data): array
    {
        $this->attachmentPaths = array_values(array_filter((array) ($data['attachment_uploads'] ?? [])));
        $this->attachmentFileNames = (array) ($data['file_names'] ?? []);

        unset($data['attachment_uploads'], $data['file_names']);

        return $data;
    }

    protected function syncAttachments(
        string $field = 'attachment_uploads',
        ?string $collection = null
    ): void {
        $paths = $this->attachmentPaths
            ?: collect((array) ($this->form->getRawState()[$field] ?? []))->filter()->values()->all();

        app(AttachmentSyncService::class)->sync(
            record: $this->record,
            paths: $paths,
            fileNames: $this->attachmentFileNames,
            collection: $collection,
        );

        $this->attachmentPaths = [];
        $this->attachmentFileNames = [];
    }
}
