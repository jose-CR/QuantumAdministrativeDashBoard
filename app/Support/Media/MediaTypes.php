<?php

namespace App\Support\Media;

use App\Models\Attachment;

/**
 * Los modelos que guardan archivos y pueden servirse por una URL.
 *
 * Un solo mapa para las dos rutas: la privada (con sesión y firma) y la pública
 * (/img/{token}, sin sesión). Agregar un tipo nuevo es una línea acá.
 */
final class MediaTypes
{
    /** @var array<string, class-string> */
    /*
     * Las dos fotos del inventario son adjuntos: la de la unidad es un adjunto con
     * dueño (la unidad) y la de la galería es un adjunto sin dueño con categoría.
     * Los alias se conservan para que las URLs firmadas ya generadas sigan sirviendo.
     */
    private const TYPES = [
        'attachment' => Attachment::class,
        'unit-image' => Attachment::class,
        'gallery' => Attachment::class,
    ];

    /**
     * @return array<string, class-string>
     */
    public static function all(): array
    {
        return self::TYPES;
    }

    /**
     * @return class-string|null
     */
    public static function model(string $type): ?string
    {
        return self::TYPES[$type] ?? null;
    }
}
