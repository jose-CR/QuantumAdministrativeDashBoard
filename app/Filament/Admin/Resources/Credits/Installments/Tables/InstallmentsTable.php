<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('credit_id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('credit.client.full_name')
                    ->label(__('resources.credits.installment.credit'))
                    ->searchable(),

                TextColumn::make('credit.articleUnit.display_name')
                    ->label(__('resources.credits.installment.vehicle')),

                TextColumn::make('number')
                    ->label(__('resources.credits.installment.number')),

                TextColumn::make('amount')
                    ->label(__('resources.credits.installment.amount'))
                    ->money('USD'),

                TextColumn::make('remaining_balance')
                    ->label(__('resources.credits.installment.remaining_balance'))
                    ->money('USD'),

                TextColumn::make('paid_amount')
                    ->label(__('resources.credits.installment.paid_amount'))
                    ->state(
                        fn ($record) =>
                        $record->amount - $record->remaining_balance
                    )
                    ->money('USD'),

                TextColumn::make('due_date')
                    ->label(__('resources.credits.installment.due_date'))
                    ->date(),

                TextColumn::make('paid_at')
                    ->label(__('resources.credits.installment.paid_at'))
                    ->date(),

                TextColumn::make('status')
                    ->label(__('resources.credits.installment.status'))
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
