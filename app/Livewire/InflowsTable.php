<?php

namespace App\Livewire;

use App\Filament\Exports\Inflow\InflowExporter;
use App\Models\Inflow;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Imports\Inflow\InflowImporter;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;

class InflowsTable extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;

    public function table(Table $table): Table
    {
        return $table
            ->query(Inflow::query())
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('N° Factura'),
                Tables\Columns\TextColumn::make('date')->date()->label('Fecha'),
                Tables\Columns\TextColumn::make('customer.full_name')->label('Cliente'),
                Tables\Columns\TextColumn::make('amount')->money('usd')->label('Monto'),
                Tables\Columns\TextColumn::make('payment_method')->label('Forma de Pago'),
                Tables\Columns\TextColumn::make('transfer_number')->label('N° Transferencia'),
                Tables\Columns\TextColumn::make('bank.name')->label('Banco'),
                Tables\Columns\TextColumn::make('transfer_date')->date()->label('Fecha Transacción'),
                Tables\Columns\TextColumn::make('payment_status')->badge()->label('Estado de Pago'),
                Tables\Columns\TextColumn::make('salesperson')->label('Vendedor'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form([
                        Forms\Components\TextInput::make('invoice_number')->label('N° Factura')->required(),
                        Forms\Components\DatePicker::make('date')->label('Fecha')->required(),
                        Forms\Components\Textarea::make('description')->label('Descripción'),
                        Forms\Components\Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount')->label('Monto')->numeric()->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pago')
                            ->options(['cash' => 'Efectivo', 'transfer' => 'Transferencia'])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('transfer_number')
                            ->label('N° Transferencia')
                            ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),
                        Forms\Components\Select::make('bank_id')
                            ->label('Banco')
                            ->relationship('bank', 'name')
                            ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),
                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('Fecha Transacción')
                            ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),
                        Forms\Components\Select::make('payment_status')
                            ->label('Estado de Pago')
                            ->options(['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'])
                            ->required(),
                        Forms\Components\TextInput::make('salesperson')->label('Vendedor')->required(),
                        Forms\Components\Textarea::make('notes')->label('Observaciones'),
                    ]),
                ExportAction::make()
                    ->exporter(InflowExporter::class),
                ImportAction::make()
                    ->importer(InflowImporter::class),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('N° Factura')
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción'),

                        Forms\Components\Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pago')
                            ->options([
                                'cash' => 'Efectivo',
                                'transfer' => 'Transferencia',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('transfer_number')
                            ->label('N° Transferencia')
                            ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                        Forms\Components\Select::make('bank_id')
                            ->label('Banco')
                            ->relationship('bank', 'name')
                            ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('Fecha Transacción')
                            ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                        Forms\Components\Select::make('payment_status')
                            ->label('Estado de Pago')
                            ->options([
                                'pending' => 'Pendiente',
                                'partial' => 'Parcial',
                                'paid' => 'Pagado',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('salesperson')
                            ->label('Vendedor')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones'),
                    ]),

                DeleteAction::make(),
            ]);
    }

    public function render()
    {
        return view('livewire.inflows-table');
    }
}