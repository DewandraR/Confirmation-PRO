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
| File ini otomatis berada di middleware "web", jadi session tersedia.
| Kita taruh SEMUA endpoint YPPI019 di sini (dengan prefix /api) agar
| bisa membaca session('sap.username') & session('sap.password').
*/

// === GUEST (belum login) ===
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// === AUTH (sudah login) ===
Route::middleware('auth')->group(function () {
    // Halaman aplikasi
    Route::get('/scan',   [ScanController::class,  'show'])->name('scan');
    Route::get('/detail', [DetailController::class,'show'])->name('detail');
    Route::post('/logout',[LoginController::class, 'destroy'])->name('logout');

    // --- API YPPI019 (tetap pakai prefix /api agar URL front-end tidak berubah)
    Route::prefix('api')->group(function () {
        Route::post('/yppi019/sync',           [Yppi019DbApiController::class, 'sync']);
        Route::post('/yppi019/sync_bulk',      [Yppi019DbApiController::class, 'syncBulk']);
        Route::get ('/yppi019/material',       [Yppi019DbApiController::class, 'material']);
        Route::post('/yppi019/confirm',        [Yppi019DbApiController::class, 'confirm']);
        Route::post('/yppi019/update_qty_spx', [Yppi019DbApiController::class, 'updateQtySpx']);
    });
});

// Root diarahkan ke /scan
Route::redirect('/', '/scan');
