<?php

namespace App\Filament\Concerns;

use App\Services\AttachmentSyncService;

trait HasAttachmentsSync
{
    protected function syncAttachments(
        string $field = 'attachment_uploads',
        ?string $collection = null
    ): void {
        $state = $this->form->getRawState();

        app(AttachmentSyncService::class)->sync(
            record: $this->record,
            paths: $state[$field] ?? [],
            fileNames: $state['file_names'] ?? [],
            collection: $collection,
        );
    }
}