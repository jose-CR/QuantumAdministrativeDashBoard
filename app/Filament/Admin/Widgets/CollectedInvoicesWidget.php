<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Administration\Inflows\InflowResource;
use App\Models\Inflow;
use App\Support\PaymentStatus;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Las facturas que SÍ se cobraron en el rango de fechas del tablero: el detalle que hay
 * detrás del número «Cobrado».
 */
class CollectedInvoicesWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            // El encabezado va en la tabla, no en getHeading() de la clase.
            ->heading(__('ui.widgets.invoices_collected'))
            ->description(__('ui.widgets.invoices_collected_help'))
            ->query(fn (): Builder => $this->inflows()->where('amount_paid', '>', 0))
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('resources.flow.inflow.cod_invoice'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label(__('resources.flow.inflow.date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('customer.full_name')
                    ->label(__('resources.flow.inflow.client'))
                    ->searchable(),

                TextColumn::make('amount')
                    ->label(__('resources.flow.inflow.amount'))
                    ->money('usd')
                    ->alignEnd()
                    ->summarize(Sum::make()->label(__('ui.tables.total'))->money('usd')),

                TextColumn::make('amount_paid')
                    ->label(__('resources.flow.inflow.amount_paid'))
                    ->money('usd')
                    ->alignEnd()
                    ->color('success')
                    ->summarize(Sum::make()->label(__('ui.tables.total'))->money('usd')),

                TextColumn::make('receivable')
                    ->label(__('resources.flow.inflow.receivable'))
                    ->money('usd')
                    ->alignEnd()
                    ->color(fn (Inflow $record): string => (float) $record->receivable > 0 ? 'warning' : 'gray'),

                TextColumn::make('payment_status')
                    ->label(__('resources.flow.inflow.payment_status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PaymentStatus::label($state))
                    ->color(fn (?string $state): string => PaymentStatus::color($state)),
            ])
            ->recordUrl(fn (Inflow $record): string => InflowResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('ui.widgets.no_invoices'))
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    /**
     * Entradas del rango, con el cliente cargado.
     *
     * @return Builder<Inflow>
     */
    private function inflows(): Builder
    {
        $desde = ($this->pageFilters ?? [])['startDate'] ?? null;
        $hasta = ($this->pageFilters ?? [])['endDate'] ?? null;

        return Inflow::query()
            ->with('customer')
            ->when($desde, fn (Builder $query, string $fecha) => $query->whereDate('date', '>=', $fecha))
            ->when($hasta, fn (Builder $query, string $fecha) => $query->whereDate('date', '<=', $fecha));
    }
}
