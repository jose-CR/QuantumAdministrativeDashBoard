<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Schemas;

use App\Models\Attachment;
use App\Models\Outflow;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use App\Models\Article;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OutflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('invoice_date')
                    ->label(__('resources.flow.outflow.date'))
                    ->required(),
                
                TextInput::make('company')
                    ->label(__('resources.flow.outflow.company'))
                    ->required(),

                TextInput::make('invoice_code')
                    ->label(__('resources.flow.outflow.cod_invoice'))
                    ->required(),

                RichEditor::make('description')
                    ->label(__('resources.flow.outflow.description')),

                TextInput::make('quantity')
                    ->label(__('resources.flow.outflow.quantity'))
                    ->numeric()
                    ->required(),

                TextInput::make('amount')
                    ->label(__('resources.flow.outflow.amount'))
                    ->numeric()
                    ->required(),

                TextInput::make('area')
                    ->label(__('resources.flow.outflow.area'))
                    ->required(),

                Section::make('Artículos')
                    ->description('Si la compra es de artículos para el inventario, se suman al stock al guardar.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Select::make('article_id')
                                    ->label('Artículo')
                                    ->searchable()
                                    ->required()
                                    ->options(fn (): array => Article::query()
                                        ->where('tracking', Article::TRACKING_NONE)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Article $articulo): array => [
                                            $articulo->id => "{$articulo->full_name} (stock: {$articulo->stock})",
                                        ])
                                        ->all())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        if ($articulo = Article::find($state)) {
                                            $set('price', (float) $articulo->cash_price);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('price')
                                    ->label('Precio unitario')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Agregar artículo')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                FileUpload::make('attachment_uploads')
                    ->label(__('resources.flow.attachment'))
                    ->multiple()
                    ->disk('attachments')
                    ->storeFileNamesIn('file_names')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->maxFiles(10)
                    ->dehydrated(false)
                    ->afterStateHydrated(
                        function (
                            FileUpload $component,
                            ?Outflow $record
                        ) {
                            if (! $record) {
                                return;
                            }

                            $component->state(
                                $record->attachments
                                    ->pluck('path')
                                    ->toArray()
                            );
                        }
                    ),


            ]);
    }
}
