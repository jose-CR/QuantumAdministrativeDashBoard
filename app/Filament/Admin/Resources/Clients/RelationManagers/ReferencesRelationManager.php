<?php

namespace App\Filament\Admin\Resources\Clients\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferencesRelationManager extends RelationManager
{
    protected static string $relationship = 'references';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required()
                    ->label('Nombre completo')
                    ->maxLength(255),
                Select::make('reference_type')
                    ->required()
                    ->options([
                        'family' => 'Familiar',
                        'friend' => 'Amigo'])
                    ->label('Tipo'),
                TextInput::make('phone')
                    ->required()
                    ->suffixIcon(Heroicon::Phone)
                    ->label('Telefono'),
                TextInput::make('address')
                    ->required()
                    ->label('Direccion')
                    ->maxLength(300),
                TextInput::make('relationship')
                    ->label('Parentesco')
                    ->required(),
                TextInput::make('occupation')
                    ->required()
                    ->label('Ocupacion')
                    ->maxLength(255),
            ])
        ;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('reference_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'family' => 'Familiar',
                        'friend' => 'Amigo',
                        default => $state,
                    }),

                TextColumn::make('relationship')
                    ->label('Parentesco'),

                TextColumn::make('phone')
                    ->label('Teléfono'),
                TextColumn::make('occupation')
                    ->label('Ocupación'),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
        ;
    }
}
