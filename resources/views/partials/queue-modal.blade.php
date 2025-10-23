{{-- resources/views/partials/queue-modal.blade.php --}}
@php $ticket = session('queue.ticket'); @endphp

@if ($ticket)
    <div id="queue-overlay" class="fixed inset-0 z-[70] bg-black/40 backdrop-blur-sm flex items-center justify-center">
        <div class="w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-base md:text-lg font-semibold text-slate-800">Antrean Login</h3>
            </div>
            <div class="px-5 py-4 space-y-2">
                <p class="text-slate-700">Sedang ada <span id="q-active">—</span> pengguna aktif.</p>
                <p class="text-slate-700">
                    Posisi Anda: <span id="q-pos" class="font-bold">—</span>
                    <span class="text-slate-500">(<span id="q-len">—</span> dalam antrean)</span>
                </p>
                <p id="q-hint" class="text-xs text-slate-500">Halaman ini akan otomatis masuk saat giliran Anda.</p>
            </div>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button id="q-cancel" type="button"
                    class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const ticket = @json($ticket);
            const urlStatus = @json(route('login.queue.status', ['ticket' => $ticket]));
            const urlClaim = @json(route('login.queue.claim'));
            const urlCancel = @json(route('login.queue.cancel'));
            const urlLogin = @json(route('login'));

            const elPos = document.getElementById('q-pos');
            const elLen = document.getElementById('q-len');
            const elAct = document.getElementById('q-active');
            const hint = document.getElementById('q-hint');
            const cancelBtn = document.getElementById('q-cancel');
            const overlay = document.getElementById('queue-overlay');

            let polling = null;
            let cancelling = false;

            async function getStatus() {
                try {
                    const res = await fetch(urlStatus, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const j = await res.json();

                    elPos.textContent = j.position ?? '—';
                    elLen.textContent = j.queue_length ?? '—';
                    elAct.textContent = j.active_count ?? '—';

                    if (j.ready === true) {
                        hint.textContent = 'Giliran Anda. Mengambil slot...';
                        await claim();
                    }
                } catch {
                    /* ignore */
                }
            }

            async function claim() {
                try {
                    const res = await fetch(urlClaim, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        }
                    });
                    const j = await res.json();
                    if (j.ok && j.redirect) {
                        window.location.replace(j.redirect);
                    }
                } catch {
                    /* ignore */
                }
            }

            async function cancelManual() {
                if (cancelling) return;
                cancelling = true;

                // stop polling & lepaskan listener sebelum navigasi
                stopPolling();
                window.removeEventListener('pagehide', pagehideCancel, {
                    capture: true
                });

                try {
                    await fetch(urlCancel, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: new URLSearchParams({
                            ticket
                        })
                    });
                } finally {
                    // sembunyikan modal agar tidak terasa "loop"
                    if (overlay) overlay.remove();
                    // muat ulang ke /login (session tiket sudah dihapus di server)
                    window.location.replace(urlLogin);
                }
            }

            // Dipakai saat tab ditutup/berpindah halaman (tidak blocking)
            function pagehideCancel() {
                if (!ticket || cancelling) return;
                const data = new URLSearchParams({
                    ticket
                });
                try {
                    navigator.sendBeacon(urlCancel, data);
                } catch {
                    /* ignore */
                }
            }

            function startPolling() {
                if (polling) return;
                polling = setInterval(getStatus, 2000);
                getStatus();
            }

            function stopPolling() {
                if (polling) {
                    clearInterval(polling);
                    polling = null;
                }
            }

            cancelBtn.addEventListener('click', cancelManual);
            window.addEventListener('pagehide', pagehideCancel, {
                capture: true
            });

            startPolling();
        })();
    </script>
@endif
