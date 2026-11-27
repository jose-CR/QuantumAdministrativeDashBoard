<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\RelationManagers;

use App\Filament\Concerns\ImageActions;
use App\Services\AttachmentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fotos de una unidad: son adjuntos del inventario (tabla `attachments`, dueño = la
 * unidad, colección `images`), no una tabla aparte.
 */
class ImagesRelationManager extends RelationManager
{
    use ImageActions;

    protected static string $relationship = 'images';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ui.images.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label(__('ui.images.image'))
                ->disk('attachments')
                ->directory('units')
                ->image()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                ImageColumn::make('image_url')
                    ->label(__('ui.images.image'))
                    ->height(60)
                    ->width(60),

                TextColumn::make('original_name')
                    ->label(__('ui.images.name'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('is_public')
                    ->label(__('ui.images.status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('ui.images.public')
                        : __('ui.images.private'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('sort')
                    ->label(__('ui.images.sort'))
                    ->numeric()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('subir')
                    ->label(__('ui.images.upload'))
                    ->icon('heroicon-m-arrow-up-tray')
                    ->schema([
                        FileUpload::make('files')
                            ->label(__('ui.images.files'))
                            ->multiple()
                            ->disk('attachments')
                            ->directory('units')
                            ->image()
                            ->maxSize(10240)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    // El servicio guarda el archivo privado y crea el adjunto con
                    // dueño = unidad y colección `images`.
                    ->action(function (array $data, AttachmentService $service): void {
                        $unidad = $this->getOwnerRecord();
                        $orden = (int) $unidad->images()->max('sort');

                        foreach ($data['files'] as $archivo) {
                            $adjunto = $service->store($unidad, $archivo, 'images', 'attachments');
                            $adjunto->forceFill(['sort' => ++$orden])->save();
                        }

                        Notification::make()
                            ->success()
                            ->title(__('ui.images.uploaded'))
                            ->send();
                    }),
            ])
            ->recordActions([
                self::viewImageAction(),
                self::obtainUrlAction(),
                self::downloadImageAction(),
                self::unpublishImageAction(),
                DeleteAction::make()->label(__('ui.images.delete')),
            ])
            ->defaultSort('sort');
    }
}
