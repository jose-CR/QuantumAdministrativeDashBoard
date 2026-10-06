<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Services\Attachments\AttachmentRelocator;
use Illuminate\Console\Command;

class RelocateAttachments extends Command
{
    protected $signature = 'attachments:relocate {--dry-run : Solo muestra qué se movería}';

    protected $description = 'Mueve los adjuntos a la carpeta que les corresponde según la configuración';

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