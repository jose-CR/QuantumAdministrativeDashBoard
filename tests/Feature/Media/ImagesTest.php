<?php

namespace Tests\Feature\Media;

use App\Models\ArticleUnit;
use App\Models\ArticleUnitImage;
use App\Models\Category;
use App\Models\GalleryImage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Imágenes del inventario: las de una unidad y las de la galería libre.
 *
 * Las dos se suben privadas, se publican con la misma URL con token y se sirven sin
 * sesión. La categoría de la galería es la de Inventario.
 */
class ImagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('attachments');
        Storage::fake('media');
    }

    private function imagenDeUnidad(string $nombre = 'frente.jpg'): ArticleUnitImage
    {
        $unidad = ArticleUnit::factory()->create();
        $ruta = UploadedFile::fake()->create($nombre, 64, 'image/jpeg')->store('units', 'attachments');

        return $unidad->images()->create([
            'name' => 'Frente',
            'path' => $ruta,
            'sort' => 1,
        ]);
    }

    private function imagenDeGaleria(string $nombre = 'repuesto.jpg'): GalleryImage
    {
        $categoria = Category::factory()->create();
        $ruta = UploadedFile::fake()->create($nombre, 64, 'image/jpeg')->store('gallery', 'attachments');

        return GalleryImage::create([
            'category_id' => $categoria->id,
            'name' => 'Repuesto',
            'path' => $ruta,
        ]);
    }

    public function test_una_unidad_tiene_varias_imagenes_en_orden(): void
    {
        $imagen = $this->imagenDeUnidad();
        $unidad = $imagen->unit;

        $unidad->images()->create([
            'name' => 'Lateral',
            'path' => UploadedFile::fake()->create('lateral.jpg', 10, 'image/jpeg')->store('units', 'attachments'),
            'sort' => 0,
        ]);

        $this->assertCount(2, $unidad->images()->get());
        // La de menor orden primero.
        $this->assertSame('Lateral', $unidad->images()->first()->name);
    }

    public function test_obtener_url_publica_la_imagen_y_se_abre_sin_sesion(): void
    {
        $imagen = $this->imagenDeUnidad();

        $url = $imagen->publish();

        $this->assertNotNull($url);
        $this->assertStringContainsString('/img/', $url);
        $this->assertTrue(Storage::disk('media')->exists($imagen->path));

        $respuesta = $this->get($url);

        $respuesta->assertOk();
        $this->assertSame('image/jpeg', $respuesta->headers->get('Content-Type'));
    }

    public function test_la_url_lleva_token_y_no_el_id(): void
    {
        $imagen = $this->imagenDeUnidad();

        $url = $imagen->publish();
        $imagen->refresh();

        $this->assertStringNotContainsString('/img/' . $imagen->id . '/', $url);
        $this->assertSame(32, strlen($imagen->public_token));
    }

    public function test_despublicar_mata_la_url_y_borra_la_copia(): void
    {
        $imagen = $this->imagenDeUnidad();
        $url = $imagen->publish();

        $imagen->refresh()->unpublish();

        $this->get($url)->assertNotFound();
        $this->assertFalse($imagen->refresh()->is_public);
        $this->assertFalse(Storage::disk('media')->exists($imagen->path));
        // El original privado sigue ahí.
        $this->assertTrue(Storage::disk('attachments')->exists($imagen->path));
    }

    public function test_la_galeria_usa_la_categoria_de_inventario(): void
    {
        $imagen = $this->imagenDeGaleria();

        $this->assertNotNull($imagen->category);
        $this->assertSame(Category::class, $imagen->category::class);
    }

    public function test_una_imagen_de_galeria_tambien_se_publica(): void
    {
        $imagen = $this->imagenDeGaleria();

        $url = $imagen->publish();

        $this->assertNotNull($url);
        $this->get($url)->assertOk();
    }

    public function test_borrar_la_imagen_borra_sus_archivos(): void
    {
        $imagen = $this->imagenDeUnidad();
        $ruta = $imagen->path;
        $imagen->publish();

        $imagen->refresh()->delete();

        $this->assertFalse(Storage::disk('media')->exists($ruta));
        $this->assertFalse(Storage::disk('attachments')->exists($ruta));
    }

    public function test_no_se_borra_una_categoria_con_imagenes_publicadas(): void
    {
        $imagen = $this->imagenDeGaleria();
        $categoria = $imagen->category;
        $imagen->publish();

        $this->assertFalse($categoria->delete());
        $this->assertDatabaseHas('categories', ['id' => $categoria->id]);
        $this->assertDatabaseHas('gallery_images', ['id' => $imagen->id]);
    }

    public function test_sin_el_permiso_de_publicar_las_acciones_no_aparecen(): void
    {
        $imagen = $this->imagenDeUnidad();
        $edit = $this->admin();          // sin Publish:Media
        $this->actingAs($edit);

        $this->assertFalse($edit->can('Publish:Media'));
        $this->assertTrue($imagen->canBePublic());
    }
}
