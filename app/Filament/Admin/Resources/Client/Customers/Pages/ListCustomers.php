<?php

namespace App\Filament\Admin\Resources\Client\Customers\Pages;

use App\Filament\Admin\Actions\CashReportAction;
use App\Filament\Admin\Resources\Client\Customers\CustomerResource; 
use App\Filament\Exports\CustomerExporter;
use App\Filament\Imports\CustomerImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->importer(CustomerImporter::class),
            ExportAction::make()
                ->exporter(CustomerExporter::class)
                ->modifyQueryUsing(function (Builder $query, array $options) {
                    if (
                        ! empty($options['document_type']) &&
                        $options['document_type'] !== 'ALL'
                    ) {
                        $query->where(
                            'document_type',
                            $options['document_type']
                        );
                    }
                }),
            CashReportAction::make(),
        ];
    }
}
