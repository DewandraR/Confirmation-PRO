<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WiDocumentController extends Controller
{
    public function materialFromWi(Request $request)
    {
        $wiCode = $request->query('wi_code');

        if (!$wiCode) {
            return response()->json(['error' => 'wi_code wajib diisi'], 422);
        }

        $baseUrl = config('services.wi_api.base_url');
        $token   = config('services.wi_api.token');

        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->post($baseUrl.'/wi/document/get', [
            'wi_code' => $wiCode,
        ]);

        if (!$response->ok()) {
            return response()->json([
                'error'       => 'Gagal mengambil dokumen WI',
                'http_status' => $response->status(),
                'body'        => $response->json(),
            ], $response->status());
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 'success') {
            return response()->json([
                'error' => 'WI API mengembalikan status bukan "success"',
                'body'  => $data,
            ], 500);
        }

        $doc = $data['wi_document'] ?? null;
        if (!$doc) {
            return response()->json(['error' => 'wi_document kosong'], 404);
        }

        $plantCode = $doc['plant_code'] ?? null;

        // document_date & expired_at diubah ke yyyy-mm-dd
        $docDate = isset($doc['document_date'])
            ? Carbon::parse($doc['document_date'])->format('Y-m-d')
            : null;

        $expDate = isset($doc['expired_at'])
            ? Carbon::parse($doc['expired_at'])->format('Y-m-d')
            : null;

        $rows = [];

        foreach ($doc['pro_items'] ?? [] as $item) {
            $qtyOrder  = (float)($item['assigned_qty']      ?? 0);
            $confirmed = (float)($item['confirmed_qty']  ?? 0); // null → 0

            // material_number tanpa leading zero
            $matNumber = $item['material_number'] ?? null;
            if ($matNumber !== null) {
                $matNumber = ltrim($matNumber, '0');
            }

            $rows[] = [
                // ====== mapping ke struktur yang dipakai detail.js ======
                'AUFNR'  => $item['aufnr'] ?? null,           // PRO
                'VORNR'  => $item['vornr'] ?? null,

                'PERNR'  => $item['nik']   ?? null,           // NIK Operator
                'SNAME'  => $item['name']  ?? null,           // Nama Operator
                'MEINH'  => $item['uom']   ?? 'ST',

                // Qty PRO & stok untuk hitung maks:
                // max = assigned_qty - confirmed_qty
                'QTY_SPK' => $qtyOrder,                       // Qty PRO
                'QTY_SPX' => $qtyOrder,                       // stok SPX = assigned_qty
                'WEMNG'   => $confirmed,                      // qty yang sudah confirm

                // Deskripsi FG & Description sama2 material_desc
                'MAKTX0' => $item['material_desc'] ?? null,   // Deskripsi FG
                'MAKTX'  => $item['material_desc'] ?? null,   // Description

                'MATNRX' => $matNumber,                       // Material tanpa leading zero

                // MRP & Control Key
                'DISPO'  => $item['dispo'] ?? null,           // MRP
                'STEUS'  => $item['steus'] ?? null,           // Control Key

                // Sales Order / Item
                'KDAUF'  => $item['kdauf'] ?? null,           // SO
                'KDPOS'  => $item['kdpos'] ?? null,           // Item (000280 dll)

                // Work Center
                'ARBPL0' => $item['workcenter_induk'] ?? null,
                'WERKS'  => $plantCode,

                // Start / Finish date (plan) → dari document_date & expired_at
                'SSAVD'  => $docDate,
                'SSSLD'  => $expDate,
                'GSTRP'  => $docDate,
                'GLTRP'  => $expDate,

                // Menit / tak time
                'LTIMEX' => $item['calculated_tak_time'] ?? null,
            ];
        }

        return response()->json([
            'T_DATA1' => $rows,
        ]);
    }
}
