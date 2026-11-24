<?php

namespace App\Filament\Admin\Resources\Settings\DocumentTypes\Tables;

use App\Models\DocumentType;
use App\Support\DocumentTypes;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort')
                    ->label(__('ui.document_types.sort'))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label(__('ui.document_types.name'))
                    ->searchable()
                    ->sortable(),

                // Es lo que queda guardado en cada entrada: va a la vista.
                TextColumn::make('code')
                    ->label(__('ui.document_types.code'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('scope')
                    ->label(__('ui.document_types.scope.label'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('ui.document_types.scope.' . $state)),

                TextColumn::make('usos')
                    ->label(__('ui.document_types.uses'))
                    ->badge()
                    ->state(fn (DocumentType $record): int => $record->usageCount())
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),

                IconColumn::make('active')
                    ->label(__('ui.document_types.active'))
                    ->boolean(),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),

                // Un tipo en uso no se borra: las entradas quedarían con un código que
                // el catálogo ya no sabe mostrar.
                DeleteAction::make()
                    ->modalDescription(fn (DocumentType $record): ?string => $record->usageCount() > 0
                        ? __('ui.document_types.delete_blocked', ['count' => $record->usageCount()])
                        : null)
                    ->before(function (DeleteAction $action, DocumentType $record): void {
                        if ($record->usageCount() > 0) {
                            $action->cancel();
                        }
                    }),
            ]);
    }
}
