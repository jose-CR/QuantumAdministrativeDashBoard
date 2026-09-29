<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Schemas;

use App\Models\Attachment;
use App\Models\Inflow;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label('N° Factura')
                    ->required(),

                DatePicker::make('date')
                    ->label('Fecha')
                    ->required(),

                Textarea::make('description')
                    ->label('Descripción'),

                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->required(),

                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->required(),

                Select::make('payment_method')
                    ->label('Forma de Pago')
                    ->options(['cash' => 'Efectivo', 'transfer' => 'Transferencia'])
                    ->required()
                    ->live(),

                TextInput::make('transfer_number')
                    ->label('N° Transferencia')
                    ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                Select::make('bank_id')
                    ->label('Banco')
                    ->relationship('bank', 'name')
                    ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                DatePicker::make('transfer_date')
                    ->label('Fecha Transacción')
                    ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                Select::make('payment_status')
                    ->label('Estado de Pago')
                    ->options(['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'])
                    ->required(),

                TextInput::make('salesperson')
                    ->label('Vendedor')
                    ->required(),

                Textarea::make('notes')
                    ->label('Observaciones')
                    ->required(),
                
                FileUpload::make('attachment_uploads')
                    ->label('Archivos')
                    ->multiple()
                    ->disk('attachments')
                    ->storeFileNamesIn('file_names')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->maxFiles(10)
                    ->dehydrated(false)
                    ->afterStateHydrated(
                        function (
                            FileUpload $component,
                            ?Inflow $record
                        ) {
                            if (! $record) {
                                return;
                            }

                            $component->state(
                                $record->attachments
                                    ->pluck('path')
                                    ->toArray()
                            );
                        }
                    ),
            ]);
    }
}
