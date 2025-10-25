<?php

// app/Http/Middleware/EnforceCountdown.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EnforceCountdown
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $exp = $request->session()->get('expires_at');

            if (!$exp || now()->greaterThanOrEqualTo(Carbon::parse($exp))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['login' => 'Sesi Anda telah habis (10 menit). Silakan login kembali.']);
            }
        }

        return $next($request);
    }
}
