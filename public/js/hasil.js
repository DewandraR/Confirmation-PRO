// public/js/hasil.js
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    /* ===================== Helpers ===================== */

    // YYYYMMDD / YYYY-MM-DD -> dd/mm/yyyy (untuk tampilan)
    function fmtBudatDisplay(raw) {
        const s = String(raw || "").replace(/\D/g, "");
        if (s.length !== 8) return raw || "-";
        return `${s.slice(6, 8)}/${s.slice(4, 6)}/${s.slice(0, 4)}`;
    }
    // dd/mm/yyyy -> YYYYMMDD
    function dmyToYmd(dmy) {
        const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(dmy || "").trim());
        return m ? `${m[3]}${m[2]}${m[1]}` : null;
    }
    // YYYYMMDD -> YYYY-MM-DD
    function ymdToDash(ymd) {
        const s = String(ymd || "").replace(/\D/g, "");
        return s.length === 8
            ? `${s.slice(0, 4)}-${s.slice(4, 6)}-${s.slice(6, 8)}`
            : "";
    }
    // Iterasi tanggal inklusif: [from..to] → array YYYYMMDD
    // -- helper lokal: Date -> 'YYYYMMDD' (tanpa shift UTC)
    function ymdLocal(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}${m}${d}`;
    }
    function rangeYMD(fromY, toY) {
        const f = String(fromY || "").replace(/\D/g, "");
        const t = String(toY || "").replace(/\D/g, "");
        if (f.length !== 8 || t.length !== 8) return [];
        const a = [];
        // pakai jam 12 siang agar aman dari DST; WIB tidak pakai DST tapi ini best-practice
        let d = new Date(
            Number(f.slice(0, 4)),
            Number(f.slice(4, 6)) - 1,
            Number(f.slice(6, 8)),
            12
        );
        const e = new Date(
            Number(t.slice(0, 4)),
            Number(t.slice(4, 6)) - 1,
            Number(t.slice(6, 8)),
            12
        );
        while (d <= e) {
            a.push(ymdLocal(d));
            d.setDate(d.getDate() + 1);
        }
        return a;
    }
    // Update URL dengan budat tunggal
    function goWithBudat(ymd) {
        if (!/^\d{8}$/.test(ymd)) return;
        const url = new URL(location.href);
        if (pernr) url.searchParams.set("pernr", pernr);
        url.searchParams.set("budat", ymd);
        url.searchParams.delete("from");
        url.searchParams.delete("to");
        location.href = url.toString();
    }
    // Update URL dengan range
    function goWithRange(from, to) {
        if (!/^\d{8}$/.test(from) || !/^\d{8}$/.test(to)) return;
        const url = new URL(location.href);
        if (pernr) url.searchParams.set("pernr", pernr);
        url.searchParams.set("from", from);
        url.searchParams.set("to", to);
        url.searchParams.delete("budat");
        location.href = url.toString();
    }

    /* ===================== Query string ===================== */
    const usp = new URLSearchParams(location.search);
    const pernr = (usp.get("pernr") || "").trim();
    const budat = (usp.get("budat") || usp.get("date") || "").trim();
    const from = (
        usp.get("from") ||
        usp.get("date_from") ||
        usp.get("budat_from") ||
        ""
    ).trim();
    const to = (
        usp.get("to") ||
        usp.get("date_to") ||
        usp.get("budat_to") ||
        ""
    ).trim();
    const isRange = /^\d{8}$/.test(from) && /^\d{8}$/.test(to);

    // Prefer budat untuk single; untuk range pakai from–to
    const tlPernr =
        document.getElementById("title-pernr") ||
        document.getElementById("tagline-pernr");
    const tlBudat =
        document.getElementById("title-budat") ||
        document.getElementById("tagline-budat");
    if (tlPernr) tlPernr.textContent = pernr || "-";
    if (tlBudat)
        tlBudat.textContent = isRange
            ? `${fmtBudatDisplay(from)} – ${fmtBudatDisplay(to)}`
            : fmtBudatDisplay(budat);

    // Back link ingat NIK
    try {
        if (pernr) localStorage.setItem("last_pernr", pernr);
        const remembered = localStorage.getItem("last_pernr") || "";
        const backLink =
            document.getElementById("btn-back") ||
            document.querySelector('a[href="/scan"]');
        if (backLink && (pernr || remembered)) {
            const p = pernr || remembered;
            backLink.href = `/scan?pernr=${encodeURIComponent(p)}`;
        }
    } catch {}

    // Hidden filters (opsional)
    const elPernr = document.getElementById("filter-pernr");
    const elBudF = document.getElementById("filter-budat-from");
    const elBudT = document.getElementById("filter-budat-to");
    const elBud1 = document.getElementById("filter-budat"); // legacy single
    if (elPernr) elPernr.value = pernr;
    if (elBudF) elBudF.value = ymdToDash(from);
    if (elBudT) elBudT.value = ymdToDash(to);
    if (elBud1) elBud1.value = ymdToDash(budat);

    /* ===================== Kontrol tanggal ===================== */
    const inpDaterange = document.getElementById("hasil-daterange-picker");
    if (inpDaterange && window.flatpickr) {
        // Cek apakah locale 'id' sudah di-load (dari layout)
        const fpLocale = window.flatpickr?.l10ns?.id
            ? window.flatpickr.l10ns.id
            : undefined;

        // Converter: 'YYYYMMDD' -> Date
        const toDateObj = (ymd) => {
            const s = String(ymd || "").replace(/\D/g, "");
            if (s.length !== 8) return null;
            return new Date(+s.slice(0, 4), +s.slice(4, 6) - 1, +s.slice(6, 8));
        };

        // Tentukan tanggal default berdasarkan URL
        let defaultDates = [];
        if (isRange) {
            defaultDates = [toDateObj(from), toDateObj(to)];
        } else if (budat) {
            const d = toDateObj(budat);
            defaultDates = [d, d];
        } else {
            const today = new Date();
            defaultDates = [today, today];
        }
        defaultDates = defaultDates.filter(Boolean); // buang null

        const fp = flatpickr(inpDaterange, {
            mode: "range",
            dateFormat: "d/m/Y",
            // gunakan Date object agar tidak tertukar
            defaultDate: defaultDates,
            // batasi ke hari ini
            maxDate: "today",
            // gunakan locale id + separator range " – "
            locale: fpLocale
                ? { ...fpLocale, rangeSeparator: " – " }
                : { rangeSeparator: " – " },
            disableMobile: true,
            onReady: function (selectedDates, dateStr, instance) {
                // pastikan nilai input sesuai default di atas (beberapa versi perlu setDate manual)
                if (defaultDates.length) instance.setDate(defaultDates, false);
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 2) {
                    const pad2 = (n) => String(n).padStart(2, "0");
                    const dateToYmd = (d) =>
                        `${d.getFullYear()}${pad2(d.getMonth() + 1)}${pad2(
                            d.getDate()
                        )}`;
                    const newFrom = dateToYmd(selectedDates[0]);
                    const newTo = dateToYmd(selectedDates[1]);
                    // redirect hanya jika berubah dari URL sekarang
                    if (newFrom !== from || newTo !== to)
                        goWithRange(newFrom, newTo);
                }
            },
        });
    }

    /* ===================== Tabel & Data ===================== */
    const tbody = document.getElementById("hasil-tbody");
    const btnRefresh = document.getElementById("btn-refresh");

    if (!pernr || (!budat && !isRange)) {
        if (tbody) {
            tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="37">
        Parameter kurang. Kembali ke halaman Scan, isi NIK dan tanggal/periode, lalu klik "Hasil Konfirmasi".
      </td></tr>`;
        }
        return;
    }

    // Map nilai + alias
    const _direct = (r, k) =>
        r?.[k] ?? r?.[k?.toLowerCase?.()] ?? r?.[k?.toUpperCase?.()];
    function getVal(r, k) {
        const v = _direct(r, k);
        if (v !== undefined) {
            if (k === "GMEIN" && v === "ST") return "PC";
            return v;
        }
        switch (k) {
            case "QTY_TARGET":
                return _direct(r, "PSMNG");
            case "SISA": {
                const p = Number(_direct(r, "PSMNG") ?? 0);
                const g = Number(_direct(r, "GMNGX") ?? 0);
                return Math.max(p - g, 0);
            }
            case "STPRO2":
                return _direct(r, "STPRO20");
            case "STPRO2X":
                return _direct(r, "TDWS");
            case "AVGKPI":
                return _direct(r, "PERAVG");
            case "CAP_TARGET":
            case "CAP_WC":
            case "INSMK":
                return "";
            default:
                return v;
        }
    }
    const sumNum = (arr) => arr.reduce((a, v) => a + (Number(v) || 0), 0);
    const fmt2 = (n) =>
        Number.isFinite(n) ? Number(Number(n).toFixed(2)) : "";

    // Render ringkasan (Operator + Total menit kerja/inspect)
    function renderSummary(allRows) {
        const opSet = new Set(
            allRows.map((r) => getVal(r, "SNAME")).filter(Boolean)
        );
        const opList = Array.from(opSet);
        const operatorLabel =
            opList.length <= 2
                ? opList.join(", ")
                : `${opList[0]} +${opList.length - 1}`;

        const totalKerja = sumNum(allRows.map((r) => getVal(r, "TTCNF")));
        const totalInspect = sumNum(allRows.map((r) => getVal(r, "TTCNF2")));

        let summary = document.getElementById("hasil-summary");
        if (!summary) {
            summary = document.createElement("div");
            summary.id = "hasil-summary";
            const anchor =
                (btnRefresh && btnRefresh.parentElement) ||
                (tbody && tbody.closest("table")) ||
                document.body;
            anchor.insertAdjacentElement("afterend", summary);
        }
        summary.innerHTML = `
      <div class="mt-3 grid grid-cols-3 sm:grid-cols-3 gap-3">
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 shadow-sm">
          <div class="text-xs text-emerald-700">Operator</div>
          <div class="text-xs font-semibold text-emerald-900">${
              operatorLabel || "-"
          }</div>
        </div>
        <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 shadow-sm">
          <div class="text-xs text-sky-700">Total Menit Kerja</div>
          <div class="text-2xs font-bold">${fmt2(totalKerja)}</div>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 shadow-sm">
          <div class="text-xs text-amber-700">Total Menit Inspect</div>
          <div class="text-2xs font-bold">${fmt2(totalInspect)}</div>
        </div>
      </div>
    `;
    }

    // Ambil tanggal untuk cell (utamakan field BUDAT, fallback dari request)
    function getDateCell(r, fallbackYmd) {
        const y =
            r?.BUDAT || r?.budat || r?.__budat || fallbackYmd || budat || from;
        return fmtBudatDisplay(y);
    }

    // Render TABLE
    function renderTable(rows) {
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500 text-left" colspan="37">Tidak ada data.</td></tr>`;
            return;
        }
        tbody.innerHTML = rows
            .map(
                (r, i) => `
      <tr class="align-top">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2">${getDateCell(r)}</td> <!-- Kolom Tanggal -->
        <td class="px-3 py-2">${getVal(r, "ARBPL") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "KTEXT") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "AUFNR") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "MATNR") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "MAKTX") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "PSMNG") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "GMNGX") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "SISA") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "GMEIN") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "TTCNF") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "TTCNF2") ?? ""}</td>
      </tr>
    `
            )
            .join("");
    }

    // Fetch helper
    async function fetchJson(url) {
        const resp = await fetch(url, {
            headers: { Accept: "application/json" },
        });
        let data = null;
        try {
            data = await resp.json();
        } catch {}
        return {
            ok: resp.ok && data && data.ok !== false,
            status: resp.status,
            data,
        };
    }

    // Single-day fetch
    async function loadSingle(ymd) {
        const url = `/api/yppi019/hasil?pernr=${encodeURIComponent(
            pernr
        )}&budat=${encodeURIComponent(ymd)}`;
        const { ok, data, status } = await fetchJson(url);
        if (!ok)
            throw new Error(
                (data && (data.error || data.message)) || `HTTP ${status}`
            );
        const rows = Array.isArray(data.rows) ? data.rows : [];
        return rows.map((r) => ({ ...r, __budat: ymd }));
    }

    // Range via API (jika tersedia)
    async function tryLoadRangeViaApi(f, t) {
        const url = `/api/yppi019/hasil-range?pernr=${encodeURIComponent(
            pernr
        )}&from=${encodeURIComponent(f)}&to=${encodeURIComponent(t)}`;
        const { ok, data, status } = await fetchJson(url);
        if (!ok) return null; // biar fallback ke FE loop
        const rows = Array.isArray(data.rows) ? data.rows : [];
        // Pastikan setiap baris punya __budat untuk kolom Tanggal
        return rows.map((r) => ({ ...r, __budat: r.BUDAT || r.budat || null }));
    }

    // Range – fallback FE loop harian
    async function loadRangeOnClient(f, t) {
        const days = rangeYMD(f, t);
        // Batas wajar agar UI tetap responsif (sesuaikan kalau perlu)
        const MAX_DAYS = 45;
        if (days.length > MAX_DAYS) {
            throw new Error(
                `Periode terlalu panjang (${days.length} hari). Maksimal ${MAX_DAYS} hari.`
            );
        }
        let all = [];
        for (const d of days) {
            const part = await loadSingle(d);
            all = all.concat(part);
        }
        return all;
    }

    // MAIN loader
    async function loadData() {
        if (tbody)
            tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500 text-left" colspan="37">Memuat data…</td></tr>`;
        try {
            let rows = [];
            if (isRange) {
                // 1) coba endpoint backend /hasil-range
                rows = await (async () => {
                    const viaApi = await tryLoadRangeViaApi(from, to);
                    if (viaApi) return viaApi;
                    // 2) fallback: loop harian dari FE
                    return await loadRangeOnClient(from, to);
                })();
            } else {
                rows = await loadSingle(budat);
            }

            if (!Array.isArray(rows)) rows = [];
            // Optional: urutkan by tanggal ASC, lalu AUFNR, lalu VORNR
            rows.sort((a, b) => {
                const ad = (a.__budat || a.BUDAT || a.budat || "").toString();
                const bd = (b.__budat || b.BUDAT || b.budat || "").toString();
                if (ad !== bd) return ad.localeCompare(bd);
                const aa = (getVal(a, "AUFNR") || "").toString();
                const bb = (getVal(b, "AUFNR") || "").toString();
                if (aa !== bb) return aa.localeCompare(bb);
                const av = (
                    getVal(a, "VORNR") ||
                    getVal(a, "VORNRX") ||
                    ""
                ).toString();
                const bv = (
                    getVal(b, "VORNR") ||
                    getVal(b, "VORNRX") ||
                    ""
                ).toString();
                return av.localeCompare(bv);
            });

            renderSummary(rows);
            renderTable(rows);
        } catch (e) {
            if (tbody)
                tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="37">${
                    e.message || e
                }</td></tr>`;
        }
    }

    btnRefresh?.addEventListener("click", loadData);
    loadData();
});
