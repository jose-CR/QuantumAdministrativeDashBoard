<?php

namespace App\Support;

use RuntimeException;

/**
 * Carpeta de trabajo para los archivos que se generan (JSON + Excel de reportes).
 *
 * El primer candidato vive dentro de `storage`, pero si quedó sin permiso de escritura
 * —por ejemplo, porque alguna corrida manual creó la carpeta como root— el panel
 * (www-data) no puede guardar ahí. En ese caso se usa la carpeta temporal del sistema en
 * vez de fallar con «Failed to open stream: Permission denied» en la cara del usuario.
 */
final class TempWorkspace
{
    private const CARPETA = 'app/private/estados-de-cuenta';

    public static function directory(): string
    {
        foreach (self::candidates() as $carpeta) {
            if (! is_dir($carpeta) && ! @mkdir($carpeta, 0775, true) && ! is_dir($carpeta)) {
                continue;
            }

            if (is_writable($carpeta)) {
                return $carpeta;
            }
        }

        throw new RuntimeException(__('ui.statement.no_workspace'));
    }

    /**
     * @return array<int, string>
     */
    private static function candidates(): array
    {
        return [
            storage_path(self::CARPETA),
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'quantum-reportes',
        ];
    }
}
