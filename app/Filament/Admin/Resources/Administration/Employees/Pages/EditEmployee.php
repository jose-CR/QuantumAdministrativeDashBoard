<?php

namespace App\Filament\Admin\Resources\Administration\Employees\Pages;

use App\Filament\Admin\Resources\Administration\Employees\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
