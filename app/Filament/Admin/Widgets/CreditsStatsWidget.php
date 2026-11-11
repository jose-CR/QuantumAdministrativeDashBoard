<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Credit;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class CreditsStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    private const ACTIVE_STATUS = 'active';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('ui.widgets.credits');
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('ui.widgets.active_credits'), $this->activeCredits()->count())
                ->description('En curso')
                ->color('primary'),
            Stat::make(__('ui.widgets.pending_balance'), $this->money($this->activeCredits()->sum('pending_balance')))
                ->description(__('ui.widgets.pending_balance_desc'))
                ->color('warning'),
/*             Stat::make('Financiado', $this->money($this->financedInRange()))
                ->description(__('ui.widgets.financed_desc'))
                ->color('success'), */
        ];
    }

    private function activeCredits(): Builder
    {
        return Credit::query()->where('status', self::ACTIVE_STATUS);
    }

    private function financedInRange(): float
    {
        return (float) Credit::query()
            ->when(
                ($this->pageFilters ?? [])['startDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('start_date', '>=', $date),
            )
            ->when(
                ($this->pageFilters ?? [])['endDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('start_date', '<=', $date),
            )
            ->sum('financed_amount');
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, 'USD');
    }
}