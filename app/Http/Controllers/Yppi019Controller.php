<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Yppi019Data;

class Yppi019Controller extends Controller
{
    private string $sapBridge;

    public function __construct()
    {
        $port = env('SAP_BRIDGE_PORT', 5051);
        $this->sapBridge = "http://127.0.0.1:{$port}";
    }

    // tampilkan data singkat (opsional)
    public function index(Request $req)
    {
        $aufnr = $req->query('aufnr');
        $query = Yppi019Data::orderByDesc('id');

        if ($aufnr) {
            $query->where('AUFNR', $aufnr);
        }

        $rows = $query->limit(50)->get();
        return view('yppi019.index', compact('rows'));
    }

    // kirim konfirmasi ke proxy API lokal (yang meneruskan ke Flask)
    public function confirm(Request $req)
    {
        $payload = $req->only(['aufnr','vornr','pernr','psmng','meinh','gstrp','gltrp','budat','charg']);

        $res = Http::acceptJson()
            ->timeout(30)
            ->post(url('/api/yppi019/confirm'), $payload);

        if ($res->failed()) {
            return back()->with('error', 'Gagal konfirmasi: '.$res->body());
        }

        $json = $res->json();
        if (!($json['ok'] ?? false)) {
            return back()->with('error', 'SAP Error: '.($json['error'] ?? 'Unknown'));
        }

        return back()->with('success', 'Konfirmasi berhasil: '.json_encode($json['sap_return']));
    }
}
