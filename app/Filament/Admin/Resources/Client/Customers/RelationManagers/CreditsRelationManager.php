<?php

namespace App\Filament\Admin\Resources\Client\Customers\RelationManagers;

use App\Filament\Admin\Actions\RefinanceCreditAction;
use App\Models\Credit;
use App\Support\CreditStatus;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Créditos del cliente: el listado los muestra todos (viejos y vigentes) y cada
 * fila abre el detalle en un modal, sin salir de la ficha.
 */
class CreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'credits';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ui.credits.relation_title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y')->sortable(),

                TextColumn::make('financed_amount')->label(__('ui.credits.financed_short'))->money('USD'),

                TextColumn::make('pending_balance')
                    ->label(__('resources.credits.credits.pending_balance'))
                    ->money('USD')
                    ->color(fn (Credit $record): string => (float) $record->pending_balance > 0 ? 'danger' : 'gray'),

                TextColumn::make('installments')->label(__('resources.credits.credits.installments')),

                TextColumn::make('status')
                    ->label(__('resources.credits.credits.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CreditStatus::label($state))
                    ->color(fn (?string $state): string => CreditStatus::color($state)),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                /*
                 * El detalle se arma con los componentes del panel: así respeta el
                 * tema, el modo oscuro y el ancho del modal sin CSS propio.
                 */
                ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Credit $record): string => "Crédito #{$record->id}")
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label(__('resources.credits.credits.status'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => CreditStatus::label($state))
                                    ->color(fn (?string $state): string => CreditStatus::color($state)),

                                TextEntry::make('start_date')
                                    ->label('Inicio')
                                    ->date('d/m/Y'),

                                TextEntry::make('periodicity')
                                    ->label('Periodicidad')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'weekly' => 'Semanal',
                                        'monthly' => 'Mensual',
                                        'yearly' => 'Anual',
                                        default => (string) $state,
                                    }),

                                TextEntry::make('pending_balance')
                                    ->label(__('resources.credits.credits.pending_balance'))
                                    ->money('USD')
                                    ->weight(FontWeight::Bold)
                                    ->color(fn (Credit $record): string => (float) $record->pending_balance > 0 ? 'danger' : 'gray'),

                                TextEntry::make('installment_amount')
                                    ->label(__('resources.credits.credits.installment_amount'))
                                    ->money('USD'),

                                TextEntry::make('interest_rate')
                                    ->label(__('resources.credits.credits.interest_type'))
                                    ->state(fn (Credit $record): string => (($record->interest_type ?? 'auto') === 'fixed'
                                        ? __('ui.credits.fixed_prefix') : __('ui.credits.calculated_prefix'))
                                        . (float) $record->interest_rate . __('ui.credits.rate_suffix')),

                                TextEntry::make('initial_amount')
                                    ->label(__('resources.credits.credits.initial_amount'))
                                    ->money('USD'),

                                TextEntry::make('down_payment')
                                    ->label(__('ui.credits.down_payment_entry'))
                                    ->money('USD'),

                                TextEntry::make('financed_amount')
                                    ->label(__('ui.credits.financed_short'))
                                    ->money('USD'),

                                TextEntry::make('total_interest')
                                    ->label(__('ui.credits.interest_total_short'))
                                    ->money('USD'),

                                TextEntry::make('total_amount')
                                    ->label(__('ui.credits.total_to_pay'))
                                    ->money('USD')
                                    ->color('success'),

                                TextEntry::make('installments')
                                    ->label(__('resources.credits.credits.installments')),

                                TextEntry::make('cuotas')
                                    ->label(__('ui.credits.installments_status'))
                                    ->state(fn (Credit $record): string => sprintf(
                                        __('ui.credits.installments_summary'),
                                        $record->installments()->where('status', 'paid')->count(),
                                        $record->installments()->where('status', 'pending')->count(),
                                        $record->installments()->where('status', 'late')->count(),
                                    ))
                                    ->columnSpan(2),

                                TextEntry::make('refinanciado')
                                    ->label(__('ui.credits.refinanced_from'))
                                    ->state(fn (Credit $record): ?string => $record->refinanced_from_id
                                        ? '#' . $record->refinanced_from_id
                                        : null)
                                    ->placeholder('—'),
                            ]),

                        RepeatableEntry::make('items')
                            ->label(__('ui.credits.items'))
                            ->state(fn (Credit $record) => $record->items()->get())
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label(__('ui.credits.item')),

                                TextEntry::make('total')
                                    ->label(__('ui.credits.price'))
                                    ->money('USD'),
                            ])
                            ->columns(2)
                            ->contained(false)
                            ->columnSpanFull(),
                    ]),

                RefinanceCreditAction::make(),
            ]);
    }
}
