// public/js/hasil.js
document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  // ===== Helpers =====
  // YYYYMMDD / YYYY-MM-DD -> dd/mm/yyyy (untuk tampilan)
  function fmtBudatDisplay(raw) {
    const s = String(raw || '').replace(/\D/g, '');
    if (s.length !== 8) return raw || '-';
    return `${s.slice(6, 8)}/${s.slice(4, 6)}/${s.slice(0, 4)}`;
  }
  // dd/mm/yyyy -> YYYYMMDD (untuk query)
  function dmyToYmd(dmy) {
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(dmy || '').trim());
    return m ? `${m[3]}${m[2]}${m[1]}` : null;
  }
  // YYYYMMDD -> YYYY-MM-DD (untuk <input type="date">)
  function ymdToDash(ymd) {
    const s = String(ymd || '').replace(/\D/g, '');
    return s.length === 8 ? `${s.slice(0, 4)}-${s.slice(4, 6)}-${s.slice(6, 8)}` : '';
  }
  function goWithBudat(ymd) {
    if (!/^\d{8}$/.test(ymd)) return;
    const url = new URL(location.href);
    if (pernr) url.searchParams.set('pernr', pernr);
    url.searchParams.set('budat', ymd);
    location.href = url.toString();
  }

  // ===== Query string =====
  const usp   = new URLSearchParams(location.search);
  const pernr = (usp.get('pernr') || '').trim();
  const budat = (usp.get('budat') || usp.get('date') || '').trim(); // prefer budat

  // --- NEW: simpan pernr terakhir & set href tombol kembali ke /scan?pernr=...
  try {
    if (pernr) localStorage.setItem('last_pernr', pernr);
    const remembered = localStorage.getItem('last_pernr') || '';
    const backLink =
      document.getElementById('btn-back') ||
      document.querySelector('a[href="/scan"]');
    if (backLink && (pernr || remembered)) {
      const p = pernr || remembered;
      backLink.href = `/scan?pernr=${encodeURIComponent(p)}`;
    }
  } catch (_) { /* ignore storage errors */ }

  // ===== Hidden filters (jika ada di DOM) =====
  const elPernr = document.getElementById('filter-pernr');
  const elBudat = document.getElementById('filter-budat');
  if (elPernr) elPernr.value = pernr;
  if (elBudat) elBudat.value = ymdToDash(budat);

  // ===== Tagline header =====
  const tlPernr = document.getElementById('tagline-pernr') || document.getElementById('title-pernr');
  const tlBudat = document.getElementById('tagline-budat') || document.getElementById('title-budat');
  if (tlPernr) tlPernr.textContent = pernr || '-';
  if (tlBudat) tlBudat.textContent = fmtBudatDisplay(budat);

  // ===== Kontrol tanggal (ikon + input text + native date) =====
  const btnDate     = document.getElementById('btn-date');
  const pickDate    = document.getElementById('choose-date') || document.getElementById('chose-date'); // jaga-jaga
  const dateDisplay = document.getElementById('date-display');

  // Set nilai awal ke input text (dd/mm/yyyy) dan native date (yyyy-mm-dd)
  if (dateDisplay) dateDisplay.value = fmtBudatDisplay(budat);
  if (pickDate && budat) {
    pickDate.value = ymdToDash(budat);
    try { pickDate.max = new Date().toISOString().slice(0, 10); } catch {}
  }

  // Klik ikon => buka picker native
  btnDate?.addEventListener('click', () => {
    if (!pickDate) return;
    try { pickDate.showPicker ? pickDate.showPicker() : pickDate.click(); }
    catch { pickDate.click(); }
  });

  // Pilih lewat kalender => sinkron ke input text, update URL
  pickDate?.addEventListener('change', () => {
    const ymd = String(pickDate.value || '').replace(/\D/g, ''); // YYYYMMDD
    if (ymd.length !== 8) return;
    if (dateDisplay) dateDisplay.value = fmtBudatDisplay(ymd);
    goWithBudat(ymd);
  });

  // Ketik manual di input text => validasi dd/mm/yyyy, update URL saat blur/Enter
  dateDisplay?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const ymd = dmyToYmd(dateDisplay.value);
      if (ymd) {
        if (pickDate) pickDate.value = ymdToDash(ymd);
        goWithBudat(ymd);
      }
    }
  });
  dateDisplay?.addEventListener('blur', () => {
    const ymd = dmyToYmd(dateDisplay.value);
    if (ymd) {
      if (pickDate) pickDate.value = ymdToDash(ymd);
      goWithBudat(ymd);
    }
  });

  // ===== Tabel data =====
  const tbody = document.getElementById('hasil-tbody');
  const btnRefresh = document.getElementById('btn-refresh');

  if (!pernr || !budat) {
    if (tbody) {
      tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="37">
        Parameter kurang. Kembali ke halaman Scan, isi NIK dan tanggal, lalu klik "Hasil Konfirmasi".
      </td></tr>`;
    }
    return;
  }

  async function loadData() {
    tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500 text-left" colspan="37">Memuat data…</td></tr>`;
    try {
      const url  = `/api/yppi019/hasil?pernr=${encodeURIComponent(pernr)}&budat=${encodeURIComponent(budat)}`;
      const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await resp.json();
      if (!data?.ok) throw new Error(data?.error || 'Gagal memuat data');

      const rows = Array.isArray(data.rows) ? data.rows : [];
      if (!rows.length) {
        tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500 text-left" colspan="37">Tidak ada data.</td></tr>`;
        return;
      }

      const map = (r, k) => (r[k] ?? r[k?.toLowerCase?.()] ?? r[k?.toUpperCase?.()]);
      tbody.innerHTML = rows.map((r, i) => `
        <tr class="align-top">
          <td class="px-3 py-2">${i + 1}</td>
          <td class="px-3 py-2">${map(r,'WABLNR') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'BUDAT') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'PERNR') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'SNAME') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'ARBPL') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'KTEXT') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'VORNR') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'STEUS') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'AUFNR') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'MATNR') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'MAKTX') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'QTY_TARGET') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'PSMNG') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'GMNGX') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'SISA') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'QTYQM') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'GMEIN') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'CAP_TARGET') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'CAP_WC') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'TTCNF') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'TTCNF2') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'STPRO') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'STPRO2') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'STPRO2X') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'ZWNOR') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'PERO') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'PERKPI') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'PERKPI2') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'AVGKPI') ?? ''}</td>
          <td class="px-3 py-2 text-right">${map(r,'MAXCF') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'AVGVGR2') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'PERAVG2') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'INSMK') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'GSTRP') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'GLTRP') ?? ''}</td>
          <td class="px-3 py-2">${map(r,'CHARG') ?? ''}</td>
        </tr>
      `).join('');
    } catch (e) {
      tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="37">${e.message || e}</td></tr>`;
    }
  }

  btnRefresh?.addEventListener('click', loadData);
  loadData();
});
