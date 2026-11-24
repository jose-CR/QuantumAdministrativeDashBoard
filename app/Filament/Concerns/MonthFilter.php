<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;

/**
 * Filtro «Mes» para una tabla con fecha.
 *
 * Las opciones salen de los meses que de verdad tienen datos: no se arma una lista de
 * 24 meses en cero. Sirve para ver un mes solo en vez de todo el histórico mezclado.
 */
final class MonthFilter
{
    /**
     * @param  class-string<Model>  $modelo
     * @param  string  $columna  la columna de fecha de esa tabla
     */
    public static function make(string $modelo, string $columna): Filter
    {
        return Filter::make('mes')
            ->label(__('ui.tables.month'))
            ->form([
                Select::make('mes')
                    ->label(__('ui.tables.month'))
                    ->options(fn (): array => self::options($modelo, $columna))
                    ->placeholder(__('ui.tables.all_months'))
                    ->native(false),
            ])
            ->query(function (Builder $query, array $data) use ($columna): Builder {
                $mes = $data['mes'] ?? null;

                if (blank($mes)) {
                    return $query;
                }

                $inicio = Carbon::createFromFormat('Y-m', (string) $mes)->startOfMonth();

                return $query->whereBetween($columna, [
                    $inicio->toDateString(),
                    $inicio->copy()->endOfMonth()->toDateString(),
                ]);
            })
            ->indicateUsing(function (array $data): array {
                $mes = $data['mes'] ?? null;

                return blank($mes)
                    ? []
                    : [__('ui.tables.month') . ': ' . self::label((string) $mes)];
            });
    }

    /**
     * Meses con datos, del más nuevo al más viejo.
     *
     * @param  class-string<Model>  $modelo
     * @return array<string, string> `2026-10` => «octubre 2026»
     */
    private static function options(string $modelo, string $columna): array
    {
        return $modelo::query()
            ->whereNotNull($columna)
            ->selectRaw("to_char({$columna}, 'YYYY-MM') as mes")
            ->distinct()
            ->orderByDesc('mes')
            ->pluck('mes')
            ->mapWithKeys(fn (string $mes): array => [$mes => self::label($mes)])
            ->all();
    }

    /**
     * «2026-10» → «octubre 2026», en el idioma del panel.
     */
    public static function label(string $mes): string
    {
        try {
            return ucfirst(Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y'));
        } catch (\Throwable) {
            return $mes;
        }
    }
}
