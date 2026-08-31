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
     * Obtiene los departamentos.
     *
     * Retorna:
     * [
     *     '01' => 'Ahuachapán',
     *     '02' => 'Santa Ana',
     * ]
     */
    public static function departments(): array
    {
        return collect(static::data())
            ->pluck('nombre', 'codigo')
            ->toArray();
    }

    /**
     * Obtiene los municipios de un departamento.
     */
    public static function municipalities(?string $departmentCode = null): array
    {
        $municipalities = [];

        foreach (static::data() as $department) {

            if (
                $departmentCode !== null &&
                $department['codigo'] !== $departmentCode
            ) {
                continue;
            }

            if (
                !isset($department['municipios']) ||
                !is_array($department['municipios'])
            ) {
                throw new RuntimeException(
                    "El departamento '{$department['nombre']}' no contiene municipios válidos."
                );
            }

            foreach ($department['municipios'] as $municipality) {
                $municipalities[$municipality['codigo']] = $municipality['nombre'];
            }
        }

        return $municipalities;
    }

    /**
     * Obtiene los distritos de un municipio.
     */
    public static function districts(?string $municipalityCode = null): array
    {
        $districts = [];

        foreach (static::data() as $department) {

            if (
                !isset($department['municipios']) ||
                !is_array($department['municipios'])
            ) {
                throw new RuntimeException(
                    "El departamento '{$department['nombre']}' no contiene municipios válidos."
                );
            }

            foreach ($department['municipios'] as $municipality) {

                if (
                    $municipalityCode !== null &&
                    $municipality['codigo'] !== $municipalityCode
                ) {
                    continue;
                }

                if (
                    !isset($municipality['distritos']) ||
                    !is_array($municipality['distritos'])
                ) {
                    throw new RuntimeException(
                        "El municipio '{$municipality['nombre']}' no contiene distritos válidos."
                    );
                }

                foreach ($municipality['distritos'] as $district) {
                    $districts[$district['codigo']] = $district['nombre'];
                }
            }
        }

        return $districts;
    }

    public static function departmentName(string $department): string
    {
        return self::departments()[$department] ?? $department;
    }

    public static function municipalityName(
        string $department,
        string $municipality
    ): string {
        return self::municipalities($department)[$municipality] ?? $municipality;
    }

    public static function districtName(
        string $municipality,
        string $district
    ): string {
        return self::districts($municipality)[$district] ?? $district;
    }

    public static function locationLabel(
        string $department,
        string $municipality,
        string $district
    ): string {
        return self::departmentName($department)
            . ' | MUNICIPIO: ' . self::municipalityName($department, $municipality)
            . ' | DISTRITO: ' . self::districtName($municipality, $district);
    }
}