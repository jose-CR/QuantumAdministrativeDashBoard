<?php

namespace App\Support\Payroll;

use App\Models\PayrollSetting;

/**
 * Las cuentas de la boleta en un solo lugar.
 *
 * ISSS: porcentaje sobre el salario de la quincena, con el tope de ley (el tope se
 * configúa mensual, así que acá se usa la mitad).
 * AFP: porcentaje sobre el salario de la quincena, sin tope.
 */
final class PayrollCalculator
{
    public static function isss(float $salary, ?PayrollSetting $settings = null): float
    {
        $settings ??= PayrollSetting::current();

        $aporte = round(round($salary, 2) * ((float) $settings->isss_rate / 100), 2);

        // El tope es sobre el aporte, no sobre el salario: el tope se carga mensual,
        // por eso acá se usa la mitad (se paga por quincena).
        return min($aporte, round((float) $settings->isss_cap / 2, 2));
    }

    public static function afp(float $salary, ?PayrollSetting $settings = null): float
    {
        $settings ??= PayrollSetting::current();

        return round(round($salary, 2) * ((float) $settings->afp_rate / 100), 2);
    }

    /**
     * Totales de una boleta a partir de lo que ya está guardado.
     *
     * @return array{total_deductions: float, net: float, total_to_receive: float}
     */
    public static function totals(
        float $salary,
        float $bonus,
        float $isss,
        float $afp,
        float $penalty,
        float $loan,
    ): array {
        $descuentos = round($isss + $afp + $penalty + $loan, 2);
        $neto = round($salary - $descuentos, 2);

        return [
            'total_deductions' => $descuentos,
            'net' => $neto,
            'total_to_receive' => round($neto + $bonus, 2),
        ];
    }
}
