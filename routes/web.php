<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\DetailController;
// use App\Http\Controllers\Yppi019DbApiController;


Route::get('/', [ScanController::class, 'show'])->name('scan');
Route::get('/scan', [ScanController::class, 'show']);
Route::get('/detail', [DetailController::class, 'show'])->name('detail');

// Route::prefix('api')->group(function () {
//     Route::post('/yppi019/sync',    [Yppi019DbApiController::class, 'sync']);
//     Route::post('/yppi019/sync_bulk', [Yppi019DbApiController::class, 'syncBulk']);
//     Route::get ('/yppi019/material',[Yppi019DbApiController::class, 'material']);
// });
