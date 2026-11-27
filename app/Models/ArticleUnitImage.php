<?php

namespace App\Models;

use App\Models\Concerns\PublishableMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * Una foto de una unidad concreta (un carrito con su VIN, una batería).
 *
 * Se sube privada; «Obtener URL» la copia a la carpeta pública y devuelve la
 * dirección para compartir (Mercado Libre, catálogo, redes).
 */
class ArticleUnitImage extends Model
{
    use PublishableMedia;

    /** Clave con la que esta imagen se resuelve en las rutas de media. */
    public const MEDIA_TYPE = 'unit-image';

    protected $fillable = [
        'article_unit_id',
        'name',
        'path',
        'sort',
        'is_public',
        'public_token',
        'private_disk',
        'private_path',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort' => 'integer',
    ];

    protected $appends = ['url', 'public_url'];

    protected static function booted(): void
    {
        static::deleting(function (ArticleUnitImage $image): void {
            $image->deleteMediaFile();
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ArticleUnit::class, 'article_unit_id');
    }

    /**
     * Las fotos del inventario siempre se pueden publicar: son imágenes del producto.
     */
    public function canBePublic(): bool
    {
        return true;
    }

    /**
     * URL firmada (30 minutos) para verla con sesión, sin exponer el archivo.
     */
    public function getUrlAttribute(): string
    {
        return URL::temporarySignedRoute('media.private', now()->addMinutes(30), [
            'type' => self::MEDIA_TYPE,
            'id' => $this->getKey(),
        ]);
    }
}
