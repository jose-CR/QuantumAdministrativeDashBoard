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
        // Cuenta comprobantes, no filas: un archivo que cubre tres cuotas es uno.
        $porArchivo = fn ($adjuntos) => $adjuntos
            ->get()
            ->unique(fn (Attachment $adjunto): string => $adjunto->disk . '|' . $adjunto->path)
            ->count();

        return [
            'credits' => $porArchivo($this->creditAttachmentsQuery()),
            'installments' => $porArchivo($this->installmentAttachmentsQuery()),
            'loose' => $porArchivo($this->record->attachments()),
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

        // Un comprobante que cubre varias cuotas es un archivo con una fila por
        // cuota: se borra entero. El archivo físico se va con la última fila.
        $hermanos = $this->allowedAttachments()
            ->where('disk', $adjunto->disk)
            ->where('path', $adjunto->path)
            ->get();

        foreach ($hermanos as $hermano) {
            $service->delete($hermano);
        }

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
                'archivos' => $this->unArchivoPorPath($credito->attachments),
            ])
            ->values()
            ->all();
    }

    /**
     * Un comprobante puede pagar varias cuotas: se agrupa POR ARCHIVO y el título
     * dice qué cuotas cubre. Antes salía una tarjeta por cuota y el mismo PDF se
     * repetía tres veces (con 50 cuotas la lista era interminable).
     */
    protected function porCuota(): array
    {
        $adjuntos = Attachment::query()
            ->where('attachable_type', Installment::class)
            ->whereIn('attachable_id', Installment::query()
                ->whereIn('credit_id', $this->record->credits()->select('id'))
                ->select('id'))
            ->with('attachable')
            ->orderByDesc('id')
            ->get();

        return $adjuntos
            ->groupBy(fn (Attachment $adjunto): string => $adjunto->disk . '|' . $adjunto->path)
            ->map(function ($grupo): array {
                $cuotas = $grupo
                    ->map(fn (Attachment $adjunto) => $adjunto->attachable)
                    ->filter()
                    ->sortBy('number')
                    ->values();

                $numeros = $cuotas->pluck('number')->unique()->sort()->values();
                $credito = $cuotas->first()?->credit_id;

                return [
                    'titulo' => $this->tituloCuotas($credito, $numeros, $cuotas->first()?->due_date?->format('d/m/Y')),
                    'archivos' => collect([$grupo->first()]),
                ];
            })
            ->sortBy('titulo')
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $numeros
     */
    protected function tituloCuotas(?int $credito, $numeros, ?string $vence): string
    {
        $prefijo = $credito ? "Crédito #{$credito} · " : '';

        if ($numeros->count() === 1) {
            return $prefijo . "Cuota #{$numeros->first()}" . ($vence ? " · vence {$vence}" : '');
        }

        return $prefijo . 'Cuotas #' . $numeros->implode(', #') . " · {$numeros->count()} cuotas";
    }

    /**
     * Deja una fila por archivo: si el mismo comprobante está en varias cuotas, se
     * muestra una sola tarjeta.
     *
     * @param  \Illuminate\Support\Collection<int, Attachment>  $archivos
     * @return \Illuminate\Support\Collection<int, Attachment>
     */
    protected function unArchivoPorPath($archivos)
    {
        return $archivos
            ->unique(fn (Attachment $adjunto): string => $adjunto->disk . '|' . $adjunto->path)
            ->values();
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
