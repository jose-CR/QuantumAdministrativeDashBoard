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
        return __('ui.widgets.cash_flow');
    }

    protected function getStats(): array
    {
        $inflows = $this->totalInRange(Inflow::class, 'date');
        $outflows = $this->totalInRange(Outflow::class, 'invoice_date');
        $balance = $inflows - $outflows;

        return [
            Stat::make(__('ui.widgets.inflows'), $this->money($inflows))
                ->description(__('ui.widgets.inflows_desc'))
                ->color('success'),
            Stat::make(__('ui.widgets.outflows'), $this->money($outflows))
                ->description('Gastos y compras')
                ->color('danger'),
            Stat::make('Balance', $this->money($balance))
                ->description(__('ui.widgets.balance_desc'))
                ->color($balance >= 0 ? 'success' : 'danger'),
            Stat::make(__('ui.widgets.collected'), $this->money($this->totalInRange(Inflow::class, 'date', 'amount_paid')))
                ->description(__('ui.widgets.collected_desc'))
                ->color('info'),
            Stat::make(__('ui.widgets.receivable'), $this->money($inflows - $this->totalInRange(Inflow::class, 'date', 'amount_paid')))
                ->description(__('ui.widgets.receivable_desc'))
                ->color('warning'),
        ];
    }

    private function totalInRange(string $model, string $dateColumn, string $sumColumn = 'amount'): float
    {
        return (float) $model::query()
            ->when(
                ($this->pageFilters ?? [])['startDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate($dateColumn, '>=', $date),
            )
            ->when(
                ($this->pageFilters ?? [])['endDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate($dateColumn, '<=', $date),
            )
            ->sum($sumColumn);
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, 'USD');
    }
}