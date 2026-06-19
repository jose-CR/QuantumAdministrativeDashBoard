<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('client.full_name')
                ->label('Cliente')
                ->searchable()
                ->sortable(),

            TextColumn::make('articleUnit.display_name')
                ->label('Vehículo')
                ->searchable(),

            TextColumn::make('down_payment')
                ->label('Prima')
                ->money('USD')
                ->sortable(),

            TextColumn::make('financed_amount')
                ->label('Financiado')
                ->money('USD')
                ->sortable(),

            TextColumn::make('installments')
                ->label('Cuotas')
                ->alignCenter(),

            TextColumn::make('installment_amount')
                ->label('Valor Cuota')
                ->money('USD'),

            TextColumn::make('pending_balance')
                ->label('Saldo Pendiente')
                ->money('USD')
                ->sortable(),

            TextColumn::make('status')
                ->label('Estado')
                ->badge()
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'pending'   => 'Pendiente',
                    'active'    => 'Activo',
                    'paid'      => 'Pagado',
                    'cancelled' => 'Cancelado',
                    default     => $state,
                })
                ->color(fn (string $state) => match ($state) {
                    'pending'   => 'warning',
                    'active'    => 'success',
                    'paid'      => 'info',
                    'cancelled' => 'danger',
                    default     => 'gray',
                }),

            // Ocultas por defecto
            TextColumn::make('initial_amount')
                ->label('Monto Inicial')
                ->money('USD')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('interest_rate')
                ->label('Interés')
                ->suffix('%')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('total_interest')
                ->label('Interés Total')
                ->money('USD')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('total_amount')
                ->label('Total a Pagar')
                ->money('USD')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('periodicity')
                ->label('Periodicidad')
                ->badge()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('start_date')
                ->label('Fecha Inicio')
                ->date('d/m/Y')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('payment_day')
                ->label('Día Pago')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('payment_month')
                ->label('Mes Pago')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('originalCredit.id')
                ->label('Refinancia Crédito')
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
