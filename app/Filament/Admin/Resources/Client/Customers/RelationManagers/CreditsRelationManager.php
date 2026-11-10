<?php

namespace App\Filament\Admin\Resources\Client\Customers\RelationManagers;

use App\Filament\Admin\Actions\RefinanceCreditAction;
use App\Models\Credit;
use App\Support\CreditStatus;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
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
                 * El detalle va en una vista propia, no en un esquema de entradas:
                 * es la misma información que se le imprime al cliente.
                 */
                ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Credit $record): string => "Crédito #{$record->id}")
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (Credit $record) => view(
                        'filament.modals.credit-summary',
                        ['credit' => $record],
                    )),

                RefinanceCreditAction::make(),
            ]);
    }
}
