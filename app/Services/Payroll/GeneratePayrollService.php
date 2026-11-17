<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Support\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Arma las boletas de una quincena con los datos del empleado.
 *
 * No pisa lo que ya existe (un empleado cobra una vez por quincena) y no toca un
 * período pagado: si hubo un error, se corrige la boleta o se ajusta en la
 * siguiente quincena.
 */
class GeneratePayrollService
{
    /**
     * @return array{created: int, skipped: int, without_salary: array<int, string>}
     */
    public function generate(PayrollPeriod $period): array
    {
        if ($period->isPaid()) {
            return ['created' => 0, 'skipped' => 0, 'without_salary' => []];
        }

        $creadas = 0;
        $saltadas = 0;
        $sinSalario = [];

        DB::transaction(function () use ($period, &$creadas, &$saltadas, &$sinSalario): void {
            $yaTienen = $period->payslips()->pluck('employee_id')->all();

            Employee::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->each(function (Employee $empleado) use ($period, $yaTienen, &$creadas, &$saltadas, &$sinSalario): void {
                    if (in_array($empleado->id, $yaTienen, true)) {
                        $saltadas++;

                        return;
                    }

                    $salario = round((float) $empleado->salary_amount, 2);

                    if ($salario <= 0) {
                        $sinSalario[] = $empleado->name;

                        return;
                    }

                    Payslip::create([
                        'period_id' => $period->id,
                        'employee_id' => $empleado->id,
                        'employee_name' => $empleado->name,
                        'salary' => $salario,
                        'bonus' => 0,
                        'penalty' => 0,
                        'loan_deduction' => 0,
                        'isss' => PayrollCalculator::isss($salario),
                        'afp' => PayrollCalculator::afp($salario),
                        'user_id' => auth()->id(),
                    ]);

                    $creadas++;
                });
        });

        return ['created' => $creadas, 'skipped' => $saltadas, 'without_salary' => $sinSalario];
    }

    /**
     * Marca la quincena como pagada y sella la fecha en cada boleta.
     */
    public function markAsPaid(PayrollPeriod $period, ?string $method = 'cash'): void
    {
        DB::transaction(function () use ($period, $method): void {
            $period->payslips()->get()->each(function (Payslip $boleta) use ($period, $method): void {
                $boleta->update([
                    'paid_at' => $boleta->paid_at ?? $period->payment_date,
                    'payment_method' => $boleta->payment_method ?? $method,
                ]);
            });

            $period->update(['status' => PayrollPeriod::STATUS_PAID]);
        });
    }
}
