<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class DisableRememberAuth
{
    /**
     * Hilangkan auto-login dari remember cookie pada setiap request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Nama cookie remember (recaller) untuk guard "web"
        $recaller = Auth::getRecallerName();

        // Jika cookie remember ada, hapus
        if ($request->cookies->has($recaller)) {
            Cookie::queue(Cookie::forget($recaller));
        }

        // Jika user masuk "via remember", paksa logout agar akses /scan minta login lagi
        if (Auth::viaRemember()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login');
        }

        return $next($request);
    }
}
