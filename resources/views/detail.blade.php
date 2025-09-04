@extends('layout')

@section('content')
<div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-700 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
    <div class="relative px-4 py-6">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-2 mb-4">
                <a href="{{ route('scan') }}" class="group flex items-center gap-1 px-3 py-1.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg text-white text-sm transition-all duration-200">
                    <svg class="w-3 h-3 group-hover:-translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
            </div>
            <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-10 h-10 bg-white/10 backdrop-blur-sm rounded-xl">
                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">Detail Barang</h1>
                    <p class="text-sm text-indigo-100">Informasi lengkap data produksi dalam format tabel</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="px-4 py-6 -mt-3 relative z-10">
    <div class="max-w-6xl mx-auto space-y-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-50 to-indigo-50 px-4 py-3 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z" />
                            </svg>
                        </div>
                        <div>
                            {{-- <div class="text-xs text-slate-500 font-medium">PRO (AUFNR)</div> --}}
                            <div id="headAUFNR" class="font-bold text-lg text-slate-800">-</div>
                        </div>
                    </div>
                    <a href="{{ route('scan') }}" class="group flex items-center gap-1 text-xs px-3 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg font-medium transition-colors">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 12h-.01M12 12v4m-4-4h4m-4 0V8a4 4 0 118 0v1.5a4 4 0 01-4 4z" />
                        </svg>
                        Scan Lain
                    </a>
                </div>
            </div>

            <div id="loading" class="p-6 text-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                    <div class="text-sm text-slate-500 font-medium">Memuat data...</div>
                    <div class="text-xs text-slate-400">Mengambil informasi dari server</div>
                </div>
            </div>

            <div id="content" class="hidden">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 text-indigo-600 rounded border-slate-300">
                            <label for="selectAll" class="text-sm text-slate-600 font-medium">Pilih Semua</label>
                        </div>
                        <div class="text-xs text-slate-500">
                            Total: <span id="totalItems">0</span> item(s)
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-indigo-50 to-purple-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 sticky left-0 bg-indigo-50">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 sticky left-12 bg-indigo-50 w-16">No</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[120px]">AUFNR</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[150px]">Quantity Konfirmasi</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[200px]">LTXA1</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[100px]">DISPO</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[100px]">STEUS</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[120px]">MATNRX</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[200px]">MAKTX</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[120px]">ARBPL0</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[100px]">PERNR</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider border-b border-slate-200 min-w-[150px]">SNAME</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="bg-white divide-y divide-slate-200"></tbody>
                        </table>
                    </div>
                </div>
                <div class="px-4 py-3 bg-white border-t border-slate-200 flex justify-end">
                    <button id="confirm-button" class="px-4 py-2 rounded-lg bg-indigo-500 text-white text-sm font-semibold transition-colors disabled:bg-slate-300 disabled:text-slate-500" disabled>
                        Konfirmasi (<span id="selected-count">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi --}}
<div id="confirm-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-4 py-3 border-b border-slate-200">
            <h4 class="text-lg font-semibold text-slate-800">Konfirmasi Aksi</h4>
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

{{-- Modal Error --}}
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

{{-- Modal Peringatan --}}
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

{{-- Modal Sukses --}}
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
    input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type="number"] { -moz-appearance: textfield; }
</style>

<script>
// ===== Helpers =====
const padVornr = v => String(parseInt(v || '0', 10)).padStart(4,'0');
const toYYYYMMDD = (d) => {
  if (!d) { const x = new Date(); return `${x.getFullYear()}${String(x.getMonth()+1).padStart(2,'0')}${String(x.getDate()).padStart(2,'0')}`; }
  const s = String(d);
  const m1 = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(s); if (m1) return `${m1[3]}${m1[2]}${m1[1]}`;
  const m2 = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);   if (m2) return `${m2[1]}${m2[2]}${m2[3]}`;
  return s.replace(/\D/g,'');
};

// Ekstrak pesan dari sap_return (untuk error/sukses)
function collectSapReturnEntries(ret) {
  const entries = [];
  if (!ret || typeof ret !== 'object') return entries;
  const keys = ['RETURN','ET_RETURN','T_RETURN','E_RETURN','ES_RETURN','EV_RETURN'];
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

window.onerror = function (msg, src, line, col) {
  const em = document.getElementById('error-message');
  const mm = document.getElementById('error-modal');
  if (em && mm) { em.textContent = 'Error JS: ' + msg + ` (${line}:${col})`; mm.classList.remove('hidden'); }
  console.error(msg, src, line, col);
};

document.addEventListener("DOMContentLoaded", async () => {
  // --- URL params ---
  const p = new URLSearchParams(location.search);
  const rawList  = p.get('aufnrs') || '';
  const single   = p.get('aufnr') || '';
  const IV_PERNR = p.get('pernr') || '';
  const IV_ARBPL = p.get('arbpl') || '';

  function ean13CheckDigit(d12){let s=0,t=0;for(let i=0;i<12;i++){const n=+d12[i];if(i%2===0)s+=n;else t+=n;}return (10-((s+3*t)%10))%10;}
  function normalizeAufnr(raw){let s=String(raw||'').replace(/\D/g,'');if(s.length===13){const cd=ean13CheckDigit(s.slice(0,12));if(cd===+s[12]) s=s.slice(0,12);}return s;}

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

  // Tampilkan PERNR yang dimasukkan; kalau kosong baru fallback ke AUFNR
  if (headAUFNR) headAUFNR.textContent = IV_PERNR ? String(IV_PERNR) : (AUFNRS.length ? AUFNRS.join(', ') : '-');

  const showError = (message) => {
    loading.innerHTML = `
      <div class="flex flex-col items-center gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="text-sm text-red-600 font-medium">Error: ${message}</div>
        <div class="text-xs text-slate-400">Gagal memuat data dari server</div>
      </div>`;
    loading.classList.remove('hidden');
    content.classList.add('hidden');
  };

  if (!AUFNRS.length) { showError('Tidak ada PRO yang dikirim dari halaman Scan.'); return; }

  // --- Ambil data ---
  let rowsAll = [], failures = [];
  try {
    const results = await Promise.allSettled(
      AUFNRS.map(async (aufnr) => {
        const url = `/api/yppi019/material?aufnr=${encodeURIComponent(aufnr)}&pernr=${encodeURIComponent(IV_PERNR)}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        let json;
        try { json = await res.json(); }
        catch (e) {
          const txt = await res.text().catch(()=> '');
          throw new Error(`Non-JSON (${res.status}): ${txt.slice(0,200)}`);
        }
        if (!res.ok) throw new Error(json.error || json.message || `HTTP ${res.status}`);
        const t = json.T_DATA1;
        return Array.isArray(t) ? t : (t ? [t] : []);
      })
    );
    results.forEach(r => r.status==='fulfilled' ? rowsAll = rowsAll.concat(r.value) : failures.push(r.reason?.message || 'unknown'));
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
  rowsAll.sort((a,b)=>{
    if ((a.AUFNR||'')!==(b.AUFNR||'')) return (a.AUFNR||'').localeCompare(b.AUFNR||'');
    const va = parseInt(a.VORNRX||a.VORNR||'0',10)||0;
    const vb = parseInt(b.VORNRX||b.VORNR||'0',10)||0;
    return va - vb;
  });
  totalItems.textContent = String(rowsAll.length);

  tableBody.innerHTML = rowsAll.map((r,i)=>{
    const vornr   = padVornr(r.VORNRX || r.VORNR || '0');
    const qtySPK  = parseFloat(r.QTY_SPK ?? 0);
    const weMng   = parseFloat(r.WEMNG   ?? 0);
    const qtySPX  = parseFloat(r.QTY_SPX ?? 0);
    const sisaSPK = Math.max(qtySPK - weMng, 0);
    const maxAllow= Math.max(0, Math.min(qtySPX, sisaSPK));

    return `
    <tr class="odd:bg-white even:bg-slate-50 hover:bg-indigo-50/40 transition-colors"
        data-aufnr="${r.AUFNR || ''}"
        data-vornr="${vornr}"
        data-pernr="${r.PERNR || IV_PERNR || ''}"
        data-meinh="${(r.MEINH||'ST')}"
        data-gstrp="${toYYYYMMDD(r.GSTRP)}"
        data-gltrp="${toYYYYMMDD(r.GLTRP)}"
        data-arbpl0="${r.ARBPL0 || r.ARBPL || IV_ARBPL || '-'}"
        data-maktx="${(r.MAKTX || '-').replace(/\"/g,'&quot;')}">
      <td class="px-3 py-3 text-center sticky left-0 bg-inherit border-r border-slate-200">
        <input type="checkbox" class="row-checkbox w-4 h-4 text-indigo-600 rounded border-slate-300">
      </td>
      <td class="px-3 py-3 text-center sticky left-12 bg-inherit border-r border-slate-200">
        <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-xs font-bold text-indigo-700 mx-auto">${i+1}</div>
      </td>
      <td class="px-3 py-3 text-sm font-semibold text-slate-900">${r.AUFNR || '-'}</td>
      <td class="px-3 py-3 text-sm text-slate-700 text-center">
        <input type="number" name="QTY_SPX"
               class="w-28 px-2 py-1 text-center rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-400/40 focus:border-indigo-500 text-sm font-mono"
               value="0" placeholder="0" min="0" data-max="${maxAllow}"
               title="Maks: ${maxAllow} (sisa SPK=${sisaSPK}, sisa SPX=${qtySPX})"/>
        <div class="mt-1 text-[11px] text-slate-400">Maks ${maxAllow}</div>
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
    const all = document.querySelectorAll('.row-checkbox');
    const on  = document.querySelectorAll('.row-checkbox:checked');
    selectAll.checked = all.length === on.length;
    selectAll.indeterminate = on.length > 0 && on.length < all.length;
    updateConfirmButtonState();
  });
  updateConfirmButtonState();

  // === Validasi input QTY_SPX ===
  let pendingResetInput = null;
  document.querySelectorAll('input[name="QTY_SPX"]').forEach(input=>{
    input.addEventListener('focus', function(){
      if (this.value==='0') this.value='';
    });
    input.addEventListener('blur',  function(){
      if (this.value==='') this.value='0';
      const v = parseFloat(this.value || '0');
      if (v <= 0) {
        warningMessage.textContent = 'Isi nilai tidak boleh kurang dari 0';
        pendingResetInput = this;
        warningModal.classList.remove('hidden');
      }
    });
    input.addEventListener('input', function(){
      const maxAllow = parseFloat(this.dataset.max || '0');
      if (this.value==='') return;
      let v = parseFloat(this.value);
      if (isNaN(v)) v = 0;

      if (v > maxAllow) {
        warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}`;
        pendingResetInput = this;
        warningModal.classList.remove('hidden');
      } else {
        this.value = v;
      }
    });
  });

  // Tutup modal peringatan → reset jadi 0
  warningOkButton.addEventListener('click', ()=>{
    if (pendingResetInput) {
      pendingResetInput.value = '0';
      pendingResetInput = null;
    }
    warningModal.classList.add('hidden');
  });

  // === Modal ringkasan ===
  confirmButton.addEventListener('click', ()=>{
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked'));
    const items = selected.map(cb=>{
      const row = cb.closest('tr');
      const qty = parseFloat(row.querySelector('input[name="QTY_SPX"]').value||'0');
      const max = parseFloat(row.querySelector('input[name="QTY_SPX"]').dataset.max||'0');
      return {
        row, qty, max,
        aufnr: row.dataset.aufnr,
        arbpl0: row.dataset.arbpl0,
        maktx: row.dataset.maktx
      };
    });

    /* === ⬇️ TAMBAHKAN BLOK INI: CEK AUFNR GANDA === */
  const counts = {};
  for (const it of items) counts[it.aufnr] = (counts[it.aufnr] || 0) + 1;
  const duplicates = Object.entries(counts).filter(([, n]) => n > 1);
  if (duplicates.length) {
    const listHTML = duplicates
      .map(([a, n]) => `<li><span class="font-mono">${a}</span> &times; ${n} baris</li>`)
      .join('');
    warningMessage.innerHTML =
      `Tidak dapat mengonfirmasi secara bersamaan untuk PRO (AUFNR) yang sama.<br><br>
       Duplikat terpilih:<ul class="list-disc pl-5 mt-1">${listHTML}</ul><br>
       Silakan konfirmasi satu per satu untuk setiap PRO.`;
    warningModal.classList.remove('hidden');
    return; // hentikan proses
  }
  /* === ⬆️ SAMPAI SINI === */

    const nonPositive = items.find(x => !(x.qty > 0));
    if (nonPositive) {
      warningMessage.textContent = 'Isi nilai tidak boleh kurang dari 0';
      warningModal.classList.remove('hidden');
      return;
    }

    const invalidMax = items.find(x => x.qty > x.max);
    if (invalidMax) {
      errorMessage.textContent = `Isi kuantitas valid (>0 & ≤ ${invalidMax.max}) untuk semua item yang dipilih.`;
      errorModal.classList.remove('hidden');
      return;
    }

    confirmationList.innerHTML = items.map(x=>`
      <li class="flex justify-between items-center text-sm font-semibold text-slate-700">
        <div class="flex-1 pr-3">
          <span class="font-mono">${x.aufnr}</span> /
          <span class="font-mono">${x.arbpl0}</span> /
          ${x.maktx}
        </div>
        <span class="font-mono">${x.qty}</span>
      </li>`).join('');
    confirmModal.classList.remove('hidden');
  });

  // === Kirim konfirmasi ===
  let pendingShowSuccess = null;
  yesButton.addEventListener('click', async () => {
    const selected = Array.from(document.querySelectorAll('.row-checkbox:checked'));
    if (!selected.length) { confirmModal.classList.add('hidden'); return; }

    const today = toYYYYMMDD();
    const payloads = selected.map(cb => {
      const row = cb.closest('tr');
      const qty = parseFloat(row.querySelector('input[name="QTY_SPX"]').value || '0');
      return {
        // ke backend
        aufnr: row.dataset.aufnr || '',
        vornr: row.dataset.vornr || '',
        pernr: row.dataset.pernr || '{{ request("pernr") }}' || '',
        psmng: qty,
        meinh: row.dataset.meinh || 'ST',
        gstrp: today, gltrp: today, budat: today,
        // meta untuk popup sukses
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
          const r = await fetch('/api/yppi019/confirm', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(p)
          });
          const j = await r.json().catch(() => ({}));
          results.push({ status: r.status, json: j, payload: p });
          await sleep(120);
        } catch (e) {
          results.push({ status: 0, json: { error: String(e) }, payload: p });
        }
      }

      const failed  = results.filter(r => r.status === 0 || r.status >= 400 || r.json?.ok === false);
      const success = results.filter(r => !(r.status === 0 || r.status >= 400 || r.json?.ok === false));

      if (failed.length) {
        const lines = failed.map((f, i) => {
          const au = f.payload?.aufnr || '-';
          const ret = f.json?.sap_return || {};
          const entries = collectSapReturnEntries(ret);
          const text = (entries.find(e => e.MESSAGE)?.MESSAGE) || f.json?.error || 'Gagal dikonfirmasi.';
          return `${i+1}. ${au} — ${text}`;
        }).join('\n');

        errorMessage.textContent = `Sebagian/seluruh konfirmasi gagal (${failed.length} item):\n\n${lines}`;
        errorMessage.style.whiteSpace = 'pre-line';
        errorModal.classList.remove('hidden');
      }

      if (success.length) {
        const contentHTML = success.map((s, i) => {
          const au  = s.payload?.aufnr   || '-';
          const ar  = s.payload?._arbpl0 || '-';
          const mk  = s.payload?._maktx  || '-';
          const qty = s.payload?.psmng   ?? '-';

          const entries = collectSapReturnEntries(s.json?.sap_return || {});
          const msgs = (Array.isArray(entries) ? entries : []).map(e => e?.MESSAGE).filter(Boolean);
          const msgsHTML = (msgs.length ? msgs : ['Confirmation of order saved'])
            .map(m => `<div class="pl-5 text-[12px] text-slate-600">• ${m}</div>`).join('');

          return `
            <li class="space-y-1">
              <div class="flex justify-between items-center text-sm font-semibold text-slate-800">
                <div class="flex-1 pr-3">
                  <span class="font-mono">${au}</span> /
                  <span class="font-mono">${ar}</span> /
                  ${mk}
                </div>
                <span class="font-mono">${qty}</span>
              </div>
              ${msgsHTML}
            </li>`;
        }).join('');

        if (failed.length) {
          pendingShowSuccess = contentHTML;
        } else {
          successList.innerHTML = contentHTML;
          successModal.classList.remove('hidden');
        }
      }
    } catch (err) {
      errorMessage.textContent = 'Terjadi kesalahan: ' + (err.message || err);
      errorModal.classList.remove('hidden');
    } finally {
      loading.classList.add('hidden');
      content.classList.remove('hidden');
    }
  });

  // Tutup modal error
  cancelButton.addEventListener('click', ()=> confirmModal.classList.add('hidden'));
  errorOkButton.addEventListener('click', ()=> {
    errorModal.classList.add('hidden');
    if (pendingShowSuccess) {
      successList.innerHTML = pendingShowSuccess;
      pendingShowSuccess = null;
      successModal.classList.remove('hidden');
    }
  });

  // OK pada modal sukses → reload agar data terbaru tampil
  successOkButton.addEventListener('click', () => {
    successModal.classList.add('hidden');
    location.reload();
  });
});
</script>
@endpush
