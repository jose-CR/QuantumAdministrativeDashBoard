<?php

namespace App\Support\Catalogos;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ActividadesEconomicas
{
    private const PATH = 'catalogos/actividades_economicas.json';

    /**
     * Obtiene y valida el catálogo completo.
     */
    protected static function data(): array
    {
        if (!Storage::exists(self::PATH)) {
            throw new RuntimeException(
                'No se encontró el catálogo de actividades económicas.'
            );
        }

        $json = Storage::get(self::PATH);

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'El catálogo de actividades económicas contiene un JSON inválido: '
                . json_last_error_msg()
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'El catálogo de actividades económicas debe contener un array.'
            );
        }

        if (empty($data)) {
            throw new RuntimeException(
                'El catálogo de actividades económicas está vacío.'
            );
        }

        foreach ($data as $section) {
            static::validateSection($section);
        }

        return $data;
    }

    /**
     * Valida una sección.
     */
    private static function validateSection(mixed $section): void
    {
        if (!is_array($section)) {
            throw new RuntimeException(
                'Una sección del catálogo tiene una estructura inválida.'
            );
        }

        if (!array_key_exists('seccion', $section)) {
            throw new RuntimeException(
                'Una sección del catálogo no contiene la clave "seccion".'
            );
        }

        if (!is_string($section['seccion']) || trim($section['seccion']) === '') {
            throw new RuntimeException(
                'La clave "seccion" debe contener un texto válido.'
            );
        }

        if (!array_key_exists('divisiones', $section)) {
            throw new RuntimeException(
                "La sección '{$section['seccion']}' no contiene la clave \"divisiones\"."
            );
        }

        if (!is_array($section['divisiones'])) {
            throw new RuntimeException(
                "Las divisiones de la sección '{$section['seccion']}' deben ser un array."
            );
        }

        if (empty($section['divisiones'])) {
            throw new RuntimeException(
                "La sección '{$section['seccion']}' no contiene divisiones."
            );
        }

        foreach ($section['divisiones'] as $division) {
            static::validateDivision($division, $section['seccion']);
        }
    }

    /**
     * Valida una división.
     */
    private static function validateDivision(
        mixed $division,
        string $sectionName
    ): void {
        if (!is_array($division)) {
            throw new RuntimeException(
                "Una división de la sección '{$sectionName}' tiene una estructura inválida."
            );
        }

        if (!array_key_exists('division', $division)) {
            throw new RuntimeException(
                "Una división de la sección '{$sectionName}' no contiene la clave \"division\"."
            );
        }

        if (!is_string($division['division']) || trim($division['division']) === '') {
            throw new RuntimeException(
                "La clave \"division\" de la sección '{$sectionName}' debe contener un texto válido."
            );
        }

        if (!array_key_exists('actividades', $division)) {
            throw new RuntimeException(
                "La división '{$division['division']}' no contiene la clave \"actividades\"."
            );
        }

        if (!is_array($division['actividades'])) {
            throw new RuntimeException(
                "Las actividades de la división '{$division['division']}' deben ser un array."
            );
        }

        if (empty($division['actividades'])) {
            throw new RuntimeException(
                "La división '{$division['division']}' no contiene actividades."
            );
        }

        foreach ($division['actividades'] as $activity) {
            static::validateActivity(
                $activity,
                $sectionName,
                $division['division']
            );
        }
    }

    /**
     * Valida una actividad económica.
     */
    private static function validateActivity(
        mixed $activity,
        string $sectionName,
        string $divisionName
    ): void {
        if (!is_array($activity)) {
            throw new RuntimeException(
                "Una actividad de la división '{$divisionName}' tiene una estructura inválida."
            );
        }

        if (!array_key_exists('codigo', $activity)) {
            throw new RuntimeException(
                "Una actividad de la división '{$divisionName}' no contiene la clave \"codigo\"."
            );
        }

        if (!is_string($activity['codigo']) || trim($activity['codigo']) === '') {
            throw new RuntimeException(
                "El código de una actividad de la división '{$divisionName}' debe ser un texto válido."
            );
        }

        if (!array_key_exists('actividad', $activity)) {
            throw new RuntimeException(
                "La actividad con código '{$activity['codigo']}' no contiene la clave \"actividad\"."
            );
        }

        if (!is_string($activity['actividad']) || trim($activity['actividad']) === '') {
            throw new RuntimeException(
                "La actividad con código '{$activity['codigo']}' debe contener un nombre válido."
            );
        }
    }

    /**
     * Obtiene todas las secciones.
     */
    public static function sections(): array
    {
        return static::data();
    }

    /**
     * Obtiene todas las divisiones.
     */
    public static function divisions(): array
    {
        $divisions = [];

        foreach (static::data() as $section) {
            foreach ($section['divisiones'] as $division) {
                $divisions[] = $division;
            }
        }

        return $divisions;
    }

    /**
     * Obtiene todas las actividades económicas.
     */
    public static function activities(): array
    {
        $activities = [];

        foreach (static::data() as $section) {
            foreach ($section['divisiones'] as $division) {
                foreach ($division['actividades'] as $activity) {
                    $activities[] = $activity;
                }
            }
        }

        return $activities;
    }

    /**
     * Busca una actividad por su código.
     */
    public static function find(string $code): ?array
    {
        foreach (static::activities() as $activity) {
            if ($activity['codigo'] === $code) {
                return $activity;
            }
        }

        return null;
    }

    /**
     * Obtiene las opciones para un Select de Filament.
     *
     * [codigo => actividad]
     */
    public static function options(): array
    {
        $options = [];

        foreach (static::activities() as $activity) {
            $options[$activity['codigo']] = $activity['actividad'];
        }

        return $options;
    }
}