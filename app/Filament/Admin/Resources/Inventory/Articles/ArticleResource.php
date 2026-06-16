<?php

namespace App\Filament\Admin\Resources\Inventory\Articles;

use App\Filament\Admin\Resources\Inventory\Articles\Pages\CreateArticle;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\EditArticle;
use App\Filament\Admin\Resources\Inventory\Articles\Pages\ListArticles;
use App\Filament\Admin\Resources\Inventory\Articles\Schemas\ArticleForm;
use App\Filament\Admin\Resources\Inventory\Articles\Tables\ArticlesTable;
use App\Models\Article;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'brand';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventary';

    protected static ?string $slug = 'inventary/article';

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
