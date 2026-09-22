<?php

namespace App\Filament\Exports\Downloaders;

use App\Models\Bank;
use App\Models\Customer;
use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
use Filament\Actions\Exports\Downloaders\XlsxDownloader as BaseXlsxDownloader;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxDownloader extends BaseXlsxDownloader
{
    /**
     * Exporters that get Resumen + Análisis sheets (duplicates/anomalies + projection)
     * appended by python/scripts/cash_report.py, in addition to the TOTAL row.
     * Keys must match the column labels set in each Exporter's getColumns().
     */
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

    public function __invoke(Export $export): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = $export->getFileDisk();

        $directory = $export->getFileDirectory();

        $fileName = $export->file_name . '.xlsx';

        if (! $disk->exists($directory)) {
            abort(404);
        }

        $writer = app(Writer::class);

        $csvDelimiter = $export->exporter::getCsvDelimiter();

        $tmp = storage_path('app/private/exports/' . Str::uuid() . '.xlsx');
        File::ensureDirectoryExists(dirname($tmp));

        // 1) Laravel writes the full Excel to a temp file (same logic as before,
        //    just openToFile instead of openToBrowser so Python can post-process it).
        $writer->openToFile($tmp);

        $this->writeHeaders(
            $writer,
            $disk,
            $directory,
            $csvDelimiter,
        );

        $this->writeDataRows(
            $writer,
            $disk,
            $directory,
            $csvDelimiter,
            $export,
        );

        $writer->close();

        // 2) Python appends TOTAL (+ Resumen/Análisis for exporters listed in REPORT_META)
        $payload = ['path' => $tmp] + (self::REPORT_META[$export->exporter] ?? []);

        $result = Process::timeout(60)
            ->input(json_encode($payload, JSON_UNESCAPED_UNICODE))
            ->run([
                base_path('python/.venv/bin/python'),
                base_path('python/scripts/cash_report.py'),
            ]);

        if ($result->failed()) {
            @unlink($tmp);

            throw new \RuntimeException($result->errorOutput() ?: 'cash_report.py falló sin mensaje.');
        }

        // 3) Download the final file and delete the temp copy afterwards
        return response()->streamDownload(
            function () use ($tmp): void {
                readfile($tmp);
                @unlink($tmp);
            },
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    private function writeHeaders(
        Writer $writer,
        FilesystemAdapter $disk,
        string $directory,
        string $csvDelimiter,
    ): void {
        $file = $directory . DIRECTORY_SEPARATOR . 'headers.csv';

        $reader = CsvReader::from(
            $disk->readStream($file),
        );

        $reader->setDelimiter($csvDelimiter);

        $results = (new Statement)->process($reader);

        foreach ($results->getRecords() as $row) {
            $writer->addRow(
                Row::fromValues($row),
            );
        }
    }

    private function writeDataRows(
        Writer $writer,
        FilesystemAdapter $disk,
        string $directory,
        string $csvDelimiter,
        Export $export,
    ): void {
        foreach ($disk->files($directory) as $file) {
            if (str($file)->endsWith('headers.csv')) {
                continue;
            }

            if (! str($file)->endsWith('.csv')) {
                continue;
            }

            $reader = CsvReader::from(
                $disk->readStream($file),
            );

            $reader->setDelimiter($csvDelimiter);

            $results = (new Statement)->process($reader);

            foreach ($results->getRecords() as $row) {
                $writer->addRow(
                    Row::fromValues(
                        $this->transformRow($row, $export),
                    ),
                );
            }
        }
    }

    private function transformRow(
        array $row,
        Export $export,
    ): array {
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
        $economicActivity = $row[8];
        $department = $row[9];
        $municipality = $row[10];
        $district = $row[11];

        $row[8] = ActividadesEconomicas::activityName(
            $economicActivity,
        );

        $row[9] = ElSalvadorCatalogo::departmentName(
            $department,
        );

        $row[10] = ElSalvadorCatalogo::municipalityName(
            $department,
            $municipality,
        );

        $row[11] = ElSalvadorCatalogo::districtName(
            $municipality,
            $district,
        );

        return $row;
    }

    private function transformInflowRow(array $row): array
    {
        $customerId = $row[3] ?? null;
        $bankId = $row[7] ?? null;

        $customer = filled($customerId)
            ? Customer::find($customerId)
            : null;

        $bank = filled($bankId)
            ? Bank::find($bankId)
            : null;

        $row[3] = $customer?->full_name;
        $row[7] = $bank?->name;

        return $row;
    }
}