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
use Illuminate\Support\Facades\DB;

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
            return (string) round($f);             // "1"
        }
        return number_format($f, 3, ',', '');         // "0,500"
    }

    /** Map ST -> PC */
    private function mapUnitForSap(string $meinh): string
    {
        $m = strtoupper(trim($meinh));
        return $m === 'ST' ? 'PC' : $m;
    }

    /**
     * PERBAIKAN KRUSIAL: Ambil dari request attributes, bukan dari session()
     * Ini mencegah session blocking.
     */
    private function sapHeaders(Request $request): array
    {
        // Ambil dari Request Attributes yang sudah diisi oleh sap_auth middleware
        $u = $request->attributes->get('sap_username');
        $p = $request->attributes->get('sap_password');

        // Middleware seharusnya sudah menangani error 440, ini hanya guard
        if (!$u || !$p) {
            abort(440, 'Sesi SAP habis atau belum login. Silakan login ulang.');
        }

        return [
            'X-SAP-Username' => $u,
            'X-SAP-Password' => $p,
            'Content-Type'      => 'application/json',
        ];
    }

    public function sync(Request $req)
    {
        $aufnrRaw = trim((string) $req->input('aufnr', ''));
        $pernr       = trim((string) $req->input('pernr', ''));
        $arbpl       = trim((string) $req->input('arbpl', ''));
        $werks       = trim((string) $req->input('werks', ''));

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
            // UBAH: Kirim $req ke sapHeaders
            $http = fn(array $body) => Http::withHeaders($this->sapHeaders($req))
                ->acceptJson()->timeout(500000)
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
                        'status'       => $res->status(),
                        'contentType' => $contentType,
                        'data'           => $payload,
                    ];

                    if ($res->failed()) {
                        $anyFailed = true;
                    }
                }

                // 207 kalau ada yang gagal, 200 kalau semua sukses
                $status = $anyFailed ? 207 : 200;
                return response()->json([
                    'ok'       => !$anyFailed,
                    'count'       => count($results),
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
        $list     = $req->input('aufnr_list', []);
        $pernr = trim((string) $req->input('pernr', ''));
        $arbpl = trim((string) $req->input('arbpl', ''));

        if (!is_array($list) || !count($list)) return response()->json(['ok' => false, 'error' => 'aufnr_list wajib'], 400);
        if ($pernr === '') return response()->json(['ok' => false, 'error' => 'pernr wajib'], 400);

        $payload = ['aufnr_list' => array_values(array_unique(array_map('strval', $list))), 'pernr' => $pernr];
        if ($arbpl !== '') $payload['arbpl'] = $arbpl;

        try {
            // UBAH: Kirim $req ke sapHeaders
            $res = Http::withHeaders($this->sapHeaders($req))
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
        $aufnr       = $req->query('aufnr');
        $limit = $req->has('limit') ? (int) $req->query('limit') : null;
        $pernr       = trim((string) $req->query('pernr', ''));
        $arbpl       = $req->query('arbpl');
        $werks       = $req->query('werks');
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
                // UBAH: Kirim $req ke sapHeaders
                $sync = Http::withHeaders($this->sapHeaders($req))
                    ->acceptJson()->timeout(5000)
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
            // UBAH: Kirim $req ke sapHeaders
            $res = Http::withHeaders($this->sapHeaders($req))
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
            // UBAH: Kirim $req ke sapHeaders
            $res = Http::withHeaders($this->sapHeaders($req))
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
            'qty'            => 'required|numeric',
            'meinh'        => 'nullable|string',
            'budat'        => 'required|string',     // format 'YYYYMMDD' dari FE
            'today'        => 'required|string',     // format 'YYYYMMDD'
            'arbpl0'        => 'nullable|string',
            'maktx'        => 'nullable|string',
            'sap_return' => 'nullable',         // biarkan sebagai array/mixed
            'confirmed_at' => 'nullable|string',     // ISO timestamp dari FE
        ]);

        // Normalisasi vornr -> 4 digit
        $payload['vornr'] = $this->padVornr($payload['vornr'] ?? null);

        try {
            // UBAH: Kirim $req ke sapHeaders
            $res = Http::withHeaders($this->sapHeaders($req))
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
        // ... (Kode ini tidak perlu diubah karena tidak memanggil sapHeaders)
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
        // 1) Validasi payload dasar
        $data = $req->validate([
            'wi_code'           => ['nullable', 'string', 'max:50'], // <-- NEW (top-level, opsional)
            'budat'             => ['required', 'regex:/^\d{8}$/'],  // yyyymmdd
            'items'             => ['required', 'array', 'min:1'],

            'items.*.aufnr'        => ['required', 'string', 'size:12'],
            'items.*.vornr'        => ['nullable', 'string', 'max:4'],
            'items.*.pernr'        => ['required', 'string', 'max:32'],
            'items.*.operator_name' => ['nullable', 'string', 'max:120'],
            'items.*.qty_confirm'     => ['required', 'numeric', 'gt:0'],
            'items.*.qty_pro'        => ['nullable', 'numeric'],
            'items.*.meinh'        => ['nullable', 'string', 'max:8'],
            'items.*.arbpl0'        => ['nullable', 'string', 'max:40'],
            'items.*.charg'        => ['nullable', 'string', 'max:40'],
            'items.*.wi_code' => ['nullable', 'string', 'max:50'],

            // metadata opsional yang akan ikut disimpan
            'items.*.werks'        => ['nullable', 'string', 'max:8'],
            'items.*.ltxa1'        => ['nullable', 'string', 'max:200'],
            'items.*.matnrx'        => ['nullable', 'string', 'max:40'],
            'items.*.maktx'        => ['nullable', 'string', 'max:200'],
            'items.*.maktx0'        => ['nullable', 'string', 'max:200'],
            'items.*.dispo'        => ['nullable', 'string', 'max:10'],
            'items.*.steus'        => ['nullable', 'string', 'max:10'],
            'items.*.soitem'        => ['nullable', 'string', 'max:30'], // contoh: "4500.../10"
            'items.*.kaufn'        => ['nullable', 'string', 'max:20'], // Sales Order (jika FE kirim terpisah)
            'items.*.kdpos'        => ['nullable', 'string', 'max:6'],  // Item 6 digit (jika FE kirim terpisah)
            'items.*.ssavd'        => ['nullable', 'string', 'max:10'], // yyyymmdd atau variasi
            'items.*.sssld'        => ['nullable', 'string', 'max:10'],
            'items.*.ltimex'        => ['nullable', 'numeric'],
            'items.*.gstrp'        => ['nullable', 'string', 'max:10'],
            'items.*.gltrp'        => ['nullable', 'string', 'max:10'],
        ]);

        // 2) Pastikan SAP session tersedia
        $sapUser = (string) $req->attributes->get('sap_username');
        $sapPass = (string) $req->attributes->get('sap_password');

        if ($sapUser === '' || $sapPass === '') {
            return response()->json(['ok' => false, 'error' => 'Sesi SAP habis atau belum login'], 440);
        }

        // --- WI mode? (jika wi_code ada) ---
        $topWiCode = $data['wi_code'] ?? null;

        $hasAnyWi = !empty($topWiCode);
        if (!$hasAnyWi) {
            foreach (($data['items'] ?? []) as $it) {
                if (!empty($it['wi_code'] ?? null)) { $hasAnyWi = true; break; }
            }
        }

        $lockedBudatUsers = [];

        if ($hasAnyWi) {
            $data['budat'] = now()->format('Ymd'); // paksa hari ini jika ada WI
        } elseif (in_array(strtoupper($sapUser), $lockedBudatUsers, true)) {
            $data['budat'] = now()->format('Ymd');
        }

        // 3) Enkripsi credential agar bisa dipakai di Job
        $sapAuthBlob = \Illuminate\Support\Facades\Crypt::encryptString(
            json_encode(['u' => $sapUser, 'p' => $sapPass])
        );

        // 4) Siapkan array id antrian
        $ids = [];
        $budatYMD = preg_replace('/\D/', '', $data['budat']); // jaga-jaga

        foreach ($data['items'] as $it) {
            $topWiCode   = $data['wi_code'] ?? null;           // fallback (kalau FE lama)
            $itemWiCode  = $it['wi_code'] ?? $topWiCode;       // <-- KUNCI
            $itemWiMode  = !empty($itemWiCode);
            // ---- Derive Sales Order / Item 6 digit ----
            $salesOrder = $it['kaufn'] ?? null; // kalau FE sudah kirim terpisah
            $soItem     = $it['kdpos'] ?? null; // kalau FE sudah kirim terpisah

            if ($soItem !== null && $soItem !== '') {
                $soItem = str_pad((string) $soItem, 6, '0', STR_PAD_LEFT);
            }

            if (!$salesOrder || !$soItem) {
                // fallback parse dari "soitem" (contoh "4500123456/10")
                [$soParsed, $itemParsed] = $this->splitSoItem($it['soitem'] ?? null);
                $salesOrder = $salesOrder ?: $soParsed;
                $soItem     = $soItem     ?: $itemParsed; // sudah 6 digit dari helper
            }

            // ---- Simpan ke DB (lengkap sesuai migrasi baru) ----
            $rec = \App\Models\Yppi019ConfirmMonitor::create([
                'aufnr'               => (string) $it['aufnr'],
                'vornr'               => $this->padVornr($it['vornr'] ?? null),
                'meinh'               => $this->mapUnitForSap($it['meinh'] ?? 'ST'), // ST -> PC
                'qty_pro'             => \Illuminate\Support\Arr::get($it, 'qty_pro'),
                'qty_confirm'         => $it['qty_confirm'],
                'confirmation_date'   => now()->toDateString(),
                'posting_date'        => $this->ymdToDate($budatYMD),

                'operator_nik'        => $it['pernr'],
                'operator_name'       => $it['operator_name'] ?? null,
                'sap_user'            => $sapUser,
                'status'              => 'PENDING',

                // metadata bisnis
                'plant'               => \Illuminate\Support\Arr::get($it, 'werks'),
                'work_center'         => \Illuminate\Support\Arr::get($it, 'arbpl0'),
                'op_desc'             => \Illuminate\Support\Arr::get($it, 'ltxa1'),

                'material'            => \Illuminate\Support\Arr::get($it, 'matnrx'),
                'material_desc'       => \Illuminate\Support\Arr::get($it, 'maktx'),
                'fg_desc'             => \Illuminate\Support\Arr::get($it, 'maktx0'),

                'mrp_controller'      => \Illuminate\Support\Arr::get($it, 'dispo'),
                'control_key'         => \Illuminate\Support\Arr::get($it, 'steus'),

                'sales_order'         => $salesOrder,
                'so_item'             => $soItem,
                'batch_no'            => \Illuminate\Support\Arr::get($it, 'charg'),

                'start_date_plan'     => $this->ymdToDate(\Illuminate\Support\Arr::get($it, 'ssavd')),
                'finish_date_plan'    => $this->ymdToDate(\Illuminate\Support\Arr::get($it, 'sssld')),
                'minutes_plan'        => \Illuminate\Support\Arr::get($it, 'ltimex'),

                // payload yang diperlukan Job (termasuk sap_auth terenkripsi)
                'request_payload'     => [
                    'budat'        => $budatYMD,
                    'arbpl0'       => \Illuminate\Support\Arr::get($it, 'arbpl0'),
                    'charg'        => \Illuminate\Support\Arr::get($it, 'charg'),
                    'sap_auth'     => $sapAuthBlob,

                    // flag WI mode
                    'wi_mode'     => $itemWiMode,
                    'wi_code'     => $itemWiCode,
                    'confirm_qty'  => $it['qty_confirm'],
                ],

                // snapshot tampilan popup (audit trail)
                'row_meta'            => [
                    'wc'      => \Illuminate\Support\Arr::get($it, 'arbpl0'),
                    'ltxa1'   => \Illuminate\Support\Arr::get($it, 'ltxa1'),
                    'matnrx'  => \Illuminate\Support\Arr::get($it, 'matnrx'),
                    'maktx'   => \Illuminate\Support\Arr::get($it, 'maktx'),
                    'maktx0'  => \Illuminate\Support\Arr::get($it, 'maktx0'),
                    'soitem'  => \Illuminate\Support\Arr::get($it, 'soitem'),
                    'kaufn'   => $salesOrder,
                    'kdpos'   => $soItem,
                    'ssavd'   => \Illuminate\Support\Arr::get($it, 'ssavd'),
                    'sssld'   => \Illuminate\Support\Arr::get($it, 'sssld'),
                    'ltimex'  => \Illuminate\Support\Arr::get($it, 'ltimex'),
                    'dispo'   => \Illuminate\Support\Arr::get($it, 'dispo'),
                    'steus'   => \Illuminate\Support\Arr::get($it, 'steus'),
                    'werks'   => \Illuminate\Support\Arr::get($it, 'werks'),
                    'gstrp'   => \Illuminate\Support\Arr::get($it, 'gstrp'),
                    'gltrp'   => \Illuminate\Support\Arr::get($it, 'gltrp'),
                ],
            ]);

            // 5) Dispatch job pemrosesan ke background
            \App\Jobs\ConfirmProJob::dispatch($rec->id);

            $ids[] = $rec->id;
        }

        // 6) Response: daftar id yang di-antri-kan
        return response()->json(['queued' => $ids], 202);
    }

    public function remarkAsync(Request $req)
    {
        // 0) Pastikan SAP user ada (harusnya sudah disediakan middleware sap_auth)
        $sapUser = (string) $req->attributes->get('sap_username');
        if ($sapUser === '') {
            return response()->json([
                'ok'    => false,
                'error' => 'Sesi SAP habis atau belum login',
            ], 440);
        }

        // 1) Validasi payload (tambahkan metadata opsional untuk tampilan)
        $data = $req->validate([
            'items' => ['required', 'array', 'min:1'],

            'items.*.wi_code'     => ['required', 'string', 'max:50'],
            'items.*.aufnr'       => ['required', 'string', 'size:12'],
            'items.*.vornr'       => ['nullable', 'string', 'max:4'],
            'items.*.nik'         => ['required', 'string', 'max:32'],
            'items.*.operator_name' => ['nullable', 'string', 'max:120'],
            'items.*.remark'      => ['required', 'string', 'max:500'],
            'items.*.remark_qty'  => ['required', 'numeric', 'gt:0'],

            // ---- metadata display (opsional, tapi disarankan FE kirim) ----
            'items.*.qty_pro'     => ['nullable', 'numeric'],
            'items.*.meinh'       => ['nullable', 'string', 'max:8'],
            'items.*.matnrx'      => ['nullable', 'string', 'max:40'],  // material
            'items.*.maktx'       => ['nullable', 'string', 'max:200'], // material_desc
            'items.*.maktx0'      => ['nullable', 'string', 'max:200'], // fg_desc

            'items.*.werks'       => ['nullable', 'string', 'max:8'],
            'items.*.arbpl0'      => ['nullable', 'string', 'max:40'],
            'items.*.ltxa1'       => ['nullable', 'string', 'max:200'],
            'items.*.dispo'       => ['nullable', 'string', 'max:10'],
            'items.*.steus'       => ['nullable', 'string', 'max:10'],
            'items.*.charg'       => ['nullable', 'string', 'max:40'],
            'items.*.soitem'      => ['nullable', 'string', 'max:30'],
            'items.*.kaufn'       => ['nullable', 'string', 'max:20'],
            'items.*.kdpos'       => ['nullable', 'string', 'max:6'],
            'items.*.ssavd'       => ['nullable', 'string', 'max:10'],
            'items.*.sssld'       => ['nullable', 'string', 'max:10'],
            'items.*.ltimex'      => ['nullable', 'numeric'],
        ]);

        $ids = [];

        foreach ($data['items'] as $it) {
            $aufnr = (string) $it['aufnr'];
            $vornr = $this->padVornr($it['vornr'] ?? null);

            $nik       = (string) $it['nik'];
            $opName    = $it['operator_name'] ?? null;

            $remark    = (string) $it['remark'];
            $remarkQty = (float)  $it['remark_qty'];
            $wiCode    = (string) $it['wi_code'];

            // 2) Fallback metadata dari record monitor terakhir untuk PRO+VORNR yang sama
            $ref = \App\Models\Yppi019ConfirmMonitor::query()
                ->where('aufnr', $aufnr)
                ->where('vornr', $vornr)
                ->where('operator_nik', $nik) 
                ->orderByDesc('id')
                ->first();

            // 3) Ambil metadata prioritas: dari FE -> fallback ref -> null
            $meinh = $this->mapUnitForSap((string)($it['meinh'] ?? ($ref->meinh ?? 'PC')));

            $qtyPro = \Illuminate\Support\Arr::get($it, 'qty_pro',
          \Illuminate\Support\Arr::get($it, 'qtyPro', $ref->qty_pro ?? null));

            $material      = $it['matnrx'] ?? ($ref->material ?? null);
            $materialDesc  = $it['maktx']  ?? ($ref->material_desc ?? null);
            $fgDesc        = $it['maktx0'] ?? ($ref->fg_desc ?? null);

            $opName = \Illuminate\Support\Arr::get($it, 'operator_name',
          \Illuminate\Support\Arr::get($it, 'operatorName', $ref->operator_name ?? null));

            // SO / Item (biar konsisten dengan confirmAsync)
            $salesOrder = $it['kaufn'] ?? ($ref->sales_order ?? null);
            $soItem     = $it['kdpos'] ?? ($ref->so_item ?? null);
            if ($soItem !== null && $soItem !== '') {
                $soItem = str_pad((string) $soItem, 6, '0', STR_PAD_LEFT);
            }
            if (!$salesOrder || !$soItem) {
                [$soParsed, $itemParsed] = $this->splitSoItem($it['soitem'] ?? null);
                $salesOrder = $salesOrder ?: $soParsed;
                $soItem     = $soItem     ?: $itemParsed;
            }

            // 4) Simpan record monitor (ISI FIELD DISPLAY)
            $rec = \App\Models\Yppi019ConfirmMonitor::create([
                'aufnr'             => $aufnr,
                'vornr'             => $vornr,

                'meinh'             => $meinh,
                'qty_pro'           => $qtyPro,
                'qty_confirm'       => $remarkQty,

                'material'          => $material,
                'material_desc'     => $materialDesc,
                'fg_desc'           => $fgDesc,

                'confirmation_date' => now()->toDateString(),
                'posting_date'      => now()->toDateString(),

                'operator_nik'      => $nik,
                'operator_name'     => $opName,

                // PENTING: ini dipakai filter confirmMonitor()
                'sap_user'          => $sapUser,

                'status'            => 'PENDING',
                'status_message'    => null,

                // metadata bisnis (opsional untuk laporan/tampilan)
                'plant'             => $it['werks']  ?? ($ref->plant ?? null),
                'work_center'       => $it['arbpl0'] ?? ($ref->work_center ?? null),
                'op_desc'           => $it['ltxa1']  ?? ($ref->op_desc ?? null),
                'mrp_controller'    => $it['dispo']  ?? ($ref->mrp_controller ?? null),
                'control_key'       => $it['steus']  ?? ($ref->control_key ?? null),

                'sales_order'       => $salesOrder,
                'so_item'           => $soItem,
                'batch_no'          => $it['charg'] ?? ($ref->batch_no ?? null),

                'start_date_plan'   => $this->ymdToDate($it['ssavd'] ?? null) ?? ($ref->start_date_plan ?? null),
                'finish_date_plan'  => $this->ymdToDate($it['sssld'] ?? null) ?? ($ref->finish_date_plan ?? null),
                'minutes_plan'      => $it['ltimex'] ?? ($ref->minutes_plan ?? null),

                // payload untuk ConfirmProJob
                'request_payload'   => [
                    'action'     => 'remark',
                    'wi_mode'    => true,
                    'wi_code'    => $wiCode,

                    'remark'     => $remark,
                    'remark_qty' => $remarkQty,

                    'nik'        => $nik,
                    'aufnr'      => $aufnr,
                    'vornr'      => $vornr,
                ],
            ]);

            // 5) Dispatch job
            \App\Jobs\ConfirmProJob::dispatch($rec->id);

            $ids[] = $rec->id;
        }

        return response()->json([
            'queued' => $ids,
        ], 202);
    }

    public function confirmMonitorByIds(Request $req)
    {
        $idsRaw = $req->query('ids', '');
        $ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $idsRaw))));

        if (!count($ids)) return response()->json(['data' => []]);

        $rows = \App\Models\Yppi019ConfirmMonitor::query()
            ->whereIn('id', $ids)
            ->get(['id','aufnr','vornr','operator_nik','status','status_message','processed_at']);

        return response()->json(['data' => $rows]);
    }
    
    // GET /api/yppi019/confirm-monitor
    public function confirmMonitor(Request $req)
    {
        $pernr = $req->query('pernr');
        $limit = min((int)$req->query('limit', 100), 500);

        // PERBAIKAN: Ambil dari request attributes
        $sapUser = (string) $req->attributes->get('sap_username');
        if ($sapUser === '') {
            return response()->json(['error' => 'Sesi SAP habis atau belum login'], 440);
        }

        $cutoff = now()->startOfDay()->subDays(7);

        $q = Yppi019ConfirmMonitor::query()
            ->whereRaw('LOWER(sap_user) = ?', [Str::lower($sapUser)])
            ->when($pernr, fn($qq) => $qq->where('operator_nik', $pernr))
            ->where(function ($qq) use ($cutoff) {
                $qq->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('processed_at')->where('processed_at', '>=', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('processed_at')->where('created_at', '>=', $cutoff);
                });
            })
            ->orderByDesc('id')
            ->limit($limit);

        // 👉 kembalikan waktu dalam WIB (+07:00)
        $data = $q->get([
            'id',
            'aufnr',
            'vornr',
            'meinh',
            'qty_pro',
            'qty_confirm',
            'material',
            'fg_desc',
            'material_desc',
            'operator_nik',
            'operator_name',
            'sap_user',
            'status',
            'status_message',
            DB::raw("DATE_ADD(processed_at, INTERVAL 7 HOUR) as processed_at"),
            DB::raw("DATE_ADD(created_at,  INTERVAL 7 HOUR) as created_at"),
        ]);

        return response()->json(['data' => $data]);
    }

    private function ymdToDate(?string $ymd): ?string
    {
        if (!$ymd) return null;
        $s = preg_replace('/\D/', '', $ymd);
        if (!preg_match('/^\d{8}$/', $s)) return null;
        return substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2);
    }
    private function splitSoItem(?string $s): array
    {
        // support "4500123456/10" atau "4500123456" + item 6 digit tersembunyi
        $so = null;
        $item = null;
        if (!$s) return [$so, $item];
        if (preg_match('~^\s*(\d+)\s*/\s*(\d+)\s*$~', $s, $m)) {
            $so = $m[1];
            $item = str_pad($m[2], 6, '0', STR_PAD_LEFT);
        } else {
            // terakhir: semua digit => SO saja
            if (preg_match('~^\d+$~', $s)) $so = $s;
        }
        return [$so, $item];
    }
    public function hasilRange(Request $req)
    {
        $pernr = trim((string)$req->query('pernr', ''));
        // dukung from/to dan alias lain
        $from  = preg_replace('/-/', '', (string)($req->query('from', $req->query('budat_from', $req->query('date_from', '')))));
        $to    = preg_replace('/-/', '', (string)($req->query('to',   $req->query('budat_to',   $req->query('date_to',   '')))));

        if ($pernr === '' || !preg_match('/^\d{8}$/', $from) || !preg_match('/^\d{8}$/', $to)) {
            return response()->json(['ok' => false, 'error' => 'param pernr & from/to (YYYYMMDD) wajib'], 400);
        }

        try {
            $res = \Illuminate\Support\Facades\Http::withHeaders($this->sapHeaders($req))
                ->acceptJson()
                ->timeout(300)
                ->get($this->flaskBase() . '/api/yppi019/hasil-range', [
                    'pernr' => $pernr,
                    'from'  => $from,
                    'to'    => $to,
                    // opsional: forward aufnr kalau Anda mau
                    'aufnr' => $req->query('aufnr'),
                ]);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
