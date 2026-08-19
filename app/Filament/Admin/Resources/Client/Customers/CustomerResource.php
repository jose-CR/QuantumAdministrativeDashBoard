<?php

namespace App\Filament\Admin\Resources\Client\Customers;

use App\Filament\Admin\Resources\Client\Customers\Pages\CreateCustomer;
use App\Filament\Admin\Resources\Client\Customers\Pages\EditCustomer;
use App\Filament\Admin\Resources\Client\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Client\Customers\Schemas\CustomerForm;
use App\Filament\Admin\Resources\Client\Customers\Tables\CustomersTable;
use App\Filament\Admin\Resources\Credits\Clients\RelationManagers\ReferencesRelationManager;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReferencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
