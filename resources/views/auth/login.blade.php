@extends('layout')

@section('content')
{{-- Bagian header --}}
<div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC41Ij48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
    <div class="relative px-6 py-16">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-4">
                <img src="{{ asset('images/kmi.jpg') }}" alt="Company Logo" class="mx-auto w-20 h-20 object-contain rounded-xl p-0.5 bg-white">
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Halaman Login</h1>
            <p class="text-base text-white/80">Silakan masuk untuk melanjutkan</p>
        </div>
    </div>
</div>

{{-- Bagian Form Login --}}
<div class="px-6 py-10 mt-6">
    <div class="w-full max-w-md mx-auto bg-white rounded-3xl shadow-xl border border-slate-200/50 overflow-hidden">
        <div class="p-8 space-y-4">
            <h1 class="text-2xl font-semibold text-slate-800 mb-6 text-center">Masuk</h1>

            @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-6" id="loginForm">
                @csrf

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700">Email</label>
                    <div class="relative group">
                        <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                class="flex-1 outline-none bg-transparent text-sm placeholder-slate-400 font-medium"
                                placeholder="you@example.com">
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700">Password</label>
                    <div class="relative group">
                        <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                            <div class="flex-shrink-0">
                                <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2" />
                                    </svg>
                                </div>
                            </div>
                            <input id="password" name="password" type="password" required
                                class="flex-1 outline-none bg-transparent text-sm placeholder-slate-400 font-medium"
                                placeholder="">
                        </div>
                    </div>
                </div>

                {{-- ====== Geolocation (hidden) ====== --}}
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
                <input type="hidden" name="acc" id="acc">
                <div id="geo-msg" class="text-xs text-slate-600"></div>
                {{-- =================================== --}}

                {{-- <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                        Ingat saya
                    </label>
                </div> --}}

                <button type="submit" id="btnLogin"
                    class="w-full py-2.5 rounded-xl bg-gradient-to-r from-green-700 to-blue-900 text-white font-medium hover:from-green-800 hover:to-blue-900 transition duration-200 disabled:opacity-60"
                    disabled>
                    Masuk
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ===== Injeksi daftar kantor dari config ke JS ===== --}}
@php
$OFFICES = collect(config('office.sites', []))
->filter() // buang null entry
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

{{-- ===== Script Geolocation ===== --}}
<script>
    (function() {
        const btn = document.getElementById('btnLogin');
        const msg = document.getElementById('geo-msg');
        const latEl = document.getElementById('lat');
        const lngEl = document.getElementById('lng');
        const accEl = document.getElementById('acc');

        // OFFICES berasal dari config/office.php (sudah diolah di @php di atas)
        const OFFICES = @json($OFFICES);

        // Haversine meter
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
                if (!best || d < best.distance) best = {
                    ...s,
                    distance: d
                };
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
                const {
                    latitude,
                    longitude,
                    accuracy
                } = pos.coords;
                latEl.value = latitude;
                lngEl.value = longitude;
                accEl.value = Math.round(accuracy || 0);

                const near = nearestOffice(latitude, longitude);
                if (near) {
                    const jarak = Math.round(near.distance);
                    const batas = near.radius_m || 0;
                    const dalam = batas ? (near.distance <= batas) : true;

                    // Tampilkan jarak ke kantor TERDEKAT (Semarang / Sidoarjo mana yang lebih dekat)
                    msg.innerHTML =
                        `Lokasi diperoleh — Terdekat: <b>${near.name}</b> ` +
                        `~<b>${jarak} m</b> (batas ${batas} m)` +
                        (dalam ? '' : ' <span class="text-red-600 font-medium">[di luar area]</span>');

                    // Opsional: nonaktifkan tombol bila di luar radius (server tetap validasi ulang)
                    btn.disabled = !dalam;
                } else {
                    // Fallback
                    msg.textContent = `Lokasi diperoleh (~${Math.round(accuracy)} m).`;
                    btn.disabled = false;
                }
            },
            function(err) {
                msg.textContent = 'Tidak bisa mengambil lokasi: ' + (err.message || 'izin ditolak');
                btn.disabled = true;
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
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
{{-- ============================= --}}
@endsection