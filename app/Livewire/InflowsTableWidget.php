<?php

namespace App\Livewire;

use App\Filament\Admin\Resources\Administration\Inflows\Tables\InflowsTable;
use App\Filament\Exports\Inflow\InflowExporter;
use App\Filament\Imports\Inflow\InflowImporter;
use App\Models\Inflow;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class InflowsTableWidget extends Component implements HasActions, HasTable, HasSchemas
{
    use InteractsWithTable;
    use InteractsWithSchemas;
    use InteractsWithActions;

    public function table(Table $table): Table
    {
        return InflowsTable::configure($table)->query(Inflow::query());
    
    }

    protected function getTableHeaderActions(): array
    {
        return[
            ExportAction::make()
                ->exporter(InflowExporter::class),
            ImportAction::make()
                ->importer(InflowImporter::class),
        ];
    }

    public function render()
    {
        return view('livewire.inflows-table-widget');
    }
}