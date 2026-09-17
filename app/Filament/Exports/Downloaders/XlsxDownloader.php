<?php

namespace App\Filament\Exports\Downloaders;

use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
use Filament\Actions\Exports\Downloaders\XlsxDownloader as BaseXlsxDownloader;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxDownloader extends BaseXlsxDownloader
{
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

        return response()->streamDownload(
            function () use (
                $disk,
                $directory,
                $fileName,
                $writer,
                $csvDelimiter,
            ): void {
                $writer->openToBrowser($fileName);

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
                );

                $writer->close();
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
                        $this->transformRow($row),
                    ),
                );
            }
        }
    }

    private function transformRow(array $row): array
    {
        $economicActivity = $row[8];
        $department = $row[9];
        $municipality = $row[10];
        $district = $row[11];

        $row[8] = ActividadesEconomicas::activityName($economicActivity);

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
}