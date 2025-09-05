<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// === GUEST (belum login) ===
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// === AUTH (sudah login) ===
Route::middleware('auth')->group(function () {
    // Pakai /scan sebagai halaman utama aplikasi setelah login
    Route::get('/scan', [ScanController::class, 'show'])->name('scan');

    // Halaman detail
    Route::get('/detail', [DetailController::class, 'show'])->name('detail');

    // Logout
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// Root diarahkan ke /scan (kalau belum login, otomatis ke /login)
Route::redirect('/', '/scan');

// Tambahan: route logout (POST), hanya untuk user yang sudah login
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login'); // arahkan ke halaman login
})->name('logout')->middleware('auth');






