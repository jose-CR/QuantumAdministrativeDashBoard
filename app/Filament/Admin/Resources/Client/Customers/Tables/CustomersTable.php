<?php

namespace App\Filament\Admin\Resources\Client\Customers\Tables;

use App\Models\Customer;
use App\Support\ElSalvadorCatalogo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),

                TextColumn::make('email'),
                TextColumn::make('phone_primary')
                    ->formatStateUsing(function ($state, $record) {
                        return $state . ' / ' . $record->phone_secondary;
                    }),
                TextColumn::make('address'),

                TextColumn::make('department')
                ->formatStateUsing(fn ($state, $record) =>
                    ElSalvadorCatalogo::locationLabel(
                        $record->department,
                        $record->municipality,
                        $record->district
                    )
                ),

                TextColumn::make('nrc'),
                
                TextColumn::make('economic_activity'),
            ])
            ->groups([
                'document_type',
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
