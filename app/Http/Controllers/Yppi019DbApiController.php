<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use App\Jobs\ConfirmProJob;
use App\Models\Yppi019ConfirmMonitor;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class Yppi019DbApiController extends Controller
{
    private function flaskBase(): string
    {
        // Pastikan env YPPI019_BASE/FLASK_BASE menunjuk ke host:port Flask yang AKTIF
        return rtrim(env('YPPI019_BASE', env('FLASK_BASE', 'http://127.0.0.1:5036')), '/');
    }

    /** VORNR 4 digit: 10 -> "0010" */
    private function padVornr(?string $v): string
    {
        $v = trim((string)($v ?? ''));
        if ($v === '') return '';
        $n = (int) round((float) $v);
        return str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }

    /** Format qty untuk SAP: integer untuk PC/EA/PCS/UNIT, desimal (koma) untuk lainnya */
    private function formatQtyForSap($qty, string $meinh = ''): string
    {
        if ($qty === null || $qty === '') return '0';
        if (is_string($qty)) $qty = str_replace(',', '.', $qty);
        $f = (float) $qty;

        $u = strtoupper(trim($meinh));
        $integerUnits = ['PC', 'EA', 'PCS', 'UNIT', 'ST'];
        if (in_array($u, $integerUnits, true)) {
            return (string) round($f);              // "1"
        }
        return number_format($f, 3, ',', '');       // "0,500"
    }

    /** Map ST -> PC */
    private function mapUnitForSap(string $meinh): string
    {
        $m = strtoupper(trim($meinh));
        return $m === 'ST' ? 'PC' : $m;
    }

    private function sapHeaders(): array
    {
        $u = session('sap.username');
        $p = session('sap.password');

        // Kalau sesi hilang/expired, hentikan lebih awal.
        if (!$u || !$p) {
            abort(440, 'Sesi SAP habis atau belum login. Silakan login ulang.'); // 440 = login timeout (konvensi)
        }

        return [
            'X-SAP-Username' => $u,
            'X-SAP-Password' => $p,
            'Content-Type'   => 'application/json',
        ];
    }

    public function sync(Request $req)
    {
        $aufnrRaw = trim((string) $req->input('aufnr', ''));
        $pernr    = trim((string) $req->input('pernr', ''));
        $arbpl    = trim((string) $req->input('arbpl', ''));
        $werks    = trim((string) $req->input('werks', ''));

        if ($pernr === '') {
            return response()->json(['ok' => false, 'error' => 'pernr wajib'], 400);
        }

        // Normalisasi & pecah aufnr kotor (spasi/tab/newline/beruntun) -> array unik
        $aufnrList = array_values(array_filter(array_unique(
            preg_split('/\s+/', $aufnrRaw, flags: PREG_SPLIT_NO_EMPTY)
        )));

        // Validasi minimal salah satu: aufnr ATAU arbpl
        $hasAufnr = count($aufnrList) > 0;
        if (!$hasAufnr && $arbpl === '') {
            return response()->json(['ok' => false, 'error' => 'aufnr atau arbpl wajib'], 400);
        }

        try {
            $http = fn(array $body) => Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(500)
                ->post($this->flaskBase() . '/api/yppi019/sync', $body);

            // Common body di luar aufnr
            $common = array_filter([
                'pernr' => $pernr,
                'arbpl' => $arbpl,
                'werks' => $werks,
            ]);

            // CASE 1: hanya satu aufnr -> perilaku lama (forward apa adanya)
            if ($hasAufnr && count($aufnrList) === 1) {
                $body = $common + ['aufnr' => $aufnrList[0]];
                $res  = $http($body);

                return response($res->body(), $res->status())
                    ->header('Content-Type', $res->header('Content-Type', 'application/json'));
            }

            // CASE 2: banyak aufnr -> kirim satu-satu dan gabungkan hasilnya
            if ($hasAufnr && count($aufnrList) > 1) {
                $results = [];
                $anyFailed = false;

                foreach ($aufnrList as $n) {
                    $res = $http($common + ['aufnr' => $n]);
                    $contentType = $res->header('Content-Type', 'application/json');

                    // Coba decode JSON; kalau gagal, simpan body mentah
                    $payload = $res->json();
                    if ($payload === null) {
                        $payload = $res->body();
                    }

                    $results[] = [
                        'aufnr'       => $n,
                        'status'      => $res->status(),
                        'contentType' => $contentType,
                        'data'        => $payload,
                    ];

                    if ($res->failed()) {
                        $anyFailed = true;
                    }
                }

                // 207 kalau ada yang gagal, 200 kalau semua sukses
                $status = $anyFailed ? 207 : 200;
                return response()->json([
                    'ok'      => !$anyFailed,
                    'count'   => count($results),
                    'results' => $results,
                ], $status);
            }

            // CASE 3: tidak ada aufnr, hanya arbpl -> perilaku lama
            $res = $http($common);
            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function syncBulk(Request $req)
    {
        $list  = $req->input('aufnr_list', []);
        $pernr = trim((string) $req->input('pernr', ''));
        $arbpl = trim((string) $req->input('arbpl', ''));

        if (!is_array($list) || !count($list)) return response()->json(['ok' => false, 'error' => 'aufnr_list wajib'], 400);
        if ($pernr === '') return response()->json(['ok' => false, 'error' => 'pernr wajib'], 400);

        $payload = ['aufnr_list' => array_values(array_unique(array_map('strval', $list))), 'pernr' => $pernr];
        if ($arbpl !== '') $payload['arbpl'] = $arbpl;

        try {
            $res = Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(420)
                ->post($this->flaskBase() . '/api/yppi019/sync_bulk', $payload);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function material(Request $req)
    {
        $aufnr    = $req->query('aufnr');
        $limit = $req->has('limit') ? (int) $req->query('limit') : null;
        $pernr    = trim((string) $req->query('pernr', ''));
        $arbpl    = $req->query('arbpl');
        $werks    = $req->query('werks');
        $autoSync = filter_var($req->query('auto_sync', '0'), FILTER_VALIDATE_BOOL);

        // Validasi input
        if (empty($aufnr) && empty($arbpl)) {
            return response()->json(['ok' => false, 'error' => 'Parameter aufnr atau arbpl wajib diisi'], 400);
        }
        if ($pernr === '') {
            return response()->json(['ok' => false, 'error' => 'Parameter pernr wajib diisi'], 400);
        }
        if (!empty($arbpl) && empty($werks)) {
            return response()->json(['ok' => false, 'error' => 'Jika arbpl diisi, parameter werks wajib diisi'], 400);
        }

        $base = $this->flaskBase();

        // Kumpulkan semua parameter query yang valid untuk dikirim ke Flask
        // bangun query params, tapi hanya tambahkan 'limit' jika tidak null
        $queryParams = array_filter([
            'aufnr' => $aufnr,
            'pernr' => $pernr,
            'arbpl' => $arbpl,
            'werks' => $werks,
        ], fn($v) => $v !== null && $v !== '');

        if ($limit !== null) {
            $queryParams['limit'] = $limit;
        }

        $res = Http::acceptJson()->timeout(30)->get($base . '/api/yppi019', $queryParams);
        $rows = $res->ok() ? ($res->json('rows') ?? []) : [];

        // PERBAIKAN: Tentukan kapan sinkronisasi harus berjalan.
        $isInitialSearch = !empty($aufnr) || !empty($arbpl);
        $shouldSync = $autoSync && (count($rows) === 0);

        if ($shouldSync) {
            $syncPayload = array_filter([
                'aufnr' => $aufnr,
                'pernr' => $pernr,
                'arbpl' => $arbpl,
                'werks' => $werks,
            ]);

            if (!empty($syncPayload['aufnr']) || (!empty($syncPayload['arbpl']) && !empty($syncPayload['werks']))) {
                $sync = Http::withHeaders($this->sapHeaders())
                    ->acceptJson()->timeout(500)
                    ->post($base . '/api/yppi019/sync', $syncPayload);

                if (!$sync->ok() || !($sync->json('ok') ?? false)) {
                    $err = $sync->json('error') ?? $sync->body();
                    return response()->json(['ok' => false, 'error' => 'sync_failed: ' . $err], 502);
                }

                // Ambil data lagi setelah sinkronisasi berhasil
                $res = Http::acceptJson()->timeout(30)->get($base . '/api/yppi019', $queryParams);

                if (!$res->ok()) {
                    $bodyArr = $res->json();
                    $err = is_array($bodyArr)
                        ? ($bodyArr['error'] ?? $bodyArr['message'] ?? json_encode($bodyArr))
                        : $res->body();
                    return response()->json(['ok' => false, 'error' => $err], $res->status());
                }
                $rows = $res->json('rows') ?? [];
            }
        }

        return response()->json([
            'ok' => true,
            'T_DATA1' => $rows,
            'RETURN' => [[
                'TYPE' => 'S',
                'MESSAGE' => count($rows) ? 'Loaded from local DB' : 'No data found',
                'ID' => '',
                'NUMBER' => '000',
                'PARAMETER' => '',
                'ROW' => 0,
                'SYSTEM' => ''
            ]],
        ]);
    }

    /** ➕ Baru: proxy hasil konfirmasi (RFC Z_FM_YPPR062) */
    public function hasil(Request $req)
    {
        $pernr = trim((string)$req->query('pernr', ''));
        $budat = preg_replace('/-/', '', (string)$req->query('budat', ''));

        if ($pernr === '' || !preg_match('/^\d{8}$/', $budat)) {
            return response()->json(['ok' => false, 'error' => 'param pernr & budat(YYYYMMDD) wajib'], 400);
        }

        try {
            $res = Http::withHeaders($this->sapHeaders())
                ->acceptJson()
                ->timeout(180)
                ->get($this->flaskBase() . '/api/yppi019/hasil', [
                    'pernr' => $pernr,
                    'budat' => $budat,
                ]);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function confirm(Request $req)
    {
        $payload = $req->validate([
            'aufnr' => 'required|string',
            'vornr' => 'nullable|string',
            'pernr' => 'required|string',
            'psmng' => 'required',
            'meinh' => 'nullable|string',
            'gstrp' => 'nullable|string',
            'gltrp' => 'nullable|string',
            'budat' => 'required|string',
            'charg' => 'nullable|string',
            'arbpl0' => 'nullable|string',
        ]);

        $payload['vornr'] = $this->padVornr($payload['vornr'] ?? null);

        if (!empty($payload['meinh'])) {
            $payload['meinh'] = $this->mapUnitForSap($payload['meinh']);
        }

        try {
            $res = Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(180)
                ->post($this->flaskBase() . '/api/yppi019/confirm', $payload);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** ➕ Proxy untuk simpan histori backdate ke Flask/MySQL (dipanggil saat konfirmasi sukses) */
    public function backdateLog(Request $req)
    {
        // Validasi ringan (biarkan Flask yang enforce lebih detail jika perlu)
        $payload = $req->validate([
            'aufnr'        => 'required|string',
            'vornr'        => 'nullable|string',
            'pernr'        => 'required|string',
            'qty'          => 'required|numeric',
            'meinh'        => 'nullable|string',
            'budat'        => 'required|string',   // format 'YYYYMMDD' dari FE
            'today'        => 'required|string',   // format 'YYYYMMDD'
            'arbpl0'       => 'nullable|string',
            'maktx'        => 'nullable|string',
            'sap_return'   => 'nullable',          // biarkan sebagai array/mixed
            'confirmed_at' => 'nullable|string',   // ISO timestamp dari FE
        ]);

        // Normalisasi vornr -> 4 digit
        $payload['vornr'] = $this->padVornr($payload['vornr'] ?? null);

        try {
            $res = Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(30)
                ->post($this->flaskBase() . '/api/yppi019/backdate-log', $payload);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** ➕ Baru: proxy ambil histori backdate untuk modal FE (TANPA header SAP) */
    public function backdateHistory(Request $req)
    {
        // Param yang dipakai FE: pernr (disarankan), aufnr (opsional), limit (default 50), order (asc|desc)
        $pernr = trim((string)$req->query('pernr', ''));
        $aufnr = trim((string)$req->query('aufnr', ''));
        $limit = $req->query('limit'); // bisa null
        $order = $req->query('order', 'desc');

        $query = array_filter([
            'pernr' => $pernr,
            'aufnr' => $aufnr,
            'order' => $order,
            // hanya tambahkan 'limit' jika user mengirimkannya
            'limit' => $limit !== null ? (int)$limit : null,
        ], fn($v) => $v !== null && $v !== '');

        try {
            // Endpoint Flask ini hanya baca MySQL, tidak butuh kredensial SAP.
            $res = Http::acceptJson()
                ->timeout(30)
                ->get($this->flaskBase() . '/api/yppi019/backdate-history', $query);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function confirmAsync(Request $req)
    {
        $data = $req->validate([
            'budat'                  => ['required', 'regex:/^\d{8}$/'], // yyyymmdd
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.aufnr'          => ['required', 'string', 'size:12'],
            'items.*.vornr'          => ['nullable', 'string', 'max:4'],
            'items.*.pernr'          => ['required', 'string', 'max:32'],
            'items.*.operator_name'  => ['nullable', 'string', 'max:120'],
            'items.*.qty_confirm'    => ['required', 'numeric', 'gt:0'],
            'items.*.qty_pro'        => ['nullable', 'numeric'],
            'items.*.meinh'          => ['nullable', 'string', 'max:8'],
            'items.*.arbpl0'         => ['nullable', 'string', 'max:40'],
            'items.*.charg'          => ['nullable', 'string', 'max:40'],
        ]);

        // Track SAP user dari sesi saat ini
        $sapUser = (string) session('sap.username');
        $sapPass = (string) session('sap.password');

        if ($sapUser === '' || $sapPass === '') {
            return response()->json(['ok' => false, 'error' => 'Sesi SAP habis atau belum login'], 440);
        }

        // Simpan credential terenkripsi agar bisa dipakai di Job (bukan plain text)
        $sapAuthBlob = Crypt::encryptString(json_encode(['u' => $sapUser, 'p' => $sapPass]));

        $ids = [];
        foreach ($data['items'] as $it) {
            $rec = Yppi019ConfirmMonitor::create([
                'aufnr'              => $it['aufnr'],
                'vornr'              => $this->padVornr($it['vornr'] ?? null),
                'meinh'              => $this->mapUnitForSap($it['meinh'] ?? 'ST'),
                'qty_pro'            => Arr::get($it, 'qty_pro'),
                'qty_confirm'        => $it['qty_confirm'],
                'confirmation_date'  => now()->toDateString(),
                'operator_nik'       => $it['pernr'],
                'operator_name'      => $it['operator_name'] ?? null,
                'sap_user'           => $sapUser, // tracking siapa yang login SAP
                'status'             => 'PENDING',
                'request_payload'    => [
                    'budat'   => $data['budat'],
                    'arbpl0'  => $it['arbpl0'] ?? null,
                    'charg'   => $it['charg'] ?? null,
                    'sap_auth' => $sapAuthBlob, // terenkripsi
                ],
            ]);
            ConfirmProJob::dispatch($rec->id);
            $ids[] = $rec->id;
        }

        return response()->json(['queued' => $ids], 202);
    }

    // GET /api/yppi019/confirm-monitor
    public function confirmMonitor(Request $req)
    {
        $pernr = $req->query('pernr');
        $limit = min((int)$req->query('limit', 100), 500);

        // Ambil SAP user dari sesi saat ini
        $sapUser = (string) session('sap.username');
        if ($sapUser === '') {
            return response()->json(['error' => 'Sesi SAP habis atau belum login'], 440);
        }

        // cutoff: mulai dari awal hari (today - 7 hari)
        $cutoff = now()->startOfDay()->subDays(7);

        $q = Yppi019ConfirmMonitor::query()
            // hanya milik SAP user yang login (case-insensitive)
            ->whereRaw('LOWER(sap_user) = ?', [Str::lower($sapUser)])
            ->when($pernr, fn($qq) => $qq->where('operator_nik', $pernr))
            // 🔽 tampilkan hanya 7 hari terakhir (pakai processed_at jika ada,
            // fallback ke created_at jika processed_at masih null)
            ->where(function ($qq) use ($cutoff) {
                $qq->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('processed_at')
                        ->where('processed_at', '>=', $cutoff);
                })
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('processed_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->orderByDesc('id')
            ->limit($limit);

        return response()->json([
            'data' => $q->get([
                'id',
                'aufnr',
                'vornr',
                'meinh',
                'qty_pro',
                'qty_confirm',
                'operator_nik',
                'operator_name',
                'sap_user',
                'status',
                'status_message',
                'processed_at',
                'created_at'
            ])
        ]);
    }
}
