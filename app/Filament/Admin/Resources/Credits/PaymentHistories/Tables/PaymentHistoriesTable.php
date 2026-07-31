<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Tables;

use App\Utils\Filament\FilamentSearch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                        ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('credit_id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bank_id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label(__('resources.credits.payment_histories.notes'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('credit.client.full_name')
                    ->label(__('resources.credits.clients.client'))
                    ->searchable(
                        query: function ($query, $search) {
                            FilamentSearch::relationColumns($query, 'credit.client', $search, ['full_name',]);
                        }
                    ),

                TextColumn::make('amount')
                    ->label(__('resources.credits.payment_histories.amount')),

                TextColumn::make('payment_method')
                    ->label(__('resources.credits.payment_histories.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'cash' => 'Efectivo',
                        'bank_transfer' => 'transferencia bancaria',
                        'card' => 'Tarjeta',
                        default => $state,
                    }),
                
                TextColumn::make('bank.name')
                    ->label(__('resources.credits.payment_histories.bank'))
                    ->default('N/A'),

                TextColumn::make('payment_date')
                    ->label(__('resources.credits.payment_histories.payment_date')),

                TextColumn::make('receipt_number')
                    ->label(__('resources.credits.payment_histories.receipt_number'))
                    ->searchable(),

                TextColumn::make('previous_balance')
                    ->label(__('resources.credits.payment_histories.previous_balance')),

                TextColumn::make('new_balance')
                    ->label(__('resources.credits.payment_histories.new_balance')),

                //
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
