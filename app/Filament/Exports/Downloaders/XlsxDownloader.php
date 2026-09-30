<?php

namespace App\Filament\Exports\Downloaders;

use App\Filament\Exports\Services\ExportZipService;
use App\Models\Bank;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Outflow;
use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
use Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxDownloader implements Downloader
{
    private const REPORT_META = [
        \App\Filament\Exports\Inflow\InflowExporter::class => [
            'title' => 'Entradas',
            'amount' => 'Monto',
            'date' => 'Fecha',
            'invoice' => 'Número de factura',
            'party' => 'Cliente',
            'group_by' => 'Método de pago',
        ],
        \App\Filament\Exports\Outflow\OutflowExporter::class => [
            'title' => 'Salidas',
            'amount' => 'Monto',
            'date' => 'Fecha de factura',
            'invoice' => 'Código de factura',
            'party' => 'Empresa',
            'group_by' => 'Área',
        ],
    ];

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

        $tmpXlsx = storage_path('app/private/exports/' . Str::uuid() . '.xlsx');
        File::ensureDirectoryExists(dirname($tmpXlsx));

        $recordIds = $this->createXlsx($tmpXlsx, $disk, $directory, $export);

        // Enriquecer con TOTAL / Resumen / Análisis antes de zippear
        $reportPayload = ['path' => $tmpXlsx] + (self::REPORT_META[$export->exporter] ?? []);

        $result = Process::timeout(60)
            ->input(json_encode($reportPayload, JSON_UNESCAPED_UNICODE))
            ->run([
                base_path('python/.venv/bin/python'),
                base_path('python/scripts/cash_report.py'),
            ]);

        if ($result->failed()) {
            @unlink($tmpXlsx);

            throw new \RuntimeException(
                $result->errorOutput() ?: 'cash_report.py falló sin mensaje.'
            );
        }

        $attachments = $this->getAttachmentsForExport($export, $recordIds);

        $zipPath = $this->zipService->createZip(
            $export,
            $tmpXlsx,
            $export->file_name . '.xlsx',
            $attachments,
        );

        return response()->streamDownload(
            function () use ($zipPath, $tmpXlsx): void {
                readfile($zipPath);
                @unlink($zipPath);
                @unlink($tmpXlsx);
            },
            $export->file_name . '.zip',
            ['Content-Type' => 'application/zip'],
        );
    }

    private function createXlsx(
        string $tmpXlsx,
        FilesystemAdapter $disk,
        string $directory,
        Export $export,
    ): array {
        $writer = app(Writer::class);
        $csvDelimiter = $export->exporter::getCsvDelimiter();

        $writer->openToFile($tmpXlsx);

        $hasIdColumn = in_array($export->exporter, array_keys(self::MODEL_MAP), true);

        $this->writeHeaders($writer, $disk, $directory, $csvDelimiter, $hasIdColumn);
        $recordIds = $this->writeDataRows($writer, $disk, $directory, $csvDelimiter, $export, $hasIdColumn);

        $writer->close();

        return array_values(array_unique($recordIds));
    }

    private function writeHeaders(
        Writer $writer,
        FilesystemAdapter $disk,
        string $directory,
        string $csvDelimiter,
        bool $hasIdColumn,
    ): void {
        $file = $directory . DIRECTORY_SEPARATOR . 'headers.csv';

        if (! $disk->exists($file)) {
            return;
        }

        $reader = CsvReader::from($disk->readStream($file));
        $reader->setDelimiter($csvDelimiter);

        $results = (new Statement)->process($reader);

        foreach ($results->getRecords() as $row) {
            $row = array_values($row);

            if ($hasIdColumn) {
                array_pop($row);
            }

            $writer->addRow(Row::fromValues($row));
        }
    }

    private function writeDataRows(
        Writer $writer,
        FilesystemAdapter $disk,
        string $directory,
        string $csvDelimiter,
        Export $export,
        bool $hasIdColumn,
    ): array {
        $recordIds = [];

        foreach ($disk->files($directory) as $file) {
            if (str($file)->endsWith('headers.csv')) {
                continue;
            }

            if (! str($file)->endsWith('.csv')) {
                continue;
            }

            $reader = CsvReader::from($disk->readStream($file));
            $reader->setDelimiter($csvDelimiter);

            $results = (new Statement)->process($reader);

            foreach ($results->getRecords() as $row) {
                $row = array_values($row);

                if ($hasIdColumn) {
                    $id = array_pop($row);

                    if (filled($id)) {
                        $recordIds[] = $id;
                    }
                }

                $writer->addRow(
                    Row::fromValues($this->transformRow($row, $export)),
                );
            }
        }

        return $recordIds;
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

    private function transformRow(array $row, Export $export): array
    {
        $exporter = $export->exporter;

        if ($exporter === \App\Filament\Exports\CustomerExporter::class) {
            return $this->transformCustomerRow($row);
        }

        if ($exporter === \App\Filament\Exports\Inflow\InflowExporter::class) {
            return $this->transformInflowRow($row);
        }

        return $row;
    }

    private function transformCustomerRow(array $row): array
    {
        $economicActivity = $row[8] ?? null;
        $department = $row[9] ?? null;
        $municipality = $row[10] ?? null;
        $district = $row[11] ?? null;

        $row[8] = ActividadesEconomicas::activityName($economicActivity);
        $row[9] = ElSalvadorCatalogo::departmentName($department);
        $row[10] = ElSalvadorCatalogo::municipalityName($department, $municipality);
        $row[11] = ElSalvadorCatalogo::districtName($municipality, $district);

        return $row;
    }

    private function transformInflowRow(array $row): array
    {
        $customerId = $row[3] ?? null;
        $bankId = $row[7] ?? null;

        $customer = filled($customerId) ? Customer::find($customerId) : null;
        $bank = filled($bankId) ? Bank::find($bankId) : null;

        $row[3] = $customer?->full_name;
        $row[7] = $bank?->name;

        return $row;
    }
}