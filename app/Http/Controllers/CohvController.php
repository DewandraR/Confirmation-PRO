<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CohvController extends Controller
{
    public function getMappingForLoggedUser(Request $request)
    {
        $sapId = (string) $request->attributes->get('sap_username'); // dari sap_auth middleware
        if ($sapId === '') {
            return response()->json(['ok' => false, 'error' => 'Sesi SAP habis / belum login'], 440);
        }

        $base  = rtrim(config('services.wi_api.base_url'), '/');   // https://cohv.kmifilebox.com/api
        $token = (string) config('services.wi_api.token');

        if ($token === '') {
            return response()->json(['ok' => false, 'error' => 'WI_API_TOKEN belum di-set'], 500);
        }

        $res = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->post($base . '/cohv/get-mapping', [
                'sap_id' => $sapId,
            ]);

        if (!$res->ok()) {
            return response()->json([
                'ok' => false,
                'error' => 'Mapping API gagal',
                'status' => $res->status(),
                'body' => $res->json() ?? $res->body(),
            ], 502);
        }

        $payload = $res->json() ?? [];

        // Flatten jadi list dropdown: "MRP - Plant"
        $opts = [];
        foreach (($payload['details'] ?? []) as $d) {
            $plant = (string)($d['kategori'] ?? '');
            foreach (($d['kodes'] ?? []) as $k) {
                foreach (($k['mrps'] ?? []) as $mrp) {
                    $mrp = trim((string)$mrp);
                    if ($mrp === '' || $plant === '') continue;

                    $key = $mrp . '|' . $plant;
                    $opts[$key] = [
                        'dispo' => $mrp,      // ✅ biar match YPPI019
                        'mrp'   => $mrp,      // (opsional) kalau masih mau simpan juga
                        'werks' => $plant,
                        'label' => $mrp . ' - ' . $plant,
                    ];
                }
            }
        }

        $payload['mrp_plant_options'] = array_values($opts);

        return response()->json($payload);
    }
}
