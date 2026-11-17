<?php

namespace App\Filament\Admin\Resources\Administration\Payroll\Pages;

use App\Filament\Admin\Actions\GeneratePayrollAction;
use App\Filament\Admin\Actions\PayrollSettingsAction;
use App\Filament\Admin\Actions\PayslipsExcelAction;
use App\Filament\Admin\Resources\Administration\Payroll\PayrollPeriodResource;
use App\Filament\Admin\Resources\Administration\Payroll\RelationManagers\PayslipsRelationManager;
use App\Models\PayrollPeriod;
use App\Services\Payroll\GeneratePayrollService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPayrollPeriod extends EditRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    public function getTitle(): string
    {
        return __('resources.payroll.title', ['label' => $this->record->label]);
    }

    public function getSubheading(): ?string
    {
        return __('resources.payroll.subheading', [
            'start' => $this->record->start_date->format('d/m/Y'),
            'end' => $this->record->end_date->format('d/m/Y'),
            'total' => number_format($this->record->total_to_pay, 2),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            GeneratePayrollAction::make(),

            PayslipsExcelAction::make(),

            // Cierra la quincena: sella la fecha de pago en todas las boletas.
            Action::make('markAsPaid')
                ->label(__('resources.payroll.actions.mark_paid'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription(__('resources.payroll.actions.mark_paid_help'))
                ->visible(fn (PayrollPeriod $record): bool => ! $record->isPaid())
                ->action(function (PayrollPeriod $record): void {
                    if ($record->payslips()->doesntExist()) {
                        Notification::make()
                            ->title(__('ui.payroll.no_payslips'))
                            ->danger()
                            ->send();

                        return;
                    }

                    app(GeneratePayrollService::class)->markAsPaid($record);

                    Notification::make()
                        ->title(__('resources.payroll.actions.marked_paid'))
                        ->success()
                        ->send();
                }),

            PayrollSettingsAction::make(),

            DeleteAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            PayslipsRelationManager::class,
        ];
    }
}
