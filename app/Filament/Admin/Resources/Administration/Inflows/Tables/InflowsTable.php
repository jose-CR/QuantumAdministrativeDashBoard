<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Tables;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Models\Inflow;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InflowsTable
{
    public static function configure(Table $table): Table
    {
        $editUrl = fn (Inflow $record): string => InflowResource::getUrl('edit', ['record' => $record]);

        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('resources.flow.inflow.cod_invoice')),
                
                TextColumn::make('date')
                    ->date()
                    ->label(__('resources.flow.inflow.date')),

                TextColumn::make('customer.full_name')
                    ->label(__('resources.flow.inflow.client')),

                TextColumn::make('amount')
                    ->money('usd')
                    ->label(__('resources.flow.inflow.amount')),

                TextColumn::make('payment_method')
                    ->label(__('resources.flow.inflow.payment_method')),

                TextColumn::make('transfer_number')
                    ->label(__('resources.flow.inflow.transfer_number')),

                TextColumn::make('bank.name')
                    ->label(__('resources.clients.fields.bank')),

                TextColumn::make('transfer_date')
                    ->date()
                    ->label(__('resources.flow.inflow.transfer_date')),

                TextColumn::make('payment_status')
                    ->badge()
                    ->label(__('resources.flow.inflow.payment_status'))
                    ->color(fn (string $state): string => match ($state) {
                        'partial' => 'warning',
                        'pending' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('salesperson')
                    ->label(__('resources.flow.inflow.salesperson')),

                TextColumn::make('attachments_count')
                    ->label(__('resources.flow.attachment'))
                    ->counts('attachments')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "{$state} archivo(s)" : 'Sin adjuntos'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->url($editUrl),
                    DeleteAction::make(),
                    Action::make('verAdjuntos')
                        ->label(__('resources.flow.attachment'))
                        ->icon('heroicon-o-paper-clip')
                        ->modalHeading(__('resources.flow.attachment'))
                        ->modalContent(fn (Inflow $record) => view('filament.modals.attachments', [
                            'attachments' => $record->attachments,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('resources.flow.close')),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
