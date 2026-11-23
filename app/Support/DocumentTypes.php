<?php

namespace App\Support;

/**
 * Tipo de documento de una entrada o salida de caja.
 *
 * Es una lista cerrada, no una etiqueta: lo fiscal no se inventa a mano. El código
 * corto es el que se guarda (`cf`, `ccf`…) y el texto visible sale de `lang/`.
 *
 * El CHECK de Postgres espeja esta lista (ver la migración `add_document_type_to_flows`):
 * si se agrega un tipo acá, va también en la migración o la base lo rechaza.
 */
final class DocumentTypes
{
    /** Consumidor Final */
    public const CF = 'cf';

    /** Comprobante de Crédito Fiscal */
    public const CCF = 'ccf';

    /** Factura de sujeto excluido */
    public const EXCLUDED = 'excluded';

    /** Recibo interno del taller (sin DTE) */
    public const RECEIPT = 'receipt';

    /** Factura de compra (proveedor) */
    public const INVOICE = 'invoice';

    /**
     * Los que puede llevar una ENTRADA de caja.
     *
     * @return array<int, string>
     */
    public static function inflow(): array
    {
        return [self::CF, self::CCF, self::EXCLUDED, self::RECEIPT];
    }

    /**
     * Los que puede llevar una SALIDA de caja (compras y gastos).
     *
     * @return array<int, string>
     */
    public static function outflow(): array
    {
        return [self::INVOICE, self::CCF, self::CF, self::RECEIPT];
    }

    /**
     * Todos, para exportar y para la validación del import.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_values(array_unique([...self::inflow(), ...self::outflow()]));
    }

    /**
     * código => etiqueta traducida.
     *
     * @param  array<int, string>|null  $tipos
     * @return array<string, string>
     */
    public static function options(?array $tipos = null): array
    {
        $opciones = [];

        foreach ($tipos ?? self::all() as $tipo) {
            $opciones[$tipo] = self::label($tipo);
        }

        return $opciones;
    }

    /**
     * Nulo se muestra como «Sin especificar»: las entradas viejas no tienen el dato y
     * no se les inventa.
     */
    public static function label(?string $state): string
    {
        if (blank($state)) {
            return __('ui.document_types.unspecified');
        }

        $clave = 'ui.document_types.' . $state;
        $texto = __($clave);

        return $texto === $clave ? $state : $texto;
    }

    public static function color(?string $state): string
    {
        return match ($state) {
            self::CCF => 'success',
            self::CF => 'info',
            self::EXCLUDED => 'warning',
            self::INVOICE => 'gray',
            self::RECEIPT => 'gray',
            default => 'gray',
        };
    }

    /**
     * Lo que acepta el import: el código o el nombre tal como se ve en pantalla.
     *
     * @return array<int, string>
     */
    public static function accepted(?array $tipos = null): array
    {
        return array_merge(array_values($tipos ?? self::all()), array_values(self::options($tipos)));
    }

    /**
     * Normaliza lo que viene de un archivo a un código válido (o nulo).
     *
     * Acepta `CCF`, `ccf`, «Comprobante de Crédito Fiscal» y variantes con espacios.
     */
    public static function cast(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        $buscado = mb_strtolower($valor);

        foreach (self::all() as $tipo) {
            if ($buscado === $tipo) {
                return $tipo;
            }

            if ($buscado === mb_strtolower(self::label($tipo))) {
                return $tipo;
            }
        }

        // Siglas escritas como en el taller: CF, CCF, sujeto excluido.
        return match ($buscado) {
            'cf', 'consumidor final', 'factura' => self::CF,
            'ccf', 'credito fiscal', 'crédito fiscal' => self::CCF,
            'excluido', 'sujeto excluido', 'factura de sujeto excluido' => self::EXCLUDED,
            'recibo', 'recibo interno' => self::RECEIPT,
            'compra', 'factura de compra' => self::INVOICE,
            default => null,
        };
    }
}
