<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Inflow;
use Illuminate\Database\Seeder;

/**
 * Empleados a partir de los vendedores que ya están escritos en las entradas.
 *
 * No inventa gente: los nombres que el taller viene usando viven en
 * `inflows.salesperson`, así que el seeder los convierte en empleados una sola vez.
 * Es idempotente (compara por nombre sin distinguir mayúsculas) y se puede correr
 * después de cambiar de vendedor sin duplicar.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = Inflow::query()
            ->whereNotNull('salesperson')
            ->where('salesperson', '!=', '')
            ->distinct()
            ->orderBy('salesperson')
            ->pluck('salesperson');

        foreach ($nombres as $nombre) {
            $nombre = trim(preg_replace('/\s+/u', ' ', (string) $nombre));

            $existe = Employee::withTrashed()
                ->whereRaw('lower(name) = ?', [mb_strtolower($nombre)])
                ->exists();

            if ($existe) {
                continue;
            }

            Employee::create([
                'code' => $this->nextCode(),
                'name' => $nombre,
                'job_title' => 'vendedor',
                'status' => Employee::STATUS_ACTIVE,
            ]);
        }
    }

    /**
     * EMP-001, EMP-002… sin chocar con los que ya están (incluidos los borrados,
     * porque el código sigue siendo único en la base).
     */
    protected function nextCode(): string
    {
        $usados = Employee::withTrashed()->pluck('code')->filter()->all();

        $numero = count($usados) + 1;

        do {
            $codigo = 'EMP-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
            $numero++;
        } while (in_array($codigo, $usados, true));

        return $codigo;
    }
}
