<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CashReportDownloadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $filename)
    {
        $path = storage_path('app/private/exports/' . basename($filename));

        abort_unless(is_file($path), 404);

        return response()->download($path, 'reporte_flujo_caja.xlsx')->deleteFileAfterSend();

    }
}
