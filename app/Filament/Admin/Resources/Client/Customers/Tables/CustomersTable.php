<?php

namespace App\Filament\Admin\Resources\Client\Customers\Tables;

use App\Filament\Admin\Actions\PayInstallmentAction;
use App\Models\Customer;
use App\Support\ActividadesEconomicas;
use App\Support\ElSalvadorCatalogo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('full_name')
                    ->label(__('resources.clients.fields.full_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_type')
                    ->label(__('resources.clients.fields.document_type.document_type'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('document_number')
                    ->label(__('resources.clients.fields.document_number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                SpatieTagsColumn::make('tags')
                    ->type('customer')
                    ->label(__('resources.clients.fields.tags.tags')),

                TextColumn::make('email')
                    ->label(__('resources.users.email'))
                    ->searchable(),

                TextColumn::make('phone_primary')
                    ->label(__('resources.clients.fields.phones'))
                    ->formatStateUsing(
                        fn ($state, $record) => collect([
                            $state,
                            $record->phone_secondary,
                        ])
                            ->filter()
                            ->implode(' / ')
                    ),

                TextColumn::make('phone_secondary')
                    ->label(__('resources.clients.fields.phone_secondary'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nrc')
                    ->label(__('resources.clients.fields.enterprise.nrc'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('economic_activity')
                    ->label(__('resources.clients.fields.enterprise.economic_activity'))
                    ->formatStateUsing(
                        fn ($state) => ActividadesEconomicas::activityName($state)
                    )
                    ->searchable(),

                TextColumn::make('department')
                    ->label(__('resources.clients.fields.enterprise.location'))
                    ->formatStateUsing(
                        fn ($state, $record) => ElSalvadorCatalogo::locationLabel(
                            $record->department,
                            $record->municipality,
                            $record->district
                        )
                    )
                    ->searchable(),

                TextColumn::make('municipality')
                    ->label(__('resources.clients.fields.enterprise.municipality'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('district')
                    ->label(__('resources.clients.fields.enterprise.distric'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label(__('resources.clients.fields.address'))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('resources.clients.fields.enterprise.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('resources.clients.fields.enterprise.updated_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('document_type')
                    ->label(__('resources.clients.fields.document_type')),
            ])
            ->defaultGroup('document_type')
            ->filters([
                SelectFilter::make('tag')
                    ->label(__('resources.clients.fields.tags.tags'))
                    ->multiple()
                    ->options(fn () => Tag::getWithType('customer')->pluck('name', 'name'))
                    ->query(fn (Builder $query, array $data): Builder => filled($data['values'] ?? null)
                        ? $query->withAnyTags($data['values'], 'customer')
                        : $query),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    PayInstallmentAction::make(),
                    Action::make('createCredit')
                        ->url(fn (Customer $record): string => route(
                            'filament.admin.resources.creditos.loans.create',
                            [
                                'customer' => $record->id,
                            ],
                        )),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
