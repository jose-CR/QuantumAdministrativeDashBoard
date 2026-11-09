<?php

namespace App\Support;

/**
 * El campo de descripción se edita con un editor enriquecido, así que en la base
 * queda HTML (`<p>texto</p>`). Para el Excel se necesita texto plano: si se
 * exporta el HTML tal cual, al reimportar entra como texto y se vuelve a
 * envolver (`<p><p>texto</p></p>`), y un `<p></p>` vacío no es un nulo.
 */
class Html
{
    /**
     * HTML → texto plano. Devuelve null cuando no queda nada (el caso de
     * `<p></p>`, `<p><br></p>` o `&nbsp;`), para que en el Excel salga en blanco.
     */
    public static function plain(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Los saltos de bloque se conservan como salto de línea antes de quitar
        // las etiquetas; si no, dos párrafos quedan pegados en una sola palabra.
        $text = preg_replace('/<\s*(br|\/p|\/div|\/li)\s*\/?>/i', "\n", $value) ?? $value;
        $text = preg_replace('/<\s*li[^>]*>/i', "\n- ", $text) ?? $text;

        $text = strip_tags($text);

        // &nbsp; y compañía: primero se decodifica, después se normaliza.
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]*\n[ \t]*/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
