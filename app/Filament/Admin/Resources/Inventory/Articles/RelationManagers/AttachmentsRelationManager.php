<?php

namespace App\Filament\Admin\Resources\Inventory\Articles\RelationManagers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Fotos y fichas del artículo, desde su propia ficha.
 *
 * Se suben privadas, como cualquier adjunto. Publicar saca una copia a la carpeta
 * pública y devuelve la URL para compartir; despublicar la borra.
 */
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = null;

    public function getTitle(): string
    {
        return __('ui.attachments.title');
    }

    public function form(Schema $schema): Schema
    {
        // La subida va por la acción del encabezado; el formulario no se usa.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label(__('ui.attachments.file'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('mime_type')
                    ->label(__('ui.attachments.type'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('formatted_size')
                    ->label(__('ui.attachments.size')),

                TextColumn::make('is_public')
                    ->label(__('ui.attachments.status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('ui.attachments.public')
                        : __('ui.attachments.private'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label(__('ui.attachments.date'))
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('subir')
                    ->label(__('ui.attachments.upload'))
                    ->icon('heroicon-m-arrow-up-tray')
                    ->schema([
                        FileUpload::make('files')
                            ->label(__('ui.attachments.files'))
                            ->multiple()
                            ->storeFiles(false)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                                'application/pdf',
                            ])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    // El archivo llega como carga temporal: el servicio lo guarda en la
                    // carpeta privada y crea el registro del adjunto.
                    ->action(function (array $data, AttachmentService $service): void {
                        $articulo = $this->getOwnerRecord();

                        foreach ($data['files'] as $archivo) {
                            $service->store($articulo, $archivo, 'galeria', 'attachments');
                        }

                        Notification::make()
                            ->success()
                            ->title(__('ui.attachments.uploaded'))
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label(__('ui.attachments.open'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Attachment $record): string => $record->url)
                    ->openUrlInNewTab(),

                Action::make('publicar')
                    ->label(__('ui.attachments.publish'))
                    ->icon('heroicon-m-globe-alt')
                    ->color('success')
                    ->visible(fn (Attachment $record): bool => $record->canBePublic() && ! $record->isPublished())
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.attachments.publish_confirm'))
                    ->modalDescription(__('ui.attachments.publish_help'))
                    ->action(function (Attachment $record): void {
                        $url = $record->publish();

                        Notification::make()
                            ->success()
                            ->title(__('ui.attachments.published'))
                            // La URL queda en el aviso, lista para copiar.
                            ->body($url ?: __('ui.attachments.publish_failed'))
                            ->persistent()
                            ->send();
                    }),

                Action::make('copiar')
                    ->label(__('ui.attachments.copy_url'))
                    ->icon('heroicon-m-clipboard')
                    ->visible(fn (Attachment $record): bool => $record->isPublished())
                    ->action(function (Attachment $record): void {
                        Notification::make()
                            ->success()
                            ->title(__('ui.attachments.public_url'))
                            ->body($record->public_url)
                            ->persistent()
                            ->send();
                    }),

                Action::make('despublicar')
                    ->label(__('ui.attachments.unpublish'))
                    ->icon('heroicon-m-eye-slash')
                    ->color('gray')
                    ->visible(fn (Attachment $record): bool => $record->isPublished())
                    ->requiresConfirmation()
                    ->modalHeading(__('ui.attachments.unpublish_confirm'))
                    ->modalDescription(__('ui.attachments.unpublish_help'))
                    ->action(function (Attachment $record): void {
                        $record->unpublish();

                        Notification::make()
                            ->success()
                            ->title(__('ui.attachments.unpublished'))
                            ->send();
                    }),

                DeleteAction::make()
                    ->label(__('ui.attachments.delete')),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
