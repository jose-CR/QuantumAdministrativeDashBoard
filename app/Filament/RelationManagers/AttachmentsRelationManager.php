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

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ui.attachments.section');
    }

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
                    ->label(__('ui.attachments.file'))
                    ->searchable(),

                TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('formatted_size')
                    ->label(__('ui.attachments.size')),

                TextColumn::make('created_at')
                    ->label(__('ui.attachments.uploaded'))
                    ->dateTime()
                    ->sortable(),
            ])

            ->headerActions([
                Action::make('subir')
                    ->label(__('ui.attachments.add'))
                    ->icon('heroicon-o-paper-clip')

                    ->schema([
                        FileUpload::make('files')
                            ->label(__('ui.attachments.files'))
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
                    ->label(__('ui.attachments.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(
                        fn (Attachment $record) => $record->url,
                        shouldOpenInNewTab: true
                    ),
                    
                Action::make('reemplazar')
                    ->label(__('ui.attachments.replace'))
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('ui.attachments.new_file'))
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