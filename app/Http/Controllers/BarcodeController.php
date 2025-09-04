<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class BarcodeController extends Controller
{
    private string $sapBridge = 'http://127.0.0.1:5051'; // sesuaikan host

    public function page() {
        return view('scan');
    }

    public function fetch($code) {
        $res = Http::timeout(10)->get($this->sapBridge.'/api/material', [
            'barcode' => $code
        ]);
        if ($res->failed()) {
            return response()->json(['ok'=>false,'error'=>'Gagal hubungi SAP Bridge'], 502);
        }
        return response()->json($res->json());
    }

    public function show(string $code)
    {
        $bridge = rtrim(config('services.sap_bridge.url'), '/');

        try {
            $resp = Http::timeout(20)->get("$bridge/api/material", [
                'aufnr' => $code, // bridge sudah handle zfill 12
            ]);
        } catch (ConnectionException $e) {
            return response()->json([
                'ok' => false,
                'error' => 'SAP bridge tidak bisa dihubungi',
                'detail' => $e->getMessage(),
            ], 502);
        }

        if (!$resp->ok()) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'error' => 'Bridge error '.$resp->status(),
            ]);
        }

        $b = $resp->json();
        if (!($b['ok'] ?? false)) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'error' => $b['message'] ?? $b['error'] ?? 'Data tidak ditemukan',
            ]);
        }

        // Ambil baris data (prioritas 'data', fallback T_DATA1[0])
        $row = $b['data'] ?? ($b['T_DATA1'][0] ?? null);
        if (!$row) {
            return response()->json(['ok' => true, 'found' => false]);
        }

        // Normalisasi & mapping untuk FE (sesuai screenshot)
        $data = [
            'AUFNR'   => $row['AUFNR'] ?? $code,
            'ARBPL'   => $row['ARBPL'] ?? ($row['ARBPL0'] ?? null),
            'VORNR'   => $row['VORNR'] ?? ($row['VORNRX'] ?? null),
            'LTXA1'   => $row['LTXA1'] ?? null,
            'DISPO'   => $row['DISPO'] ?? null,
            'WERKS'   => $row['WERKS'] ?? null,
            'CHARG'   => $row['CHARG'] ?? null,
            'MATNR'   => $row['MATNR'] ?? ($row['MATNRX'] ?? null),
            'MAKTX'   => $row['MAKTX'] ?? null,
            'QTY_SPK' => $row['QTY_SPK'] ?? null,
            'QTY_SPX' => $row['QTY_SPX'] ?? null,
            'WEMNG'   => $row['WEMNG'] ?? null,
            'MEINS'   => $row['MEINS'] ?? ($row['MEINH'] ?? null), // FE pakai MEINS
            'PERNR'   => $row['PERNR'] ?? null,
            'SNAME'   => $row['SNAME'] ?? null,
            'GSTRP'   => $row['GSTRP'] ?? null,
            'GLTRP'   => $row['GLTRP'] ?? null,
        ];

        return response()->json([
            'ok'    => true,
            'found' => true,
            'data'  => $data,
        ]);
    }

    public function confirm() { return response()->json(['ok'=>true]); }


    
}
