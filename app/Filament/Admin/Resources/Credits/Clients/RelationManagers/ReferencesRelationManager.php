<?php

namespace App\Filament\Admin\Resources\Credits\Clients\RelationManagers;

use App\Filament\Exports\Credits\ClientRelationsExporter;
use App\Filament\Imports\Credits\ReferenceClientImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
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
                    ->label(__('resources.clients.fields.full_name'))
                    ->maxLength(255),
                Select::make('reference_type')
                    ->required()
                    ->label(__('resources.clients.fields.type'))
                    ->options([
                        'family' => __('resources.clients.reference_types.family'),
                        'friend' => __('resources.clients.reference_types.friend')]),
                TextInput::make('phone')
                    ->required()
                    ->label(__('resources.clients.fields.phone'))
                    ->suffixIcon(Heroicon::Phone),
                TextInput::make('address')
                    ->required()
                    ->label(__('resources.clients.fields.address'))
                    ->maxLength(300),
                TextInput::make('relationship')
                    ->label(__('resources.clients.fields.relationship'))
                    ->required(),
                TextInput::make('occupation')
                    ->required()
                    ->label(__('resources.clients.fields.occupation'))
                    ->maxLength(255),
            ])
        ;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('client_id')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label(__('resources.clients.fields.address'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('full_name')
                    ->label(__('resources.clients.fields.full_name'))
                    ->searchable(),
                TextColumn::make('reference_type')
                    ->label(__('resources.clients.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'family' => __('resources.clients.reference_types.family'),
                        'friend' => __('resources.clients.reference_types.friend'),
                        default => $state,
                    }),

                TextColumn::make('relationship')
                    ->label(__('resources.clients.fields.relationship')),

                TextColumn::make('phone')
                    ->label(__('resources.clients.fields.phone')),
                TextColumn::make('occupation')
                    ->label(__('resources.clients.fields.occupation')),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
                ExportAction::make()
                    ->exporter(ClientRelationsExporter::class),
                ImportAction::make()
                    ->importer(ReferenceClientImporter::class),
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
