<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
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
                ->label('Tipo de documento'),

            ExportColumn::make('document_number')
                ->label('Número de documento'),

            ExportColumn::make('full_name')
                ->label('Nombre completo'),

            ExportColumn::make('email')
                ->label('Correo electrónico'),

            ExportColumn::make('phone_primary')
                ->label('Teléfono principal'),

            ExportColumn::make('phone_secondary')
                ->label('Teléfono secundario'),

            ExportColumn::make('nrc')
                ->label('NRC'),

            ExportColumn::make('economic_activity')
                ->label('Actividad económica')
                ->formatStateUsing(
                    fn (string $state) => ActividadesEconomicas::activityName($state)
                ),

            ExportColumn::make('department')
                ->label('Departamento')
                ->formatStateUsing(
                    fn (string $state) => ElSalvadorCatalogo::departmentName($state)
                ),

            ExportColumn::make('municipality')
                ->label('Municipio')
                ->formatStateUsing(
                    fn ($state, $record) => ElSalvadorCatalogo::municipalityName(
                        $record->department,
                        $state
                    )
                ),

            ExportColumn::make('district')
                ->label('Distrito')
                ->formatStateUsing(
                    fn ($state, $record) => ElSalvadorCatalogo::districtName(
                        $record->municipality,
                        $state
                    )
                ),

            ExportColumn::make('address')
                ->label('Dirección'),

            ExportColumn::make('created_at')
                ->label('Fecha de creación'),

            ExportColumn::make('updated_at')
                ->label('Última actualización'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('document_type')
                ->label('Tipo de documento')
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

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customer export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
