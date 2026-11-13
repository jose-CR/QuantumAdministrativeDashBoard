<?php

namespace App\Filament\Concerns;

use App\Models\Attachment;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Installment;
use App\Services\AttachmentService;
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
 *
 * En las entradas de caja hay dos cosas más: se pueden **reusar** adjuntos que ya
 * tiene el cliente (`attachment_picks`, sin volver a subir el archivo) y lo que se
 * sube acá se adjunta también al cliente, así la factura vive en los dos lados.
 */
trait HasAttachmentsSync
{
    /** @var array<int, string> */
    protected array $attachmentPaths = [];

    /** @var array<string, string> */
    protected array $attachmentFileNames = [];

    /** Si el formulario envió el campo de archivos (aunque sea vacío). */
    protected bool $attachmentFieldPresent = false;

    /** @var array<int, int> Adjuntos del cliente elegidos para reusar. */
    protected array $attachmentPicks = [];

    protected function captureAttachments(array $data): array
    {
        $this->attachmentFieldPresent = array_key_exists('attachment_uploads', $data);
        $this->attachmentPaths = array_values(array_filter((array) ($data['attachment_uploads'] ?? [])));
        $this->attachmentFileNames = (array) ($data['file_names'] ?? []);
        $this->attachmentPicks = array_values(array_filter((array) ($data['attachment_picks'] ?? [])));

        unset($data['attachment_uploads'], $data['file_names'], $data['attachment_picks']);

        return $data;
    }

    protected function syncAttachments(
        string $field = 'attachment_uploads',
        ?string $collection = null
    ): void {
        $paths = $this->attachmentPaths
            ?: collect((array) ($this->form->getRawState()[$field] ?? []))->filter()->values()->all();

        $antes = $this->record->attachments()->pluck('path')->all();

        app(AttachmentSyncService::class)->sync(
            record: $this->record,
            paths: $paths,
            fileNames: $this->attachmentFileNames,
            collection: $collection,
            allowEmpty: $this->attachmentFieldPresent,
        );

        $this->attachPickedFiles();
        $this->mirrorNewFilesToCustomer($antes);

        $this->attachmentPaths = [];
        $this->attachmentFileNames = [];
        $this->attachmentPicks = [];
    }

    /**
     * Adjunta a la entrada los archivos que ya tenía el cliente: no se copia nada,
     * la fila nueva apunta al mismo path.
     */
    protected function attachPickedFiles(): void
    {
        if ($this->attachmentPicks === [] || ! $this->record instanceof Inflow) {
            return;
        }

        $cliente = $this->record->customer;

        if (! $cliente) {
            return;
        }

        $service = app(AttachmentService::class);
        $yaTiene = $this->record->attachments()->pluck('path')->all();

        foreach ($this->allowedPickedAttachments($cliente) as $adjunto) {
            if (in_array($adjunto->path, $yaTiene, true)) {
                continue;
            }

            $service->attachFromPath(
                record: $this->record,
                path: $adjunto->path,
                disk: $adjunto->disk,
                collection: $adjunto->collection ?: 'default',
            );

            $yaTiene[] = $adjunto->path;
        }
    }

    /**
     * Lo que se subió desde la entrada también queda como adjunto suelto del
     * cliente: la factura vive en los dos lugares.
     *
     * @param  array<int, string>  $antes  paths que la entrada ya tenía
     */
    protected function mirrorNewFilesToCustomer(array $antes): void
    {
        if (! $this->record instanceof Inflow) {
            return;
        }

        $cliente = $this->record->customer;

        if (! $cliente) {
            return;
        }

        $nuevos = array_diff($this->record->attachments()->pluck('path')->all(), $antes);

        if ($nuevos === []) {
            return;
        }

        $service = app(AttachmentService::class);
        $delCliente = $cliente->attachments()->pluck('path')->all();

        foreach ($nuevos as $path) {
            if (in_array($path, $delCliente, true)) {
                continue;
            }

            $service->attachFromPath(
                record: $cliente,
                path: $path,
                collection: 'default',
            );
        }
    }

    /**
     * Los adjuntos elegidos, acotados a lo que cuelga de ese cliente.
     *
     * @return \Illuminate\Support\Collection<int, Attachment>
     */
    protected function allowedPickedAttachments(Customer $cliente)
    {
        $credits = Credit::query()->where('customer_id', $cliente->id)->select('id');
        $installments = Installment::query()->whereIn('credit_id', $credits)->select('id');

        return Attachment::query()
            ->whereIn('id', $this->attachmentPicks)
            ->where(fn ($query) => $query
                ->where(fn ($sub) => $sub->where('attachable_type', Customer::class)->where('attachable_id', $cliente->id))
                ->orWhere(fn ($sub) => $sub->where('attachable_type', Credit::class)->whereIn('attachable_id', $credits))
                ->orWhere(fn ($sub) => $sub->where('attachable_type', Installment::class)->whereIn('attachable_id', $installments)))
            ->get();
    }
}
