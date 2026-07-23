<?php

namespace App\Filament\Admin\Resources\Credits\Clients;

use App\Filament\Admin\Resources\Credits\Clients\Pages\CreateClient;
use App\Filament\Admin\Resources\Credits\Clients\Pages\EditClient;
use App\Filament\Admin\Resources\Credits\Clients\Pages\ListClients;
use App\Filament\Admin\Resources\Credits\Clients\Pages\ViewClient;
use App\Filament\Admin\Resources\Credits\Clients\RelationManagers\ReferencesRelationManager;
use App\Filament\Admin\Resources\Credits\Clients\Schemas\ClientForm;
use App\Filament\Admin\Resources\Credits\Clients\Schemas\ClientInfolist;
use App\Filament\Admin\Resources\Credits\Clients\Tables\ClientsTable;
use App\Models\Client;
use App\Support\Filament\HasTranslatedLabels;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.credits.clients';
    }

    protected static ?string $model = Client::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $slug = 'creditos/clients';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ReferencesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): string {
        return __('models.others.credits');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
