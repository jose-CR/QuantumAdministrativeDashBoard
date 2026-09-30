<?php

namespace App\Filament\Exports\Downloaders;

use App\Filament\Exports\Services\ExportZipService;
use App\Models\Inflow;
use App\Models\Outflow;
use Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvDownloader implements Downloader
{
    private const MODEL_MAP = [
        \App\Filament\Exports\Inflow\InflowExporter::class => Inflow::class,
        \App\Filament\Exports\Outflow\OutflowExporter::class => Outflow::class,
    ];

    public function __construct(
        private readonly ExportZipService $zipService,
    ) {}

    public function __invoke(Export $export): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = $export->getFileDisk();
        $directory = $export->getFileDirectory();

        if (! $disk->exists($directory)) {
            abort(404);
        }

        $tmpCsv = storage_path('app/private/exports/' . Str::uuid() . '.csv');
        File::ensureDirectoryExists(dirname($tmpCsv));

        $recordIds = $this->createCsv($tmpCsv, $disk, $directory, $export);

        $attachments = $this->getAttachmentsForExport($export, $recordIds);

        $zipPath = $this->zipService->createZip(
            $export,
            $tmpCsv,
            $export->file_name . '.csv',
            $attachments,
        );

        return response()->streamDownload(
            function () use ($zipPath, $tmpCsv): void {
                readfile($zipPath);
                @unlink($zipPath);
                @unlink($tmpCsv);
            },
            $export->file_name . '.zip',
            ['Content-Type' => 'application/zip'],
        );
    }

    private function createCsv(
        string $tmpCsv,
        FilesystemAdapter $disk,
        string $directory,
        Export $export,
    ): array {
        $handle = fopen($tmpCsv, 'w');

        if ($handle === false) {
            throw new \RuntimeException('No se pudo crear el CSV temporal.');
        }

        $recordIds = [];
        $hasIdColumn = in_array($export->exporter, array_keys(self::MODEL_MAP), true);
        $headersFile = $directory . '/headers.csv';

        if ($disk->exists($headersFile)) {
            $stream = $disk->readStream($headersFile);

            if ($stream !== false) {
                $headerRow = fgetcsv($stream);
                fclose($stream);

                if ($hasIdColumn && $headerRow) {
                    array_pop($headerRow);
                }

                if ($headerRow) {
                    fputcsv($handle, $headerRow);
                }
            }
        }

        foreach ($disk->files($directory) as $file) {
            if (str($file)->endsWith('headers.csv') || ! str($file)->endsWith('.csv')) {
                continue;
            }

            $stream = $disk->readStream($file);

            if ($stream === false) {
                continue;
            }

            while (($row = fgetcsv($stream)) !== false) {
                if ($hasIdColumn) {
                    $id = array_pop($row);

                    if (filled($id)) {
                        $recordIds[] = $id;
                    }
                }

                fputcsv($handle, $row);
            }

            fclose($stream);
        }

        fclose($handle);

        return array_values(array_unique($recordIds));
    }

    private function getAttachmentsForExport(Export $export, array $recordIds): Collection
    {
        $modelClass = self::MODEL_MAP[$export->exporter] ?? null;

        if (! $modelClass || empty($recordIds)) {
            return collect();
        }

        return $modelClass::query()
            ->whereIn('id', $recordIds)
            ->with('attachments')
            ->get()
            ->flatMap(fn ($record) => $record->attachments);
    }
}