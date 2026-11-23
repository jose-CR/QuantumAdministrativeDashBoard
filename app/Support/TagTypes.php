<?php

namespace App\Support;

/**
 * Los tipos de etiqueta del sistema.
 *
 * El tipo separa los mundos: una etiqueta de cliente no se mezcla con las de una
 * entrada o una salida de caja. Vive en un solo lugar para que agregar uno nuevo sea
 * una línea acá y no un `match` copiado en cada pantalla.
 */
final class TagTypes
{
    public const CUSTOMER = 'customer';

    public const INFLOW = 'inflow';

    public const OUTFLOW = 'outflow';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::CUSTOMER, self::INFLOW, self::OUTFLOW];
    }

    /**
     * valor => etiqueta traducida (lo que ve el usuario en el select y el filtro).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $opciones = [];

        foreach (self::all() as $tipo) {
            $opciones[$tipo] = self::label($tipo);
        }

        return $opciones;
    }

    /**
     * Un tipo desconocido (o nulo) se muestra tal cual: nunca se pinta la clave cruda
     * de una traducción que no existe.
     */
    public static function label(?string $state): string
    {
        if (blank($state)) {
            return __('ui.tags.types.unknown');
        }

        $clave = 'ui.tags.types.' . $state;
        $texto = __($clave);

        return $texto === $clave ? $state : $texto;
    }

    /**
     * ¿Existe ese tipo? Sirve para validar sin repetir la lista.
     */
    public static function exists(?string $state): bool
    {
        return in_array($state, self::all(), true);
    }
}
