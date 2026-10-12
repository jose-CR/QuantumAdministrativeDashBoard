<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Schemas;

use App\Models\Attachment;
use App\Models\Outflow;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OutflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('invoice_date')
                    ->label(__('resources.flow.outflow.date'))
                    ->required(),
                
                TextInput::make('company')
                    ->label(__('resources.flow.outflow.company'))
                    ->required(),

                TextInput::make('invoice_code')
                    ->label(__('resources.flow.outflow.cod_invoice'))
                    ->required(),

                RichEditor::make('description')
                    ->label(__('resources.flow.outflow.description')),

                TextInput::make('quantity')
                    ->label(__('resources.flow.outflow.quantity'))
                    ->numeric()
                    ->required(),

                TextInput::make('amount')
                    ->label(__('resources.flow.outflow.amount'))
                    ->numeric()
                    ->required(),

                TextInput::make('area')
                    ->label(__('resources.flow.outflow.area'))
                    ->required(),

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
                            ?Outflow $record
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
