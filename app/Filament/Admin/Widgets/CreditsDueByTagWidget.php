<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Credits\CreditsMonthSummary;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

/**
 * Lo que VENCE en el rango de fechas del tablero, por etiqueta del cliente.
 *
 * Es solo el vencimiento: lo cobrado va en su propia tabla, porque se mide con otra
 * fecha y mezclarlos daba diferencias que no significaban nada.
 */
class CreditsDueByTagWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            // El encabezado va en la TABLA: en un TableWidget el de la clase no se usa
            // y Filament termina mostrando el nombre de la clase ("Credits Due By Tag").
            ->heading(__('ui.widgets.credits_due_by_tag'))
            ->description(fn (): string => __('ui.widgets.range_help', $this->range()))
            ->records(fn (): array => app(CreditsMonthSummary::class)->due(
                ($this->pageFilters ?? [])['startDate'] ?? null,
                ($this->pageFilters ?? [])['endDate'] ?? null,
            ))
            ->columns([
                TextColumn::make('tag')
                    ->label(__('ui.widgets.tag'))
                    ->weight(fn (array $record): string => $record['is_total'] ? 'bold' : 'normal')
                    ->description(function (array $record): ?string {
                        $total = count($record['customers']);

                        if ($total === 0) {
                            return null;
                        }

                        $nombres = collect($record['customers'])->take(3)->pluck('name')->implode(', ');

                        return $total > 3 ? $nombres . ' +' . ($total - 3) : $nombres;
                    }),

                TextColumn::make('amount')
                    ->label(__('ui.widgets.amount'))
                    ->money('usd')
                    ->color('gray')
                    ->weight(fn (array $record): string => $record['is_total'] ? 'bold' : 'normal'),
            ])
            ->recordActions([
                Action::make('clientes')
                    ->label(__('ui.widgets.see_customers'))
                    ->icon('heroicon-o-users')
                    ->color('gray')
                    ->modalHeading(fn (array $record): string => $record['tag'])
                    ->modalContent(fn (array $record) => view('filament.modals.tag-customers', [
                        'clientes' => $record['customers'],
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('ui.widgets.close')),
            ])
            ->paginated(false);
    }

    /**
     * @return array{from: string, to: string}
     */
    private function range(): array
    {
        $desde = ($this->pageFilters ?? [])['startDate'] ?? null;
        $hasta = ($this->pageFilters ?? [])['endDate'] ?? null;

        return [
            'from' => $desde ? Carbon::parse($desde)->format('d/m/Y') : now()->startOfMonth()->format('d/m/Y'),
            'to' => $hasta ? Carbon::parse($hasta)->format('d/m/Y') : now()->endOfMonth()->format('d/m/Y'),
        ];
    }
}
