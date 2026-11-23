<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Cash\CashInvoices;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

/**
 * Dos filas —cobradas y por cobrar— con sus cifras y un botón que abre el detalle de
 * las facturas en un modal.
 *
 * Antes eran dos tablas completas en el tablero: llenaban la pantalla y los números se
 * perdían entre tanto listado.
 */
class CashInvoicesWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            // El encabezado va en la tabla, no en getHeading() de la clase.
            ->heading(__('ui.widgets.cash_invoices'))
            ->description(__('ui.widgets.cash_invoices_help'))
            ->records(function (): array {
                $resumen = app(CashInvoices::class)->summary(
                    ($this->pageFilters ?? [])['startDate'] ?? null,
                    ($this->pageFilters ?? [])['endDate'] ?? null,
                );

                // Las filas van con clave (cobradas / por cobrar): así el modal sabe
                // qué fila se abrió.
                return [
                    'collected' => [
                        'kind' => 'collected',
                        'label' => __('ui.widgets.collected'),
                        'count' => $resumen['collected']['count'],
                        'amount' => $resumen['collected']['amount'],
                        'invoices' => $resumen['collected']['invoices'],
                    ],
                    'pending' => [
                        'kind' => 'pending',
                        'label' => __('ui.widgets.receivable'),
                        'count' => $resumen['pending']['count'],
                        'amount' => $resumen['pending']['amount'],
                        'invoices' => $resumen['pending']['invoices'],
                    ],
                ];
            })
            ->columns([
                TextColumn::make('label')
                    ->label(__('ui.widgets.concept'))
                    ->weight('bold'),

                TextColumn::make('count')
                    ->label(__('ui.widgets.invoice_count'))
                    ->formatStateUsing(fn (int $state): string => (string) $state),

                TextColumn::make('amount')
                    ->label(__('ui.widgets.amount'))
                    ->money('usd')
                    ->weight('bold')
                    ->color(fn (array $record): string => $record['kind'] === 'collected' ? 'success' : 'warning'),
            ])
            ->recordActions([
                Action::make('facturas')
                    ->label(__('ui.widgets.see_invoices'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading(fn (array $record): string => $record['label'])
                    ->modalWidth('4xl')
                    ->modalContent(fn (array $record) => view('filament.modals.invoice-list', [
                        'facturas' => $record['invoices'],
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('ui.widgets.close')),
            ])
            ->paginated(false);
    }
}
