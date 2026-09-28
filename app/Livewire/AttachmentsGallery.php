<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;

class AttachmentsGallery extends Component
{
    use WithFileUploads;

    public Model $record;

    public string $collection = 'default';

    public $file = null;

    public ?int $editingAttachmentId = null;

    #[Computed]
    public function attachments()
    {
        return $this->record
            ->attachments()
            ->where('collection', $this->collection)
            ->latest()
            ->get();
    }

    public function upload(AttachmentService $service): void
    {
        $this->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
        ]);

        $service->store(
            $this->record,
            $this->file,
            $this->collection,
            'public',
        );

        $this->reset('file');

        unset($this->attachments);

        $this->dispatch('attachment-uploaded');
    }

    public function startReplace(int $attachmentId): void
    {
        $this->attachment($attachmentId);

        $this->editingAttachmentId = $attachmentId;
        $this->reset('file');
    }

    public function replace(AttachmentService $service): void
    {
        $this->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
        ]);

        $attachment = $this->attachment($this->editingAttachmentId);

        $service->replace($attachment, $this->file);

        $this->reset([
            'file',
            'editingAttachmentId',
        ]);

        unset($this->attachments);

        $this->dispatch('attachment-replaced');
    }

    public function delete(int $attachmentId, AttachmentService $service): void
    {
        $attachment = $this->attachment($attachmentId);

        $service->delete($attachment);

        unset($this->attachments);

        $this->dispatch('attachment-deleted');
    }

    protected function attachment(?int $attachmentId): Attachment
    {
        abort_unless($attachmentId, 404);

        return $this->record
            ->attachments()
            ->where('collection', $this->collection)
            ->whereKey($attachmentId)
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.attachments-gallery');
    }
}