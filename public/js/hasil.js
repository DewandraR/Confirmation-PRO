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
        applyModeParams(url);
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
        applyModeParams(url);
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
    const werks = (usp.get("werks") || "").trim();

    const dispoSingle = (usp.get("dispo") || usp.get("mrp") || "").trim();
    const disposRaw = (usp.get("dispos") || usp.get("mrps") || "").trim();

    const dispoList = (disposRaw || dispoSingle)
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);

    const dispo = dispoList[0] || ""; // fallback buat kebutuhan lama
    const bagian = (usp.get("bagian") || "").trim();

    const hasPernr = pernr !== "";
    const hasMrp = werks !== "" && dispoList.length > 0;
    const isMrpMode = hasMrp && !hasPernr;

    function applyModeParams(url) {
        if (hasPernr) url.searchParams.set("pernr", pernr);
        else url.searchParams.delete("pernr");

        if (hasMrp) {
            url.searchParams.set("werks", werks);
            if (bagian) url.searchParams.set("bagian", bagian);
            else url.searchParams.delete("bagian");

            if (dispoList.length <= 1) {
                url.searchParams.set("dispo", dispoList[0]);
                url.searchParams.delete("dispos");
            } else {
                url.searchParams.set("dispos", dispoList.join(","));
                url.searchParams.delete("dispo");
            }
        } else {
            url.searchParams.delete("werks");
            url.searchParams.delete("dispo");
            url.searchParams.delete("dispos");
            url.searchParams.delete("bagian");
        }
    }

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
    if (tlPernr)
        tlPernr.textContent = isMrpMode
            ? bagian
                ? `${bagian} / ${werks}`
                : `${dispo} - ${werks}`
            : pernr || "-";
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
    /* ===================== Modal Detail MRP ===================== */
    const mdModal = document.getElementById("mrpDetailModal");
    const mdClose1 = document.getElementById("mrpDetailClose");
    const mdClose2 = document.getElementById("mrpDetailClose2");
    const mdMeta = document.getElementById("mrpDetailMeta");
    const mdTanggal = document.getElementById("mrpDetailTanggal");
    const mdOperator = document.getElementById("mrpDetailOperator");
    const mdPerDate = document.getElementById("mrpDetailPerDate");
    const mdMrp = document.getElementById("mrpDetailMrp");
    const mdWerks = document.getElementById("mrpDetailWerks");
    const mdTbody = document.getElementById("mrpDetailTbody");

    function openMrpDetailModal(group) {
        if (!mdModal || !group) return;

        // isi info
        const dispPeriod =
            group.minYmd && group.maxYmd && group.minYmd !== group.maxYmd
                ? `${fmtBudatDisplay(group.minYmd)} – ${fmtBudatDisplay(
                      group.maxYmd
                  )}`
                : fmtBudatDisplay(group.minYmd || group.maxYmd || group.ymd);

        if (mdTanggal) mdTanggal.textContent = dispPeriod;

        if (mdOperator) mdOperator.textContent = group.op || "-";

        // MRP yang benar-benar muncul di detail (ambil dari __dispo)
        const mrpsInDetail = Array.from(
            new Set((group.details || []).map((r) => r.__dispo).filter(Boolean))
        );
        const mrpText =
            (mrpsInDetail.length ? mrpsInDetail : dispoList).join(", ") || "-";

        if (mdMrp) mdMrp.textContent = mrpText;
        if (mdWerks) mdWerks.textContent = werks || "-";

        if (mdMeta) {
            mdMeta.textContent =
                `${fmtBudatDisplay(group.ymd)} • ${group.op || "-"} • ` +
                `Menit Kerja ${fmt2(group.totalKerja)} • Menit Inspect ${fmt2(
                    group.totalInspect
                )}`;
        }

        // ✅ tampilkan total per tanggal (kalau tanggalnya > 1)
        if (mdPerDate) {
            const perDates = Array.from(
                (group.byDate || new Map()).values()
            ).sort((a, b) => a.ymd.localeCompare(b.ymd));

            if (perDates.length <= 1) {
                mdPerDate.classList.add("hidden");
                mdPerDate.innerHTML = "";
            } else {
                mdPerDate.classList.remove("hidden");

                const openByDefault = perDates.length <= 2; // >2 hari: default collapse biar hemat ruang HP

                mdPerDate.innerHTML = `
      <details class="group" ${openByDefault ? "open" : ""}>
        <summary class="cursor-pointer select-none flex items-center justify-between gap-2">
          <div class="text-xs font-semibold text-slate-700">
            Total per Tanggal
            <span class="text-[11px] text-slate-500 font-normal">(${
                perDates.length
            } hari)</span>
          </div>
          <span class="text-[11px] text-slate-500 group-open:rotate-180 transition-transform">▾</span>
        </summary>

        <div class="mt-2 overflow-x-auto">
          <table class="w-full min-w-[420px] text-[11px]">
            <thead class="text-slate-600">
              <tr>
                <th class="text-left py-1 pr-3">Tanggal</th>
                <th class="text-right py-1 px-3">Menit Kerja</th>
                <th class="text-right py-1 pl-3">Menit Inspect</th>
              </tr>
            </thead>
            <tbody class="text-slate-800">
              ${perDates
                  .map(
                      (d) => `
                <tr class="border-t border-slate-200">
                  <td class="py-1 pr-3 whitespace-nowrap">${fmtBudatDisplay(
                      d.ymd
                  )}</td>
                  <td class="py-1 px-3 text-right font-semibold">${fmt2(
                      d.totalKerja
                  )}</td>
                  <td class="py-1 pl-3 text-right font-semibold">${fmt2(
                      d.totalInspect
                  )}</td>
                </tr>
              `
                  )
                  .join("")}
            </tbody>
          </table>
        </div>
      </details>
    `;
            }
        }

        // render tbody detail
        if (mdTbody) {
            const rows = group.details || [];
            mdTbody.innerHTML = rows
                .map(
                    (r, i) => `
      <tr class="align-top">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2">${getDateCell(r)}</td>
        <td class="px-3 py-2">${getVal(r, "SNAME") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "ARBPL") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "KTEXT") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "AUFNR") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "MATNR") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "MAKTX") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "PSMNG") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "GMNGX") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "SISA") ?? ""}</td>
        <td class="px-3 py-2">${getVal(r, "GMEIN") ?? ""}</td>
        <td class="px-3 py-2 whitespace-nowrap">${fmt2(
            toNum(getVal(r, "TTCNF"))
        )}</td>
        <td class="px-3 py-2 whitespace-nowrap">${fmt2(
            toNum(getVal(r, "TTCNF2"))
        )}</td>

      </tr>
    `
                )
                .join("");
        }

        // show modal + lock scroll
        mdModal.classList.remove("hidden");
        mdModal.classList.add("flex");
        document.body.classList.add("overflow-hidden");
    }

    function closeMrpDetailModal() {
        if (!mdModal) return;
        mdModal.classList.add("hidden");
        mdModal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    }

    // tombol close
    mdClose1?.addEventListener("click", closeMrpDetailModal);
    mdClose2?.addEventListener("click", closeMrpDetailModal);

    // klik backdrop untuk tutup
    mdModal?.addEventListener("click", (e) => {
        if (
            e.target === mdModal ||
            e.target.classList.contains("mrp-detail-backdrop")
        ) {
            closeMrpDetailModal();
        }
    });

    // ESC untuk tutup
    document.addEventListener("keydown", (e) => {
        if (
            e.key === "Escape" &&
            mdModal &&
            !mdModal.classList.contains("hidden")
        ) {
            closeMrpDetailModal();
        }
    });

    const COLSPAN = isMrpMode ? 5 : 13;

    if ((!hasPernr && !hasMrp) || (!budat && !isRange)) {
        if (tbody) {
            tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="${COLSPAN}">
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
    const NF2 = new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    function fmt2(v) {
        const n = Number(v);
        return Number.isFinite(n) ? NF2.format(n) : "-";
    }
    ("");

    // Render ringkasan (Operator + Total menit kerja/inspect)
    function renderSummary(allRows) {
        const summary = document.getElementById("hasil-summary");
        const headerSlot = document.getElementById("header-mrp-slot");

        // Reset dulu keduanya
        if (summary) summary.innerHTML = "";
        if (headerSlot) {
            headerSlot.innerHTML = "";
            headerSlot.classList.add("hidden");
        }

        // =========================
        // ✅ MODE MRP: TAMPIL DI HEADER (SEBELAH TANGGAL)
        // =========================
        if (isMrpMode) {
            const mrpList = Array.from(new Set(dispoList)).filter(Boolean);
            const mrpValue = mrpList.length ? mrpList.join(", ") : "-";

            if (headerSlot) {
                // Tampilkan slot header
                headerSlot.classList.remove("hidden");
                // Style badge yang rapi sejajar tombol
                headerSlot.innerHTML = `
                  <div class="inline-flex items-center h-9 px-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm">
                     <span class="text-[10px] font-bold uppercase tracking-wider mr-2 text-emerald-500">MRP</span>
                     <span class="text-sm text-emerald-900">${mrpValue}</span>
                  </div>
                `;
            }
            return; // Selesai, tidak perlu render summary bawah
        }
        // =========================
        // ✅ MODE NIK / NON-MRP: TAMPIL DI BAWAH (3 KOTAK)
        // =========================
        if (!summary) return;

        const opSet = new Set(
            allRows.map((r) => getVal(r, "SNAME")).filter(Boolean)
        );
        const opList = Array.from(opSet);
        const operatorLabel =
            opList.length <= 2
                ? opList.join(", ")
                : `${opList[0]} +${opList.length - 1}`;

        const totalKerja = sumNum(
            allRows.map((r) => toNum(getVal(r, "TTCNF")))
        );
        const totalInspect = sumNum(
            allRows.map((r) => toNum(getVal(r, "TTCNF2")))
        );

        summary.innerHTML = `
          <div class="mt-3 grid grid-cols-3 gap-3">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 shadow-sm">
              <div class="text-xs text-emerald-700">Operator</div>
              <div class="text-xs font-semibold text-emerald-900 overflow-hidden text-ellipsis leading-tight max-h-[2.5em]">
                ${operatorLabel || "-"}
              </div>
            </div>
            
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 shadow-sm">
              <div class="text-xs text-sky-700">Total Menit Kerja</div>
              <div class="text-xs font-bold sm:text-sm">${fmt2(
                  totalKerja
              )}</div>
            </div>
            
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 shadow-sm">
              <div class="text-xs text-amber-700">Total Menit Inspect</div>
              <div class="text-xs font-bold sm:text-sm">${fmt2(
                  totalInspect
              )}</div>
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

    // --- helper parsing angka (aman untuk "123,45") ---
    // parsing aman: bisa baca "707.20" atau "707,20" atau "1.234,50"
    function toNum(v) {
        if (v === null || v === undefined || v === "") return 0;
        let s = String(v).trim().replace(/\s/g, "");

        const hasDot = s.includes(".");
        const hasComma = s.includes(",");

        if (hasDot && hasComma) {
            // separator terakhir = desimal
            if (s.lastIndexOf(",") > s.lastIndexOf(".")) {
                // 1.234,56 -> 1234.56
                s = s.replace(/\./g, "").replace(",", ".");
            } else {
                // 1,234.56 -> 1234.56
                s = s.replace(/,/g, "");
            }
        } else if (hasComma) {
            // 1234,56 -> 1234.56
            s = s.replace(/\./g, "").replace(",", ".");
        } else {
            // 1234.56 atau 1234
            s = s.replace(/,/g, "");
        }

        const n = Number(s);
        return Number.isFinite(n) ? n : 0;
    }

    // --- ambil tanggal YYYYMMDD untuk grouping ---
    function getDateYmd(r) {
        const raw = (
            r?.__budat ||
            r?.BUDAT ||
            r?.budat ||
            budat ||
            from ||
            ""
        ).toString();
        const y = raw.replace(/\D/g, "");
        return y.length === 8 ? y : "";
    }

    let mrpGroupMap = new Map();
    let mrpClickBound = false;

    function renderMrpSummaryTable(allRows) {
        mrpGroupMap = new Map();

        for (const r of allRows) {
            const ymd = getDateYmd(r) || "";
            const op = String(getVal(r, "SNAME") || "-").trim() || "-";

            // ✅ kalau range: gabungkan per OPERATOR (bukan per tanggal)
            // kalau single day: tetap aman (boleh gabung juga, hasilnya sama)
            const key = isRange ? op : `${ymd}|${op}`;

            if (!mrpGroupMap.has(key)) {
                mrpGroupMap.set(key, {
                    key,
                    op,
                    minYmd: ymd || null,
                    maxYmd: ymd || null,
                    totalKerja: 0,
                    totalInspect: 0,
                    details: [],
                    byDate: new Map(), // ymd -> { ymd, totalKerja, totalInspect }
                });
            }

            const g = mrpGroupMap.get(key);

            // update rentang tanggal yang benar-benar ada untuk operator ini
            if (ymd) {
                if (!g.minYmd || ymd < g.minYmd) g.minYmd = ymd;
                if (!g.maxYmd || ymd > g.maxYmd) g.maxYmd = ymd;

                if (!g.byDate.has(ymd)) {
                    g.byDate.set(ymd, { ymd, totalKerja: 0, totalInspect: 0 });
                }
                const d = g.byDate.get(ymd);
                d.totalKerja += toNum(getVal(r, "TTCNF"));
                d.totalInspect += toNum(getVal(r, "TTCNF2"));
            }

            // total gabungan
            g.totalKerja += toNum(getVal(r, "TTCNF"));
            g.totalInspect += toNum(getVal(r, "TTCNF2"));
            g.details.push(r);
        }

        const groups = Array.from(mrpGroupMap.values()).sort((a, b) => {
            const ad = a.minYmd || "";
            const bd = b.minYmd || "";
            if (ad !== bd) return ad.localeCompare(bd);
            return a.op.localeCompare(b.op);
        });

        const periodText = (g) => {
            if (g.minYmd && g.maxYmd && g.minYmd !== g.maxYmd) {
                return `${fmtBudatDisplay(g.minYmd)} – ${fmtBudatDisplay(
                    g.maxYmd
                )}`;
            }
            return fmtBudatDisplay(g.minYmd || g.maxYmd || "");
        };

        tbody.innerHTML = groups
            .map((g, idx) => {
                const dispPeriod = periodText(g);
                const dataKey = encodeURIComponent(g.key);

                return `
            <tr class="mrp-summary-row cursor-pointer hover:bg-emerald-50/60 transition-colors"
                data-key="${dataKey}">
              <td class="px-2 py-2">${idx + 1}</td>
              <td class="px-2 py-2 whitespace-normal leading-tight">${dispPeriod}</td>
              <td class="px-2 py-2 font-semibold text-slate-800">${g.op}</td>
              <td class="px-2 py-2 whitespace-nowrap">${fmt2(g.totalKerja)}</td>
              <td class="px-2 py-2 whitespace-nowrap">${fmt2(
                  g.totalInspect
              )}</td>
            </tr>
          `;
            })
            .join("");
    }
    // Render TABLE
    function renderTable(rows) {
        if (!tbody) return;

        if (!rows.length) {
            tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500 text-left" colspan="${COLSPAN}">Tidak ada data.</td></tr>`;
            return;
        }

        // ✅ MODE MRP: tampilkan ringkasan per Tanggal + Operator (klik untuk detail)
        if (isMrpMode) {
            return renderMrpSummaryTable(rows);
        }

        // ✅ MODE NON-MRP: tampilkan detail seperti sekarang
        tbody.innerHTML = rows
            .map(
                (r, i) => `
      <tr class="align-top">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2">${getDateCell(r)}</td>
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

    function buildDetailSubTable(detailRows) {
        const head = `
      <table class="w-full min-w-[1200px] text-sm text-center">
        <thead class="bg-slate-100 sticky top-0 z-10">
          <tr>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">No.</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Tanggal</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Operator</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Work Center</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Desc. Work Center</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">PRO</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Material</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Desc</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">QTY PRO</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Qty Konfirmasi</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">QTY Sisa</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Uom</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Menit Kerja</th>
            <th class="px-3 py-2 text-xs font-semibold text-slate-700 border-b">Menit Inspect</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-200">
    `;

        const body = detailRows
            .map(
                (r, i) => `
        <tr class="align-top">
          <td class="px-3 py-2">${i + 1}</td>
          <td class="px-3 py-2">${getDateCell(r)}</td>
          <td class="px-3 py-2">${getVal(r, "SNAME") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "ARBPL") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "KTEXT") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "AUFNR") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "MATNR") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "MAKTX") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "PSMNG") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "GMNGX") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "SISA") ?? ""}</td>
          <td class="px-3 py-2">${getVal(r, "GMEIN") ?? ""}</td>
          <td class="px-3 py-2">${fmt2(toNum(getVal(r, "TTCNF")))}</td>
          <td class="px-3 py-2">${fmt2(toNum(getVal(r, "TTCNF2")))}</td>
        </tr>
    `
            )
            .join("");

        const foot = `</tbody></table>`;
        return head + body + foot;
    }

    if (!mrpClickBound) {
        mrpClickBound = true;

        tbody.addEventListener("click", (e) => {
            const tr = e.target.closest(".mrp-summary-row");
            if (!tr) return;

            const key = decodeURIComponent(tr.dataset.key || "");
            const group = mrpGroupMap.get(key);
            if (!group) return;

            openMrpDetailModal(group);
        });
    }

    const escHtml = (s) =>
        String(s ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");

    const mrpList = Array.from(new Set(dispoList)).filter(Boolean);

    const modalEl = document.getElementById("mrpDetailModal");
    const modalBackdrop = document.getElementById("mrpDetailBackdrop");
    const modalCloseBtn = document.getElementById("mrpDetailClose");
    const modalTitleEl = document.getElementById("mrpDetailTitle");
    const modalSubEl = document.getElementById("mrpDetailSubtitle");
    const modalMetaEl = document.getElementById("mrpDetailMeta");
    const modalTableWrap = document.getElementById("mrpDetailTableWrap");
    const modalCardsWrap = document.getElementById("mrpDetailCardsWrap");

    function closeMrpModal() {
        modalEl?.classList.add("hidden");
        document.body.style.overflow = "";
    }

    modalCloseBtn?.addEventListener("click", closeMrpModal);
    modalBackdrop?.addEventListener("click", closeMrpModal);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeMrpModal();
    });

    function buildDetailCards(detailRows) {
        return detailRows
            .map((r) => {
                const pro = getVal(r, "AUFNR") ?? "-";
                const wc = getVal(r, "ARBPL") ?? "-";
                const wcDesc = getVal(r, "KTEXT") ?? "-";
                const mat = getVal(r, "MATNR") ?? "-";
                const desc = getVal(r, "MAKTX") ?? "-";
                const uom = getVal(r, "GMEIN") ?? "-";

                return `
        <div class="rounded-xl border border-slate-200 p-3 bg-white shadow-sm">
          <div class="flex items-start justify-between gap-2">
            <div class="text-xs font-semibold">${escHtml(pro)}</div>
            <div class="text-[11px] text-slate-500">${escHtml(
                getDateCell(r)
            )}</div>
          </div>
          <div class="mt-1 text-[11px] text-slate-600">${escHtml(
              wc
          )} • ${escHtml(wcDesc)}</div>
          <div class="mt-2 text-xs font-medium">${escHtml(desc)}</div>
          <div class="mt-1 text-[11px] text-slate-600">${escHtml(mat)}</div>

          <div class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
            <div><div class="text-slate-500">QTY PRO</div><div class="font-semibold">${escHtml(
                getVal(r, "PSMNG") ?? ""
            )}</div></div>
            <div><div class="text-slate-500">Qty Konfirmasi</div><div class="font-semibold">${escHtml(
                getVal(r, "GMNGX") ?? ""
            )}</div></div>
            <div><div class="text-slate-500">QTY Sisa</div><div class="font-semibold">${escHtml(
                getVal(r, "SISA") ?? ""
            )}</div></div>
            <div><div class="text-slate-500">Uom</div><div class="font-semibold">${escHtml(
                uom
            )}</div></div>
            <div><div class="text-slate-500">Menit Kerja</div><div class="font-semibold">${fmt2(
                toNum(getVal(r, "TTCNF"))
            )}</div></div>
            <div><div class="text-slate-500">Menit Inspect</div><div class="font-semibold">${fmt2(
                toNum(getVal(r, "TTCNF2"))
            )}</div></div>
          </div>
        </div>
      `;
            })
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
        async function fetchOne(oneDispo) {
            const qs = new URLSearchParams();
            if (hasPernr) qs.set("pernr", pernr);
            if (hasMrp) {
                qs.set("werks", werks);
                qs.set("dispo", oneDispo);
            }
            qs.set("budat", ymd);

            const url = `/api/yppi019/hasil?${qs.toString()}`;
            const { ok, data, status } = await fetchJson(url);

            if (!ok)
                throw new Error(
                    (data && (data.error || data.message)) || `HTTP ${status}`
                );
            const rows = Array.isArray(data.rows) ? data.rows : [];
            return rows.map((r) => ({ ...r, __budat: ymd, __dispo: oneDispo }));
        }

        // ✅ multi mrp -> loop dispo satu-satu
        if (hasMrp && dispoList.length > 1) {
            let all = [];
            for (const d of dispoList) {
                const part = await fetchOne(d);
                all = all.concat(part);
            }
            return all;
        }

        // single mode
        return await fetchOne(dispoList[0] || dispoSingle);
    }
    // Range via API (jika tersedia)
    async function tryLoadRangeViaApi(f, t) {
        // ✅ kalau multi dispo (mis. D24,G32) -> panggil endpoint berkali-kali lalu gabungkan
        if (hasMrp && Array.isArray(dispoList) && dispoList.length > 1) {
            let merged = [];
            for (const oneDispo of dispoList) {
                const qs = new URLSearchParams();
                if (hasPernr) qs.set("pernr", pernr);
                qs.set("werks", werks);
                qs.set("dispo", oneDispo);
                qs.set("from", f);
                qs.set("to", t);

                const url = `/api/yppi019/hasil-range?${qs.toString()}`;
                const { ok, data } = await fetchJson(url);
                if (!ok) return null; // biar fallback ke FE loop

                const rows = Array.isArray(data.rows) ? data.rows : [];
                merged = merged.concat(
                    rows.map((r) => ({
                        ...r,
                        __budat: r.BUDAT || r.budat || null,
                        __dispo: oneDispo,
                    }))
                );
            }
            return merged;
        }

        // single dispo / pernr mode -> tetap seperti semula
        const qs = new URLSearchParams();
        if (hasPernr) qs.set("pernr", pernr);
        if (hasMrp) {
            qs.set("werks", werks);
            qs.set("dispo", dispoList?.[0] || dispo);
        }
        qs.set("from", f);
        qs.set("to", t);

        const url = `/api/yppi019/hasil-range?${qs.toString()}`;
        const { ok, data } = await fetchJson(url);
        if (!ok) return null;

        const rows = Array.isArray(data.rows) ? data.rows : [];
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
            tbody.innerHTML = `<tr><td class="px-3 py-4 text-slate-500 text-left" colspan="${COLSPAN}">Memuat data…</td></tr>`;
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
                tbody.innerHTML = `<tr><td class="px-3 py-4 text-red-600" colspan="${COLSPAN}">${
                    e.message || e
                }</td></tr>`;
        }
    }

    btnRefresh?.addEventListener("click", loadData);
    loadData();
});
