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
                    ->label('N° Factura'),
                
                TextColumn::make('date')
                    ->date()
                    ->label('Fecha'),

                TextColumn::make('customer.full_name')
                    ->label('Cliente'),

                TextColumn::make('amount')
                    ->money('usd')
                    ->label('Monto'),

                TextColumn::make('payment_method')
                    ->label('Forma de Pago'),

                TextColumn::make('transfer_number')
                    ->label('N° Transferencia'),

                TextColumn::make('bank.name')
                    ->label('Banco'),

                TextColumn::make('transfer_date')
                    ->date()
                    ->label('Fecha de Transsaccion'),

                TextColumn::make('payment_status')
                    ->badge()
                    ->label('Estado de Pago'),

                TextColumn::make('salesperson')
                    ->label('Vendedor'),

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
                ActionGroup::make([
                    EditAction::make()
                        ->url($editUrl),
                    DeleteAction::make(),
                    Action::make('verAdjuntos')
                        ->label('Ver adjuntos')
                        ->icon('heroicon-o-paper-clip')
                        ->modalHeading('Adjuntos')
                        ->modalContent(fn (Inflow $record) => view('filament.modals.attachments', [
                            'attachments' => $record->attachments,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar'),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
