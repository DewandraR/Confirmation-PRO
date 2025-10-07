// scan.js — versi full (dengan client-timeout & loading overlay)
document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // =================================================================
  // ===== FUNGSI HELPER & STATE
  // =================================================================
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const aufnrList = new Set();

  const aufnrListContainer = document.getElementById('aufnr-list-container');
  const aufnrInput  = document.getElementById('IV_AUFNR');
  const pernrInput  = document.getElementById('IV_PERNR');
  const arbplInput  = document.getElementById('IV_ARBPL');
  const werksInput  = document.getElementById('IV_WERKS');

  // === Prefill dari querystring (NIK Operator)
  const qs = new URLSearchParams(location.search);
  const qsPernr = (qs.get('pernr') || '').trim();
  if (qsPernr && pernrInput) {
    pernrInput.value = qsPernr;
    pernrInput.dispatchEvent(new Event('input', { bubbles: true }));
  }

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
  // ===== PATCH: CLIENT TIMEOUT + OVERLAY HELPERS (baru)
  // =================================================================
  // >>> Tambahkan util di dekat helper lain
  function getClientTimeoutMs() {
    // prioritas: meta -> data-timeout pada form -> default 240s
    const m = document.querySelector('meta[name="client-timeout-ms"]');
    if (m && /^\d+$/.test(m.content)) return parseInt(m.content, 10);
    const f = document.getElementById('main-form');
    const d = f?.dataset?.timeoutMs;
    if (d && /^\d+$/.test(String(d))) return parseInt(d, 10);
    return 240_000; // default 240 dtk
  }

  function showOverlay(msg = 'Mengambil data dari SAP…') {
    const ov = document.getElementById('overlay');
    const t  = document.getElementById('overlayText');
    if (t) t.textContent = msg;
    ov?.classList.remove('hidden');
  }
  function hideOverlay() {
    document.getElementById('overlay')?.classList.add('hidden');
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

  // ➕ helper baru: tampilkan error → setelah OK jalankan thenFn
  function showErrorAndThen(title, msg, thenFn) {
    showError(title, msg);
    const okBtn = document.querySelector('#errorModal button');
    if (okBtn && typeof thenFn === 'function') {
      okBtn.addEventListener('click', () => { try { thenFn(); } catch (_) {} }, { once: true });
    }
  }

  window.closeError = () => {
    errModal?.classList.add('hidden');
    errModal?.classList.remove('flex');
  };

  // =================================================================
  // === PATCH: helper sinkron + TECO modal
  // =================================================================
  // >>> Ganti isi ajaxSync dengan versi ber-timeout
  async function ajaxSync(payload) {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': CSRF,
    };
    if (window.SAP_AUTH) headers['Authorization'] = window.SAP_AUTH;

    const controller = new AbortController();
    const to = setTimeout(() => controller.abort(), getClientTimeoutMs());

    showOverlay('Mengambil data dari SAP… (harap tunggu)');
    try {
      const res = await fetch('/api/yppi019/sync', {
        method: 'POST',
        headers,
        credentials: 'same-origin',
        body: JSON.stringify(payload),
        signal: controller.signal,
      });

      const rawText = await res.text().catch(() => '');
      let json = {};
      try { json = rawText ? JSON.parse(rawText) : {}; } catch { json = { _raw: rawText }; }

      if (res.status === 404) {
        return { ok:false, teco:!!json.teco_possible, msg: json.message || 'Data Tidak Ditemukan', raw: json };
      }
      if (!res.ok) {
        const msg = json?.error || json?.message || `HTTP ${res.status} ${res.statusText}`;
        throw new Error(msg);
      }
      return { ok:true, received: Number(json?.received || json?.count || 0), raw: json };
    } catch (e) {
      if (e.name === 'AbortError') throw new Error('Waktu tunggu klien habis / dibatalkan.');
      throw e;
    } finally {
      clearTimeout(to);
      hideOverlay();
    }
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

  // ⬇️ Cegah Enter auto-submit di seluruh input; khusus PRO: Enter = add to list
  if (form) {
    form.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (e.target && e.target.id === 'IV_AUFNR') {
          const val = String(e.target.value || '').trim();
          if (val.length > 0) {
            addAufnrToList(val);
            e.target.value = '';
          }
        }
      }
    });
  }

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

      const pernr      = pernrInput?.value.trim() || '';
      const aufnrArray = Array.from(aufnrList);
      const arbpl      = arbplInput?.value.trim() || '';
      const werks      = werksInput?.value.trim() || '';

      if (!pernr) { showError('Input Belum Lengkap', 'NIK Operator wajib diisi.'); return pernrInput?.focus(); }

      const hasAufnr = aufnrArray.length > 0;
      const hasWc    = arbpl !== '';
      const hasPlant = werks !== '';

      if (!hasAufnr && !hasWc) {
        showError('Input Tidak Lengkap', 'Anda harus mengisi "Work Center" atau "PRO".');
        return arbplInput?.focus();
      }

      // ⛔️ Aturan baru: kalau Plant dipilih, WC wajib
      if (hasPlant && !hasWc) {
        showError('Input Tidak Valid', 'Jika memilih "Plant", isi juga "Work Center" atau kosongkan Plant.');
        return arbplInput?.focus();
      }

      // Jika WC diisi, Plant wajib (aturan lama, tetap)
      if (hasWc && !hasPlant) {
        showError('Input Tidak Lengkap', 'Jika "Work Center" diisi, maka "Plant" wajib dipilih.');
        return werksInput?.focus();
      }

      // ⬇️ Pastikan setBusy menemukan tombol baru (id="submitBtn")
      const submitBtn = document.getElementById('submitBtn') || form.querySelector('button[type="submit"], button:not([type])');
      const setBusy = (b) => {
        if (!submitBtn) return;
        if (b) { submitBtn.dataset._txt = submitBtn.innerHTML; submitBtn.innerHTML = 'Memeriksa data...'; submitBtn.disabled = true; }
        else   { submitBtn.innerHTML = submitBtn.dataset._txt || 'Kirim Data'; submitBtn.disabled = false; }
      };

      setBusy(true);
      try {
        // Selalu kirim pernr; arbpl hanya bila ada WC; werks hanya bila ada WC+Plant
        const basePayload = { pernr };
        if (hasWc)   basePayload.arbpl = arbpl;
        if (hasWc && hasPlant) basePayload.werks = werks;

        if (hasAufnr) {
          const results = await Promise.allSettled(
            aufnrArray.map(aufnr => ajaxSync({ ...basePayload, aufnr }))
          );

          const okAufnrs = [];
          let adaTeco = false;
          let firstErrMsg = null;
          const failures = []; // daftar gagal per-PRO

          results.forEach((r, i) => {
            const pro = aufnrArray[i];
            if (r.status === 'fulfilled') {
              if (r.value.ok) {
                okAufnrs.push(pro);
              } else {
                if (r.value.teco) adaTeco = true;
                const msg = r.value.msg || r.value.raw?.error || 'Data Tidak Ditemukan';
                failures.push({ key: pro, msg });
                if (!firstErrMsg) firstErrMsg = msg;
              }
            } else {
              const msg = r.reason?.message || String(r.reason || 'Gagal request');
              failures.push({ key: pro, msg });
              if (!firstErrMsg) firstErrMsg = msg;
            }
          });

          if (okAufnrs.length === 0) {
            if (adaTeco) showTeco(aufnrArray);
            else showError('Gagal Memuat Data', firstErrMsg || 'PRO/WC tidak mengembalikan data dari SAP.');
            return;
          }

          // ← di sini minimal ada 1 sukses
          const goRedirect = () => {
            const to = new URL(form.action, location.origin);
            to.searchParams.set('pernr', pernr);
            to.searchParams.set('aufnrs', okAufnrs.join(','));
            if (hasWc)   to.searchParams.set('arbpl', arbpl);
            if (hasWc && hasPlant) to.searchParams.set('werks', werks);
            location.href = to.toString();
          };

          if (failures.length > 0) {
            const lines = failures.map(f => `${f.key} — ${f.msg}`).join('\n');
            // tampilkan daftar gagal, lalu lanjut ke halaman detail utk PRO yang sukses
            showErrorAndThen('Sebagian PRO gagal memuat data', lines, goRedirect);
            return;
          }

          // semua sukses → langsung redirect
          goRedirect();

        } else {
          const r = await ajaxSync(basePayload);
          if (!r.ok) {
            if (r.teco) showTeco(`${arbpl} - ${werks}`);
            else showError('Data Tidak Ditemukan', r.msg || 'WC/Plant tidak mengembalikan data.');
            return;
          }
          const to = new URL(form.action, location.origin);
          to.searchParams.set('pernr', pernr);
          if (hasWc)   to.searchParams.set('arbpl', arbpl);
          if (hasWc && hasPlant) to.searchParams.set('werks', werks);
          location.href = to.toString();
          return;
        }
      } catch (err) {
        showError('Gagal Mengambil Data', err?.message || 'Terjadi kesalahan saat sinkronisasi.');
      } finally {
        setBusy(false);
      }
    });
  }

  // ⬇️ Klik tombol Kirim Data → trigger submit handler di atas
  const submitBtnEl = document.getElementById('submitBtn');
  if (submitBtnEl && form) {
    submitBtnEl.addEventListener('click', () => {
      form.requestSubmit(); // memicu handler 'submit' yang sudah ada
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
    if (cur === lastCode && (now - lastAt) < 1600) {
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
      decoder: { readers: ["code_128_reader"] },
      locate: true,
      numOfWorkers: navigator.hardwareConcurrency || 2,
    }, (err) => {
      if (err) { console.error(err); showError('Gagal memulai kamera', err?.message || err); return; }
      Quagga.start();
      quaggaRunning = true;
      try { currentTrack = Quagga.CameraAccess.getActiveStream()?.getVideoTracks?.()[0] || null; } catch(_) {}
      onDet = (res) => {
        if (committing) return;
        const cr  = res?.codeResult;
        const raw = cr?.code || '';
        const fmt = cr?.format || '';
        if (!raw) return;
      
        // filter berdasarkan rata-rata error decoding
        const errs   = (cr.decodedCodes || [])
                        .map(d => d.error)
                        .filter(e => typeof e === 'number');
        const avgErr = errs.length ? (errs.reduce((a,b)=>a+b,0) / errs.length) : 1;
        if (avgErr > 0.15) return;
      
        if (stableCommit(raw, fmt)) {
          committing = true;
          closeModal();
        }
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

  if (openBtn)         openBtn.addEventListener('click', openModal);
  if (closeBtn)        closeBtn.addEventListener('click', closeModal);
  if (toggleTorchBtn)  toggleTorchBtn.addEventListener('click', () => setTorch(!torchOn));
  if (modal)           modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

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
    // lebih besar sedikit: 0.8
    const target = Math.round(side * 0.8);
    return {
      width: Math.max(300, Math.min(500, target)),
      height: Math.max(300, Math.min(500, target))
    };
  }

  // =================================================================
  // ===== SCANNER QR (Html5Qrcode / jsQR) & MODAL
  // =================================================================
  const qrModal = document.getElementById('qrScannerModal');
  const openQrBtn = document.getElementById('openQrScanner');
  const closeQrBtn = document.getElementById('closeQrScanner');
  let html5QrCode = null;
  let jsQrStream = null;
  let jsQrVideo = null;

  function stopJsQr() {
    if (jsQrStream) {
      jsQrStream.getTracks().forEach(track => track.stop());
    }
    jsQrStream = null;
    if (jsQrVideo) {
      jsQrVideo.pause();
      jsQrVideo.remove();
    }
    jsQrVideo = null;
  }

  function closeQrModal() {
    stopQrScanner();
    stopJsQr();
    qrModal?.classList.add('hidden');
    qrModal?.classList.remove('flex');
  }

  async function startQrScanner() {
    const qrReaderDiv = document.getElementById('qr-reader');
    qrReaderDiv.innerHTML = '';

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

    if (isIOS) {
      if (typeof window.jsQR === 'undefined') {
        showError('Scanner QR tidak tersedia', 'Library jsQR belum dimuat.');
        closeQrModal();
        return;
      }

      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        jsQrStream = stream;

        const video = document.createElement('video');
        video.setAttribute('playsinline', 'true');
        video.srcObject = stream;
        video.classList.add('w-full', 'h-full', 'object-cover');
        qrReaderDiv.appendChild(video);

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.style.display = 'none';
        qrReaderDiv.appendChild(canvas);

        const scanLoop = () => {
          if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = window.jsQR(imageData.data, imageData.width, imageData.height, {
              inversionAttempts: "dontInvert",
            });

            if (code) {
              const normalizedText = code.data.trim().replace(/[^a-zA-Z0-9\s]/g, '').toUpperCase();
              if (arbplInput && normalizedText.length > 0) {
                arbplInput.value = normalizedText;
                closeQrModal();
                return;
              }
            }
          }
          requestAnimationFrame(scanLoop);
        };

        video.onloadedmetadata = () => {
          video.play();
          requestAnimationFrame(scanLoop);
        };
      } catch (err) {
        const msg = (err && err.message) ? err.message : String(err);
        showError("Gagal Kamera", msg.includes('NotAllowedError')
          ? "Akses kamera ditolak. Berikan izin di pengaturan peramban."
          : "Tidak dapat memulai pemindaian.");
        closeQrModal();
      }

    } else {
      // --- Android/lainnya menggunakan html5-qrcode ---
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
        const config = {
          fps: 15,
          qrbox: (vw, vh) => qrboxSizer(vw, vh),
          disableFlip: true,
        };

        if (html5QrCode.isScanning) {
          await html5QrCode.stop();
        }

        const cameraId = await getBestCameraId();
        if (!cameraId) {
          showError('Gagal Kamera', 'Tidak ada kamera yang terdeteksi.');
          closeQrModal();
          return;
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

  if (openQrBtn) {
    openQrBtn.addEventListener('click', () => {
      // Cek library yang diperlukan tergantung OS
      const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
      const requiredLib = isIOS ? window.jsQR : window.Html5Qrcode;

      if (!requiredLib) {
        showError('Scanner QR tidak tersedia', 'Library belum dimuat.');
        return;
      }
      openQrModal();
    });
  }

  if (closeQrBtn) closeQrBtn.addEventListener('click', closeQrModal);
  if (qrModal) qrModal.addEventListener('click', e => { if (e.target === qrModal) closeQrModal(); });

  // =================================================================
// ===== LOGOUT (dibuat anti double-click / double-submit)
// =================================================================
const logoutModal   = document.getElementById('logoutModal');
const openLogoutBtn = document.getElementById('openLogoutConfirm');
const logoutCancel  = document.getElementById('logoutCancel');
const logoutConfirm = document.getElementById('logoutConfirm');
const logoutForm    = document.getElementById('logoutForm');

let logoutModalOpen = false;
let logoutSubmitting = false;

function openLogout() {
  if (logoutModalOpen) return;           // cegah buka berkali-kali
  logoutModalOpen = true;
  if (openLogoutBtn) {
    openLogoutBtn.disabled = true;
    openLogoutBtn.classList.add('opacity-60','cursor-not-allowed');
  }
  logoutModal?.classList.remove('hidden');
  logoutModal?.classList.add('flex');
}

function closeLogout() {
  logoutModalOpen = false;
  if (openLogoutBtn) {
    openLogoutBtn.disabled = false;
    openLogoutBtn.classList.remove('opacity-60','cursor-not-allowed');
  }
  logoutModal?.classList.add('hidden');
  logoutModal?.classList.remove('flex');

  // reset tombol konfirmasi bila modal ditutup
  if (logoutConfirm) {
    logoutConfirm.disabled = false;
    logoutConfirm.removeAttribute('aria-busy');
    logoutConfirm.classList.remove('opacity-60','cursor-not-allowed');
    if (logoutConfirm.dataset._txt) logoutConfirm.innerHTML = logoutConfirm.dataset._txt;
  }
  logoutSubmitting = false;
}

openLogoutBtn?.addEventListener('click', (e) => { e.preventDefault(); openLogout(); });
logoutCancel?.addEventListener('click', () => closeLogout());

logoutConfirm?.addEventListener('click', (e) => {
  e.preventDefault();
  if (logoutSubmitting) return;          // hard guard
  logoutSubmitting = true;

  // kunci UI tombol
  logoutConfirm.dataset._txt = logoutConfirm.innerHTML;
  logoutConfirm.disabled = true;
  logoutConfirm.setAttribute('aria-busy','true');
  logoutConfirm.classList.add('opacity-60','cursor-not-allowed');
  logoutConfirm.innerHTML = 'Keluar…';

  // submit sekali saja
  if (logoutForm?.requestSubmit) logoutForm.requestSubmit();
  else logoutForm?.submit();
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
    host.classList.add('block','relative');

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.id = 'plantTrigger';
    trigger.setAttribute('aria-haspopup','listbox');
    trigger.setAttribute('aria-expanded','false');
    trigger.className = [
      'text-xs','font-semibold','relative','h-5',
      'outline-none','bg-transparent','rounded-lg',
      'focus:ring-2','focus:ring-emerald-500','select-none',
      'flex','items-center','justify-center','gap-0.5'
    ].join(' ');

    const label = document.createElement('span');
    label.id = 'plantLabel';
    label.textContent = select.value ? select.value : 'Pilih Plant';
    trigger.appendChild(label);

    const caret = document.createElementNS('http://www.w3.org/2000/svg','svg');
    caret.setAttribute('viewBox','0 0 24 24');
    caret.setAttribute('fill','currentColor');
    caret.classList.add('w-3','h-3','text-emerald-600','pointer-events-none');
    caret.innerHTML = '<path d="M7 10l5 5 5-5H7z"/>';
    trigger.appendChild(caret); // Pindahkan panah ke dalam tombol

    host.appendChild(trigger);

    // Perbarui kode untuk menu
    const menu = document.createElement('div');
    menu.id = 'plantMenu';
    menu.className = 'dropdown-enter invisible opacity-0 scale-95 absolute right-0 mt-1 z-30 w-32 bg-white rounded-xl shadow-2xl ring-1 ring-slate-200 overflow-hidden';
    menu.setAttribute('role','listbox');
    menu.tabIndex = -1;

    const header = document.createElement('div');
    header.className = 'px-2 py-0.5 text-[10px] font-semibold tracking-wider text-slate-500 bg-slate-50';
    header.textContent = 'Pilih Plant';

    const ul = document.createElement('ul');
    ul.className = 'text-xs';

    const makeItem = (text, value) => {
      const li = document.createElement('li');
      li.dataset.value = value;
      li.setAttribute('role','option');
      li.className = 'dd-opt relative px-0.5 py-0.5 cursor-pointer hover:bg-emerald-50 hover:text-emerald-700';
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
      menu.classList.add('dropdown-leave-active');
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

  function mapUom(u) {
    const x = String(u || '').toUpperCase();
    return (x === 'ST' || x === 'EA' || x === 'PCS' || x === 'PC') ? 'PC' : x;
  }  

  function openHistoryModalScan(){
    if (!historyModal) return;
    historyList.innerHTML = '';
    historyEmpty.classList.add('hidden');
    historyLoading.classList.remove('hidden');
    historyMeta.textContent = 'Semua operator • Maks 50 data terbaru';

    historyModal.classList.remove('hidden'); historyModal.classList.add('flex');

    fetch('/api/yppi019/backdate-history?limit=50&order=desc', {
      headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
      credentials: 'same-origin'
    })
    .then(r => r.json().catch(()=>({})))
    .then(js => {
      const rows = Array.isArray(js.rows) ? js.rows : [];
      historyLoading.classList.add('hidden');

      if (!rows.length) { historyEmpty.classList.remove('hidden'); return; }

      historyList.innerHTML = rows.map((r, i) => {
        const auf   = r.AUFNR || r.aufnr || '-';
        const vor   = r.VORNR || r.vornr || '-';
        const qty   = r.QTY   || r.qty   || '-';
        const me    = mapUom(r.MEINH || r.meinh || '-');     // sudah ada dari step sebelumnya
        const bud   = fmtYMD(r.BUDAT || r.budat || '-');
        const today = fmtYMD(r.TODAY || r.today || '-');
        const wc    = r.ARBPL0|| r.arbpl0|| '-';
        const mkx   = r.MAKTX || r.maktx || '-';
        const nik   = r.PERNR || r.pernr || r.NIK || r.nik || '-';
      
        return `<tr class="odd:bg-white even:bg-slate-50">
          <!-- ⬇️ sel nomor baru -->
          <td class="px-3 py-2 border-b text-center font-mono">${i + 1}</td>
      
          <td class="px-3 py-2 border-b font-mono">${auf} / ${vor}</td>
          <td class="px-3 py-2 border-b font-mono">${nik}</td>
          <td class="px-3 py-2 border-b font-mono">${qty} ${me}</td>
          <td class="px-3 py-2 border-b">${bud}</td>
          <td class="px-3 py-2 border-b">${today}</td>
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


// === Modal Hasil Konfirmasi (dd/mm/yyyy + ikon kalender) ===
(function () {
  const btnOpen   = document.getElementById('openHasilKonfirmasi');
  const modal     = document.getElementById('hasilModal');
  const form      = document.getElementById('hasilForm');
  const inpPernr  = document.getElementById('hasil-pernr');

  const inpBudatTxt    = document.getElementById('hasil-budat') || document.getElementById('hasil-budat-display');
  const btnBudat       = document.getElementById('hasil-budat-btn');
  const inpBudatNative = document.getElementById('hasil-budat-native');

  const btnCancel = document.getElementById('hasilCancel');
  if (!btnOpen || !modal || !form || !inpBudatTxt) return;

  // helpers
  const toDMY = (d) => {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yy = d.getFullYear();
    return `${dd}/${mm}/${yy}`;
  };
  const dmyToYmdNoDash = (s) => {
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(s || '').trim());
    return m ? `${m[3]}${m[2]}${m[1]}` : '';
  };
  const dmyToIso = (s) => {
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(s || '').trim());
    return m ? `${m[3]}-${m[2]}-${m[1]}` : '';
  };
  const isoToDmy = (iso) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(iso || '').trim());
    return m ? `${m[3]}/${m[2]}/${m[1]}` : '';
  };
  const getLastPernr = () => { try { return localStorage.getItem('last_pernr') || ''; } catch { return ''; } };

  // default hari ini ke keduanya
  function ensureTodayIfEmpty() {
    if (!inpBudatTxt.value) inpBudatTxt.value = toDMY(new Date());
    if (inpBudatNative && !inpBudatNative.value) {
      try { inpBudatNative.value = new Date().toISOString().slice(0,10); } catch {}
    }
  }

  // sinkronisasi dua input
  function syncTxtToNative() {
    if (!inpBudatNative) return;
    const iso = dmyToIso(inpBudatTxt.value);
    if (iso) inpBudatNative.value = iso;
  }
  function syncNativeToTxt() {
    const dmy = isoToDmy(inpBudatNative.value);
    if (dmy) inpBudatTxt.value = dmy;
  }

  const show = () => {
    ensureTodayIfEmpty();
    syncTxtToNative();

    if (inpPernr && !inpPernr.value) {
      const last = getLastPernr();
      if (last) inpPernr.value = last;
    }
    try { if (inpBudatNative) inpBudatNative.max = new Date().toISOString().slice(0,10); } catch {}

    modal.classList.remove('hidden');
    setTimeout(() => inpPernr?.focus(), 50);
  };

  const hide = () => modal.classList.add('hidden');

  // events
  btnOpen.addEventListener('click', show);
  btnCancel?.addEventListener('click', hide);
  modal.addEventListener('click', (e) => { if (e.target === modal) hide(); });

  btnBudat?.addEventListener('click', () => {
    if (!inpBudatNative) return;
    syncTxtToNative();
    try { inpBudatNative.showPicker ? inpBudatNative.showPicker() : inpBudatNative.click(); }
    catch { inpBudatNative.click(); }
  });

  inpBudatNative?.addEventListener('change', syncNativeToTxt);
  inpBudatTxt.addEventListener('blur', syncTxtToNative);

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const pernr = (inpPernr.value || '').trim();
    const dmy   = (inpBudatTxt.value || '').trim();

    if (!pernr) { alert('NIK Operator wajib diisi.'); inpPernr.focus(); return; }
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(dmy)) { alert('Tanggal tidak valid. Gunakan dd/mm/yyyy.'); inpBudatTxt.focus(); return; }

    const budat = dmyToYmdNoDash(dmy); // YYYYMMDD
    try { localStorage.setItem('last_pernr', pernr); } catch {}

    const url = `/hasil?pernr=${encodeURIComponent(pernr)}&budat=${encodeURIComponent(budat)}`;
    window.location.assign(url);
  });

  // prefilling awal
  ensureTodayIfEmpty();
  syncTxtToNative();
})();

// ==== Overlay extras (non-intrusive) ====
// Menambahkan animasi titik-titik & rotasi tips ketika #overlay tampil.
// Tidak mengubah flow submit Anda; hanya observe class "hidden".
(function () {
  const ov  = document.getElementById('overlay');
  if (!ov) return;
  const txt = document.getElementById('overlayText');
  const tip = document.getElementById('overlayTip');

  const TIPS = [
    'Tips: izinkan kamera di browser agar scan lebih cepat.',
    'Gunakan cahaya yang cukup saat memindai barcode/QR.',
    'Jika barcode buram, isi manual selalu tersedia.',
    'Sebaiknya berdoa terlebih dahulu.',
  ];
  let dotsTimer = null, tipTimer = null, tipIdx = 0;

  function start() {
    // animasi titik status
    clearInterval(dotsTimer);
    const base = (txt && txt.textContent) ? txt.textContent.replace(/\.*$/,'') : 'Memuat';
    let dots = 0;
    dotsTimer = setInterval(() => {
      dots = (dots + 1) % 4;
      if (txt) txt.textContent = base + '.'.repeat(dots);
    }, 450);

    // rotasi tips
    if (tip) {
      tip.textContent = TIPS[tipIdx % TIPS.length];
      clearInterval(tipTimer);
      tipTimer = setInterval(() => {
        tipIdx++;
        tip.textContent = TIPS[tipIdx % TIPS.length];
      }, 3000);
    }
  }
  function stop() {
    clearInterval(dotsTimer); dotsTimer = null;
    clearInterval(tipTimer);  tipTimer  = null;
  }

  // Observe perubahan class (tampil/sembunyi)
  const obs = new MutationObserver(() => {
    if (ov.classList.contains('hidden')) stop(); else start();
  });
  obs.observe(ov, { attributes: true, attributeFilter: ['class'] });

  // Jika page load sudah dalam kondisi tampil (jarang, tapi aman)
  if (!ov.classList.contains('hidden')) start();
})();

