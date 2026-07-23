<?php

namespace App\Filament\Admin\Resources\Inventory\Categories;

use App\Filament\Admin\Resources\Inventory\Categories\Pages\CreateCategories;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\EditCategories;
use App\Filament\Admin\Resources\Inventory\Categories\Pages\ListCategories;
use App\Filament\Admin\Resources\Inventory\Categories\Schemas\CategoriesForm;
use App\Filament\Admin\Resources\Inventory\Categories\Tables\CategoriesTable;
use App\Models\Category;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CategoriesResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.inventary.category';
    }

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'inventary/categories';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return CategoriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategories::route('/create'),
            'edit' => EditCategories::route('/{record}/edit'),
        ];
    }
}
