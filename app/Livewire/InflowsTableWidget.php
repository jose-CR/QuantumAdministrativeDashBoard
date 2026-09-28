<?php

namespace App\Livewire;

use App\Filament\Admin\Resources\Administration\Inflows\Tables\InflowsTable;
use App\Models\Inflow;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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

    public function render()
    {
        return view('livewire.inflows-table-widget');
    }
}