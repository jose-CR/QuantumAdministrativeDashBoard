<?php

namespace App\Models;

use App\Models\Concerns\PublishableMedia;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'size' => 'integer',
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
                Storage::disk($attachment->disk)->delete($attachment->path);

                // Si estaba publicado, la copia de la carpeta pública también se va.
                if (filled($attachment->private_path)
                    && Storage::disk('media')->exists($attachment->path)) {
                    Storage::disk('media')->delete($attachment->path);
                }
            }
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
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

    public function canBePublic(): bool
    {
        if (! in_array($this->attachable_type, static::publishableTypes(), true)) {
            return false;
        }

        return $this->mime_type === 'application/pdf' || $this->is_image;
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