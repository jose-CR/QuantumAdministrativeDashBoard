<?php

namespace App\Filament\Admin\Actions;

use App\Models\Credit;
use App\Services\Credits\RefinancingService;
use App\Services\LoanCalculatorService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

class RefinanceCreditAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'refinance';
    }

    /**
     * Crédito que se va a refinanciar.
     *
     * Sobre un crédito es ese mismo. Sobre un cliente solo se puede si tiene UN
     * crédito activo con saldo: con varios, adivinar cuál es una ruleta, así que
     * la acción se esconde y hay que refinar desde el crédito concreto.
     */
    protected function resolveCredit(Model $record): ?Credit
    {
        if ($record instanceof Credit) {
            return $record->status === 'active' && (float) $record->pending_balance > 0
                ? $record
                : null;
        }

        if (! method_exists($record, 'credits')) {
            return null;
        }

        $activos = $record->credits()
            ->where('status', 'active')
            ->where('pending_balance', '>', 0)
            ->get();

        return $activos->count() === 1 ? $activos->first() : null;
    }

    /**
     * Vista previa del crédito nuevo: cuota, total e intereses.
     *
     * Es solo la vista: al guardar, el servicio vuelve a calcular con los mismos
     * datos, así el número que se ve es el que queda.
     */
    protected static function preview(callable $set, Get $get): void
    {
        try {
            $cotizacion = app(LoanCalculatorService::class)->calculate([
                'initial_amount' => (float) $get('initial_amount'),
                'down_payment' => 0,
                'installments' => (int) ($get('installments') ?? 0),
                'installment_amount' => (float) ($get('installment_amount') ?? 0),
                'interest_type' => $get('interest_type') ?? 'auto',
                'interest_rate' => (float) ($get('interest_rate') ?? 0),
            ]);
        } catch (\Throwable) {
            // Faltan datos para cotizar: se limpia la vista previa.
            $set('preview_installment', null);
            $set('preview_total', null);
            $set('preview_interest', null);

            return;
        }

        $set('preview_installment', $cotizacion['installment_amount']);
        $set('preview_total', $cotizacion['total_amount']);
        $set('preview_interest', $cotizacion['total_interest']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Refinanciar')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (Model $record): bool => $this->resolveCredit($record) !== null
                && (bool) auth()->user()?->can('Create:Credit'))
            ->modalHeading('Refinanciar crédito')
            ->modalDescription('Se creará un crédito nuevo por el saldo pendiente. El actual queda cerrado como refinanciado.')
            ->modalSubmitActionLabel('Refinanciar')
            ->fillForm(function (Model $record): array {
                $credit = $this->resolveCredit($record);

                if (! $credit) {
                    return [];
                }

                $cuotas = max(1, (int) $credit->installments);
                $tipoInteres = $credit->interest_type ?? 'auto';

                $datos = [
                    'initial_amount' => (float) $credit->pending_balance,
                    'interest_type' => $tipoInteres,
                    'interest_rate' => (float) $credit->interest_rate,
                    'installments' => $cuotas,
                    // En modo calculado el default reparte la deuda sin interés extra:
                    // el usuario sube la cuota si quiere cobrar intereses.
                    'installment_amount' => $tipoInteres === 'fixed'
                        ? (float) $credit->installment_amount
                        : round((float) $credit->pending_balance / $cuotas, 2),
                    'start_date' => today()->addMonth(),
                    'periodicity' => $credit->periodicity,
                    'payment_day' => $credit->payment_day,
                    'interest_label' => $tipoInteres === 'fixed'
                        ? 'Fijo: ' . (float) $credit->interest_rate . '% por cuota'
                        : 'Calculado según la cuota',
                ];

                try {
                    $cotizacion = app(LoanCalculatorService::class)->calculate([
                        'initial_amount' => $datos['initial_amount'],
                        'down_payment' => 0,
                        'installments' => $datos['installments'],
                        'installment_amount' => $datos['installment_amount'],
                        'interest_type' => $datos['interest_type'],
                        'interest_rate' => $datos['interest_rate'],
                    ]);
                } catch (\Throwable) {
                    return $datos;
                }

                return $datos + [
                    'preview_installment' => $cotizacion['installment_amount'],
                    'preview_total' => $cotizacion['total_amount'],
                    'preview_interest' => $cotizacion['total_interest'],
                ];
            })
            ->schema([
                TextInput::make('initial_amount')
                    ->label('Saldo a refinanciar')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('interest_label')
                    ->label('Interés')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('installments')
                    ->label('Número de cuotas')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue((int) config('quantum.installments.max', 100))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set, Get $get) => self::preview($set, $get)),

                TextInput::make('installment_amount')
                    ->label('Monto de cada cuota')
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->helperText('Subí la cuota para cobrar intereses: el total es cuota × cuotas.')
                    ->visible(fn (Get $get) => ($get('interest_type') ?? 'auto') === 'auto')
                    ->required(fn (Get $get) => ($get('interest_type') ?? 'auto') === 'auto')
                    ->live()
                    ->afterStateUpdated(fn (callable $set, Get $get) => self::preview($set, $get)),

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

                /*
                 * Así queda el crédito nuevo. Todo lo de abajo es solo lectura: se
                 * calcula con el mismo servicio que guarda, no con una fórmula aparte.
                 */
                TextInput::make('preview_installment')
                    ->label('Cuota resultante')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('preview_total')
                    ->label('Total a pagar')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('preview_interest')
                    ->label('Intereses')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),
            ])
            ->action(function (array $data, Model $record): void {
                $credit = $this->resolveCredit($record);

                if (! $credit) {
                    Notification::make()
                        ->title('No se pudo refinanciar')
                        ->body('No hay un crédito activo con saldo para refinanciar.')
                        ->danger()
                        ->send();

                    $this->halt();
                }

                try {
                    $nuevo = app(RefinancingService::class)->execute($credit->client, $credit, $data);
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
                    ->body(sprintf(
                        'Crédito #%d por $%s. El #%d quedó cerrado.',
                        $nuevo->id,
                        number_format((float) $nuevo->total_amount, 2),
                        $credit->id,
                    ))
                    ->success()
                    ->send();

                $record->refresh();
            });
    }
}
