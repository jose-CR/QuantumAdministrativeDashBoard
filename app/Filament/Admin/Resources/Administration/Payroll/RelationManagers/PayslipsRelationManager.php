<?php

namespace App\Filament\Admin\Resources\Administration\Payroll\RelationManagers;

use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Las boletas de la quincena.
 *
 * Solo se editan el bono, la multa y el préstamo; el salario, los descuentos de ley
 * y los totales los calcula el modelo al guardar.
 */
class PayslipsRelationManager extends RelationManager
{
    protected static string $relationship = 'payslips';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('models.payroll.singular');
    }

    public function isReadOnly(): bool
    {
        return ($this->getOwnerRecord()->status === PayrollPeriod::STATUS_PAID);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('employee_name')
                    ->label(__('resources.payroll.fields.employee'))
                    ->disabled()
                    ->dehydrated(),

                \Filament\Forms\Components\TextInput::make('salary')
                    ->label(__('resources.payroll.fields.salary'))
                    ->numeric()
                    ->prefix('$')
                    ->required(),

                \Filament\Forms\Components\TextInput::make('bonus')
                    ->label(__('resources.payroll.fields.bonus'))
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->default(0),

                \Filament\Forms\Components\TextInput::make('penalty')
                    ->label(__('resources.payroll.fields.penalty'))
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->default(0),

                \Filament\Forms\Components\TextInput::make('penalty_reason')
                    ->label(__('resources.payroll.fields.penalty_reason'))
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (float) $get('penalty') > 0),

                \Filament\Forms\Components\TextInput::make('loan_deduction')
                    ->label(__('resources.payroll.fields.loan'))
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->default(0),

                \Filament\Forms\Components\Textarea::make('notes')
                    ->label(__('resources.payroll.fields.notes'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee_name')
            ->columns([
                TextColumn::make('employee_name')
                    ->label(__('resources.payroll.fields.employee'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('salary')
                    ->label(__('resources.payroll.fields.salary'))
                    ->money('usd'),

                TextColumn::make('isss')
                    ->label(__('resources.payroll.fields.isss'))
                    ->money('usd'),

                TextColumn::make('afp')
                    ->label(__('resources.payroll.fields.afp'))
                    ->money('usd'),

                TextColumn::make('penalty')
                    ->label(__('resources.payroll.fields.penalty'))
                    ->money('usd'),

                TextColumn::make('loan_deduction')
                    ->label(__('resources.payroll.fields.loan'))
                    ->money('usd'),

                TextColumn::make('bonus')
                    ->label(__('resources.payroll.fields.bonus'))
                    ->money('usd'),

                TextColumn::make('net')
                    ->label(__('resources.payroll.fields.net'))
                    ->money('usd'),

                TextColumn::make('total_to_receive')
                    ->label(__('resources.payroll.fields.total_to_receive'))
                    ->money('usd')
                    ->summarize(Sum::make()->label(__('ui.tables.total'))->money('usd')),
            ])
            ->defaultSort('employee_name')
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->headerActions([]);
    }
}
