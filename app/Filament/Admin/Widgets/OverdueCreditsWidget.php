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

    protected static ?string $heading = 'Créditos vencidos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->overdueCredits())
            ->columns([
                TextColumn::make('client.full_name') // relación
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('overdue_count')
                    ->label('Cuotas vencidas')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('oldest_due_date')
                    ->label('Vence desde')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('pending_balance')
                    ->label('Saldo pendiente')
                    ->money('USD'),
            ])
            ->defaultSort('oldest_due_date')
            ->recordActions([
                Action::make('view')
                    ->label('Ver cliente')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Credit $record): string => CustomerResource::getUrl('edit', [
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