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

/**
 * Adjuntos del cliente, separados en tres tabs:
 *
 *  - Créditos: lo que se subió a cada crédito (boleta firmada, foto del bien).
 *  - Cuotas: los comprobantes de cada cuota (voucher, recibo).
 *  - Sueltos: facturas e imágenes del cliente que no son de ningún crédito.
 *
 * Lista y borra lo que cuelga de este cliente. La subida la hace la acción
 * «Adjuntar archivo», que la sección monta al lado con el FileUpload de Filament.
 *
 * Se puede borrar lo que sea de este cliente, nunca de otro.
 */
class CustomerAttachments extends Component
{
    public Customer $record;

    public string $tab = 'credits';

    public function setTab(string $tab): void
    {
        // De dónde se sube es la acción «Adjuntar archivo», con el FileUpload de
        // Filament; acá solo se mira y se borra.
        $this->tab = in_array($tab, ['credits', 'installments', 'loose'], true)
            ? $tab
            : 'credits';
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
