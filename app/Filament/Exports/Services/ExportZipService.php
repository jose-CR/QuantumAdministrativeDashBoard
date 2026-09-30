<?php

namespace App\Filament\Exports\Services;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportZipService
{
    public function createZip(
        Export $export,
        string $path,
        string $fileNameInsideZip,
        Collection $attachments,
    ): string {
        $directory = storage_path('app/private/exports');
        File::ensureDirectoryExists($directory);

        $zipPath = $directory . '/' . Str::uuid() . '.zip';

        $payload = [
            'zip_only' => true,
            'path' => $path,
            'xlsx_name' => $fileNameInsideZip,
            'zip_path' => $zipPath,
            'attachments' => $attachments
                ->map(fn ($attachment) => [
                    'source' => Storage::disk($attachment->disk)->path($attachment->path),
                    'target' => $attachment->path,
                ])
                ->values()
                ->all(),
        ];

        $result = Process::timeout(60)
            ->input(json_encode($payload, JSON_UNESCAPED_UNICODE))
            ->run([
                base_path('python/.venv/bin/python'),
                base_path('python/scripts/cash_report.py'),
            ]);

        if ($result->failed()) {
            @unlink($zipPath);

            throw new \RuntimeException(
                $result->errorOutput() ?: 'cash_report.py falló sin mensaje.'
            );
        }

        if (! file_exists($zipPath)) {
            throw new \RuntimeException('cash_report.py no generó el archivo ZIP.');
        }

        return $zipPath;
    }
}