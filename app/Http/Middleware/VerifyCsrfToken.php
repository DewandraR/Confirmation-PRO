<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Jika kamu pakai SPA / API tertentu, pertimbangkan pengecualian di sini.
     * Tambahkan endpoint beacon logout agar bisa dipanggil via navigator.sendBeacon().
     */
    protected $except = [
        '/logout-beacon',
        // Tambahkan endpoint lain bila perlu...
    ];
}
