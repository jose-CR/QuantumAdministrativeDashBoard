<?php

namespace App\Filament\Admin\Resources\Credits\Clients;

use App\Filament\Admin\Resources\Credits\Clients\Pages\CreateClient;
use App\Filament\Admin\Resources\Credits\Clients\Pages\EditClient;
use App\Filament\Admin\Resources\Credits\Clients\Pages\ListClients;
use App\Filament\Admin\Resources\Credits\Clients\RelationManagers\ReferencesRelationManager;
use App\Filament\Admin\Resources\Credits\Clients\Schemas\ClientForm;
use App\Filament\Admin\Resources\Credits\Clients\Tables\ClientsTable;
use App\Models\Client;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Creditos';

    protected static ?string $recordTitleAttribute = 'Clientes';

    protected static ?string $slug = 'creditos/clients';

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
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
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
