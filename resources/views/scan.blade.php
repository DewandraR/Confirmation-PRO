@extends('layout')

@section('content')
{{-- Bagian header dengan gradasi yang disesuaikan --}}
<div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC41Ij48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
    <div class="relative px-6 py-8">
        <div class="max-w-2xl mx-auto text-center">
            {{-- LOGO PERUSAHAAN --}}
            <div class="mb-4">
                <img src="{{ asset('images/kmi.jpg') }}" alt="Company Logo" class="mx-auto w-20 h-20 object-contain rounded-xl p-0.5 bg-white">
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Konfirmasi PRO</h1>
            <p class="text-base text-white opacity-80">Pindai barcode atau masukkan data secara manual</p>
        </div>
    </div>
</div>

{{-- Kontainer Form Input Data dengan jarak atas yang disesuaikan --}}
<div class="px-6 py-12 -mt-8 relative z-10">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200/50 overflow-hidden">
            {{-- Header form input data --}}
            <div class="bg-slate-100 px-5 py-3 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Form Input Data</h2>
                        <p class="text-xs text-slate-600">Lengkapi informasi di bawah ini</p>
                    </div>

                    {{-- === Tambahan: tombol Logout di sisi kanan header === --}}
                      @auth
                      <button id="openLogoutConfirm"
                          class="ml-auto inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs md:text-sm font-semibold
                                bg-gradient-to-r from-red-600 to-rose-700 text-white shadow-md
                                hover:shadow-lg hover:from-red-700 hover:to-rose-800 active:scale-[0.98] transition">
                          <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                              stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 8l4 4m0 0l-4 4m4-4H3"/>
                          </svg>
                          Logout
                      </button>
                      @endauth
                </div>
            </div>

            {{-- Isi form input data --}}
            <div class="p-5">
                <form class="space-y-4" action="{{ route('detail') }}" method="get">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z" />
                                </svg>
                            </div>
                            <label class="text-xs font-medium text-slate-700">Order Number</label>
                            <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Required</span>
                        </div>
                        <div class="relative group">
                            <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                                <div class="flex-shrink-0">
                                    <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                </div>
                                <input id="IV_AUFNR" name="aufnr" class="flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium" placeholder="Masukkan atau pindai barcode PRO" />
                                <button type="button" id="openScanner" class="group p-1.5 rounded-lg bg-gradient-to-r from-green-600 to-blue-900 hover:from-green-700 hover:to-blue-900 transition-all duration-200 shadow-md hover:shadow-lg hover:scale-105" title="Buka kamera">
                                    <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="aufnr-list-container" class="space-y-1"></div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-yellow-400 rounded-lg flex items-center justify-center">
                                <svg class="w-3 h-3 text-yellow-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <label class="text-xs font-medium text-slate-700">NIK Operator</label>
                            <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">Required</span>
                        </div>
                        <div class="relative group">
                            <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2">
                                <div class="flex-shrink-0">
                                    <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <input id="IV_PERNR" name="pernr" class="flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium" placeholder="Masukkan NIK Operator" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-3">
                        <button class="w-full py-2 px-4 rounded-xl bg-gradient-to-r from-green-700 to-blue-900 hover:from-green-800 hover:to-blue-900 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Kirim Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tips Penggunaan section --}}
        <div class="mt-6 bg-gradient-to-r from-slate-50 to-green-50 rounded-xl p-4 border border-slate-200">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-green-500 rounded-lg flex-shrink-0 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Tips Penggunaan</h3>
                    <ul class="text-xs text-slate-600 space-y-0.5">
                        <li>• Posisikan barcode di area tengah kamera dan hindari pantulan cahaya</li>
                        <li>• Field <b>Order Number</b> dan <b>NIK Operator</b> wajib diisi saat submit</li>
                        <li>• Anda harus mengisi "NIK Operator" terlebih dulu untuk melakukan scan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scanner Modal --}}
<div id="scannerModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50 p-3">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden">
        <div class="bg-gradient-to-r from-green-700 to-blue-900 px-5 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">Scanner Barcode</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="toggleTorch" class="px-2 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Lampu</button>
                    <button type="button" id="closeScanner" class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Tutup</button>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div id="reader" class="rounded-xl overflow-hidden bg-black shadow-inner"></div>
            <div class="mt-3 p-3 bg-green-50 rounded-xl border border-green-200">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-xs text-green-800 font-medium">Arahkan kamera ke barcode PRO (AUFNR) dengan jelas</p>
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

{{-- TECO Modal (baru) --}}
<div id="tecoModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 z-[60] p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-green-500 px-5 py-3">
            <h3 class="text-sm font-semibold text-white">PRO kemungkinan sudah TECO</h3>
        </div>
        <div class="p-5 space-y-3">
            <p class="text-xs text-slate-700">
                PRO berikut tidak memiliki akses untuk saat ini. Kemungkinan statusnya
                <b>TECO (Technically Completed)</b>, sehingga tidak bisa diakses.
            </p>
            <pre id="tecoText" class="text-xs text-slate-800 whitespace-pre-wrap"></pre>
            <div class="flex justify-end">
                <button id="tecoOk" type="button"
                    class="px-4 py-1.5 text-sm rounded-lg bg-green-500 text-white hover:bg-green-600">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Logout Confirm Modal --}}
<div id="logoutModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 z-[70] p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="bg-red-600 px-5 py-3">
      <h3 class="text-sm font-semibold text-white">Konfirmasi Logout</h3>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-slate-700">Kamu yakin ingin keluar?</p>
      <div class="flex justify-end gap-2">
        <button id="logoutCancel" type="button"
                class="px-4 py-1.5 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100">
          Tidak
        </button>
        <button id="logoutConfirm" type="button"
                class="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700">
          Ya, Logout
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Hidden POST form for logout --}}
<form id="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
  @csrf
</form>


@endsection

@push('head')
<script src="https://unpkg.com/quagga/dist/quagga.min.js"></script>
<style>
    #reader {
        width: 100%;
        max-width: 520px;
        height: 320px;
        margin: 0 auto;
        border-radius: 12px;
        overflow: hidden;
        background: #000;
    }
    #reader video, #reader canvas {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        display: block;
    }
    @media (min-width: 768px) { #reader { height: 320px; } }
</style>
@endpush

@push('scripts')
<script>
    // ========== Helper normalisasi AUFNR ==========
    function ean13CheckDigit(d12){let s=0,t=0;for(let i=0;i<12;i++){const n=+d12[i];if(i%2===0)s+=n;else t+=n}return(10-((s+3*t)%10))%10}
    function normalizeAufnr(raw){let s=String(raw||'').replace(/\D/g,'');if(s.length===13){const cd=ean13CheckDigit(s.slice(0,12));if(cd===+s[12]) s=s.slice(0,12);}return s;}
    const sleep=(ms)=>new Promise(r=>setTimeout(r,ms));

    // ====== Sanitizer pesan error (menghilangkan PERNR) ======
    function sanitizeErrorMessage(msg){
        if(msg==null) return '';
        let s=String(msg);
        s = s.replace(/\s*oleh\s+PERNR\s+\d+.*$/i, ''); // hapus "oleh PERNR ..."
        s = s.replace(/[.,;:\s]+$/, '');
        if(!/[.?!]$/.test(s) && s!=='') s += '.';
        return s;
    }

    // ========== State / elemen ==========
    const aufnrList=new Set();
    const aufnrListContainer=document.getElementById('aufnr-list-container');
    const aufnrInput=document.getElementById('IV_AUFNR');
    const pernrInput=document.getElementById('IV_PERNR');

    function addAufnrToList(aufnr){
        aufnr=normalizeAufnr(aufnr);
        if(aufnr.length!==12||aufnrList.has(aufnr)) return;
        aufnrList.add(aufnr);
        const div=document.createElement('div');
        div.className='px-3 py-1.5 bg-slate-100 rounded-xl flex items-center justify-between text-xs font-medium text-slate-700 transition-all duration-200 hover:bg-slate-200';
        div.textContent=aufnr;
        const del=document.createElement('button');
        del.type='button';
        del.className='w-5 h-5 ml-2 bg-red-100 rounded-full flex items-center justify-center text-red-600 hover:bg-red-200';
        del.innerHTML='<svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
        del.onclick=()=>{aufnrList.delete(aufnr);div.remove();};
        div.appendChild(del);
        aufnrListContainer.appendChild(div);
    }

    // ========== Modal error ==========
    const errModal=document.getElementById('errorModal');
    function showError(title,msg){
        const t=document.getElementById('errTitle'); const p=document.getElementById('errText');
        if(t) t.textContent=title||'Terjadi Kesalahan';
        const cleaned = String(msg||'')
            .split('\n')
            .map(line => sanitizeErrorMessage(line))
            .join('\n');
        if(p) p.textContent=cleaned;
        errModal.classList.remove('hidden'); errModal.classList.add('flex');
    }
    function closeError(){errModal.classList.add('hidden'); errModal.classList.remove('flex');}
    window.closeError=closeError;

    // ====== TECO modal ======
    const tecoModal=document.getElementById('tecoModal');
    const tecoText=document.getElementById('tecoText');
    document.getElementById('tecoOk')?.addEventListener('click',()=>{
        tecoModal.classList.add('hidden'); tecoModal.classList.remove('flex');
    });
    function showTeco(list){
        if(!Array.isArray(list)||!list.length) return;
        if(tecoText) tecoText.textContent=list.map(a=>'• '+a).join('\n');
        tecoModal.classList.remove('hidden'); tecoModal.classList.add('flex');
    }

    // ========== Klasifikasi & ekstraksi error ==========
    function extractMsg(body){ if(body==null) return ''; if(typeof body==='string') return body;
        if(typeof body==='object'){ return body.error||body.message||body.msg||JSON.stringify(body); }
        return String(body);
    }
    function classifySap(msg){ const m=(msg||'').toUpperCase();
        if(/NOT AUTHORIZATION|NO AUTH|AUTHORIZATION/i.test(m)) return 'auth';
        if(/RFC_CLOSED|LOGON|PASSWORD|CONNECTION|COMMUNICATION/i.test(m)) return 'sap';
        return 'other';
    }

    // ========== Callers ==========
    async function postSync(aufnr,pernr){
        try{
            const res=await fetch('/api/yppi019/sync',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({aufnr,pernr,force:true})});
            let body; try{body=await res.clone().json();}catch{body=await res.text();}
            const rawMsg=extractMsg(body);
            let msg=sanitizeErrorMessage(rawMsg);
            const kind=res.ok?'ok':classifySap(rawMsg);

            // Custom 409: ganti pesan & sembunyikan prefix
            if(res.status===409 && /sudah terdaftar/i.test(rawMsg)){
                msg="PRO mungkin sudah di input oleh NIK Operator lain.\nSilahkan coba lagi atau ganti PRO yang lain.";
            }

            return {ok:res.ok,status:res.status,body,msg,kind,aufnr};
        }catch(e){
            const rawMsg=String(e);
            const msg=sanitizeErrorMessage(rawMsg);
            return {ok:false,status:0,body:rawMsg,msg,kind:classifySap(rawMsg),aufnr};
        }
    }

    async function verifyMaterial(aufnr,pernr){
        const url=new URL('/api/yppi019/material',location.origin);
        url.searchParams.set('aufnr',aufnr); url.searchParams.set('pernr',pernr);
        url.searchParams.set('limit','1'); url.searchParams.set('auto_sync','1');
        try{
            let res=await fetch(url,{headers:{'Accept':'application/json'}});
            let body; try{body=await res.clone().json();}catch{body=await res.text();}
            if(!res.ok){ const raw=extractMsg(body); const kind=classifySap(raw); return {ok:false,kind,msg:sanitizeErrorMessage(raw),aufnr}; }
            let rows=Array.isArray(body?.T_DATA1)?body.T_DATA1:(Array.isArray(body?.rows)?body.rows:[]);
            if(Array.isArray(rows)&&rows.length>0) return {ok:true,kind:'ok',aufnr};
            await sleep(200);
            res=await fetch(url,{headers:{'Accept':'application/json'}});
            body=await res.json().catch(()=>({}));
            rows=Array.isArray(body?.T_DATA1)?body.T_DATA1:(Array.isArray(body?.rows)?body.rows:[]);
            if(Array.isArray(rows)&&rows.length>0) return {ok:true,kind:'ok',aufnr};
            return {ok:false,kind:'notfound',msg:'Data tidak ditemukan',aufnr};
        }catch(e){ return {ok:false,kind:'sap',msg:sanitizeErrorMessage(String(e)),aufnr}; }
    }

    // ========== Submit handler ==========
    document.addEventListener('DOMContentLoaded',()=>{
        const form=document.querySelector('form[action]');

        aufnrInput.addEventListener('change',(e)=>{
            const code=normalizeAufnr(e.target.value);
            if(code.length===12) addAufnrToList(code);
            e.target.value='';
        });

        form.addEventListener('submit', async (e)=>{
            e.preventDefault();

            const pernr=(pernrInput?.value||'').trim();
            const aufnrArray=Array.from(aufnrList);

            if(!pernr){ showError('Input belum lengkap','NIK Operator wajib di isi.'); pernrInput.focus(); return; }
            if(!aufnrArray.length){ showError('Input belum lengkap','Order Number wajib diisi / scan.'); aufnrInput?.focus(); return; }

            // 1) Sync
            const syncResults=await Promise.all(aufnrArray.map(a=>postSync(a,pernr)));

            // Tandai kemungkinan TECO (backend: teco_possible=true atau received==0)
            const tecoFromSync=new Set(
                syncResults.filter(r=>r?.ok && (r.body?.teco_possible===true || Number(r.body?.received||0)===0)).map(r=>r.aufnr)
            );

            const failedSync=syncResults.filter(r=>!r.ok);
            if(failedSync.length){
                const lines=failedSync.map(f=>{
                    // Untuk kasus 409 yang sudah kita custom, tampilkan pesan saja (tanpa "AUFNR [409]")
                    if(f.status===409 && /PRO mungkin sudah di input/i.test(f.msg)){
                        return f.msg;
                    }
                    return `• ${f.aufnr}  [${f.status}]  ${f.msg}`;
                });
                const hasAuth=failedSync.some(f=>f.kind==='auth');
                showError(hasAuth?'Akses SAP ditolak':'Gagal sinkronisasi ke SAP',lines.join('\n'));
                return;
            }

            // 2) Verifikasi data
            const verResults=await Promise.all(aufnrArray.map(a=>verifyMaterial(a,pernr)));
            const verSapErrs=verResults.filter(v=>!v.ok && v.kind!=='notfound');
            if(verSapErrs.length){
                const lines=verSapErrs.map(v=>`• ${v.aufnr}  ${sanitizeErrorMessage(v.msg)}`);
                const hasAuth=verSapErrs.some(v=>v.kind==='auth');
                showError(hasAuth?'Akses SAP ditolak':'Gagal ambil data dari SAP',lines.join('\n'));
                return;
            }

            // 3) Pisahkan TECO vs benar-benar tidak ada
            const notFound=verResults.filter(v=>v.kind==='notfound').map(v=>v.aufnr);
            const tecoNotFound = notFound.filter(a=>tecoFromSync.has(a));
            const trulyNotFound= notFound.filter(a=>!tecoFromSync.has(a));

            if(trulyNotFound.length){
                showError('Tidak bisa masuk ke Detail',
                    'PRO berikut kemungkinan sudah teco:\n\n'+trulyNotFound.map(a=>`• ${a}`).join('\n')
                );
                return;
            }
            if(tecoNotFound.length){
                showTeco(tecoNotFound);
                return;
            }

            // 4) Lanjut ke detail
            const to=new URL(form.action,location.origin);
            to.searchParams.set('pernr',pernr);
            to.searchParams.set('aufnr',aufnrArray[0]);
            to.searchParams.set('aufnrs',aufnrArray.join(','));
            location.href=to.toString();
        });
    });

    // ========== Scanner ==========
    const modal=document.getElementById('scannerModal');
    const openBtn=document.getElementById('openScanner');
    const closeBtn=document.getElementById('closeScanner');
    const toggleTorchBtn=document.getElementById('toggleTorch');
    const reader=document.getElementById('reader');

    let quaggaRunning=false, committing=false, onDet=null, onProc=null;
    const VOTE_WINDOW_MS=2000, VOTE_MIN=1;
    let votes=[]; let currentTrack=null; let torchOn=false;

    function pickFacingModeSupported(){
        return navigator.mediaDevices?.getUserMedia ? {facingMode:{ideal:"environment"}} : {facingMode:"environment"};
    }

    function startQuagga(){
        stopQuagga(true); votes.length=0; committing=false; reader.innerHTML='';
        const workers=(/(iPad|iPhone|iPod)/.test(navigator.userAgent)?0:(navigator.hardwareConcurrency||2));
        Quagga.init({
            inputStream:{name:"Live",type:"LiveStream",target:reader,constraints:{...pickFacingModeSupported(),width:{ideal:1280},height:{ideal:720},aspectRatio:{ideal:16/9}},area:{top:"15%",right:"5%",left:"5%",bottom:"15%"}},
            locator:{halfSample:false,patchSize:"medium"},
            decoder:{readers:["code_128_reader","upc_reader","ean_reader"],multiple:false},
            locate:true,frequency:25,numOfWorkers:workers
        }, async function(err){
            if(err){ console.error('Quagga init error:',err); return; }
            Quagga.start(); quaggaRunning=true;
            try{ const stream=Quagga.CameraAccess.getActiveStream(); currentTrack=stream?.getVideoTracks?.()[0]||null; }catch(_){ currentTrack=null; }

            onProc=function(result){
                const ctx=Quagga.canvas?.ctx?.overlay, cvs=Quagga.canvas?.dom?.overlay; if(!ctx||!cvs) return;
                ctx.clearRect(0,0,cvs.width,cvs.height);
                if(result?.box) Quagga.ImageDebug.drawPath(result.box,{x:0,y:1},ctx,{color:"rgba(0,255,0,.8)",lineWidth:3});
                if(result?.line) Quagga.ImageDebug.drawPath(result.line,{x:'x',y:'y'},ctx,{color:"rgba(255,0,0,.8)",lineWidth:3});
            };

            onDet=function(res){
                if(committing) return;
                const raw=res?.codeResult?.code||''; const digits=normalizeAufnr(raw);
                if(!/^\d{12,13}$/.test(digits)) return;
                const now=Date.now(); votes.push({code:digits,t:now}); votes=votes.filter(v=>now-v.t<=VOTE_WINDOW_MS);
                const freq=votes.reduce((m,v)=>((m[v.code]=(m[v.code]||0)+1),m),{}); const top=Object.entries(freq).sort((a,b)=>b[1]-a[1])[0]; if(!top) return;
                let [topCode,count]=top; const final=normalizeAufnr(topCode); if(final.length!==12) return;
                if(count>=VOTE_MIN){ committing=true; addAufnrToList(final);
                    const box=aufnrInput.parentElement; box.classList.add('border-green-500'); setTimeout(()=>box.classList.remove('border-green-500'),1200);
                    stopQuagga(true); setTimeout(closeModal,50);
                }
            };

            Quagga.onProcessed(onProc); Quagga.onDetected(onDet);
        });
    }

    function stopQuagga(detach){
        if(quaggaRunning){ try{Quagga.stop();}catch(_){} quaggaRunning=false; }
        if(detach){ try{ if(onDet) Quagga.offDetected(onDet); onDet=null; }catch(_){} try{ if(onProc) Quagga.offProcessed(onProc); onProc=null; }catch(_){} }
        try{ Quagga.CameraAccess?.release?.(); }catch(_){} try{ reader.innerHTML=''; }catch(_){} currentTrack=null; torchOn=false;
    }

    async function setTorch(on){
        try{ const track=currentTrack; if(!track) return; const capabilities=track.getCapabilities?.(); if(!capabilities||!capabilities.torch) return;
            await track.applyConstraints({advanced:[{torch:!!on}]}); torchOn=!!on;
        }catch(e){ console.debug('Torch not supported',e); }
    }

    function openModal(){
        const pernr=(pernrInput?.value||'').trim();
        if(!pernr){ showError('Input belum lengkap','Silakan isi "NIK Operator" terlebih dahulu.'); pernrInput?.focus(); return; }
        modal.classList.remove('hidden'); modal.classList.add('flex'); startQuagga();
    }
    function closeModal(){ stopQuagga(true); modal.classList.add('hidden'); modal.classList.remove('flex'); }

    openBtn.addEventListener('click',openModal);
    closeBtn.addEventListener('click',closeModal);
    toggleTorchBtn.addEventListener('click',()=>setTorch(!torchOn));
    modal.addEventListener('click',e=>{ if(e.target===modal) closeModal(); });


    // ===== Logout modal handlers =====
const logoutModal   = document.getElementById('logoutModal');
const openLogoutBtn = document.getElementById('openLogoutConfirm');
const logoutCancel  = document.getElementById('logoutCancel');
const logoutConfirm = document.getElementById('logoutConfirm');
const logoutForm    = document.getElementById('logoutForm');

function openLogoutModal(){
  logoutModal.classList.remove('hidden');
  logoutModal.classList.add('flex');
}
function closeLogoutModal(){
  logoutModal.classList.add('hidden');
  logoutModal.classList.remove('flex');
}

openLogoutBtn?.addEventListener('click', (e)=>{ e.preventDefault(); openLogoutModal(); });
logoutCancel?.addEventListener('click', closeLogoutModal);
logoutConfirm?.addEventListener('click', ()=> { logoutForm?.submit(); });
logoutModal?.addEventListener('click', (e)=>{ if(e.target===logoutModal) closeLogoutModal(); });

</script>
@endpush
