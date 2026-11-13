<?php

namespace App\Support;

use App\Models\Inflow;

/**
 * Vendedores de caja.
 *
 * No hay tabla de vendedores: la lista sale de los nombres que ya se usaron en las
 * entradas. Al escribir uno nuevo, el select lo agrega a esa lista (y queda guardado
 * en la entrada que se está creando).
 */
final class Salespeople
{
    /**
     * Nombres ya usados, ordenados alfabéticamente.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return Inflow::query()
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->distinct()
            ->orderBy('salesperson')
            ->pluck('salesperson')
            ->mapWithKeys(fn (string $nombre): array => [$nombre => $nombre])
            ->all();
    }

    /**
     * Los que coinciden con lo que se está escribiendo (para el buscador del select).
     *
     * @return array<string, string>
     */
    public static function search(?string $termino, int $limite = 50): array
    {
        $termino = mb_strtolower(trim((string) $termino));

        return collect(self::all())
            ->filter(fn (string $nombre): bool => $termino === ''
                || str_contains(mb_strtolower($nombre), $termino))
            ->take($limite)
            ->all();
    }

    /**
     * Limpia el nombre y, si ya existe uno igual salvo mayúsculas o espacios, devuelve
     * el que ya está para no llenar la lista de variantes del mismo vendedor.
     */
    public static function normalize(?string $nombre): ?string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', (string) $nombre));

        if ($nombre === '') {
            return null;
        }

        foreach (array_keys(self::all()) as $existente) {
            if (mb_strtolower($existente) === mb_strtolower($nombre)) {
                return $existente;
            }
        }

        return $nombre;
    }
}
