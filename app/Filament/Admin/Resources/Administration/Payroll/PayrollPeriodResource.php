<?php

namespace App\Filament\Admin\Resources\Administration\Payroll;

use App\Filament\Admin\Resources\Administration\Payroll\Pages\CreatePayrollPeriod;
use App\Filament\Admin\Resources\Administration\Payroll\Pages\EditPayrollPeriod;
use App\Filament\Admin\Resources\Administration\Payroll\Pages\ListPayrollPeriods;
use App\Filament\Admin\Resources\Administration\Payroll\Schemas\PayrollPeriodForm;
use App\Filament\Admin\Resources\Administration\Payroll\Tables\PayrollPeriodsTable;
use App\Models\PayrollPeriod;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class PayrollPeriodResource extends Resource
{
    use HasTranslatedLabels;

    #[Override]
    protected static function getTranslationKey(): string
    {
        return 'models.payroll';
    }

    protected static ?string $model = PayrollPeriod::class;

    protected static ?string $slug = 'administration/payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return PayrollPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollPeriodsTable::configure($table);
    }

    public static function getNavigationGroup(): string
    {
        return __('models.others.administration');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollPeriods::route('/'),
            'create' => CreatePayrollPeriod::route('/create'),
            'edit' => EditPayrollPeriod::route('/{record}/edit'),
        ];
    }
}
