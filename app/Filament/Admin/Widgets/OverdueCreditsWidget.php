<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use App\Models\Credit;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OverdueCreditsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    public function getHeading(): ?string
    {
        return __('ui.widgets.overdue_credits');
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->overdueCredits())
            ->columns([
                TextColumn::make('client.full_name') // relación
                    ->label(__('ui.columns.customer'))
                    ->searchable(),
                TextColumn::make('overdue_count')
                    ->label(__('ui.widgets.overdue_installments'))
                    ->badge()
                    ->color('danger'),
                TextColumn::make('oldest_due_date')
                    ->label('Vence desde')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('pending_balance')
                    ->label(__('resources.credits.credits.pending_balance'))
                    ->money('USD'),
            ])
            ->defaultSort('oldest_due_date')
            ->recordActions([
                Action::make('view')
                    ->label(__('ui.widgets.see_customer'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Credit $record): string => CustomerResource::getUrl('view', [
                        'record' => $record->customer_id,
                    ])),
            ])
            ->paginated([5, 10]);
    }

    private function overdueCredits(): Builder
    {
        return Credit::query()
            ->overdue()
            ->with('customer') // relación
            ->withCount(['installments as overdue_count' => fn (Builder $query) => $query->overdue()])
            ->withMin(['installments as oldest_due_date' => fn (Builder $query) => $query->overdue()], 'due_date');
    }
}