<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetailController extends Controller
{
    public function show(Request $req)
    {
        // Ambil dari request attributes (kalau route ini kena middleware sap_auth)
        // kalau belum ada, fallback ke session('sap.username')
        $sapUser = (string) $req->attributes->get('sap_username')
            ?: (string) session('sap.username', '');

        return view('detail', [
            'IV_AUFNR' => $req->query('aufnr'),
            'IV_PERNR' => $req->query('pernr'),
            'IV_ARBPL' => $req->query('arbpl'),
            'sapUser'  => $sapUser,
        ]);
    }
}
