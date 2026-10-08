<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Tables;

use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use App\Models\Outflow;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutflowsTable
{
    public static function configure(Table $table): Table
    {
        $editUrl = fn (Outflow $record): string => OutflowResource::getUrl('edit', ['record' => $record]);

        return $table
            ->columns([
                TextColumn::make('invoice_date')
                    ->date()
                    ->label(__('resources.flow.outflow.date')),

                TextColumn::make('company')
                    ->label(__('resources.flow.outflow.company')),

                TextColumn::make('invoice_code')
                    ->label(__('resources.flow.outflow.cod_invoice')),

                TextColumn::make('quantity')
                    ->label(__('resources.flow.outflow.quantity')),

                TextColumn::make('amount')
                    ->money('usd')
                    ->label(__('resources.flow.outflow.amount')),

                TextColumn::make('source')->label(__('resources.flow.outflow.source')),

                TextColumn::make('area')->label(__('resources.flow.outflow.area')),

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
                EditAction::make()
                    ->url($editUrl),
                DeleteAction::make()
                    ->modalHeading(fn (Outflow $record) => "__('resources.flow.delete') {$record->invoice_code}"),
                Action::make('verAdjuntos')
                    ->label(__('resources.flow.see_attachment'))
                    ->icon('heroicon-o-paper-clip')
                    ->modalHeading(__('resources.flow.attachment'))
                    ->modalContent(fn (Outflow $record) => view('filament.modals.attachments', [
                        'attachments' => $record->attachments,
                    ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('resources.flow.close')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
