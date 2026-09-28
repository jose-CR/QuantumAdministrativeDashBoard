<?php

namespace App\Filament\Concerns;

use App\Services\AttachmentService;

trait HasAttachmentsSync
{
    protected function syncAttachments(string $field = 'attachment_uploads', ?string $collection = null): void
    {
        $paths = $this->data[$field] ?? [];
        $collection ??= str($this->record->getTable())->snake();
        $service = app(AttachmentService::class);

        $this->record->attachments()
            ->whereNotIn('path', $paths)
            ->get()
            ->each(fn ($attachment) => $service->delete($attachment));

        $existing = $this->record->attachments()->pluck('path')->toArray();
        foreach (array_diff($paths, $existing) as $path) {
            $service->attachFromPath($this->record, $path, collection: $collection);
        }
    }
}