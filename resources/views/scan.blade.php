{{-- resources/views/scan.blade.php --}}
@extends('layout')

@push('head')
    <meta name="client-timeout-ms" content="2400000">
    {{-- kirim SAP user aktif ke JS --}}
    <meta name="sap-user" content="{{ session('sap_user') ?? (config('sap.user') ?? '') }}">
@endpush

@section('content')
    {{-- Bagian header dengan gradasi yang disesuaikan --}}
    <div class="bg-gradient-to-br from-green-700 via-green-800 to-blue-900 relative overflow-hidden">
        {{-- perbaiki base64 (sebelumnya korup) --}}
        <div
            class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC41Ij48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSI0Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20">
        </div>
        <div class="relative px-4 py-8 md:px-6">
            <div class="max-w-2xl mx-auto text-center">
                {{-- LOGO PERUSAHAAN --}}
                <div class="mb-3">
                    <img src="{{ asset('images/kmi.jpg') }}" alt="Company Logo"
                        class="mx-auto w-16 h-16 md:w-20 md:h-20 object-contain rounded-xl p-0.5 bg-white">
                </div>
                <h1 class="text-xl md:text-3xl font-bold text-white mb-1 md:mb-2 leading-tight">Konfirmasi PRO App</h1>
                <p class="text-sm md:text-base text-white/80 leading-tight">Pindai barcode atau masukkan data secara manual
                </p>

                {{-- ===== Sapaan Selamat Datang (hanya saat login) ===== --}}
                @php
                    $namaUser =
                        optional(Auth::user())->name ??
                        (optional(Auth::user())->username ??
                            (\Illuminate\Support\Str::before(optional(Auth::user())->email, '@') ?: 'User'));
                @endphp
                @auth
                    <div class="mt-3 md:mt-4">
                        <div class="max-w-2xl mx-auto">
                            <div id="welcomeBanner"
                                class="relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md welcome-type-card"
                                data-username="{{ $namaUser }}">

                                <div class="flex items-center gap-3 px-4 py-3">
                                    <div class="shrink-0 w-9 h-9 rounded-xl bg-white/20 grid place-items-center">
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor"
                                            aria-hidden="true">
                                            <path
                                                d="M12 22a2.25 2.25 0 0 0 2.12-1.5H9.88A2.25 2.25 0 0 0 12 22Zm7.5-6.75h-.75a1.5 1.5 0 0 1-1.5-1.5V10a5.25 5.25 0 1 0-10.5 0v3.75a1.5 1.5 0 0 1-1.5 1.5H4.5a.75.75 0 0 0 0 1.5h15a.75.75 0 0 0 0-1.5Z" />
                                        </svg>




                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <!-- Marquee -->
                                        <div class="welcome-marquee">
                                            <div class="welcome-track">
                                                <span class="font-semibold">Selamat datang, <span
                                                        class="underline decoration-white/40 underline-offset-2">{{ $namaUser }}</span>
                                                    👋 </span>
                                                <span class="opacity-90">Semoga proses konfirmasi berjalan lancar hari
                                                    ini.</span>
                                                <span class="mx-6">•</span>
                                                <!-- duplikasi konten agar scroll kontinu -->
                                                <span class="font-semibold">Selamat datang, <span
                                                        class="underline decoration-white/40 underline-offset-2">{{ $namaUser }}</span>
                                                    👋 </span>
                                                <span class="opacity-90">Semoga proses konfirmasi berjalan lancar hari
                                                    ini.</span>
                                            </div>
                                        </div>

                                        <!-- fallback bila JS/animasi dimatikan -->
                                        <noscript>
                                            <div class="text-white text-sm font-semibold">
                                                Selamat datang, {{ $namaUser }} 👋
                                            </div>
                                            <div class="text-white/80 text-xs">Semoga proses konfirmasi berjalan lancar hari
                                                ini.</div>
                                        </noscript>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                @endauth
                {{-- ===== END Sapaan ===== --}}

            </div>
        </div>
    </div>

    {{-- Kontainer Form Input Data dengan jarak atas yang disesuaikan --}}
    <div class="px-4 py-1 -mt-6 relative z-10 md:px-6">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-200/50 overflow-hidden">
                {{-- Header form input data --}}
                <div class="bg-slate-100 px-5 py-3 border-b border-slate-200">
                    {{-- wrap + gap agar aman di 414px --}}
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-white rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-base md:text-lg font-semibold text-slate-800 leading-tight">Form Input Data
                                </h2>
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
                    <form id="main-form" class="space-y-5" action="{{ route('detail') }}" method="get" data-timeout-ms="2400000">

    {{-- ===================== --}}
    {{-- SECTION 1: DATA PRODUKSI --}}
    {{-- ===================== --}}
    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex w-9 h-9 items-center justify-center rounded-2xl bg-emerald-600/10 text-emerald-700">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 3h6v2H9V3Zm10 5H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800 leading-tight">Data Produksi</div>
                    <div class="text-[11px] text-slate-500 leading-tight">
                        Isi salah satu: <b>Work Center & Plant</b> atau <b>PRO</b>
                    </div>
                </div>
            </div>
        </div>

        {{-- Work Center & Plant --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2 ios-nowrap-row">
                <div class="w-5 h-5 bg-emerald-500 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>

                <div class="min-w-0 flex items-center gap-2">
                    <label class="text-xs font-medium text-slate-700 truncate">Work Center &amp; Plant</label>
                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full shrink-0 whitespace-nowrap">
                        Optional
                    </span>
                </div>

                <button type="button" id="openBackdateHistory"
                    class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold
                    bg-gradient-to-r from-green-600 to-blue-900 text-white shadow
                    hover:from-green-700 hover:to-blue-900 active:scale-[0.98] transition
                    max-w-[50%] sm:max-w-none overflow-hidden text-ellipsis whitespace-nowrap justify-center"
                    title="Lihat histori backdate">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                        fill="currentColor">
                        <path d="M12 8v5l3 3m5-4a8 8 0 11-16 0 8 8 0 0116 0z" />
                    </svg>
                    Histori Backdate
                </button>
            </div>

            <div class="relative group">
                <div class="w-full rounded-xl shadow-sm border-2 border-gray-300
                    group-focus-within:border-emerald-500 group-hover:border-gray-400 transition-colors duration-200
                    px-3 py-1.5 flex items-center gap-2 flex-wrap bg-white">
                    <div class="flex-shrink-0">
                        <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" />
                            </svg>
                        </div>
                    </div>

                    <input id="IV_ARBPL" name="arbpl"
                        class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium"
                        placeholder="Masukkan atau pindai QR Work Center" />

                    <div class="relative flex items-center shrink-0">
                        <select id="IV_WERKS" name="werks"
                            class="bg-transparent outline-none text-xs font-medium text-slate-700 appearance-none pr-6 text-right">
                            <option value="">Pilih Plant</option>
                            <option value="1000">1000</option>
                            <option value="1001">1001</option>
                            <option value="1200">1200</option>
                            <option value="2000">2000</option>
                            <option value="3000">3000</option>
                        </select>
                        <svg class="w-4 h-4 text-emerald-600 pointer-events-none absolute right-0 top-1/2 -translate-y-1/2"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5H7z" />
                        </svg>
                    </div>

                    <button type="button" id="openQrScanner"
                        class="shrink-0 p-1.5 rounded-lg bg-gradient-to-r from-green-600 to-blue-900
                        hover:from-green-700 hover:to-blue-900 transition-all duration-200
                        shadow-md hover:shadow-lg hover:scale-105"
                        title="Buka QR Scanner">
                        <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Divider "ATAU" --}}
        <div class="my-4 flex items-center">
            <div class="flex-grow border-t border-slate-200"></div>
            <span class="mx-4 text-[11px] text-slate-400 font-semibold tracking-wide">ATAU</span>
            <div class="flex-grow border-t border-slate-200"></div>
        </div>

        {{-- PRO --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
                <div class="w-5 h-5 bg-green-500 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 110 2h-1v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4z" />
                    </svg>
                </div>

                <label class="text-xs font-medium text-slate-700">PRO</label>
                <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full shrink-0">Optional</span>

                <button type="button" id="openHasilKonfirmasi"
                    class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold
                    bg-gradient-to-r from-emerald-600 to-blue-900 text-white shadow
                    hover:from-emerald-700 hover:to-blue-900 active:scale-[0.98] transition min-w-[140px] justify-center"
                    title="Lihat hasil konfirmasi">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                        fill="currentColor">
                        <path d="M12 8v8m4-4H8m12 0a8 8 0 11-16 0 8 8 0 0116 0z" />
                    </svg>
                    Hasil Konfirmasi
                </button>
            </div>

            <div class="relative group">
                <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200
                    group-focus-within:border-green-500 group-hover:border-slate-300 transition-colors
                    px-3 py-1.5 flex items-center gap-2 flex-wrap">
                    <div class="flex-shrink-0">
                        <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                    </div>

                    <input id="IV_AUFNR" name="aufnr"
                        class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium"
                        placeholder="Masukkan atau pindai barcode PRO" />

                    <button type="button" id="openScanner"
                        class="shrink-0 p-1.5 rounded-lg bg-gradient-to-r from-green-600 to-blue-900
                        hover:from-green-700 hover:to-blue-900 transition-all duration-200 shadow-md
                        hover:shadow-lg hover:scale-105"
                        title="Buka kamera">
                        <svg class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- list PRO multiple (tetap) --}}
            <div id="aufnr-list-container" class="space-y-1"></div>
        </div>
    </div>


    {{-- ===================== --}}
    {{-- SECTION 2: DOKUMEN WI --}}
    {{-- ===================== --}}
    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-50/80 to-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex w-9 h-9 items-center justify-center rounded-2xl bg-blue-600/10 text-blue-700">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 2h7l5 5v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V8h4.5L14 3.5Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800 leading-tight">Kode Dokumen WI</div>
                    <div class="text-[11px] text-slate-500 leading-tight">
                        Bisa lebih dari satu (pisah spasi/koma). Tekan <b>Enter</b> untuk menambah chip.
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <div class="relative group space-y-1">
                <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200
                    group-focus-within:border-blue-500 group-hover:border-slate-300 transition-colors
                    px-3 py-1.5 flex items-center gap-2">
                    <div class="flex-shrink-0">
                        <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>

                    <input id="wi_code" name="wi_code"
                        class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium"
                        placeholder="Ketik WI lalu Enter (contoh: WI12345, WI67890)" />
                </div>

                {{-- daftar chip WI --}}
                <div id="wi-list-container" class="flex flex-wrap gap-1 pt-1"></div>

                <div class="text-[11px] text-slate-500">
                    <span class="font-semibold text-blue-700">Hint:</span>
                    Kalau ada PRO yang butuh WI, kamu bisa copy dari detail monitor dan tempel di sini.
                </div>
            </div>
        </div>
    </div>


    {{-- ===================== --}}
    {{-- SECTION 3: IDENTITAS OPERATOR --}}
    {{-- ===================== --}}
    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-amber-50/80 to-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex w-9 h-9 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-700">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4.5A1.5 1.5 0 0 0 6.5 20h11A1.5 1.5 0 0 0 19 18.5C19 16 16 14 12 14Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800 leading-tight">Identitas Operator</div>
                    <div class="text-[11px] text-slate-500 leading-tight">Wajib diisi sebelum kirim data.</div>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
                <label class="text-xs font-medium text-slate-700">NIK Operator</label>
                <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded-full shrink-0">Required</span>
            </div>

            <div class="relative group">
                <div class="w-full bg-white rounded-xl shadow-sm border-2 border-slate-200
                    group-focus-within:border-amber-500 group-hover:border-slate-300 transition-colors
                    px-3 py-1.5 flex items-center gap-2">
                    <div class="flex-shrink-0">
                        <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z" />
                            </svg>
                        </div>
                    </div>

                    <input id="IV_PERNR" name="pernr"
                        class="min-w-0 flex-1 outline-none bg-transparent text-xs placeholder-slate-400 font-medium"
                        placeholder="Masukkan NIK Operator" />
                </div>
            </div>
        </div>
    </div>


    {{-- SUBMIT --}}
    <div class="pt-1">
        <button type="button" id="submitBtn"
            class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-green-700 to-blue-900
            hover:from-green-800 hover:to-blue-900 text-white font-semibold text-sm
            shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-[1.01] active:scale-[0.98]
            flex items-center justify-center gap-2">
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            Kirim Data
        </button>

        <div class="mt-2 text-[11px] text-slate-500 text-center">
            Pastikan <b>NIK Operator</b> terisi. (Work Center/Plant atau PRO boleh salah satu)
        </div>
    </div>
</form>
                </div>
            </div>

            <div class="mt-6 bg-gradient-to-r from-slate-50 to-green-50 rounded-xl p-4 border border-slate-200">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex-shrink-0 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 mb-1">Tips Penggunaan</h3>
                        <ul class="text-xs text-slate-600 space-y-0.5">
                            <li>• Pastikan untuk <b>menghidupkan dan mengizinkan kamera di browser anda</b>, saat akan
                                melakukan scan.</li>
                            <li>• Field <b>NIK Operator</b> wajib diisi.</li>
                            <li>• Anda bisa mengisi <b>Work Center & Plant</b>, ATAU <b>PRO</b>.</li>
                            <li>• Pastikan sebelum melakukan scan <b>PRO</b>, barcodenya dalam bentuk <b>PDF</b> atau <b>di
                                    Print fisik</b>.</li>
                            <li>• Posisikan <b>Barcode</b> dan <b>QR Code</b> di area tengah kamera dan hindari pantulan
                                cahaya.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section class="mt-4 px-4 md:px-6">
        <div
            class="max-w-6xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-200/50 overflow-hidden monitor-card">

            {{-- header kartu (match Detail) --}}
            <div class="bg-gradient-to-r from-green-700 to-blue-900 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-white/90 text-xs" id="monitorMeta">0 entri</span>
                </div>
                <button id="monitorRefresh"
                    class="h-8 px-3 rounded-full bg-white/20 hover:bg-white/30 text-white text-xs font-semibold transition">
                    Refresh
                </button>
            </div>

            {{-- isi tabel (tanpa horizontal scroll) --}}
            <div class="p-3">
                <div
                    class="rounded-xl border border-slate-200
                    overflow-x-auto overflow-y-auto
                    max-h-[60vh]">
                    <table class="w-full text-[11.5px] sm:text-sm leading-tight" id="monitorTable">
                        <thead class="bg-green-700/90 text-white sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 text-center text-[10px] sm:text-xs uppercase tracking-wider w-10">#
                                </th>
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[18%]">
                                    PRO</th>
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[10%]">
                                    PRO Qty</th>
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[12%]">
                                    Confirm Qty</th>

                                <!-- ➕ kolom baru -->
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[14%]">
                                    Material</th>
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[20%]">FG
                                    Desc</th>

                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[24%]">
                                    Operator</th>
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[12%]">
                                    Status</th>
                                <th class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[28%]">
                                    Massage</th>
                                <th
                                    class="px-3 py-2 text-left  text-[10px] sm:text-xs uppercase tracking-wider w-[12%] whitespace-nowrap">
                                    Processed At</th>
                            </tr>
                        </thead>
                        <tbody id="monitorBody" class="bg-white divide-y divide-slate-200">
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
    </section>

    {{-- MODALS --}}
    <div id="scannerModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50 p-3">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden">
            <div class="bg-gradient-to-r from-green-700 to-blue-900 px-5 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-white/20 rounded-lg flex items-center justify-center"><svg
                                class="w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 14 2H9zm3 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10z" />
                            </svg></div>
                        <h3 class="text-base font-semibold text-white">Scanner Barcode</h3>
                    </div>
                    <div class="flex items-center gap-2"><button type="button" id="toggleTorch"
                            class="px-2 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Lampu</button><button
                            type="button" id="closeScanner"
                            class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <div id="reader" class="rounded-xl overflow-hidden bg-black shadow-inner"></div>
                <div class="mt-3 p-3 bg-green-50 rounded-xl border border-green-200">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center"><svg
                                class="w-3 h-3 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg></div>
                        <p class="text-xs text-green-800 font-medium">Arahkan kamera ke barcode PRO (AUFNR) dengan jelas
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="qrScannerModal"
        class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50 p-3">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto overflow-hidden">
            <div class="bg-gradient-to-r from-blue-700 to-indigo-900 px-5 py-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-white">Scanner QR Work Center</h3><button type="button"
                        id="closeQrScanner"
                        class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs rounded-lg transition-colors">Tutup</button>
                </div>
            </div>
            <div class="p-4">
                <div id="qr-reader" class="rounded-xl overflow-hidden bg-black shadow-inner border-4 border-slate-200">
                </div>
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
                <div class="flex justify-end"><button type="button"
                        class="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700"
                        onclick="closeError()">OK</button></div>
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
                <div class="flex justify-end gap-2"><button id="logoutCancel" type="button"
                        class="px-4 py-1.5 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100">Tidak</button><button
                        id="logoutConfirm" type="button"
                        class="px-4 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700">Ya, Keluar</button>
                </div>
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
                    Semua operator • Menampilkan seluruh histori
                </div>
            </div>

            <div class="p-4">
                <div class="overflow-auto max-h-[65vh] border border-slate-200 rounded-xl">
                    <table class="min-w-full text-[12px]">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-center font-semibold border-b w-[60px]">No</th>
                                <th class="px-3 py-2 text-left font-semibold border-b w-[190px]">PRO / Activity</th>
                                <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">NIK Operator</th>
                                <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Qty (UoM)</th>
                                <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Posting Date</th>
                                <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Today</th>
                                <th class="px-3 py-2 text-left font-semibold border-b w-[120px]">Work Center</th>
                                <th class="px-3 py-2 text-left font-semibold border-b">Material Desc</th>
                            </tr>
                        </thead>
                        <tbody id="historyList"></tbody>
                    </table>

                    <div id="historyLoading" class="py-10 text-center text-sm text-slate-500">Memuat…</div>
                    <div id="historyEmpty" class="hidden py-10 text-center text-sm text-slate-500">Belum ada histori
                        backdate.</div>
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

    {{-- ⬇️ Modal Detail Monitor (khusus view mobile) --}}
    <div id="monitorDetailModal"
        class="fixed inset-0 hidden z-[80] bg-black/50 backdrop-blur-[1px] p-4
            flex items-center justify-center">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
            <div
                class="px-4 py-3 bg-gradient-to-r from-green-700 to-blue-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-flex w-7 h-7 items-center justify-center rounded-xl bg-white/20">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m5-9a2 2 0 00-2-2h-3l-1-2.5A1 1 0 0013 2h-2a1 1 0 00-.9.6L9 4H6a2 2 0 00-2 2v9a2 2 0 002 2h11a2 2 0 002-2V7z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold leading-tight">Detail Konfirmasi</h3>
                        <div id="monitorDetailMeta" class="text-[11px] text-emerald-100/90"></div>
                    </div>
                </div>
                <button id="monitorDetailClose"
                    class="ml-3 px-3 py-1.5 rounded-full bg-white/15 hover:bg-white/25
                           text-[11px] font-semibold">
                    Tutup
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs text-slate-700">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">PRO</span>
                    <span id="monitorDetailPro" class="font-mono text-right"></span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">PRO Qty</div>
                        <div id="monitorDetailProQty" class="mt-0.5 font-mono"></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Confirm Qty</div>
                        <div id="monitorDetailConfirmQty" class="mt-0.5 font-mono"></div>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Material</div>
                    <div id="monitorDetailMaterial" class="mt-0.5 font-mono"></div>
                </div>

                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">FG Desc</div>
                    <div id="monitorDetailFgDesc" class="mt-0.5 leading-snug"></div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Operator</div>
                        <div id="monitorDetailOperator" class="mt-0.5 leading-snug"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Status</div>
                        <div id="monitorDetailStatusPill" class="mt-0.5"></div>
                    </div>
                </div>

                <div class="pt-2 mt-1 border-t border-slate-200">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Message</div>
                    <div id="monitorDetailMessage"
                        class="text-xs leading-snug whitespace-pre-wrap break-words text-slate-800">
                    </div>
                    <div id="wiCopyContainer" class="hidden mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex flex-col gap-2">
                            <p class="text-[11px] text-amber-800">
                                PRO ini butuh <b>Kode WI</b>. Salin kode di bawah ini lalu tempel ke kolom "Kode Dokumen WI" di form input.
                            </p>
                            <div class="flex gap-2">
                                <input type="text" id="wiCodeTarget" readonly
                                    class="flex-1 text-xs font-mono font-bold text-slate-700 bg-white border border-slate-300 rounded px-2 py-1.5 focus:outline-none">
                                <button type="button" id="btnCopyWi"
                                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ⬇️ Modal Hasil Konfirmasi (baru) --}}
    <!-- Modal Hasil Konfirmasi -->
    <div id="hasilModal"
        class="fixed inset-0 z-[100] hidden p-4 flex items-center justify-center bg-black/40 backdrop-blur-[1px]"
        aria-hidden="true">

        {{-- HAPUS div backdrop dan div wrapper yang sebelumnya ada di sini --}}

        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-gradient-to-r from-emerald-600 to-blue-900 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 8v8m4-4H8m12 0a8 8 0 11-16 0 8 8 0 0116 0z" />
                    </svg>
                    <h3 class="font-semibold">Hasil Konfirmasi</h3>
                </div>
                <p class="text-white/80 text-xs">Masukkan NIK dan tanggal untuk membuka halaman hasil.</p>
            </div>

            <form id="hasilForm" class="px-5 py-4 space-y-3">
                <div>
                    <label class="text-xs text-slate-600">NIK Operator<span class="text-red-600">*</span></label>
                    <input id="hasil-pernr" name="pernr" type="text" inputmode="numeric" autocomplete="off"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        placeholder="contoh: 100234" required>
                </div>
                <!-- ganti blok ini SAJA -->
                <div>
                    <label class="text-xs text-slate-600">Periode Tanggal<span class="text-red-600">*</span></label>
                    <div class="relative mt-1">
                        <input id="hasil-daterange" name="daterange" type="text"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            placeholder="Pilih rentang tanggal..." required>

                        <svg class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">

                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

                        </svg>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">


                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" id="hasilCancel"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Lanjutkan</button>
                    </div>
            </form>
        </div>
    </div>
    </div>

    <!-- Overlay Loading -->
    <div id="overlay" class="hidden fixed inset-0 z-50 grid place-items-center bg-black/40">
        <div
            class="overlay-card rounded-2xl bg-white shadow-2xl px-5 py-4 text-sm text-slate-700 w-[min(92vw,360px)] text-center">
            <div class="flex flex-col items-center gap-2">
                {{-- Pembungkus baru untuk logo dan garis putar --}}
                <div class="relative logo-wrapper">
                    <img src="{{ asset('images/kmi.jpg') }}" alt="Company Logo" class="w-10 h-10 rounded-xl select-none"
                        draggable="false" />
                </div>

                <div id="overlayText" class="font-medium">Mengambil data dari SAP…</div>
                <div class="text-[11px] text-slate-500">Jangan tutup halaman ini.</div>
                <div id="overlayTip" class="mt-1 text-[11px] text-emerald-700/80"></div>
            </div>

            <div class="mt-3 overlay-bar h-1.5 rounded-full bg-slate-200">
                <i class="block h-full rounded-full"></i>
            </div>
        </div>
    </div>
@endsection

{{-- ↓ Tidak ada CSS inline. Semua style khusus halaman dipindah ke scan.css --}}
@push('styles')
    {{-- Kita ganti 'v' nya secara manual untuk memaksa browser download ulang --}}
    <link rel="stylesheet" href="{{ asset('css/scan.css') }}?v=kmi-theme-v1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
@endpush

@push('scripts')
    <script src="{{ asset('js/scan.js') }}?v={{ filemtime(public_path('js/scan.js')) }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
@endpush
