<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
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
        // 1) Validasi form + lokasi
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

        // Sesi habis saat browser ditutup
        config(['session.expire_on_close' => true]);

        // 2) SAP AUTH via Flask
        $sapId      = $request->input('sap_id');
        $sapPass    = $request->input('password');
        $sapAuthUrl = config('services.sap.login_url', env('SAP_AUTH_URL', 'http://127.0.0.1:5036/api/sap-login'));

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

        // 3) Validasi lokasi kantor
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

        // 4) BATAS USER AKTIF: pakai list + kapasitas dari config
        $activeList = $this->getActiveList();
        $maxActive  = $this->maxActiveUsers();

        if (count($activeList) >= $maxActive) {
            // BUAT TIKET ANTREAN + simpan kredensial di session (untuk auto-claim)
            $ticket = (string) Str::uuid();
            session([
                'queue.ticket'       => $ticket,
                'sap.username'       => $sapId,
                'sap.password'       => $sapPass,
                'queue.matched_site' => $matched['site']['name'] ?? ($matched['site']['code'] ?? null),
            ]);
            // masukkan tiket ke antrean (FIFO) secara atomic
            $this->queueLock()->block(5, function () use ($ticket) {
                $list = Cache::get($this->queueListKey(), []);
                if (!in_array($ticket, $list, true)) {
                    $list[] = $ticket;
                    Cache::put($this->queueListKey(), $list, now()->addMinutes(30));
                }
            });
            // kembali ke halaman login (popup antrean akan muncul)
            return redirect()->route('login')->with('queued', true);
        }

        // 5) SAP OK & kapasitas ada → buat/ambil user lokal & login
        $user = User::firstOrCreate(
            ['email' => $sapId . '@kmi.local'],
            ['name' => $sapId, 'password' => Hash::make(Str::random(16)), 'role' => 'user']
        );

        // simpan kredensial untuk request berikutnya
        session(['sap.username' => $sapId, 'sap.password' => $sapPass]);

        // set countdown X menit (dari config)
        $expiresAt = now()->addMinutes($this->sessionMinutes());
        session(['expires_at' => $expiresAt->toIso8601String()]);

        // tambahkan user ke list aktif
        $activeList[] = [
            'sap_id'     => $sapId,
            'expires_at' => $expiresAt->getTimestamp(),
        ];
        $this->saveActiveList($activeList);

        // login (tanpa remember)
        Cookie::queue(Cookie::forget(Auth::getRecallerName()));
        Auth::login($user, false);

        if ($matched) {
            session(['login_office' => ($matched['site']['name'] ?? $matched['site']['code'] ?? 'unknown')]);
        }

        return redirect()->intended(route('scan'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Keluarkan user ini dari list aktif
        $me = session('sap.username');
        if ($me) {
            $list = $this->getActiveList(); // asumsi: metode ini ada di controller/trait yang sama
            $list = array_values(array_filter($list, fn($u) => ($u['sap_id'] ?? null) !== $me));
            $this->saveActiveList($list);   // asumsi: metode ini ada di controller/trait yang sama
        }

        // Bersih-bersih antrean tiket milik sesi ini (jika ada)
        $ticket = session('queue.ticket');
        if ($ticket) {
            $this->queueLock()->block(3, function () use ($ticket) {
                $list = Cache::get($this->queueListKey(), []);
                $list = array_values(array_filter($list, fn($t) => $t !== $ticket));
                Cache::put($this->queueListKey(), $list, now()->addMinutes(30));
            });
        }

        // Logout & invalidasi sesi
        Auth::logout();
        $request->session()->invalidate();

        // IMPORTANT: regenerate CSRF token baru untuk sesi berikutnya
        $request->session()->regenerateToken();

        // Hapus remember-me cookie (recaller)
        Cookie::queue(Cookie::forget(Auth::getRecallerName()));

        return redirect()->route('login');
    }

    /* =========================
     * QUEUE ENDPOINTS (guest)
     * ========================= */

    // Status antrean: posisi & siap-claim atau belum
    public function queueStatus(Request $request, string $ticket)
    {
        $position = null;
        $length = 0;

        $this->queueLock()->block(3, function () use (&$position, &$length, $ticket) {
            $list = Cache::get($this->queueListKey(), []);
            $length = count($list);
            $idx = array_search($ticket, $list, true);
            $position = ($idx === false) ? null : ($idx + 1);
        });

        $activeList = $this->getActiveList();
        $ready = (count($activeList) < $this->maxActiveUsers()) && ($position === 1);

        return response()->json([
            'ok'           => true,
            'position'     => $position,
            'queue_length' => $length,
            'active_count' => count($activeList),
            'capacity'     => $this->maxActiveUsers(),
            'ready'        => $ready,
        ]);
    }

    // Claim antrean: menyelesaikan login otomatis ketika giliran Anda
    public function queueClaim(Request $request)
    {
        $ticket = session('queue.ticket');
        if (!$ticket) {
            return response()->json(['ok' => false, 'error' => 'No ticket in session'], 400);
        }

        $claimed = false;
        $this->queueLock()->block(5, function () use (&$claimed, $ticket) {
            $queue = Cache::get($this->queueListKey(), []);
            $activeList = $this->getActiveList();
            $hasCapacity = count($activeList) < $this->maxActiveUsers();

            if ($hasCapacity && $queue && $queue[0] === $ticket) {
                array_shift($queue); // keluarkan tiket kita
                Cache::put($this->queueListKey(), $queue, now()->addMinutes(30));
                $claimed = true;
            }
        });

        if (!$claimed) {
            return response()->json(['ok' => false, 'error' => 'Not your turn yet'], 409);
        }

        // Lengkapi login seperti biasa
        $sapId   = session('sap.username');
        $sapPass = session('sap.password');
        if (!$sapId || !$sapPass) {
            return response()->json(['ok' => false, 'error' => 'Missing SAP credentials'], 400);
        }

        $user = User::firstOrCreate(
            ['email' => $sapId . '@kmi.local'],
            ['name' => $sapId, 'password' => Hash::make(Str::random(16)), 'role' => 'user']
        );

        // set masa berlaku sesi — TIDAK expire manual; nanti front-end yang redirect ke logout
        $expiresAt = now()->addMinutes($this->sessionMinutes());
        session(['expires_at' => $expiresAt->toIso8601String()]);

        // tambahkan user ke list aktif
        $activeList   = $this->getActiveList();
        $activeList[] = [
            'sap_id'     => $sapId,
            'expires_at' => $expiresAt->getTimestamp(),
        ];
        $this->saveActiveList($activeList);

        Cookie::queue(Cookie::forget(Auth::getRecallerName()));
        Auth::login($user, false);
        $request->session()->regenerate(); // amankan session id

        if ($name = session('queue.matched_site')) {
            session(['login_office' => $name]);
        }

        // bersihkan flag antrean dari session
        session()->forget(['queue.ticket', 'queue.matched_site']);

        // KIRIMKAN info auto-logout ke client
        return response()->json([
            'ok'                 => true,
            'redirect'           => route('scan'),
            'logout_url'         => route('logout'),
            'logout_at'          => $expiresAt->toIso8601String(),
            'logout_in_seconds'  => $expiresAt->diffInRealSeconds(now()),
        ]);
    }
    // Cancel antrean (klik "Batal" / tutup tab)
    public function queueCancel(Request $request)
    {
        $ticket = session('queue.ticket') ?? $request->input('ticket');
        if ($ticket) {
            $this->queueLock()->block(3, function () use ($ticket) {
                $list = Cache::get($this->queueListKey(), []);
                $list = array_values(array_filter($list, fn($t) => $t !== $ticket));
                Cache::put($this->queueListKey(), $list, now()->addMinutes(30));
            });
        }
        session()->forget(['queue.ticket', 'queue.matched_site']);
        return response()->json(['ok' => true]);
    }

    /* =========================
     * HELPERS
     * ========================= */

    // ——— konfigurasi dari config/login.php ———
    private function sessionMinutes(): int
    {
        return (int) config('login.session_minutes', 1);
    }
    private function maxActiveUsers(): int
    {
        return (int) config('login.max_active', 1);
    }

    // ——— daftar user aktif (array) ———
    private function activeListKey(): string
    {
        return 'active_login_users';
    }
    private function getActiveList(): array
    {
        $now = now()->getTimestamp();
        $list = Cache::get($this->activeListKey(), []);
        // bersihkan yang expired
        $list = array_values(array_filter($list, fn($u) => ($u['expires_at'] ?? 0) > $now));
        // simpan balik (perpanjang TTL container list)
        Cache::put($this->activeListKey(), $list, now()->addMinutes($this->sessionMinutes()));
        return $list;
    }
    private function saveActiveList(array $list): void
    {
        Cache::put($this->activeListKey(), $list, now()->addMinutes($this->sessionMinutes()));
    }

    // ——— antrean login (FIFO) ———
    private function queueLock()
    {
        return Cache::lock('login_queue_mutex', 5);
    }
    private function queueListKey(): string
    {
        return 'login_queue_list';
    }

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
    /**
     * Whitelist (opsional)
     */
    // private function isWhitelistedUser(string $sapId): bool
    // {
    //     $allowed = ['auto_email'];
    //     $id = strtolower(trim($sapId));
    //     foreach ($allowed as $u) {
    //         if ($id === strtolower($u)) return true;
    //     }
    //     return false;
    // }
}
