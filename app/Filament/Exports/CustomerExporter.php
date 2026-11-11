<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use App\Support\ElSalvadorCatalogo;
use App\Utils\Filament\FileHelper;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Illuminate\Support\Number;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('document_type')
                ->label(__('ui.columns.document_type')),

            ExportColumn::make('document_number')
                ->label(__('ui.columns.document_number')),

            ExportColumn::make('full_name')
                ->label('Nombre completo'),

            ExportColumn::make('email')
                ->label(__('ui.columns.email')),

            ExportColumn::make('phone_primary')
                ->label(__('ui.columns.primary_phone')),

            ExportColumn::make('phone_secondary')
                ->label(__('ui.columns.secondary_phone')),

            ExportColumn::make('nrc')
                ->label('NRC'),

            ExportColumn::make('economic_activity')
                ->label(__('ui.columns.economic_activity')),
/*                 ->formatStateUsing(
                    fn ($state) => ActividadesEconomicas::activityName($state)
                ), */

            // Se exporta el nombre: la planilla se lee. El import acepta nombre o código.
            ExportColumn::make('department')
                ->label('Departamento')
                ->formatStateUsing(
                    fn ($state) => ElSalvadorCatalogo::departmentName((string) $state)
                ),

            ExportColumn::make('municipality')
                ->label('Municipio')
                ->formatStateUsing(
                    fn ($state, $record) => ElSalvadorCatalogo::municipalityName(
                        (string) $record->department,
                        (string) $state,
                    )
                ),

            ExportColumn::make('district')
                ->label('Distrito')
                ->formatStateUsing(
                    fn ($state, $record) => ElSalvadorCatalogo::districtName(
                        (string) $record->municipality,
                        (string) $state,
                    )
                ),

            ExportColumn::make('address')
                ->label(__('ui.columns.address')),

            ExportColumn::make('created_at')
                ->label(__('ui.columns.created_at')),

            ExportColumn::make('updated_at')
                ->label(__('ui.columns.updated_at')),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('document_type')
                ->label(__('ui.columns.document_type'))
                ->options([
                    'ALL' => 'Todos',
                    'DUI' => 'DUI',
                    'NIT' => 'NIT',
                    'Passport' => 'Passport',
                    'Carnet RES' => 'Carnet RES',
                    'OTRO' => 'OTRO',
                ])
                ->default('ALL')
                ->required(),
        ];
    }

    public function getFileExtension(): string
    {
        return FileHelper::extension(
            $this->export->file_name
        );
    }

    public function isExcel(): bool
    {
        return $this->getFileExtension() === 'xlsx';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customer export has completed and '
            . Number::format($export->successful_rows)
            . ' '
            . str('row')->plural($export->successful_rows)
            . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '
                . Number::format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to export.';
        }

        return $body;
    }
}
