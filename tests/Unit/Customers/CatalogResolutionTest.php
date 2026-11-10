<?php

namespace Tests\Unit\Customers;

use App\Support\ElSalvadorCatalogo;
use Tests\TestCase;

/**
 * El export escribe nombres y la base guarda códigos: el import tiene que aceptar
 * los dos, y rechazar lo que no existe.
 */
class CatalogResolutionTest extends TestCase
{
    public function test_un_departamento_se_resuelve_por_codigo(): void
    {
        $codigos = array_keys(ElSalvadorCatalogo::departments());

        $this->assertSame($codigos[0], ElSalvadorCatalogo::resolveDepartment($codigos[0]));
    }

    public function test_un_departamento_se_resuelve_por_nombre(): void
    {
        $codigo = array_key_first(ElSalvadorCatalogo::departments());
        $nombre = ElSalvadorCatalogo::departments()[$codigo];

        $this->assertSame($codigo, ElSalvadorCatalogo::resolveDepartment($nombre));
        $this->assertSame($codigo, ElSalvadorCatalogo::resolveDepartment(strtolower($nombre)));
        $this->assertSame($codigo, ElSalvadorCatalogo::resolveDepartment('  ' . $nombre . ' '));
    }

    public function test_un_municipio_se_resuelve_dentro_de_su_departamento(): void
    {
        $departamento = array_key_first(ElSalvadorCatalogo::departments());
        $municipio = array_key_first(ElSalvadorCatalogo::municipalities($departamento));
        $nombre = ElSalvadorCatalogo::municipalities($departamento)[$municipio];

        $this->assertSame(
            $municipio,
            ElSalvadorCatalogo::resolveMunicipality($departamento, $nombre)
        );
    }

    public function test_un_distrito_se_resuelve_dentro_de_su_municipio(): void
    {
        $departamento = array_key_first(ElSalvadorCatalogo::departments());
        $municipio = array_key_first(ElSalvadorCatalogo::municipalities($departamento));
        $distrito = array_key_first(ElSalvadorCatalogo::districts($municipio));
        $nombre = ElSalvadorCatalogo::districts($municipio)[$distrito];

        $this->assertSame(
            $distrito,
            ElSalvadorCatalogo::resolveDistrict($municipio, $nombre)
        );
    }

    public function test_lo_que_no_existe_devuelve_null(): void
    {
        $this->assertNull(ElSalvadorCatalogo::resolveDepartment('Narnia'));
        $this->assertNull(ElSalvadorCatalogo::resolveDepartment(''));
    }
}
