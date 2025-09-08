<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login', ['title' => 'Masuk']);
    }

    public function store(Request $request)
    {
        // Validasi form + lokasi
        $request->validate([
            'sap_id'   => ['required','string'],
            'password' => ['required','string'],
            'lat'      => ['required','numeric'],
            'lng'      => ['required','numeric'],
            'acc'      => ['nullable','numeric'],
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $acc = (float) $request->input('acc', 9999);

        // Konfigurasi kantor
        $sites   = array_values(array_filter(config('office.sites', [])));
        $maxAccM = (int) config('office.max_accuracy_m', 300);

        if ($maxAccM > 0 && $acc > $maxAccM) {
            return back()->withErrors([
                'location' => "Akurasi lokasi terlalu rendah (> {$maxAccM} m). Aktifkan GPS/high accuracy dan coba lagi."
            ])->onlyInput('sap_id');
        }
        if (empty($sites)) {
            return back()->withErrors(['location' => 'Konfigurasi kantor belum diatur.'])->onlyInput('sap_id');
        }

        // Cek jarak kantor
        $allowed = false; $matched = null; $closest = null;
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
                ])->onlyInput('sap_id');
            }
            return back()->withErrors(['location' => 'Di luar area kantor.'])->onlyInput('sap_id');
        }

        // Session habis saat browser ditutup
        config(['session.expire_on_close' => true]);

        // SAP AUTH via Flask
        $sapId   = $request->input('sap_id');
        $sapPass = $request->input('password');
        $sapAuthUrl = config('services.sap.login_url', env('SAP_AUTH_URL', 'http://127.0.0.1:5051/api/sap-login'));

        try {
            $resp = Http::timeout(30)->post($sapAuthUrl, [
                'username' => $sapId,
                'password' => $sapPass,
            ]);
            if (!$resp->successful()) {
                $msg = $resp->json('error') ?? $resp->json('message') ?? 'Username atau Password SAP tidak valid.';
                return back()->withErrors(['login' => $msg])->onlyInput('sap_id');
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['login' => 'Tidak dapat terhubung ke layanan otentikasi SAP.'])->onlyInput('sap_id');
        }

        // SAP OK -> buat/ambil user lokal TANPA SapUser
        $user = User::firstOrCreate(
            ['email' => $sapId . '@kmi.local'],
            [
                'name'     => $sapId,            // nama default = sap_id
                'password' => Hash::make(Str::random(16)),
                'role'     => 'user',            // sesuaikan role default jika perlu
            ]
        );

        // Simpan kredensial SAP untuk header ke Flask di request berikutnya
        session([
            'sap.username' => $sapId,
            'sap.password' => $sapPass,
        ]);

        // Bersihkan cookie remember & login
        Cookie::queue(Cookie::forget(Auth::getRecallerName()));
        Auth::login($user, false);

        if ($matched) {
            session(['login_office' => ($matched['site']['name'] ?? $matched['site']['code'] ?? 'unknown')]);
        }

        return redirect()->intended(route('scan'));
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
