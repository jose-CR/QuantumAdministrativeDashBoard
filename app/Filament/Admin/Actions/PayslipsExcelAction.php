<?php

namespace App\Filament\Admin\Actions;

use App\Models\PayrollPeriod;
use App\Services\Payroll\PayslipExcelService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Un solo Excel con todas las boletas de la quincena, con la plantilla del taller:
 * se imprime una vez, se firma y se entrega.
 */
class PayslipsExcelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'payslipsExcel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('resources.payroll.actions.excel'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (PayrollPeriod $record): void {
                try {
                    $ruta = app(PayslipExcelService::class)->generate($record);
                } catch (\Throwable $error) {
                    report($error);

                    Notification::make()
                        ->title(__('ui.payroll.excel_failed'))
                        ->body($error->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $url = URL::temporarySignedRoute(
                    'payroll.download',
                    now()->addMinutes(10),
                    ['filename' => basename($ruta)],
                );

                Notification::make()
                    ->title(__('resources.payroll.actions.excel_ready'))
                    ->success()
                    ->actions([
                        Action::make('download')
                            ->label(__('resources.payroll.actions.download'))
                            ->url($url)
                            ->openUrlInNewTab(),
                    ])
                    ->sendToDatabase(Auth::user());
            });
    }
}
