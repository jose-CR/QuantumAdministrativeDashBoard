<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Credit;
use App\Models\Customer;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class CustomersStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function getHeading(): ?string
    {
        return 'Clientes';
    }

    protected function getStats(): array
    {
        return [
            $this->totalCustomers(),
            $this->newCustomersThisMonth(),
            $this->overdueCustomers(),
        ];
    }

    private function totalCustomers(): Stat
    {
        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;

        return Stat::make('Clientes', Customer::query()
                    ->when($startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $startDate))
                    ->when($endDate, fn (Builder $query) => $query->whereDate('created_at', '<=', $endDate))
                    ->count())
            ->description('Registrados en total')
            ->color('primary');
    }

    private function newCustomersThisMonth(): Stat
    {
        $count = Customer::where('created_at', '>=', now()->startOfMonth())->count();

        return Stat::make('Nuevos este mes', $count)
            ->description('Clientes dados de alta')
            ->descriptionIcon(Heroicon::ArrowTrendingUp)
            ->color('success');
    }

    private function overdueCustomers(): Stat
    {
        $count = Credit::overdue()->distinct('customer_id')->count('customer_id');

        return Stat::make('Clientes en mora', $count)
            ->description('Con cuotas vencidas sin pagar')
            ->descriptionIcon(Heroicon::ExclamationTriangle)
            ->color($count > 0 ? 'danger' : 'success');
    }
}