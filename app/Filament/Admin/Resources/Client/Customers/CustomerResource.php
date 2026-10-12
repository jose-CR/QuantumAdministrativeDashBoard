<?php

namespace App\Filament\Admin\Resources\Client\Customers;

use App\Filament\Admin\Resources\Client\Customers\Pages\CreateCustomer;
use App\Filament\Admin\Resources\Client\Customers\Pages\EditCustomer;
use App\Filament\Admin\Resources\Client\Customers\Pages\ListCustomers;
use App\Filament\Admin\Resources\Client\Customers\Pages\ViewCustomer;
use App\Filament\Admin\Resources\Client\Customers\RelationManagers\ReferencesRelationManager;
use App\Filament\Admin\Resources\Client\Customers\Schemas\CustomerForm;
use App\Filament\Admin\Resources\Client\Customers\Schemas\CustomerInfoList;
use App\Filament\Admin\Resources\Client\Customers\Tables\CustomersTable;
use App\Filament\RelationManagers\AttachmentsRelationManager;
use App\Models\Customer;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class CustomerResource extends Resource
{
    use HasTranslatedLabels;

    #[Override]
    protected static function getTranslationKey(): string
    {
        return 'models.clients';
    }

    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfoList::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ReferencesRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
