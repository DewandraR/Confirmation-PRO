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

// === Html5Qrcode (baru)
const needHtml5 = document.getElementById('qr-reader') || document.getElementById('openQrScanner');
if (needHtml5) {
  import('html5-qrcode')
    .then((mod) => {
      // sebagian bundler mengekspor named exports
      const Html5Qrcode = mod.Html5Qrcode ?? mod.default?.Html5Qrcode ?? mod.default;
      const Html5QrcodeScanner = mod.Html5QrcodeScanner ?? mod.default?.Html5QrcodeScanner;

      // simpan ke global agar script inline (Blade) tetap bisa akses
      window.Html5Qrcode = Html5Qrcode;
      window.Html5QrcodeScanner = Html5QrcodeScanner;

      window.dispatchEvent(new Event('html5qrcode:ready'));
    })
    .catch(err => {
      console.error('Gagal memuat html5-qrcode:', err);
      window.dispatchEvent(new Event('html5qrcode:failed'));
    });
}
