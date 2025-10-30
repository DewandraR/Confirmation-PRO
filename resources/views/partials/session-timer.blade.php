{{-- resources/views/partials/session-timer.blade.php --}}
{{-- @php $exp = session('expires_at'); @endphp
@if ($exp)
    <div id="session-timer"
        class="fixed up-4 right-4 z-[50] bg-white/90 backdrop-blur border border-slate-200 shadow-lg rounded-2xl px-3 py-2 text-sm text-slate-700">
        <span class="font-medium">Sisa waktu sesi:</span>
        <span id="timer-mmss" class="font-bold tabular-nums">--:--</span>
        <form id="autoLogoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
    </div>

    <script>
        (function() {
            const exp = new Date("{{ $exp }}").getTime();
            const el = document.getElementById('timer-mmss');
            const form = document.getElementById('autoLogoutForm');

            function mmss(ms) {
                const t = Math.max(0, Math.floor(ms / 1000));
                const m = String(Math.floor(t / 60)).padStart(2, '0');
                const s = String(t % 60).padStart(2, '0');
                return `${m}:${s}`;
            }

            function tick() {
                const left = exp - Date.now();
                if (left <= 0) {
                    el.textContent = '00:00';
                    form.submit(); // auto logout
                    return;
                }
                el.textContent = mmss(left);
            }

            tick();
            setInterval(tick, 1000);
        })();
    </script>
@endif --}}
