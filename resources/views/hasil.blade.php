{{-- resources/views/hasil.blade.php --}}
@extends('layout')

@section('content')
@php
  $dispos = request('dispos') ?: request('mrps') ?: request('dispo') ?: request('mrp');
  $mrpArr = array_values(array_filter(array_map('trim', explode(',', (string) $dispos))));
  $hasMrp = request('werks') && count($mrpArr) > 0;
  $isMrpMode = $hasMrp && !request('pernr');

  // optional: label ringkas untuk title (mis: D24 +1)
  $mrpLabel = count($mrpArr) > 1 ? ($mrpArr[0].' +'.(count($mrpArr)-1)) : ($mrpArr[0] ?? '');
@endphp

    <div class="px-4 py-8 md:px-6">
        <div class="max-w-[1280px] mx-auto">

            {{-- ===== Header kartu ===== --}}
            <div class="bg-white rounded-3xl shadow-xl border border-slate-200/60 overflow-hidden mt-4 sm:mt-6 lg:mt-8">
                <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-blue-900 text-white flex items-center gap-3">
                    <h1 class="text-lg md:text-2xl font-bold">
                        Hasil Konfirmasi
                        <span class="ml-1 text-white/90 text-base md:text-lg font-semibold">
                            / <span id="title-pernr">
                                {{ request('pernr') ?: ($isMrpMode ? ((request('bagian') ?: '-') . ' / ' . request('werks')) : '-') }}
                            </span>
                            <span id="title-budat">
                                {{ request('from') && request('to')
                                    ? substr(request('from'), 6, 2) .
                                        '/' .
                                        substr(request('from'), 4, 2) .
                                        '/' .
                                        substr(request('from'), 0, 4) .
                                        ' – ' .
                                        substr(request('to'), 6, 2) .
                                        '/' .
                                        substr(request('to'), 4, 2) .
                                        '/' .
                                        substr(request('to'), 0, 4)
                                    : (request('budat') ?:
                                        request('date') ?:
                                        '-') }}
                            </span>
                        </span>
                    </h1>
                </div>

                {{-- ===== Hidden fields (tetap ada untuk JS, tapi tidak terlihat) ===== --}}
                <div class="hidden">
                    <input id="filter-pernr" type="hidden" value="{{ request('pernr') }}">
                    <input id="filter-budat" type="hidden" value="{{ request('budat') ?: request('date') }}">
                    <input id="filter-budat-from" type="hidden" value="{{ request('from') ?: request('budat_from') }}">
                    <input id="filter-budat-to" type="hidden" value="{{ request('to') ?: request('budat_to') }}">
                </div>

                {{-- ===== Bar aksi (Tombol & Datepicker & Label MRP) ===== --}}
                <div class="px-5 py-4 flex flex-col md:flex-row gap-3 items-center justify-between border-b border-slate-100">

                    {{-- Grup 1: Tombol Aksi (di kiri pada desktop, di atas pada mobile) --}}
                    <div class="flex gap-3 w-full md:w-auto">
                        {{-- Tombol Kembali --}}
                        <a href="/scan"
                            class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm transition-colors flex-1 md:flex-none">
                            Kembali
                        </a>

                        {{-- Tombol Refresh --}}
                        <button id="btn-refresh"
                            class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm transition-colors flex-1 md:flex-none">
                            Refresh
                        </button>
                    </div>

                    {{-- Grup 2: Datepicker & Label MRP (di kanan pada desktop, di bawah pada mobile) --}}
                    <div class="flex gap-3 w-full md:w-auto items-center">
                        {{-- Datepicker --}}
                        {{-- UBAH: flex-1 agar mengisi ruang di HP, dan perlebar width di desktop (md:w-[280px] lg:w-[320px]) --}}
                        <div class="relative flex-1 md:flex-none md:w-[280px] lg:w-[320px]">
                            <input id="hasil-daterange-picker" type="text"
                                class="inline-flex h-9 w-full rounded-lg border border-slate-300 px-3 py-2 pl-9 text-center text-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-500 placeholder:text-slate-400 truncate"
                                placeholder="Pilih tanggal..." required>

                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        {{-- Slot Khusus untuk Label MRP --}}
                        {{-- UBAH: tambahkan flex-none agar ukurannya pas dengan isinya --}}
                        <div id="header-mrp-slot" class="hidden empty:hidden flex-none">
                            {{-- JS akan menyuntikkan badge MRP di sini --}}
                        </div>
                    </div>
                </div>
                <div id="hasil-summary" class="px-5 pb-4 empty:hidden"></div>

                {{-- ===== Tabel ===== --}}
                <div class="px-5 pb-6">
                    <div class="overflow-auto max-h-[72vh] rounded-xl border border-slate-200">
                        <table id="hasil-table" class="w-full {{ $isMrpMode ? 'min-w-0' : 'min-w-[1200px]' }} text-sm text-center">
                            <thead class="bg-green-700/90 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[70px]">
                                        No.
                                    </th>
                                    <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px] whitespace-nowrap">
                                        Tanggal
                                    </th>

                                    @if($isMrpMode)
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[180px] whitespace-nowrap">
                                            Operator
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px] whitespace-nowrap">
                                            Total Menit Kerja
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[130px] whitespace-nowrap">
                                            Total Menit Inspect
                                        </th>
                                    @else
                                        {{-- HEADER DETAIL (seperti kamu punya sekarang) --}}
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px] whitespace-nowrap">
                                            Work Center
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[160px]">
                                            Desc. Work Center
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[160px]">
                                            PRO
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[150px]">
                                            Material
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[320px]">
                                            Desc
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[110px] whitespace-nowrap">
                                            QTY PRO
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[140px] whitespace-nowrap">
                                            Qty Konfirmasi
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[110px] whitespace-nowrap">
                                            QTY Sisa
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[80px] whitespace-nowrap">
                                            Uom
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[120px] whitespace-nowrap">
                                            Menit Kerja
                                        </th>
                                        <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider border-b border-green-400 min-w-[130px] whitespace-nowrap">
                                            Menit Inspect
                                        </th>
                                    @endif
                                </tr>
                            </thead>

                            {{-- Baris data diisi oleh hasil.js --}}
                            <tbody id="hasil-tbody" class="bg-white divide-y divide-slate-200 text-slate-800">
                                <tr>
                                    <td class="px-3 py-4 text-slate-500" colspan="{{ $isMrpMode ? 5 : 14 }}">Memuat data…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    {{-- ===== Modal Detail MRP (dipakai saat klik row summary) ===== --}}
    <div id="mrpDetailModal"
        class="fixed inset-0 z-[9999] hidden"
        aria-hidden="true">

        {{-- backdrop --}}
        <div class="mrp-detail-backdrop absolute inset-0 bg-black/50"></div>

        {{-- panel --}}
        <div class="relative w-full h-full sm:h-auto sm:max-h-[92vh] sm:w-[min(1100px,100%)]
                    sm:mx-auto sm:my-6 bg-white sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col">

            {{-- HEADER MODAL (STICKY) --}}
            <div class="sticky top-0 z-30 px-4 py-3 bg-gradient-to-r from-emerald-600 to-blue-900 text-white
                        flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-base font-bold leading-tight">Detail Operator</div>
                    <div id="mrpDetailMeta" class="text-xs text-white/90 truncate">-</div>
                </div>

                <button id="mrpDetailClose"
                        type="button"
                        class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20">
                    ✕
                </button>
            </div>

            {{-- INFO RINGKAS --}}
            <div class="px-4 py-2 border-b text-xs text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                <div><span class="font-semibold">Tanggal:</span> <span id="mrpDetailTanggal">-</span></div>
                <div><span class="font-semibold">Operator:</span> <span id="mrpDetailOperator">-</span></div>
                <div><span class="font-semibold">MRP:</span> <span id="mrpDetailMrp">-</span></div>
                <div><span class="font-semibold">Plant:</span> <span id="mrpDetailWerks">-</span></div>
            </div>


            {{-- BODY TABLE (SCROLL) --}}
            <div class="flex-1 overflow-auto">
            {{-- TOTAL PER TANGGAL (diisi JS) --}}
            <div id="mrpDetailPerDate" class="px-4 py-3 border-b bg-slate-50 hidden"></div>
                <table class="w-full min-w-[1200px] text-sm text-center">
                    <thead class="bg-slate-100 sticky top-0 z-20">
                        <tr>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">No.</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Tanggal</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Operator</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Work Center</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Desc. Work Center</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">PRO</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Material</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Desc</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">QTY PRO</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Qty Konfirmasi</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">QTY Sisa</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Uom</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Menit Kerja</th>
                            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Menit Inspect</th>
                        </tr>
                    </thead>

                    <tbody id="mrpDetailTbody" class="bg-white divide-y divide-slate-200">
                        <tr><td class="px-3 py-4 text-slate-500" colspan="14">Klik baris summary untuk melihat detail…</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- FOOTER (STICKY BOTTOM) --}}
            <div class="sticky bottom-0 z-30 bg-white border-t px-4 py-3 flex justify-end">
                <button id="mrpDetailClose2"
                        type="button"
                        class="h-9 px-4 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/hasil.css') }}?v={{ filemtime(public_path('css/hasil.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
@endpush

@push('scripts')
    {{-- Flatpickr dulu --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

    {{-- Baru file Anda --}}
    <script src="{{ asset('js/hasil.js') }}?v={{ filemtime(public_path('js/hasil.js')) }}"></script>
@endpush
