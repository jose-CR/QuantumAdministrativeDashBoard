<?php

namespace App\Http\Controllers\Attachments;

use App\Support\Media\MediaTypes;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve un archivo privado con sesión iniciada: la miniatura de la tabla y el visor
 * del panel. La URL va firmada y vence en 30 minutos.
 */
final class PrivateMediaController
{
    public function __invoke(string $type, int $id): StreamedResponse
    {
        $modelo = MediaTypes::model($type);

        abort_unless($modelo, 404);

        $archivo = $modelo::query()->findOrFail($id);
        $disco = $archivo->private_disk ?: 'attachments';

        abort_unless(Storage::disk($disco)->exists($archivo->path), 404);

        return Storage::disk($disco)->response($archivo->path, basename($archivo->path), [
            'Content-Type' => $archivo->mime_type ?? $this->mimeFor($archivo->path),
            'Cache-Control' => 'private, max-age=600',
        ], 'inline');
    }

    private function mimeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            default => 'image/jpeg',
        };
    }
}
