<?php

namespace App\Http\Controllers\Attachments;

use App\Support\Media\MediaTypes;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve un archivo publicado, SIN sesión: es la URL que se comparte.
 *
 * El token aleatorio es la autorización. Se busca en los modelos que publican
 * (adjuntos, fotos de unidad, galería) y lo que no aparece es 404 — no 403, para no
 * confirmar que el archivo existe.
 */
final class PublicImageController
{
    public function __invoke(string $token, ?string $name = null): StreamedResponse
    {
        $archivo = null;

        foreach (MediaTypes::all() as $modelo) {
            $archivo = $modelo::query()
                ->where('public_token', $token)
                ->where('is_public', true)
                ->first();

            if ($archivo) {
                break;
            }
        }

        abort_unless($archivo && $archivo->canBePublic(), 404);

        // El archivo tiene que estar en la copia pública, no en la privada.
        abort_unless(Storage::disk('media')->exists($archivo->path), 404);

        return Storage::disk('media')->response(
            $archivo->path,
            $name ?: basename($archivo->path),
            [
                'Content-Type' => $archivo->mime_type ?? $this->mimeFor($archivo->path),
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'X-Robots-Tag' => 'all',
            ],
            'inline',
        );
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
