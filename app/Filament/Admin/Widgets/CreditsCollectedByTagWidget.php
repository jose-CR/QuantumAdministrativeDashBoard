<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Credits\CreditsMonthSummary;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

/**
 * Lo COBRADO en el rango de fechas del tablero (Ingresos de clientes con crédito),
 * por etiqueta. Separado del vencimiento: una tabla por pregunta.
 */
class CreditsCollectedByTagWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('ui.widgets.credits_collected_by_tag');
    }

    public function getSubheading(): ?string
    {
        return __('ui.widgets.range_help', $this->range());
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => app(CreditsMonthSummary::class)->collected(
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
                    ->color('success')
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
