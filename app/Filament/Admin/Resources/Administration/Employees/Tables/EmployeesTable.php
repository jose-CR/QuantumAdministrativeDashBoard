<?php

namespace App\Filament\Admin\Resources\Administration\Employees\Tables;

use App\Models\Employee;
use App\Utils\Filament\FilamentSearch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('resources.employees.fields.code'))
                    ->searchable()
                    ->sortable(),

                // El buscador de la tabla mira nombre, documento y teléfono: es como
                // el taller busca a una persona.
                TextColumn::make('name')
                    ->label(__('resources.employees.fields.name'))
                    ->sortable()
                    ->searchable(query: fn (Builder $query, string $search): Builder => FilamentSearch::applySearch(
                        $query,
                        $search,
                        ['name', 'document_number', 'phone'],
                    )),

                TextColumn::make('document_number')
                    ->label(__('resources.employees.fields.document_number'))
                    ->formatStateUsing(fn (?string $state, Employee $record): string => trim(
                        (string) $record->document_type . ' ' . (string) $state
                    ))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('job_title')
                    ->label(__('resources.employees.fields.job_title'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('resources.employees.job_titles.' . $state)),

                TextColumn::make('phone')
                    ->label(__('resources.employees.fields.phone'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label(__('resources.employees.fields.user'))
                    ->placeholder(__('resources.employees.fields.no_user'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('salary_amount')
                    ->label(__('resources.employees.fields.salary_amount'))
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('hire_date')
                    ->label(__('resources.employees.fields.hire_date'))
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('resources.employees.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('resources.employees.statuses.' . $state))
                    ->color(fn (string $state): string => $state === Employee::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('resources.employees.fields.status'))
                    ->options([
                        Employee::STATUS_ACTIVE => __('resources.employees.statuses.active'),
                        Employee::STATUS_INACTIVE => __('resources.employees.statuses.inactive'),
                    ]),

                SelectFilter::make('job_title')
                    ->label(__('resources.employees.fields.job_title'))
                    ->options(collect(Employee::JOB_TITLES)
                        ->mapWithKeys(fn (string $title): array => [
                            $title => __('resources.employees.job_titles.' . $title),
                        ])
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),

                // Dar de baja no borra: el empleado queda para el historial de ventas.
                Action::make('deactivate')
                    ->label(__('resources.employees.actions.deactivate'))
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Employee $record): bool => $record->isActive())
                    ->action(fn (Employee $record) => $record->update([
                        'status' => Employee::STATUS_INACTIVE,
                        'termination_date' => $record->termination_date ?? today(),
                    ])),

                Action::make('reactivate')
                    ->label(__('resources.employees.actions.reactivate'))
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Employee $record): bool => ! $record->isActive())
                    ->action(fn (Employee $record) => $record->update([
                        'status' => Employee::STATUS_ACTIVE,
                        'termination_date' => null,
                    ])),

                // Un empleado con ventas no se borra: se da de baja.
                DeleteAction::make()
                    ->visible(fn (Employee $record): bool => ! $record->hasHistory()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        // La baja masiva tampoco se lleva a quien tiene historial.
                        ->before(function (DeleteBulkAction $action): void {
                            $conHistorial = $action->getSelectedRecords()
                                ->filter(fn (Employee $record): bool => $record->hasHistory());

                            if ($conHistorial->isNotEmpty()) {
                                Notification::make()
                                    ->title(__('resources.employees.actions.cannot_delete'))
                                    ->body($conHistorial->pluck('name')->implode(', '))
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
    }
}
