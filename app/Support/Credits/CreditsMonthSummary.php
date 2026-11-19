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
 * Un cliente con dos etiquetas suma en las dos filas; el total lo cuenta una vez.
 */
final class CreditsMonthSummary
{
    /**
     * Lo que vence en el rango, por etiqueta.
     *
     * @return array<int, array{tag: string, amount: float, is_total: bool}>
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
     * @return array<int, array{tag: string, amount: float, is_total: bool}>
     */
    public function collected(?string $from = null, ?string $to = null): array
    {
        [$desde, $hasta] = $this->range($from, $to);

        $montos = DB::table('inflows as f')
            ->whereBetween('f.date', [$desde, $hasta])
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

        return $this->rows($montos);
    }

    /**
     * Arma las filas: una por etiqueta, una para los clientes sin etiqueta y el total.
     *
     * @param  array<int, float>  $montos  cliente => monto
     * @return array<int, array{tag: string, amount: float, is_total: bool}>
     */
    private function rows(array $montos): array
    {
        $etiquetas = $this->tagsByCustomer();
        $filas = [];

        foreach ($this->tags() as $tag) {
            $total = 0.0;

            foreach ($montos as $cliente => $monto) {
                if (in_array($tag->id, $etiquetas[$cliente] ?? [], true)) {
                    $total += $monto;
                }
            }

            $filas[] = $this->row($this->tagLabel($tag->name), $total);
        }

        $sinEtiqueta = 0.0;

        foreach ($montos as $cliente => $monto) {
            if (($etiquetas[$cliente] ?? []) === []) {
                $sinEtiqueta += $monto;
            }
        }

        $filas[] = $this->row(__('ui.widgets.no_tag'), $sinEtiqueta);

        // Total: cada cliente una sola vez, aunque tenga varias etiquetas.
        $filas[] = $this->row(__('ui.widgets.credits_total'), array_sum($montos), true);

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
     * @return array{tag: string, amount: float, is_total: bool}
     */
    private function row(string $etiqueta, float $monto, bool $total = false): array
    {
        return [
            'tag' => $etiqueta,
            'amount' => round($monto, 2),
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
