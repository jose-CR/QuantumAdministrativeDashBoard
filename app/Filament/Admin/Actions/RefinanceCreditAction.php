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

class RefinanceCreditAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refinance';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Refinanciar')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (Credit $record): bool => $record->status === 'active')
            ->modalHeading('Refinanciar crédito')
            ->modalDescription('El crédito actual se cerrará como refinanciado y se creará uno nuevo con el saldo pendiente y la misma prima.')
            ->fillForm(fn (Credit $record): array => [
                'initial_amount' => $record->pending_balance,
                'start_date' => today()->addMonth(),
                'periodicity' => $record->periodicity,
                'payment_day' => $record->payment_day,
            ])
            ->schema([
                TextInput::make('initial_amount')
                    ->label('Saldo a refinanciar')
                    ->prefix('$')
                    ->disabled(),
                    
                TextInput::make('down_payment')
                    ->label('Pago de refinanciamiento')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->required(),

                TextInput::make('installments')
                    ->label('Número de cuotas')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                Select::make('periodicity')
                    ->label('Periodicidad')
                    ->options([
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                        'yearly' => 'Anual',
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
            ->action(function (array $data, Credit $record): void {
                try {
                    app(RefinancingService::class)->execute($record->client, $record, $data);
                } catch (Exception $e) {
                    Notification::make()->title('No se pudo refinanciar')->body($e->getMessage())->danger()->send();

                    $this->halt();
                }

                Notification::make()->title('Crédito refinanciado')->success()->send();
            });
    }
}