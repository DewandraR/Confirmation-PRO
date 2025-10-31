<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Yppi019DbApiController;

/*
|--------------------------------------------------------------------------
| Web Routes (Pages & API Session-Dependent)
|--------------------------------------------------------------------------
| Halaman yang memerlukan otentikasi UI (session) berada di grup 'auth'.
| API yang memerlukan kredensial SAP dipindahkan ke grup 'api', 'sap_auth' (stateless).
*/

// === GUEST (belum login) ===
Route::middleware('guest')->group(function () {
    Route::get('/login',     [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/login/queue/status/{ticket}', [LoginController::class, 'queueStatus'])->name('login.queue.status');
    Route::post('/login/queue/claim',          [LoginController::class, 'queueClaim'])->name('login.queue.claim');
    Route::post('/login/queue/cancel',         [LoginController::class, 'queueCancel'])->name('login.queue.cancel');
});

// === AUTH (sudah login) - PAGES (HTML VIEW) ===
// Grup ini menangani halaman UI dan tetap menggunakan middleware 'auth' (session).
Route::middleware(['auth', /*'countdown']*/])->group(function () {
    Route::get('/scan',     [ScanController::class,     'show'])->name('scan');
    Route::get('/detail', [DetailController::class, 'show'])->name('detail');
    Route::get('/hasil', fn() => view('hasil'))->name('hasil');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// === API YPPI019 (NON-BLOCKING terhadap Session) ===
// Grup ini menggunakan:
// 1. 'api' group (stateless, TANPA session)
// 2. 'sap_auth' (middleware kustom yang membaca session SAP dan segera melepaskan lock)
Route::middleware(['api', 'sap_auth'])->prefix('api')->group(function () {
    // Semua API yang membutuhkan otentikasi SAP (memanggil sapHeaders())
    Route::post('/yppi019/sync',               [Yppi019DbApiController::class, 'sync']);
    Route::post('/yppi019/sync_bulk',          [Yppi019DbApiController::class, 'syncBulk']);
    Route::get('/yppi019/material',          [Yppi019DbApiController::class, 'material']);
    Route::post('/yppi019/confirm',          [Yppi019DbApiController::class, 'confirm']);

    Route::post('/yppi019/backdate-log',     [Yppi019DbApiController::class, 'backdateLog']);
    Route::get('/yppi019/hasil', [Yppi019DbApiController::class, 'hasil'])->name('api.yppi019.hasil');

    // API yang menggunakan Job/Monitor (juga butuh sapHeaders/kredensial)
    Route::post('/yppi019/confirm-async',     [Yppi019DbApiController::class, 'confirmAsync'])
        ->name('yppi019.confirm.async');
    Route::get('/yppi019/confirm-monitor', [Yppi019DbApiController::class, 'confirmMonitor'])
        ->name('yppi019.confirm.monitor');
});

// === API YPPI019 (BENAR-BENAR STATLEESS) ===
// Route yang tidak memanggil sapHeaders() dan tidak butuh sap_auth
Route::prefix('api')->group(function () {
    Route::get('/yppi019/backdate-history', [Yppi019DbApiController::class, 'backdateHistory']);
});


// Root diarahkan ke /scan
Route::redirect('/', '/scan');
