<?php

use App\Http\Controllers\Attachments\PrivateMediaController;
use App\Http\Controllers\Attachments\PublicImageController;
use App\Http\Controllers\Payroll\PayslipDownloadController;
use App\Http\Controllers\Report\CashReportDownloadController;
use App\Models\Attachment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome', ['login' => '/admin/login']);
});

Route::get('/cash-report/{filename}', CashReportDownloadController::class)
    ->name('cash-report.download')
    ->middleware(['web', 'auth', 'signed']);
    
// routes/web.php
Route::get('/payroll/{filename}', PayslipDownloadController::class)
    ->name('payroll.download')
    ->middleware(['web', 'auth', 'signed']);

/*
 * Adjunto publicado: URL para compartir afuera (foto de artículo, catálogo).
 *
 * Sin sesión y sin firma: el token aleatorio ES la autorización. Solo responde a
 * adjuntos publicados de artículos; todo lo demás es 404.
 */
Route::get('/img/{token}/{name?}', PublicImageController::class)
    ->name('attachments.public')
    ->middleware('throttle:120,1');

// Archivo privado con sesión: miniaturas y visor del panel (URL firmada, 30 minutos).
Route::get('/media/{type}/{id}/ver', PrivateMediaController::class)
    ->name('media.private')
    ->middleware(['web', 'auth', 'signed']);

Route::get('/attachments/{attachment}/ver', function (Attachment $attachment) {
    return Storage::disk($attachment->disk)->response($attachment->path);
})->middleware(['signed', 'attachments.access'])->name('attachments.show');
