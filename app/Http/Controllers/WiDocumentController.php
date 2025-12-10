<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WiDocumentController extends Controller
{
    public function materialFromWi(Request $request)
    {
        // NIK sekarang wajib
        $pernr = trim((string) $request->query('pernr', ''));
        if ($pernr === '') {
            return response()->json(['error' => 'pernr (NIK) wajib diisi'], 422);
        }

        // Bisa dapat:
        // - wi_code=WIH0000001
        // - wi_codes=WIH0000001,WIH0000002
        // - wi_code[]=WIH0000001&wi_code[]=WIH0000002
        $rawSingle = $request->query('wi_code');
        $rawMany   = $request->query('wi_codes');

        $codes = [];

        if (is_array($rawSingle)) {
            // kasus wi_code[]=...
            $codes = $rawSingle;
        } elseif (is_string($rawMany) && $rawMany !== '') {
            // wi_codes=WIH0000001,WIH0000002 atau dipisah spasi
            $codes = preg_split('/[,\s;]+/', $rawMany, -1, PREG_SPLIT_NO_EMPTY);
        } elseif (is_string($rawSingle) && $rawSingle !== '') {
            // fallback: single wi_code=...
            $codes = [$rawSingle];
        }

        // bersihkan
        $codes = array_values(array_unique(array_filter(array_map('trim', $codes))));
        if (empty($codes)) {
            return response()->json(['error' => 'wi_code atau wi_codes wajib diisi'], 422);
        }

        $baseUrl = config('services.wi_api.base_url');
        $token   = config('services.wi_api.token');

        $rows   = [];
        $errors = [];

        foreach ($codes as $code) {
            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->post($baseUrl . '/wi/document/get', [
                'wi_code' => $code,
            ]);

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

            $doc = $data['wi_document'] ?? null;
            if (!$doc) {
                $errors[] = [
                    'wi_code'     => $code,
                    'http_status' => $response->status(),
                    'body'        => $data,
                ];
                continue;
            }

            $plantCode = $doc['plant_code'] ?? null;

            $docDate = isset($doc['document_date'])
                ? Carbon::parse($doc['document_date'])->format('Y-m-d')
                : null;

            $expDate = isset($doc['expired_at'])
                ? Carbon::parse($doc['expired_at'])->format('Y-m-d')
                : null;

            foreach ($doc['pro_items'] ?? [] as $item) {
                // FILTER: hanya data untuk NIK yang login
                if (($item['nik'] ?? null) !== $pernr) {
                    continue;
                }

                $qtyOrder  = (float)($item['assigned_qty']  ?? 0);
                $confirmed = (float)($item['confirmed_qty'] ?? 0);

                // material_number tanpa leading zero
                $matNumber = $item['material_number'] ?? null;
                if ($matNumber !== null) {
                    $matNumber = ltrim($matNumber, '0');
                }

                $rows[] = [
                    // === field yang dipakai untuk konfirmasi ===
                    'AUFNR'  => $item['aufnr'] ?? null,   // IV_AUFNR
                    'VORNR'  => $item['vornr'] ?? null,   // IV_VORNR
                    'PERNR'  => $item['nik']   ?? null,   // IV_PERNR (NIK)
                    'SNAME'  => $item['name']  ?? null,   // Nama Operator
                    'MEINH'  => $item['uom']   ?? 'ST',   // IV_MEINH

                    // Qty PRO & stok
                    'QTY_SPK' => $qtyOrder,              // Qty PRO (untuk batas)
                    'QTY_SPX' => $qtyOrder,
                    'WEMNG'   => $confirmed,

                    // Deskripsi material
                    'MAKTX0' => $item['material_desc'] ?? null,
                    'MAKTX'  => $item['material_desc'] ?? null,
                    'MATNRX' => $matNumber,

                    // MRP & Control Key
                    'DISPO'  => $item['dispo'] ?? null,
                    'STEUS'  => $item['steus'] ?? null,

                    // Sales Order / Item
                    'KDAUF'  => $item['kdauf'] ?? null,
                    'KDPOS'  => $item['kdpos'] ?? null,

                    // Work Center & Plant
                    'ARBPL0' => $item['workcenter_induk'] ?? null,
                    'WERKS'  => $plantCode,

                    // Start / Finish (plan) → boleh dipakai sebagai referensi,
                    // tapi untuk konfirmasi kita tetap pakai BUDAT = hari ini
                    'SSAVD'  => $docDate,
                    'SSSLD'  => $expDate,
                    'GSTRP'  => $docDate,
                    'GLTRP'  => $expDate,

                    // Tak time (menit)
                    'LTIMEX' => $item['calculated_tak_time'] ?? null,

                    // optional: simpan kode WI sumber (buat info di FE kalau mau)
                    'WI_CODE' => $doc['wi_code'] ?? $code,
                ];
            }
        }

        if (empty($rows)) {
            // tidak ada baris untuk NIK ini dari semua WI
            return response()->json([
                'error'   => 'Tidak ada data PRO untuk NIK ini dari kode WI yang diberikan.',
                'details' => $errors,
            ], 404);
        }

        return response()->json([
            'T_DATA1'  => $rows,
            // opsional, kalau mau di-debug di FE
            'WI_META' => [
                'codes'  => $codes,
                'errors' => $errors,
            ],
        ]);
    }

}
