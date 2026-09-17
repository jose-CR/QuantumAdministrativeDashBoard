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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

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
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_type')
                    ->label('Tipo de documento')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('document_number')
                    ->label('Número de documento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable(),

                TextColumn::make('phone_primary')
                    ->label('Teléfonos')
                    ->formatStateUsing(
                        fn ($state, $record) => collect([
                            $state,
                            $record->phone_secondary,
                        ])
                            ->filter()
                            ->implode(' / ')
                    ),

                TextColumn::make('phone_secondary')
                    ->label('Teléfono secundario')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nrc')
                    ->label('NRC')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('economic_activity')
                    ->label('Actividad económica')
                    ->formatStateUsing(
                        fn ($state) => ActividadesEconomicas::activityName($state)
                    )
                    ->searchable(),

                TextColumn::make('department')
                    ->label('Ubicación')
                    ->formatStateUsing(
                        fn ($state, $record) => ElSalvadorCatalogo::locationLabel(
                            $record->department,
                            $record->municipality,
                            $record->district
                        )
                    )
                    ->searchable(),

                TextColumn::make('municipality')
                    ->label('Municipio')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('district')
                    ->label('Distrito')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('document_type')
                    ->label('Tipo de documento'),
            ])
            ->defaultGroup('document_type')
            ->filters([
                //
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
