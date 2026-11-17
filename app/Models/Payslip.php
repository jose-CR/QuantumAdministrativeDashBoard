<?php

namespace App\Models;

use App\Support\Payroll\PayrollCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La boleta de un empleado en una quincena.
 *
 * Lo que escribe el encargado (bono, multa, préstamo) recalcula los totales al
 * guardar; los descuentos de ley solo se recalculan si cambia el salario de la
 * boleta, para no mover una boleta ya generada cuando cambian los porcentajes.
 */
class Payslip extends Model
{
    protected $fillable = [
        'period_id',
        'employee_id',
        'employee_name',
        'salary',
        'bonus',
        'isss',
        'afp',
        'penalty',
        'penalty_reason',
        'loan_deduction',
        'paid_at',
        'payment_method',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
            'bonus' => 'decimal:2',
            'isss' => 'decimal:2',
            'afp' => 'decimal:2',
            'penalty' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net' => 'decimal:2',
            'total_to_receive' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Payslip $boleta): void {
            if ($boleta->isDirty('salary') || $boleta->isss === null) {
                $boleta->isss = PayrollCalculator::isss((float) $boleta->salary);
                $boleta->afp = PayrollCalculator::afp((float) $boleta->salary);
            }

            $totales = PayrollCalculator::totals(
                salary: (float) $boleta->salary,
                bonus: (float) $boleta->bonus,
                isss: (float) $boleta->isss,
                afp: (float) $boleta->afp,
                penalty: (float) $boleta->penalty,
                loan: (float) $boleta->loan_deduction,
            );

            $boleta->total_deductions = $totales['total_deductions'];
            $boleta->net = $totales['net'];
            $boleta->total_to_receive = $totales['total_to_receive'];
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
