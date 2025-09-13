@extends('layout')

@section('content')
{{-- Bagian header (dipadatkan, subjudul tetap tampil) --}}
<div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC41Ij48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
    <div class="relative px-4 py-6 md:px-6 md:py-10">
        <div class="max-w-md mx-auto text-center">
            <div class="mb-2 md:mb-3">
                <img src="{{ asset('images/kmi.jpg') }}" alt="Company Logo" class="mx-auto w-14 h-14 md:w-20 md:h-20 object-contain rounded-xl p-0.5 bg-white">
            </div>
            <h1 class="text-lg md:text-3xl font-bold text-white mb-1 md:mb-2 leading-tight">Halaman Login</h1>
            {{-- Selalu tampil, diperkecil di mobile --}}
            <p class="text-xs md:text-base text-white/80 leading-tight">Silakan masuk menggunakan SAP ID</p>
        </div>
    </div>
</div>

{{-- Bagian Form Login (spacing dipadatkan) --}}
<div class="px-4 py-6 mt-4 md:px-6 md:py-10 md:mt-6">
    <div class="w-full max-w-md mx-auto bg-white rounded-3xl shadow-xl border border-slate-200/50 overflow-hidden">
        <div class="p-6 md:p-8 space-y-4">
            <h1 class="text-xl md:text-2xl font-semibold text-slate-800 mb-4 md:mb-6 text-center">Masuk</h1>

            @if ($errors->any())
            <div class="mb-3 md:mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
                {{ trim(
                    $errors->first('login')
                    ?: $errors->first('location')
                    ?: $errors->first('sap_id')
                    ?: $errors->first()
                ) ?: 'Login SAP gagal. Silakan coba lagi.' }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5 md:space-y-6" id="loginForm" autocomplete="off">
                @csrf

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700">SAP ID</label>
                    <div class="relative group">
                        <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 10-6 0 3 3 0 006 0z" />
                                    </svg>
                                </div>
                            </div>
                            <input id="sap_id" name="sap_id" type="text"
                                   inputmode="text"
                                   autocomplete="username"
                                   autocapitalize="none" autocorrect="off" spellcheck="false" enterkeyhint="go"
                                   class="flex-1 outline-none bg-transparent text-sm placeholder-slate-400 font-medium"
                                   placeholder="Masukkan SAP ID">
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700">Password SAP</label>
                    <div class="relative group">
                        <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                                    </svg>
                                </div>
                            </div>
                            <input id="password" name="password" type="password" required
                                   class="flex-1 outline-none bg-transparent text-sm placeholder-slate-400 font-medium"
                                   placeholder="******" autocomplete="current-password">
                            <button type="button" id="togglePass" class="text-xs text-slate-500 hover:text-slate-700 px-2">Tampil</button>
                        </div>
                    </div>
                </div>

                {{-- ====== Geolocation (hidden) ====== --}}
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
                <input type="hidden" name="acc" id="acc">
                <div id="geo-msg" class="text-xs text-slate-600"></div>
                {{-- =================================== --}}

                <button type="submit" id="btnLogin"
                    class="w-full py-2.5 rounded-xl bg-gradient-to-r from-green-700 to-blue-900 text-white font-medium hover:from-green-800 hover:to-blue-900 transition duration-200 disabled:opacity-60"
                    disabled>
                    Masuk
                </button>
            </form>
        </div>
    </div>

    {{-- ===== Tips penggunaan (DITAMBAHKAN) ===== --}}
    <div class="w-full max-w-md mx-auto mt-3">
        <div class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
            <div class="font-semibold mb-1">Tips penggunaan:</div>
            <ul class="list-disc pl-5 space-y-1">
                <li>Pastikan menggunakan internet/Wi-Fi <span class="font-semibold">perusahaan</span> agar koneksi ke SAP berhasil.</li>
                <li>Jika lokasi tidak akurat, coba matikan lalu nyalakan kembali Wi-Fi/data seluler, kemudian refresh browser.</li>
            </ul>
        </div>
    </div>
    {{-- ===== Akhir tips ===== --}}
</div>

{{-- ===== Injeksi daftar kantor dari config ke JS ===== --}}
@php
$OFFICES = collect(config('office.sites', []))
    ->filter()
    ->map(function ($s) {
        return [
            'name' => $s['name'] ?? ($s['code'] ?? 'Kantor'),
            'lat' => (float) ($s['lat'] ?? 0),
            'lng' => (float) ($s['lng'] ?? 0),
            'radius_m' => (int) ($s['radius_m'] ?? 0),
        ];
    })
    ->values()
    ->all();
@endphp

{{-- ===== Script Geolocation & UX ===== --}}
<script>
(function() {
    const btn   = document.getElementById('btnLogin');
    const msg   = document.getElementById('geo-msg');
    const latEl = document.getElementById('lat');
    const lngEl = document.getElementById('lng');
    const accEl = document.getElementById('acc');
    const pass  = document.getElementById('password');
    const toggle= document.getElementById('togglePass');

    // Toggle password visibility
    toggle.addEventListener('click', () => {
        const show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Sembunyikan' : 'Tampil';
        pass.focus();
    });

    const OFFICES = @json($OFFICES);

    function distMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const toRad = d => d * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function nearestOffice(lat, lng) {
        if (!Array.isArray(OFFICES) || OFFICES.length === 0) return null;
        let best = null;
        for (const s of OFFICES) {
            if (!s || !s.lat || !s.lng) continue;
            const d = distMeters(lat, lng, s.lat, s.lng);
            if (!best || d < best.distance) best = { ...s, distance: d };
        }
        return best;
    }

    const isLocalhost = ['localhost', '127.0.0.1', '::1'].includes(location.hostname);
    if (!window.isSecureContext && !isLocalhost) {
        msg.textContent = 'Lokasi tidak aktif: akses harus HTTPS.';
        btn.disabled = true;
        return;
    }

    if (!('geolocation' in navigator)) {
        msg.textContent = 'Perangkat tidak mendukung geolokasi.';
        btn.disabled = true;
        return;
    }

    msg.textContent = 'Meminta lokasi…';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const { latitude, longitude, accuracy } = pos.coords;
            latEl.value = latitude;
            lngEl.value = longitude;
            accEl.value = Math.round(accuracy || 0);

            const near = nearestOffice(latitude, longitude);
            if (near) {
                const jarak = Math.round(near.distance);
                const batas = near.radius_m || 0;
                const dalam = batas ? (near.distance <= batas) : true;

                msg.innerHTML =
                    `Lokasi diperoleh — Terdekat: <b>${near.name}</b> ` +
                    `~<b>${jarak} m</b> (batas ${batas} m)` +
                    (dalam ? '' : ' <span class="text-red-600 font-medium">[di luar area]</span>');

                // Server tetap validasi ulang; tombol bisa diaktifkan meski di luar radius jika mau.
                btn.disabled = false;
            } else {
                msg.textContent = `Lokasi diperoleh (~${Math.round(accuracy)} m).`;
                btn.disabled = false;
            }
        },
        function(err) {
            msg.textContent = 'Tidak bisa mengambil lokasi: ' + (err.message || 'izin ditolak');
            btn.disabled = true;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );

    // Pengaman ekstra: cegah submit bila koordinat kosong
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        if (!latEl.value || !lngEl.value) {
            e.preventDefault();
            msg.textContent = 'Koordinat belum tersedia. Pastikan akses lokasi diizinkan.';
            btn.disabled = true;
        }
    });
})();
</script>
@endsection
