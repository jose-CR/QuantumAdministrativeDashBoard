<?php

namespace App\Filament\Admin\Resources\Administration\Employees;

use App\Filament\Admin\Resources\Administration\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\Administration\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Administration\Employees\Pages\ListEmployees;
use App\Filament\Admin\Resources\Administration\Employees\Schemas\EmployeeForm;
use App\Filament\Admin\Resources\Administration\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class EmployeeResource extends Resource
{
    use HasTranslatedLabels;

    #[Override]
    protected static function getTranslationKey(): string
    {
        return 'models.employees';
    }

    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getNavigationGroup(): string
    {
        return __('models.others.administration');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
