<?php

namespace App\Utils\Filament;

use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Búsqueda de los selects contra la base de datos.
 *
 * Un Select con ->options(Model::all()) o con ->preload() trae TODAS las filas al
 * navegador y filtra ahí: con miles de clientes o artículos se pone lento, y lo que
 * no entró en la lista no se encuentra. Estas funciones filtran en Postgres con
 * ILIKE (sin distinguir mayúsculas) por varias columnas a la vez y devuelven solo
 * las primeras coincidencias.
 */
class FilamentSearch
{
    /** Cuántas opciones se devuelven como máximo por búsqueda. */
    public const LIMIT = 50;

    /**
     * Filtra la consulta por varias columnas, o por columnas de una relación.
     *
     * @param  array<int, string>  $columns  columnas de la propia tabla
     * @param  array<string, array<int, string>>  $relations  ['client' => ['full_name']]
     * @param  array<int, string>  $castColumns  enteros que se buscan como texto (id)
     */
    public static function applySearch(
        Builder $query,
        ?string $search,
        array $columns = [],
        array $relations = [],
        array $castColumns = [],
    ): Builder {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $pattern = "%{$search}%";

        return $query->where(function (Builder $query) use ($pattern, $columns, $relations, $castColumns): void {
            foreach ($columns as $column) {
                if (in_array($column, $castColumns, true)) {
                    $query->orWhereRaw("cast(\"{$column}\" as text) ilike ?", [$pattern]);

                    continue;
                }

                $query->orWhere($column, 'ilike', $pattern);
            }

            foreach ($relations as $relation => $relationColumns) {
                $query->orWhereHas($relation, function (Builder $sub) use ($relationColumns, $pattern): void {
                    $sub->where(function (Builder $sub) use ($relationColumns, $pattern): void {
                        foreach ($relationColumns as $column) {
                            $sub->orWhere($column, 'ilike', $pattern);
                        }
                    });
                });
            }
        });
    }

    /**
     * Filtra por columnas de una relación (el registro tiene que tener al menos una
     * coincidencia). Se mantiene por compatibilidad con las tablas que ya la usan.
     */
    public static function relationColumns(
        Builder $query,
        string $relation,
        string $search,
        array $columns,
    ): Builder {
        if (trim($search) === '') {
            return $query;
        }

        return $query->whereHas(
            $relation,
            fn (Builder $sub) => static::applySearch($sub, $search, $columns),
        );
    }

    /**
     * Opciones [valor => etiqueta] para getSearchResultsUsing().
     *
     * @param  array<int, string>|Closure(Model): string  $label
     * @param  array<int, string>  $searchColumns
     * @param  array<string, array<int, string>>  $relations
     */
    public static function options(
        Builder $query,
        ?string $search,
        array $searchColumns = [],
        array|Closure $label = [],
        string $key = 'id',
        int $limit = self::LIMIT,
        array $relations = [],
        array $castColumns = [],
    ): array {
        // Sin etiqueta explícita se usa la misma columna por la que se busca.
        $label = $label === [] ? $searchColumns : $label;

        return static::applySearch($query, $search, $searchColumns, $relations, $castColumns)
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn (Model $record): array => [
                data_get($record, $key) => $label instanceof Closure
                    ? (string) $label($record)
                    : (string) static::label($record, $label),
            ])
            ->all();
    }

    /**
     * Etiqueta de una opción: las columnas pedidas unidas con " - ".
     */
    public static function label(?Model $record, array $labelColumns, string $separator = ' - '): ?string
    {
        if (! $record) {
            return null;
        }

        $label = collect($labelColumns)
            ->map(fn (string $column) => data_get($record, $column))
            ->filter(fn ($value): bool => filled($value))
            ->implode($separator);

        return $label !== '' ? $label : (string) $record->getKey();
    }

    /**
     * Prepara un Select para buscar en la base de datos: filtra en el servidor por
     * varias columnas y arma la etiqueta. Sirve igual para un select con
     * ->relationship() (el estado sigue siendo la llave foránea), y no hay que
     * precargar la tabla entera.
     *
     * @param  array<int, string>|Closure(Model): string  $label
     * @param  array<int, string>  $searchColumns
     * @param  array<string, array<int, string>>  $relations
     */
    public static function searchable(
        Select $select,
        Builder $query,
        array $searchColumns = [],
        array|Closure $label = [],
        string $key = 'id',
        int $limit = self::LIMIT,
        array $relations = [],
        array $castColumns = [],
    ): Select {
        $label = $label === [] ? $searchColumns : $label;

        $etiqueta = $label instanceof Closure
            ? $label
            : fn (Model $record): string => (string) static::label($record, $label);

        return $select
            ->searchable()
            ->getSearchResultsUsing(fn (?string $search): array => static::options(
                clone $query,
                $search,
                $searchColumns,
                $etiqueta,
                $key,
                $limit,
                $relations,
                $castColumns,
            ))
            ->getOptionLabelUsing(function ($value) use ($query, $key, $etiqueta): ?string {
                if (blank($value)) {
                    return null;
                }

                $record = (clone $query)->where($key, $value)->first();

                return $record ? $etiqueta($record) : null;
            });
    }
}
