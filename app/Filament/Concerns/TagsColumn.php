<?php

namespace App\Filament\Concerns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * Columna de etiquetas para cualquier tabla.
 *
 * No se usa `SpatieTagsColumn` del plugin: en esta versión de Filament no pinta nada
 * (devolvía un arreglo que la tabla no sabe dibujar) y la columna salía vacía siempre.
 */
final class TagsColumn
{
    /**
     * @param  string|null  $type  tipo de etiqueta; null = todas las del registro
     */
    public static function make(?string $type, string $label): TextColumn
    {
        return TextColumn::make('tags_' . ($type ?? 'all'))
            ->label($label)
            ->badge()
            ->color('gray')
            ->placeholder('—')
            ->state(function (Model $record) use ($type): string {
                /** @var Model $record */
                $tags = $type
                    ? $record->tagsWithType($type)
                    : $record->getRelationValue('tags');

                return $tags->pluck('name')->implode(' · ');
            });
    }
}
