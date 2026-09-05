<?php

namespace Database\Factories\Concerns;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait InteractsWithModels
{
    protected function randomItem(
        string $modelClass,
        ?callable $query = null
    ): Model {
        $builder = $modelClass::query();

        if ($query) {
            $query($builder);
        }

        $item = $builder
            ->inRandomOrder()
            ->first();

        if (! $item) {
            throw new RuntimeException(
                "No se encontró ningún registro disponible de {$modelClass}."
            );
        }

        return $item;
    }
}