<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ElSalvadorCatalogo
{
    private const PATH = 'catalogos/el_salvador_catalogo.json';

    /**
     * Obtiene y valida el catálogo completo.
     */
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
                'El catálogo de El Salvador debe contener un array.'
            );
        }

        if (empty($data)) {
            throw new RuntimeException(
                'El catálogo de El Salvador está vacío.'
            );
        }

        return $data;
    }

    /**
     * Obtiene todos los departamentos.
     */
    public static function departments(): array
    {
        return static::data();
    }

    /**
     * Obtiene todos los municipios.
     */
    public static function municipalities(): array
    {
        $municipalities = [];

        foreach (static::data() as $department) {

            if (!isset($department['municipios']) || !is_array($department['municipios'])) {
                throw new RuntimeException(
                    "El departamento '{$department['nombre']}' no contiene municipios válidos."
                );
            }

            foreach ($department['municipios'] as $municipality) {
                $municipalities[] = $municipality;
            }
        }

        return $municipalities;
    }

    /**
     * Obtiene todos los distritos.
     */
    public static function districts(): array
    {
        $districts = [];

        foreach (static::data() as $department) {

            if (!isset($department['municipios']) || !is_array($department['municipios'])) {
                throw new RuntimeException(
                    "El departamento '{$department['nombre']}' no contiene municipios válidos."
                );
            }

            foreach ($department['municipios'] as $municipality) {

                if (!isset($municipality['distritos']) || !is_array($municipality['distritos'])) {
                    throw new RuntimeException(
                        "El municipio '{$municipality['nombre']}' no contiene distritos válidos."
                    );
                }

                foreach ($municipality['distritos'] as $district) {
                    $districts[] = $district;
                }
            }
        }

        return $districts;
    }
}