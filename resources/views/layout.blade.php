<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Konfirmasi PRO App' }}</title>

    {{-- Load CSS & JS via Vite (Tailwind v4 di-import dari resources/css/app.css) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Patch iOS Safari agar layout stabil (anti auto-zoom & text inflation) --}}
    <style>
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        /* Cegah iOS zoom saat fokus input font <16px */
        input,
        select,
        textarea {
            font-size: 16px;
        }

        button {
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }
    </style>

    @stack('head')
    {{-- Tambahkan stack untuk stylesheet halaman --}}
    @stack('styles')
</head>

<body
    class="min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100 antialiased touch-manipulation">

    {{-- Loading Animation --}}
    <div id="pageLoader" class="fixed inset-0 bg-white z-[60] flex items-center justify-center">
        <div class="text-center">
            <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
            <p class="text-slate-600 font-medium">Loading...</p>
        </div>
    </div>

    {{-- Main Content --}}
    <main class="flex-1 animate-fade-in">
        @yield('content')
    </main>

    {{-- Footer (sticky di bawah) --}}
    <footer class="mt-auto py-8 text-center text-slate-500 text-sm">
        <div class="max-w-2xl mx-auto px-6">
            <div class="border-t border-slate-200 pt-6">
                <p>&copy; {{ date('Y') }} PT Kayu Mebel Indonesia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    {{-- Stack untuk script per-halaman --}}
    @stack('scripts')

    <script>
        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            const loader = document.getElementById('pageLoader');
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        });

        // Smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
    @auth
        @include('partials.session-timer')
    @endauth

</body>

</html>
