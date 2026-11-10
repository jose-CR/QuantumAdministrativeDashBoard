<?php

namespace App\Services\Credits;

use App\Models\Credit;
use App\Models\CreditItem;
use App\Models\Customer;
use App\Models\Installment;
use App\Services\Alerts\AlertService;
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

            $saldo = round((float) $oldCredit->pending_balance, 2);

            if ($saldo <= 0) {
                throw new Exception('Este crédito no tiene saldo pendiente: no hay nada que refinanciar.');
            }

            /*
             * El refinanciamiento es un crédito nuevo por lo que se debe.
             *
             * No arrastra la prima del anterior: esa ya se pagó cuando se abrió el
             * crédito viejo, y volver a ponerla inflaba el "precio" del nuevo sin
             * que nadie la fuera a pagar.
             */
            $data = array_merge($data, [
                'refinanced_from_id' => $oldCredit->id,
                'down_payment'       => 0,
                'initial_amount'     => $saldo,
                'status'             => 'active',
                // Si el interés se pactó, se mantiene: el acuerdo sigue vigente.
                // Con 'auto' manda la cuota que se cargue en el refinanciamiento.
                'interest_type'      => $oldCredit->interest_type ?? 'auto',
                'interest_rate'      => $oldCredit->interest_rate,
            ]);

            $newCredit = $client->credits()->create(array_merge(
                $data,
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

            // Esas cuotas ya no se cobran: sus alertas pendientes se cancelan. Si no,
            // la campana sigue avisando de un crédito que no existe más.
            $cuotasQueQuedan = $oldCredit->installments()
                ->whereIn('status', ['pending', 'partial', 'late'])
                ->get();

            $cuotasQueQuedan->each(
                fn (Installment $installment) => app(AlertService::class)->cancel($installment)
            );

            // 'partial' no lo escribe el sistema, pero puede venir de datos viejos.
            $oldCredit->installments()
                ->whereIn('status', ['pending', 'partial', 'late'])
                ->update(['status' => 'refinanced']);

            return $newCredit;
        });
    }
}