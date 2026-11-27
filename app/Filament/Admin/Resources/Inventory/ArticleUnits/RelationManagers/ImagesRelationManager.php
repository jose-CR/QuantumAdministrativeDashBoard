<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\RelationManagers;

use App\Filament\Concerns\ImageActions;
use App\Models\ArticleUnitImage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Fotos de una unidad: la del frente, el tablero, el detalle de la batería.
 *
 * Se suben privadas y se publican con «Obtener URL» cuando hay que compartirlas.
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
            TextInput::make('name')
                ->label(__('ui.images.name'))
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('url')
                    ->label(__('ui.images.image'))
                    ->height(60)
                    ->width(60),

                TextColumn::make('name')
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
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $unidad = $this->getOwnerRecord();
                        $orden = (int) $unidad->images()->max('sort');

                        foreach ($data['files'] as $ruta) {
                            $unidad->images()->create([
                                'name' => pathinfo((string) $ruta, PATHINFO_FILENAME),
                                'path' => $ruta,
                                'sort' => ++$orden,
                            ]);
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
