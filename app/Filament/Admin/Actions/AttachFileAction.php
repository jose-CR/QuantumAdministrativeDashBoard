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
 * (la prima, la boleta firmada) o en una cuota (el comprobante). La subida la
 * maneja Filament de punta a punta: acá solo se registra el adjunto.
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
            ->label('Adjuntar archivo')
            ->icon('heroicon-o-paper-clip')
            ->modalHeading('Adjuntar archivo')
            ->modalDescription('La prima, una factura, una foto: se guarda en el cliente, en un crédito o en una cuota.')
            ->modalSubmitActionLabel('Subir')
            ->schema([
                Select::make('target')
                    ->label('¿A qué se adjunta?')
                    ->options([
                        'customer' => 'El cliente (suelto)',
                        'credit' => 'Un crédito',
                        'installment' => 'Una cuota',
                    ])
                    ->default('customer')
                    ->live()
                    ->required(),

                Select::make('credit_id')
                    ->label('Crédito')
                    ->options(function (Customer $record): array {
                        return $record->credits()
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn ($credito): array => [
                                $credito->id => "Crédito #{$credito->id} · \${$credito->amount} · {$credito->status}",
                            ])
                            ->all();
                    })
                    ->visible(fn (Get $get): bool => in_array($get('target'), ['credit', 'installment'], true))
                    ->required(fn (Get $get): bool => in_array($get('target'), ['credit', 'installment'], true))
                    ->live(),

                Select::make('installment_id')
                    ->label('Cuota')
                    ->options(function (Get $get, Customer $record): array {
                        if (blank($get('credit_id'))) {
                            return [];
                        }

                        return Installment::query()
                            ->where('credit_id', $get('credit_id'))
                            ->orderBy('number')
                            ->get()
                            ->mapWithKeys(fn (Installment $cuota): array => [
                                $cuota->id => "Cuota #{$cuota->number} · vence " . $cuota->due_date?->format('d/m/Y'),
                            ])
                            ->all();
                    })
                    ->visible(fn (Get $get): bool => $get('target') === 'installment')
                    ->required(fn (Get $get): bool => $get('target') === 'installment'),

                FileUpload::make('files')
                    ->label('Archivos')
                    ->multiple()
                    ->required()
                    ->disk('attachments')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(10240)
                    ->maxFiles(10)
                    ->storeFileNamesIn('file_names')
                    ->helperText('PDF o imágenes, hasta 10 MB cada uno.'),
            ])
            ->action(function (Customer $record, array $data, AttachmentService $service): void {
                $destino = match ($data['target']) {
                    'credit' => $record->credits()->findOrFail($data['credit_id']),
                    'installment' => Installment::query()
                        ->whereIn('credit_id', $record->credits()->select('id'))
                        ->findOrFail($data['installment_id']),
                    default => $record,
                };

                $guardados = 0;

                foreach ((array) ($data['files'] ?? []) as $path) {
                    if (blank($path)) {
                        continue;
                    }

                    $service->storeFromPath(
                        record: $destino,
                        temporaryPath: $path,
                        originalName: $data['file_names'][$path] ?? basename($path),
                        collection: $destino->getTable(),
                    );

                    $guardados++;
                }

                Notification::make()
                    ->title($guardados === 1 ? 'Adjunto guardado' : "{$guardados} adjuntos guardados")
                    ->success()
                    ->send();
            });
    }
}
