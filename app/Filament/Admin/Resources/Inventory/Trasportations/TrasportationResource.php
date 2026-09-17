<?php

namespace App\Filament\Admin\Resources\Inventory\Trasportations;

use App\Filament\Admin\Resources\Inventory\Trasportations\Pages\CreateTrasportation;
use App\Filament\Admin\Resources\Inventory\Trasportations\Pages\EditTrasportation;
use App\Filament\Admin\Resources\Inventory\Trasportations\Pages\ListTrasportations;
use App\Filament\Admin\Resources\Inventory\Trasportations\Schemas\TrasportationForm;
use App\Filament\Admin\Resources\Inventory\Trasportations\Tables\TrasportationsTable;
use App\Models\Transportation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrasportationResource extends Resource
{
    protected static ?string $model = Transportation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $recordTitleAttribute = 'district';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'inventary/transportation';

    public static function form(Schema $schema): Schema
    {
        return TrasportationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrasportationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /*
    * funciones para la traduccion
    */

    public static function getNavigationGroup(): string {
        return __('models.others.inventary');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrasportations::route('/'),
            'create' => CreateTrasportation::route('/create'),
            'edit' => EditTrasportation::route('/{record}/edit'),
        ];
    }
}
