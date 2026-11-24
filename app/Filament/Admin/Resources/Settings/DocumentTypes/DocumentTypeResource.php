<?php

namespace App\Filament\Admin\Resources\Settings\DocumentTypes;

use App\Filament\Admin\Resources\Settings\DocumentTypes\Pages\ManageDocumentTypes;
use App\Filament\Admin\Resources\Settings\DocumentTypes\Schemas\DocumentTypeForm;
use App\Filament\Admin\Resources\Settings\DocumentTypes\Tables\DocumentTypesTable;
use App\Models\DocumentType;
use App\Support\DocumentTypes;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Configuración → Tipos de documento.
 *
 * Es el catálogo del que salen las opciones de Tipo de documento en Caja: agregar uno
 * nuevo acá no pide código ni migración.
 */
class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getModelLabel(): string
    {
        return __('ui.document_types.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.document_types.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTypesTable::configure($table);
    }

    public static function getNavigationGroup(): string
    {
        return __('models.others.settings');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocumentTypes::route('/'),
        ];
    }

    /**
     * Un tipo nuevo tiene que verse de inmediato en el formulario de Caja.
     */
    public static function refreshCatalog(): void
    {
        DocumentTypes::flush();
    }
}
