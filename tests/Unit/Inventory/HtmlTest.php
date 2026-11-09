<?php

namespace Tests\Unit\Inventory;

use App\Support\Html;
use PHPUnit\Framework\TestCase;

/**
 * Prueba pura (sin base de datos ni aplicación): el helper que convierte el HTML
 * del editor en texto plano para el Excel.
 */
class HtmlTest extends TestCase
{
    public function test_convierte_el_html_del_editor_en_texto_plano(): void
    {
        $this->assertSame('Batería 48V de litio', Html::plain('<p>Batería <b>48V</b> de litio</p>'));
    }

    public function test_un_parrafo_vacio_queda_en_null(): void
    {
        $this->assertNull(Html::plain('<p></p>'));
        $this->assertNull(Html::plain('<p><br></p>'));
        $this->assertNull(Html::plain(''));
        $this->assertNull(Html::plain('   '));
        $this->assertNull(Html::plain(null));
    }

    public function test_decodifica_entidades(): void
    {
        $this->assertSame('Repuestos & baterías', Html::plain('<p>Repuestos &amp; baterías</p>'));
    }

    public function test_separa_los_parrafos_con_salto_de_linea(): void
    {
        $this->assertSame("Linea 1\nLinea 2", Html::plain('<p>Linea 1</p><p>Linea 2</p>'));
    }

    public function test_un_texto_plano_no_se_modifica(): void
    {
        $this->assertSame('Nota normal', Html::plain('Nota normal'));
    }
}
