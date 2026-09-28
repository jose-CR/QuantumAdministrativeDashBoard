<?php

namespace App\Filament\Admin\Resources\Administration\Outflows\Schemas;

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
                    ->label('Fecha')
                    ->required(),
                
                TextInput::make('company')
                    ->label('Empresa')
                    ->required(),

                TextInput::make('invoice_code')
                    ->label('Cod. Factura')
                    ->required(),

                RichEditor::make('description'),

                TextInput::make('quantity')
                    ->label('Cant')
                    ->numeric()
                    ->required(),

                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->required(),

                TextInput::make('area')
                    ->label('Área')
                    ->required(),

                FileUpload::make('attachment_uploads')
                    ->label('Adjuntos')
                    ->multiple()
                    ->disk('public')
                    ->directory('outflows/attachments')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->maxFiles(10)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (FileUpload $component, ?Outflow $record) {
                        if ($record) {
                            $component->state(
                                $record->attachments->pluck('path')->toArray()
                            );
                        }
                    }),

            ]);
    }
}
