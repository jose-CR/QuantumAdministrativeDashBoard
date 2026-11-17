<?php

namespace App\Filament\Admin\Actions;

use App\Models\PayrollSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;

/**
 * Los parámetros de la planilla (ISSS y AFP) en un modal, para que los ajuste el
 * encargado sin tocar código. Cambiarlos no recalcula boletas ya generadas.
 */
class PayrollSettingsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'payrollSettings';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('ui.payroll.settings'))
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading(__('ui.payroll.settings'))
            ->modalDescription(__('ui.payroll.settings_help'))
            ->modalSubmitActionLabel(__('ui.payroll.settings_save'))
            ->fillForm(fn (): array => PayrollSetting::current()->only([
                'isss_rate',
                'isss_cap',
                'afp_rate',
            ]))
            ->schema([
                TextInput::make('isss_rate')
                    ->label(__('resources.payroll.settings.isss_rate'))
                    ->numeric()
                    ->suffix('%')
                    ->required()
                    ->minValue(0)
                    ->maxValue(100),

                TextInput::make('isss_cap')
                    ->label(__('resources.payroll.settings.isss_cap'))
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->minValue(0)
                    ->helperText(__('resources.payroll.settings.isss_cap_help')),

                TextInput::make('afp_rate')
                    ->label(__('resources.payroll.settings.afp_rate'))
                    ->numeric()
                    ->suffix('%')
                    ->required()
                    ->minValue(0)
                    ->maxValue(100),

                // El ejemplo es lo que hace que sepa cómo llenarlo.
                Text::make(__('resources.payroll.settings.example_title'))
                    ->columnSpanFull()
                    ->color('gray'),
            ])
            ->action(function (array $data): void {
                PayrollSetting::current()->update($data + ['updated_by' => auth()->id()]);

                Notification::make()
                    ->title(__('ui.payroll.settings_saved'))
                    ->success()
                    ->send();
            });
    }
}
