<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Tables;

use App\Models\Alert;
use App\Models\Bank;
use App\Models\Client;
use App\Models\Installment;
use App\Models\User;
use App\Services\Alerts\AlertService;
use App\Services\Credits\RefinancingService;
use App\Services\RegisterPaymentService;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('resources.credits.clients.client'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('identity_document')
                    ->label(__('resources.credits.clients.identity_document')),

                TextColumn::make('phone_primary')
                    ->label(__('resources.credits.clients.phone_primary'))
                    ->formatStateUsing(function ($state, $record) {
                        return $state . ' / ' . $record->phone_secondary;
                    }),
                TextColumn::make('address')
                    ->label(__('resources.credits.clients.address')),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('payInstallment')
                    ->label(__('resources.credits.clients.pay_installment.installment'))
                    ->form([

                        Select::make('installment_id')
                            ->label(__('resources.credits.clients.pay_installment.installment_to_pay'))
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
                                        $installment->id => __('resources.credits.clients.pay_installment.installment_format', [
                                            'number' => $installment->number,
                                            'balance' => number_format($installment->remaining_balance, 2),
                                        ]),
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),

                        TextInput::make('amount')
                            ->label(__('resources.credits.clients.pay_installment.amount'))
                            ->numeric()
                            ->required(),

                        Select::make('payment_method')
                            ->label(__('resources.credits.clients.pay_installment.payment_method'))
                            ->options([
                                'cash' => __('resources.credits.clients.pay_installment.payment_methods.cash'),
                                'card' => __('resources.credits.clients.pay_installment.payment_methods.card'),
                                'bank_transfer' => __('resources.credits.clients.pay_installment.payment_methods.bank_transfer'),
                            ])
                            ->live()
                            ->required(),

                        Select::make('bank_id')
                            ->label(__('resources.credits.clients.pay_installment.bank'))
                            ->options(
                                Bank::pluck('name', 'id')
                            )
                            ->visible(
                                fn (Get $get) =>
                                    $get('payment_method') !== 'cash'
                            )
                            ->required(
                                fn (Get $get) =>
                                    $get('payment_method') !== 'cash'
                            ),

                        TextInput::make('receipt_number')
                            ->label(__('resources.credits.clients.pay_installment.receipt_number'))
                            ->required(),

                        DatePicker::make('payment_date')
                            ->label(__('resources.credits.clients.pay_installment.payment_date'))
                            ->default(now())
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
                            bankId: $data['bank_id'] ?? null,
                            paymentDate: $data['payment_date'],
                        );
                    }),

                Action::make('refinanced')
                    ->visible(fn (Client $record) =>
                        $record->latestCredit !== null
                        && $record->latestCredit->status === 'active'
                    )
                    ->label(__('resources.credits.clients.refinanced'))
                    ->form([
                        Section::make(__('resources.credits.clients.refinance.current_credit_section'))
                            ->description(__('resources.credits.clients.refinance.current_credit_description'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('client')
                                            ->label(__('resources.credits.clients.client'))
                                            ->default(fn (Client $record) => $record->full_name)
                                            ->readOnly()
                                            ->dehydrated(false),

                                        TextInput::make('current_credit')
                                            ->label(__('resources.credits.clients.refinance.current_credit'))
                                            ->default(function (Client $record) {

                                                $credit = $record->activeCredit;

                                                return __('resources.credits.clients.refinance.credit_format', [
                                                    'credit' => $credit->id,
                                                    'article' => $credit->articleUnit->display_name,
                                                    'installments' => $credit->installments,
                                                ]);
                                            })
                                            ->readOnly()
                                            ->dehydrated(false),

                                        TextInput::make('pending_balance')
                                            ->label(__('resources.credits.clients.refinance.pending_balance'))
                                            ->default(fn (Client $record) => number_format(
                                                $record->activeCredit->pending_balance,
                                                2
                                            ))
                                            ->prefix('$')
                                            ->readOnly()
                                            ->dehydrated(false),

                                        TextInput::make('remaining_installments')
                                            ->label(__('resources.credits.clients.refinance.remaining_installments'))
                                            ->default(fn (Client $record) => $record
                                                ->activeCredit
                                                ->installments()
                                                ->where('status', 'pending')
                                                ->count())
                                            ->readOnly()
                                            ->dehydrated(false),

                                    ]),
                            ]),

                        Section::make(__('resources.credits.clients.refinance.new_credit_section'))
                            ->description(__('resources.credits.clients.refinance.new_credit_description'))
                            ->schema([

                                Grid::make(2)
                                    ->schema([

                                        TextInput::make('initial_amount')
                                            ->label(__('resources.credits.clients.refinance.initial_amount'))
                                            ->prefix('$')
                                            ->numeric()
                                            ->helperText(
                                                __('resources.credits.clients.refinance.helper_initial_amount')
                                            )
                                            ->required(),

                                        Select::make('assigned_user_id')
                                            ->label(__('resources.alert.assigned_user'))
                                            ->options(
                                                User::query()
                                                    ->pluck('name', 'id')
                                            )
                                            ->searchable()
                                            ->required(),

                                        TextInput::make('installments')
                                            ->label(__('resources.credits.clients.refinance.installments'))
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('installment_amount')
                                            ->label(__('resources.credits.clients.refinance.installment_amount'))
                                            ->prefix('$')
                                            ->numeric()
                                            ->required(),

                                        Select::make('periodicity')
                                            ->label('Periodicidad')
                                            ->options([
                                                'weekly' => __('resources.credits.clients.refinance.weekly'),
                                                'biweekly' => __('resources.credits.clients.refinance.biweekly'),
                                                'monthly' => __('resources.credits.clients.refinance.monthly'),
                                            ])
                                            ->required(),

                                        DatePicker::make('start_date')
                                            ->label(__('resources.credits.clients.refinance.start_date'))
                                            ->required(),

                                        TextInput::make('payment_day')
                                            ->label(__('resources.credits.clients.refinance.payment_day'))
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

                    }),
                Action::make('Alert')
                    ->label(__('resources.alert.label'))
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('assigned_user_id')
                                    ->label(__('resources.alert.assigned_user'))
                                    ->options(
                                        User::query()
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->required(),

                                Select::make('installment_id')
                                    ->label(__('resources.alert.installment'))
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->options(function ($record): array {

                                        $credit = $record->activeCredit;

                                        if (! $credit) {
                                            return [];
                                        }

                                        return $credit->installments()
                                            ->where('status', 'pending')
                                            ->orderBy('number')
                                            ->get()
                                            ->mapWithKeys(function (Installment $installment) {

                                                return [
                                                    $installment->id => sprintf(
                                                        __('resources.alert.installment_format'),
                                                        $installment->number,
                                                        $installment->due_date->format('d/m/Y'),
                                                        number_format($installment->remaining_balance, 2),
                                                    ),
                                                ];
                                            })
                                            ->toArray();
                                    }),

                                Select::make('type')
                                    ->label(__('resources.alert.type'))
                                    ->options(Alert::getManualTypes())
                                    ->searchable()
                                    ->native(false)
                                    ->required(),

                                TextInput::make('title')
                                    ->label(__('resources.alert.title'))
                                    ->placeholder(__('resources.alert.title_placeholder'))
                                    ->maxLength(255)
                                    ->required(),

                                DateTimePicker::make('alert_at')
                                    ->label(__('resources.alert.alert_at'))
                                    ->seconds(false)
                                    ->native(false)
                                    ->required(),

                                RichEditor::make('message')
                                    ->label(__('resources.alert.message'))
                                    ->placeholder(__('resources.alert.message_placeholder'))
                                    ->columnSpanFull()
                                    ->required(),
                            ])
                    ])
                    ->action(function (array $data, Client $record) {
                        $credit = $record->activeCredit;

                        $assignedUser = User::findOrFail($data['assigned_user_id']);

                        if (! $credit) {
                            Notification::make()
                                ->title('El cliente no tiene un crédito activo.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $installment = $data['installment_id']
                            ? $credit->installments()->findOrFail($data['installment_id'])
                            : null;

                        app(AlertService::class)->create(
                            client: $record,
                            credit: $credit,
                            creator: Filament::auth()->user(),
                            assignedUser: $assignedUser,
                            installment: $installment,
                            data: $data,
                        );

                        Notification::make()
                            ->title(__('notifications.alert.create.title'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('notifications.alert.deletes.title'))
                                ->body(__('notifications.alert.deletes.body'))
                        ),
                ]),
            ]);
    }
}
