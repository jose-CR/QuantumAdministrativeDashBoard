<?php

namespace App\Support;

use App\Models\Employee;

/**
 * Puestos del taller.
 *
 * Los de siempre viven en `Employee::JOB_TITLES` (con su etiqueta en `lang`); los que
 * el encargado agregue desde el formulario se guardan en `employees.job_title` y
 * pasan a formar parte de la lista. Así el catálogo no obliga a tocar código para dar
 * de alta un puesto nuevo, y los ya usados nunca desaparecen del select.
 */
final class JobTitles
{
    /**
     * Los puestos conocidos: los del catálogo más los que ya se usaron, sin repetir
     * mayúsculas ni espacios.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $usados = Employee::query()
            ->whereNotNull('job_title')
            ->where('job_title', '!=', '')
            ->distinct()
            ->orderBy('job_title')
            ->pluck('job_title');

        return collect(Employee::JOB_TITLES)
            ->merge($usados)
            ->map(fn ($puesto): string => trim(preg_replace('/\s+/u', ' ', (string) $puesto)))
            ->filter(fn (string $puesto): bool => $puesto !== '')
            // Sin tipo de retorno `bool`: el callback devuelve la clave para comparar,
            // y un bool convertiría todos los puestos en `true` (uno solo quedaría).
            ->unique(fn (string $puesto) => mb_strtolower($puesto))
            ->sort()
            ->mapWithKeys(fn (string $puesto): array => [$puesto => self::label($puesto)])
            ->all();
    }

    /**
     * Los que coinciden con lo que se está escribiendo (buscador del select).
     *
     * @return array<string, string>
     */
    public static function search(?string $termino, int $limite = 50): array
    {
        $termino = mb_strtolower(trim((string) $termino));

        return collect(self::all())
            ->filter(fn (string $etiqueta, string $valor): bool => $termino === ''
                || str_contains(mb_strtolower($valor), $termino)
                || str_contains(mb_strtolower($etiqueta), $termino))
            ->take($limite)
            ->all();
    }

    /**
     * Limpia el nombre y devuelve el que ya existe si coincide salvo mayúsculas, para
     * no llenar la lista de variantes del mismo puesto.
     */
    public static function normalize(?string $puesto): ?string
    {
        $puesto = trim(preg_replace('/\s+/u', ' ', (string) $puesto));

        if ($puesto === '') {
            return null;
        }

        foreach (array_keys(self::all()) as $existente) {
            if (mb_strtolower($existente) === mb_strtolower($puesto)) {
                return $existente;
            }
        }

        return $puesto;
    }

    /**
     * Etiqueta visible: la traducción si el puesto es del catálogo, y si no el nombre
     * tal como se escribió (un puesto nuevo no puede salir como su clave de `lang`).
     */
    public static function label(?string $puesto): string
    {
        $puesto = (string) $puesto;

        $clave = 'resources.employees.job_titles.' . $puesto;
        $traducido = __($clave);

        return $traducido === $clave ? ucfirst($puesto) : $traducido;
    }
}
