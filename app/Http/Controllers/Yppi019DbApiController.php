<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class Yppi019DbApiController extends Controller
{
    private function flaskBase(): string
    {
        // Pastikan env YPPI019_BASE/FLASK_BASE menunjuk ke host:port Flask yang AKTIF
        return rtrim(env('YPPI019_BASE', env('FLASK_BASE', 'http://127.0.0.1:5035')), '/');
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
        $aufnr = trim((string) $req->input('aufnr', ''));
        $pernr = trim((string) $req->input('pernr', ''));
        $arbpl = trim((string) $req->input('arbpl', ''));

        if ($aufnr === '') return response()->json(['ok' => false, 'error' => 'aufnr wajib'], 400);
        if ($pernr === '') return response()->json(['ok' => false, 'error' => 'pernr wajib'], 400);

        $body = ['aufnr' => $aufnr, 'pernr' => $pernr];
        if ($arbpl !== '') $body['arbpl'] = $arbpl;

        try {
            $res = Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(120)
                ->post($this->flaskBase() . '/api/yppi019/sync', $body);

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
                ->acceptJson()->timeout(300)
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
        $limit    = (int) $req->query('limit', 100);
        $pernr    = trim((string) $req->query('pernr', ''));
        $arbpl    = $req->query('arbpl');
        $autoSync = filter_var($req->query('auto_sync', '1'), FILTER_VALIDATE_BOOL);

        if (!$aufnr) return response()->json(['ok' => false, 'error' => 'param aufnr wajib'], 400);
        if ($pernr === '') return response()->json(['ok' => false, 'error' => 'param pernr wajib'], 400);

        $base = $this->flaskBase();

        $res = Http::acceptJson()->timeout(30)->get($base . '/api/yppi019', [
            'aufnr' => $aufnr,
            'pernr' => $pernr,
            'limit' => $limit,
        ]);
        $rows = $res->ok() ? ($res->json('rows') ?? []) : [];

        if ($autoSync && count($rows) === 0) {
            $payload = ['aufnr' => $aufnr, 'pernr' => $pernr];
            if (!empty($arbpl)) $payload['arbpl'] = $arbpl;

            $sync = Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(120)
                ->post($base . '/api/yppi019/sync', $payload);

            if (!$sync->ok() || !($sync->json('ok') ?? false)) {
                $err = $sync->json('error') ?? $sync->body();
                return response()->json(['ok' => false, 'error' => 'sync_failed: ' . $err], 502);
            }

            $res = Http::acceptJson()->timeout(30)->get($base . '/api/yppi019', [
                'aufnr' => $aufnr,
                'pernr' => $pernr,
                'limit' => $limit,
            ]);

            if (!$res->ok()) {
                $bodyArr = $res->json();
                $err = is_array($bodyArr)
                    ? ($bodyArr['error'] ?? $bodyArr['message'] ?? json_encode($bodyArr))
                    : $res->body();
                return response()->json(['ok' => false, 'error' => $err], $res->status());
            }
            $rows = $res->json('rows') ?? [];
        }

        return response()->json([
            'ok' => true,
            'T_DATA1' => $rows,
            'RETURN' => [[
                'TYPE' => 'S',
                'MESSAGE' => count($rows) ? 'Loaded from MySQL' : 'No data',
                'ID' => '',
                'NUMBER' => '000',
                'PARAMETER' => '',
                'ROW' => 0,
                'SYSTEM' => ''
            ]],
        ]);
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
        ]);

        $payload['vornr'] = $this->padVornr($payload['vornr'] ?? null);

        if (!empty($payload['meinh'])) {
            $payload['meinh'] = $this->mapUnitForSap($payload['meinh']);
        }

        // =========================================================================
        // PERUBAHAN: Baris di bawah ini dikomentari/dihapus.
        // Biarkan Flask yang menangani format angka untuk SAP vs Database.
        // =========================================================================
        // $payload['psmng'] = $this->formatQtyForSap($payload['psmng'], $payload['meinh'] ?? '');

        try {
            $res = Http::withHeaders($this->sapHeaders())
                ->acceptJson()->timeout(60)
                ->post($this->flaskBase() . '/api/yppi019/confirm', $payload);

            return response($res->body(), $res->status())
                ->header('Content-Type', $res->header('Content-Type', 'application/json'));
        } catch (ConnectionException $e) {
            return response()->json(['ok' => false, 'error' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateQtySpx(Request $request)
    {
        $request->validate([
            'aufnr'   => 'required|string|max:20',
            'vornr'   => 'required|string|max:10',
            'charg'   => 'required|string|max:20',
            'qty_spx' => 'required|numeric|min:0',
        ]);

        try {
            $response = Http::withHeaders($this->sapHeaders())
                ->acceptJson()
                ->post($this->flaskBase() . '/api/yppi019/update_qty_spx', [
                    'aufnr'   => $request->input('aufnr'),
                    'vornrx'  => $this->padVornr($request->input('vornr')),
                    'charg'   => $request->input('charg'),
                    'qty_spx' => $request->input('qty_spx'),
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'QTY_SPX berhasil diperbarui.',
                    'data'    => $response->json(),
                ]);
            } else {
                $errorMessage = $response->json('error') ?? 'Gagal memperbarui data.';
                return response()->json(['success' => false, 'message' => $errorMessage], $response->status());
            }
        } catch (ConnectionException $e) {
            return response()->json(['success' => false, 'message' => 'Flask tidak dapat dihubungi: ' . $e->getMessage()], 502);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()], 500);
        }
    }
}
