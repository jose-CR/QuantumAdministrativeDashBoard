<?php

namespace Tests\Feature;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * La descarga del export es un GET firmado a /filament/exports/{id}/download que
 * arma un ZIP (Excel + adjuntos) con python. El 404 que veía el panel venía de
 * permisos: el worker escribía 0640 y Apache (www-data) no podía leer.
 */
class ExportDownloadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_export_completado_se_descarga_en_zip(): void
    {
        $export = Export::query()
            ->whereNotNull('completed_at')
            ->latest('id')
            ->first();

        if ($export === null) {
            $this->markTestSkipped('No hay exports completados en la base para probar.');
        }

        $url = URL::signedRoute('filament.exports.download', [
            'authGuard' => 'web',
            'export' => $export,
            'format' => 'xlsx',
        ]);

        $response = $this->actingAs($export->user)->get($url);

        $response->assertOk();

        $this->assertStringContainsString(
            'application/zip',
            (string) $response->headers->get('content-type'),
        );
    }
}
