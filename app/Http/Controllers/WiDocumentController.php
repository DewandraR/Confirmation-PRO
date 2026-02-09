<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WiDocumentController extends Controller
{
    public function materialFromWi(Request $request)
    {
        // ✅ Ambil NIK dari body JSON ATAU query (biar kompatibel GET/POST)
        $nik = trim((string)(
            $request->input('nik')
            ?? $request->query('nik')
            ?? $request->input('pernr')
            ?? $request->query('pernr', '')
        ));

        // Ambil WI dari body JSON / query:
        $rawSingleIn = $request->input('wi_code', null);
        $rawManyIn   = $request->input('wi_codes', null);

        $rawSingleQ  = $request->query('wi_code', null);
        $rawManyQ    = $request->query('wi_codes', null);

        $rawSingle = $rawSingleIn ?? $rawSingleQ;
        $rawMany   = $rawManyIn   ?? $rawManyQ;

        $codes = [];

        if (is_array($rawSingle)) {
            $codes = $rawSingle;
        } elseif (is_string($rawMany) && $rawMany !== '') {
            $codes = preg_split('/[,\s;]+/', $rawMany, -1, PREG_SPLIT_NO_EMPTY);
        } elseif (is_string($rawSingle) && $rawSingle !== '') {
            $codes = [$rawSingle];
        }

        $codes = array_values(array_unique(array_filter(array_map('trim', $codes))));

        // ✅ Validasi: minimal salah satu ada (nik atau wi_code(s))
        if ($nik === '' && empty($codes)) {
            return response()->json(['error' => 'nik atau wi_code/wi_codes wajib diisi'], 422);
        }

        $baseUrl = config('services.wi_api.base_url'); // https://cohv.kmifilebox.com/api
        $token   = config('services.wi_api.token');

        $rows   = [];
        $errors = [];

        // helper untuk normalisasi & push rows (dengan filter nik defensif)
        $pushRows = function(array $docs) use (&$rows, $nik) {
            foreach ($docs as $doc) {
                $plantCode = $doc['plant_code'] ?? null;

                $docDate = isset($doc['document_date'])
                    ? Carbon::parse($doc['document_date'])->format('Y-m-d')
                    : null;

                $expDate = isset($doc['expired_at'])
                    ? Carbon::parse($doc['expired_at'])->format('Y-m-d')
                    : null;

                // ✅ ambil item dari key yang benar
                $items = $doc['history_wi_item'] ?? $doc['pro_items'] ?? [];
                if (!is_array($items)) $items = [];

                foreach ($items as $item) {
                    if ($nik !== '') {
                        $itemNik = trim((string)($item['nik'] ?? ''));
                        if ($itemNik !== $nik) continue;
                    }

                    $itemStart = !empty($item['ssavd'])
                        ? Carbon::parse($item['ssavd'])->format('Y-m-d')
                        : $docDate;

                    $itemFinish = !empty($item['sssld'])
                        ? Carbon::parse($item['sssld'])->format('Y-m-d')
                        : $expDate;

                    $matNumber = $item['material_number'] ?? null;
                    if (is_string($matNumber)) {
                        $matNumber = trim($matNumber);
                        if ($matNumber !== '' && ctype_digit($matNumber)) {
                            $matNumber = ltrim($matNumber, '0');
                        }
                    }

                    $assigned  = (float)($item['assigned_qty'] ?? $item['qty_order'] ?? 0);
                    $confirmed = (float)($item['confirmed_qty'] ?? 0);
                    $remarkQty = (float)($item['remark_qty'] ?? 0);

                    $maxQty = max(0, $assigned - $confirmed - $remarkQty);

                    $rows[] = [
                        'AUFNR'   => $item['aufnr'] ?? null,
                        'VORNR'   => $item['vornr'] ?? null,
                        'PERNR'   => $item['nik']   ?? null,
                        'SNAME'   => $item['operator_name'] ?? null,
                        'MEINH'   => $item['uom']   ?? 'ST',

                        'QTY_SPK'    => $assigned,
                        'WEMNG'      => $confirmed,
                        'REMARK_QTY' => $remarkQty,
                        'QTY_SPX'    => $maxQty,
                        'MAX_QTY'    => $maxQty,

                        'MAKTX0'  => $item['material_desc'] ?? null,
                        'MAKTX'   => $item['material_desc'] ?? null,
                        'MATNRX'  => $matNumber,

                        'DISPO'   => $item['dispo'] ?? null,
                        'STEUS'   => $item['steus'] ?? null,

                        'KDAUF'   => $item['kdauf'] ?? null,
                        'KDPOS'   => $item['kdpos'] ?? null,

                        // ✅ supaya FE bisa tampil WC Induk/WC Anak
                        'ARBPL0'  => $item['parent_wc'] ?? ($doc['workcenter'] ?? null),
                        'WC_CHILD'=> $item['child_wc'] ?? null,

                        // optional status utk filter non-WI (walau WI disembunyikan)
                        'STATS2'  => $item['status_pro_wi'] ?? ($item['status'] ?? null),

                        'WERKS'   => $plantCode,
                        
                        'LONGSHIFT' => (int)($doc['longshift'] ?? $item['longshift'] ?? 0),

                        'SSAVD'   => $itemStart,
                        'SSSLD'   => $itemFinish,
                        'GSTRP'   => $itemStart,
                        'GLTRP'   => $itemFinish,

                        // ✅ typo fix: takt_time
                        'LTIMEX'  => $item['calculated_takt_time'] ?? null,

                        'WI_CODE' => $doc['wi_code'] ?? null,
                    ];
                }
            }
        };

        // ============================
        // ✅ MODE BARU: nik-only
        // ============================
        if ($nik !== '' && empty($codes)) {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->post($baseUrl . '/wi/document/get', [
                'nik' => $nik,
            ]);

            if (!$response->ok()) {
                return response()->json([
                    'error' => 'Gagal mengambil WI by NIK',
                    'details' => [
                        'http_status' => $response->status(),
                        'body' => $response->json(),
                    ]
                ], 502);
            }

            $data = $response->json();

            if (($data['status'] ?? null) !== 'success') {
                return response()->json([
                    'error' => 'Response WI API tidak success',
                    'details' => $data
                ], 502);
            }

            $docs = $data['wi_documents'] ?? (isset($data['wi_document']) ? [$data['wi_document']] : []);
            if (empty($docs)) {
                return response()->json([
                    'error' => "Tidak ada WI untuk NIK $nik",
                ], 404);
            }

            // ambil daftar kode WI dari docs
            $codes = array_values(array_unique(array_filter(array_map(fn($d) => trim((string)($d['wi_code'] ?? '')), $docs))));

            // push rows (akan tetap terfilter nik karena filter di $pushRows)
            $pushRows($docs);
        }

        // ============================
        // MODE LAMA: wi_code(s)
        // ============================
        if (!empty($codes)) {
            foreach ($codes as $code) {
                $body = ['wi_code' => $code];
                if ($nik !== '') $body['nik'] = $nik; // ✅ opsional: supaya backend ikut filter

                $response = Http::withHeaders([
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])->post($baseUrl . '/wi/document/get', $body);

                if (!$response->ok()) {
                    $errors[] = [
                        'wi_code'     => $code,
                        'http_status' => $response->status(),
                        'body'        => $response->json(),
                    ];
                    continue;
                }

                $data = $response->json();

                if (($data['status'] ?? null) !== 'success') {
                    $errors[] = [
                        'wi_code'     => $code,
                        'http_status' => $response->status(),
                        'body'        => $data,
                    ];
                    continue;
                }

                $docs = $data['wi_documents'] ?? (isset($data['wi_document']) ? [$data['wi_document']] : []);
                if (empty($docs)) {
                    $errors[] = [
                        'wi_code'     => $code,
                        'http_status' => $response->status(),
                        'body'        => $data,
                    ];
                    continue;
                }

                $pushRows($docs);
            }
        }

        if (empty($rows)) {
            return response()->json([
                'error'   => 'Tidak ada data PRO/WI untuk input yang diberikan.',
                'details' => $errors,
            ], 404);
        }

        // ✅ Tambahkan wi_codes supaya FE gampang bikin header "MULTI WI (...)"
        return response()->json([
            'T_DATA1'   => $rows,
            'wi_codes'  => $codes,           // <--- ini yang paling enak buat FE
            'WI_META'   => [
                'nik'    => $nik,
                'codes'  => $codes,
                'errors' => $errors,
            ],
        ]);
    }
    public function getByNik(Request $request)
    {
        $nik = trim((string)(
            $request->input('nik')
            ?? $request->query('nik')
            ?? $request->input('pernr')
            ?? $request->query('pernr', '')
        ));
        if ($nik === '') {
            return response()->json(['status' => 'error', 'message' => 'nik wajib diisi'], 422);
        }

        $baseUrl = config('services.wi_api.base_url'); // contoh: https://cohv.kmifilebox.com/api
        $token   = config('services.wi_api.token');

        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->timeout(30)->post($baseUrl . '/wi/document/get', [
            'nik' => $nik,
        ]);

        if (!$response->ok()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ditemukan WI aktif untuk NIK ini.',
                'details' => $response->json(),
            ], $response->status());
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 'success') {
            return response()->json([
                'status' => 'error',
                'message' => $data['message'] ?? 'WI API tidak sukses',
                'details' => $data,
            ], 422);
        }

        $docs = $data['wi_documents'] ?? [];
        $codes = array_values(array_unique(array_filter(array_map(
            fn($d) => $d['wi_code'] ?? null,
            $docs
        ))));

        return response()->json([
            'status' => 'success',
            'nik' => $nik,
            'wi_codes' => $codes,
            'wi_documents' => $docs, // optional kalau FE butuh meta
        ]);
    }
}
