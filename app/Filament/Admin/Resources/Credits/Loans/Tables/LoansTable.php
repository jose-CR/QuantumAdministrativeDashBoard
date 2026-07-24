<?php

namespace App\Filament\Admin\Resources\Credits\Loans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
                ->label(__('resources.credits.clients.client'))
                ->searchable()
                ->sortable(),

            TextColumn::make('articleUnit.display_name')
                ->label(__('resources.credits.clients.vehicle'))
                ->searchable(),

            TextColumn::make('down_payment')
                ->label(__('resources.credits.credits.down_payment'))
                ->money('USD')
                ->sortable(),

            TextColumn::make('financed_amount')
                ->label(__('resources.credits.credits.financed_amount'))
                ->money('USD')
                ->sortable(),

            TextColumn::make('installments')
                ->label(__('resources.credits.credits.installments'))
                ->alignCenter(),

            TextColumn::make('installment_amount')
                ->label(__('resources.credits.credits.installment_amount'))
                ->money('USD'),

            TextColumn::make('pending_balance')
                ->label(__('resources.credits.credits.pending_balance'))
                ->money('USD')
                ->sortable(),

            TextColumn::make('status')
                ->label(__('resources.credits.credits.status'))
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
                ->label(__('resources.credits.credits.initial_amount'))
                ->money('USD')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('interest_rate')
                ->label(__('resources.credits.credits.interest_rate'))
                ->suffix('%')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('total_interest')
                ->label(__('resources.credits.credits.total_interest'))
                ->money('USD')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('total_amount')
                ->label(__('resources.credits.credits.total_amount'))
                ->money('USD')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('periodicity')
                ->label(__('resources.credits.credits.periodicity'))
                ->badge()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('start_date')
                ->label(__('resources.credits.credits.start_date'))
                ->date('d/m/Y')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('payment_day')
                ->label(__('resources.credits.credits.payment_day'))
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('payment_month')
                ->label(__('resources.credits.credits.payment_month'))
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('originalCredit.id')
                ->label(__('resources.credits.credits.originalCredit'))
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
