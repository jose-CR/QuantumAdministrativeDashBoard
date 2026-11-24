<?php

namespace App\Filament\Admin\Actions;

use App\Models\Customer;
use App\Services\Reports\AccountStatementService;
use App\Support\PythonBinary;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

/**
 * Descarga el estado de cuenta del cliente: todas sus facturas, sus pagos y el plan de
 * pago de lo vendido a crédito, en un Excel listo para imprimir y firmar.
 *
 * Los datos los arma AccountStatementService y el archivo lo dibuja
 * python/account_statement.py (mismo camino que la plantilla de boletas).
 */
final class AccountStatementAction
{
    public static function make(): Action
    {
        return Action::make('estadoDeCuenta')
            ->label(__('ui.statement.customer_excel'))
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->visible(fn (Customer $record): bool => $record->inflows()->exists())
            ->requiresConfirmation()
            ->modalHeading(__('ui.statement.heading_customer'))
            ->modalDescription(__('ui.statement.description_customer'))
            ->modalSubmitActionLabel(__('ui.statement.download'))
            ->action(fn (Customer $record): ?BinaryFileResponse => self::download($record));
    }

    private static function download(Customer $customer): ?BinaryFileResponse
    {
        try {
            $ruta = self::build($customer);
        } catch (RuntimeException $error) {
            Notification::make()
                ->danger()
                ->title(__('ui.statement.failed'))
                ->body($error->getMessage())
                ->persistent()
                ->send();

            return null;
        }

        return response()
            ->download($ruta, 'estado-de-cuenta-' . $customer->getKey() . '-' . now()->format('Y-m-d') . '.xlsx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Genera el archivo y devuelve su ruta (lo usan la acción y las pruebas).
     */
    public static function build(Customer $customer): string
    {
        $datos = app(AccountStatementService::class)->forCustomer($customer);

        if ($datos['resumen']['facturas'] === 0) {
            throw new RuntimeException(__('ui.statement.no_invoices'));
        }

        $carpeta = storage_path('app/private/estados-de-cuenta');
        @mkdir($carpeta, 0775, true);

        $marca = uniqid('estado-cliente-', true);
        $json = $carpeta . '/data-' . $marca . '.json';
        $xlsx = $carpeta . '/estado-de-cuenta-' . $marca . '.xlsx';

        file_put_contents($json, json_encode($datos, JSON_UNESCAPED_UNICODE));

        $proceso = new Process([
            PythonBinary::path(),
            base_path('python/account_statement.py'),
            $json,
            $xlsx,
        ]);

        $proceso->setTimeout(120);
        $proceso->run();

        @unlink($json);

        if (! $proceso->isSuccessful() || ! is_file($xlsx)) {
            throw new RuntimeException(
                __('ui.statement.failed') . ' ' . trim($proceso->getErrorOutput() . ' ' . $proceso->getOutput())
            );
        }

        return $xlsx;
    }
}
