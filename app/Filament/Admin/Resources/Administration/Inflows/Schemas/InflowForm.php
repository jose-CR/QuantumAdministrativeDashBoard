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
                    ->label(__('resources.flow.inflow.cod_invoice'))
                    ->required(),

                DatePicker::make('date')
                    ->label(__('resources.flow.inflow.date'))
                    ->required(),

                Textarea::make('description')
                    ->label(__('resources.flow.inflow.description')),

                Select::make('customer_id')
                    ->label(__('resources.flow.inflow.client'))
                    ->relationship('customer', 'full_name')
                    ->searchable()
                    ->required(),

                TextInput::make('amount')
                    ->label(__('resources.flow.inflow.amount'))
                    ->numeric()
                    ->required(),

                Select::make('payment_method')
                    ->label(__('resources.flow.inflow.payment_method'))
                    ->options([
                        'cash' => __('resources.credits.clients.pay_installment.payment_methods.cash'),
                        'card' => __('resources.credits.clients.pay_installment.payment_methods.card'),
                        'bank_transfer' => __('resources.credits.clients.pay_installment.payment_methods.bank_transfer'),
                        'transfer' => __('resources.credits.clients.pay_installment.payment_methods.transfer'),
                    ])
                    ->required()
                    ->live(),

                TextInput::make('transfer_number')
                    ->label(__('resources.flow.inflow.transfer_number'))
                    ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                Select::make('bank_id')
                    ->label(__('resources.clients.fields.bank'))
                    ->relationship('bank', 'name')
                    ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                DatePicker::make('transfer_date')
                    ->label(__('resources.flow.inflow.transfer_date'))
                    ->visible(fn (Get $get) => $get('payment_method') === 'transfer'),

                Select::make('payment_status')
                    ->label(__('resources.flow.inflow.payment_status'))
                    ->options([
                        'pending' => __('resources.clients.statuses.pending'),
                        'partial' => __('resources.clients.statuses.partial'),
                        'paid' => __('resources.clients.statuses.paid'),
                    ])
                    ->required(),

                TextInput::make('salesperson')
                    ->label(__('resources.flow.inflow.salesperson'))
                    ->required(),

                Textarea::make('notes')
                    ->label(__('resources.flow.inflow.notes')),
                
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
