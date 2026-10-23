<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Article;
use App\Models\Category;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ArticlesStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 4;

    protected function getHeading(): ?string
    {
        return 'Inventario';
    }

    protected function getStats(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;

        return [
            Stat::make('Artículos', Article::query()
                    ->when($startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $startDate))
                    ->when($endDate, fn (Builder $query) => $query->whereDate('created_at', '<=', $endDate))
                    ->count())
                ->description('Modelos en el catálogo')
                ->color('primary'),
            Stat::make('Categorías', Category::count())
                ->description('Para clasificarlos')
                ->color('info'),
        ];
    }
}