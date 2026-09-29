<?php

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
Route::get('/attachments/{attachment}/ver', function (Attachment $attachment) {
    return Storage::disk($attachment->disk)->response($attachment->path);
})->middleware(['signed', 'attachments.access'])->name('attachments.show');
