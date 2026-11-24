<?php

namespace App\Support;

use App\Models\DocumentType;
use Illuminate\Support\Collection;

/**
 * Tipo de documento de una entrada o salida de caja (CF, CCF, sujeto excluido…).
 *
 * La lista sale del catálogo `document_types`, no del código: el taller agrega uno
 * nuevo desde Configuración → Tipos de documento y aparece solo en el formulario, el
 * filtro, el export y el import.
 *
 * Es una lista cerrada y fiscal, no una etiqueta: si fuera etiqueta, «CF», «C.F.» y
 * «ccf» quedarían como tres tipos y ningún reporte los sumaría.
 */
final class DocumentTypes
{
    /** Caché por petición: el formulario y la tabla piden la lista varias veces. */
    private static ?Collection $cache = null;

    /** Todos, activos e inactivos: para mostrar (y reimportar) lo ya guardado. */
    private static ?Collection $todos = null;

    /**
     * Todos los tipos ACTIVOS, en el orden del catálogo. Es lo que se ofrece.
     *
     * @return Collection<int, DocumentType>
     */
    public static function catalog(): Collection
    {
        return self::$cache ??= DocumentType::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    /**
     * El catálogo completo, con los inactivos.
     *
     * Un tipo que se desactivó sigue guardado en las entradas viejas: si se busca solo
     * entre los activos, la pantalla muestra el código crudo (`excluded`) en vez de su
     * nombre.
     *
     * @return Collection<int, DocumentType>
     */
    public static function every(): Collection
    {
        return self::$todos ??= DocumentType::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    /**
     * Los que puede llevar una ENTRADA de caja.
     *
     * @return Collection<int, DocumentType>
     */
    public static function forInflow(): Collection
    {
        return self::catalog()
            ->filter(fn (DocumentType $tipo): bool => in_array($tipo->scope, [
                DocumentType::SCOPE_INFLOW,
                DocumentType::SCOPE_BOTH,
            ], true))
            ->values();
    }

    /**
     * Los que puede llevar una SALIDA de caja.
     *
     * @return Collection<int, DocumentType>
     */
    public static function forOutflow(): Collection
    {
        return self::catalog()
            ->filter(fn (DocumentType $tipo): bool => in_array($tipo->scope, [
                DocumentType::SCOPE_OUTFLOW,
                DocumentType::SCOPE_BOTH,
            ], true))
            ->values();
    }

    /**
     * Todos los códigos activos, para validar el import.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::catalog()->pluck('code')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function inflow(): array
    {
        return self::forInflow()->pluck('code')->all();
    }

    /**
     * @return array<int, string>
     */
    public static function outflow(): array
    {
        return self::forOutflow()->pluck('code')->all();
    }

    /**
     * código => nombre, para `Select::options()`.
     *
     * @param  array<int, string>|null  $tipos  limita a esos códigos
     * @return array<string, string>
     */
    public static function options(?array $tipos = null): array
    {
        return self::catalog()
            ->when($tipos !== null, fn (Collection $tipos2) => $tipos2->whereIn('code', $tipos ?? []))
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * Nulo se muestra como «Sin especificar»: las filas viejas no tienen el dato y no
     * se les inventa.
     */
    public static function label(?string $state): string
    {
        if (blank($state)) {
            return __('ui.document_types.unspecified');
        }

        $nombre = self::every()->firstWhere('code', $state)?->name;

        return $nombre ?? (string) $state;
    }

    public static function color(?string $state): string
    {
        return match ($state) {
            'ccf' => 'success',
            'cf' => 'info',
            'excluded' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Lo que acepta el import: los códigos o los nombres tal como se ven.
     *
     * @return array<int, string>
     */
    public static function accepted(?array $tipos = null): array
    {
        return array_merge(
            array_values($tipos ?? self::all()),
            array_values(self::options($tipos)),
        );
    }

    /**
     * Normaliza lo que viene de un archivo a un código del catálogo (o nulo si no
     * existe: un tipo inventado no entra).
     */
    public static function cast(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        $buscado = self::normalizar($valor);

        foreach (self::every() as $tipo) {
            if ($buscado === self::normalizar($tipo->code) || $buscado === self::normalizar($tipo->name)) {
                return $tipo->code;
            }
        }

        // Nombres parciales («sujeto excluido» por «Factura de sujeto excluido»).
        foreach (self::every() as $tipo) {
            $nombre = self::normalizar($tipo->name);

            if ($nombre !== '' && (str_contains($nombre, $buscado) || str_contains($buscado, $nombre))) {
                return $tipo->code;
            }
        }

        return null;
    }

    /**
     * Limpia el catálogo en memoria (después de crear o editar un tipo).
     */
    public static function flush(): void
    {
        self::$cache = null;
        self::$todos = null;
    }

    private static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));

        // Sin tildes: «Crédito» y «Credito» son el mismo documento.
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto,
        );
    }
}
