{{-- resources/views/hasil.blade.php --}}
@extends('layout')

@section('content')
<div class="px-4 py-8 md:px-6">
  <div class="max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/60 overflow-hidden">
      <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-blue-900 text-white">
        <h1 class="text-lg md:text-2xl font-bold">Hasil Konfirmasi</h1>
        <p class="text-white/80 text-sm">Rekap dari RFC Z_FM_YPPR062</p>
      </div>

      {{-- Filter ringkas --}}
      <div class="px-5 py-4 flex flex-wrap gap-3 items-end">
        <div>
          <label class="text-xs text-slate-600">NIK Operator (PERNR)</label>
          <input id="filter-pernr" type="text" class="mt-1 w-48 rounded-lg border border-slate-300 px-3 py-1.5" readonly>
        </div>
        <div>
          <label class="text-xs text-slate-600">Tanggal (BUDAT)</label>
          <input id="filter-budat" type="text" class="mt-1 w-40 rounded-lg border border-slate-300 px-3 py-1.5" readonly>
        </div>
        <div class="ml-auto flex gap-2">
          <a href="/scan" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
            Kembali ke Scan
          </a>
          <button id="btn-refresh" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
            Muat Ulang
          </button>
        </div>
      </div>

      {{-- Tabel --}}
      <div class="px-5 pb-6 overflow-x-auto">
        <table id="hasil-table" class="min-w-[960px] w-full text-sm">
          <thead>
            <tr class="bg-slate-50 text-slate-700">
              <th class="px-3 py-2 text-left">No.</th>
              <th class="px-3 py-2 text-left">Matrial Doc</th>
              <th class="px-3 py-2 text-left">Posting Date</th>
              <th class="px-3 py-2 text-left">NIK</th>
              <th class="px-3 py-2 text-left">Operator</th>
              <th class="px-3 py-2 text-left">Work Center</th>
              <th class="px-3 py-2 text-left">Desc. Work Center</th>
              <th class="px-3 py-2 text-left">Activity</th>
              <th class="px-3 py-2 text-left">Operation</th>
              <th class="px-3 py-2 text-left">PRO</th>
              <th class="px-3 py-2 text-left">Material</th>
              <th class="px-3 py-2 text-left">Desc</th>
              <th class="px-3 py-2 text-right">QTY TARGET</th>
              <th class="px-3 py-2 text-right">QTY PRO</th>
              <th class="px-3 py-2 text-right">Qty Konfirmasi</th>
              <th class="px-3 py-2 text-right">QTY Sisa</th>
              <th class="px-3 py-2 text-right">Fin. Inspect</th>
              <th class="px-3 py-2 text-left">Uom</th>
              <th class="px-3 py-2 text-right">Cap. Target</th>
              <th class="px-3 py-2 text-right">Cap. WC</th>
              <th class="px-3 py-2 text-right">Menit Kerja</th>
              <th class="px-3 py-2 text-right">Menit Inspect</th>
              <th class="px-3 py-2 text-right">Total Menit Kerja</th>
              <th class="px-3 py-2 text-right">Total Menit Inspect</th>
              <th class="px-3 py-2 text-right">Total Detik Inspect</th>
              <th class="px-3 py-2 text-left">Setting Time</th>
              <th class="px-3 py-2 text-left">Perkalian Routing</th>
              <th class="px-3 py-2 text-left">KPI</th>
              <th class="px-3 py-2 text-right">Total % KPI</th>
              <th class="px-3 py-2 text-right">Avg. KPI</th>
              <th class="px-3 py-2 text-right">Time Confirmation</th>
              <th class="px-3 py-2 text-left">Variant</th>
              <th class="px-3 py-2 text-left">Variant (2)</th>
              <th class="px-3 py-2 text-left">Stock Type</th>
              <th class="px-3 py-2 text-left">Start Date</th>
              <th class="px-3 py-2 text-left">Finish Date</th>
              <th class="px-3 py-2 text-left">Batch</th>
            </tr>
          </thead>
          <tbody id="hasil-tbody">
            <tr><td class="px-3 py-4 text-slate-500" colspan="37">Memuat data…</td></tr>
          </tbody>
        </table>
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
