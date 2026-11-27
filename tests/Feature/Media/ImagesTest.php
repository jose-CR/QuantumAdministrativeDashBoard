<?php

namespace Tests\Feature\Media;

use App\Filament\Admin\Resources\Inventory\Gallery\Pages\ManageGallery;
use App\Models\ArticleUnit;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las imágenes del inventario son adjuntos: la foto de una unidad es un adjunto con
 * dueño (la unidad, colección `images`) y la de la galería uno sin dueño con categoría.
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

    private function fotoDeUnidad(string $nombre = 'frente.jpg'): Attachment
    {
        $unidad = ArticleUnit::factory()->create();
        $ruta = UploadedFile::fake()->create($nombre, 64, 'image/jpeg')->store('units', 'attachments');

        return $unidad->images()->create([
            'collection' => 'images',
            'disk' => 'attachments',
            'path' => $ruta,
            'original_name' => 'Frente',
            'mime_type' => 'image/jpeg',
            'size' => 64,
            'sort' => 1,
        ]);
    }

    private function fotoDeGaleria(string $nombre = 'repuesto.jpg'): Attachment
    {
        $categoria = Category::factory()->create();
        $ruta = UploadedFile::fake()->create($nombre, 64, 'image/jpeg')->store('gallery', 'attachments');

        return Attachment::create([
            'collection' => 'gallery',
            'category_id' => $categoria->id,
            'disk' => 'attachments',
            'path' => $ruta,
            'original_name' => 'Repuesto',
            'mime_type' => 'image/jpeg',
            'size' => 64,
        ]);
    }

    public function test_una_unidad_tiene_varias_fotos_en_orden(): void
    {
        $foto = $this->fotoDeUnidad();
        $unidad = $foto->attachable;

        $unidad->images()->create([
            'collection' => 'images',
            'disk' => 'attachments',
            'path' => UploadedFile::fake()->create('lateral.jpg', 10, 'image/jpeg')->store('units', 'attachments'),
            'original_name' => 'Lateral',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'sort' => 0,
        ]);

        $this->assertCount(2, $unidad->images()->get());
        $this->assertSame('Lateral', $unidad->images()->first()->original_name);
    }

    public function test_obtener_url_publica_la_foto_y_se_abre_sin_sesion(): void
    {
        $foto = $this->fotoDeUnidad();

        $url = $foto->publish();

        $this->assertNotNull($url);
        $this->assertStringContainsString('/img/', $url);
        $this->assertTrue(Storage::disk('media')->exists($foto->path));

        $respuesta = $this->get($url);

        $respuesta->assertOk();
        $this->assertSame('image/jpeg', $respuesta->headers->get('Content-Type'));
    }

    public function test_la_url_lleva_token_y_no_el_id(): void
    {
        $foto = $this->fotoDeUnidad();

        $url = $foto->publish();
        $foto->refresh();

        $this->assertStringNotContainsString('/img/' . $foto->id . '/', $url);
        $this->assertSame(32, strlen($foto->public_token));
    }

    public function test_despublicar_mata_la_url_y_borra_la_copia(): void
    {
        $foto = $this->fotoDeUnidad();
        $url = $foto->publish();

        $foto->refresh()->unpublish();

        $this->get($url)->assertNotFound();
        $this->assertFalse($foto->refresh()->is_public);
        $this->assertFalse(Storage::disk('media')->exists($foto->path));
        $this->assertTrue(Storage::disk('attachments')->exists($foto->path));
    }

    public function test_la_galeria_usa_la_categoria_del_inventario(): void
    {
        $foto = $this->fotoDeGaleria();

        $this->assertNotNull($foto->category);
        $this->assertNull($foto->attachable_id);
        $this->assertSame(Category::class, $foto->category::class);
    }

    public function test_una_foto_de_galeria_tambien_se_publica(): void
    {
        $foto = $this->fotoDeGaleria();

        $url = $foto->publish();

        $this->assertNotNull($url);
        $this->get($url)->assertOk();
    }

    public function test_borrar_la_foto_borra_sus_archivos(): void
    {
        $foto = $this->fotoDeUnidad();
        $ruta = $foto->path;
        $foto->publish();

        $foto->refresh()->delete();

        $this->assertFalse(Storage::disk('media')->exists($ruta));
        $this->assertFalse(Storage::disk('attachments')->exists($ruta));
    }

    public function test_no_se_borra_una_categoria_con_fotos_publicadas(): void
    {
        $foto = $this->fotoDeGaleria();
        $categoria = $foto->category;
        $foto->publish();

        $this->assertFalse($categoria->delete());
        $this->assertDatabaseHas('categories', ['id' => $categoria->id]);
    }

    public function test_al_publicar_desde_la_pantalla_la_fila_queda_en_publicada(): void
    {
        $foto = $this->fotoDeGaleria();
        $admin = $this->admin();

        $this->assertTrue($admin->can('Publish:Media'));

        Livewire::actingAs($admin)
            ->test(ManageGallery::class)
            ->loadTable()
            ->callTableAction('obtenerUrl', $foto)
            ->assertTableColumnStateSet('is_public', true, record: $foto);

        $this->assertTrue($foto->refresh()->is_public);
        $this->assertNotNull($foto->public_url);
    }

    public function test_publicar_pide_su_propio_permiso(): void
    {
        $foto = $this->fotoDeGaleria();

        $operador = User::factory()->create();
        $operador->assignRole('Administrativo');

        $this->assertTrue($operador->can('ViewAny:Attachment'));
        $this->assertFalse($operador->can('Publish:Media'));

        Livewire::actingAs($operador)
            ->test(ManageGallery::class)
            ->assertTableActionHidden('obtenerUrl', $foto);
    }
}
