<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits;

use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\CreateArticleUnit;
use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\EditArticleUnit;
use App\Filament\Admin\Resources\Inventory\ArticleUnits\Pages\ListArticleUnits;
use App\Filament\Admin\Resources\Inventory\ArticleUnits\Schemas\ArticleUnitForm;
use App\Filament\Admin\Resources\Inventory\ArticleUnits\Tables\ArticleUnitsTable;
use App\Models\ArticleUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArticleUnitResource extends Resource
{
    protected static ?string $model = ArticleUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'vin';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'inventary/articleunit';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return ArticleUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleUnitsTable::configure($table);
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

    public static function getModelLabel(): string {
        return __('models.inventary.articleunits.navegation');
    }

    public static function getNavigationGroup(): string {
        return __('models.others.inventary');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticleUnits::route('/'),
            'create' => CreateArticleUnit::route('/create'),
            'edit' => EditArticleUnit::route('/{record}/edit'),
        ];
    }
}
