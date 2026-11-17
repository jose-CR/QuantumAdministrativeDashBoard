<?php

namespace App\Filament\Admin\Resources\Administration\Payroll\Schemas;

use App\Models\PayrollPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PayrollPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.payroll.sections.period'))
                    ->description(__('resources.payroll.sections.period_help'))
                    ->schema([
                        Select::make('year')
                            ->label(__('resources.payroll.fields.year'))
                            ->options(self::years())
                            ->default(now()->year)
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::fillDates($get, $set)),

                        Select::make('month')
                            ->label(__('resources.payroll.fields.month'))
                            ->options(self::months())
                            ->default(now()->month)
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::fillDates($get, $set)),

                        Select::make('half')
                            ->label(__('resources.payroll.fields.half'))
                            ->options([
                                'first' => __('resources.payroll.fields.half_first'),
                                'second' => __('resources.payroll.fields.half_second'),
                            ])
                            ->default(now()->day <= 15 ? 'first' : 'second')
                            ->required()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::fillDates($get, $set)),

                        TextInput::make('label')
                            ->label(__('resources.payroll.fields.label'))
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        // Se calculan: 1-15 y 16-último día del mes.
                        DatePicker::make('start_date')
                            ->label(__('resources.payroll.fields.start_date'))
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        DatePicker::make('end_date')
                            ->label(__('resources.payroll.fields.end_date'))
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        DatePicker::make('payment_date')
                            ->label(__('resources.payroll.fields.payment_date'))
                            ->helperText(__('resources.payroll.fields.payment_date_help'))
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('resources.payroll.fields.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Arma las fechas del período apenas se elige año, mes y quincena.
     */
    protected static function fillDates(Get $get, Set $set): void
    {
        $year = (int) $get('year');
        $month = (int) $get('month');
        $half = $get('half');

        if (! $year || ! $month || ! $half) {
            return;
        }

        $fechas = PayrollPeriod::datesFor($year, $month, $half);

        $set('label', $fechas['label']);
        $set('start_date', $fechas['start_date']);
        $set('end_date', $fechas['end_date']);
        $set('payment_date', $fechas['payment_date']);
    }

    /**
     * @return array<int, string>
     */
    protected static function years(): array
    {
        return collect(range(now()->year - 1, now()->year + 1))
            ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function months(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => ucfirst(Carbon::create(null, $month, 1)->locale('es')->translatedFormat('F')),
            ])
            ->all();
    }
}
