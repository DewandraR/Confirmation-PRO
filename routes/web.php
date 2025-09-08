<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\Auth\LoginController;

// === GUEST (belum login) ===
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// === AUTH (sudah login) ===
Route::middleware('auth')->group(function () {
    Route::get('/scan', [ScanController::class, 'show'])->name('scan');
    Route::get('/detail', [DetailController::class, 'show'])->name('detail');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// Root diarahkan ke /scan
Route::redirect('/', '/scan');
