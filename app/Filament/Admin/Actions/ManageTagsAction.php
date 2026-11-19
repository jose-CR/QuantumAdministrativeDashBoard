<?php

namespace App\Filament\Admin\Actions;

use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Illuminate\Database\Eloquent\Model;

/**
 * Administrar las etiquetas de un registro desde su tabla.
 *
 * Usa `TagsInput` de Filament y no `SpatieTagsInput`: el del plugin guarda por
 * relación (`saveRelationshipsUsing`) y dentro de una acción no devuelve su valor al
 * formulario, así que guardaba siempre vacío.
 */
final class ManageTagsAction
{
    public static function make(string $type): Action
    {
        return Action::make('etiquetas')
            ->label(__('resources.clients.fields.tags.tags'))
            ->icon('heroicon-o-tag')
            ->color('gray')
            ->modalHeading(__('ui.tags.manage'))
            ->modalDescription(__('ui.tags.manage_help'))
            ->modalSubmitActionLabel(__('ui.tags.save'))
            ->fillForm(fn (Model $record): array => [
                'tags' => $record->tagsWithType($type)->pluck('name')->all(),
            ])
            ->schema([
                TagsInput::make('tags')
                    ->hiddenLabel()
                    ->placeholder(__('ui.tags.placeholder'))
                    ->suggestions(fn (): array => Tag::getWithType($type)->pluck('name')->all())
                    ->splitKeys(['Tab', ',']),
            ])
            ->action(function (Model $record, array $data) use ($type): void {
                // Reemplaza solo las de este tipo: las demás etiquetas no se tocan.
                $record->syncTagsWithType(array_values($data['tags'] ?? []), $type);
            })
            ->successNotificationTitle(__('ui.tags.saved'));
    }
}
