<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las descripciones se editan con RichEditor, así que quedaron guardadas como
 * HTML (`<p>texto</p>`) y algunas como `<p></p>` (una etiqueta vacía, no un
 * nulo). El Excel las exporta e importa como texto plano, así que la base tiene
 * que quedar igual: se quitan las etiquetas y lo que queda vacío pasa a NULL.
 *
 * Solo toca filas que realmente traen etiquetas o entidades: el texto ya limpio
 * no se reescribe (y por eso se puede correr dos veces sin efecto).
 */
return new class () extends Migration {
    public function up(): void
    {
        foreach (['articles', 'categories'] as $table) {
            DB::statement("
                UPDATE {$table}
                   SET description = NULLIF(BTRIM(
                         regexp_replace(
                           regexp_replace(
                             replace(replace(description, '&nbsp;', ' '), '&amp;', '&'),
                           '<[^>]*>', ' ', 'g'),
                         '\\s+', ' ', 'g')
                       ), '')
                 WHERE description IS NOT NULL
                   AND (description LIKE '%<%' OR description LIKE '%&%')
            ");
        }
    }

    /**
     * Las etiquetas HTML no se pueden reconstruir: el texto original queda en el
     * respaldo (storage/app/backups/descripciones-antes-de-limpiar.sql).
     */
    public function down(): void
    {
        //
    }
};
