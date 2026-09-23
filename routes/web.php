<?php

use App\Http\Controllers\Report\CashReportDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['login' => '/admin/login']);
});

Route::get('/cash-report/{filename}', CashReportDownloadController::class)
    ->name('cash-report.download')
    ->middleware(['web', 'auth', 'signed']);
