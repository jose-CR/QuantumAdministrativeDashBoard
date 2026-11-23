<?php

namespace App\Filament\Admin\Resources\Tags;

use App\Filament\Admin\Resources\Tags\Pages\ManageTags;
use App\Models\Tag;
use App\Support\TagTypes;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

/**
 * Configuración → Etiquetas.
 *
 * Los tipos (clientes, ingresos, salidas) salen de App\Support\TagTypes: el select, el
 * badge, el filtro y el bloqueo al borrar usan la misma lista.
 */
class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    /**
     * Los textos van en métodos: una constante no puede llamar a `__()`.
     */
    public static function getModelLabel(): string
    {
        return __('ui.tags.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ui.tags.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('ui.tags.name'))
                ->required()
                ->maxLength(255)
                // El nombre es un JSON por idioma: se compara traducido para no
                // terminar con "mayorista" y "Mayorista" como dos etiquetas.
                ->rule(function (Get $get, ?Tag $record): Closure {
                    return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $buscado = mb_strtolower(trim((string) $value));

                        $existe = Tag::query()
                            ->where('type', $get('type'))
                            ->when($record, fn ($consulta) => $consulta->whereKeyNot($record->getKey()))
                            ->get()
                            ->contains(fn (Tag $tag): bool => mb_strtolower(trim((string) $tag->name)) === $buscado);

                        if ($existe) {
                            $fail(__('ui.validation.tag_taken'));
                        }
                    };
                }),

            Select::make('type')
                ->label(__('ui.tags.type'))
                ->options(TagTypes::options())
                ->default(TagTypes::CUSTOMER)
                ->required()
                // Cambiar el tipo de una etiqueta en uso la muda de mundo: deja de
                // aparecer en los filtros del anterior. Se fija apenas se usa.
                ->disabled(fn (?Tag $record): bool => $record !== null && $record->usageCount() > 0)
                ->helperText(fn (?Tag $record): ?string => $record !== null && $record->usageCount() > 0
                    ? __('ui.tags.type_locked')
                    : __('ui.tags.type_help')),
        ]);
    }

    public static function table(Table $table): Table
    {
        // Una sola consulta para los usos de todas las filas.
        $usos = Tag::usageCounts();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('ui.tags.name'))
                    ->searchable(),

                TextColumn::make('type')
                    ->label(__('ui.tags.type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => TagTypes::label($state))
                    ->color(fn (?string $state): string => match ($state) {
                        TagTypes::CUSTOMER => 'info',
                        TagTypes::INFLOW => 'success',
                        TagTypes::OUTFLOW => 'warning',
                        default => 'gray',
                    }),

                // Una etiqueta en cero solo ensucia el filtro.
                TextColumn::make('usos')
                    ->label(__('ui.tags.uses'))
                    ->badge()
                    ->state(fn (Tag $record): int => $usos[$record->getKey()] ?? 0)
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->defaultSort('type')
            ->groups([
                Group::make('type')
                    ->label(__('ui.tags.type'))
                    ->getTitleFromRecordUsing(fn (Tag $record): string => TagTypes::label($record->type)),
            ])
            // Mezclar clientes, ingresos y salidas en una lista sola no se lee.
            ->defaultGroup('type')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('ui.tags.type'))
                    ->options(TagTypes::options()),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Tag $record): array => [
                        ...$data,
                        'name' => $record->name,
                    ]),

                // La FK de `taggables` es cascada: borrar la etiqueta la saca de todos
                // los registros que la tienen y no queda rastro de la clasificación.
                DeleteAction::make()
                    ->modalDescription(fn (Tag $record): ?string => $record->usageCount() > 0
                        ? __('ui.tags.delete_blocked', ['count' => $record->usageCount()])
                        : null)
                    ->before(function (DeleteAction $action, Tag $record): void {
                        if ($record->usageCount() > 0) {
                            $action->cancel();
                        }
                    }),
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
