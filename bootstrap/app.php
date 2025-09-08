<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',      // <- pastikan baris ini ada
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Daftarkan default middleware groups
        $middleware->web();
        $middleware->api();

        // Tambahkan middleware custom ke group "web" (untuk routes/web.php kamu)
        // Middleware ini akan menghapus cookie remember_* dan mencegah auto-login via remember
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\DisableRememberAuth::class,
        ]);

        // (opsional) alias custom bisa ditambahkan di sini
        // $middleware->alias(['auth.admin' => \App\Http\Middleware\AdminOnly::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
