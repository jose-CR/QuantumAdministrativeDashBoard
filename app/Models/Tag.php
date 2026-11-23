<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag as SpatieTag;

class Tag extends SpatieTag
{
    /**
     * Cuántos registros usan esta etiqueta.
     *
     * La etiqueta es una clasificación: si la borran, desaparece de todos los
     * clientes/entradas a los que se la pusieron (la FK de `taggables` es cascada) y
     * eso no se recupera. Con esto la pantalla avisa y bloquea.
     */
    public function usageCount(): int
    {
        return (int) DB::table('taggables')->where('tag_id', $this->getKey())->count();
    }

    /**
     * Usos de todas las etiquetas en una sola consulta (la tabla las pinta de a una).
     *
     * @return array<int, int> tag_id => usos
     */
    public static function usageCounts(): array
    {
        return DB::table('taggables')
            ->select('tag_id', DB::raw('count(*) as total'))
            ->groupBy('tag_id')
            ->pluck('total', 'tag_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
