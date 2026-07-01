<?php

namespace App\Utils\Filament;

use Illuminate\Database\Eloquent\Model;

class FilamentSelect
{
    public static function options(
        string $model,
        array $columns,
        string $key = 'id'
    ): array {

        return $model::query()
            ->get()
            ->mapWithKeys(function (Model $record) use ($columns, $key) {

                $label = collect($columns)
                    ->map(fn ($column) => $record->{$column})
                    ->implode(' - ');

                return [
                    $record->{$key} => $label,
                ];
            })
            ->toArray();
    }
}
