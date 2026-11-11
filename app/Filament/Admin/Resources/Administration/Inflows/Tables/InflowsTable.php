<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Tables;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Models\Inflow;
use App\Support\PaymentStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->label(__('resources.flow.inflow.amount'))
                    ->summarize(Sum::make()->label(__('ui.tables.total'))->money('usd')),

                TextColumn::make('amount_paid')
                    ->money('usd')
                    ->label(__('resources.flow.inflow.amount_paid'))
                    ->color(fn (Inflow $record): string => (float) $record->amount_paid > 0 ? 'success' : 'gray')
                    ->summarize(Sum::make()->label('Cobrado')->money('usd')),

                TextColumn::make('receivable')
                    ->money('usd')
                    ->label(__('resources.flow.inflow.receivable'))
                    ->color(fn (Inflow $record): string => (float) $record->receivable > 0 ? 'danger' : 'gray'),

                TextColumn::make('payment_method')
                    ->label(__('resources.flow.inflow.payment_method'))
                    ->formatStateUsing(fn (?string $state): string => PaymentStatus::methodLabel($state)),

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
                    ->formatStateUsing(fn (?string $state): string => PaymentStatus::label($state))
                    ->color(fn (?string $state): string => PaymentStatus::color($state)),

                TextColumn::make('salesperson')
                    ->label(__('resources.flow.inflow.salesperson')),

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
                SelectFilter::make('payment_status')
                    ->label(__('resources.flow.inflow.payment_status'))
                    ->options([
                        'pending' => __('resources.clients.statuses.pending'),
                        'partial' => __('resources.clients.statuses.partial'),
                        'paid' => __('resources.clients.statuses.paid'),
                    ]),

                Filter::make('rango')
                    ->label(__('ui.tables.date_range'))
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('date', '>=', $fecha))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('date', '<=', $fecha))),

                // Los parciales viejos no tienen monto cobrado: hay que revisarlos
                // a mano porque el dato no se registró nunca.
                Filter::make('parciales_sin_cobro')
                    ->label(__('ui.tables.partials_without_payment'))
                    ->query(fn (Builder $query): Builder => $query
                        ->where('payment_status', 'partial')
                        ->where('amount_paid', '<=', 0)),
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
