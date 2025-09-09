// resources/js/app.js
import './bootstrap';

// Muat Quagga2 hanya di halaman yang butuh (ada #reader / tombol #openScanner)
const needQuagga = document.getElementById('reader') || document.getElementById('openScanner');

if (needQuagga) {
  import('@ericblade/quagga2')
    .then(({ default: Quagga }) => {
      // expose ke global agar script inline di Blade tetap bisa pakai window.Quagga
      window.Quagga = Quagga;
      // beri sinyal ke script view kalau sudah siap
      window.dispatchEvent(new Event('quagga:ready'));
    })
    .catch(err => {
      console.error('Gagal memuat Quagga2:', err);
      window.dispatchEvent(new Event('quagga:failed'));
    });
}
