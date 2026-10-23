<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Inflow;
use App\Models\Outflow;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Builder;

class CashFlowStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return 'Flujo de caja';
    }

    protected function getStats(): array
    {
        $inflows = $this->totalInRange(Inflow::class, 'date');
        $outflows = $this->totalInRange(Outflow::class, 'invoice_date');
        $balance = $inflows - $outflows;

        return [
            Stat::make('Entradas', $this->money($inflows))
                ->description('Dinero que ingresó')
                ->color('success'),
            Stat::make('Salidas', $this->money($outflows))
                ->description('Gastos y compras')
                ->color('danger'),
            Stat::make('Balance', $this->money($balance))
                ->description('Entradas menos salidas')
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }

    private function totalInRange(string $model, string $dateColumn): float
    {
        return (float) $model::query()
            ->when(
                $this->pageFilters['startDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate($dateColumn, '>=', $date),
            )
            ->when(
                $this->pageFilters['endDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate($dateColumn, '<=', $date),
            )
            ->sum('amount');
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, 'USD');
    }
}