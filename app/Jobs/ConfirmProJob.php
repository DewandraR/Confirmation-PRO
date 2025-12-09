<?php

namespace App\Jobs;

use App\Models\Yppi019ConfirmMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Http\Client\ConnectionException;

class ConfirmProJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Durasi maksimum job berjalan (detik) */
    public int $timeout = 180;

    /** Jumlah percobaan ulang oleh queue (set ke 1 agar pesan error tidak tertimpa) */
    public int $tries = 1;

    /** ID baris monitor yang akan diproses */
    public function __construct(public int $monitorId) {}

    /** Base URL Flask bridge */
    private function flaskBase(): string
    {
        return rtrim(env('YPPI019_BASE', env('FLASK_BASE', 'http://127.0.0.1:5036')), '/');
    }

    /** VORNR 4 digit */
    private function padVornr(?string $v): string
    {
        $v = trim((string)($v ?? ''));
        if ($v === '') return '';
        $n = (int) round((float) $v);
        return str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }

    /** Map ST -> PC; default PC */
    private function mapUnitForSap(?string $meinh): string
    {
        $m = strtoupper(trim((string)$meinh));
        return $m === 'ST' ? 'PC' : ($m ?: 'PC');
    }

    private function findWiCodeForOperation(string $aufnr, string $vornr): ?string
    {
        $base  = rtrim(config('services.wi_api.base_url'), '/');
        $token = config('services.wi_api.token');

        if (!$base || !$token) {
            // Kalau config belum di-set, jangan blokir konfirmasi
            return null;
        }

        try {
            $res = Http::withToken($token)
                ->acceptJson()
                ->get($base . '/wi/aufnr/unexpired');

            if (!$res->ok()) {
                Log::warning('WI unexpired API not OK', [
                    'aufnr'  => $aufnr,
                    'vornr'  => $vornr,
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);
                return null; // jangan blokir konfirmasi kalau API error
            }

            $json = $res->json() ?? [];
            if (($json['status'] ?? null) !== 'success') {
                Log::warning('WI unexpired API status != success', [
                    'aufnr'   => $aufnr,
                    'vornr'   => $vornr,
                    'payload' => $json,
                ]);
                return null;
            }

            $data = $json['data'] ?? [];
            if (!is_array($data)) {
                return null;
            }

            // $data bentuknya: { "WIH0000001": [ {aufnr, vornr}, ... ], "WIH0000002": [...] }
            foreach ($data as $wiCode => $items) {
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $auf = (string) ($row['aufnr'] ?? '');
                    $vor = $this->padVornr($row['vornr'] ?? null);

                    if ($auf === $aufnr && $vor === $vornr) {
                        // ketemu WI yg nyangkut ke PRO+VORNR ini
                        return (string) $wiCode;
                    }
                }
            }

        } catch (Throwable $e) {
            Log::warning('WI unexpired API check failed', [
                'aufnr' => $aufnr,
                'vornr' => $vornr,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /** Ambil seluruh entri pesan dari struktur sap_return (RETURN/ET_RETURN/…) */
    private function collectReturnEntries($ret): array
    {
        $out = [];
        if (!is_array($ret)) return $out;

        $cands = ['RETURN', 'ET_RETURN', 'T_RETURN', 'E_RETURN', 'ES_RETURN', 'EV_RETURN'];
        foreach ($cands as $k) {
            $v = $ret[$k] ?? null;
            if (is_array($v)) {
                foreach ($v as $row) {
                    if (is_array($row)) $out[] = $row;
                }
            }
        }

        // sap_return kadang nested bebas → sikat semua array of object
        foreach ($ret as $v) {
            if (is_array($v)) {
                foreach ($v as $row) {
                    if (is_array($row)) $out[] = $row;
                }
            }
        }

        return $out;
    }

    public function handle(): void
    {
        $rec = Yppi019ConfirmMonitor::find($this->monitorId);
        if (!$rec) {
            return;
        }

        // tandai sedang diproses
        $rec->update([
            'status'         => 'PROCESSING',
            'status_message' => null,
        ]);

        $payload = $rec->request_payload ?? [];

        // === DETEKSI WI MODE ===
        $wiMode     = !empty($payload['wi_mode']) && !empty($payload['wi_code']);
        $wiCode     = $payload['wi_code'] ?? null;
        $confirmQty = $payload['confirm_qty'] ?? $rec->qty_confirm; // fallback ke kolom monitor

         // === NON-WI MODE: cek dulu apakah PRO/VORNR ini punya WI unexpired ===
        if (!$wiMode) {
            $currentAufnr = (string) $rec->aufnr;
            $currentVornr = $this->padVornr($rec->vornr);

            $wiForOp = $this->findWiCodeForOperation($currentAufnr, $currentVornr);

            if ($wiForOp) {
                // PRO+VORNR ini wajib dikonfirmasi lewat WI, jadi stop di sini
                $msg = 'PRO tersebut telah memiliki kode WI mohon konfirmasi dengan kode WI "' . $wiForOp . '"';

                $rec->update([
                    'status'         => 'FAILED',
                    'status_message' => $msg,
                    'processed_at'   => now(),
                ]);

                return; // JANGAN lanjut ke SAP / Flask
            }
        }

        // tanggal hari ini & budat dari payload (sudah dipaksa hari ini di controller untuk WI)
        $todayYmd  = now()->format('Ymd');
        $budatYmd  = preg_replace('/\D/', '', (string)($payload['budat'] ?? $todayYmd));

        // 1) Validasi keberadaan kredensial terenkripsi
        if (empty($payload['sap_auth'])) {
            $rec->update([
                'status'         => 'FAILED',
                'status_message' => 'Missing sap_auth in request_payload.',
                'processed_at'   => now(),
            ]);
            return;
        }

        // 2) Decrypt sap_auth (wajib APP_KEY sama antara web & worker)
        try {
            $dec  = Crypt::decryptString($payload['sap_auth']);
            $pair = json_decode($dec, true) ?: [];
        } catch (Throwable $e) {
            Log::error('ConfirmProJob decrypt failed', [
                'monitor_id' => $rec->id,
                'error'      => $e->getMessage(),
            ]);
            $rec->update([
                'status'         => 'FAILED',
                'status_message' => 'Decrypt sap_auth failed: ' . $e->getMessage(),
                'processed_at'   => now(),
            ]);
            return;
        }

        $sapU = (string)($pair['u'] ?? '');
        $sapP = (string)($pair['p'] ?? '');
        if ($sapU === '' || $sapP === '') {
            $rec->update([
                'status'         => 'FAILED',
                'status_message' => 'sap_auth decrypted but empty username/password.',
                'processed_at'   => now(),
            ]);
            return;
        }

        // Jaga supaya kolom sap_user mencerminkan user yang benar
        if ($rec->sap_user !== $sapU) {
            $rec->sap_user = $sapU;
            $rec->save();
        }

        $headers = [
            'X-SAP-Username' => $sapU,
            'X-SAP-Password' => $sapP,
            'Content-Type'   => 'application/json',
        ];

        // === BODY UNTUK FLASK /api/yppi019/confirm ===
        $body = [
            'aufnr'  => $rec->aufnr,
            'vornr'  => $this->padVornr($rec->vornr),
            'pernr'  => $rec->operator_nik,
            'psmng'  => (float) $confirmQty,              // qty input user
            'meinh'  => $this->mapUnitForSap($rec->meinh),
            'budat'  => $budatYmd,                        // posting date
            'arbpl0' => $payload['arbpl0'] ?? null,
            'charg'  => $payload['charg']  ?? null,
        ];

        // === Bedakan WI mode vs non-WI ===
        if ($wiMode) {
            // WI: jangan kirim GSTRP/GLTRP, tapi kirim flag WI
            $body['wi_mode'] = true;
            if (!empty($wiCode)) {
                $body['wi_code'] = $wiCode;
            }
        } else {
            // Non-WI: kirim GSTRP/GLTRP (start/finish = hari ini)
            $body['gstrp'] = $todayYmd;
            $body['gltrp'] = $todayYmd;
        }

        try {
            // 3) Panggil Flask confirm
            $res  = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout($this->timeout)
                ->post($this->flaskBase() . '/api/yppi019/confirm', $body);

            $json = $res->json() ?? [];

            // 4) Deteksi error SAP (TYPE=E/A) atau HTTP non-OK
            $entries   = $this->collectReturnEntries($json['sap_return'] ?? []);
            $hasSapErr = !$res->ok();
            foreach ($entries as $e) {
                $t = strtoupper((string)($e['TYPE'] ?? ''));
                if ($t === 'E' || $t === 'A') {
                    $hasSapErr = true;
                    break;
                }
            }

            if ($hasSapErr) {
                $msg = (string)($json['error'] ?? $json['message'] ?? 'Konfirmasi gagal');
                foreach ($entries as $e) {
                    $t = strtoupper((string)($e['TYPE'] ?? ''));
                    if (($t === 'E' || $t === 'A') && !empty($e['MESSAGE'])) {
                        $msg = (string)$e['MESSAGE'];
                        break;
                    }
                }
                $rec->update([
                    'status'         => 'FAILED',
                    'status_message' => Str::limit($msg, 600),
                    'sap_return'     => $json['sap_return'] ?? null,
                    'processed_at'   => now(),
                ]);
                return;
            }

            // 5) Sukses konfirmasi SAP
            $rec->update([
                'status'         => 'SUCCESS',
                'status_message' => 'Confirmed',
                'sap_return'     => $json['sap_return'] ?? null,
                'processed_at'   => now(),
            ]);

            // === 5b) Jika WI mode → hit API WI /api/wi/pro/complete ===
            if ($wiMode && $wiCode) {
                try {
                    Http::withToken(env('WI_API_TOKEN'))
                        ->acceptJson()
                        ->post('https://cohv.kmifilebox.com/api/wi/pro/complete', [
                            'wi_code'       => $wiCode,
                            'aufnr'         => $rec->aufnr,
                            'confirmed_qty' => $confirmQty,
                        ]);
                } catch (Throwable $e) {
                    // Jangan menggagalkan konfirmasi SAP kalau WI API gagal
                    Log::warning('WI pro/complete gagal', [
                        'monitor_id' => $rec->id,
                        'wi_code'    => $wiCode,
                        'aufnr'      => $rec->aufnr,
                        'qty'        => $confirmQty,
                        'error'      => $e->getMessage(),
                    ]);

                    // opsional: tandai di status_message
                    $rec->status_message = trim(
                        ($rec->status_message ?? '') . ' | WI update gagal: ' . $e->getMessage()
                    );
                    $rec->save();
                }
            }

            // 6) (Opsional) catat backdate jika budat berbeda dari hari ini
            if ($budatYmd !== $todayYmd) {
                try {
                    Http::withHeaders($headers)->acceptJson()->timeout(30)
                        ->post($this->flaskBase() . '/api/yppi019/backdate-log', [
                            'aufnr'        => $rec->aufnr,
                            'vornr'        => $this->padVornr($rec->vornr),
                            'pernr'        => $rec->operator_nik,
                            'qty'          => (float) $confirmQty,
                            'meinh'        => $this->mapUnitForSap($rec->meinh),
                            'budat'        => $budatYmd,
                            'today'        => $todayYmd,
                            'arbpl0'       => $payload['arbpl0'] ?? null,
                            'maktx'        => $payload['maktx'] ?? null,
                            'sap_return'   => $json['sap_return'] ?? null,
                            'confirmed_at' => now()->toIso8601String(),
                        ]);
                } catch (Throwable $e) {
                    Log::warning('ConfirmProJob backdate-log failed', [
                        'monitor_id' => $rec->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

        } catch (ConnectionException $e) {
            Log::error('ConfirmProJob HTTP connection error', [
                'monitor_id' => $rec->id,
                'error'      => $e->getMessage(),
            ]);
            $rec->update([
                'status'         => 'FAILED',
                'status_message' => 'Flask tidak dapat dihubungi: ' . $e->getMessage(),
                'processed_at'   => now(),
            ]);

        } catch (Throwable $e) {
            Log::error('ConfirmProJob unexpected error', [
                'monitor_id' => $rec->id,
                'error'      => $e->getMessage(),
            ]);
            $rec->update([
                'status'         => 'FAILED',
                'status_message' => $e->getMessage(),
                'processed_at'   => now(),
            ]);
        }
    }
}
