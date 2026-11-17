<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Descarga del Excel de boletas por la ruta firmada que arma la notificación.
 */
class PayslipDownloadController extends Controller
{
    public function __invoke(Request $request, string $filename)
    {
        $ruta = storage_path('app/private/payroll/' . basename($filename));

        abort_unless(is_file($ruta), 404);

        return response()->download($ruta, basename($filename))->deleteFileAfterSend();
    }
}
