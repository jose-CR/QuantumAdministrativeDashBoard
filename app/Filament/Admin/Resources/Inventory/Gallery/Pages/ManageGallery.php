<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery\Pages;

use App\Filament\Admin\Resources\Inventory\Gallery\GalleryImageResource;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGallery extends ManageRecords
{
    protected static string $resource = GalleryImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('ui.images.upload'))
                // Modal de tamaño razonable: a todo el ancho el formulario quedaba
                // flotando en un espacio vacío.
                ->modalWidth('2xl')
                ->createAnother(false)
                // Una foto de galería es un adjunto sin dueño, en su propia colección.
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'collection' => 'gallery',
                    'attachable_type' => null,
                    'attachable_id' => null,
                    'disk' => 'attachments',
                ])
                // El archivo se escribe después de crear el registro: mime y tamaño se
                // completan acá, cuando ya está en el disco.
                ->after(fn ($record) => $record->fillMediaMetadata()),
        ];
    }
}
