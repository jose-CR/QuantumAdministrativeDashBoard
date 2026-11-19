<?php

namespace App\Support\Credits;

use App\Models\Customer;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cuánto vence y cuánto se cobró en un rango de fechas, separado por la etiqueta del
 * cliente.
 *
 * Dos números por etiqueta:
 *  - **cuotas del mes**: lo que vence en el rango (cuotas con ese vencimiento, de
 *    créditos no cancelados ni refinanciados).
 *  - **cobrado del mes**: lo efectivamente cobrado en caja (`inflows.amount_paid`) de
 *    clientes que tienen crédito.
 *
 * Un cliente con dos etiquetas suma en las dos filas (por eso el total se calcula
 * aparte, contando a cada cliente una sola vez).
 */
final class CreditsMonthSummary
{
    /**
     * @return array<int, array{tag: string, installments: float, collected: float, difference: float, is_total: bool}>
     */
    public function rows(?string $from = null, ?string $to = null): array
    {
        [$from, $to] = $this->range($from, $to);

        $cuotasPorCliente = $this->installmentsByCustomer($from, $to);
        $cobradoPorCliente = $this->collectedByCustomer($from, $to);

        $etiquetas = $this->tagsByCustomer();

        $filas = [];
        $totalCuotas = 0.0;
        $totalCobrado = 0.0;

        foreach ($this->tags() as $tag) {
            [$cuotas, $cobrado] = $this->sumFor(
                $cuotasPorCliente,
                $cobradoPorCliente,
                fn (int $cliente): bool => in_array($tag->id, $etiquetas[$cliente] ?? [], true),
            );

            $filas[] = $this->row($this->tagLabel($tag->name), $cuotas, $cobrado);
        }

        // Clientes sin ninguna etiqueta: no se pierden en el informe.
        [$cuotasSueltas, $cobradoSuelto] = $this->sumFor(
            $cuotasPorCliente,
            $cobradoPorCliente,
            fn (int $cliente): bool => ($etiquetas[$cliente] ?? []) === [],
        );

        if ($cuotasSueltas > 0 || $cobradoSuelto > 0) {
            $filas[] = $this->row(__('ui.widgets.no_tag'), $cuotasSueltas, $cobradoSuelto);
        }

        // Total: cada cliente una sola vez, aunque tenga varias etiquetas.
        foreach ($cuotasPorCliente as $cliente => $monto) {
            $totalCuotas += (float) $monto;
        }

        foreach ($cobradoPorCliente as $monto) {
            $totalCobrado += (float) $monto;
        }

        $filas[] = $this->row(__('ui.widgets.credits_total'), $totalCuotas, $totalCobrado, true);

        return $filas;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function range(?string $from, ?string $to): array
    {
        $inicio = $from ? Carbon::parse($from) : now()->startOfMonth();
        $fin = $to ? Carbon::parse($to) : now()->endOfMonth();

        return [$inicio->startOfDay()->toDateString(), $fin->endOfDay()->toDateString()];
    }

    /**
     * @return array<int, float> cliente => monto de las cuotas que vencen en el rango
     */
    private function installmentsByCustomer(string $from, string $to): array
    {
        return DB::table('installments as i')
            ->join('credits as c', 'c.id', '=', 'i.credit_id')
            ->whereNotIn('c.status', ['cancelled', 'refinanced'])
            ->whereBetween('i.due_date', [$from, $to])
            ->groupBy('c.customer_id')
            ->select('c.customer_id', DB::raw('sum(i.amount) as total'))
            ->pluck('total', 'c.customer_id')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }

    /**
     * Cobrado en caja de clientes que tienen crédito.
     *
     * @return array<int, float> cliente => monto cobrado en el rango
     */
    private function collectedByCustomer(string $from, string $to): array
    {
        return DB::table('inflows as f')
            ->whereBetween('f.date', [$from, $to])
            ->whereNotNull('f.customer_id')
            ->whereExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('credits as c')
                ->whereColumn('c.customer_id', 'f.customer_id'))
            ->groupBy('f.customer_id')
            ->select('f.customer_id', DB::raw('sum(f.amount_paid) as total'))
            ->pluck('total', 'f.customer_id')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }

    /**
     * @return array<int, array<int, int>> cliente => ids de sus etiquetas
     */
    private function tagsByCustomer(): array
    {
        return DB::table('taggables')
            ->where('taggable_type', (new Customer)->getMorphClass())
            ->get()
            ->groupBy('taggable_id')
            ->map(fn ($filas): array => $filas->pluck('tag_id')->map(fn ($id): int => (int) $id)->all())
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Tag>
     */
    private function tags()
    {
        return Tag::query()->where('type', 'customer')->orderBy('id')->get();
    }

    /**
     * @param  array<int, float>  $cuotas
     * @param  array<int, float>  $cobrado
     * @return array{0: float, 1: float}
     */
    private function sumFor(array $cuotas, array $cobrado, callable $incluir): array
    {
        $clientes = array_unique(array_merge(array_keys($cuotas), array_keys($cobrado)));

        $totalCuotas = 0.0;
        $totalCobrado = 0.0;

        foreach ($clientes as $cliente) {
            if (! $incluir((int) $cliente)) {
                continue;
            }

            $totalCuotas += $cuotas[$cliente] ?? 0.0;
            $totalCobrado += $cobrado[$cliente] ?? 0.0;
        }

        return [round($totalCuotas, 2), round($totalCobrado, 2)];
    }

    /**
     * @return array{tag: string, installments: float, collected: float, difference: float, is_total: bool}
     */
    private function row(string $etiqueta, float $cuotas, float $cobrado, bool $total = false): array
    {
        return [
            'tag' => $etiqueta,
            'installments' => $cuotas,
            'collected' => $cobrado,
            'difference' => round($cuotas - $cobrado, 2),
            'is_total' => $total,
        ];
    }

    /**
     * Las etiquetas viejas quedaron guardadas como JSON (`{"es":"mayorista"}`).
     */
    private function tagLabel(?string $name): string
    {
        $name = (string) $name;

        if (str_starts_with($name, '{')) {
            $traducido = json_decode($name, true);

            if (is_array($traducido)) {
                return (string) (reset($traducido) ?: $name);
            }
        }

        return $name;
    }
}
