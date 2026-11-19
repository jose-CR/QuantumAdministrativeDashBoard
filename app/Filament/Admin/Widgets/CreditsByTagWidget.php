<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Credits\CreditsMonthSummary;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

/**
 * Cuánto vence y cuánto se cobró en el rango de fechas del tablero, por etiqueta de
 * cliente: «Crédito de ventas», «Crédito de ventas y empresa», y las que se agreguen.
 */
class CreditsByTagWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('ui.widgets.credits_by_tag');
    }

    public function getSubheading(): ?string
    {
        [$desde, $hasta] = $this->range();

        return __('ui.widgets.credits_by_tag_help', ['from' => $desde, 'to' => $hasta]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => app(CreditsMonthSummary::class)->rows(
                ($this->pageFilters ?? [])['startDate'] ?? null,
                ($this->pageFilters ?? [])['endDate'] ?? null,
            ))
            ->columns([
                TextColumn::make('tag')
                    ->label(__('ui.widgets.tag'))
                    ->weight(fn (array $record): string => $record['is_total'] ? 'bold' : 'normal'),

                // Sin sumarizador al pie: la última fila ya es el total (sumarizador
                // necesita una consulta y esta tabla se arma con registros calculados).
                TextColumn::make('installments')
                    ->label(__('ui.widgets.installments_month'))
                    ->money('usd'),

                TextColumn::make('collected')
                    ->label(__('ui.widgets.collected_month'))
                    ->money('usd')
                    ->color('success'),

                TextColumn::make('difference')
                    ->label(__('ui.widgets.difference_month'))
                    ->money('usd')
                    ->color(fn (array $record): string => $record['difference'] > 0 ? 'danger' : 'gray'),
            ])
            ->paginated(false);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function range(): array
    {
        $desde = ($this->pageFilters ?? [])['startDate'] ?? null;
        $hasta = ($this->pageFilters ?? [])['endDate'] ?? null;

        return [
            $desde ? \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') : now()->startOfMonth()->format('d/m/Y'),
            $hasta ? \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') : now()->endOfMonth()->format('d/m/Y'),
        ];
    }
}
