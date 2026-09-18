<?php

namespace App\Livewire;

use App\Filament\Exports\Outflow\OutflowExporter;
use App\Filament\Imports\Outflow\OutflowImporter;
use App\Models\Outflow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;

class OutflowsTable extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;

    public function table(Table $table): Table
    {
        return $table
            ->query(Outflow::query())
            ->columns([
                Tables\Columns\TextColumn::make('invoice_date')->date()->label('Fecha'),
                Tables\Columns\TextColumn::make('company')->label('Empresa'),
                Tables\Columns\TextColumn::make('invoice_code')->label('Cod. Factura'),
                Tables\Columns\TextColumn::make('quantity')->label('Cant'),
                Tables\Columns\TextColumn::make('amount')->money('usd')->label('Monto'),
                Tables\Columns\TextColumn::make('source')->label('Caja'),
                Tables\Columns\TextColumn::make('area')->label('Área'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form([
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Fecha')
                            ->required(),

                        Forms\Components\TextInput::make('company')
                            ->label('Empresa')
                            ->required(),

                        Forms\Components\TextInput::make('invoice_code')
                            ->label('Cod. Factura')
                            ->required(),

                        RichEditor::make('description'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cant')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('area')
                            ->label('Área')
                            ->required(),
                        
                    ]),
                ExportAction::make()
                    ->label('exportar salidas')
                    ->exporter(OutflowExporter::class),
                ImportAction::make()
                    ->importer(OutflowImporter::class),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Fecha')
                            ->required(),

                        Forms\Components\TextInput::make('company')
                            ->label('Empresa')
                            ->required(),

                        Forms\Components\TextInput::make('invoice_code')
                            ->label('Cod. Factura')
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cant')
                            ->numeric()
                            ->required(),

                        RichEditor::make('description'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('area')
                            ->label('Área')
                            ->required(),
                    ]),

                DeleteAction::make(),
            ]);

    }

    public function render()
    {
        return view('livewire.outflows-table');
    }
}