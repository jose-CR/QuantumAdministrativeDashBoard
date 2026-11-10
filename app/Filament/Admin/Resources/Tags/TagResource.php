<?php

namespace App\Filament\Admin\Resources\Tags;

use App\Filament\Admin\Resources\Tags\Pages\ManageTags;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Tag;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'etiqueta';

    protected static ?string $pluralModelLabel = 'etiquetas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                // El nombre es un JSON por idioma: se compara traducido para no
                // terminar con "mayorista" y "Mayorista" como dos etiquetas.
                ->rule(function (Get $get, ?Tag $record) {
                    return function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                        $buscado = mb_strtolower(trim((string) $value));

                        $existe = Tag::query()
                            ->where('type', $get('type'))
                            ->when($record, fn ($consulta) => $consulta->whereKeyNot($record->getKey()))
                            ->get()
                            ->contains(fn (Tag $tag): bool => mb_strtolower(trim((string) $tag->name)) === $buscado);

                        if ($existe) {
                            $fail('Ya hay una etiqueta con ese nombre.');
                        }
                    };
                }),

            Select::make('type')
                ->label('Tipo')
                ->options([
                    'customer' => 'Clientes',
                ])
                ->default('customer')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'customer' => 'Clientes',
                        default => (string) $state,
                    }),

                // Cuántos registros la usan: una etiqueta en cero solo ensucia el filtro.
                TextColumn::make('usos')
                    ->label('Usos')
                    ->badge()
                    ->state(fn (Tag $record): int => \Illuminate\Support\Facades\DB::table('taggables')
                        ->where('tag_id', $record->id)
                        ->count())
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make()
                ->mutateRecordDataUsing(fn (array $data, Tag $record): array => [
                    ...$data,
                    'name' => $record->name,
                ]),
                DeleteAction::make(),
            ]);
    }

    public static function getNavigationGroup(): string
    {
        return __('models.others.settings');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTags::route('/'),
        ];
    }
}