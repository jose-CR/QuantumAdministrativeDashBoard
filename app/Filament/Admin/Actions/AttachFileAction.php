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

/**
 * Subir un archivo desde la ficha del cliente con el FileUpload de Filament.
 *
 * El archivo puede quedar en el cliente (factura o foto suelta), en un crédito
 * (la prima, la boleta firmada) o en **una o varias cuotas**: un solo comprobante
 * que paga las cuotas 2 y 3 se guarda una vez y queda colgado de las dos.
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

                Select::make('installment_ids')
                    ->label(__('ui.attachments.installments'))
                    ->multiple()
                    ->options(function (Get $get): array {
                        if (blank($get('credit_id'))) {
                            return [];
                        }

                        return Installment::query()
                            ->where('credit_id', $get('credit_id'))
                            ->orderBy('number')
                            ->get()
                            ->mapWithKeys(fn (Installment $installment): array => [
                                $installment->id => "Cuota #{$installment->number} · vence " . $installment->due_date?->format('d/m/Y'),
                            ])
                            ->all();
                    })
                    ->helperText(__('ui.attachments.installments_help'))
                    ->visible(fn (Get $get): bool => $get('target') === 'installment')
                    ->required(fn (Get $get): bool => $get('target') === 'installment'),

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
            ->action(function (Customer $record, array $data, AttachmentService $service): void {
                $targets = $this->resolveTargets($record, $data);

                if ($targets === []) {
                    return;
                }

                $saved = 0;

                $primary = $targets[0];
                $others = array_slice($targets, 1);

                foreach ((array) ($data['files'] ?? []) as $path) {
                    if (blank($path)) {
                        continue;
                    }

                    $name = $data['file_names'][$path] ?? basename($path);

                    // El primer destino mueve el archivo a su carpeta; los demás
                    // apuntan al mismo archivo, así no queda duplicado en el disco.
                    $attachment = $service->storeFromPath(
                        record: $primary,
                        temporaryPath: $path,
                        originalName: $name,
                        collection: 'default',
                    );

                    foreach ($others as $target) {
                        $service->attachFromPath(
                            record: $target,
                            path: $attachment->path,
                            collection: 'default',
                        );
                    }

                    $saved++;
                }

                Notification::make()
                    ->title($saved === 1
                        ? __('ui.attachments.saved')
                        : __('ui.attachments.saved_many', ['count' => $saved]))
                    ->success()
                    ->send();
            });
    }

    /**
     * A qué registros se cuelga el archivo: uno por cada cuota elegida.
     *
     * @return array<int, \Illuminate\Database\Eloquent\Model>
     */
    protected function resolveTargets(Customer $record, array $data): array
    {
        return match ($data['target']) {
            'credit' => [$record->credits()->findOrFail($data['credit_id'])],

            'installment' => Installment::query()
                ->whereIn('credit_id', $record->credits()->select('id'))
                ->whereIn('id', array_filter((array) ($data['installment_ids'] ?? [])))
                ->orderBy('number')
                ->get()
                ->all(),

            default => [$record],
        };
    }
}
