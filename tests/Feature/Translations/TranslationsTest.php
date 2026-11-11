<?php

namespace Tests\Feature\Translations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guardia de traducciones: toda clave que el código usa tiene que existir en los
 * dos idiomas, y es y en tienen que tener las mismas claves.
 *
 * Sin esto, una clave mal escrita o que se quedó sin su versión en inglés aparece
 * en pantalla como "resources.clients.fields.nrc" y nadie se entera hasta que el
 * taller lo ve.
 */
class TranslationsTest extends TestCase
{
    /**
     * Claves usadas con __() y @lang() en el código, sin las dinámicas.
     *
     * @return array<int, string>
     */
    private function usedKeys(): array
    {
        $rutas = [
            ...File::allFiles(app_path()),
            ...File::allFiles(resource_path('views')),
        ];

        $claves = [];

        foreach ($rutas as $archivo) {
            if (! in_array($archivo->getExtension(), ['php'], true)) {
                continue;
            }

            $contenido = $archivo->getContents();

            preg_match_all(
                "/(?:__|@lang|trans)\(\s*'([a-z0-9_]+(?:\.[a-z0-9_]+)+)'/i",
                $contenido,
                $coincidencias,
            );

            foreach ($coincidencias[1] as $clave) {
                $claves[$clave] = true;
            }
        }

        return array_keys($claves);
    }

    public function test_toda_clave_usada_existe_en_espanol_y_en_ingles(): void
    {
        $faltantes = [];

        foreach ($this->usedKeys() as $clave) {
            foreach (['es', 'en'] as $idioma) {
                if (__($clave, [], $idioma) === $clave) {
                    $faltantes[] = "{$idioma}: {$clave}";
                }
            }
        }

        $this->assertSame([], $faltantes, "Claves sin traducción:\n" . implode("\n", $faltantes));
    }

    public function test_los_dos_idiomas_tienen_las_mismas_claves(): void
    {
        $diferencias = [];

        foreach (File::files(lang_path('es')) as $archivo) {
            $nombre = $archivo->getFilename();

            if (! File::exists(lang_path("en/{$nombre}"))) {
                $diferencias[] = "falta lang/en/{$nombre}";

                continue;
            }

            $es = array_keys(Arr::dot(require lang_path("es/{$nombre}")));
            $en = array_keys(Arr::dot(require lang_path("en/{$nombre}")));

            foreach (array_diff($es, $en) as $clave) {
                $diferencias[] = "{$nombre}: falta en inglés → {$clave}";
            }

            foreach (array_diff($en, $es) as $clave) {
                $diferencias[] = "{$nombre}: falta en español → {$clave}";
            }
        }

        $this->assertSame([], $diferencias, "Idiomas desalineados:\n" . implode("\n", $diferencias));
    }
}
