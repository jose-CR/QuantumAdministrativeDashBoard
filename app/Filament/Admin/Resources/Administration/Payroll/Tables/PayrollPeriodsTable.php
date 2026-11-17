<?php

namespace App\Filament\Admin\Resources\Administration\Payroll\Tables;

use App\Models\PayrollPeriod;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('resources.payroll.fields.label'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('resources.payroll.fields.start_date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('resources.payroll.fields.end_date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label(__('resources.payroll.fields.payment_date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('payslips_count')
                    ->label(__('resources.payroll.fields.employees'))
                    ->counts('payslips'),

                TextColumn::make('total_to_pay')
                    ->label(__('resources.payroll.fields.total'))
                    ->money('usd')
                    ->state(fn (PayrollPeriod $record): float => $record->total_to_pay),

                TextColumn::make('status')
                    ->label(__('resources.payroll.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('resources.payroll.statuses.' . $state))
                    ->color(fn (string $state): string => $state === PayrollPeriod::STATUS_PAID ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('resources.payroll.fields.status'))
                    ->options([
                        PayrollPeriod::STATUS_OPEN => __('resources.payroll.statuses.open'),
                        PayrollPeriod::STATUS_PAID => __('resources.payroll.statuses.paid'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('start_date', 'desc');
    }
}
