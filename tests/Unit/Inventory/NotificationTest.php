<?php

namespace Tests\Unit\Inventory;

/**
 * Mensajes que ve el usuario cuando no se puede borrar algo del inventario.
 */
class NotificationTest extends InventoryTestCase
{
    public function test_el_aviso_de_categoria_dice_que_credito_lo_impide(): void
    {
        $categoria = $this->makeCategory('Categoría con crédito');
        $unidad = $this->makeUnit($this->makeArticle($categoria));
        $credito = $this->makeCreditFor($unidad);

        $cuerpo = __('notifications.inventary.category.blocked.body', [
            'name' => $categoria->name,
            'units' => 1,
            'credits' => $categoria->creditsBlockingDeletionLabel(),
        ]);

        $this->assertStringContainsString($categoria->name, $cuerpo);
        $this->assertStringContainsString('#' . $credito->id, $cuerpo);
        $this->assertStringContainsString($credito->client->full_name, $cuerpo);
        $this->assertStringNotContainsString(':credits', $cuerpo);
    }

    public function test_el_aviso_de_articulo_dice_que_credito_lo_impide(): void
    {
        $articulo = $this->makeArticle($this->makeCategory(), ['name' => 'Moto con crédito']);
        $unidad = $this->makeUnit($articulo);
        $credito = $this->makeCreditFor($unidad);

        $cuerpo = __('notifications.inventary.article.blocked.body', [
            'name' => $articulo->full_name,
            'units' => 1,
            'credits' => $articulo->creditsBlockingDeletionLabel(),
        ]);

        $this->assertStringContainsString($articulo->full_name, $cuerpo);
        $this->assertStringContainsString('#' . $credito->id, $cuerpo);
        $this->assertStringContainsString($credito->client->full_name, $cuerpo);
    }

    public function test_la_etiqueta_corta_la_lista_cuando_hay_muchos_creditos(): void
    {
        $categoria = $this->makeCategory('Categoría con varios créditos');

        // Seis créditos con la misma unidad: solo se listan cinco.
        $unidad = $this->makeUnit($this->makeArticle($categoria));

        foreach (range(1, 6) as $ignored) {
            $this->makeCreditFor($unidad);
        }

        $etiqueta = $categoria->creditsBlockingDeletionLabel();

        $this->assertStringContainsString('y 1 más', $etiqueta);
        $this->assertSame(6, $categoria->creditsBlockingDeletion()->count());
    }

    public function test_el_resumen_de_borrado_cuenta_articulos_y_unidades(): void
    {
        $categoria = $this->makeCategory();
        $unidad = $this->makeUnit($this->makeArticle($categoria, ['name' => 'Primero']));

        $this->assertSame(
            ['articles' => 1, 'units' => 1, 'units_in_credits' => 0],
            $categoria->deletionSummary(),
        );

        $this->makeCreditFor($unidad);

        $this->assertSame(
            ['articles' => 1, 'units' => 1, 'units_in_credits' => 1],
            $categoria->deletionSummary(),
        );
    }

    public function test_el_mensaje_de_confirmacion_de_la_cascada_tiene_los_numeros(): void
    {
        $mensaje = __('notifications.inventary.category.delete.cascade', [
            'articles' => 12,
            'units' => 34,
        ]);

        $this->assertStringContainsString('12 artículos', $mensaje);
        $this->assertStringContainsString('34 unidades', $mensaje);
    }

    public function test_la_categoria_sin_creditos_no_tiene_etiqueta(): void
    {
        $categoria = $this->makeCategory();
        $this->makeUnit($this->makeArticle($categoria));

        $this->assertSame('', $categoria->creditsBlockingDeletionLabel());
        $this->assertTrue($categoria->canBeDeletedWithChildren());
    }
}
