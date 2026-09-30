<?php

namespace App\Livewire;

use App\Filament\Admin\Resources\Administration\Outflows\Tables\OutflowsTable;
use App\Filament\Exports\Outflow\OutflowExporter;
use App\Filament\Imports\Outflow\OutflowImporter;
use App\Models\Outflow;
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

class OutflowsTableWidget extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return OutflowsTable::configure($table)->query(Outflow::query());
    }

    protected function getTableHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Exportar salidas')
                ->exporter(OutflowExporter::class)
                ->columnMapping(false),
            ImportAction::make()
                ->importer(OutflowImporter::class),
        ];
    }

    public function render()
    {
        return view('livewire.outflows-table-widget');
    }
}