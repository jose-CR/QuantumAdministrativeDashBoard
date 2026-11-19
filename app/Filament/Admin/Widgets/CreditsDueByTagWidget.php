<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Credits\CreditsMonthSummary;
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

    public function getHeading(): ?string
    {
        return __('ui.widgets.credits_due_by_tag');
    }

    public function getSubheading(): ?string
    {
        return __('ui.widgets.range_help', $this->range());
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => app(CreditsMonthSummary::class)->due(
                ($this->pageFilters ?? [])['startDate'] ?? null,
                ($this->pageFilters ?? [])['endDate'] ?? null,
            ))
            ->columns([
                TextColumn::make('tag')
                    ->label(__('ui.widgets.tag'))
                    ->weight(fn (array $record): string => $record['is_total'] ? 'bold' : 'normal'),

                TextColumn::make('amount')
                    ->label(__('ui.widgets.amount'))
                    ->money('usd')
                    ->weight(fn (array $record): string => $record['is_total'] ? 'bold' : 'normal'),
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
