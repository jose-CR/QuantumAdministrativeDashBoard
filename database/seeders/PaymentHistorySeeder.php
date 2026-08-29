<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Credit;
use App\Models\Installment;
use App\Services\RegisterPaymentService;
use Illuminate\Database\Seeder;

class PaymentHistorySeeder extends Seeder
{
    public function run(): void
    {
        Installment::withoutEvents(function () {

            $registerPayment = app(RegisterPaymentService::class);

            Credit::query()
                ->where('status', 'active')
                ->each(function (Credit $credit) use ($registerPayment) {

                    $scenario = fake()->randomElement([
                        'none',
                        'partial',
                        'full',
                    ]);

                    if ($scenario === 'none') {
                        return;
                    }

                    $amount = match ($scenario) {
                        'partial' => fake()->randomFloat(
                            2,
                            1,
                            $credit->pending_balance - 1
                        ),

                        'full' => $credit->pending_balance,
                    };

                    $paymentMethod = fake()->randomElement([
                        'cash',
                        'bank_transfer',
                        'card',
                    ]);

                    $bankId = $paymentMethod === 'cash'
                        ? null
                        : Bank::query()
                            ->inRandomOrder()
                            ->value('id');

                    $registerPayment->execute(
                        credit: $credit,
                        amount: $amount,
                        paymentMethod: $paymentMethod,
                        receiptNumber: fake()->numerify('REC-#####'),
                        notes: fake()->sentence(),
                        mode: 'auto',
                        installmentId: null,
                        bankId: $bankId,
                        paymentDate: now()->toDateString(),
                    );
                });
        });
    }
}