<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Las acciones que toda imagen del inventario comparte: verla completa, obtener la
 * URL para compartir, descargarla y dejar de publicarla.
 *
 * Lo usan la pestaña de fotos de una unidad y la galería libre, para que se comporten
 * igual. La lógica de publicar vive en el trait PublishableMedia.
 */
trait ImageActions
{
    /**
     * Ver la imagen completa en un modal, escalada a 500 px de ancho sin recortar.
     */
    protected static function viewImageAction(): Action
    {
        return Action::make('ver')
            ->label(__('ui.images.view'))
            ->icon(Heroicon::OutlinedEye)
            ->modalHeading(fn (Model $record): string => (string) $record->name)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('ui.images.close'))
            ->modalContent(fn (Model $record): HtmlString => new HtmlString(
                '<img src="' . e($record->url) . '" alt="' . e((string) $record->name) . '"'
                . ' style="display:block;margin:0 auto;width:100%;max-width:500px;height:auto;border-radius:.5rem">'
            ));
    }

    /**
     * Publicar (si hace falta) y devolver la URL para pegar afuera.
     */
    protected static function obtainUrlAction(): Action
    {
        return Action::make('obtenerUrl')
            ->label(__('ui.images.get_url'))
            ->icon(Heroicon::OutlinedLink)
            ->color('success')
            // Publicar saca el archivo afuera: pide permiso propio, no el de editar artículos.
            ->visible(fn (): bool => auth()->user()?->can('Publish:Media') ?? false)
            ->requiresConfirmation()
            ->modalHeading(__('ui.images.get_url'))
            ->modalDescription(__('ui.images.get_url_help'))
            ->action(function (Model $record): void {
                $url = $record->publish();

                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title($url ? __('ui.images.url_ready') : __('ui.images.url_failed'))
                    ->body($url)
                    ->persistent()
                    ->send();
            });
    }

    /**
     * Descargar el archivo (no abrirlo en una pestaña).
     */
    protected static function downloadImageAction(): Action
    {
        return Action::make('descargar')
            ->label(__('ui.images.download'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->action(function (Model $record) {
                $disco = $record->private_disk ?: 'attachments';

                return \Illuminate\Support\Facades\Storage::disk($disco)
                    ->download($record->path, basename($record->path));
            });
    }

    protected static function unpublishImageAction(): Action
    {
        return Action::make('despublicar')
            ->label(__('ui.images.unpublish'))
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('gray')
            ->visible(fn (Model $record): bool => $record->isPublished()
                && (auth()->user()?->can('Publish:Media') ?? false))
            ->requiresConfirmation()
            ->modalHeading(__('ui.images.unpublish'))
            ->modalDescription(__('ui.images.unpublish_help'))
            ->action(function (Model $record): void {
                $record->unpublish();

                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title(__('ui.images.unpublished'))
                    ->send();
            });
    }
}
