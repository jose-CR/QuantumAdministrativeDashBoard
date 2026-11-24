<?php

namespace App\Filament\Admin\Resources\Settings\DocumentTypes\Schemas;

use App\Models\DocumentType;
use App\Support\DocumentTypes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('ui.document_types.code'))
                    ->required()
                    ->maxLength(20)
                    ->alphaDash()
                    ->helperText(__('ui.document_types.code_help'))
                    // El código se guarda en cada entrada: cambiarlo deja las viejas
                    // apuntando a un tipo que ya no existe.
                    ->disabled(fn (?DocumentType $record): bool => $record !== null && $record->usageCount() > 0)
                    ->unique(ignoreRecord: true),

                TextInput::make('name')
                    ->label(__('ui.document_types.name'))
                    ->required()
                    ->maxLength(255),

                Select::make('scope')
                    ->label(__('ui.document_types.scope.label'))
                    ->options([
                        DocumentType::SCOPE_INFLOW => __('ui.document_types.scope.inflow'),
                        DocumentType::SCOPE_OUTFLOW => __('ui.document_types.scope.outflow'),
                        DocumentType::SCOPE_BOTH => __('ui.document_types.scope.both'),
                    ])
                    ->default(DocumentType::SCOPE_BOTH)
                    ->required(),

                TextInput::make('sort')
                    ->label(__('ui.document_types.sort'))
                    ->numeric()
                    ->default(fn (): int => (int) DocumentType::query()->max('sort') + 1)
                    ->helperText(__('ui.document_types.sort_help')),

                Toggle::make('active')
                    ->label(__('ui.document_types.active'))
                    ->default(true)
                    ->helperText(__('ui.document_types.active_help')),
            ]);
    }
}
