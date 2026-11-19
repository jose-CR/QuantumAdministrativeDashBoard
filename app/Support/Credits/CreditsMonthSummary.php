<?php

namespace App\Support\Credits;

use App\Models\Customer;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dos informes distintos, no uno con una resta:
 *
 *  - **Vencimientos**: lo que vence en el rango (cuotas de créditos vigentes).
 *  - **Cobranza**: lo cobrado en caja en el rango, de clientes que tienen crédito.
 *
 * Van separados por la etiqueta del cliente porque miden cosas distintas: lo que
 * vence se cuenta por la fecha de la cuota y lo cobrado por la fecha del ingreso.
 * Restarlos daba diferencias negativas que no significaban nada.
 *
 * Un cliente con dos etiquetas suma en las dos filas; el total lo cuenta una vez. Cada
 * fila lleva además el detalle de qué clientes la componen.
 */
final class CreditsMonthSummary
{
    /**
     * Lo que vence en el rango, por etiqueta.
     *
     * @return array<int, array{tag: string, amount: float, is_total: bool, customers: array<int, array{name: string, amount: float}>}>
     */
    public function due(?string $from = null, ?string $to = null): array
    {
        [$desde, $hasta] = $this->range($from, $to);

        $montos = DB::table('installments as i')
            ->join('credits as c', 'c.id', '=', 'i.credit_id')
            ->whereNotIn('c.status', ['cancelled', 'refinanced'])
            ->whereBetween('i.due_date', [$desde, $hasta])
            ->groupBy('c.customer_id')
            ->select('c.customer_id', DB::raw('sum(i.amount) as total'))
            ->pluck('total', 'c.customer_id')
            ->map(fn ($total): float => (float) $total)
            ->all();

        return $this->rows($montos);
    }

    /**
     * Lo cobrado en caja en el rango, por etiqueta, solo de clientes con crédito.
     *
     * El vendedor sale de la entrada de caja (`inflows.salesperson`), que es donde el
     * taller lo anota hoy: el crédito todavía no guarda quién lo vendió.
     *
     * @return array<int, array{tag: string, amount: float, is_total: bool, customers: array<int, array{name: string, amount: float}>}>
     */
    public function collected(?string $from = null, ?string $to = null, ?string $salesperson = null): array
    {
        [$desde, $hasta] = $this->range($from, $to);

        $montos = DB::table('inflows as f')
            ->whereBetween('f.date', [$desde, $hasta])
            ->whereNotNull('f.customer_id')
            ->when(filled($salesperson), fn ($query) => $query->where('f.salesperson', $salesperson))
            ->whereExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('credits as c')
                ->whereColumn('c.customer_id', 'f.customer_id'))
            ->groupBy('f.customer_id')
            ->select('f.customer_id', DB::raw('sum(f.amount_paid) as total'))
            ->pluck('total', 'f.customer_id')
            ->map(fn ($total): float => (float) $total)
            ->all();

        return $this->rows($montos);
    }

    /**
     * Arma las filas: una por etiqueta, una para los clientes sin etiqueta y el total.
     *
     * @param  array<int, float>  $montos  cliente => monto
     * @return array<int, array{tag: string, amount: float, is_total: bool, customers: array<int, array{name: string, amount: float}>}>
     */
    private function rows(array $montos): array
    {
        $etiquetas = $this->tagsByCustomer();
        $nombres = $this->customerNames(array_keys($montos));
        $filas = [];

        foreach ($this->tags() as $tag) {
            $incluye = fn (int $cliente): bool => in_array($tag->id, $etiquetas[$cliente] ?? [], true);

            $filas[] = $this->row(
                $this->tagLabel($tag->name),
                $this->amountFor($montos, $incluye),
                $this->customersFor($montos, $nombres, $incluye),
            );
        }

        // Clientes sin ninguna etiqueta: no se pierden en el informe.
        $sinEtiqueta = fn (int $cliente): bool => ($etiquetas[$cliente] ?? []) === [];

        $filas[] = $this->row(
            __('ui.widgets.no_tag'),
            $this->amountFor($montos, $sinEtiqueta),
            $this->customersFor($montos, $nombres, $sinEtiqueta),
        );

        // Total: cada cliente una sola vez, aunque tenga varias etiquetas.
        $filas[] = $this->row(
            __('ui.widgets.credits_total'),
            array_sum($montos),
            $this->customersFor($montos, $nombres, fn (int $cliente): bool => true),
            true,
        );

        return $filas;
    }

    /**
     * @param  array<int, float>  $montos
     */
    private function amountFor(array $montos, callable $incluir): float
    {
        $total = 0.0;

        foreach ($montos as $cliente => $monto) {
            if ($incluir((int) $cliente)) {
                $total += $monto;
            }
        }

        return round($total, 2);
    }

    /**
     * @param  array<int, float>  $montos
     * @param  array<int, string>  $nombres
     * @return array<int, array{name: string, amount: float}>
     */
    private function customersFor(array $montos, array $nombres, callable $incluir): array
    {
        $clientes = [];

        foreach ($montos as $cliente => $monto) {
            if (! $incluir((int) $cliente) || $monto <= 0) {
                continue;
            }

            $clientes[] = [
                'name' => $nombres[$cliente] ?? '#' . $cliente,
                'amount' => round((float) $monto, 2),
            ];
        }

        usort($clientes, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);

        return $clientes;
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, string>
     */
    private function customerNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Customer::query()
            ->whereIn('id', $ids)
            ->pluck('full_name', 'id')
            ->all();
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
     * @param  array<int, array{name: string, amount: float}>  $clientes
     * @return array{tag: string, amount: float, is_total: bool, customers: array<int, array{name: string, amount: float}>}
     */
    private function row(string $etiqueta, float $monto, array $clientes, bool $total = false): array
    {
        return [
            'tag' => $etiqueta,
            'amount' => round($monto, 2),
            'is_total' => $total,
            'customers' => $clientes,
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
