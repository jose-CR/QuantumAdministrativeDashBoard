<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Schemas;

use App\Models\Inflow;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos generales')
                    ->description('Información principal del ingreso')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label(__('resources.flow.inflow.cod_invoice'))
                            ->required(),

                        Select::make('customer_id')
                            ->label(__('resources.flow.inflow.client'))
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('date')
                            ->label(__('resources.flow.inflow.date'))
                            ->required(),

                        TextInput::make('salesperson')
                            ->label(__('resources.flow.inflow.salesperson'))
                            ->required(),

                        Textarea::make('description')
                            ->label(__('resources.flow.inflow.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Pago')
                    ->description('Detalles del cobro y su estado')
                    ->schema([
                        TextInput::make('amount')
                            ->label(__('resources.flow.inflow.amount'))
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->live()
                            // Por defecto se cobra entero: si todavía no se tocó lo
                            // cobrado, se copia el monto.
                            ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                if ((float) ($get('amount_paid') ?? 0) <= 0) {
                                    $set('amount_paid', $state);
                                }
                            }),

                        /*
                         * Lo cobrado manda: el estado (pendiente, parcial o pagado)
                         * se calcula solo al guardar.
                         */
                        TextInput::make('amount_paid')
                            ->label(__('resources.flow.inflow.amount_paid'))
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('0 = pendiente · igual al monto = pagado · en medio = parcial')
                            ->required(),

                        Select::make('payment_method')
                            ->label(__('resources.flow.inflow.payment_method'))
                            ->options([
                                'cash' => __('resources.credits.clients.pay_installment.payment_methods.cash'),
                                'card' => __('resources.credits.clients.pay_installment.payment_methods.card'),
                                'transfer' => __('resources.credits.clients.pay_installment.payment_methods.transfer'),
                                'cheque' => __('resources.credits.clients.pay_installment.payment_methods.cheque'),
                                'quedan' => __('resources.credits.clients.pay_installment.payment_methods.quedan'),
                            ])
                            ->required()
                            ->live(),

                        TextInput::make('transfer_number')
                            ->label(__('resources.flow.inflow.transfer_number'))
                            ->visible(fn (Get $get): bool => $get('payment_method') !== 'cash'),

                        Select::make('bank_id')
                            ->label(__('resources.clients.fields.bank'))
                            ->relationship('bank', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('payment_method') !== 'cash'),

                        DatePicker::make('transfer_date')
                            ->label(__('resources.flow.inflow.transfer_date'))
                            ->visible(fn (Get $get): bool => $get('payment_method') !== 'cash'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Documentación')
                    ->description('Observaciones y archivos relacionados con el ingreso')
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('resources.flow.inflow.notes'))
                            ->columnSpanFull(),

                        FileUpload::make('attachment_uploads')
                            ->label(__('resources.flow.attachment'))
                            ->multiple()
                            ->disk('attachments')
                            ->storeFileNamesIn('file_names')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->maxFiles(10)
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->afterStateHydrated(
                                function (
                                    FileUpload $component,
                                    ?Inflow $record
                                ): void {
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
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}