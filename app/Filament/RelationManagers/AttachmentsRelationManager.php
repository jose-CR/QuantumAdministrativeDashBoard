<?php

namespace App\Filament\RelationManagers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Adjuntos';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')

            ->columns([
                TextColumn::make('original_name')
                    ->label('Archivo')
                    ->searchable(),

                TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('formatted_size')
                    ->label('Tamaño'),

                TextColumn::make('created_at')
                    ->label('Subido')
                    ->dateTime()
                    ->sortable(),
            ])

            ->headerActions([
                Action::make('subir')
                    ->label('Agregar adjuntos')
                    ->icon('heroicon-o-paper-clip')

                    ->schema([
                        FileUpload::make('files')
                            ->label('Archivos')
                            ->multiple()
                            ->required()
                            ->disk('attachments')
                            ->storeFileNamesIn('file_names'),
                    ])

                    ->action(function (
                        array $data,
                        AttachmentService $service
                    ): void {
                        $owner = $this->getOwnerRecord();

                        foreach ($data['files'] as $path) {
                            $originalName = $data['file_names'][$path]
                                ?? basename($path);

                            $service->storeFromPath(
                                record: $owner,
                                temporaryPath: $path,
                                originalName: $originalName,
                                collection: $owner->getTable(),
                                disk: 'attachments',
                            );
                        }
                    }),
            ])

            ->recordActions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(
                        fn (Attachment $record) => $record->url,
                        shouldOpenInNewTab: true
                    ),
                    
                Action::make('reemplazar')
                    ->label('Reemplazar')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Nuevo archivo')
                            ->required()
                            ->disk('attachments')
                            ->storeFileNamesIn('file_name'),
                    ])
                    ->action(function (Attachment $record, array $data, AttachmentService $service): void {
                        $service->replaceFromPath(
                            attachment: $record,
                            temporaryPath: $data['file'],
                            originalName: $data['file_name'] ?? null,
                        );
                    }),

                    DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}