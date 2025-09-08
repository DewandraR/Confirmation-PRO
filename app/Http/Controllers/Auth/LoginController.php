<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login', ['title' => 'Masuk']);
    }

    public function store(Request $request)
    {
        // Validasi form + lokasi dari Blade
        $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
            'lat'      => ['required','numeric'],
            'lng'      => ['required','numeric'],
            'acc'      => ['nullable','numeric'],
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $acc = (float) $request->input('acc', 9999);

        // Baca daftar kantor dari config
        $sites = array_values(array_filter(config('office.sites', [])));
        $maxAccM = (int) config('office.max_accuracy_m', 300);

        if ($maxAccM > 0 && $acc > $maxAccM) {
            return back()->withErrors([
                'location' => "Akurasi lokasi terlalu rendah (> {$maxAccM} m). Aktifkan GPS/high accuracy dan coba lagi."
            ])->onlyInput('email');
        }

        if (empty($sites)) {
            return back()->withErrors([
                'location' => 'Konfigurasi kantor belum diatur.'
            ])->onlyInput('email');
        }

        // Cek jarak ke tiap kantor -> lolos jika ada yang dalam radius
        $allowed = false;
        $matched = null;
        $closest = null;

        foreach ($sites as $site) {
            if (!isset($site['lat'], $site['lng'], $site['radius_m'])) continue;

            $d = $this->haversineMeters($lat, $lng, (float)$site['lat'], (float)$site['lng']);
            if ($closest === null || $d < $closest['distance']) {
                $closest = ['site' => $site, 'distance' => $d];
            }
            if ($d <= (int)$site['radius_m']) {
                $allowed = true;
                $matched = ['site' => $site, 'distance' => $d];
                break;
            }
        }

        if (!$allowed) {
            if ($closest) {
                $nearName   = $closest['site']['name'] ?? ($closest['site']['code'] ?? 'kantor terdekat');
                $nearRadius = (int) ($closest['site']['radius_m'] ?? 0);
                $dist       = round($closest['distance']);
                return back()->withErrors([
                    'location' => "Di luar area kantor. Terdekat: {$nearName} (~{$dist} m, batas {$nearRadius} m)."
                ])->onlyInput('email');
            }
            return back()->withErrors(['location' => 'Di luar area kantor.'])->onlyInput('email');
        }

        // ===== KUNCI: session habis saat browser ditutup + nonaktifkan remember =====
        config(['session.expire_on_close' => true]);

        $credentials = $request->only('email','password');
        if (Auth::attempt($credentials, false)) { // <— selalu false
            $request->session()->regenerate();

            // Bersihkan cookie remember jika ada
            Cookie::queue(Cookie::forget(Auth::getRecallerName()));

            if ($matched) {
                session(['login_office' => ($matched['site']['name'] ?? $matched['site']['code'] ?? 'unknown')]);
            }
            return redirect()->intended(route('scan'));
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget(Auth::getRecallerName()));

        return redirect()->route('login');
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000.0; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}
