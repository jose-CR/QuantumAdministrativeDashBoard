<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories\Tables;

use App\Utils\Filament\FilamentSearch;
use Filament\Actions\BulkActionGroup;
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
                TextColumn::make('credit.client.full_name')
                    ->label('Cliente')
                    ->searchable(
                        query: function ($query, $search) {
                            FilamentSearch::relationColumns($query, 'credit.client', $search,['first_name', 'last_name',]);
                        }
                    ),
                
                TextColumn::make('amount')
                    ->label('monto'),
                
                TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'cash' => 'Efectivo',
                        'bank_transfer' => 'transferencia bancaria',
                        'card' => 'Tarjeta',
                        default => $state,                   
                    }),
                
                TextColumn::make('payment_date')
                    ->label('dia de pago'),
                
                TextColumn::make('receipt_number')
                    ->label('numero de facturacion o transferencia')
                    ->searchable(),
                    
                TextColumn::make('previous_balance')
                    ->label('balance previo'),
                
                TextColumn::make('new_balance')
                    ->label('nuevo balance'),                
                    
                //
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
