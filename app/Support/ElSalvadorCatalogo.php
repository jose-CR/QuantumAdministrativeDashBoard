<?php

namespace App\Support;

use App\Utils\ArraySearch;
use Illuminate\Database\Eloquent\Builder;
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
        if (! Storage::exists(self::PATH)) {
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

        if (! is_array($data)) {
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
     * @return array<string, string>
     */
    public static function departments(): array
    {
        $options = [];

        foreach (static::data() as $department) {
            $options[$department['codigo']] = $department['nombre'];
        }

        return $options;
    }

    /**
     * Obtiene los municipios de un departamento.
     *
     * @return array<string, string>
     */
    public static function municipalities(
        ?string $departmentCode = null
    ): array {
        $options = [];

        foreach (static::data() as $department) {
            if (
                $departmentCode !== null &&
                $department['codigo'] !== $departmentCode
            ) {
                continue;
            }

            if (
                ! isset($department['municipios']) ||
                ! is_array($department['municipios'])
            ) {
                throw new RuntimeException(
                    "El departamento '{$department['nombre']}' no contiene municipios válidos."
                );
            }

            foreach ($department['municipios'] as $municipality) {
                $options[$municipality['codigo']] = $municipality['nombre'];
            }
        }

        return $options;
    }

    /**
     * Obtiene los distritos de un municipio.
     *
     * @return array<string, string>
     */
    public static function districts(
        ?string $municipalityCode = null
    ): array {
        $options = [];

        foreach (static::data() as $department) {
            if (
                ! isset($department['municipios']) ||
                ! is_array($department['municipios'])
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
                    ! isset($municipality['distritos']) ||
                    ! is_array($municipality['distritos'])
                ) {
                    throw new RuntimeException(
                        "El municipio '{$municipality['nombre']}' no contiene distritos válidos."
                    );
                }

                foreach ($municipality['distritos'] as $district) {
                    $options[$district['codigo']] = $district['nombre'];
                }
            }
        }

        return $options;
    }

    /**
     * Obtiene el nombre del departamento.
     */
    public static function departmentName(string $department): string
    {
        return self::departments()[$department] ?? $department;
    }

    /**
     * Obtiene el nombre del municipio.
     */
    public static function municipalityName(
        string $department,
        string $municipality
    ): string {
        return self::municipalities($department)[$municipality] ?? $municipality;
    }

    /**
     * Obtiene el nombre del distrito.
     */
    public static function districtName(
        string $municipality,
        string $district
    ): string {
        return self::districts($municipality)[$district] ?? $district;
    }

    /**
     * Aplica la búsqueda de departamentos.
     */
    public static function applyDepartmentSearch(
        Builder $query,
        string $search
    ): Builder {
        $codes = ArraySearch::search(
            self::departments(),
            $search
        );

        return $query->whereIn('department', $codes);
    }

    /**
     * Aplica la búsqueda de municipios.
     */
    public static function applyMunicipalitySearch(
        Builder $query,
        string $search
    ): Builder {
        $query->where(function (Builder $query) use ($search) {
            foreach (static::data() as $department) {
                $municipalityCodes = ArraySearch::search(
                    self::municipalities($department['codigo']),
                    $search
                );

                if ($municipalityCodes === []) {
                    continue;
                }

                $query->orWhere(function (Builder $query) use (
                    $department,
                    $municipalityCodes
                ) {
                    $query
                        ->where('department', $department['codigo'])
                        ->whereIn('municipality', $municipalityCodes);
                });
            }
        });

        return $query;
    }

    /**
     * Aplica la búsqueda de distritos.
     */
    public static function applyDistrictSearch(
        Builder $query,
        string $search
    ): Builder {
        $query->where(function (Builder $query) use ($search) {
            foreach (static::data() as $department) {
                foreach ($department['municipios'] as $municipality) {
                    $districtCodes = ArraySearch::search(
                        self::districts($municipality['codigo']),
                        $search
                    );

                    if ($districtCodes === []) {
                        continue;
                    }

                    $query->orWhere(function (Builder $query) use (
                        $municipality,
                        $districtCodes
                    ) {
                        $query
                            ->where('municipality', $municipality['codigo'])
                            ->whereIn('district', $districtCodes);
                    });
                }
            }
        });

        return $query;
    }

    /**
     * Obtiene una etiqueta completa de ubicación.
     */
    public static function locationLabel(
        string $department,
        string $municipality,
        string $district
    ): string {
        return self::departmentName($department)
            . ' | MUNICIPIO: ' . self::municipalityName(
                $department,
                $municipality
            )
            . ' | DISTRITO: ' . self::districtName(
                $municipality,
                $district
            );
    }
}