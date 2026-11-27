<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery;

use App\Filament\Admin\Resources\Inventory\Gallery\Pages\ManageGallery;
use App\Filament\Admin\Resources\Inventory\Gallery\Schemas\GalleryImageForm;
use App\Filament\Admin\Resources\Inventory\Gallery\Tables\GalleryImagesTable;
use App\Models\GalleryImage;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Galería: fotos que no pertenecen a ninguna unidad (un repuesto, un daño, el taller).
 * Se clasifican por categoría y se comparten con la misma URL pública que las fotos de
 * una unidad.
 */
class GalleryImageResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.inventory.gallery';
    }

    protected static ?string $model = GalleryImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'inventory/gallery';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function form(Schema $schema): Schema
    {
        return GalleryImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GalleryImagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGallery::route('/'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('models.others.inventory');
    }
}
