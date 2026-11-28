<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Acciones de una foto del inventario: verla, obtener la URL para compartir,
 * descargarla y dejar de publicarla.
 *
 * Las usan la galería y la pestaña de fotos de una unidad, para que las dos se
 * comporten igual. La lógica de publicar vive en el trait PublishableMedia.
 */
trait ImageActions
{
    /**
     * Visor: la foto grande y, debajo, sus datos como chips del panel.
     *
     * Todo con componentes de Filament (nada de HTML suelto) para que herede el
     * estilo del modal; los botones viven en la fila de la tabla.
     */
    protected static function viewImageAction(): Action
    {
        return Action::make('ver')
            ->label(__('ui.images.view'))
            ->icon(Heroicon::OutlinedEye)
            ->modalHeading(fn (Model $record): string => (string) $record->original_name)
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('ui.images.close'))
            ->schema([
                Image::make(
                    fn (Model $record): string => (string) $record->image_url,
                    fn (Model $record): string => (string) ($record->original_name ?? ''),
                )
                    ->imageWidth('100%')
                    ->imageHeight('auto')
                    ->extraAttributes([
                        // La foto manda: ocupa todo el ancho del modal y hasta el 78% del
                        // alto de la pantalla. `contain` evita recortar (una foto vertical
                        // no se deforma ni se corta).
                        'style' => 'display:block;width:100%;height:auto;'
                            . 'max-height:78vh;object-fit:contain;border-radius:.75rem;'
                            . 'background:rgba(127,127,127,.06)',
                    ]),

                // Los datos en una sola fila de chips (un Grid acá apilaba todo).
                Flex::make([
                    Text::make(fn (Model $item): string => $item->formatted_size)->badge(),

                    Text::make(fn (Model $item): string => $item->created_at?->format('d/m/Y H:i') ?? '—')
                        ->badge(),

                    Text::make(fn (Model $item): string => $item->is_public
                        ? __('ui.images.public')
                        : __('ui.images.private'))
                        ->badge()
                        ->color(fn (Model $item): string => $item->is_public ? 'success' : 'gray'),
                ])->extraAttributes(['style' => 'justify-content:center;gap:.5rem;flex-wrap:wrap']),
            ]);
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
            // Publicar saca el archivo afuera: pide permiso propio.
            ->visible(fn (): bool => auth()->user()?->can('Publish:Media') ?? false)
            ->requiresConfirmation()
            ->modalHeading(__('ui.images.get_url'))
            ->modalDescription(__('ui.images.get_url_help'))
            ->action(function (Model $record): void {
                $url = $record->publish();

                // Sin refrescar, la fila sigue pintando el estado anterior.
                $record->refresh();

                Notification::make()
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
                $disco = $record->private_disk ?: ($record->disk ?: 'attachments');

                return Storage::disk($disco)->download($record->path, basename($record->path));
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
                $record->refresh();

                Notification::make()
                    ->success()
                    ->title(__('ui.images.unpublished'))
                    ->send();
            });
    }
}
