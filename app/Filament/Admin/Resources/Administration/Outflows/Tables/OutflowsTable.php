<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Tables;

use App\Filament\Admin\Actions\ManageTagsAction;
use App\Filament\Admin\Resources\Administration\Outflows\OutflowResource;
use App\Models\Outflow;
use App\Support\DocumentTypes;
use App\Support\TagTypes;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Filament\Concerns\MonthFilter;
use App\Filament\Concerns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->label(__('resources.flow.outflow.company'))
                    ->searchable(),

                TextColumn::make('document_type')
                    ->label(__('ui.document_types.label'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => DocumentTypes::label($state))
                    ->color(fn (?string $state): string => DocumentTypes::color($state)),

                TextColumn::make('invoice_code')
                    ->label(__('resources.flow.outflow.cod_invoice'))
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label(__('resources.flow.outflow.quantity')),

                TextColumn::make('amount')
                    ->money('usd')
                    ->label(__('resources.flow.outflow.amount')),

                TextColumn::make('source')->label(__('resources.flow.outflow.source')),

                TextColumn::make('area')
                    ->label(__('resources.flow.outflow.area'))
                    ->searchable(),

                // Etiquetas de la salida (tipo `outflow`): para marcar, por ejemplo,
                // compras de repuestos o gastos de taller.
                TagsColumn::make(TagTypes::OUTFLOW, __('resources.clients.fields.tags.tags')),

                TextColumn::make('items_count')
                    ->label(__('ui.tables.items'))
                    ->counts('items')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} artículo(s)" : '—'),

                TextColumn::make('attachments_count')
                    ->label(__('resources.flow.attachment'))
                    ->counts('attachments')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "{$state} archivo(s)" : __('ui.tables.no_attachments')),
            ])
            ->filters([
                MonthFilter::make(Outflow::class, 'invoice_date'),

                SelectFilter::make('document_type')
                    ->label(__('ui.document_types.label'))
                    ->options(DocumentTypes::options(DocumentTypes::outflow())),
            ])
            // Más reciente primero: la última compra es la que se mira.
            ->defaultSort('invoice_date', 'desc')
            ->persistSortInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make()
                    ->url($editUrl),
                DeleteAction::make()
                    ->modalHeading(fn (Outflow $record) => "__('resources.flow.delete') {$record->invoice_code}"),
                ManageTagsAction::make(TagTypes::OUTFLOW),

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
