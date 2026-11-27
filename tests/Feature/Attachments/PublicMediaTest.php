<?php

namespace Tests\Feature\Attachments;

use App\Models\Article;
use App\Models\Attachment;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Publicar una foto de artículo: sale de la carpeta privada a la pública y se sirve
 * sin sesión por /img/{token}. Los comprobantes de clientes nunca se publican.
 */
class PublicMediaTest extends TestCase
{
    use DatabaseTransactions;

    private function adjuntoDeArticulo(string $nombre = 'foto.jpg', string $mime = 'image/jpeg'): Attachment
    {
        $articulo = Article::factory()->create();

        return $this->clienteAdjunto($articulo, $nombre, $mime);
    }

    private function clienteAdjunto($modelo, string $nombre, string $mime): Attachment
    {
        Storage::fake('attachments');
        Storage::fake('media');

        // create() y no image(): fabricar un JPEG real necesita la extensión GD, que el
        // servidor no tiene instalada. Para lo que se prueba —el registro, el disco y la
        // ruta pública— alcanza con un archivo con su tipo declarado.
        $ruta = UploadedFile::fake()->create($nombre, 64, $mime)->store('galeria', 'attachments');

        return $modelo->attachments()->create([
            'collection' => 'galeria',
            'disk' => 'attachments',
            'path' => $ruta,
            'original_name' => $nombre,
            'mime_type' => $mime,
            'size' => 1024,
        ]);
    }

    public function test_una_foto_de_articulo_publicada_se_abre_sin_sesion(): void
    {
        $adjunto = $this->adjuntoDeArticulo();

        $url = $adjunto->publish();

        $this->assertNotNull($url);
        $this->assertStringContainsString('/img/' . $adjunto->fresh()->public_token, $url);

        $adjunto->refresh();
        $this->assertTrue($adjunto->is_public);
        $this->assertSame('media', $adjunto->disk);
        // La copia pública existe y la privada sigue ahí.
        $this->assertTrue(Storage::disk('media')->exists($adjunto->path));
        $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));

        $respuesta = $this->get($url);
        $respuesta->assertOk();
        $this->assertStringContainsString('immutable', (string) $respuesta->headers->get('Cache-Control'));
    }

    public function test_un_comprobante_de_cliente_no_se_puede_publicar(): void
    {
        $cliente = Customer::factory()->create();
        $adjunto = $this->clienteAdjunto($cliente, 'comprobante.jpg', 'image/jpeg');

        $this->assertFalse($adjunto->canBePublic());
        $this->assertNull($adjunto->publish());
        $this->assertFalse($adjunto->fresh()->is_public);
        $this->assertNull($adjunto->fresh()->public_url);
    }

    public function test_despublicar_devuelve_el_archivo_a_privado_y_mata_la_url(): void
    {
        $adjunto = $this->adjuntoDeArticulo();
        $url = $adjunto->publish();

        $adjunto->refresh()->unpublish();
        $adjunto->refresh();

        $this->assertFalse($adjunto->is_public);
        $this->assertNull($adjunto->public_token);
        $this->assertSame('attachments', $adjunto->disk);
        $this->assertFalse(Storage::disk('media')->exists($adjunto->path));
        $this->assertTrue(Storage::disk('attachments')->exists($adjunto->path));

        $this->get($url)->assertNotFound();
    }

    public function test_el_pdf_del_articulo_tambien_se_publica(): void
    {
        $adjunto = $this->adjuntoDeArticulo('ficha.pdf', 'application/pdf');

        $this->assertTrue($adjunto->canBePublic());
        $this->assertNotNull($adjunto->publish());
    }

    public function test_la_url_lleva_token_y_no_el_id(): void
    {
        $adjunto = $this->adjuntoDeArticulo();

        $url = $adjunto->publish();
        $adjunto->refresh();

        $this->assertStringNotContainsString('/img/' . $adjunto->id . '/', $url);
        $this->assertSame(32, strlen($adjunto->public_token));
    }

    public function test_publicar_dos_veces_no_cambia_la_url(): void
    {
        $adjunto = $this->adjuntoDeArticulo();

        $primera = $adjunto->publish();
        $segunda = $adjunto->fresh()->publish();

        $this->assertSame($primera, $segunda);
    }

    public function test_un_token_inventado_es_404(): void
    {
        $this->get('/img/no-existe/foto.jpg')->assertNotFound();
    }
}
