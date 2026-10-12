<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Services\Attachments\AttachmentRelocator;
use Illuminate\Console\Command;

class RelocateAttachments extends Command
{
    protected $signature = 'attachments:relocate {--dry-run : Solo muestra qué se movería}';

    protected $description = 'Ordena los adjuntos en su carpeta correcta (empresa/factura)';

    protected $help = <<<'TXT'
    QUÉ HACE
    Revisa los archivos adjuntos y mueve a su carpeta correcta los que quedaron
    en otro lugar. La carpeta correcta se arma con los datos del registro:

        outflow/NOMBRE-DE-LA-EMPRESA/NUMERO-DE-FACTURA/archivo.pdf

    CUÁNDO USARLO
    - Se corrigió la empresa o la factura de un registro que ya tenía adjuntos.
    - Hay adjuntos viejos en carpetas con formato anterior (por ejemplo 1-pp).

    CÓMO USARLO
    1. Primero, solo ver qué se movería (no cambia nada):

        sudo -u www-data php artisan attachments:relocate --dry-run

    2. Si la lista se ve bien, ejecutarlo de verdad:

        sudo -u www-data php artisan attachments:relocate

    Debe ejecutarse con "sudo -u www-data". Si se ejecuta con otro usuario,
    falla por permisos al crear carpetas.

    QUÉ ESPERAR
    Muestra una línea por archivo movido (ruta vieja → ruta nueva) y al final el
    total. Las carpetas viejas que quedan vacías se borran solas. Los archivos
    que ya están en su lugar no se tocan, así que es seguro correrlo varias veces.

    SI ALGO SALE MAL
    Se guardan respaldos antes de mover. Si el resultado no es el esperado,
    avisar a quien administra el sistema antes de repetir el comando.
    TXT;

    public function handle(AttachmentRelocator $relocator): int
    {
        $moved = 0;

        Attachment::with('attachable')->each(function (Attachment $attachment) use ($relocator, &$moved) {
            $old = $attachment->path;
            $new = $relocator->relocate($attachment, $this->option('dry-run'));

            if ($new) {
                $this->line("{$old}  →  {$new}");
                $moved++;
            }
        });

        $this->info(($this->option('dry-run') ? 'A mover: ' : 'Movidos: ') . $moved);

        return self::SUCCESS;
    }
}