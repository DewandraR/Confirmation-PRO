<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachSapCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = session('sap.username');
        $p = session('sap.password');

        if (!$u || !$p) {
            // Sesi SAP expired/hilang, beri respon 440 (Login Timeout)
            return response()->json(['ok' => false, 'error' => 'Sesi SAP habis atau belum login.'], 440);
        }

        // LEWATKAN CREDENTIAL KE REQUEST SEBAGAI ATTRIBUTE
        $request->attributes->set('sap_username', $u);
        $request->attributes->set('sap_password', $p);

        // JANGAN PANGGIL session()->save() di sini! Biarkan Laravel melepaskan lock
        // setelah session di-read.

        return $next($request);
    }
}
