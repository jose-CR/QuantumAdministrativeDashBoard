<?php

namespace App\Filament\Admin\Actions;

use App\Models\Credit;
use App\Services\Credits\RefinancingService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class RefinanceCreditAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refinance';
    }

    protected function resolveCredit(Model $record): ?Credit
    {
        if ($record instanceof Credit) {
            return $record->status === 'active' ? $record : null;
        }

        return $record->credits()
            ->where('status', 'active')
            ->latest()
            ->first();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Refinanciar')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (Model $record): bool => $this->resolveCredit($record) !== null)
            ->modalHeading('Refinanciar crédito')
            ->modalDescription('El crédito actual se cerrará como refinanciado y se creará uno nuevo con el saldo pendiente y la misma prima.')
            ->fillForm(function (Model $record): array {
                $credit = $this->resolveCredit($record);

                return [
                    'initial_amount' => $credit?->pending_balance,
                    'start_date'     => today()->addMonth(),
                    'periodicity'    => $credit?->periodicity,
                    'payment_day'    => $credit?->payment_day,
                ];
            })
            ->schema([
                TextInput::make('initial_amount')
                    ->label('Saldo a refinanciar')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('installments')
                    ->label('Número de cuotas')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                TextInput::make('installment_amount')
                    ->label('Monto de cada cuota')
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->required(),

                Select::make('periodicity')
                    ->label('Periodicidad')
                    ->options([
                        'weekly'  => 'Semanal',
                        'monthly' => 'Mensual',
                        'yearly'  => 'Anual',
                    ])
                    ->required(),

                DatePicker::make('start_date')
                    ->label('Vencimiento de la primera cuota')
                    ->required(),

                TextInput::make('payment_day')
                    ->label('Día de pago')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(31)
                    ->required(),
            ])
            ->action(function (array $data, Model $record): void {
                $credit = $this->resolveCredit($record);

                if (! $credit) {
                    Notification::make()
                        ->title('No se pudo refinanciar')
                        ->body('No hay un crédito activo para refinanciar.')
                        ->danger()
                        ->send();

                    $this->halt();
                }

                try {
                    app(RefinancingService::class)->execute($credit->client, $credit, $data);
                } catch (Exception $e) {
                    Notification::make()
                        ->title('No se pudo refinanciar')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $this->halt();
                }

                Notification::make()
                    ->title('Crédito refinanciado')
                    ->success()
                    ->send();
            });
    }
}