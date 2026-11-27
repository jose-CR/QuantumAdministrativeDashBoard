<?php

namespace App\Filament\Admin\Resources\Inventory\Gallery\Tables;

use App\Filament\Concerns\ImageActions;
use App\Models\Category;
use App\Models\GalleryImage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GalleryImagesTable
{
    use ImageActions;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('url')
                    ->label(__('ui.images.image'))
                    ->height(60)
                    ->width(60),

                TextColumn::make('name')
                    ->label(__('ui.images.name'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label(__('ui.images.category'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('is_public')
                    ->label(__('ui.images.status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('ui.images.public')
                        : __('ui.images.private'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label(__('ui.images.date'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('ui.images.category'))
                    ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),

                SelectFilter::make('is_public')
                    ->label(__('ui.images.status'))
                    ->options([
                        1 => __('ui.images.public'),
                        0 => __('ui.images.private'),
                    ]),
            ])
            ->recordActions([
                self::viewImageAction(),
                self::obtainUrlAction(),
                self::downloadImageAction(),
                self::unpublishImageAction(),
                EditAction::make()->label(__('ui.images.edit')),
                DeleteAction::make()->label(__('ui.images.delete')),
            ])
            ->defaultSort('sort')
            ->searchPlaceholder(__('ui.images.search_placeholder'));
    }
}
