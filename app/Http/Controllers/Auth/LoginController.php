<?php

// app\Http\Controllers\Auth\LoginController.php
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
        return view('auth.login', ['title' => 'Masuk | Konfirmasi PRO App']);
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

        // Session habis saat browser ditutup
        config(['session.expire_on_close' => true]);

        // SAP AUTH via Flask
        $sapId      = $request->input('sap_id');
        $sapPass    = $request->input('password');
        $sapAuthUrl = config('services.sap.login_url', env('SAP_AUTH_URL', 'http://127.0.0.1:5036/api/sap-login'));

        // === Cek whitelist user lebih dulu ===
        // if (!$this->isWhitelistedUser($sapId)) {
        //     return back()
        //         ->withErrors(['login' => 'Aplikasi sedang maintenance. Akun ini sementara tidak diberi izin mengakses aplikasi.'])
        //         ->onlyInput('sap_id');
        // }
        // === End cek whitelist ===

        try {
            $resp = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($sapAuthUrl, [
                    'username' => $sapId,
                    'password' => $sapPass,
                ]);

            if (!$resp->successful()) {
                $raw = $resp->json('error') ?? $resp->json('message') ?? $resp->body();
                $msg = $this->normalizeSapError((string) $raw, $resp->status());
                return back()->withErrors(['login' => $msg])->onlyInput('sap_id');
            }
        } catch (\Throwable $e) {
            $msg = $this->normalizeSapError($e->getMessage() ?: 'Network error', 0);
            return back()->withErrors(['login' => $msg])->onlyInput('sap_id');
        }

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

        // SAP OK -> buat/ambil user lokal TANPA SapUser
        $user = User::firstOrCreate(
            ['email' => $sapId . '@kmi.local'],
            [
                'name'     => $sapId,
                'password' => Hash::make(Str::random(16)),
                'role'     => 'user',
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

    /**
     * Ubah pesan error teknis dari SAP/HTTP jadi pesan manusia.
     * Diperketat agar kasus "internet pribadi / SAP unreachable" tidak salah dikira password salah.
     */
    private function normalizeSapError(string $raw, int $status): string
    {
        $t = strtolower($raw ?? '');

        // ----- 1) Koneksi/Jaringan/VPN (gunakan internet perusahaan) -----
        $networkHints = [
            'rfc_communication_failure',
            'wsaetimedout',
            'connection timed out',
            'timed out',
            'partner',      // biasanya "partner 'x.x.x.x:sapdpNN' not reached"
            'not reached',
            'name or service not known',
            'network is unreachable',
            'connection refused',
            'could not connect',
            'ssl', 'certificate verify failed',
        ];
        foreach ($networkHints as $h) {
            if (strpos($t, $h) !== false) {
                return 'Tidak dapat terhubung ke server SAP. Gunakan internet/Wi-Fi perusahaan atau VPN, lalu coba lagi.';
            }
        }

        // ----- 2) Kredensial salah (murni indikasi logon failure), tanpa mengandalkan status 401 saja -----
        if (
            strpos($t, 'rfc_logon_failure') !== false ||
            strpos($t, 'name or password is incorrect') !== false ||
            strpos($t, 'password logon no user') !== false ||
            strpos($t, 'password incorrect') !== false
        ) {
            return 'SAP ID atau Password tidak valid.';
        }

        // ----- 3) User terkunci / wajib ganti password / expired -----
        if (
            strpos($t, 'user locked') !== false ||
            strpos($t, 'password logon no change') !== false ||
            strpos($t, 'password must be changed') !== false ||
            strpos($t, 'password expired') !== false
        ) {
            return 'Akun SAP terkunci atau butuh reset password. Silakan hubungi admin SAP.';
        }

        // ----- 4) Tidak berotorisasi -----
        if (
            strpos($t, 'not authorization') !== false ||
            strpos($t, 'no authorization') !== false ||
            $status === 403
        ) {
            return 'Anda tidak memiliki akses untuk login SAP. Hubungi admin SAP untuk otorisasi.';
        }

        // ----- 5) Fallback ambil "message=..." kalau ada -----
        if (preg_match('/message\s*=\s*([^\n]+)/i', (string) $raw, $m)) {
            $hint = trim($m[1]);
            if (stripos($hint, 'name or password') !== false) {
                return 'SAP ID atau Password tidak valid.';
            }
            if ($hint !== '') {
                return $hint;
            }
        }

        // ----- 6) Default generik -----
        return 'Gagal login ke SAP. Silakan periksa kredensial dan koneksi jaringan Anda.';
    }

    /**
     * Whitelist user yang diizinkan login.
     */
    // private function isWhitelistedUser(string $sapId): bool
    // {
    //     // Hardcode sesuai kebutuhan:
    //     $allowed = ['auto_email'];

    //     $id = strtolower(trim($sapId));
    //     foreach ($allowed as $u) {
    //         if ($id === strtolower($u)) {
    //             return true;
    //         }
    //     }
    //     return false;
    // }
}
