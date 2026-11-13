<?php

namespace App\Filament\Admin\Actions;

use App\Models\Customer;
use App\Models\Installment;
use App\Services\AttachmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Subir un archivo desde la ficha del cliente con el FileUpload de Filament.
 *
 * El archivo puede quedar en el cliente (factura o foto suelta), en un crédito
 * (la prima, la boleta firmada) o en un **tramo de cuotas**: un comprobante que
 * paga las cuotas 2 y 3 se elige con "desde 2 hasta 3", no de una lista de 50.
 * Se guarda un solo archivo y queda colgado de cada cuota del tramo.
 */
class AttachFileAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'attachFile';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('ui.attachments.action_title'))
            ->icon('heroicon-o-paper-clip')
            ->modalHeading(__('ui.attachments.action_title'))
            ->modalDescription(__('ui.attachments.action_description'))
            ->modalSubmitActionLabel(__('ui.attachments.submit'))
            ->extraModalFooterActions(fn (Action $action): array => [
                // Sube y deja el modal abierto y vacío para cargar el siguiente.
                $action->makeModalSubmitAction('attachAnother', arguments: ['another' => true])
                    ->label(__('ui.attachments.submit_another')),
            ])
            ->schema([
                Select::make('target')
                    ->label(__('ui.attachments.target'))
                    ->options([
                        'customer' => __('ui.attachments.target_customer'),
                        'credit' => __('ui.attachments.target_credit'),
                        'installment' => __('ui.attachments.target_installments'),
                    ])
                    ->default('customer')
                    ->live()
                    ->required(),

                Select::make('credit_id')
                    ->label(__('ui.attachments.credit'))
                    ->options(function (Customer $record): array {
                        return $record->credits()
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn ($credit): array => [
                                $credit->id => "Crédito #{$credit->id} · \${$credit->amount} · {$credit->status}",
                            ])
                            ->all();
                    })
                    ->visible(fn (Get $get): bool => in_array($get('target'), ['credit', 'installment'], true))
                    ->required(fn (Get $get): bool => in_array($get('target'), ['credit', 'installment'], true))
                    ->live(),

                // Tramo de cuotas: dos selects cortos en vez de una lista de 50.
                Select::make('installment_from')
                    ->label(__('ui.attachments.from_installment'))
                    ->options(fn (Get $get): array => $this->installmentOptions($get('credit_id')))
                    ->default(1)
                    ->visible(fn (Get $get): bool => $get('target') === 'installment')
                    ->required(fn (Get $get): bool => $get('target') === 'installment')
                    ->live(),

                Select::make('installment_to')
                    ->label(__('ui.attachments.to_installment'))
                    ->options(fn (Get $get): array => $this->installmentOptions($get('credit_id')))
                    ->visible(fn (Get $get): bool => $get('target') === 'installment')
                    ->required(fn (Get $get): bool => $get('target') === 'installment')
                    ->live()
                    ->helperText(function (Get $get): string {
                        $desde = (int) $get('installment_from');
                        $hasta = (int) ($get('installment_to') ?: $desde);

                        if ($desde <= 0) {
                            return '';
                        }

                        [$desde, $hasta] = $desde <= $hasta ? [$desde, $hasta] : [$hasta, $desde];

                        return $desde === $hasta
                            ? __('ui.attachments.range_one', ['number' => $desde])
                            : __('ui.attachments.range_many', ['from' => $desde, 'to' => $hasta, 'count' => $hasta - $desde + 1]);
                    }),

                FileUpload::make('files')
                    ->label(__('ui.attachments.files'))
                    ->multiple()
                    ->required()
                    ->disk('attachments')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(10240)
                    ->maxFiles(10)
                    ->storeFileNamesIn('file_names')
                    ->helperText(__('ui.attachments.files_help')),
            ])
            ->action(function (Customer $record, array $data, array $arguments, Schema $schema, AttachmentService $service): void {
                $targets = $this->resolveTargets($record, $data);

                if ($targets === []) {
                    return;
                }

                $this->saveFiles($service, $targets, $data);

                Notification::make()
                    ->title(isset($data['files']) && count((array) $data['files']) > 1
                        ? __('ui.attachments.saved_many', ['count' => count((array) $data['files'])])
                        : __('ui.attachments.saved'))
                    ->success()
                    ->send();

                if ($arguments['another'] ?? false) {
                    // Formulario limpio y modal abierto, sin volver a la ficha.
                    $schema->fill();
                    $schema->rawState($schema->getRawState());
                    $schema->dispatchClientSideStateReset();

                    $this->halt();
                }
            });
    }

    /**
     * Guarda los archivos una sola vez: el primero mueve el archivo a su carpeta y
     * los demás destinos apuntan al mismo path (una fila por cuota, un archivo).
     *
     * @param  array<int, \Illuminate\Database\Eloquent\Model>  $targets
     */
    protected function saveFiles(AttachmentService $service, array $targets, array $data): void
    {
        $primary = $targets[0];
        $others = array_slice($targets, 1);

        foreach ((array) ($data['files'] ?? []) as $path) {
            if (blank($path)) {
                continue;
            }

            $attachment = $service->storeFromPath(
                record: $primary,
                temporaryPath: $path,
                originalName: $data['file_names'][$path] ?? basename($path),
                collection: 'default',
            );

            foreach ($others as $target) {
                $service->attachFromPath(
                    record: $target,
                    path: $attachment->path,
                    collection: 'default',
                );
            }
        }
    }

    /**
     * A qué registros se cuelga el archivo: uno por cada cuota del tramo.
     *
     * @return array<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function resolveTargets(Customer $record, array $data): array
    {
        if ($data['target'] === 'credit') {
            return [$record->credits()->findOrFail($data['credit_id'])];
        }

        if ($data['target'] !== 'installment') {
            return [$record];
        }

        $desde = (int) ($data['installment_from'] ?? 0);
        $hasta = (int) ($data['installment_to'] ?? $desde);

        if ($desde <= 0) {
            return [];
        }

        [$desde, $hasta] = $desde <= $hasta ? [$desde, $hasta] : [$hasta, $desde];

        return Installment::query()
            ->whereIn('credit_id', $record->credits()->select('id'))
            ->where('credit_id', $data['credit_id'])
            ->whereBetween('number', [$desde, $hasta])
            ->orderBy('number')
            ->get()
            ->all();
    }

    /**
     * Números de cuota del crédito, para los dos selects del tramo.
     *
     * @return array<int, int>
     */
    protected function installmentOptions(mixed $creditId): array
    {
        if (blank($creditId)) {
            return [];
        }

        return Installment::query()
            ->where('credit_id', $creditId)
            ->orderBy('number')
            ->pluck('number')
            ->mapWithKeys(fn ($number): array => [(int) $number => (int) $number])
            ->all();
    }
}
