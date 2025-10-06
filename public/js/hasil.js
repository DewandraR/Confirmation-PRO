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

      // --- mapping tolerant + alias khusus sesuai data RFC (tanpa mengubah kunci lain)
      const _direct = (r, k) => (r[k] ?? r[k?.toLowerCase?.()] ?? r[k?.toUpperCase?.()]);
      function getVal(r, k) {
        const v = _direct(r, k);
        if (v !== undefined) {
          // Transformasi tampilan UOM: ST -> PC
          if (k === 'GMEIN' && v === 'ST') return 'PC';
          return v;
        }

        switch (k) {
          case 'QTY_TARGET': return _direct(r, 'PSMNG');          // alias target = PSMNG
          case 'SISA': {
            const p = Number(_direct(r, 'PSMNG') ?? 0);
            const g = Number(_direct(r, 'GMNGX') ?? 0);
            return Math.max(p - g, 0);                            // hitung sisa
          }
          case 'STPRO2':  return _direct(r, 'STPRO20');           // total menit inspect
          case 'STPRO2X': return _direct(r, 'TDWS');              // total detik inspect
          case 'AVGKPI':  return _direct(r, 'PERAVG');            // avg kpi
          // kolom yang memang tidak ada di JSON RFC ini -> biarkan kosong
          case 'CAP_TARGET':
          case 'CAP_WC':
          case 'INSMK':   return '';
          default:        return v;
        }
      }

      // ===== Ringkasan di bawah header (Operator + Total Menit Kerja + Total Menit Inspect)
      // Operator (unique list, tampil singkat)
      const opSet = new Set(rows.map(r => getVal(r,'SNAME')).filter(Boolean));
      const opList = Array.from(opSet);
      const operatorLabel = opList.length <= 2 ? opList.join(', ') : `${opList[0]} +${opList.length - 1}`;

      // Total Menit Kerja/Inspect: jika semua baris sama -> tampil angka itu; kalau beda -> jumlahkan
      const sameOrSum = (arr) => {
        const vals = arr.filter(v => Number.isFinite(v));
        if (!vals.length) return '';
        return vals.every(v => v === vals[0]) ? vals[0] : vals.reduce((a,b)=>a+b,0);
      };
      const totalKerja   = sameOrSum(rows.map(r => Number(getVal(r,'STPRO'))));
      const totalInspect = sameOrSum(rows.map(r => Number(getVal(r,'STPRO2'))));

      // sisipkan summary card (buat elemen jika belum ada)
      let summary = document.getElementById('hasil-summary');
      if (!summary) {
        summary = document.createElement('div');
        summary.id = 'hasil-summary';
        // taruh di bawah bar deretan tombol (setelah parent dari btn-refresh), jika tidak ada: sebelum tabel
        const anchor = (btnRefresh && btnRefresh.parentElement) || (tbody && tbody.closest('table')) || document.body;
        anchor.insertAdjacentElement('afterend', summary);
      }
      summary.innerHTML = `
        <div class="mt-3 grid grid-cols-3 sm:grid-cols-3 gap-3">
          <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 shadow-sm">
            <div class="text-xs text-emerald-700">Operator</div>
            <div class="text-xs font-semibold text-emerald-900">${operatorLabel || '-'}</div>
          </div>
          <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 shadow-sm">
            <div class="text-xs text-sky-700">Total Menit Kerja</div>
            <div class="text-2xs font-bold">${(totalKerja ?? '')}</div>
          </div>
          <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 shadow-sm">
            <div class="text-xs text-amber-700">Total Menit Inspect</div>
            <div class="text-2xs font-bold">${(totalInspect ?? '')}</div>
          </div>
        </div>
      `;

      // ===== Render tabel (tetap generate sel, tapi 3 kolom akan disembunyikan via CSS)
      tbody.innerHTML = rows.map((r, i) => `
        <tr class="align-top">
          <td class="px-3 py-2">${i + 1}</td>  
          <td class="px-3 py-2">${getVal(r,'ARBPL') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'KTEXT') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'AUFNR') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'MATNR') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'MAKTX') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'PSMNG') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'GMNGX') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'SISA') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'GMEIN') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'TTCNF') ?? ''}</td>
          <td class="px-3 py-2">${getVal(r,'TTCNF2') ?? ''}</td>     
        </tr>
      `).join('');
    } catch (e) {
      tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="37">${e.message || e}</td></tr>`;
    }
  }

  btnRefresh?.addEventListener('click', loadData);
  loadData();
});
