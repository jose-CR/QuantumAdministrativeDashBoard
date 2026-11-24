<?php

namespace App\Filament\Admin\Actions;

use App\Services\Reports\CustomerStatementService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Descarga el Excel con el estado de cuenta.
 *
 * Dos alcances, mismo archivo:
 *  - overdue(): los créditos del taller que tienen cuotas vencidas (botón del tablero).
 *  - customer(): todos los créditos de una persona (botón de su ficha).
 */
final class CustomerStatementAction
{
    public static function overdue(): Action
    {
        return self::base()
            ->label(__('ui.statement.overdue'))
            ->action(function (): ?BinaryFileResponse {
                $servicio = app(CustomerStatementService::class);

                return self::download($servicio, $servicio->overdueCredits());
            });
    }

    private static function base(): Action
    {
        return Action::make('estadoDeCuenta')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('ui.statement.heading'))
            ->modalDescription(__('ui.statement.description'))
            ->modalSubmitActionLabel(__('ui.statement.download'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Credit>  $creditos
     */
    private static function download(CustomerStatementService $servicio, $creditos): ?BinaryFileResponse
    {
        try {
            $ruta = $servicio->build($creditos);
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
            ->download($ruta, 'estados-de-cuenta-' . now()->format('Y-m-d') . '.xlsx')
            ->deleteFileAfterSend(true);
    }
}
