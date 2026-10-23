<?php

namespace App\Filament\Admin\Actions;

use App\Models\Bank;
use App\Models\Credit;
use App\Models\Customer as ModelsCustomer;
use App\Models\Installment;
use App\Services\RegisterPaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Exceptions\Halt;
use InvalidArgumentException;

class PayInstallmentAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'payInstallment');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('resources.credits.clients.pay_installment.installment'))
            ->form([
                Section::make(__('resources.credits.clients.pay_installment.installment'))
                    ->schema([
                        Select::make('installment_id')
                            ->label(__('resources.credits.clients.pay_installment.installment_to_pay'))
                            ->options(function ($record) {
                                $credit = $this->resolveCredit($record);

                                if (! $credit) {
                                    return [];
                                }

                                return $credit->installments()
                                    ->whereIn('status', Installment::UNPAID_STATUSES)
                                    ->orderBy('number')
                                    ->get()
                                    ->mapWithKeys(fn ($installment) => [
                                        $installment->id => __(
                                            'resources.credits.clients.pay_installment.installment_format',
                                            [
                                                'number' => $installment->number,
                                                'balance' => number_format(
                                                    $installment->remaining_balance,
                                                    2
                                                ),
                                            ]
                                        ),
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->searchable(),

                        TextInput::make('amount')
                            ->label(__('resources.credits.clients.pay_installment.amount'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('resources.credits.clients.pay_installment.payment'))
                    ->schema([
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
                    ->columns(2),
            ])
            ->action(function (array $data, $record) {
                $credit = $this->resolveCredit($record);

                if (! $credit) {
                    return;
                }

                try {
                    app(RegisterPaymentService::class)->execute(
                        credit: $credit,
                        amount: (float) $data['amount'],
                        paymentMethod: $data['payment_method'],
                        receiptNumber: $data['receipt_number'],
                        notes: null,
                        mode: 'single',
                        installmentId: (int) $data['installment_id'],
                        bankId: $data['bank_id'] ?? null,
                        paymentDate: $data['payment_date'],
                    );
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    throw new Halt();
                }

                $record->refresh();
            });
    }

    protected function resolveCredit(ModelsCustomer|Credit $record): ?Credit
    {
        return match (true) {
            $record instanceof ModelsCustomer => $record->activeCredit,
            $record instanceof Credit => $record,
        };
    }
}