<?php

namespace App\Utils\Filament;

use Illuminate\Database\Eloquent\Builder;

class FilamentSearch
{
    public static function relationColumns(
        Builder $query,
        string $relation,
        string $search,
        array $columns
    ): Builder
    {

        $query->whereHas(
            $relation,
            function ($query) use (
                $search,
                $columns
            ) {

                $query->where(
                    function ($query) use (
                        $search,
                        $columns
                    ) {

                        foreach ($columns as $column) {

                            $query->orWhere(
                                $column,
                                'ilike',
                                "%{$search}%"
                            );
                        }
                    }
                );
            }
        );

        return $query;
    }
}