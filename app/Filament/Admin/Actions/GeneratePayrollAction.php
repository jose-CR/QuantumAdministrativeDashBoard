<?php

namespace App\Filament\Admin\Actions;

use App\Models\PayrollPeriod;
use App\Services\Payroll\GeneratePayrollService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Crea una boleta por cada empleado activo, con su salario y los descuentos de ley.
 */
class GeneratePayrollAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generatePayroll';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('resources.payroll.actions.generate'))
            ->icon('heroicon-o-document-plus')
            ->requiresConfirmation()
            ->modalDescription(__('resources.payroll.actions.generate_help'))
            ->visible(fn (PayrollPeriod $record): bool => ! $record->isPaid())
            ->action(function (PayrollPeriod $record): void {
                $resultado = app(GeneratePayrollService::class)->generate($record);

                $notificacion = Notification::make()
                    ->title(__('resources.payroll.actions.generated', [
                        'count' => $resultado['created'],
                    ]));

                $lineas = [];

                if ($resultado['skipped'] > 0) {
                    $lineas[] = __('resources.payroll.actions.generated_skipped', [
                        'count' => $resultado['skipped'],
                    ]);
                }

                // Sin salario no se puede calcular nada: se avisa quiénes son.
                if ($resultado['without_salary'] !== []) {
                    array_unshift($lineas, __('resources.payroll.actions.generated_no_salary', [
                        'names' => implode(', ', $resultado['without_salary']),
                    ]));
                }

                if ($lineas !== []) {
                    $notificacion->body(implode(' ', $lineas))->warning();
                } else {
                    $notificacion->success();
                }

                $notificacion->send();
            });
    }
}
