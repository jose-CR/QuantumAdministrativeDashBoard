<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Support\PythonBinary;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;

/**
 * Genera el Excel de la quincena: un solo archivo con todas las boletas, un bloque
 * por empleado, rellenando la plantilla del taller (python/payslips.py).
 */
class PayslipExcelService
{
    /**
     * @return string ruta del archivo generado
     */
    public function generate(PayrollPeriod $period): string
    {
        $rows = $period->payslips()
            ->orderBy('employee_name')
            ->get()
            ->map(fn (Payslip $boleta): array => [
                'NOMBRE' => $boleta->employee_name,
                'FECHA' => $period->payslip_date,
                'SALARIO' => (float) $boleta->salary,
                'ISSS' => (float) $boleta->isss,
                'AFP' => (float) $boleta->afp,
                'TOTAL DESCUENTOS' => (float) $boleta->total_deductions,
                'SUELDO NETO' => (float) $boleta->net,
                'BONO' => (float) $boleta->bonus,
                'TOTAL' => (float) $boleta->total_to_receive,
                'TOTAL RECIBIDO' => (float) $boleta->total_to_receive,
            ])
            ->all();

        if ($rows === []) {
            throw ValidationException::withMessages([
                'payslips' => __('ui.payroll.no_payslips'),
            ]);
        }

        $directorio = storage_path('app/private/payroll');
        File::ensureDirectoryExists($directorio);

        $datos = $directorio . '/periodo-' . $period->id . '.json';
        $salida = $directorio . '/boletas-' . $period->label . '.xlsx';

        File::put($datos, json_encode([
            'template' => base_path('python/templates/boleta-de-pago.xlsx'),
            'rows' => $rows,
        ], JSON_UNESCAPED_UNICODE));

        $resultado = Process::timeout(120)->run([
            PythonBinary::path(),
            base_path('python/payslips.py'),
            $datos,
            $salida,
        ]);

        // El JSON de entrada solo sirve para esta corrida.
        File::delete($datos);

        throw_if(
            $resultado->failed() || ! File::exists($salida),
            \RuntimeException::class,
            $resultado->errorOutput() ?: __('ui.payroll.excel_failed'),
        );

        return $salida;
    }
}
