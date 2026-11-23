<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Actions\CustomerStatementAction;
use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use App\Filament\Admin\Resources\Credits\Loans\LoansResource;
use App\Models\Credit;
use Filament\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Lo que hay que cobrar hoy: un renglón por crédito con cuotas vencidas.
 *
 * Primero lo que más plata tiene atrasada, con los días de atraso a la vista. Antes
 * ordenaba por fecha de vencimiento y mostraba el saldo del crédito entero, que no
 * dice cuánto está vencido.
 */
class OverdueCreditsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    /**
     * El resumen que va debajo del título («3 créditos · 1.250,00»).
     */
    public function summary(): ?string
    {
        $creditos = $this->overdueCredits()->count();

        if ($creditos === 0) {
            return null;
        }

        $monto = (float) $this->overdueCredits()
            ->withSum(['installments as overdue_amount' => fn (Builder $q) => $q->overdue()], 'remaining_balance')
            ->get()
            ->sum('overdue_amount');

        return __('ui.widgets.overdue_summary', [
            'count' => $creditos,
            'amount' => number_format($monto, 2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // En un TableWidget el encabezado va en la tabla; el de la clase no se usa
            // y Filament muestra el nombre de la clase ("Overdue Credits").
            ->heading(__('ui.widgets.overdue_credits'))
            ->description(fn (): ?string => $this->summary())
            ->query($this->overdueCredits())
            ->columns([
                TextColumn::make('id')
                    ->label(__('ui.widgets.credit_number'))
                    ->formatStateUsing(fn (int $state): string => '#' . $state)
                    ->sortable(),

                TextColumn::make('client.full_name')
                    ->label(__('ui.columns.customer'))
                    ->searchable()
                    ->description(fn (Credit $record): ?string => $record->client?->document_number),

                TextColumn::make('overdue_count')
                    ->label(__('ui.widgets.overdue_installments'))
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                // Días desde la cuota vencida más antigua: es lo que apura la llamada.
                TextColumn::make('days_late')
                    ->label(__('ui.widgets.overdue_days'))
                    ->badge()
                    ->state(fn (Credit $record): ?int => $record->oldest_due_date
                        ? (int) Carbon::parse($record->oldest_due_date)->startOfDay()->diffInDays(today(), true)
                        : null)
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 30 => 'danger',
                        $state >= 8 => 'warning',
                        default => 'gray',
                    })
                    ->alignCenter(),

                TextColumn::make('oldest_due_date')
                    ->label(__('ui.widgets.overdue_since'))
                    ->date('d/m/Y')
                    ->sortable(),

                // Lo vencido, no el saldo del crédito: es lo que se persigue.
                TextColumn::make('overdue_amount')
                    ->label(__('ui.widgets.overdue_amount'))
                    ->money('usd')
                    ->weight('bold')
                    ->color('danger')
                    ->summarize(Sum::make()->label(__('ui.tables.total'))->money('usd')),

                TextColumn::make('pending_balance')
                    ->label(__('resources.credits.credits.pending_balance'))
                    ->money('usd')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CustomerStatementAction::overdue(),
            ])
            ->defaultSort('overdue_amount', 'desc')
            ->recordActions([
                Action::make('view')
                    ->label(__('ui.widgets.see_customer'))
                    ->icon('heroicon-o-user')
                    ->url(fn (Credit $record): string => CustomerResource::getUrl('view', [
                        'record' => $record->customer_id,
                    ])),

                Action::make('credit')
                    ->label(__('ui.widgets.see_credit'))
                    ->icon('heroicon-o-credit-card')
                    ->color('gray')
                    ->url(fn (Credit $record): string => LoansResource::getUrl('edit', [
                        'record' => $record->getKey(),
                    ])),
            ])
            ->emptyStateHeading(__('ui.widgets.no_overdue'))
            ->emptyStateDescription(__('ui.widgets.no_overdue_help'))
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated([5, 10, 25])
            ->poll('60s');
    }

    private function overdueCredits(): Builder
    {
        return Credit::query()
            ->overdue()
            ->with('customer')
            ->withCount(['installments as overdue_count' => fn (Builder $query) => $query->overdue()])
            ->withMin(['installments as oldest_due_date' => fn (Builder $query) => $query->overdue()], 'due_date')
            ->withSum(['installments as overdue_amount' => fn (Builder $query) => $query->overdue()], 'remaining_balance');
    }
}
