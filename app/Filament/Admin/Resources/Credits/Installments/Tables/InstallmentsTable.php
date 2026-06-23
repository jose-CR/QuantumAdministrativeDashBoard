<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'credit.client',
                'credit.articleUnit',
            ]))
            ->columns([
                TextColumn::make('credit.client.full_name')
                    ->label('Crédito'),

                TextColumn::make('credit.articleUnit.display_name')
                    ->label('Vehículo')
                    ->searchable(),

                TextColumn::make('number')
                    ->label('Cuota'),

                TextColumn::make('amount')
                    ->money('USD'),

                TextColumn::make('remaining_balance')
                    ->money('USD'),

                TextColumn::make('paid_amount')
                    ->label('Abonado')
                    ->state(
                        fn ($record) =>
                        $record->amount - $record->remaining_balance
                    )
                    ->money('USD'),

                TextColumn::make('due_date')
                    ->date(),

                TextColumn::make('paid_at')
                    ->date(),

                TextColumn::make('status')
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
