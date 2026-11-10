<?php

namespace App\Filament\Admin\pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Filament\Admin\Widgets\ArticlesStatsWidget;
use App\Filament\Admin\Widgets\CashFlowStatsWidget;
use App\Filament\Admin\Widgets\CreditsStatsWidget;
use App\Filament\Admin\Widgets\CustomersStatsWidget;
use App\Filament\Admin\Widgets\OverdueCreditsWidget;
use Filament\Widgets\Widget;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    /**
     * El tablero existía vacío: los widgets estaban escritos pero nadie los
     * registraba. Acá se arma el informe del día.
     *
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [
            CashFlowStatsWidget::class,
            CreditsStatsWidget::class,
            CustomersStatsWidget::class,
            ArticlesStatsWidget::class,
            OverdueCreditsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate'),
                        DatePicker::make('endDate'),
                        // ...
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}