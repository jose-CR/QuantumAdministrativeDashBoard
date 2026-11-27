<?php

namespace App\Models;

use App\Models\Concerns\PublishableMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * Foto suelta del taller: lo que no pertenece a ninguna unidad (un repuesto, un daño,
 * una herramienta). Se clasifica por categoría — la misma de Inventario.
 */
class GalleryImage extends Model
{
    use PublishableMedia;

    /** Clave con la que esta imagen se resuelve en las rutas de media. */
    public const MEDIA_TYPE = 'gallery';

    protected $fillable = [
        'category_id',
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
        static::deleting(function (GalleryImage $image): void {
            $image->deleteMediaFile();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function canBePublic(): bool
    {
        return true;
    }

    public function getUrlAttribute(): string
    {
        return URL::temporarySignedRoute('media.private', now()->addMinutes(30), [
            'type' => self::MEDIA_TYPE,
            'id' => $this->getKey(),
        ]);
    }
}
