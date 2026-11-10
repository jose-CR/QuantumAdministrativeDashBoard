<?php

namespace App\Filament\Admin\Resources\Client\Customers\RelationManagers;

use App\Filament\Admin\Actions\RefinanceCreditAction;
use App\Models\Credit;
use App\Support\CreditStatus;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Créditos del cliente: el listado los muestra todos (viejos y vigentes) y cada
 * fila abre el detalle en un modal, sin salir de la ficha.
 */
class CreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'credits';

    protected static ?string $title = 'Créditos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y')->sortable(),

                TextColumn::make('financed_amount')->label('Financiado')->money('USD'),

                TextColumn::make('pending_balance')
                    ->label('Saldo pendiente')
                    ->money('USD')
                    ->color(fn (Credit $record): string => (float) $record->pending_balance > 0 ? 'danger' : 'gray'),

                TextColumn::make('installments')->label('Cuotas'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CreditStatus::label($state))
                    ->color(fn (?string $state): string => CreditStatus::color($state)),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                /*
                 * El detalle se arma con los componentes del panel: así respeta el
                 * tema, el modo oscuro y el ancho del modal sin CSS propio.
                 */
                ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Credit $record): string => "Crédito #{$record->id}")
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => CreditStatus::label($state))
                                    ->color(fn (?string $state): string => CreditStatus::color($state)),

                                TextEntry::make('start_date')
                                    ->label('Inicio')
                                    ->date('d/m/Y'),

                                TextEntry::make('periodicity')
                                    ->label('Periodicidad')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'weekly' => 'Semanal',
                                        'monthly' => 'Mensual',
                                        'yearly' => 'Anual',
                                        default => (string) $state,
                                    }),

                                TextEntry::make('pending_balance')
                                    ->label('Saldo pendiente')
                                    ->money('USD')
                                    ->weight(FontWeight::Bold)
                                    ->color(fn (Credit $record): string => (float) $record->pending_balance > 0 ? 'danger' : 'gray'),

                                TextEntry::make('installment_amount')
                                    ->label('Monto de cuota')
                                    ->money('USD'),

                                TextEntry::make('interest_rate')
                                    ->label('Interés')
                                    ->state(fn (Credit $record): string => (($record->interest_type ?? 'auto') === 'fixed'
                                        ? 'Fijo ' : 'Calculado ')
                                        . (float) $record->interest_rate . '% por cuota'),

                                TextEntry::make('initial_amount')
                                    ->label('Monto inicial')
                                    ->money('USD'),

                                TextEntry::make('down_payment')
                                    ->label('Pago inicial')
                                    ->money('USD'),

                                TextEntry::make('financed_amount')
                                    ->label('Financiado')
                                    ->money('USD'),

                                TextEntry::make('total_interest')
                                    ->label('Intereses')
                                    ->money('USD'),

                                TextEntry::make('total_amount')
                                    ->label('Total a pagar')
                                    ->money('USD')
                                    ->color('success'),

                                TextEntry::make('installments')
                                    ->label('Cuotas'),

                                TextEntry::make('cuotas')
                                    ->label('Estado de las cuotas')
                                    ->state(fn (Credit $record): string => sprintf(
                                        '%d pagadas · %d pendientes · %d atrasadas',
                                        $record->installments()->where('status', 'paid')->count(),
                                        $record->installments()->where('status', 'pending')->count(),
                                        $record->installments()->where('status', 'late')->count(),
                                    ))
                                    ->columnSpan(2),

                                TextEntry::make('refinanciado')
                                    ->label('Refinanciado de')
                                    ->state(fn (Credit $record): ?string => $record->refinanced_from_id
                                        ? '#' . $record->refinanced_from_id
                                        : null)
                                    ->placeholder('—'),
                            ]),

                        RepeatableEntry::make('items')
                            ->label('Bienes del crédito')
                            ->state(fn (Credit $record) => $record->items()->get())
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label('Bien'),

                                TextEntry::make('total')
                                    ->label('Precio')
                                    ->money('USD'),
                            ])
                            ->columns(2)
                            ->contained(false)
                            ->columnSpanFull(),
                    ]),

                RefinanceCreditAction::make(),
            ]);
    }
}
