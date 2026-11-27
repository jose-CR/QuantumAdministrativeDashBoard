<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Attachment extends Model
{
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

    public function isPublished(): bool
    {
        return $this->is_public && filled($this->public_token);
    }

    /**
     * Copia el archivo a la carpeta pública y devuelve la URL para pegar afuera.
     *
     * El original privado se anota (private_disk/private_path) para que al
     * despublicar vuelva exactamente al mismo lugar.
     */
    public function publish(): ?string
    {
        if (! $this->canBePublic()) {
            return null;
        }

        if ($this->isPublished()) {
            return $this->public_url;
        }

        $origen = Storage::disk($this->disk);

        if (! $origen->exists($this->path)) {
            return null;
        }

        Storage::disk('media')->put($this->path, $origen->get($this->path));

        $this->forceFill([
            'is_public' => true,
            'public_token' => Str::random(32),
            'private_disk' => $this->disk,
            'private_path' => $this->path,
            'disk' => 'media',
        ])->save();

        return $this->public_url;
    }

    /**
     * Despublica: el archivo vuelve a la carpeta privada y la URL deja de existir.
     */
    public function unpublish(): void
    {
        if ($this->is_public && Storage::disk($this->disk)->exists($this->path)) {
            $destino = $this->private_disk ?: 'attachments';

            Storage::disk($destino)->put($this->path, Storage::disk($this->disk)->get($this->path));
            Storage::disk($this->disk)->delete($this->path);

            $this->forceFill(['disk' => $destino]);
        }

        $this->forceFill([
            'is_public' => false,
            'public_token' => null,
            'private_disk' => null,
            'private_path' => null,
        ])->save();
    }

    /**
     * La URL que se comparte: token aleatorio (no el id) y nombre legible.
     */
    public function getPublicUrlAttribute(): ?string
    {
        if (! $this->isPublished()) {
            return null;
        }

        $base = config('quantum.media_url');
        $ruta = 'img/' . $this->public_token . '/' . $this->publicFileName();

        return filled($base)
            ? rtrim((string) $base, '/') . '/' . $ruta
            : route('attachments.public', ['token' => $this->public_token, 'name' => $this->publicFileName()]);
    }

    private function publicFileName(): string
    {
        $nombre = Str::slug(pathinfo((string) $this->original_name, PATHINFO_FILENAME)) ?: 'archivo';
        $extension = $this->extension ?: 'jpg';

        return mb_substr($nombre, 0, 60) . '.' . $extension;
    }
}