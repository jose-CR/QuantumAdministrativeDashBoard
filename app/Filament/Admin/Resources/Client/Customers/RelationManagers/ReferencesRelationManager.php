<?php

namespace App\Filament\Admin\Resources\Client\Customers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Referencias personales del cliente.
 *
 * La tabla y las reglas existían desde el principio, y la pestaña del formulario
 * estaba comentada: no había forma de cargarlas desde el panel.
 */
class ReferencesRelationManager extends RelationManager
{
    protected static string $relationship = 'references';

    protected static ?string $title = 'Referencias';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label(__('resources.clients.fields.full_name'))
                    ->required()
                    ->maxLength(255),

                Select::make('reference_type')
                    ->label(__('resources.clients.fields.reference_type'))
                    ->options([
                        'family' => __('resources.clients.reference_types.family'),
                        'friend' => __('resources.clients.reference_types.friend'),
                    ])
                    ->required(),

                TextInput::make('relationship')
                    ->label(__('resources.clients.fields.relationship'))
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label(__('resources.clients.fields.phone'))
                    ->tel()
                    ->required()
                    ->maxLength(20),

                TextInput::make('occupation')
                    ->label(__('resources.clients.fields.occupation'))
                    ->maxLength(255),

                TextInput::make('address')
                    ->label(__('resources.clients.fields.address'))
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('resources.clients.fields.full_name'))
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('reference_type')
                    ->label(__('resources.clients.fields.reference_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'family' => __('resources.clients.reference_types.family'),
                        'friend' => __('resources.clients.reference_types.friend'),
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'family' => 'info',
                        'friend' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('relationship')
                    ->label(__('resources.clients.fields.relationship'))
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label(__('resources.clients.fields.phone')),

                TextColumn::make('occupation')
                    ->label(__('resources.clients.fields.occupation'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label(__('resources.clients.fields.address'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar referencia'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
