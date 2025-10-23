<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Yppi019DbApiController;

/*
|--------------------------------------------------------------------------
| Web Routes (punya session)
|--------------------------------------------------------------------------
| Semua endpoint YPPI019 dipasang di middleware "web" agar bisa baca session
| sap.username / sap.password, tapi tetap pakai prefix /api agar URL FE sama.
*/

// === GUEST (belum login) ===
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/login/queue/status/{ticket}', [LoginController::class, 'queueStatus'])->name('login.queue.status');
    Route::post('/login/queue/claim',         [LoginController::class, 'queueClaim'])->name('login.queue.claim');
    Route::post('/login/queue/cancel',        [LoginController::class, 'queueCancel'])->name('login.queue.cancel');
});

// === AUTH (sudah login) ===
Route::middleware(['auth', 'countdown'])->group(function () {
    Route::get('/scan',   [ScanController::class,  'show'])->name('scan');
    Route::get('/detail', [DetailController::class, 'show'])->name('detail');
    Route::get('/hasil', fn() => view('hasil'))->name('hasil');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // --- API YPPI019 (prefix /api)
    Route::prefix('api')->group(function () {
        Route::post('/yppi019/sync',           [Yppi019DbApiController::class, 'sync']);
        Route::post('/yppi019/sync_bulk',      [Yppi019DbApiController::class, 'syncBulk']);
        Route::get('/yppi019/material',       [Yppi019DbApiController::class, 'material']);
        Route::post('/yppi019/confirm',        [Yppi019DbApiController::class, 'confirm']);

        // ➕ Histori backdate (dipakai modal di detail.blade)
        Route::post('/yppi019/backdate-log',     [Yppi019DbApiController::class, 'backdateLog']);
        Route::get('/yppi019/backdate-history', [Yppi019DbApiController::class, 'backdateHistory']); // <- tambahkan ini

        // ➕ Hasil Konfirmasi (proxy RFC Z_FM_YPPR062 ke Flask)
        Route::get('/yppi019/hasil', [Yppi019DbApiController::class, 'hasil'])->name('api.yppi019.hasil');
        Route::post('/yppi019/confirm-async',  [Yppi019DbApiController::class, 'confirmAsync'])
            ->name('yppi019.confirm.async');

        Route::get('/yppi019/confirm-monitor', [Yppi019DbApiController::class, 'confirmMonitor'])
            ->name('yppi019.confirm.monitor');
    });
});

// Root diarahkan ke /scan
Route::redirect('/', '/scan');
