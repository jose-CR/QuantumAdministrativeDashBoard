<?php

namespace App\Filament\Admin\Resources\Client\Customers\RelationManagers;

use App\Filament\Admin\Actions\RefinanceCreditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'credits';

    protected static ?string $title = 'Créditos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_date')->label('Inicio')->date('d/m/Y')->sortable(),
                TextColumn::make('financed_amount')->label('Financiado')->money('USD'),
                TextColumn::make('pending_balance')->label('Saldo pendiente')->money('USD'),
                TextColumn::make('installments')->label('Cuotas'),
                TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordActions([
                RefinanceCreditAction::make(),
            ]);
    }
}
