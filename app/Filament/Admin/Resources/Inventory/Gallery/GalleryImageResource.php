<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery;

use App\Filament\Admin\Resources\Inventory\Gallery\Pages\ManageGallery;
use App\Filament\Admin\Resources\Inventory\Gallery\Schemas\GalleryImageForm;
use App\Filament\Admin\Resources\Inventory\Gallery\Tables\GalleryImagesTable;
use App\Models\Attachment;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Galería: fotos sueltas del taller (un repuesto, un daño, una herramienta).
 *
 * No es un modelo nuevo: son adjuntos sin dueño (`attachable_id` nulo) con colección
 * `gallery` y una categoría del inventario.
 */
class GalleryImageResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.inventory.gallery';
    }

    protected static ?string $model = Attachment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'original_name';

    protected static ?string $slug = 'inventory/gallery';

    protected static string|\UnitEnum|null $navigationGroup = null;

    /** La pantalla trabaja solo con las fotos sueltas. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->gallery();
    }

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
