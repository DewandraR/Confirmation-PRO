<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class LoginController extends Controller
{
    /* =========================
     * PUBLIC PAGES
     * ========================= */
    public function show()
    {
        return view('auth.login', ['title' => 'Masuk | Konfirmasi PRO App']);
    }

    public function store(Request $request)
    {
        // 1) Validasi form + lokasi (tetap dipakai)
        $request->validate([
            'sap_id'   => ['required', 'string'],
            'password' => ['required', 'string'],
            'lat'      => ['required', 'numeric'],
            'lng'      => ['required', 'numeric'],
            'acc'      => ['nullable', 'numeric'],
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $acc = (float) $request->input('acc', 9999);

        // Tidak memaksa sesi habis saat browser ditutup lagi.
        // Biarkan default dari config/session.php atau pakai remember-me (di bawah).
        // config(['session.expire_on_close' => false]);

        // 2) SAP AUTH via Flask (tetap)
        $sapId      = $request->input('sap_id');
        $sapPass    = $request->input('password');
        $sapAuthUrl = config('services.sap.login_url', env('SAP_AUTH_URL', 'http://192.168.90.27:5005/api/sap-login'));

        try {
            $resp = Http::timeout(30)->acceptJson()->asJson()->post($sapAuthUrl, [
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

        // 3) Validasi lokasi kantor (tetap)
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

        $allowed = false;
        $matched = null;
        $closest = null;
        foreach ($sites as $site) {
            if (!isset($site['lat'], $site['lng'], $site['radius_m'])) continue;
            $d = $this->haversineMeters($lat, $lng, (float)$site['lat'], (float)$site['lng']);
            if ($closest === null || $d < $closest['distance']) $closest = ['site' => $site, 'distance' => $d];
            if ($d <= (int) $site['radius_m']) {
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

        // ================================
        // 4) HILANGKAN BATAS & ANTREAN
        //    (Tidak ada pengecekan kapasitas/queue sama sekali)
        // ================================

        // 5) SAP OK → buat/ambil user lokal & login TANPA timer custom
        $user = User::firstOrCreate(
            ['email' => $sapId . '@kmi.local'],
            ['name' => $sapId, 'password' => Hash::make(Str::random(16)), 'role' => 'user']
        );

        // simpan kredensial bila perlu dipakai lagi
        session([
            'sap.username' => $sapId,
            'sap.password' => $sapPass,
            // alias untuk FE lama
            'sap_user'     => $sapId,
        ]);

        // Aktifkan "remember me" agar tidak auto-logout oleh timer aplikasi ini.
        // (Catatan: tetap tunduk ke konfigurasi session Laravel & masa berlaku cookie browser.)
        // false karena sudah tidak di butuhkan
        Auth::login($user, false); // <-- perhatikan argumen "true" untuk remember

        // Jangan hapus recaller cookie (kebalikan dari kode lama)
        // Cookie::queue(Cookie::forget(Auth::getRecallerName()));

        if ($matched) {
            session(['login_office' => ($matched['site']['name'] ?? $matched['site']['code'] ?? 'unknown')]);
        }

        return redirect()->intended(route('scan'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Tidak perlu kelola active-list / antrean lagi

        // Logout & invalidasi sesi
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Biarkan cookie remember-me ikut dibersihkan oleh Auth::logout()
        // (Jika ingin paksa hapus juga: Cookie::queue(Cookie::forget(Auth::getRecallerName())) )

        return redirect()->route('login');
    }

    public function destroyViaBeacon(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->noContent(); // 204, ringan untuk sendBeacon
    }

    /* =========================
     * HELPERS
     * ========================= */

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000.0; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    /** Ubah pesan error SAP/HTTP jadi manusiawi */
    private function normalizeSapError(string $raw, int $status): string
    {
        $t = strtolower($raw ?? '');

        // 1) Koneksi/Jaringan/VPN
        $networkHints = [
            'rfc_communication_failure',
            'wsaetimedout',
            'connection timed out',
            'timed out',
            'partner',
            'not reached',
            'name or service not known',
            'network is unreachable',
            'connection refused',
            'could not connect',
            'ssl',
            'certificate verify failed',
        ];
        foreach ($networkHints as $h) {
            if (strpos($t, $h) !== false) {
                return 'Tidak dapat terhubung ke server SAP. Gunakan internet/Wi-Fi perusahaan atau VPN, lalu coba lagi.';
            }
        }

        // 2) Kredensial salah
        if (
            strpos($t, 'rfc_logon_failure') !== false ||
            strpos($t, 'name or password is incorrect') !== false ||
            strpos($t, 'password logon no user') !== false ||
            strpos($t, 'password incorrect') !== false
        ) {
            return 'SAP ID atau Password tidak valid.';
        }

        // 3) User terkunci / wajib ganti / expired
        if (
            strpos($t, 'user locked') !== false ||
            strpos($t, 'password logon no change') !== false ||
            strpos($t, 'password must be changed') !== false ||
            strpos($t, 'password expired') !== false
        ) {
            return 'Akun SAP terkunci atau butuh reset password. Silakan hubungi admin SAP.';
        }

        // 4) Tidak berotorisasi
        if (
            strpos($t, 'not authorization') !== false ||
            strpos($t, 'no authorization') !== false ||
            $status === 403
        ) {
            return 'Anda tidak memiliki akses untuk login SAP. Hubungi admin SAP untuk otorisasi.';
        }

        // 5) Fallback "message=..."
        if (preg_match('/message\s*=\s*([^\n]+)/i', (string) $raw, $m)) {
            $hint = trim($m[1]);
            if (stripos($hint, 'name or password') !== false) return 'SAP ID atau Password tidak valid.';
            if ($hint !== '') return $hint;
        }

        // 6) Default
        return 'Gagal login ke SAP. Silakan periksa kredensial dan koneksi jaringan Anda.';
    }
}
