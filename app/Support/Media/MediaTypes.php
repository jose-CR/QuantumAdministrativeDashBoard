<?php

namespace App\Support\Media;

use App\Models\ArticleUnitImage;
use App\Models\Attachment;
use App\Models\GalleryImage;

/**
 * Los modelos que guardan archivos y pueden servirse por una URL.
 *
 * Un solo mapa para las dos rutas: la privada (con sesión y firma) y la pública
 * (/img/{token}, sin sesión). Agregar un tipo nuevo es una línea acá.
 */
final class MediaTypes
{
    /** @var array<string, class-string> */
    private const TYPES = [
        'attachment' => Attachment::class,
        'unit-image' => ArticleUnitImage::class,
        'gallery' => GalleryImage::class,
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
