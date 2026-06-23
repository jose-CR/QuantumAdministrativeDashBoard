<?php

namespace Database\Factories;

use App\Models\ArticleUnit;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit>
 */
class CreditFactory extends Factory
{
    public function definition(): array
    {
        $initialAmount = fake()->randomFloat(
            2,
            3000,
            15000
        );

        $downPayment = fake()->randomFloat(
            2,
            500,
            2000
        );

        $financedAmount = $initialAmount - $downPayment;

        $installments = fake()->randomElement([
            12,
            18,
            24,
            36,
        ]);

        $periodicity = fake()->randomElement([
            'weekly',
            'monthly',
            'yearly',
        ]);

        $interestRate = fake()->randomFloat(
            2,
            5,
            25
        );

        $totalInterest = round(
            $financedAmount * ($interestRate / 100),
            2
        );

        $totalAmount = round(
            $financedAmount + $totalInterest,
            2
        );

        $installmentAmount = round(
            $totalAmount / $installments,
            2
        );

        $paymentDay = null;
        $paymentMonth = null;

        match ($periodicity) {

            'weekly' => $paymentDay = fake()->numberBetween(
                1,
                7
            ),

            'monthly' => $paymentDay = fake()->numberBetween(
                1,
                28
            ),

            'yearly' => [
                $paymentDay = fake()->numberBetween(
                    1,
                    28
                ),

                $paymentMonth = fake()->numberBetween(
                    1,
                    12
                ),
            ],
        };

        return [

            'client_id' => Client::factory(),

            'article_unit_id' => ArticleUnit::factory(),

            'refinanced_from_id' => null,

            'initial_amount' => $initialAmount,

            'down_payment' => $downPayment,

            'financed_amount' => $financedAmount,

            'installments' => $installments,

            'installment_amount' => $installmentAmount,

            'periodicity' => $periodicity,

            'interest_rate' => $interestRate,

            'total_interest' => $totalInterest,

            'total_amount' => $totalAmount,

            'pending_balance' => $totalAmount,

            'start_date' => fake()->dateTimeBetween(
                '-1 year',
                'now'
            ),

            'payment_day' => $paymentDay,

            'payment_month' => $paymentMonth,

            'status' => 'active',
        ];
    }
}
