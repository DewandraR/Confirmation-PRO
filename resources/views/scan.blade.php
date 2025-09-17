{{-- resources/views/scan.blade.php --}}
@extends('layout')

@section('content')
{{-- Bagian header dengan gradasi yang disesuaikan --}}
<div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
  {{-- perbaiki base64 (sebelumnya korup) --}}
  <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC41Ij48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
  <div class="relative px-4 py-8 md:px-6">
    <div class="max-w-2xl mx-auto text-center">
      {{-- LOGO PERUSAHAAN --}}
      <div class="mb-3">
        <img src="{{ asset('images/kmi.jpg') }}" alt="Company Logo" class="mx-auto w-16 h-16 md:w-20 md:h-20 object-contain rounded-xl p-0.5 bg-white">
      </div>
      <h1 class="text-xl md:text-3xl font-bold text-white mb-1 md:mb-2 leading-tight">Konfirmasi PRO</h1>
      <p class="text-sm md:text-base text-white/80 leading-tight">Pindai barcode atau masukkan data secara manual</p>
    </div>
  </div>
</div>

{{-- Kontainer Form Input Data dengan jarak atas yang disesuaikan --}}
<div class="px-4 py-10 -mt-6 relative z-10 md:px-6">
  <div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/50 overflow-hidden">
      {{-- Header form input data --}}
      <div class="bg-slate-100 px-5 py-3 border-b border-slate-200">
        {{-- wrap + gap agar aman di 414px --}}
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white rounded-xl flex items-center justify-center">
              <svg class="w-4 h-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="min-w-0">
              <h2 class="text-base md:text-lg font-semibold text-slate-800 leading-tight">Form Input Data</h2>
              <p class="text-[11px] text-slate-600 leading-tight">Lengkapi informasi di bawah ini</p>
            </div>
          </div>

          {{-- Tombol Logout --}}
          @auth
          <button id="openLogoutConfirm"
            class="shrink-0 inline-flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 rounded-xl text-xs md:text-sm font-semibold
                   bg-gradient-to-r from-red-600 to-rose-700 text-white shadow-md
                   hover:shadow-lg hover:from-red-700 hover:to-rose-800 active:scale-[0.98] transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            Logout
          </button>
          @endauth
        </div>
      </div>

      {{-- Isi form input data --}}
      <div class="p-5">
        <form id="main-form" class="space-y-4" action="{{ route('detail') }}" method="get">

<!-- Work Center & Plant (match PRO style) -->
<div class="space-y-2">
  <div class="flex items-center gap-2 flex-wrap ios-nowrap-row">
    <div class="w-5 h-5 bg-emerald-500 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </div>
    <label class="text-xs font-medium text-slate-700 whitespace-nowrap">Work Center & Plant</label>
    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full shrink-0 whitespace-nowrap">Optional</span>

    {{-- ⬇️ Tombol Histori Backdate (baru) --}}
    <button type="button" id="openBackdateHistory"
            class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold
                   bg-gradient-to-r from-green-600 to-blue-900 text-white shadow
                   hover:from-green-700 hover:to-blue-900 active:scale-[0.98] transition min-w-[130px] justify-center"
            title="Lihat histori backdate">
      <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 8v5l3 3m5-4a8 8 0 11-16 0 8 8 0 0116 0z"/>
      </svg>
      Histori Backdate
    </button>
  </div>

  <div class="relative group">
    <!-- Shell: border abu-abu solid saat tidak fokus -->
    <div class="w-full rounded-xl shadow-sm border-2 border-gray-300
                group-focus-within:border-emerald-500
                group-hover:border-gray-400 transition-colors duration-200
                px-3 py-1.5 flex items-center gap-2 flex-wrap">

      <!-- ikon kiri -->
      <div class="flex-shrink-0">
  <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
    <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none"
         viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" />
    </svg>
  </div>
</div>

      <!-- input WC -->
      <input id="IV_ARBPL" name="arbpl"
             class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium"
             placeholder="Masukkan atau pindai QR Work Center" />

      <!-- select Plant: transparan agar menyatu di shell -->
      <div class="relative flex items-center shrink-0">
        <select id="IV_WERKS" name="werks"
                class="bg-transparent outline-none text-xs font-medium text-slate-700
                       appearance-none pr-6 text-right">
          <option value="">Pilih Plant</option>
          <option value="1000">1000</option>
          <option value="1200">1200</option>
          <option value="2000">2000</option>
          <option value="3000">3000</option>
        </select>
        <!-- caret custom -->
        <svg class="w-4 h-4 text-emerald-600 pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 24 24" fill="currentColor">
          <path d="M7 10l5 5 5-5H7z"/>
        </svg>
      </div>
      <!-- tombol kamera: sama gradient dengan PRO -->
      <button type="button" id="openQrScanner"
              class="shrink-0 p-1.5 rounded-lg bg-gradient-to-r from-green-600 to-blue-900
                     hover:from-green-700 hover:to-blue-900 transition-all duration-200
                     shadow-md hover:shadow-lg hover:scale-105"
              title="Buka QR Scanner">
        <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" />
        </svg>
      </button>
    </div>
  </div>
</div>

          <div class="flex items-center text-center">
            <div class="flex-grow border-t border-slate-200"></div>
            <span class="flex-shrink mx-4 text-xs text-slate-400 font-medium">ATAU</span>
            <div class="flex-grow border-t border-slate-200"></div>
          </div>

          <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
              <div class="w-5 h-5 bg-green-500 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z" />
                </svg>
              </div>
              <label class="text-xs font-medium text-slate-700">PRO</label>
              <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full shrink-0">Optional</span>
            </div>
            <div class="relative group">
              <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200 group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors px-3 py-1.5 flex items-center gap-2 flex-wrap">
                <div class="flex-shrink-0">
                  <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                    <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                  </div>
                </div>
                <input id="IV_AUFNR" name="aufnr" class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium" placeholder="Masukkan atau pindai barcode PRO" />
                <button type="button" id="openScanner" class="shrink-0 group p-1.5 rounded-lg bg-gradient-to-r from-green-600 to-blue-900 hover:from-green-700 hover:to-blue-900 transition-all duration-200 shadow-md hover:shadow-lg hover:scale-105" title="Buka kamera">
                  <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div id="aufnr-list-container" class="space-y-1"></div>

          <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
              <div class="w-5 h-5 bg-yellow-400 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-3 h-3 text-yellow-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <label class="text-xs font-medium text-slate-700">NIK Operator</label>
              <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full shrink-0">Required</span>
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
                <input id="IV_PERNR" name="pernr" class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium" placeholder="Masukkan NIK Operator" />
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
            <li>• Pastikan untuk <b>menghidupkan dan mengizinkan kamera di browser anda</b>, saat akan melakukan scan.</li>
            <li>• Field <b>NIK Operator</b> wajib diisi.</li>
            <li>• Anda bisa mengisi <b>Work Center & Plant</b>, ATAU <b>PRO</b>, ATAU ketiganya.</li>
            <li>• Posisikan <b>Barcode</b> dan <b>QR Code</b> di area tengah kamera dan hindari pantulan cahaya.</li>
            <li>• Untuk pengguna <b>iOS</b> jika kamera Work Center tidak berfungsi, harap inputkan kode secara manual.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- MODALS --}}
<div id="scannerModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50 p-3">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden">
    <div class="bg-gradient-to-r from-green-700 to-blue-900 px-5 py-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 bg-white/20 rounded-lg flex items-center justify-center"><svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" />
            </svg></div>
          <h3 class="text-base font-semibold text-white">Scanner Barcode</h3>
        </div>
        <div class="flex items-center gap-2"><button type="button" id="toggleTorch" class="px-2 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Lampu</button><button type="button" id="closeScanner" class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Tutup</button></div>
      </div>
    </div>
    <div class="p-4">
      <div id="reader" class="rounded-xl overflow-hidden bg-black shadow-inner"></div>
      <div class="mt-3 p-3 bg-green-50 rounded-xl border border-green-200">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center"><svg class="w-3 h-3 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg></div>
          <p class="text-xs text-green-800 font-medium">Arahkan kamera ke barcode PRO (AUFNR) dengan jelas</p>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="qrScannerModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50 p-3">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden">
    <div class="bg-gradient-to-r from-blue-700 to-indigo-900 px-5 py-3">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Scanner QR Work Center</h3><button type="button" id="closeQrScanner" class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Tutup</button>
      </div>
    </div>
    <div class="p-4">
      <div id="qr-reader" class="rounded-xl overflow-hidden bg-black shadow-inner border-4 border-slate-200"></div>
      <p class="mt-3 text-center text-xs text-slate-600">Arahkan kamera ke QR Code Work Center.</p>
    </div>
  </div>
</div>
<div id="errorModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 z-[60] p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="bg-red-600 px-5 py-3">
      <h3 class="text-sm font-semibold text-white" id="errTitle">Terjadi Kesalahan</h3>
    </div>
    <div class="p-5 space-y-3">
      <pre id="errText" class="text-xs text-slate-700 whitespace-pre-wrap"></pre>
      <div class="flex justify-end"><button type="button" class="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700" onclick="closeError()">OK</button></div>
    </div>
  </div>
</div>

<div id="tecoModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 z-[60] p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="bg-yellow-500 px-5 py-3">
      <h3 class="text-sm font-semibold text-white">Data Tidak Ditemukan</h3>
    </div>
    <div class="p-5 space-y-3">
      <p class="text-xs text-slate-700">
        Data berikut tidak ditemukan atau tidak tersedia untuk saat ini.
        Silakan periksa kembali PRO / Work Center / Plant / NIK.
      </p>
      <pre id="tecoText" class="text-xs text-slate-800 whitespace-pre-wrap"></pre>
      <div class="flex justify-end">
        <button id="tecoOk" type="button"
          class="px-4 py-1.5 text-sm rounded-lg bg-yellow-500 text-white hover:bg-yellow-600">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

<div id="logoutModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 z-[70] p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="bg-red-600 px-5 py-3">
      <h3 class="text-sm font-semibold text-white">Konfirmasi Logout</h3>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-slate-700">Kamu yakin ingin keluar?</p>
      <div class="flex justify-end gap-2"><button id="logoutCancel" type="button" class="px-4 py-1.5 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100">Tidak</button><button id="logoutConfirm" type="button" class="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700">Ya, Keluar</button></div>
    </div>
  </div>
</div>
<form id="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

{{-- ⬇️ Modal Histori Backdate (baru, global tanpa filter) --}}
<div id="historyModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 z-[65] p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden">
    <div class="bg-gradient-to-r from-green-700 to-blue-900 px-5 py-3">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Histori Backdate</h3>
        <button id="historyClose" type="button"
                class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">
          Tutup
        </button>
      </div>
      <div id="historyMeta" class="text-[11px] text-white/80 mt-1">
        Semua operator • Maks 50 data terbaru
      </div>
    </div>

    <div class="p-4">
      <div class="overflow-auto max-h-[65vh] border border-slate-200 rounded-xl">
        <table class="min-w-full text-[12px]">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left font-semibold border-b w-[190px]">PRO / Activity</th>
              <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">NIK Operator</th>
              <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Qty (UoM)</th>
              <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Posting Date</th>
              <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Work Center</th>
              <th class="px-3 py-2 text-left font-semibold border-b">Material Desc</th>
            </tr>
          </thead>
          <tbody id="historyList"></tbody>
        </table>

        <div id="historyLoading" class="py-10 text-center text-sm text-slate-500">Memuat…</div>
        <div id="historyEmpty" class="hidden py-10 text-center text-sm text-slate-500">Belum ada histori backdate.</div>
      </div>

      <div class="mt-3 flex justify-end">
        <button id="historyOk" type="button"
                class="px-4 py-1.5 text-sm rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
          OK
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('head')
<style>
  #reader,
#qr-reader {
  width: 100%;
  /* Perbesar ukuran maksimalnya */
  max-width: 600px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  background: #000;
  position: relative;
  /* Pertahankan rasio aspek agar tidak melar */
  aspect-ratio: 16 / 9;
}
  #reader video, #reader canvas,
  #qr-reader video, #qr-reader canvas {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    -webkit-touch-callout: none; /* iOS: cegah fullscreen otomatis */
  }

  /* =============== iPhone layout helper =============== */
  /* Pada perangkat iOS kecil, paksa baris title Work Center & tombol agar tidak turun */
  @supports (-webkit-touch-callout: none) {
    .ios-nowrap-row { flex-wrap: nowrap !important; }
    .ios-nowrap-row > * { white-space: nowrap; }
    #openBackdateHistory { min-width: 132px; }
  }
  @media (max-width: 430px) {
    .ios-nowrap-row { flex-wrap: nowrap; }
    #openBackdateHistory { min-width: 132px; }
  }

  /* Dropdown kustom */
  .dropdown-enter { opacity: 0; transform: scale(0.97); }
  .dropdown-enter-active { opacity: 1; transform: scale(0.97); transition: opacity .12s ease, transform .12s ease; }
  .dropdown-leave-active { opacity: 0; transform: scale(0.97); transition: opacity .12s ease, transform .12s ease; }
  .dd-opt-focus { background-color: rgb(240 253 244); }
  .dd-scroll { max-height: 14rem; overflow: auto; -webkit-overflow-scrolling: touch; }
  .hidden-native-select { position:absolute; inset:auto 0 0 auto; width:0; height:0; opacity:0; pointer-events:none; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // =================================================================
  // ===== FUNGSI HELPER & STATE
  // =================================================================
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
  const aufnrList = new Set();

  const aufnrListContainer = document.getElementById('aufnr-list-container');
  const aufnrInput  = document.getElementById('IV_AUFNR');
  const pernrInput  = document.getElementById('IV_PERNR');
  const arbplInput  = document.getElementById('IV_ARBPL');
  const werksInput  = document.getElementById('IV_WERKS');

  function ean13CheckDigit(d12) {
    let s = 0, t = 0;
    for (let i = 0; i < 12; i++) {
      const n = +d12[i];
      if (i % 2 === 0) s += n; else t += n;
    }
    return (10 - ((s + 3 * t) % 10)) % 10;
  }

  // HANYA normalisasi kalau formatnya EAN; untuk Code128 biarkan apa adanya
  function isLikelyEANFormat(fmt){ return /^ean(_\d+)?$/i.test(String(fmt||'')); }

  function normalizeByFormat(raw, fmt) {
    const s = String(raw || '');
    if (isLikelyEANFormat(fmt)) {
      let digits = s.replace(/\D/g, '');
      if (digits.length === 13) {
        const cd = ean13CheckDigit(digits.slice(0, 12));
        if (cd === +digits[12]) digits = digits.slice(0, 12);
      }
      return digits;
    }
    return s.trim();
  }

  function addAufnrToList(aufnr) {
    if (!aufnr || aufnrList.has(aufnr)) return;
    aufnrList.add(aufnr);
    const div = document.createElement('div');
    div.className = 'px-3 py-1.5 bg-slate-100 rounded-xl flex items-center justify-between text-xs font-medium text-slate-700 transition-all duration-200 hover:bg-slate-200';
    div.textContent = aufnr;
    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'w-5 h-5 ml-2 bg-red-100 rounded-full flex items-center justify-center text-red-600 hover:bg-red-200';
    del.innerHTML = '<svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
    del.onclick = () => { aufnrList.delete(aufnr); div.remove(); };
    div.appendChild(del);
    aufnrListContainer?.appendChild(div);
  }

  // =================================================================
  // ===== MODAL & ERROR HANDLING
  // =================================================================
  const errModal = document.getElementById('errorModal');
  function showError(title, msg) {
    const h = document.getElementById('errTitle');
    const p = document.getElementById('errText');
    if (h) h.textContent = title || 'Terjadi Kesalahan';
    if (p) p.textContent = String(msg || '');
    errModal?.classList.remove('hidden');
    errModal?.classList.add('flex');
  }
  window.closeError = () => {
    errModal?.classList.add('hidden');
    errModal?.classList.remove('flex');
  };

  // =================================================================
  // === PATCH: helper sinkron + TECO modal
  // =================================================================
  async function ajaxSync(payload) {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': CSRF,
    };
    if (window.SAP_AUTH) headers['Authorization'] = window.SAP_AUTH; // opsional

    const res = await fetch('/api/yppi019/sync', {
      method: 'POST',
      headers,
      credentials: 'include',
      body: JSON.stringify(payload),
    });

    const rawText = await res.text().catch(() => '');
    let json = {};
    try { json = rawText ? JSON.parse(rawText) : {}; } catch (_) { json = { _raw: rawText }; }

    if (res.status === 404) {
      return { ok: false, teco: !!json.teco_possible, msg: json.message || 'Data Tidak Ditemukan', raw: json };
    }
    if (!res.ok) {
      const msg = json?.error || json?.message || `HTTP ${res.status} ${res.statusText}${rawText ? ` — ${rawText.slice(0,200)}` : ''}`;
      console.error('SYNC ERROR', { status: res.status, body: json, rawText });
      throw new Error(msg);
    }
    return { ok: true, received: Number(json?.received || json?.count || 0), raw: json };
  }

  function showTeco(listOrText) {
    const m = document.getElementById('tecoModal');
    const t = document.getElementById('tecoText');
    const okBtn = document.getElementById('tecoOk');
    const text = Array.isArray(listOrText) ? listOrText.join(', ') : String(listOrText || '');
    if (t) t.textContent = text;
    m?.classList.remove('hidden'); m?.classList.add('flex');
    okBtn?.addEventListener('click', () => { m?.classList.add('hidden'); m?.classList.remove('flex'); }, { once:true });
  }

  // =================================================================
  // ===== FORM HANDLER (preflight → /api/yppi019/sync)
  // =================================================================
  const form = document.getElementById('main-form');

  if (aufnrInput) {
    aufnrInput.addEventListener('change', (e) => {
      const val = String(e.target.value || '').trim();
      if (val.length > 0) addAufnrToList(val);
      e.target.value = '';
    });
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const pernr = pernrInput?.value.trim() || '';
      const aufnrArray = Array.from(aufnrList);
      const arbpl = arbplInput?.value.trim() || '';
      const werks = werksInput?.value.trim() || '';

      if (!pernr) { showError('Input Belum Lengkap', 'NIK Operator wajib diisi.'); return pernrInput?.focus(); }
      const hasAufnr = aufnrArray.length > 0;
      const hasWc = arbpl !== '';
      if (!hasAufnr && !hasWc) { showError('Input Tidak Lengkap', 'Anda harus mengisi "Work Center" atau "PRO".'); return arbplInput?.focus(); }
      if (hasWc && werks === '') { showError('Input Tidak Lengkap', 'Jika "Work Center" diisi, maka "Plant" wajib dipilih.'); return werksInput?.focus(); }

      const submitBtn = form.querySelector('button[type="submit"], button:not([type])');
      const setBusy = (b) => {
        if (!submitBtn) return;
        if (b) { submitBtn.dataset._txt = submitBtn.innerHTML; submitBtn.innerHTML = 'Memeriksa data...'; submitBtn.disabled = true; }
        else { submitBtn.innerHTML = submitBtn.dataset._txt || 'Kirim Data'; submitBtn.disabled = false; }
      };

      setBusy(true);
      try {
        const basePayload = { pernr };
        if (hasWc) { basePayload.arbpl = arbpl; basePayload.werks = werks; }

        if (hasAufnr) {
          const results = await Promise.allSettled(aufnrArray.map(aufnr => ajaxSync({ ...basePayload, aufnr })));

          const okAufnrs = [];
          let adaTeco = false;
          results.forEach((r, i) => {
            if (r.status === 'fulfilled') {
              if (r.value.ok) okAufnrs.push(aufnrArray[i]);
              if (!r.value.ok && r.value.teco) adaTeco = true;
            }
          });

          if (okAufnrs.length === 0) {
            if (adaTeco) showTeco(aufnrArray);
            else showError('Data Tidak Ditemukan', 'PRO/WC tidak mengembalikan data dari SAP. Periksa NIK/WORK CENTER/PLANT/PRO.');
            return;
          }

          const to = new URL(form.action, location.origin);
          to.searchParams.set('pernr', pernr);
          to.searchParams.set('aufnrs', okAufnrs.join(','));
          if (hasWc) { to.searchParams.set('arbpl', arbpl); to.searchParams.set('werks', werks); }
          location.href = to.toString();
          return;
        } else {
          const r = await ajaxSync(basePayload);
          if (!r.ok) {
            if (r.teco) showTeco(`${arbpl} - ${werks}`);
            else showError('Data Tidak Ditemukan', r.msg || 'WC/Plant tidak mengembalikan data.');
            return;
          }
          const to = new URL(form.action, location.origin);
          to.searchParams.set('pernr', pernr);
          to.searchParams.set('arbpl', arbpl);
          to.searchParams.set('werks', werks);
          location.href = to.toString();
          return;
        }
      } catch (err) {
        showError('Gagal Sinkron', err?.message || 'Terjadi kesalahan saat sinkronisasi.');
      } finally {
        setBusy(false);
      }
    });
  }

  // =================================================================
  // ===== SCANNER BARCODE (QuaggaJS) & MODAL
  // =================================================================
  const modal            = document.getElementById('scannerModal');
  const openBtn          = document.getElementById('openScanner');
  const closeBtn         = document.getElementById('closeScanner');
  const toggleTorchBtn   = document.getElementById('toggleTorch');
  const reader           = document.getElementById('reader');

  let quaggaRunning = false;
  let committing    = false;
  let onDet         = null;
  let currentTrack  = null;
  let torchOn       = false;

  function stopQuagga(detach) {
    if (quaggaRunning) {
      try { Quagga.stop(); } catch(_) {}
      quaggaRunning = false;
    }
    if (detach && onDet) {
      try { Quagga.offDetected(onDet); } catch(_) {}
      onDet = null;
    }
    try { Quagga.CameraAccess?.release?.(); } catch(_) {}
    if (reader) reader.innerHTML = '';
    currentTrack = null;
    torchOn = false;
  }

  async function setTorch(on) {
    if (!currentTrack) return;
    try {
      await currentTrack.applyConstraints({ advanced: [{ torch: !!on }] });
      torchOn = !!on;
    } catch (e) {
      console.debug('Torch not supported', e);
    }
  }

  // konfirmasi dua kali bacaan sama agar anti false-positive
  let lastCode = null, lastAt = 0;
  function stableCommit(raw, fmt) {
    const now = Date.now();
    const cur = normalizeByFormat(raw, fmt);
    const valid = /^[A-Za-z0-9\-\/\.]{6,20}$/.test(cur);
    if (!valid) return false;
    if (cur === lastCode && (now - lastAt) < 800) {
      addAufnrToList(cur);
      return true;
    }
    lastCode = cur; lastAt = now;
    return false;
  }

  function startQuagga() {
    if (typeof Quagga === 'undefined') {
      showError('Scanner tidak tersedia', 'Library Quagga belum dimuat.');
      return;
    }
    stopQuagga(true);
    committing = false;
    lastCode = null; lastAt = 0;
    if (reader) reader.innerHTML = '';

    Quagga.init({
      inputStream: {
        name: "Live",
        type: "LiveStream",
        target: reader,
        constraints: {
          facingMode: "environment",
          width: { ideal: 1280 },
          height: { ideal: 720 },
          aspectRatio: { ideal: 1.777 }
        }
      },
      locator: { patchSize: "medium", halfSample: true },
      decoder: { readers: ["code_128_reader", "ean_reader", "ean_8_reader"] },
      locate: true,
      numOfWorkers: navigator.hardwareConcurrency || 2,
    }, (err) => {
      if (err) { console.error(err); showError('Gagal memulai kamera', err?.message || err); return; }
      Quagga.start();
      quaggaRunning = true;
      try { currentTrack = Quagga.CameraAccess.getActiveStream()?.getVideoTracks?.()[0] || null; } catch(_) {}
      onDet = (res) => {
        if (committing) return;
        const raw = res?.codeResult?.code || '';
        const fmt = res?.codeResult?.format || '';
        if (!raw) return;
        if (stableCommit(raw, fmt)) { committing = true; closeModal(); }
      };
      Quagga.onDetected(onDet);

      // paksa playsinline di iOS
      setTimeout(() => {
        const video = reader?.querySelector('video');
        if (video) {
          video.setAttribute('playsinline','true');
          video.setAttribute('autoplay','true');
          video.setAttribute('muted','true');
        }
      }, 200);
    });
  }

  function openModal() { modal?.classList.remove('hidden'); modal?.classList.add('flex'); startQuagga(); }
  function closeModal() { stopQuagga(true); modal?.classList.add('hidden'); modal?.classList.remove('flex'); }

  if (openBtn)        openBtn.addEventListener('click', openModal);
  if (closeBtn)       closeBtn.addEventListener('click', closeModal);
  if (toggleTorchBtn) toggleTorchBtn.addEventListener('click', () => setTorch(!torchOn));
  if (modal)          modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

// =================================================================
// ===== FUNGSI BANTU UNTUK SCANNER QR
// =================================================================
async function getBestCameraId() {
  try {
    const cameras = await Html5Qrcode.getCameras();
    if (!cameras || cameras.length === 0) return null;
    
    // Prioritaskan kamera belakang yang ada
    const backCamera = cameras.find(cam => 
      /back|rear|belakang|environment/i.test(cam.label)
    );
    return backCamera ? backCamera.id : cameras[0].id;
  } catch (err) {
    console.error("Gagal mendapatkan daftar kamera:", err);
    return null;
  }
}

function qrboxSizer(vw, vh) {
  const side = Math.min(vw, vh);
  // Ubah faktor pengali untuk membuat kotak lebih besar, misal dari 0.7 menjadi 0.8
  const target = Math.round(side * 0.8);
  return { 
    width: Math.max(300, Math.min(500, target)),
    height: Math.max(300, Math.min(500, target)) 
  };
}

// =================================================================
// ===== SCANNER QR (Html5Qrcode) & MODAL
// =================================================================
const qrModal = document.getElementById('qrScannerModal');
const openQrBtn = document.getElementById('openQrScanner');
const closeQrBtn = document.getElementById('closeQrScanner');
let html5QrCode = null;

async function startQrScanner() {
  if (typeof Html5Qrcode === 'undefined') {
    showError('Scanner QR tidak tersedia', 'Library html5-qrcode belum dimuat.');
    closeQrModal();
    return;
  }
  
  if (!html5QrCode) {
    html5QrCode = new Html5Qrcode("qr-reader", { verbose: false });
  }

  const onScanSuccess = (decodedText) => {
    const normalizedText = decodedText.trim().replace(/[^a-zA-Z0-9\s]/g, '').toUpperCase();
    if (arbplInput && normalizedText.length > 0) {
      arbplInput.value = normalizedText;
      closeQrModal();
    }
  };

  try {
    const cameraId = await getBestCameraId();
    if (!cameraId) {
      showError('Gagal Kamera', 'Tidak ada kamera yang terdeteksi.');
      closeQrModal();
      return;
    }

    const config = {
      fps: 15,
      qrbox: (vw, vh) => qrboxSizer(vw, vh),
      disableFlip: true,
    };
    
    if (html5QrCode.isScanning) {
        await html5QrCode.stop();
    }

    await html5QrCode.start(
      { deviceId: { exact: cameraId } }, 
      config, 
      onScanSuccess, 
      (error) => {
        console.warn('Pemindaian error:', error);
      }
    );

    const applyVideoAttributes = () => {
      const v = document.querySelector('#qr-reader video');
      if (v) {
        v.setAttribute('playsinline', 'true');
        v.style.width = '100%';
        v.style.height = '100%';
        v.style.objectFit = 'cover';
      }
    };
    applyVideoAttributes();

  } catch (err) {
    const msg = (err && err.message) ? err.message : String(err);
    showError("Gagal Kamera", msg.includes('NotAllowedError')
      ? "Akses kamera ditolak. Berikan izin di pengaturan peramban."
      : "Tidak dapat memulai pemindaian.");
    closeQrModal();
  }
}
 
async function stopQrScanner() {
  if (html5QrCode && html5QrCode.isScanning) { 
    try { 
      await html5QrCode.stop(); 
    } catch (e) {
      console.error("Gagal menghentikan scanner QR:", e);
    } 
  }
}
function openQrModal() {
  qrModal?.classList.remove('hidden'); 
  qrModal?.classList.add('flex');
  setTimeout(startQrScanner, 200);
}
function closeQrModal() { 
  stopQrScanner();
  qrModal?.classList.add('hidden'); 
  qrModal?.classList.remove('flex'); 
}

if (openQrBtn) {
  openQrBtn.addEventListener('click', () => {
    if (!window.Html5Qrcode) {
      showError('Scanner QR tidak tersedia', 'Library html5-qrcode belum dimuat.');
      return;
    }
    openQrModal();
  });
}
if (closeQrBtn) closeQrBtn.addEventListener('click', closeQrModal);
if (qrModal) qrModal.addEventListener('click', e => { if (e.target === qrModal) closeQrModal(); });

  // =================================================================
  // ===== LOGOUT
  // =================================================================
  const logoutModal  = document.getElementById('logoutModal');
  const openLogoutBtn= document.getElementById('openLogoutConfirm');
  const logoutCancel = document.getElementById('logoutCancel');
  const logoutConfirm= document.getElementById('logoutConfirm');
  const logoutForm   = document.getElementById('logoutForm');

  if (openLogoutBtn) openLogoutBtn.addEventListener('click', (e) => {
    e.preventDefault();
    logoutModal?.classList.remove('hidden');
    logoutModal?.classList.add('flex');
  });
  if (logoutCancel)  logoutCancel.addEventListener('click', () => {
    logoutModal?.classList.add('hidden');
    logoutModal?.classList.remove('flex');
  });
  if (logoutConfirm) logoutConfirm.addEventListener('click', () => {
    logoutForm?.submit();
  });

  // =================================================================
  // ===== DROPDOWN KUSTOM PLANT (tidak mengubah yang lama)
  // =================================================================
  (function () {
    const select = document.getElementById('IV_WERKS');
    if (!select) return;

    const camBtn = document.getElementById('openQrScanner');
    if (camBtn) camBtn.style.marginLeft = '-4px';

    select.classList.add('hidden-native-select');
    const oldCaret = select.nextElementSibling;
    if (oldCaret && oldCaret.tagName?.toLowerCase() === 'svg') oldCaret.style.display = 'none';

    const host = select.parentElement;
    host.classList.remove('flex');
    host.classList.add('block','relative','min-w-[120px]','w-[120px]');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.id = 'plantTrigger';
    trigger.setAttribute('aria-haspopup','listbox');
    trigger.setAttribute('aria-expanded','false');
    trigger.className = [
      'w-full','text-center','px-1','py-0','h-5','leading-[18px]',
      'text-[11px]','font-semibold','relative','-top-[1px]',
      'outline-none','bg-transparent','rounded-lg','focus:ring-2','focus:ring-emerald-500','select-none'
    ].join(' ');

    const label = document.createElement('span');
    label.id = 'plantLabel';
    label.className = 'relative -top-px';
    label.textContent = select.value ? select.value : 'Pilih Plant';
    trigger.appendChild(label);

    const caret = document.createElementNS('http://www.w3.org/2000/svg','svg');
    caret.setAttribute('viewBox','0 0 24 24');
    caret.setAttribute('fill','currentColor');
    caret.classList.add('w-4','h-4','text-emerald-600','pointer-events-none','absolute','right-0','top-1/2','-translate-y-1/2');

    caret.innerHTML = '<path d="M7 10l5 5 5-5H7z"/>';
    host.appendChild(trigger);
    host.appendChild(caret);

    const menu = document.createElement('div');
    menu.id = 'plantMenu';
    menu.className = 'dropdown-enter invisible opacity-0 scale-95 absolute right-0 mt-1 z-30 w-44 bg-white rounded-xl shadow-2xl ring-1 ring-slate-200 overflow-hidden';
    menu.setAttribute('role','listbox');
    menu.tabIndex = -1;

    const header = document.createElement('div');
    header.className = 'px-3 py-2 text-[10px] font-semibold tracking-wider text-slate-500 bg-slate-50';
    header.textContent = 'Pilih Plant';

    const ul = document.createElement('ul');
    ul.className = 'dd-scroll text-xs';

    const makeItem = (text, value) => {
      const li = document.createElement('li');
      li.dataset.value = value;
      li.setAttribute('role','option');
      li.className = 'dd-opt relative px-3 py-2 cursor-pointer hover:bg-emerald-50 hover:text-emerald-700';
      li.innerHTML = `
        <span class="block w-full text-center">${text}</span>
        <span class="check absolute right-3 top-1/2 -translate-y-1/2 ${select.value===value?'':'hidden'}">✓</span>
      `;
      return li;
    };

    const nativeOptions = Array.from(select.querySelectorAll('option')).filter(o => o.value !== '');
    nativeOptions.forEach(o => ul.appendChild(makeItem(o.textContent.trim(), o.value)));

    menu.appendChild(header);
    menu.appendChild(ul);
    host.appendChild(menu);

    let open = false, activeIdx = -1;
    const items = () => Array.from(menu.querySelectorAll('.dd-opt'));

    function showMenu(){
      if (open) return;
      open = true;
      trigger.setAttribute('aria-expanded','true');
      menu.classList.remove('invisible','opacity-0','scale-95','dropdown-enter');
      menu.classList.add('dropdown-enter-active');
      const cur = items().findIndex(li => li.dataset.value === select.value);
      setActive(cur >= 0 ? cur : 0);
      setTimeout(() => menu.classList.remove('dropdown-enter-active'), 130);
      setTimeout(() => document.addEventListener('click', clickAway, { once:true }), 0);
      menu.focus({preventScroll:true});
    }
    function hideMenu(){
      if (!open) return;
      open = false;
      trigger.setAttribute('aria-expanded','false');
      menu.classList.
      add('dropdown-leave-active');
      setTimeout(() => {
        menu.classList.add('invisible','opacity-0','scale-95');
        menu.classList.remove('dropdown-leave-active');
      }, 130);
      activeIdx = -1;
    }
    function clickAway(e){ if (!menu.contains(e.target) && e.target !== trigger) hideMenu(); }
    function setActive(i){
      const arr = items();
      arr.forEach((li,idx)=> li.classList.toggle('dd-opt-focus', idx===i));
      activeIdx = i;
      arr[i]?.scrollIntoView({block:'nearest'});
    }
    function choose(li){
      const val = li.dataset.value || '';
      select.value = val;
      label.textContent = val || 'Pilih Plant';
      items().forEach(o => o.querySelector('.check')?.classList.add('hidden'));
      li.querySelector('.check')?.classList.remove('hidden');
      select.dispatchEvent(new Event('change', { bubbles: true }));
      select.dispatchEvent(new Event('input',  { bubbles: true }));
      hideMenu();
    }

    trigger.addEventListener('click', () => (open ? hideMenu() : showMenu()));
    trigger.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showMenu(); }
    });

    items().forEach((li, i) => {
      li.addEventListener('mouseenter', () => setActive(i));
      li.addEventListener('click', () => choose(li));
    });

    menu.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') return hideMenu();
      if (e.key === 'Enter')  { e.preventDefault(); if (activeIdx>=0) choose(items()[activeIdx]); }
      if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(items().length-1, activeIdx+1)); }
      if (e.key === 'ArrowUp')   { e.preventDefault(); setActive(Math.max(0, activeIdx-1)); }
      if (e.key === 'Tab') hideMenu();
    });

  })(); // dropdown IIFE

  // =================================================================
  // ===== HISTORI BACKDATE (GLOBAL TANPA FILTER)
  // =================================================================
  const historyModal   = document.getElementById('historyModal');
  const historyBtn     = document.getElementById('openBackdateHistory');
  const historyClose   = document.getElementById('historyClose');
  const historyOk      = document.getElementById('historyOk');
  const historyList    = document.getElementById('historyList');
  const historyMeta    = document.getElementById('historyMeta');
  const historyEmpty   = document.getElementById('historyEmpty');
  const historyLoading = document.getElementById('historyLoading');

  function pad2(n){ return String(n).padStart(2,'0'); }

  function fmtYMD(v){
    if (!v) return '-';
    const s = String(v);
    let d = null;

    if (/^\d{8}$/.test(s)) d = new Date(`${s.slice(0,4)}-${s.slice(4,6)}-${s.slice(6,8)}T00:00:00`);
    else if (/^\d{4}-\d{2}-\d{2}$/.test(s)) d = new Date(`${s}T00:00:00`);
    else return s;

    return new Intl.DateTimeFormat('id-ID', { day:'2-digit', month:'2-digit', year:'numeric' }).format(d);
  }

  function fmtDateTime(v){
    if(!v) return '-';
    let d = new Date(v);
    if (isNaN(d)) {
      const s = String(v);
      if (/^\d{4}-\d{2}-\d{2}$/.test(s)) d = new Date(`${s}T00:00:00`);
      else return s;
    }
    const tgl = new Intl.DateTimeFormat('id-ID', { day:'2-digit', month:'2-digit', year:'numeric' }).format(d);
    const jam = `${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
    return `${tgl} ${jam}`;
  }

  function openHistoryModalScan(){
    if (!historyModal) return;
    historyList.innerHTML = '';
    historyEmpty.classList.add('hidden');
    historyLoading.classList.remove('hidden');
    historyMeta.textContent = 'Semua operator • Maks 50 data terbaru';

    historyModal.classList.remove('hidden'); historyModal.classList.add('flex');

    fetch('/api/yppi019/backdate-history?limit=50&order=desc', {
      headers: { 'Accept':'application/json' },
      credentials: 'include'
    })
    .then(r => r.json().catch(()=>({})))
    .then(js => {
      const rows = Array.isArray(js.rows) ? js.rows : [];
      historyLoading.classList.add('hidden');

      if (!rows.length) { historyEmpty.classList.remove('hidden'); return; }

      historyList.innerHTML = rows.map(r => {
        const auf   = r.AUFNR || r.aufnr || '-';
        const vor   = r.VORNR || r.vornr || '-';
        const qty   = r.QTY   || r.qty   || '-';
        const me    = (r.MEINH || r.meinh || '-') || '';
        const bud   = fmtYMD(r.BUDAT || r.budat || '-');
        const wc    = r.ARBPL0|| r.arbpl0|| '-';
        const mkx   = r.MAKTX || r.maktx || '-';
        const nik   = r.PERNR || r.pernr || r.NIK || r.nik || '-';

        return `<tr class="odd:bg-white even:bg-slate-50">
          <td class="px-3 py-2 border-b font-mono">${auf} / ${vor}</td>
          <td class="px-3 py-2 border-b font-mono">${nik}</td>
          <td class="px-3 py-2 border-b font-mono">${qty} ${me}</td>
          <td class="px-3 py-2 border-b">${bud}</td>
          <td class="px-3 py-2 border-b">${wc}</td>
          <td class="px-3 py-2 border-b">${mkx}</td>
        </tr>`;
      }).join('');
    })
    .catch(err => {
      historyLoading.classList.add('hidden');
      historyEmpty.classList.remove('hidden');
      historyEmpty.textContent = 'Gagal memuat: ' + (err?.message || err);
    });
  }

  function closeHistoryModal(){
    historyModal?.classList.add('hidden');
    historyModal?.classList.remove('flex');
  }

  historyBtn?.addEventListener('click', openHistoryModalScan);
  historyClose?.addEventListener('click', closeHistoryModal);
  historyOk?.addEventListener('click', closeHistoryModal);
  historyModal?.addEventListener('click', e => { if (e.target === historyModal) closeHistoryModal(); });

}); // end DOMContentLoaded
</script>
@endpush