<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Schemas;

use App\Models\Client;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cliente')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nombre completo'),

                        TextEntry::make('phone_primary') 
                                ->label('Telefonos') 
                                ->formatStateUsing(function ($state, $record){ 
                                    return $state . '/' . $record->phone_secondary; 
                                }),
                    ])
                    ->columns(1),

                Section::make('Artículo financiado')
                    ->schema([
                        TextEntry::make('latestCredit.articleUnit.display_name')
                            ->label('Vehículo'),
                    ]),

                Section::make('Resumen del crédito')
                    ->schema([
                        TextEntry::make('latestCredit.start_date')
                            ->label('Fecha de inicio')
                            ->date(),

                        TextEntry::make('latestCredit.total_amount')
                            ->label('Total a pagar')
                            ->money('USD')
                            ->color('success'),

                        TextEntry::make('latestCredit.pending_balance')
                            ->label('Saldo pendiente')
                            ->money('USD')
                            ->color('danger'),

                        TextEntry::make('latestCredit.installment_amount')
                            ->label('Monto por cuota')
                            ->money('USD'),
                    ])
                    ->columns(2),

                Section::make('Estado del crédito')
                    ->schema([
                        TextEntry::make('remaining_installments')
                            ->label('Cuotas restantes')
                            ->state(function (Client $record): string {
                                $credit = $record->latestCredit;

                                if (! $credit) {
                                    return 'Sin créditos registrados';
                                }

                                $remaining = $credit->installments()
                                    ->where('status', '!=', 'paid')
                                    ->count();

                                return "{$remaining} de {$credit->installments}";
                            }),

                        TextEntry::make('credit_progress')
                            ->label('Avance')
                            ->state(function (Client $record): string {
                                $credit = $record->latestCredit;

                                if (! $credit) {
                                    return '0%';
                                }

                                $paid = $credit->installments()
                                    ->where('status', 'paid')
                                    ->count();

                                $progress = $credit->installments > 0
                                    ? ($paid / $credit->installments) * 100
                                    : 0;

                                return number_format($progress, 0) . '%';
                            }),

                        TextEntry::make('latestCredit.status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'active' => 'success',
                                'refinanced' => 'warning',
                                'closed' => 'gray',
                                default => 'primary',
                            }),
                    ])
                    ->columns(3),

                Section::make('Últimos pagos realizados')
                    ->description('Historial reciente de pagos del crédito.')
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->state(function (Client $record) {
                                return $record->latestCredit?->paymentHistories()
                                    ->latest('payment_date')
                                    ->take(5)
                                    ->get();
                            })
                            ->label('Pagos recientes')
                            ->contained(false)
                            ->schema([
                                TextEntry::make('payment_date')
                                    ->label('Fecha')
                                    ->date(),

                                TextEntry::make('amount')
                                    ->label('Monto')
                                    ->money('USD')
                                    ->color('success'),

                                TextEntry::make('payment_method')
                                    ->label('Método'),

                                TextEntry::make('receipt_number')
                                    ->label('Recibo'),
                            ])
                            ->columns(4),
                    ]),
            ])
        ;
    }

}