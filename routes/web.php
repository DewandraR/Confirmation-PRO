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
| CSRF untuk /logout-beacon sudah di-exempt pada App\Http\Middleware\VerifyCsrfToken.
*/

// === GUEST (belum login) ===
Route::middleware('guest')->group(function () {
    Route::get('/login',                     [LoginController::class, 'show'])->name('login');
    Route::post('/login',                    [LoginController::class, 'store'])->name('login.store');

    // Antrian login (opsional)
    Route::get('/login/queue/status/{ticket}', [LoginController::class, 'queueStatus'])->name('login.queue.status');
    Route::post('/login/queue/claim',          [LoginController::class, 'queueClaim'])->name('login.queue.claim');
    Route::post('/login/queue/cancel',         [LoginController::class, 'queueCancel'])->name('login.queue.cancel');
});

// === AUTH (sudah login) - PAGES (HTML VIEW) ===
// Grup ini menggunakan middleware 'auth' (session). Saat session expired, otomatis redirect ke /login.
Route::middleware(['auth' /*, 'countdown' */])->group(function () {
    Route::get('/scan',   [ScanController::class,   'show'])->name('scan');
    Route::get('/detail', [DetailController::class, 'show'])->name('detail');
    Route::get('/hasil',  fn() => view('hasil'))->name('hasil');

    // Logout normal (form POST, pakai CSRF)
    Route::post('/logout',         [LoginController::class, 'destroy'])->name('logout');

    // Logout via Beacon (tanpa CSRF; sudah di-exempt di VerifyCsrfToken)
    Route::post('/logout-beacon',  [LoginController::class, 'destroyViaBeacon'])->name('logout.beacon');
});

// === API YPPI019 (NON-BLOCKING terhadap Session) ===
// 1) 'api' group (stateless, TANPA session cookie Laravel)
// 2) 'sap_auth' (middleware kustom yang membaca kredensial SAP dan segera melepas lock)
Route::middleware(['api', 'sap_auth'])->prefix('api')->group(function () {

    Route::post('/yppi019/sync',          [Yppi019DbApiController::class, 'sync']);
    Route::post('/yppi019/sync_bulk',     [Yppi019DbApiController::class, 'syncBulk']);
    Route::get('/yppi019/material',       [Yppi019DbApiController::class, 'material']);
    Route::post('/yppi019/confirm',       [Yppi019DbApiController::class, 'confirm']);

    Route::post('/yppi019/backdate-log',  [Yppi019DbApiController::class, 'backdateLog']);
    Route::get('/yppi019/hasil',          [Yppi019DbApiController::class, 'hasil'])->name('api.yppi019.hasil');

    Route::post('/yppi019/confirm-async', [Yppi019DbApiController::class, 'confirmAsync'])->name('yppi019.confirm.async');
    Route::get('/yppi019/confirm-monitor',[Yppi019DbApiController::class, 'confirmMonitor'])->name('yppi019.confirm.monitor');

    // ✅ PINDAHKAN KE SINI
    Route::post('/yppi019/remark-async', [Yppi019DbApiController::class, 'remarkAsync'])
        ->name('yppi019.remark.async');

    Route::get('/yppi019/confirm-monitor-ids', [Yppi019DbApiController::class, 'confirmMonitorByIds']);
});

// === API YPPI019 (BENAR-BENAR STATELESS) ===
// Route yang tidak memanggil sapHeaders() dan tidak butuh sap_auth
Route::prefix('api')->group(function () {
    Route::get('/yppi019/backdate-history', [Yppi019DbApiController::class, 'backdateHistory']);
});

// Root diarahkan ke /scan (akan melewati middleware 'auth' dan otomatis minta login jika belum)
Route::redirect('/', '/scan');
