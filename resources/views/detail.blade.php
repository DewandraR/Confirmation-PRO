@extends('layout')

@section('content')
{{-- Header dengan gradasi baru yang lebih kalem --}}
<div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvZ2lkIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
    <div class="relative px-4 py-6">
        <div class="max-w-6xl mx-auto">
            {{-- Tombol Kembali --}}
            <div class="flex items-center gap-2 mb-4">
                <a href="{{ route('scan') }}" class="group flex items-center gap-1 px-3 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-white text-sm transition-all duration-200">
                    <svg class="w-3 h-3 group-hover:-translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
            </div>
            {{-- Judul Halaman --}}
            <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">Detail Barang</h1>
                    <p class="text-sm text-indigo-100 opacity-80">Informasi lengkap data produksi dalam format tabel</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="px-4 py-6 -mt-3 relative z-10">
    <div class="max-w-6xl mx-auto space-y-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 overflow-hidden">
            {{-- Header Bagian Tabel (Menggunakan gradasi baru) --}}
            <div class="bg-gradient-to-r from-green-700 to-blue-900 px-4 py-3 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        {{-- Icon yang disesuaikan --}}
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z" />
                            </svg>
                        </div>
                        <div>
                            <div id="headAUFNR" class="font-bold text-lg text-white">-</div>
                        </div>
                    </div>
                    {{-- Tombol Scan Lain (tetap) --}}
                    <a href="{{ route('scan') }}" class="group flex items-center gap-1 text-xs px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition-colors">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 12h-.01M12 12v4m-4-4h4m-4 0V8a4 4 0 118 0v1.5a4 4 0 01-4 4z" />
                        </svg>
                        Scan Lain
                    </a>
                </div>
            </div>

            {{-- Konten Tabel --}}
            <div id="loading" class="p-6 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-4 border-green-200 border-t-green-600 rounded-full animate-spin"></div>
                    <div class="text-sm text-slate-500 font-medium">Memuat data...</div>
                    <div class="text-xs text-slate-400">Mengambil informasi dari server</div>
                </div>
            </div>

            <div id="content" class="hidden">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 text-green-600 rounded border-slate-300">
                            <label for="selectAll" class="text-sm text-slate-600 font-medium">Pilih Semua</label>
                        </div>
                        <div class="text-xs text-slate-500">
                            Total: <span id="totalItems">0</span> item(s)
                        </div>
                    </div>

                    <div class="overflow-auto max-h-[70vh] rounded-xl border border-slate-200">
                        <table class="w-full">
                            <thead class="bg-green-700/90 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 sticky left-0 bg-green-700/90">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 w-16">No</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px]">Order Number</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Qty Input</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[200px]">Act Desc</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[100px]">MRP</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[100px]">Control Key</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px]">Material</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[200px]">Description</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px]">Work Center</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[100px]">NIK Operator</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Nama Operator</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="bg-white divide-y divide-slate-200">
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Tombol Konfirmasi --}}
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button id="confirm-button" class="px-4 py-2 rounded-lg bg-green-500 hover:bg-green-600 text-white text-sm font-semibold transition-colors disabled:bg-slate-300 disabled:text-slate-500" disabled>
                        Konfirmasi (<span id="selected-count">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Semua Modal (Confirm, Error, Warning, Success) tidak diubah --}}
<div id="confirm-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
        <div class="bg-gradient-to-r from-green-700 to-blue-900 px-4 py-3 border-b border-green-600">
            <h4 class="text-lg font-semibold text-white">Konfirmasi Aksi</h4>
        </div>
        <div class="p-4 space-y-4">
            <p class="text-sm text-slate-700 mb-2">Anda akan mengonfirmasi data berikut:</p>
            <ul id="confirmation-list" class="space-y-2"></ul>
        </div>
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
            <button id="yes-button" type="button" class="px-4 py-2 rounded-lg bg-green-500 hover:bg-green-600 text-white text-sm font-semibold transition-colors">Ya</button>
            <button id="cancel-button" type="button" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold transition-colors">Batal</button>
        </div>
    </div>
</div>

<div id="error-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
        <div class="bg-gradient-to-r from-red-50 to-red-100 px-4 py-3 border-b border-red-200">
            <h4 class="text-lg font-semibold text-red-800">Terjadi Kesalahan</h4>
        </div>
        <div class="p-4 space-y-4">
            <p id="error-message" class="text-sm text-slate-700"></p>
        </div>
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button id="error-ok-button" type="button" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition-colors">OK</button>
        </div>
    </div>
</div>

<div id="warning-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 px-4 py-3 border-b border-yellow-200">
            <h4 class="text-lg font-semibold text-yellow-800">Peringatan</h4>
        </div>
        <div class="p-4 space-y-4">
            <p id="warning-message" class="text-sm text-slate-700"></p>
        </div>
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button id="warning-ok-button" type="button" class="px-4 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold transition-colors">OK</button>
        </div>
    </div>
</div>

<div id="success-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 px-4 py-3 border-b border-emerald-200">
            <h4 class="text-lg font-semibold text-emerald-800">Berhasil Dikonfirmasi</h4>
        </div>
        <div class="p-4 space-y-3">
            <p class="text-sm text-slate-700">Rincian respon SAP:</p>
            <ul id="success-list" class="space-y-2 text-sm"></ul>
        </div>
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button id="success-ok-button" type="button" class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold transition-colors">OK</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>

<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    function apiPost(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
    }

    // ===== Helpers =====
    const padVornr = v => String(parseInt(v || '0', 10)).padStart(4, '0');
    const toYYYYMMDD = (d) => {
        if (!d) {
            const x = new Date();
            return `${x.getFullYear()}${String(x.getMonth()+1).padStart(2,'0')}${String(x.getDate()).padStart(2,'0')}`;
        }
        const s = String(d);
        const m1 = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(s);
        if (m1) return `${m1[3]}${m1[2]}${m1[1]}`;
        const m2 = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (m2) return `${m2[1]}${m2[2]}${m2[3]}`;
        return s.replace(/\D/g, '');
    };

    function getUnitName(unit) {
        const u = String(unit || '').toUpperCase();
        switch (u) {
            case 'ST':
            case 'PC':
            case 'PCS':
            case 'EA':
                return 'PC';
            case 'M3':
                return 'Meter Kubik';
            case 'M2':
                return 'Meter Persegi';
            case 'KG':
                return 'Kilogram';
            default:
                return u;
        }
    }

    function collectSapReturnEntries(ret) {
        const entries = [];
        if (!ret || typeof ret !== 'object') return entries;
        const keys = ['RETURN', 'ET_RETURN', 'T_RETURN', 'E_RETURN', 'ES_RETURN', 'EV_RETURN'];
        for (const k of keys) {
            const v = ret[k];
            if (Array.isArray(v)) entries.push(...v.filter(Boolean));
            else if (v && typeof v === 'object') entries.push(v);
        }
        for (const [, v] of Object.entries(ret)) {
            if (Array.isArray(v)) v.forEach(o => (o && typeof o === 'object' && (o.MESSAGE || o.TYPE || o.ID)) && entries.push(o));
        }
        return entries;
    }

    function hasSapError(ret) {
        const entries = collectSapReturnEntries(ret);
        return entries.some(e => ['E', 'A'].includes(String(e?.TYPE || '').toUpperCase()));
    }

    window.onerror = function(msg, src, line, col) {
        const em = document.getElementById('error-message');
        const mm = document.getElementById('error-modal');
        if (em && mm) {
            em.textContent = `Error JS: ${msg} (${line}:${col})`;
            mm.classList.remove('hidden');
        }
        console.error(msg, src, line, col);
    };

    document.addEventListener("DOMContentLoaded", async () => {
        // --- URL params ---
        const p = new URLSearchParams(location.search);
        const rawList = p.get('aufnrs') || '';
        const single = p.get('aufnr') || '';
        const IV_PERNR = p.get('pernr') || '';
        const IV_ARBPL = p.get('arbpl') || '';
        const IV_WERKS = p.get('werks') || '';

        function ean13CheckDigit(d12) {
            let s = 0,
                t = 0;
            for (let i = 0; i < 12; i++) {
                const n = +d12[i];
                if (i % 2 === 0) s += n;
                else t += n;
            }
            return (10 - ((s + 3 * t) % 10)) % 10;
        }

        function normalizeAufnr(raw) {
            let s = String(raw || '').replace(/\D/g, '');
            if (s.length === 13) {
                const cd = ean13CheckDigit(s.slice(0, 12));
                if (cd === +s[12]) s = s.slice(0, 12);
            }
            return s;
        }

        let AUFNRS = (rawList ? rawList.split(/[,\s]+/) : []).filter(Boolean);
        if (!AUFNRS.length && single) AUFNRS = [single];
        AUFNRS = [...new Set(AUFNRS.map(normalizeAufnr).filter(x => /^\d{12}$/.test(x)))];

        // --- Elemen UI ---
        const headAUFNR = document.getElementById('headAUFNR');
        const content = document.getElementById('content');
        const loading = document.getElementById('loading');
        const tableBody = document.getElementById('tableBody');
        const totalItems = document.getElementById('totalItems');
        const selectAll = document.getElementById('selectAll');
        const confirmButton = document.getElementById('confirm-button');
        const selectedCountSpan = document.getElementById('selected-count');
        const confirmModal = document.getElementById('confirm-modal');
        const confirmationList = document.getElementById('confirmation-list');
        const yesButton = document.getElementById('yes-button');
        const cancelButton = document.getElementById('cancel-button');
        const errorModal = document.getElementById('error-modal');
        const errorMessage = document.getElementById('error-message');
        const errorOkButton = document.getElementById('error-ok-button');
        const warningModal = document.getElementById('warning-modal');
        const warningMessage = document.getElementById('warning-message');
        const warningOkButton = document.getElementById('warning-ok-button');
        const successModal = document.getElementById('success-modal');
        const successList = document.getElementById('success-list');
        const successOkButton = document.getElementById('success-ok-button');

        // Tampilkan PERNR, AUFNR, atau ARBPL
        if (headAUFNR) {
            let headText = '-';
            if (AUFNRS.length > 0) {
                headText = AUFNRS.join(', ');
            } else if (IV_ARBPL) {
                headText = `WC: ${IV_ARBPL}`;
            }
            if (IV_PERNR) {
                headText = `${IV_PERNR} / ${headText}`;
            }
            headAUFNR.textContent = headText.replace(/ \/ -$/, ''); // Hapus trailing slash jika tidak ada data utama
        }

        const showError = (message) => {
            loading.innerHTML = `<div class="flex flex-col items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-sm text-red-600 font-medium">Error: ${message}</div>
                <div class="text-xs text-slate-400">Gagal memuat data dari server</div>
            </div>`;
            loading.classList.remove('hidden');
            content.classList.add('hidden');
        };

        if (AUFNRS.length === 0 && !(IV_ARBPL && IV_PERNR && IV_WERKS)) {
            showError('Parameter tidak lengkap. Harap kembali ke halaman Scan dan isi data dengan benar.');
            return;
        }

        // --- Ambil data ---
        let rowsAll = [],
            failures = [];
        try {
            if (AUFNRS.length > 0) {
                // Logika jika ada AUFNR
                const results = await Promise.allSettled(
                    AUFNRS.map(async (aufnr) => {
                        // --- PERBAIKAN DIMULAI DI SINI ---
                        // Bangun URL dasar dengan parameter wajib
                        let url = `/api/yppi019/material?aufnr=${encodeURIComponent(aufnr)}&pernr=${encodeURIComponent(IV_PERNR)}`;

                        // Tambahkan arbpl dan werks jika ada nilainya
                        if (IV_ARBPL) {
                            url += `&arbpl=${encodeURIComponent(IV_ARBPL)}`;
                        }
                        if (IV_WERKS) {
                            url += `&werks=${encodeURIComponent(IV_WERKS)}`;
                        }
                        // --- PERBAIKAN SELESAI ---

                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        let json;
                        try {
                            json = await res.json();
                        } catch (e) {
                            throw new Error(`Non-JSON (${res.status}): ${(await res.text().catch(()=>'')).slice(0,200)}`);
                        }
                        if (!res.ok) throw new Error(json.error || json.message || `HTTP ${res.status}`);
                        const t = json.T_DATA1;
                        return Array.isArray(t) ? t : (t ? [t] : []);
                    })
                );
                results.forEach(r => r.status === 'fulfilled' ? rowsAll = rowsAll.concat(r.value) : failures.push(r.reason?.message || 'unknown'));
            } else {
                // Logika baru jika hanya ada ARBPL, WERKS, PERNR
                const url = `/api/yppi019/material?arbpl=${encodeURIComponent(IV_ARBPL)}&werks=${encodeURIComponent(IV_WERKS)}&pernr=${encodeURIComponent(IV_PERNR)}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                let json;
                try {
                    json = await res.json();
                } catch (e) {
                    throw new Error(`Non-JSON (${res.status}): ${(await res.text().catch(()=>'')).slice(0,200)}`);
                }
                if (!res.ok) throw new Error(json.error || json.message || `HTTP ${res.status}`);
                const t = json.T_DATA1;
                rowsAll = Array.isArray(t) ? t : (t ? [t] : []);
            }
        } catch (e) {
            errorMessage.textContent = e.message || 'Gagal mengambil data';
            errorModal.classList.remove('hidden');
        } finally {
            loading.classList.add('hidden');
            content.classList.remove('hidden');
        }

        if (!rowsAll.length) {
            tableBody.innerHTML = '<tr><td colspan="12" class="px-4 py-8 text-center text-slate-500">Tidak ada data</td></tr>';
            totalItems.textContent = '0';
            return;
        }

        // --- urutkan & render ---
        rowsAll.sort((a, b) => {
            if ((a.AUFNR || '') !== (b.AUFNR || '')) return (a.AUFNR || '').localeCompare(b.AUFNR || '');
            const va = parseInt(a.VORNRX || a.VORNR || '0', 10) || 0;
            const vb = parseInt(b.VORNRX || b.VORNR || '0', 10) || 0;
            return va - vb;
        });
        totalItems.textContent = String(rowsAll.length);

        tableBody.innerHTML = rowsAll.map((r, i) => {
            const vornr = padVornr(r.VORNRX || r.VORNR || '0');
            const qtySPK = parseFloat(r.QTY_SPK ?? 0);
            const weMng = parseFloat(r.WEMNG ?? 0);
            const qtySPX = parseFloat(r.QTY_SPX ?? 0);
            const sisaSPK = Math.max(qtySPK - weMng, 0);
            const maxAllow = Math.max(0, Math.min(qtySPX, sisaSPK));
            const meinh = (r.MEINH || 'ST').toUpperCase();

            return `<tr class="odd:bg-white even:bg-slate-50 hover:bg-green-50/40 transition-colors"
                data-aufnr="${r.AUFNR || ''}" data-vornr="${vornr}" data-pernr="${r.PERNR || IV_PERNR || ''}"
                data-meinh="${r.MEINH||'ST'}" data-gstrp="${toYYYYMMDD(r.GSTRP)}" data-gltrp="${toYYYYMMDD(r.GLTRP)}"
                data-arbpl0="${r.ARBPL0 || r.ARBPL || IV_ARBPL || '-'}"
                data-maktx="${(r.MAKTX || '-').replace(/"/g,'&quot;')}">
                <td class="px-3 py-3 text-center sticky left-0 bg-inherit border-r border-slate-200">
                    <input type="checkbox" class="row-checkbox w-4 h-4 text-green-600 rounded border-slate-300">
                </td>
                <td class="px-3 py-3 text-center bg-inherit border-r border-slate-200">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-xs font-bold text-green-700 mx-auto">${i+1}</div>
                </td>
                <td class="px-3 py-3 text-sm font-semibold text-slate-900">${r.AUFNR || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700 text-center">
                    <input type="number" name="QTY_SPX" class="w-28 px-2 py-1 text-center rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-green-400/40 focus:border-green-500 text-sm font-mono"
                        value="0" placeholder="0" min="0" data-max="${maxAllow}" data-meinh="${meinh}"
                        step="${meinh==='M3' ? '0.001' : '1'}" inputmode="${meinh==='M3' ? 'decimal' : 'numeric'}"
                        title="Maks: ${maxAllow} (sisa SPK=${sisaSPK}, sisa SPX=${qtySPX})"/>
                    <div class="mt-1 text-[11px] text-slate-400">Maks: <b>${maxAllow}</b> (${getUnitName(meinh)})</div>
                </td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.LTXA1 || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.DISPO || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.STEUS || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.MATNRX || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.MAKTX || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.ARBPL0 || r.ARBPL || IV_ARBPL || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.PERNR || IV_PERNR || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-700">${r.SNAME || '-'}</td>
            </tr>`;
        }).join('');

        // === Checkbox & tombol konfirmasi ===
        const updateConfirmButtonState = () => {
            const count = document.querySelectorAll('.row-checkbox:checked').length;
            selectedCountSpan.textContent = count;
            confirmButton.disabled = count === 0;
        };
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = selectAll.checked);
            updateConfirmButtonState();
        });
        document.addEventListener('change', (e) => {
            if (!e.target.classList.contains('row-checkbox')) return;
            const all = document.querySelectorAll('.row-checkbox'),
                on = document.querySelectorAll('.row-checkbox:checked');
            selectAll.checked = all.length > 0 && all.length === on.length;
            selectAll.indeterminate = on.length > 0 && on.length < all.length;
            updateConfirmButtonState();
        });
        updateConfirmButtonState();

        // === Validasi input QTY_SPX ===
        let pendingResetInput = null,
            isWarningOpen = false;
        document.querySelectorAll('input[name="QTY_SPX"]').forEach(input => {
            input.addEventListener('focus', function() {
                if (this.value === '0') this.value = '';
            });
            input.addEventListener('input', function() {
                if (this.value === '' || this.value === '-' || this.value === '.') return;
                const v = parseFloat(String(this.value).replace(',', '.'));
                const maxAllow = parseFloat(this.dataset.max || '0');
                if (!isNaN(v) && v > maxAllow && !isWarningOpen) {
                    warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
                    pendingResetInput = this;
                    isWarningOpen = true;
                    warningModal.classList.remove('hidden');
                    this.blur();
                }
            });
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') this.value = '0';
                let v = parseFloat(this.value.replace(',', '.') || '0');
                if (isNaN(v)) v = 0;
                const maxAllow = parseFloat(this.dataset.max || '0');
                if (v > maxAllow || v < 0) {
                    if (!isWarningOpen) {
                        warningMessage.textContent = v > maxAllow ? `Nilai tidak boleh melebihi batas: ${maxAllow}.` : 'Nilai tidak boleh negatif.';
                        pendingResetInput = this;
                        isWarningOpen = true;
                        warningModal.classList.remove('hidden');
                    }
                    return;
                }
                const u = (this.dataset.meinh || 'ST').toUpperCase();
                if (u === 'ST' || u === 'PC' || u === 'PCS' || u === 'EA') v = Math.floor(v);
                else if (u === 'M3') v = Math.round(v * 1000) / 1000;
                this.value = String(v);
            });
        });
        warningOkButton.addEventListener('click', () => {
            if (pendingResetInput) {
                pendingResetInput.value = '0';
                pendingResetInput = null;
            }
            isWarningOpen = false;
            warningModal.classList.add('hidden');
        });

        // === Larang konfirmasi multi-baris untuk PRO yang sama ===
        confirmButton.addEventListener('click', () => {
            const items = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => {
                const row = cb.closest('tr');
                const qtyInput = row.querySelector('input[name="QTY_SPX"]');
                return {
                    row,
                    qty: parseFloat(qtyInput.value || '0'),
                    max: parseFloat(qtyInput.dataset.max || '0'),
                    aufnr: row.dataset.aufnr,
                    arbpl0: row.dataset.arbpl0,
                    maktx: row.dataset.maktx
                };
            });

            const counts = items.reduce((acc, it) => (acc[it.aufnr] = (acc[it.aufnr] || 0) + 1, acc), {});
            const duplicates = Object.entries(counts).filter(([, n]) => n > 1);
            if (duplicates.length) {
                warningMessage.innerHTML = `Tidak dapat mengonfirmasi beberapa baris untuk PRO (AUFNR) yang sama secara bersamaan.<br><br>Duplikat terpilih:<ul class="list-disc pl-5 mt-1">${duplicates.map(([a,n])=>`<li><span class="font-mono">${a}</span> &times; ${n} baris</li>`).join('')}</ul><br>Silakan konfirmasi satu per satu untuk setiap PRO.`;
                warningModal.classList.remove('hidden');
                return;
            }
            if (items.some(x => x.qty <= 0)) {
                warningMessage.textContent = 'Kuantitas konfirmasi harus lebih dari 0.';
                warningModal.classList.remove('hidden');
                return;
            }
            const invalidMax = items.find(x => x.qty > x.max);
            if (invalidMax) {
                errorMessage.textContent = `Isi kuantitas valid (>0 & ≤ ${invalidMax.max}) untuk semua item yang dipilih.`;
                errorModal.classList.remove('hidden');
                return;
            }

            confirmationList.innerHTML = items.map(x => `<li class="flex justify-between items-center text-sm font-semibold text-slate-700">
                <div class="flex-1 pr-3"><span class="font-mono">${x.aufnr}</span> / <span class="font-mono">${x.arbpl0}</span> / ${x.maktx}</div>
                <span class="font-mono">${x.qty}</span></li>`).join('');
            confirmModal.classList.remove('hidden');
        });

        // === Kirim konfirmasi ===
        let pendingShowSuccess = null;
        yesButton.addEventListener('click', async () => {
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked'));
            if (!selected.length) {
                confirmModal.classList.add('hidden');
                return;
            }
            const today = toYYYYMMDD();
            const payloads = selected.map(cb => {
                const row = cb.closest('tr');
                return {
                    aufnr: row.dataset.aufnr || '',
                    vornr: row.dataset.vornr || '',
                    pernr: row.dataset.pernr || '{{ request("pernr") }}' || '',
                    psmng: parseFloat(row.querySelector('input[name="QTY_SPX"]').value || '0'),
                    meinh: row.dataset.meinh || 'ST',
                    gstrp: today,
                    gltrp: today,
                    budat: today,
                    _arbpl0: row.dataset.arbpl0 || '-',
                    _maktx: row.dataset.maktx || '-'
                };
            });

            confirmModal.classList.add('hidden');
            loading.classList.remove('hidden');
            content.classList.add('hidden');

            try {
                const sleep = (ms) => new Promise(r => setTimeout(r, ms));
                const results = [];
                for (const p of payloads) {
                    try {
                        const r = await apiPost('/api/yppi019/confirm', p);
                        results.push({
                            status: r.status,
                            json: await r.json().catch(() => ({})),
                            payload: p
                        });
                        await sleep(120);
                    } catch (e) {
                        results.push({
                            status: 0,
                            json: {
                                error: String(e)
                            },
                            payload: p
                        });
                    }
                }

                const failed = results.filter(r => r.status < 200 || r.status >= 300 || r.json?.ok === false || hasSapError(r.json?.sap_return));
                const success = results.filter(r => !failed.includes(r));

                if (failed.length) {
                    const lines = failed.map((f, i) => {
                        const entries = collectSapReturnEntries(f.json?.sap_return || {});
                        const errMsg = (entries.find(e => ['E', 'A'].includes(String(e?.TYPE || '').toUpperCase()) && e?.MESSAGE)?.MESSAGE) ||
                            (entries.find(e => e?.MESSAGE)?.MESSAGE) || f.json?.error || 'Gagal dikonfirmasi.';
                        return `${i+1}. ${f.payload?.aufnr || '-'} — ${errMsg}`;
                    }).join('\n');
                    errorMessage.innerHTML = `Sebagian/seluruh konfirmasi gagal (${failed.length} item):<br><br>${lines.replace(/\n/g, '<br>')}`;
                    errorModal.classList.remove('hidden');
                }
                if (success.length) {
                    successList.innerHTML = success.map((s, i) => {
                        const {
                            aufnr = '-', _arbpl0 = '-', _maktx = '-', psmng = '-'
                        } = s.payload;
                        const entries = collectSapReturnEntries(s.json?.sap_return || {});
                        const msgs = (entries.map(e => e?.MESSAGE).filter(Boolean).length ? entries.map(e => e?.MESSAGE).filter(Boolean) : ['Confirmation of order saved']);
                        const msgsHTML = msgs.map(m => `<div class="pl-5 text-[12px] text-slate-600">• ${m}</div>`).join('');
                        return `<li class="space-y-1">
                            <div class="flex justify-between items-center text-sm font-semibold text-slate-800">
                                <div class="flex-1 pr-3"><span class="font-mono">${aufnr}</span> / <span class="font-mono">${_arbpl0}</span> / ${_maktx}</div>
                                <span class="font-mono">${psmng}</span>
                            </div>
                            ${msgsHTML}</li>`;
                    }).join('');
                    pendingShowSuccess = () => successModal.classList.remove('hidden');
                }

                if (success.length || failed.length) {
                    selected.forEach(cb => {
                        const row = cb.closest('tr');
                        const isSuccess = success.some(s => s.payload?.aufnr === row.dataset.aufnr);
                        const isFailed = failed.some(f => f.payload?.aufnr === row.dataset.aufnr);
                        if (isSuccess && !isFailed) row.style.backgroundColor = '#dcfce7'; // hijau muda
                        else if (isFailed) row.style.backgroundColor = '#fee2e2'; // merah muda
                    });
                }
            } catch (e) {
                errorMessage.textContent = 'Terjadi kesalahan saat memproses respon: ' + e.message;
                errorModal.classList.remove('hidden');
            } finally {
                loading.classList.add('hidden');
                content.classList.remove('hidden');
                if (pendingShowSuccess) {
                    pendingShowSuccess();
                    pendingShowSuccess = null;
                }
            }
        });

        cancelButton.addEventListener('click', () => confirmModal.classList.add('hidden'));
        errorOkButton.addEventListener('click', () => errorModal.classList.add('hidden'));
        successOkButton.addEventListener('click', () => {
            window.location.href = "{{ route('scan') }}";
        });
    });
</script>
@endpush