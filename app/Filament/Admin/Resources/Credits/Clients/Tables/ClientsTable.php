<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Tables;

use App\Models\Credit;
use App\Models\Installment;
use App\Services\RegisterPaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('Apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identity_document')
                    ->label('DUI'),
                TextColumn::make('phone_primary')
                    ->label('Telefonos')
                    ->formatStateUsing(function ($state, $record) {
                        return $state . ' / ' . $record->phone_secondary;
                    }),
                TextColumn::make('address')
                    ->label('direccion'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                Action::make('payInstallment')
                        ->label('Pagar Cuota')
                        ->form([

                            Select::make('installment_id')
                                ->label('Cuota')
                                ->options(function ($record) {

                                    $credit = $record->activeCredit;

                                    if (! $credit) {
                                        return [];
                                    }

                                    return $credit->installments()
                                        ->where('status', 'pending')
                                        ->orderBy('number')
                                        ->get()
                                        ->mapWithKeys(fn ($installment) => [
                                            $installment->id =>
                                                "Cuota #{$installment->number} - Saldo: $" .
                                                number_format(
                                                    $installment->remaining_balance,
                                                    2
                                                ),
                                        ])
                                        ->toArray();
                                })
                                ->required()
                                ->searchable(),

                            TextInput::make('amount')
                                ->label('Monto a pagar')
                                ->numeric()
                                ->required(),

                            Select::make('payment_method')
                                ->label('Método de pago')
                                ->options([
                                    'cash' => 'Efectivo',
                                    'card' => 'Tarjeta',
                                    'bank_transfer' => 'Transferencia bancaria',
                                ])
                                ->required(),

                            TextInput::make('receipt_number')
                                ->label('N° de factura')
                                ->required(),

                        ])
                        ->action(function (array $data, $record) {

                            $credit = $record->activeCredit;

                            if (! $credit) {
                                return;
                            }

                            app(RegisterPaymentService::class)->execute(
                                credit: $credit,
                                amount: $data['amount'],
                                paymentMethod: $data['payment_method'],
                                receiptNumber: $data['receipt_number'],
                                notes: null,
                                mode: 'single',
                                installmentId: $data['installment_id'],
                            );
                        }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Clientes eliminados')
                                ->body('Los Clientes seleccionados fueron eliminados correctamente.')
                        ),
                ]),
            ]);
    }
}
