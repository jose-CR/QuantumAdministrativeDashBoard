<?php

namespace App\Filament\Admin\Actions;

use App\Support\CashReport;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;

class CashReportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cashReport';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $range = CashReport::dateRange();

        $this
            ->label('Reporte de flujo de caja')
            ->icon('heroicon-o-arrow-down-tray')
            ->modalHeading('Reporte de flujo de caja')
            ->modalDescription(
                $range['min'] && $range['max']
                    ? "Entradas y salidas en una hoja, con resumen, gráficos y análisis. Datos disponibles entre {$range['min']} y {$range['max']}. Sin fechas incluye todo."
                    : 'Entradas y salidas en una hoja, con resumen, gráficos y análisis. Sin fechas incluye todo.'
            )
            ->modalSubmitActionLabel('Generar y descargar')
            ->schema([
                DatePicker::make('from')
                    ->label('Desde')
                    ->minDate($range['min'])
                    ->maxDate($range['max']),
                DatePicker::make('until')
                    ->label('Hasta')
                    ->minDate($range['min'])
                    ->maxDate($range['max'])
                    ->afterOrEqual('from'),
            ])
            ->action(function (array $data) {
                try {
                    $path = CashReport::generate($data['from'] ?? null, $data['until'] ?? null);
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->title('No se pudo generar el reporte')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return null;
                }

                return response()->download($path)->deleteFileAfterSend();
            });
    }
}