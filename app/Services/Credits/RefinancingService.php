<?php

namespace App\Services\Credits;

use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\Customer;
use App\Services\InstallmentGeneratorService;
use App\Services\LoanCalculatorService;
use Exception;
use Illuminate\Support\Facades\DB;

class RefinancingService
{
    public function execute(Customer $client, Credit $oldCredit, array $data): Credit
    {
        return DB::transaction(function () use ($client, $oldCredit, $data) {
            $oldCredit = Credit::lockForUpdate()->findOrFail($oldCredit->id);

            if ($oldCredit->status !== 'active') {
                throw new Exception('Solo se pueden refinanciar créditos activos.');
            }

            $data = array_merge($data, [
                'down_payment'   => $oldCredit->down_payment,
                'initial_amount' => round((float) $oldCredit->pending_balance + (float) $oldCredit->down_payment, 2),
                // Si el interés se pactó, se mantiene: el acuerdo sigue vigente.
                // Con 'auto' manda la cuota que se cargue en el refinanciamiento.
                'interest_type' => $oldCredit->interest_type ?? 'auto',
                'interest_rate' => $oldCredit->interest_rate,
            ]);

            $newCredit = $client->credits()->create(array_merge(
                $data,
                [
                    'status'             => 'active',
                    'refinanced_from_id' => $oldCredit->id,
                ],
                app(LoanCalculatorService::class)->calculate($data),
            ));

            // Los bienes pasan al crédito nuevo sin volver a mover stock: la
            // mercancía ya salió con el crédito original y replicar el ítem
            // dispararía el descuento por segunda vez.
            CreditItem::withoutEvents(function () use ($oldCredit, $newCredit): void {
                $oldCredit->items->each(
                    fn (CreditItem $item) => $newCredit->items()->save($item->replicate())
                );
            });

            app(InstallmentGeneratorService::class)->generate($newCredit);

            $newCredit->update([
                'pending_balance' => round((float) $newCredit->installments()->sum('remaining_balance'), 2),
            ]);

            $oldCredit->update([
                'status'          => 'refinanced',
                'pending_balance' => 0,
            ]);

            // 'partial' no lo escribe el sistema, pero puede venir de datos viejos.
            $oldCredit->installments()
                ->whereIn('status', ['pending', 'partial', 'late'])
                ->update(['status' => 'refinanced']);

            return $newCredit;
        });
    }
}