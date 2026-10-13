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
        return 'Créditos';
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Créditos activos', $this->activeCredits()->count())
                ->description('En curso')
                ->color('primary'),
            Stat::make('Saldo por cobrar', $this->money($this->activeCredits()->sum('pending_balance')))
                ->description('Pendiente de créditos activos')
                ->color('warning'),
            Stat::make('Financiado', $this->money($this->financedInRange()))
                ->description('Créditos iniciados en el período')
                ->color('success'),
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
                $this->pageFilters['startDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('start_date', '>=', $date),
            )
            ->when(
                $this->pageFilters['endDate'] ?? null,
                fn (Builder $query, string $date) => $query->whereDate('start_date', '<=', $date),
            )
            ->sum('financed_amount');
    }

    private function money(float $amount): string
    {
        return Number::currency($amount, 'USD');
    }
}