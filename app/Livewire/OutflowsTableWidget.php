<?php

namespace App\Livewire;

use App\Filament\Admin\Resources\Administration\Outflows\Tables\OutflowsTable;
use App\Models\Outflow;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class OutflowsTableWidget extends Component implements HasTable, HasSchemas, HasActions
{
    use InteractsWithTable;
    use InteractsWithSchemas;
    use InteractsWithActions;

    public function table(Table $table): Table
    {
        return OutflowsTable::configure($table)->query(Outflow::query());
    }

    public function render()
    {
        return view('livewire.outflows-table-widget');
    }
}
