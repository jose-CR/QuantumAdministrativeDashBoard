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
        $deleteUrl = fn (Outflow $record): string => OutflowResource::getUrl('delete', ['record' => $record]);

        return $table
            ->columns([
                TextColumn::make('invoice_date')
                    ->date()
                    ->label('Fecha'),

                TextColumn::make('company')
                    ->label('Empresa'),

                TextColumn::make('invoice_code')
                    ->label('Cod. Factura'),

                TextColumn::make('quantity')
                    ->label('Cant'),

                TextColumn::make('amount')
                    ->money('usd')
                    ->label('Monto'),

                TextColumn::make('source')->label('Caja'),

                TextColumn::make('area')->label('Área'),

                TextColumn::make('attachments_count')
                    ->label('Adjuntos')
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
                    ->modalHeading(fn (Outflow $record) => "Eliminar factura {$record->invoice_code}"),
                Action::make('verAdjuntos')
                    ->label('Ver adjuntos')
                    ->icon('heroicon-o-paper-clip')
                    ->modalHeading('Adjuntos')
                    ->modalContent(fn (Outflow $record) => view('filament.modals.attachments', [
                        'attachments' => $record->attachments,
                    ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
