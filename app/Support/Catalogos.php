<?php

namespace App\Support\Catalogos;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ElSalvadorCatalogo
{
    private const PATH = 'catalogos/el_salvador_catalogo.json';

    protected static function data(): array
    {
        if (!Storage::exists(self::PATH)) {
            throw new RuntimeException(
                'No se encontró el catálogo de El Salvador.'
            );
        }

        $json = Storage::get(self::PATH);

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'El catálogo de El Salvador contiene un JSON inválido: '
                . json_last_error_msg()
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'El catálogo de El Salvador debe contener un objeto JSON válido.'
            );
        }

        return $data;
    }

    private static function section(string $section): array
    {
        $data = static::data();

        if (!array_key_exists($section, $data)) {
            throw new RuntimeException(
                "El catálogo no contiene la estructura '{$section}'."
            );
        }

        if (!is_array($data[$section])) {
            throw new RuntimeException(
                "La estructura '{$section}' debe ser un array."
            );
        }

        if (empty($data[$section])) {
            throw new RuntimeException(
                "El catálogo no contiene ningún elemento en '{$section}'."
            );
        }

        return $data[$section];
    }

    public static function departments(): array
    {
        return static::section('departamentos');
    }

    public static function municipalities(): array
    {
        return static::section('municipios');
    }

    public static function districts(): array
    {
        return static::section('distritos');
    }
}

