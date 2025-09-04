<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ?? 'Barcode Scanner App' }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          animation: {
            'fade-in': 'fadeIn 0.5s ease-in-out',
            'slide-up': 'slideUp 0.3s ease-out',
            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0' },
              '100%': { opacity: '1' }
            },
            slideUp: {
              '0%': { transform: 'translateY(20px)', opacity: '0' },
              '100%': { transform: 'translateY(0)', opacity: '1' }
            }
          }
        }
      }
    }
  </script>
  @stack('head')
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-100 antialiased">
  <!-- Loading Animation -->
  <div id="pageLoader" class="fixed inset-0 bg-white z-50 flex items-center justify-center">
    <div class="text-center">
      <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
      <p class="text-slate-600 font-medium">Loading...</p>
    </div>
  </div>

  <!-- Main Content -->
  <main class="animate-fade-in">
    @yield('content')
  </main>

  <!-- Footer -->
  <footer class="mt-16 py-8 text-center text-slate-500 text-sm">
    <div class="max-w-2xl mx-auto px-6">
      <div class="border-t border-slate-200 pt-6">
        <p>&copy; {{ date('Y') }} Barcode Scanner App. All rights reserved.</p>
      </div>
    </div>
  </footer>

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

    // Add smooth scrolling
    document.documentElement.style.scrollBehavior = 'smooth';
  </script>
</body>
</html>