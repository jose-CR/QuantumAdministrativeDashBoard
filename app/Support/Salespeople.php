<?php

namespace App\Support;

use App\Models\Employee;

/**
 * Vendedores de caja.
 *
 * La lista son los EMPLEADOS de la plantilla, no nombres escritos a mano: así el
 * vendedor de una entrada es una persona con código.
 *
 * Manda el código de vendedor (`VEN-0001`). Mientras el taller no haya marcado
 * ninguno, se usan los empleados cuyo puesto es «vendedor» para no dejar el select
 * vacío (es un campo obligatorio).
 */
final class Salespeople
{
    /**
     * Vendedores, ordenados alfabéticamente.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::query()
            ->orderBy('name')
            ->pluck('name')
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
     * El empleado detrás de un nombre, si existe.
     */
    public static function employee(?string $nombre): ?Employee
    {
        if (blank($nombre)) {
            return null;
        }

        return self::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $nombre))])
            ->first();
    }

    /**
     * Crea el empleado vendedor con el siguiente código libre (VEN-0001, VEN-0002…).
     *
     * Es lo que hace el botón «Nuevo vendedor» del select: si solo se guardara el
     * nombre, no volvería a aparecer en la lista.
     */
    public static function create(string $nombre): string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre));

        Employee::query()->create([
            'code' => self::nextCode(),
            'name' => $nombre,
            'job_title' => 'vendedor',
            'status' => Employee::STATUS_ACTIVE,
        ]);

        return $nombre;
    }

    /**
     * Siguiente código de la serie VEN-0000.
     */
    public static function nextCode(): string
    {
        $ultimo = Employee::query()
            ->where('code', 'like', Employee::SALESPERSON_CODE_PREFIX . '%')
            ->pluck('code')
            ->map(fn (string $codigo): int => (int) preg_replace('/\D/', '', $codigo))
            ->max() ?? 0;

        return Employee::SALESPERSON_CODE_PREFIX . str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * ¿El taller ya marcó a alguien con código de vendedor?
     */
    public static function usesCodes(): bool
    {
        return Employee::query()
            ->where('code', 'like', Employee::SALESPERSON_CODE_PREFIX . '%')
            ->exists();
    }

    /**
     * Empleados con código de vendedor; si todavía no hay ninguno, los del puesto
     * «vendedor».
     *
     * @return \Illuminate\Database\Eloquent\Builder<Employee>
     */
    private static function query()
    {
        return Employee::query()->when(
            self::usesCodes(),
            fn ($query) => $query->where('code', 'like', Employee::SALESPERSON_CODE_PREFIX . '%'),
            fn ($query) => $query->where('job_title', 'vendedor'),
        );
    }
}
