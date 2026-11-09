<?php

namespace App\Support;

/**
 * Dónde está el python que corre python/scripts/cash_report.py.
 *
 * En la VM de producción hay un venv en python/.venv; en Docker se instala el
 * python del sistema con pandas y openpyxl. PYTHON_BIN manda sobre todo, y si
 * nada existe se cae a `python3` del PATH para no romper la descarga.
 */
class PythonBinary
{
    /** Rutas del venv, en orden de preferencia (Linux y Windows). */
    protected const VENV_CANDIDATES = [
        'python/.venv/bin/python',
        'python/.venv/Scripts/python.exe',
    ];

    public static function path(): string
    {
        $configured = config('quantum.python.bin');

        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        foreach (self::VENV_CANDIDATES as $relative) {
            $candidate = base_path($relative);

            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        if (is_executable('/usr/bin/python3')) {
            return '/usr/bin/python3';
        }

        return 'python3';
    }
}
