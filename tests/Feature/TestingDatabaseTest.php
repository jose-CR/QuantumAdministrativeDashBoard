<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las pruebas nunca deben correr contra la base de desarrollo: los tests que no
 * usan transacciones dejarían basura en los datos reales.
 */
class TestingDatabaseTest extends TestCase
{
    #[Test]
    public function las_pruebas_corren_contra_una_base_aparte(): void
    {
        $base = DB::connection()->getDatabaseName();

        $this->assertStringEndsWith(
            '_test',
            (string) $base,
            "Las pruebas corrieron contra '{$base}'. Revisá DB_DATABASE en phpunit.xml.",
        );
    }
}
