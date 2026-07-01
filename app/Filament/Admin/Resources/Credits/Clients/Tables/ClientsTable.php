<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Tables;

use App\Models\Client;
use App\Services\Credits\RefinancingService;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

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

                Action::make('refinanced')
                    ->label('Refinanciar')
                    ->form([
                        Section::make('Crédito actual')
                            ->description('Información del crédito que será refinanciado.')
                            ->schema([
                                Grid::make(2)
                                    ->schema([

                                        TextInput::make('client')
                                            ->label('Cliente')
                                            ->default(fn (Client $record) => $record->full_name)
                                            ->readOnly()
                                            ->dehydrated(false),

                                        TextInput::make('current_credit')
                                            ->label('Crédito a refinanciar')
                                            ->default(function (Client $record) {

                                                $credit = $record->activeCredit;

                                                return "Crédito #{$credit->id} • {$credit->articleUnit->display_name} • ({$credit->installments} cuotas)";
                                            })
                                            ->readOnly()
                                            ->dehydrated(false),

                                        TextInput::make('pending_balance')
                                            ->label('Saldo pendiente')
                                            ->default(fn (Client $record) => number_format(
                                                $record->activeCredit->pending_balance,
                                                2
                                            ))
                                            ->prefix('$')
                                            ->readOnly()
                                            ->dehydrated(false),

                                        TextInput::make('remaining_installments')
                                            ->label('Cuotas pendientes')
                                            ->default(fn (Client $record) => $record
                                                ->activeCredit
                                                ->installments()
                                                ->where('status', 'pending')
                                                ->count())
                                            ->readOnly()
                                            ->dehydrated(false),

                                    ]),
                            ]),

                        Section::make('Nuevo crédito')
                            ->description('Ingrese la información del nuevo crédito.')
                            ->schema([

                                Grid::make(2)
                                    ->schema([

                                        TextInput::make('initial_amount')
                                            ->label('Monto a financiar')
                                            ->prefix('$')
                                            ->numeric()
                                            ->helperText(
                                                'Puede ser diferente al saldo pendiente.'
                                            )
                                            ->required(),

                                        TextInput::make('down_payment')
                                            ->label('Prima')
                                            ->prefix('$')
                                            ->numeric(),

                                        TextInput::make('installments')
                                            ->label('Cantidad de cuotas')
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('installment_amount')
                                            ->label('Valor de la cuota')
                                            ->prefix('$')
                                            ->numeric()
                                            ->required(),

                                        Select::make('periodicity')
                                            ->label('Periodicidad')
                                            ->options([
                                                'weekly' => 'Semanal',
                                                'biweekly' => 'Quincenal',
                                                'monthly' => 'Mensual',
                                            ])
                                            ->required(),

                                        DatePicker::make('start_date')
                                            ->label('Fecha de inicio')
                                            ->required(),

                                        TextInput::make('payment_day')
                                            ->label('Día de pago')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(31)
                                            ->required(),

                                    ]),
                            ]),
                    ])
                    ->action(function (array $data, Client $record) {

                        app(RefinancingService::class)->execute(
                            client: $record,
                            oldCredit: $record->activeCredit,
                            data: $data,
                        );

                    })
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
