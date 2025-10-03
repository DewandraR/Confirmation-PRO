@extends('layout')

@section('content')
{{-- ===== Header (gradien) ===== --}}
<div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
  <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvZ2lkIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
  <div class="relative px-4 py-6">
    <div class="max-w-6xl mx-auto">
      {{-- Tombol Kembali (href akan dioverride JS agar ikut ?pernr) --}}
      <div class="flex items-center gap-2 mb-4">
        <a id="back-link"
           href="{{ route('scan') }}"
           class="group flex items-center gap-1 px-3 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-white text-sm transition-all duration-200">
          <svg class="w-3 h-3 group-hover:-translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Kembali
        </a>
      </div>

      {{-- Judul --}}
      <div class="flex items-center gap-3">
        <div class="inline-flex items-center justify-center w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl">
          <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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

{{-- ===== Konten ===== --}}
<div class="px-4 py-6 -mt-3 relative z-10">
  <div class="max-w-6xl mx-auto space-y-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 overflow-hidden">
      {{-- Header section tabel --}}
      <div class="bg-gradient-to-r from-green-700 to-blue-900 px-4 py-3 border-b border-slate-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z"/>
              </svg>
            </div>
            <div><div id="headAUFNR" class="font-bold text-lg text-white">-</div></div>
          </div>

          {{-- Scan lagi (href dioverride JS juga) --}}
          <a id="scan-again-link" href="{{ route('scan') }}"
             class="ml-auto transform -translate-y-0.5 inline-flex items-center gap-2
                    h-8 px-3 rounded-full bg-white/20 hover:bg-white/30
                    text-white text-xs font-semibold shadow-md hover:shadow-lg transition">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 4a1 1 0 011-1h5l2 2h9a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V4z"/>
            </svg>
            <span>Scan Lagi</span>
          </a>
        </div>
      </div>

      {{-- Loading --}}
      <div id="loading" class="p-6 text-center">
        <div class="flex flex-col items-center gap-3">
          <div class="w-10 h-10 border-4 border-green-200 border-t-green-600 rounded-full animate-spin"></div>
          <div class="text-sm text-slate-500 font-medium">Memuat data...</div>
          <div class="text-xs text-slate-400">Mengambil informasi dari server</div>
        </div>
      </div>

      {{-- Konten tabel --}}
      <div id="content" class="hidden">
        <div class="p-4">
          {{-- Bar kontrol atas --}}
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
              <input type="checkbox" id="selectAll" class="w-4 h-4 text-green-600 rounded border-slate-300">
              <label for="selectAll" class="text-sm text-slate-600 font-medium">Pilih Semua</label>
            </div>

            {{-- ===== QUICK DATE FILTERS (NEW) ===== --}}
            <div class="w-full md:w-auto">
              <div class="flex flex-wrap items-center gap-2">
                <button id="fltToday"
                        class="px-3 py-1.5 rounded-full border border-slate-300 text-sm font-semibold hover:bg-slate-50">
                  Today
                </button>
                <button id="fltOutgoing"
                        class="px-3 py-1.5 rounded-full border border-slate-300 text-sm font-semibold hover:bg-slate-50">
                  Outgoing
                </button>
                <button id="fltPeriod"
                        class="px-3 py-1.5 rounded-full border border-slate-300 text-sm font-semibold hover:bg-slate-50">
                  Period
                </button>

                <!-- tambahkan di samping tombol Period -->
<button type="button" id="fltAllDate"
  class="px-3 py-1.5 rounded-full border border-slate-300 text-sm font-semibold hover:bg-slate-50">
  All date
</button>


                {{-- Range picker hanya saat Period aktif --}}
                <div id="periodPicker" class="hidden items-center gap-2 ml-1">
                  <input type="date" id="periodFrom"
                         class="px-2 py-1.5 rounded-md border border-slate-300 text-sm">
                  <span class="text-slate-400 text-sm">s/d</span>
                  <input type="date" id="periodTo"
                         class="px-2 py-1.5 rounded-md border border-slate-300 text-sm">
                  <button id="applyPeriod"
                          class="px-3 py-1.5 rounded-md text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700">
                    Terapkan
                  </button>
                  <button id="clearFilter"
                          class="px-2 py-1.5 rounded-md text-sm text-slate-600 hover:underline">
                    Bersihkan
                  </button>
                </div>
              </div>
            </div>
            {{-- ===== END QUICK DATE FILTERS ===== --}}

            <div class="flex items-center gap-6 mt-3 sm:mt-0">
              <div class="flex items-center gap-2">
                <label for="budat-input-text" class="text-xs text-slate-500 font-bold">Posting Date</label>

                {{-- hidden: yyyy-mm-dd (dibaca JS) --}}
                <input type="date" id="budat-input"
                       value="{{ now()->format('Y-m-d') }}"
                       max="{{ now()->format('Y-m-d') }}"
                       class="sr-only">

                {{-- terlihat: dd/mm/yyyy (user input) --}}
                <input type="text" id="budat-input-text"
                       value="{{ now()->format('d/m/Y') }}"
                       inputmode="numeric" pattern="\d{2}/\d{2}/\d{4}"
                       placeholder="hh/bb/tttt"
                       class="px-2 py-1 rounded-md border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-green-400/40 focus:border-green-500 w-[120px] text-center">

                <button type="button" id="budat-open"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50"
                        title="Pilih tanggal">
                  <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>

              <div class="text-xs text-slate-500">
                Total: <span id="totalItems">0</span> item(s)
              </div>
            </div>

            {{-- Search --}}
            <div class="w-full md:w-72">
              <label for="searchInput" class="sr-only">Cari</label>
              <div class="relative">
                <input id="searchInput" type="text" placeholder="Cari data…"
                       class="w-full h-10 pl-9 pr-9 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-500 text-sm"
                       autocomplete="off">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                </svg>
                <button type="button" id="clearSearch"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full hover:bg-slate-100 text-slate-500 hidden"
                        title="Bersihkan">✕</button>
              </div>
              <div class="mt-1 text-[11px] text-slate-500">
                Ditampilkan: <span id="shownCount">0</span>
              </div>
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
                  <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px]">PRO</th>

                  {{-- ====== TAMBAHAN BARU (sinkron dengan detail.js) ====== --}}
                  
                  <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[110px]">Qty PRO</th>
                  {{-- ====== AKHIR TAMBAHAN ====== --}}

                  <th class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Qty Input</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[100px]">MRP</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[100px]">Control Key</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px]">Material</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[200px]">Description</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px]">Work Center</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[100px]">NIK Operator</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Nama Operator</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Start Date</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Finish Date</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[130px]">Sales Order</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[90px]">Item</th>

                </tr>
              </thead>
              <tbody id="tableBody" class="bg-white divide-y divide-slate-200"></tbody>
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

{{-- ===== Modal Konfirmasi ===== --}}
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

{{-- ===== Modal Error ===== --}}
<div id="error-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
    <div class="bg-gradient-to-r from-red-50 to-red-100 px-4 py-3 border-b border-red-200">
      <h4 class="text-lg font-semibold text-red-800">Konfirmasi Gagal</h4>
    </div>
    <div class="p-4 space-y-4">
      <p id="error-message" class="text-sm text-slate-700"></p>
    </div>
    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
      <button id="error-ok-button" type="button" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition-colors">OK</button>
    </div>
  </div>
</div>

{{-- ===== Modal Warning ===== --}}
<div id="warning-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl border border-slate-200/50 w-full max-w-sm overflow-hidden">
    <div id="warning-header" class="bg-gradient-to-r from-yellow-50 to-yellow-100 px-4 py-3 border-b border-yellow-200">
      <h4 id="warning-title" class="text-lg font-semibold text-yellow-800">Peringatan</h4>
    </div>
    <div class="p-4 space-y-3">
      <p id="warning-message" class="text-sm text-slate-700"></p>
      <ul id="warning-list" class="space-y-2 text-sm hidden"></ul>
    </div>
    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
      <button id="warning-ok-button" type="button" class="px-4 py-2 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold transition-colors">OK</button>
    </div>
  </div>
</div>

{{-- ===== Modal Success ===== --}}
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

{{-- ===== Head extras (dibaca di detail.js) ===== --}}
@push('head')
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="scan-url" content="{{ route('scan') }}">
@endpush

{{-- ===== Styles ===== --}}
@push('styles')
  <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endpush

{{-- ===== Scripts ===== --}}
@push('scripts')
  {{-- cache-busting opsional --}}
  <script src="{{ asset('js/detail.js') }}?v={{ filemtime(public_path('js/detail.js')) }}"></script>
@endpush
