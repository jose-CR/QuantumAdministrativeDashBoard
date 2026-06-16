<?php

namespace App\Filament\Admin\Resources\Inventory\ArticleUnits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('article_id')
                    ->label('Artículo')
                    ->relationship('article', 'id')
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => $record->full_name
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('vin')
                    ->label('VIN')
                    ->required(),

                TextInput::make('engine_number')
                    ->label('Motor')
                    ->required(),

                TextInput::make('plate')
                    ->label('Placa')
                    ->required(),

                TextInput::make('color')
                    ->label('Color')
                    ->required(),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'reserved'  => 'Reservado',
                        'sold'      => 'Vendido',
                    ])
                    ->default('available')
                    ->hiddenOn('create'),
            ]);
    }
}
