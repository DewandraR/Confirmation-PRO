{{-- resources/views/hasil.blade.php --}}
@extends('layout')

@section('content')
<div class="px-4 py-8 md:px-6">
  <div class="max-w-[1280px] mx-auto">

    {{-- ===== Header kartu ===== --}}
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/60 overflow-hidden mt-4 sm:mt-6 lg:mt-8">
      <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-blue-900 text-white flex items-center gap-3">
        <h1 class="text-lg md:text-2xl font-bold">
          Hasil Konfirmasi
          <span class="ml-1 text-white/90 text-base md:text-lg font-semibold">
            / <span id="title-pernr">{{ request('pernr') ?: '-' }}</span>
            / <span id="title-budat">{{ request('budat') ?: request('date') ?: '-' }}</span>
          </span>
        </h1>
      </div>

      {{-- ===== Hidden fields (tetap ada untuk JS, tapi tidak terlihat) ===== --}}
      <div class="hidden">
        <input id="filter-pernr" type="hidden" value="{{ request('pernr') }}">
        <input id="filter-budat" type="hidden" value="{{ request('budat') ?: request('date') }}">
      </div>

      {{-- ===== Bar aksi (geser ke kiri + tombol pilih tanggal) ===== --}}
      <div class="px-5 py-4 flex flex-wrap gap-3 items-end">
        <div class="ml-auto flex gap-2">
          <a href="/scan"
             class="inline-flex items-center h-9 px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
            Kembali
          </a>

          <button id="btn-refresh"
                  class="inline-flex items-center h-9 px-3 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
            Refresh
          </button>

          {{-- Ikon kalender (dibuka dengan native date picker) --}}
          <button id="btn-date" type="button" title="Pilih tanggal"
                  class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </button>

          {{-- Kolom tanggal (dd/mm/yyyy) — bisa diketik --}}
          <input id="date-display" type="text"
                 inputmode="numeric" pattern="\d{2}/\d{2}/\d{4}"
                 placeholder="dd/mm/yyyy"
                 class="inline-flex h-9 px-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-500 w-[120px] text-center" />

          {{-- Native date picker tersembunyi (pemicu kalender) --}}
          <input id="choose-date" type="date" class="sr-only">
        </div>
      </div>

      <!-- Ringkasan (Operator, Total Menit Kerja, Total Menit Inspect) diisi oleh JS -->
      {{-- <div id="hasil-summary" class="px-5 pb-2"></div> --}}

      {{-- ===== Tabel ===== --}}
      <div class="px-5 pb-6">
        <div class="overflow-auto max-h-[72vh] rounded-xl border border-slate-200">
          <table id="hasil-table" class="w-full min-w-[1200px] text-sm text-center">
            <thead class="bg-green-700/90 sticky top-0 z-10">
              <tr>
                {{-- ====== Header utama ====== --}}
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[70px]">No.</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px] whitespace-nowrap">Work Center</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[160px]">Desc. Work Center</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[160px]">PRO</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">Material</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[320px]">Desc</th>

                {{-- ====== Kolom-kolom tambahan ====== --}}
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[110px] whitespace-nowrap">QTY PRO</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[140px] whitespace-nowrap">Qty Konfirmasi</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[110px] whitespace-nowrap">QTY Sisa</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[80px] whitespace-nowrap">Uom</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px] whitespace-nowrap">Menit Kerja</th>
                <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[130px] whitespace-nowrap">Menit Inspect</th>
              </tr>
            </thead>

            {{-- Baris data diisi oleh hasil.js --}}
            <tbody id="hasil-tbody" class="bg-white divide-y divide-slate-200 text-slate-800">
              <tr>
                <td class="px-3 py-4 text-slate-500" colspan="37">Memuat data…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hasil.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/hasil.js') }}"></script>
@endpush
