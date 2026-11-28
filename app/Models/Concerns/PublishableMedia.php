<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Publicar un archivo: copiarlo a la carpeta pública y devolver una URL para compartir.
 *
 * Lo usan los adjuntos (comprobantes de cliente, que normalmente no se publican) y las
 * imágenes del inventario (fotos de una unidad y galería), para que la ruta
 * /img/{token} y el botón «Obtener URL» sean uno solo.
 *
 * El archivo nunca se mueve: se copia. El original privado se queda donde estaba y la
 * copia pública se borra al despublicar.
 */
trait PublishableMedia
{
    /** Disco donde se copia lo publicado. */
    protected function mediaDisk(): string
    {
        return 'media';
    }

    /** Disco privado de origen (el que tenía antes de publicarse). */
    protected function privateDisk(): string
    {
        return $this->private_disk ?: ($this->disk ?? 'attachments');
    }

    public function isPublished(): bool
    {
        return (bool) $this->is_public && filled($this->public_token);
    }

    /**
     * Copia el archivo a la carpeta pública y devuelve la URL para compartir.
     * Devuelve null si el tipo no puede publicarse o el archivo ya no está.
     */
    public function publish(): ?string
    {
        // Un adjunto creado desde un formulario puede no traer mime ni tamaño: se
        // completan desde el archivo antes de decidir si se puede publicar.
        if (method_exists($this, 'fillMediaMetadata')) {
            $this->fillMediaMetadata();
        }

        if (! $this->canBePublic()) {
            return null;
        }

        if ($this->isPublished()) {
            return $this->public_url;
        }

        $privado = $this->privateDisk();
        $origen = Storage::disk($privado);

        if (! $origen->exists($this->path)) {
            return null;
        }

        Storage::disk($this->mediaDisk())->put($this->path, $origen->get($this->path));

        $this->forceFill([
            'is_public' => true,
            'public_token' => Str::random(32),
            'private_disk' => $privado,
            'private_path' => $this->path,
        ] + $this->mediaStateOnPublish())->save();

        return $this->public_url;
    }

    /**
     * Despublica: borra la copia pública y la URL deja de existir.
     */
    public function unpublish(): void
    {
        $privado = $this->privateDisk();

        if ($this->isPublished() && Storage::disk($this->mediaDisk())->exists($this->path)) {
            Storage::disk($this->mediaDisk())->delete($this->path);
        }

        $this->forceFill([
            'is_public' => false,
            'public_token' => null,
            'private_disk' => null,
            'private_path' => null,
        ] + $this->mediaStateOnUnpublish($privado))->save();
    }

    /**
     * La URL que se comparte: token aleatorio (no el id) y nombre legible.
     */
    public function getPublicUrlAttribute(): ?string
    {
        if (! $this->isPublished()) {
            return null;
        }

        $ruta = 'img/' . $this->public_token . '/' . $this->publicFileName();
        $base = config('quantum.media_url');

        return filled($base)
            ? rtrim((string) $base, '/') . '/' . $ruta
            : route('attachments.public', ['token' => $this->public_token, 'name' => $this->publicFileName()]);
    }

    /**
     * Borra el archivo físico: la copia pública si está publicada, y el original
     * privado. Se llama desde el `deleting` del modelo.
     */
    public function deleteMediaFile(): void
    {
        if (Storage::disk($this->mediaDisk())->exists($this->path)) {
            Storage::disk($this->mediaDisk())->delete($this->path);
        }

        if (Storage::disk($this->privateDisk())->exists($this->path)) {
            Storage::disk($this->privateDisk())->delete($this->path);
        }
    }

    /**
     * Nombre que se ve al final de la URL.
     */
    protected function publicFileName(): string
    {
        $base = $this->name ?? $this->original_name ?? 'archivo';
        $nombre = Str::slug(pathinfo((string) $base, PATHINFO_FILENAME)) ?: 'archivo';
        $extension = pathinfo((string) $this->path, PATHINFO_EXTENSION) ?: 'jpg';

        return mb_substr($nombre, 0, 60) . '.' . $extension;
    }

    /**
     * Columnas extra al publicar (los adjuntos guardan el disco en `disk`).
     *
     * @return array<string, mixed>
     */
    protected function mediaStateOnPublish(): array
    {
        return [];
    }

    /**
     * Columnas extra al despublicar, con el disco privado al que se vuelve.
     *
     * @return array<string, mixed>
     */
    protected function mediaStateOnUnpublish(string $discoPrivado): array
    {
        return [];
    }
}
