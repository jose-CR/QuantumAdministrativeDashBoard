<?php

namespace App\Filament\Admin\pages;

use App\Support\Salespeople;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Filament\Admin\Widgets\ArticlesStatsWidget;
use App\Filament\Admin\Widgets\CashFlowStatsWidget;
use App\Filament\Admin\Widgets\CreditsCollectedByTagWidget;
use App\Filament\Admin\Widgets\CreditsDueByTagWidget;
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
            CreditsDueByTagWidget::class,
            CreditsCollectedByTagWidget::class,
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
                        DatePicker::make('startDate')
                            ->label(__('ui.widgets.start_date')),

                        DatePicker::make('endDate')
                            ->label(__('ui.widgets.end_date')),

                        // Filtra la cobranza: el vendedor se guarda en la entrada de caja.
                        Select::make('salesperson')
                            ->label(__('ui.columns.salesperson'))
                            ->options(fn (): array => Salespeople::all())
                            ->searchable()
                            ->placeholder(__('ui.widgets.all_salespeople')),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}