<?php

namespace Tests\Unit\Credits;

use App\Models\Credit;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Base de las pruebas de créditos: arma un crédito con sus cuotas sin pasar por
 * el panel. Corre en transacción, así no deja filas en la base.
 */
abstract class CreditTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * @param  array<int, array{amount: float, status?: string, due?: string}>  $installments
     */
    protected function makeCredit(array $installments = [['amount' => 100]], ?Customer $customer = null): Credit
    {
        $total = round(array_sum(array_column($installments, 'amount')), 2);

        $credit = Credit::query()->create([
            'customer_id' => ($customer ?? Customer::factory()->create())->id,
            'initial_amount' => $total,
            'financed_amount' => $total,
            'installments' => count($installments),
            'installment_amount' => $installments[0]['amount'],
            'total_amount' => $total,
            'pending_balance' => $total,
            'start_date' => today(),
            'payment_day' => 1,
            'status' => 'active',
        ]);

        foreach ($installments as $index => $row) {
            $credit->installments()->create([
                'number' => $index + 1,
                'amount' => $row['amount'],
                'remaining_balance' => $row['amount'],
                'due_date' => $row['due'] ?? today()->addMonth(),
                'status' => $row['status'] ?? 'pending',
            ]);
        }

        return $credit;
    }
}
