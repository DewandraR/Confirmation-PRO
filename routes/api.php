<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Yppi019DbApiController;
use Illuminate\Support\Facades\Http;

Route::post('/yppi019/sync',             [Yppi019DbApiController::class, 'sync']);
Route::post('/yppi019/sync_bulk',        [Yppi019DbApiController::class, 'syncBulk']);
Route::get ('/yppi019/material',         [Yppi019DbApiController::class, 'material']);
Route::post('/yppi019/update_qty_spx',   [Yppi019DbApiController::class, 'updateQtySpx']); // opsional

Route::post('/yppi019/confirm', function (Request $req) {
    $base = rtrim(env('YPPI019_BASE', 'http://127.0.0.1:5051'), '/');

    // Ambil kredensial SAP dari header request kalau ada, kalau tidak pakai .env
    $sapUser = $req->header('X-SAP-Username', env('SAP_USERNAME'));
    $sapPass = $req->header('X-SAP-Password', env('SAP_PASSWORD'));

    // Tujuan ke Flask: /api/yppi019/confirm
    $url = "{$base}/api/yppi019/confirm";

    $res = Http::withHeaders([
        'X-SAP-Username' => $sapUser,
        'X-SAP-Password' => $sapPass,
        'Accept'         => 'application/json',
    ])->post($url, $req->all());

    return response($res->body(), $res->status())
        ->header('Content-Type', 'application/json');
});
