@extends('layout')

@section('content')
<div class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
    <div class="relative px-6 py-8">
        <div class="max-w-2xl mx-auto text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-white/10 backdrop-blur-sm rounded-2xl mb-4">
                <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M12 12h-.01M12 12v4m-4-4h4m-4 0V8a4 4 0 118 0v1.5a4 4 0 01-4 4z"/>
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Scan Barcode</h1>
            <p class="text-base text-blue-100">Pindai barcode atau masukkan data secara manual</p>
        </div>
    </div>
</div>

<div class="px-6 py-6 -mt-6 relative z-10">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200/50 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-5 py-3 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Form Input Data</h2>
                        <p class="text-xs text-slate-500">Lengkapi informasi di bawah ini</p>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <form class="space-y-4" action="{{ route('detail') }}" method="get">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-3 h-3 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z"/>
                                </svg>
                            </div>
                            <label class="text-xs font-medium text-slate-700">PRO : IV_AUFNR</label>
                            <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Required</span>
                        </div>
                        <div class="relative group">
                            <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-blue-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                                <div class="flex-shrink-0">
                                    <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </div>
                                </div>
                                <input id="IV_AUFNR" name="aufnr" class="flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium" placeholder="Masukkan atau pindai barcode PRO"/>
                                <button type="button" id="openScanner" class="group p-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg hover:scale-105" title="Buka kamera">
                                    <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="aufnr-list-container" class="space-y-1"></div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-3 h-3 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <label class="text-xs font-medium text-slate-700">ID : IV_PERNR</label>
                            <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Required</span>
                        </div>
                        <div class="relative group">
                            <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-blue-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                                <div class="flex-shrink-0">
                                    <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                </div>
                                <input id="IV_PERNR" name="pernr" class="flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium" placeholder="Masukkan ID (Pernr)"/>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button class="w-full py-2 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Kirim Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 bg-gradient-to-r from-slate-50 to-blue-50 rounded-xl p-4 border border-slate-200">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex-shrink-0 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Tips Penggunaan</h3>
                    <ul class="text-xs text-slate-600 space-y-0.5">
                        <li>• Gunakan tombol kamera untuk memindai barcode secara otomatis</li>
                        <li>• Pastikan barcode dalam kondisi jelas dan tidak rusak</li>
                        <li>• Field <b>PRO (IV_AUFNR)</b> dan <b>ID (IV_PERNR)</b> wajib diisi untuk melanjutkan proses</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scanner Modal --}}
<div id="scannerModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50 p-3">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">Scanner Barcode</h3>
                </div>
                <button type="button" id="closeScanner" class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Tutup</button>
            </div>
            <div class="p-4">
                <div id="reader" class="rounded-xl overflow-hidden bg-black shadow-inner"></div>
                <div class="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3 h-3 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-amber-800 font-medium">Arahkan kamera ke barcode/QR PRO (AUFNR) dengan jelas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Error Modal --}}
<div id="errorModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 z-[60] p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="bg-red-600 px-5 py-3">
      <h3 class="text-sm font-semibold text-white" id="errTitle">Tidak bisa masuk ke Detail</h3>
    </div>
    <div class="p-5 space-y-3">
      <pre id="errText" class="text-xs text-slate-700 whitespace-pre-wrap"></pre>
      <div class="flex justify-end">
        <button type="button" class="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700"
                onclick="closeError()">OK</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('head')
<script src="https://unpkg.com/quagga/dist/quagga.min.js"></script>
<style>
    #reader{ width:100%; max-width:520px; height:160px; margin:0 auto; border-radius:12px; overflow:hidden; background:#000; }
    #reader video, #reader canvas{ width:100% !important; height:100% !important; object-fit: cover; display:block; }
    @media (min-width: 768px){ #reader{ height:120px; } }
</style>
@endpush

@push('scripts')
<script>
// ========== Helper normalisasi AUFNR ==========
function ean13CheckDigit(d12){let s=0,t=0;for(let i=0;i<12;i++){const n=+d12[i];if(i%2===0)s+=n;else t+=n}return (10-((s+3*t)%10))%10;}
function normalizeAufnr(raw){
  let s = String(raw||'').replace(/\D/g,'');
  if (s.length===13){ const cd=ean13CheckDigit(s.slice(0,12)); if (cd===+s[12]) s=s.slice(0,12); }
  return s;
}
const sleep = (ms)=> new Promise(r=>setTimeout(r,ms));

// ========== State / elemen ==========
const aufnrList = new Set();
const aufnrListContainer = document.getElementById('aufnr-list-container');
const aufnrInput = document.getElementById('IV_AUFNR');
const pernrInput = document.getElementById('IV_PERNR');

// ========== Tambah AUFNR ke daftar ==========
function addAufnrToList(aufnr){
  aufnr = normalizeAufnr(aufnr);
  if (aufnr.length !== 12 || aufnrList.has(aufnr)) return;
  aufnrList.add(aufnr);

  const div = document.createElement('div');
  div.className = 'px-3 py-1.5 bg-slate-100 rounded-xl flex items-center justify-between text-xs font-medium text-slate-700 transition-all duration-200 hover:bg-slate-200';
  div.textContent = aufnr;

  const del = document.createElement('button');
  del.type='button';
  del.className='w-5 h-5 ml-2 bg-red-100 rounded-full flex items-center justify-center text-red-600 hover:bg-red-200';
  del.innerHTML='<svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
  del.onclick=()=>{ aufnrList.delete(aufnr); div.remove(); };

  div.appendChild(del);
  aufnrListContainer.appendChild(div);
}

// ========== Modal error (tetap) ==========
const errModal = document.getElementById('errorModal');
function showError(title, msg){
  const t = document.getElementById('errTitle');
  const p = document.getElementById('errText');
  if (t) t.textContent = title || 'Terjadi Kesalahan';
  if (p) p.textContent  = msg || '';
  errModal.classList.remove('hidden'); errModal.classList.add('flex');
}
function closeError(){ errModal.classList.add('hidden'); errModal.classList.remove('flex'); }
window.closeError = closeError;

// ========== Klasifikasi & ekstraksi error ==========
function extractMsg(body){
  if (body == null) return '';
  if (typeof body === 'string') return body;
  if (typeof body === 'object'){
    return body.error || body.message || body.msg || JSON.stringify(body);
  }
  return String(body);
}
function classifySap(msg){
  const m = (msg||'').toUpperCase();
  if (/NOT AUTHORIZATION|NO AUTH|AUTHORIZATION/i.test(m)) return 'auth';
  if (/RFC_CLOSED|LOGON|PASSWORD|CONNECTION|COMMUNICATION/i.test(m)) return 'sap';
  return 'other';
}

// ========== Callers ==========
async function postSync(aufnr, pernr){
  try{

    const SAP_USER = sessionStorage.getItem('sap_user') || '';   // isi dari login form/modal
    const SAP_PASS = sessionStorage.getItem('sap_pass') || '';

    const res = await fetch('/api/yppi019/sync',{
      method: 'POST',
      headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    
  },
      body: JSON.stringify({
        aufnr,
        pernr,
        force: true   // <<< PENTING: selalu refresh dari SAP
      })
    });
    let body;
    try{ body = await res.clone().json(); }catch{ body = await res.text(); }
    const msg  = extractMsg(body);
    const kind = res.ok ? 'ok' : classifySap(msg);
    return { ok: res.ok, status: res.status, body, msg, kind, aufnr };
  }catch(e){
    const msg = String(e);
    return { ok:false, status:0, body:msg, msg, kind: classifySap(msg), aufnr };
  }
}


// VERIFIKASI keberadaan data utk AUFNR+PERNR.
// Kembalikan detail kalau error SAP agar tidak tertukar dengan “tidak ditemukan”.
async function verifyMaterial(aufnr, pernr){
  const url = new URL('/api/yppi019/material', location.origin);
  url.searchParams.set('aufnr', aufnr);
  url.searchParams.set('pernr', pernr);
  url.searchParams.set('limit', '1');
  url.searchParams.set('auto_sync', '1');

  try{
    let res = await fetch(url, { headers:{ 'Accept':'application/json' }});
    let body; try{ body = await res.clone().json(); }catch{ body = await res.text(); }
    if (!res.ok){
      const msg  = extractMsg(body);
      const kind = classifySap(msg);
      return { ok:false, kind, msg, aufnr };
    }
    let rows = Array.isArray(body?.T_DATA1) ? body.T_DATA1 : (Array.isArray(body?.rows) ? body.rows : []);
    if (Array.isArray(rows) && rows.length > 0) return { ok:true, kind:'ok', aufnr };

    // kecilkan race-condition: tunggu sebentar & coba lagi sekali
    await sleep(200);
    res = await fetch(url, { headers:{ 'Accept':'application/json' }});
    body = await res.json().catch(()=> ({}));
    rows = Array.isArray(body?.T_DATA1) ? body.T_DATA1 : (Array.isArray(body?.rows) ? body.rows : []);
    if (Array.isArray(rows) && rows.length > 0) return { ok:true, kind:'ok', aufnr };

    return { ok:false, kind:'notfound', msg:'Data tidak ditemukan', aufnr };
  }catch(e){
    return { ok:false, kind:'sap', msg:String(e), aufnr };
  }
}

// ========== Submit handler ==========
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[action]');

  aufnrInput.addEventListener('change', (e) => {
    const code = normalizeAufnr(e.target.value);
    if (code.length === 12) addAufnrToList(code);
    e.target.value='';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const pernr = (pernrInput?.value || '').trim();
    const aufnrArray = Array.from(aufnrList);

    if (!pernr){ showError('Input belum lengkap','ID (Pernr) wajib diisi.'); pernrInput.focus(); return; }
    if (!aufnrArray.length){ showError('Input belum lengkap','PRO (AUFNR) wajib diisi / scan.'); aufnrInput?.focus(); return; }

    // 1) SYNC semua AUFNR (jika ada yang ditolak → tampilkan pesan SAP mentahnya)
    const syncResults = await Promise.all(aufnrArray.map(a => postSync(a, pernr)));
    const failedSync  = syncResults.filter(r => !r.ok);
    if (failedSync.length){
      const lines = failedSync.map(f => `• ${f.aufnr}  [${f.status}]  ${f.msg}`);
      const hasAuth = failedSync.some(f => f.kind==='auth');
      showError(
        hasAuth ? 'Akses SAP ditolak' : 'Gagal sinkronisasi ke SAP',
        lines.join('\n')
      );
      return; // stop redirect
    }

    // 2) VERIFIKASI DB utk setiap AUFNR (auto_sync=1). 
    //    Error SAP saat verifikasi → tampilkan pesan SAP.
    const verResults = await Promise.all(aufnrArray.map(a => verifyMaterial(a, pernr)));
    const verSapErrs = verResults.filter(v => !v.ok && v.kind!=='notfound');
    if (verSapErrs.length){
      const lines = verSapErrs.map(v => `• ${v.aufnr}  ${v.msg}`);
      const hasAuth = verSapErrs.some(v => v.kind==='auth');
      showError(
        hasAuth ? 'Akses SAP ditolak' : 'Gagal ambil data dari SAP',
        lines.join('\n')
      );
      return; // stop redirect
    }

    // 3) Kalau masih ada yang tidak ditemukan → tampilkan modal “tidak ditemukan”
    const notFound = verResults.filter(v => v.kind==='notfound').map(v => v.aufnr);
    if (notFound.length){
      showError(
        'Tidak bisa masuk ke Detail',
        'PRO berikut tidak ditemukan untuk ID tersebut (atau tidak ada di SAP):\n\n' + notFound.map(a=>`• ${a}`).join('\n')
      );
      return;
    }

    // 4) Semua oke → redirect
    const to = new URL(form.action, location.origin);
    to.searchParams.set('pernr', pernr);
    to.searchParams.set('aufnr', aufnrArray[0]);
    to.searchParams.set('aufnrs', aufnrArray.join(','));
    location.href = to.toString();
  });
});

// ========== Scanner (tanpa ubah tampilan) ==========
const modal = document.getElementById('scannerModal');
const openBtn = document.getElementById('openScanner');
const closeBtn = document.getElementById('closeScanner');
const reader = document.getElementById('reader');
let quaggaRunning=false, committing=false, onDet=null, onProc=null;
const VOTE_WINDOW_MS=1200, VOTE_MIN=2; let votes=[];

function startQuagga(){
  stopQuagga(true); votes.length=0; committing=false; reader.innerHTML='';
  Quagga.init({
    inputStream:{name:"Live",type:"LiveStream",target:reader,constraints:{facingMode:"environment",width:{ideal:1920},height:{ideal:1080},aspectRatio:{ideal:16/9}},area:{top:"32%",right:"8%",left:"8%",bottom:"32%"}},
    locator:{halfSample:true,patchSize:"x-large"},
    decoder:{readers:["code_128_reader","ean_reader","upc_reader"],multiple:false},
    locate:true,frequency:15
  }, function(err){
    if (err){ console.error('Quagga init error:',err); return; }
    Quagga.start(); quaggaRunning=true;

    onProc = function(result){
      const ctx=Quagga.canvas?.ctx?.overlay, cvs=Quagga.canvas?.dom?.overlay;
      if(!ctx||!cvs) return;
      ctx.clearRect(0,0,cvs.width,cvs.height);
      if(result?.box) Quagga.ImageDebug.drawPath(result.box,{x:0,y:1},ctx,{color:"rgba(0,255,0,.8)",lineWidth:3});
      if(result?.line)Quagga.ImageDebug.drawPath(result.line,{x:'x',y:'y'},ctx,{color:"rgba(255,0,0,.8)",lineWidth:3});
    };

    onDet = function(res){
      if (committing) return;
      const raw = res?.codeResult?.code || '';
      let digits = normalizeAufnr(raw);
      if (!/^\d{12,13}$/.test(digits)) return;
      const now = Date.now();
      votes.push({code:digits,t:now});
      votes = votes.filter(v=> now - v.t <= VOTE_WINDOW_MS);
      const freq = votes.reduce((m,v)=>((m[v.code]=(m[v.code]||0)+1),m),{});
      const top = Object.entries(freq).sort((a,b)=>b[1]-a[1])[0];
      if(!top) return;
      let [topCode,count]=top; let final = normalizeAufnr(topCode);
      if(final.length!==12) return;
      if(count>=VOTE_MIN){
        committing=true; addAufnrToList(final);
        const box = aufnrInput.parentElement; box.classList.add('border-green-500'); setTimeout(()=>box.classList.remove('border-green-500'),1200);
        stopQuagga(true); setTimeout(closeModal,50);
      }
    };

    Quagga.onProcessed(onProc); Quagga.onDetected(onDet);
  });
}
function stopQuagga(detach){
  if(quaggaRunning){ try{Quagga.stop();}catch(_){} quaggaRunning=false; }
  if(detach){
    try{ if(onDet) Quagga.offDetected(onDet); onDet=null; }catch(_){}
    try{ if(onProc) Quagga.offProcessed(onProc); onProc=null; }catch(_){}
  }
  try{ Quagga.CameraAccess?.release?.(); }catch(_){}
  try{ reader.innerHTML=''; }catch(_){}
}
function openModal(){
  if(!pernrInput.value){ showError('Input belum lengkap','Silakan isi ID (Pernr) terlebih dahulu.'); pernrInput.focus(); return; }
  modal.classList.remove('hidden'); modal.classList.add('flex'); startQuagga();
}
function closeModal(){ stopQuagga(true); modal.classList.add('hidden'); modal.classList.remove('flex'); }
openBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);
modal.addEventListener('click', e=>{ if(e.target===modal) closeModal(); });
</script>
@endpush



