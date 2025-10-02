// hasil.js
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
  
    // Ambil query string
    const usp = new URLSearchParams(location.search);
    const pernr = (usp.get('pernr') || '').trim();
    const budat = (usp.get('budat') || '').trim(); // YYYYMMDD
  
    // set filter tampilan
    const elPernr = document.getElementById('filter-pernr');
    const elBudat = document.getElementById('filter-budat');
    elPernr.value = pernr;
    elBudat.value = budat.replace(/^(\d{4})(\d{2})(\d{2})$/, '$1-$2-$3');
  
    const tbody = document.getElementById('hasil-tbody');
    const btnRefresh = document.getElementById('btn-refresh');
  
    if (!pernr || !budat) {
      tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="37">
        Parameter kurang. Kembali ke halaman Scan, isi NIK dan tanggal, lalu klik "Hasil Konfirmasi".
      </td></tr>`;
      return;
    }
  
    async function loadData() {
      tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500" colspan="37">Memuat data…</td></tr>`;
      try {
        // Endpoint lokal Flask (relatif seperti scan.js)
        const url = `/api/yppi019/hasil?pernr=${encodeURIComponent(pernr)}&budat=${encodeURIComponent(budat)}`;
        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();
        if (!data?.ok) throw new Error(data?.error || 'Gagal memuat data');
  
        const rows = Array.isArray(data.rows) ? data.rows : [];
        if (!rows.length) {
          tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500" colspan="37">Tidak ada data.</td></tr>`;
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
  