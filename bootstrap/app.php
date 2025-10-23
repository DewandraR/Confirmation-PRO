<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Daftarkan group bawaan
        $middleware->web();
        $middleware->api();

        // 👉 Alias middleware khusus kita
        $middleware->alias([
            'countdown' => \App\Http\Middleware\EnforceCountdown::class,
        ]);

        // (Opsional) kalau kamu ingin menambah middleware global ke group 'web',
        // taruh di sini. Untuk kasus kita, TIDAK perlu append countdown ke 'web'
        // agar halaman login tetap bebas dari pemaksaan expired.
        // $middleware->appendToGroup('web', [
        //     \App\Http\Middleware\SomethingGlobal::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
