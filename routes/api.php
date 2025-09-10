<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes (stateless, TANPA session)
|--------------------------------------------------------------------------
| JANGAN taruh endpoint YPPI019 di sini karena butuh session login SAP.
| Simpan di routes/web.php (dengan prefix /api) seperti pada file di atas.
*/

// Default contoh (opsional)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check sederhana (opsional)
Route::get('/ping', fn () => ['ok' => true, 'ts' => now()->toISOString()]);
