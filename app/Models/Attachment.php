<?php

namespace App\Models;

use App\Models\Concerns\PublishableMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Attachment extends Model
{
    // Publicar, despublicar y armar la URL: la misma lógica que las imágenes del
    // inventario (app/Models/Concerns/PublishableMedia.php).
    use PublishableMedia;

    protected $fillable = [
        'collection',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'is_public',
        'public_token',
        'private_disk',
        'private_path',
        'category_id',
        'sort',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'size' => 'integer',
        'sort' => 'integer',
    ];

    protected $appends = [
        'url',
        'extension',
        'is_image',
        'formatted_size',
        'public_url',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            // Un comprobante que paga varias cuotas es un solo archivo con varias
            // filas: se borra del disco recién cuando no queda ninguna otra.
            $compartido = static::query()
                ->where('disk', $attachment->disk)
                ->where('path', $attachment->path)
                ->whereKeyNot($attachment->getKey())
                ->exists();

            if (! $compartido) {
                // Se borran las dos copias: la del disco actual y, si estaba publicado,
                // la de la carpeta pública y la privada de origen.
                foreach (array_unique(array_filter([
                    $attachment->disk,
                    'media',
                    $attachment->private_disk,
                ])) as $disco) {
                    if (Storage::disk($disco)->exists($attachment->path)) {
                        Storage::disk($disco)->delete($attachment->path);
                    }
                }
            }
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Categoría del inventario: solo la llevan las fotos sueltas de la galería.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getUrlAttribute(): string
    {
        return URL::temporarySignedRoute(
            'attachments.show',
            now()->addMinutes(30),
            ['attachment' => $this->id],
        );
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * URL firmada (30 minutos) para ver el archivo con sesión, sin exponerlo.
     * Es la que usan las miniaturas y el visor del inventario.
     */
    public function getImageUrlAttribute(): string
    {
        return URL::temporarySignedRoute('media.private', now()->addMinutes(30), [
            'type' => 'attachment',
            'id' => $this->getKey(),
        ]);
    }

    /**
     * Nombre editable de la foto (en el inventario se escribe «Carrito Duke — lateral»).
     */
    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->original_name ?: basename((string) $this->path));
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / 1024 / 1024, 1) . ' MB';
    }

    /*
    |--------------------------------------------------------------------------
    | Publicación: de la carpeta privada a la carpeta pública
    |--------------------------------------------------------------------------
    */

    /**
     * Tipos de registro que pueden publicar adjuntos.
     *
     * Los clientes están fuera a propósito: sus comprobantes, facturas e
     * identificaciones no se publican nunca.
     */
    public static function publishableTypes(): array
    {
        return array_values((array) config('quantum.public_attachments', []));
    }

    /**
     * Colecciones del inventario: fotos de una unidad y fotos sueltas de la galería.
     * Son las únicas que se publican sin preguntar de quién son.
     */
    public const IMAGE_COLLECTIONS = ['images', 'gallery'];

    public function canBePublic(): bool
    {
        if (in_array($this->collection, self::IMAGE_COLLECTIONS, true)) {
            // Fotos de producto: solo imágenes, nunca un PDF.
            return $this->is_image;
        }

        if (! in_array($this->attachable_type, static::publishableTypes(), true)) {
            return false;
        }

        return $this->mime_type === 'application/pdf' || $this->is_image;
    }

    /**
     * Fotos sueltas: sin dueño y con categoría.
     */
    public function scopeGallery($query)
    {
        return $query->where('collection', 'gallery')->whereNull('attachable_id');
    }

    /**
     * Fotos que cuelgan de una unidad del inventario.
     */
    public function scopeUnitImages($query)
    {
        return $query->where('collection', 'images');
    }

    /**
     * El adjunto sigue guardando su disco en la columna `disk`: al publicar pasa a
     * `media` y al despublicar vuelve al privado del que salió.
     *
     * @return array<string, mixed>
     */
    protected function mediaStateOnPublish(): array
    {
        return ['disk' => 'media'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mediaStateOnUnpublish(string $discoPrivado): array
    {
        return ['disk' => $discoPrivado];
    }
}