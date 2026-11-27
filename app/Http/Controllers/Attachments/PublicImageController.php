<?php

namespace App\Http\Controllers\Attachments;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve un adjunto publicado, sin sesión: es la URL que se comparte (Mercado Libre,
 * catálogo, redes).
 *
 * Reglas:
 *  - solo adjuntos publicados con token válido; el resto es 404, no 403 (no se
 *    confirma que el archivo exista);
 *  - solo imágenes o PDF, y solo de los tipos que pueden publicar (artículos);
 *  - caché larga e inmutable: el token es único por archivo y su contenido no cambia.
 */
final class PublicImageController
{
    public function __invoke(string $token, ?string $name = null): StreamedResponse
    {
        $adjunto = Attachment::query()
            ->where('public_token', $token)
            ->where('is_public', true)
            ->first();

        abort_unless($adjunto && $adjunto->canBePublic(), 404);
        abort_unless($adjunto->disk === 'media', 404);
        abort_unless(Storage::disk($adjunto->disk)->exists($adjunto->path), 404);

        return Storage::disk($adjunto->disk)->response(
            $adjunto->path,
            $name ?: basename($adjunto->path),
            [
                'Content-Type' => $adjunto->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'X-Robots-Tag' => 'all',
            ],
            'inline',
        );
    }
}
