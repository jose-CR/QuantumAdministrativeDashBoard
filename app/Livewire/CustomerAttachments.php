<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Installment;
use App\Services\AttachmentService;
use App\Support\CreditStatus;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Adjuntos del cliente, separados en tres tabs:
 *
 *  - Créditos: lo que se subió a cada crédito (boleta firmada, foto del bien).
 *  - Cuotas: los comprobantes de cada cuota (voucher, recibo).
 *  - Sueltos: facturas e imágenes del cliente que no son de ningún crédito.
 *
 * Se puede subir desde cualquier tab eligiendo el destino, y borrar lo que sea de
 * este cliente (nunca de otro).
 */
class CustomerAttachments extends Component
{
    use WithFileUploads;

    public Customer $record;

    public string $tab = 'credits';

    /** A qué se adjunta el próximo archivo. */
    public string $target = 'credit';

    public ?int $targetId = null;

    public $file = null;

    public function mount(): void
    {
        $this->target = 'credit';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['credits', 'installments', 'loose'], true)
            ? $tab
            : 'credits';

        $this->target = match ($this->tab) {
            'credits' => 'credit',
            'installments' => 'installment',
            default => 'customer',
        };

        $this->reset('targetId', 'file');
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'credits' => $this->creditAttachmentsQuery()->count(),
            'installments' => $this->installmentAttachmentsQuery()->count(),
            'loose' => $this->record->attachments()->count(),
        ];
    }

    /**
     * Grupos a mostrar en el tab activo: título + archivos.
     *
     * @return array<int, array{titulo: string, archivos: \Illuminate\Support\Collection<int, Attachment>}>
     */
    #[Computed]
    public function grupos(): array
    {
        return match ($this->tab) {
            'credits' => $this->porCredito(),
            'installments' => $this->porCuota(),
            default => $this->sueltos(),
        };
    }

    /**
     * Créditos del cliente, para elegir el destino al subir.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function creditos(): array
    {
        return $this->record->credits()
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (Credit $credito): array => [
                $credito->id => 'Crédito #' . $credito->id . ' · ' . CreditStatus::label($credito->status),
            ])
            ->all();
    }

    /**
     * Cuotas de los créditos del cliente, para elegir el destino al subir.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function cuotas(): array
    {
        return Installment::query()
            ->whereIn('credit_id', $this->record->credits()->select('id'))
            ->orderBy('credit_id')
            ->orderBy('number')
            ->get()
            ->mapWithKeys(fn (Installment $cuota): array => [
                $cuota->id => "Crédito #{$cuota->credit_id} · Cuota #{$cuota->number} · vence " . $cuota->due_date?->format('d/m/Y'),
            ])
            ->all();
    }

    public function upload(AttachmentService $service): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
            'targetId' => [$this->target === 'customer' ? 'nullable' : 'required', 'integer'],
        ], [
            'file.required' => 'Elegí un archivo.',
            'file.mimes' => 'Solo PDF o imágenes (jpg, png, webp).',
            'file.max' => 'El archivo no puede pasar de 10 MB.',
            'targetId.required' => 'Elegí a qué se adjunta.',
        ]);

        $destino = match ($this->target) {
            'credit' => $this->record->credits()->findOrFail($this->targetId),
            'installment' => Installment::query()
                ->whereIn('credit_id', $this->record->credits()->select('id'))
                ->findOrFail($this->targetId),
            default => $this->record,
        };

        $service->store($destino, $this->file, 'default');

        $this->reset('file', 'targetId');

        unset($this->grupos, $this->counts);
    }

    public function delete(int $attachmentId, AttachmentService $service): void
    {
        // Solo lo que cuelga de este cliente, sus créditos o sus cuotas.
        $adjunto = $this->allowedAttachments()->whereKey($attachmentId)->firstOrFail();

        $service->delete($adjunto);

        unset($this->grupos, $this->counts);
    }

    protected function creditAttachmentsQuery()
    {
        return Attachment::query()
            ->where('attachable_type', Credit::class)
            ->whereIn('attachable_id', $this->record->credits()->select('id'));
    }

    protected function installmentAttachmentsQuery()
    {
        return Attachment::query()
            ->where('attachable_type', Installment::class)
            ->whereIn('attachable_id', Installment::query()
                ->whereIn('credit_id', $this->record->credits()->select('id'))
                ->select('id'));
    }

    protected function allowedAttachments()
    {
        return Attachment::query()->where(function ($query): void {
            $query
                ->where(fn ($sub) => $sub
                    ->where('attachable_type', Customer::class)
                    ->where('attachable_id', $this->record->id))
                ->orWhere(fn ($sub) => $sub
                    ->where('attachable_type', Credit::class)
                    ->whereIn('attachable_id', $this->record->credits()->select('id')))
                ->orWhere(fn ($sub) => $sub
                    ->where('attachable_type', Installment::class)
                    ->whereIn('attachable_id', Installment::query()
                        ->whereIn('credit_id', $this->record->credits()->select('id'))
                        ->select('id')));
        });
    }

    protected function porCredito(): array
    {
        return $this->record->credits()
            ->with(['attachments' => fn ($query) => $query->latest()])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Credit $credito): bool => $credito->attachments->isNotEmpty())
            ->map(fn (Credit $credito): array => [
                'titulo' => 'Crédito #' . $credito->id . ' · ' . CreditStatus::label($credito->status),
                'archivos' => $credito->attachments,
            ])
            ->values()
            ->all();
    }

    protected function porCuota(): array
    {
        return Installment::query()
            ->whereIn('credit_id', $this->record->credits()->select('id'))
            ->whereIn('id', $this->installmentAttachmentsQuery()->select('attachable_id'))
            ->with(['attachments' => fn ($query) => $query->latest()])
            ->orderBy('credit_id')
            ->orderBy('number')
            ->get()
            ->map(fn (Installment $cuota): array => [
                'titulo' => "Crédito #{$cuota->credit_id} · Cuota #{$cuota->number} · vence " . $cuota->due_date?->format('d/m/Y'),
                'archivos' => $cuota->attachments,
            ])
            ->all();
    }

    protected function sueltos(): array
    {
        $archivos = $this->record->attachments()->latest()->get();

        return $archivos->isEmpty()
            ? []
            : [['titulo' => 'Sin crédito asignado', 'archivos' => $archivos]];
    }

    public function render()
    {
        return view('livewire.customer-attachments');
    }
}
