<?php

namespace App\Utils;

class ArraySearch
{
    /**
     * Busca coincidencias parciales por nombre
     * y devuelve los códigos encontrados.
     *
     * @param array<string, string> $options
     * @return array<string>
     */
    public static function search(
        array $options,
        string $search
    ): array {
        $search = mb_strtolower(trim($search));

        if ($search === '') {
            return [];
        }

        $codes = [];

        foreach ($options as $code => $name) {
            if (str_contains(mb_strtolower($name), $search)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }
}