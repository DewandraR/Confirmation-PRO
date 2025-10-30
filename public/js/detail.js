/* public/js/detail.js */
"use strict";

// --- CSRF dari <meta>, tanpa fallback Blade ---
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";

/* =======================
   TIMEOUT HELPERS (NEW)
   ======================= */
function getClientTimeoutMs() {
    const m = document.querySelector('meta[name="client-timeout-ms"]');
    return m && /^\d+$/.test(m.content) ? parseInt(m.content, 10) : 240000; // default 240s
}
async function fetchWithTimeout(url, init = {}) {
    const ctl = new AbortController();
    const ms = getClientTimeoutMs();
    const t = setTimeout(() => ctl.abort(), ms);
    try {
        const res = await fetch(url, { ...init, signal: ctl.signal });
        return res;
    } finally {
        clearTimeout(t);
    }
}

// --- Helper request POST JSON (UPDATED: with timeout) ---
function apiPost(url, payload) {
    const ctl = new AbortController();
    const t = setTimeout(() => ctl.abort(), getClientTimeoutMs());
    return fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": CSRF,
            "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
        body: JSON.stringify(payload),
        signal: ctl.signal,
    }).finally(() => clearTimeout(t));
}

// ===== Helpers umum =====
const padVornr = (v) => String(parseInt(v || "0", 10)).padStart(4, "0");
// NEW: pad item SO ke 6 digit
const padKdpos = (v) => {
    const n = String(v ?? "").trim();
    if (!n) return "";
    const x = parseInt(n, 10);
    return Number.isFinite(x) ? String(x).padStart(6, "0") : n.padStart(6, "0");
};
// NEW: tampilkan tanpa leading zero (front-end only)
function stripLeadingZeros(val) {
    const s = String(val ?? "").trim();
    const t = s.replace(/^0+/, "");
    return t === "" && s !== "" ? "0" : t || s;
}

// Terima dd.mm.yyyy, dd/mm/yyyy, atau yyyy-mm-dd -> yyyymmdd
const toYYYYMMDD = (d) => {
    if (!d) {
        const x = new Date();
        return `${x.getFullYear()}${String(x.getMonth() + 1).padStart(
            2,
            "0"
        )}${String(x.getDate()).padStart(2, "0")}`;
    }
    const s = String(d);
    const m1 = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(s);
    if (m1) return `${m1[3]}${m1[2]}${m1[1]}`;
    const m2 = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
    if (m2) return `${m2[1]}${m2[2]}${m2[3]}`;
    const m3 = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(s);
    if (m3) return `${m3[3]}${m3[2]}${m3[1]}`;
    return s.replace(/\D/g, "");
};

// Format ISO/Date ke dd/mm/yyyy hh:mm
const fmtDateTime = (iso) => {
    if (!iso) return "-";
    try {
        const d = new Date(iso);
        const dd = String(d.getDate()).padStart(2, "0");
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const yy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, "0");
        const mi = String(d.getMinutes()).padStart(2, "0");
        return `${dd}/${mm}/${yy} ${hh}:${mi}`;
    } catch {
        return iso;
    }
};

// NEW: format menit (angka) → up to 3 desimal, hilangkan nol di akhir
function fmtMinutes(v) {
    if (v === null || v === undefined || v === "") return "-";
    const n = parseFloat(String(v).replace(",", "."));
    if (!Number.isFinite(n)) return "-";
    return n.toFixed(3).replace(/\.?0+$/, "");
}

const ymdToDmy = (ymd) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(ymd || "").trim());
    return m ? `${m[3]}/${m[2]}/${m[1]}` : "";
};
const dmyToYmd = (dmy) => {
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(dmy || "").trim());
    return m ? `${m[3]}-${m[2]}-${m[1]}` : null;
};
const isFutureYmd = (ymd) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(ymd || ""));
    if (!m) return false;
    const d = new Date(+m[1], +m[2] - 1, +m[3]);
    const t = new Date();
    d.setHours(0, 0, 0, 0);
    t.setHours(0, 0, 0, 0);
    return d > t;
};

function getUnitName(unit) {
    const u = String(unit || "").toUpperCase();
    switch (u) {
        case "ST":
        case "PC":
        case "PCS":
        case "EA":
            return "PC";
        case "M3":
            return "Meter Kubik";
        case "M2":
            return "Meter Persegi";
        case "KG":
            return "Kilogram";
        default:
            return u;
    }
}

function collectSapReturnEntries(ret) {
    const entries = [];
    if (!ret || typeof ret !== "object") return entries;
    const keys = [
        "RETURN",
        "ET_RETURN",
        "T_RETURN",
        "E_RETURN",
        "ES_RETURN",
        "EV_RETURN",
    ];
    for (const k of keys) {
        const v = ret[k];
        if (Array.isArray(v)) entries.push(...v.filter(Boolean));
        else if (v && typeof v === "object") entries.push(v);
    }
    for (const [, v] of Object.entries(ret)) {
        if (Array.isArray(v))
            v.forEach(
                (o) =>
                    o &&
                    typeof o === "object" &&
                    (o.MESSAGE || o.TYPE || o.ID) &&
                    entries.push(o)
            );
    }
    return entries;
}
const hasSapError = (ret) =>
    collectSapReturnEntries(ret).some((e) =>
        ["E", "A"].includes(String(e?.TYPE || "").toUpperCase())
    );

// === NEW: normalisasi pesan error server (hindari "0") + handle 423 ===
function mapServerErrorMessage(result) {
    const status = result?.status || 0;
    const j = result?.json || {};
    const entries = collectSapReturnEntries(j.sap_return || {});
    let msg =
        entries.find(
            (e) =>
                ["E", "A"].includes(String(e?.TYPE || "").toUpperCase()) &&
                e?.MESSAGE
        )?.MESSAGE ||
        entries.find((e) => e?.MESSAGE)?.MESSAGE ||
        j.error ||
        j.message ||
        "";

    if (status === 423) {
        // backend per-AUFNR lock
        msg = j.error || "Sedang diproses oleh user lain. Coba lagi sebentar.";
    }
    if (!msg || /^\s*0\s*$/.test(String(msg))) {
        msg =
            status >= 500
                ? "Kesalahan server. Coba lagi."
                : "Gagal dikonfirmasi.";
    }
    return msg;
}

// Tangkap error global
window.onerror = function (msg, src, line, col) {
    const em = document.getElementById("error-message");
    const mm = document.getElementById("error-modal");
    if (em && mm) {
        em.textContent = `Error JS: ${msg} (${line}:${col})`;
        mm.classList.remove("hidden");
    }
    console.error(msg, src, line, col);
};

document.addEventListener("DOMContentLoaded", async () => {
    const isMobile = () => window.matchMedia("(max-width: 768px)").matches;

    // --- URL params ---
    const p = new URLSearchParams(location.search);
    const rawList = p.get("aufnrs") || "";
    const single = p.get("aufnr") || "";
    const IV_PERNR = p.get("pernr") || "";
    const IV_ARBPL = p.get("arbpl") || "";
    const IV_WERKS = p.get("werks") || "";

    // ===== Helper URL /scan (tidak pakai Blade di file publik) =====
    function buildScanUrl() {
        const baseScan =
            document.querySelector('meta[name="scan-url"]')?.content ||
            document.getElementById("back-link")?.getAttribute("href") ||
            document.getElementById("scan-again-link")?.getAttribute("href") ||
            "/scan";
        return IV_PERNR
            ? `${baseScan}?pernr=${encodeURIComponent(IV_PERNR)}`
            : baseScan;
    }
    function goScanWithPernr() {
        window.location.href = buildScanUrl();
    }

    // Update link Kembali & Scan Lagi agar bawa ?pernr=
    const backLink = document.getElementById("back-link");
    const scanAgainLink = document.getElementById("scan-again-link");
    if (backLink) backLink.href = buildScanUrl();
    if (scanAgainLink) scanAgainLink.href = buildScanUrl();

    function ean13CheckDigit(d12) {
        let s = 0,
            t = 0;
        for (let i = 0; i < 12; i++) {
            const n = +d12[i];
            if (i % 2 === 0) s += n;
            else t += n;
        }
        return (10 - ((s + 3 * t) % 10)) % 10;
    }
    function normalizeAufnr(raw) {
        let s = String(raw || "").replace(/\D/g, "");
        if (s.length === 13) {
            const cd = ean13CheckDigit(s.slice(0, 12));
            if (cd === +s[12]) s = s.slice(0, 12);
        }
        return s;
    }

    let AUFNRS = (rawList ? rawList.split(/[,\s]+/) : []).filter(Boolean);
    if (!AUFNRS.length && single) AUFNRS = [single];
    AUFNRS = [
        ...new Set(
            AUFNRS.map(normalizeAufnr).filter((x) => /^\d{12}$/.test(x))
        ),
    ];

    // --- Elemen UI ---
    const headAUFNR = document.getElementById("headAUFNR");
    const content = document.getElementById("content");
    const loading = document.getElementById("loading");
    const tableBody = document.getElementById("tableBody");
    const totalItems = document.getElementById("totalItems");
    const selectAll = document.getElementById("selectAll");
    const confirmButton = document.getElementById("confirm-button");
    const selectedCountSpan = document.getElementById("selected-count");
    const confirmModal = document.getElementById("confirm-modal");
    const confirmationList = document.getElementById("confirmation-list");
    const yesButton = document.getElementById("yes-button");
    const cancelButton = document.getElementById("cancel-button");
    const errorModal = document.getElementById("error-modal");
    const errorMessage = document.getElementById("error-message");
    const errorOkButton = document.getElementById("error-ok-button");
    const warningModal = document.getElementById("warning-modal");
    const warningMessage = document.getElementById("warning-message");
    const warningList = document.getElementById("warning-list");
    const warningTitle = document.getElementById("warning-title");
    const warningHeader = document.getElementById("warning-header");
    const warningOkButton = document.getElementById("warning-ok-button");
    const successModal = document.getElementById("success-modal");
    const successList = document.getElementById("success-list");
    const successOkButton = document.getElementById("success-ok-button");
    // Mode halaman: WC (pakai arbpl+werks+nik) vs PRO (pakai aufnr+nik)
    const isWCMode = AUFNRS.length === 0 && !!IV_ARBPL;

    // BUDAT controls
    const budatInput = document.getElementById("budat-input"); // hidden yyyy-mm-dd
    const budatInputText = document.getElementById("budat-input-text"); // visible dd/mm/yyyy
    const budatOpen = document.getElementById("budat-open");

    // Search controls
    const searchInput = document.getElementById("searchInput");
    const clearSearch = document.getElementById("clearSearch");
    const shownCountEl = document.getElementById("shownCount");

    // Quick date filter controls
    const fltToday = document.getElementById("fltToday");
    const fltOutgoing = document.getElementById("fltOutgoing");
    const fltPeriod = document.getElementById("fltPeriod");
    const fltAllDate = document.getElementById("fltAllDate"); // <-- NEW
    const periodPicker = document.getElementById("periodPicker");
    const periodFrom = document.getElementById("periodFrom");
    const periodTo = document.getElementById("periodTo");
    const applyPeriod = document.getElementById("applyPeriod");
    const clearFilterBtn = document.getElementById("clearFilter");

    // tombol yang tidak digunakan
    fltToday?.classList.add("hidden");
    fltPeriod?.classList.add("hidden");
    periodPicker?.classList.add("hidden");

    // Header: PERNR/AUFNR/WC
    if (headAUFNR) {
        let headText = "-";
        if (AUFNRS.length > 0) headText = AUFNRS.join(", ");
        else if (IV_ARBPL) headText = `WC: ${IV_ARBPL}`;
        if (IV_PERNR) headText = `${IV_PERNR} / ${headText}`;
        headAUFNR.textContent = headText.replace(/ \/ -$/, "");
    }

    // === Flags untuk kontrol redirect setelah hasil konfirmasi ===
    let pendingResetInput = null,
        isWarningOpen = false;
    let isResultWarning = false; // warning dari hasil konfirmasi (campuran)
    let isResultError = false; // error dari hasil konfirmasi (semua gagal)

    // Sinkronisasi BUDAT text ↔ hidden
    const warning = (msg) => {
        // Warning validasi biasa (bukan hasil konfirmasi)
        isResultWarning = false;

        warningTitle.textContent = "Peringatan";
        warningHeader.className =
            "bg-gradient-to-r from-yellow-50 to-yellow-100 px-4 py-3 border-b border-yellow-200";
        warningTitle.classList.remove("text-red-800");
        warningTitle.classList.add("text-yellow-800");
        warningMessage.innerHTML = msg;
        warningMessage.classList.remove("hidden");
        warningList.classList.add("hidden");
        warningModal.classList.remove("hidden");
    };
    function syncTextToHidden() {
        const ymd = dmyToYmd(budatInputText.value);
        if (!ymd) {
            warning("Format tanggal harus dd/mm/yyyy.");
            budatInputText.value = ymdToDmy(budatInput.value);
            return;
        }
        if (isFutureYmd(ymd)) {
            warning("Posting Date tidak boleh melebihi hari ini.");
            budatInputText.value = ymdToDmy(budatInput.value);
            return;
        }
        budatInput.value = ymd;
    }
    function syncHiddenToText() {
        budatInputText.value = ymdToDmy(budatInput.value);
    }
    budatInputText.addEventListener("blur", syncTextToHidden);
    budatInputText.addEventListener("change", syncTextToHidden);
    budatInput.addEventListener("change", syncHiddenToText);
    budatOpen?.addEventListener("click", (e) => {
        e.preventDefault();
        try {
            budatInput.showPicker && budatInput.showPicker();
        } catch {}
    });
    syncHiddenToText();

    // --- Ambil data ---
    let rowsAll = [],
        failures = [];
    try {
        if (AUFNRS.length > 0) {
            const results = await Promise.allSettled(
                AUFNRS.map(async (aufnr) => {
                    let url = `/api/yppi019/material?aufnr=${encodeURIComponent(
                        aufnr
                    )}&pernr=${encodeURIComponent(IV_PERNR)}&auto_sync=0`;
                    if (IV_ARBPL)
                        url += `&arbpl=${encodeURIComponent(IV_ARBPL)}`;
                    if (IV_WERKS)
                        url += `&werks=${encodeURIComponent(IV_WERKS)}`;
                    const res = await fetchWithTimeout(url, {
                        headers: { Accept: "application/json" },
                    });
                    let json;
                    try {
                        json = await res.json();
                    } catch {
                        json = {};
                    }
                    if (!res.ok)
                        throw new Error(
                            json.error || json.message || `HTTP ${res.status}`
                        );
                    const t = json.T_DATA1;
                    return Array.isArray(t) ? t : t ? [t] : [];
                })
            );
            results.forEach((r) =>
                r.status === "fulfilled"
                    ? (rowsAll = rowsAll.concat(r.value))
                    : failures.push(r.reason?.message || "unknown")
            );
        } else {
            const url = `/api/yppi019/material?arbpl=${encodeURIComponent(
                IV_ARBPL
            )}&werks=${encodeURIComponent(IV_WERKS)}&pernr=${encodeURIComponent(
                IV_PERNR
            )}&auto_sync=0`;
            const res = await fetchWithTimeout(url, {
                headers: { Accept: "application/json" },
            });
            let json;
            try {
                json = await res.json();
            } catch {
                json = {};
            }
            if (!res.ok)
                throw new Error(
                    json.error || json.message || `HTTP ${res.status}`
                );
            const t = json.T_DATA1;
            rowsAll = Array.isArray(t) ? t : t ? [t] : [];
        }
    } catch (e) {
        errorMessage.textContent =
            e?.name === "AbortError"
                ? "Waktu tunggu klien habis. Silakan coba lagi."
                : e?.message || "Gagal mengambil data";
        errorModal.classList.remove("hidden");
    } finally {
        loading.classList.add("hidden");
        content.classList.remove("hidden");
    }

    if (!rowsAll.length) {
        tableBody.innerHTML =
            '<tr><td colspan="19" class="px-4 py-8 text-center text-slate-500">Tidak ada data</td></tr>';
        totalItems.textContent = "0";
        if (shownCountEl) shownCountEl.textContent = "0";
        return;
    }

    // --- urutkan & render ---
    // Urut SSAVD ascending; SSAVD kosong ditaruh paling akhir.
    // Tie-breaker: AUFNR, lalu VORNR numerik.
    rowsAll.sort((a, b) => {
        const sa = a.SSAVD ? toYYYYMMDD(a.SSAVD) : "99999999";
        const sb = b.SSAVD ? toYYYYMMDD(b.SSAVD) : "99999999";
        if (sa !== sb) return sa.localeCompare(sb);

        if ((a.AUFNR || "") !== (b.AUFNR || "")) {
            return (a.AUFNR || "").localeCompare(b.AUFNR || "");
        }
        const va = parseInt(a.VORNRX || a.VORNR || "0", 10) || 0;
        const vb = parseInt(b.VORNRX || b.VORNR || "0", 10) || 0;
        return va - vb;
    });

    totalItems.textContent = String(rowsAll.length);

    const esc = (s) =>
        String(s ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");

    // helper normalisasi untuk data-search
    const toKey = (s) => String(s ?? "").toLowerCase();

    tableBody.innerHTML = rowsAll
        .map((r, i) => {
            const vornr = padVornr(r.VORNRX || r.VORNR || "0");
            const qtySPK = parseFloat(r.QTY_SPK ?? 0); // Qty_PRO
            const weMng = parseFloat(r.WEMNG ?? 0);
            const qtySPX = parseFloat(r.QTY_SPX ?? 0);
            const sisaSPK = Math.max(qtySPK - weMng, 0);
            const maxAllow = Math.max(0, Math.min(qtySPX, sisaSPK));
            const meinh = (r.MEINH || "ST").toUpperCase();
            const ltxa1 = String(
                r.LTXA1 ?? r.ltxa1 ?? r.OPDESC ?? r.OPR_TXT ?? r.LTXA1X ?? ""
            ).trim();
            const wcRaw = r.ARBPL0 || r.ARBPL || IV_ARBPL || "-";
            const wcWithDesc = ltxa1 ? `${wcRaw} / ${ltxa1}` : wcRaw;

            // ========= PREFILL MAX (FIX: cek plant dari data baris) =========
            const dispo = String(r.DISPO || "").toUpperCase();
            // Ambil plant dari data baris; fallback ke URL jika ada.
            // Beberapa backend menamai plant sebagai WERKS / PWERK / PLANT, jadi coba semuanya.
            const werksRow = String(
                r.WERKS ?? r.PWERK ?? r.PLANT ?? IV_WERKS ?? ""
            ).replace(/^0+/, ""); // hilangkan leading zero seperti "01000" -> "1000"

            const shouldPrefillMax =
                ["WE1", "WE2", "WM1"].includes(dispo) && werksRow === "1000";

            const defaultQty = shouldPrefillMax ? maxAllow : 0;
            // ================================================================

            // === NEW: normalisasi SSAVD/SSSLD
            const ssavdYMD = toYYYYMMDD(r.SSAVD);
            const sssldYMD = toYYYYMMDD(r.SSSLD);
            const ssavdDMY =
                ssavdYMD && ssavdYMD.length === 8
                    ? `${ssavdYMD.slice(6, 8)}/${ssavdYMD.slice(
                          4,
                          6
                      )}/${ssavdYMD.slice(0, 4)}`
                    : "";
            const sssldDMY =
                sssldYMD && sssldYMD.length === 8
                    ? `${sssldYMD.slice(6, 8)}/${sssldYMD.slice(
                          4,
                          6
                      )}/${sssldYMD.slice(0, 4)}`
                    : "";

            // === NEW: LTIMEX (menit)
            const ltimexStr = fmtMinutes(r.LTIMEX);

            // NEW: Item view tanpa leading zero + versi 6 digit
            const kdpos6 = padKdpos(r.KDPOS);
            const kdposView = stripLeadingZeros(r.KDPOS);
            // gabungan "Sales Order / Item" untuk tampilan & pencarian
            const soItem =
                [r.KDAUF || "", kdposView || ""].filter(Boolean).join("/") ||
                "-";

            const searchStr = [
                r.AUFNR,
                r.MATNRX,
                r.MAKTX,
                r.MAKTX0,
                r.LTXA1,
                r.DISPO,
                r.STEUS,
                r.ARBPL0 || r.ARBPL || "",
                vornr,
                r.PERNR || "",
                r.SNAME || "",
                ssavdDMY,
                sssldDMY,
                // tambahkan LTIME/LTIMEX ke pencarian
                ltimexStr,
                r.KDAUF, // cari berdasarkan SO
                r.KDPOS, // raw KDPOS
                kdpos6, // versi 6 digit
                kdposView, // versi tanpa leading zero (tampilan)
                qtySPK, // bisa cari Qty_PRO
                soItem, // "1110001101/10"
                (r.KDAUF || "") + "/" + kdpos6,
                (r.KDAUF || "") + kdpos6, // tanpa slash juga bisa
                wcWithDesc,
            ]
                .map(toKey)
                .join(" ");

            return `<tr class="odd:bg-white even:bg-slate-50 hover:bg-green-50/40 transition-colors"
        data-aufnr="${r.AUFNR || ""}" data-vornr="${vornr}" data-pernr="${
                r.PERNR || IV_PERNR || ""
            }"
        data-meinh="${r.MEINH || "ST"}" data-gstrp="${toYYYYMMDD(
                r.GSTRP
            )}" data-gltrp="${toYYYYMMDD(r.GLTRP)}"
        data-ssavd="${ssavdYMD}" data-sssld="${sssldYMD}"
        data-ltimex="${ltimexStr}"
        data-arbpl0="${r.ARBPL0 || r.ARBPL || IV_ARBPL || "-"}"
        data-ltxa1="${(r.LTXA1 || "-").replace(/"/g, "&quot;")}"
        data-charg="${r.CHARG || ""}"
        data-maktx="${(r.MAKTX || "-").replace(/"/g, "&quot;")}"
        data-qtyspk="${qtySPK}"
        data-sname="${(r.SNAME || "").replace(/"/g, "&quot;")}"
        data-matnrx="${r.MATNRX || ""}"
        data-maktx0="${(r.MAKTX0 || "").replace(/"/g, "&quot;")}"
        data-dispo="${r.DISPO || ""}"
        data-steus="${r.STEUS || ""}"
        data-soitem="${soItem.replace(/"/g, "&quot;")}"
        data-search="${searchStr}">
        <td class="px-3 py-3 text-center sticky left-0 bg-inherit border-r border-slate-200">
          <input type="checkbox" class="row-checkbox w-4 h-4 text-green-600 rounded border-slate-300">
        </td>
        <td class="px-3 py-3 text-center bg-inherit border-r border-slate-200">
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-xs font-bold text-green-700 mx-auto">${
              i + 1
          }</div>
        </td>
        <td class="px-3 py-3 text-sm font-semibold text-slate-900">${
            r.AUFNR || "-"
        }</td>

        <!-- NEW: Qty_PRO tampilan ring -->
        <td class="align-middle">
          <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center text-xs font-bold text-gray-700 mx-auto">
            ${Number.isFinite(qtySPK) ? qtySPK : "-"}
          </div>
        </td>
        <!-- END NEW -->

        <td class="px-3 py-3 text-sm text-slate-700 text-center">
          <input type="number" name="QTY_SPX" class="w-28 px-2 py-1 text-center rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-green-400/40 focus:border-green-500 text-sm font-mono"
              value="${defaultQty}" placeholder="${defaultQty}" min="0" data-max="${maxAllow}" data-meinh="${meinh}"
              step="${meinh === "M3" ? "0.001" : "1"}" inputmode="${
                meinh === "M3" ? "decimal" : "numeric"
            }"
              title="Maks: ${maxAllow} (sisa SPK=${sisaSPK}, sisa SPX=${qtySPX})"/>
          <div class="mt-1 text-[11px] text-slate-400">Maks: <b>${maxAllow}</b> (${getUnitName(
                meinh
            )})</div>
        </td>

        <td class="px-3 py-3 text-sm text-slate-700">${ssavdDMY || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${sssldDMY || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${r.MATNRX || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${r.MAKTX || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${r.MAKTX0 || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${ltimexStr}</td>
        <td class="px-3 py-3 text-sm text-slate-700 col-workcenter">${wcWithDesc}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${
            r.PERNR || IV_PERNR || "-"
        }</td>
        <td class="px-3 py-3 text-sm text-slate-700">${r.SNAME || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${r.DISPO || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${r.STEUS || "-"}</td>
        <td class="px-3 py-3 text-sm text-slate-700 font-mono whitespace-nowrap">${soItem}</td>
      </tr>`;
        })
        .join("");

    // === WC mode: tampilkan " / LTXA1" di header (sebelah WC) ===
    if (isWCMode && headAUFNR) {
        // Ambil LTXA1 pertama yang tidak kosong (fallback untuk variasi nama field)
        const getOpDesc = (r) =>
            String(
                r.LTXA1 ??
                    r.ltxa1 ??
                    r.OPDESC ?? // possible alias
                    r.OPR_TXT ?? // possible alias
                    r.LTXA1X ?? // possible alias
                    ""
            ).trim();

        const uniqueDesc = [...new Set(rowsAll.map(getOpDesc).filter(Boolean))];
        if (uniqueDesc.length) {
            // jika banyak deskripsi, tampilkan satu + penanda jumlah lainnya
            const first = uniqueDesc[0];
            const more =
                uniqueDesc.length > 1
                    ? ` (+${uniqueDesc.length - 1} lainnya)`
                    : "";
            const nikPrefix = IV_PERNR ? `${IV_PERNR} / ` : "";
            headAUFNR.textContent = `${nikPrefix}WC: ${IV_ARBPL} / ${first}${more}`;
        }
    }

    /* === WC MODE: sembunyikan kolom Work Center (header + semua sel) === */
    if (isWCMode) {
        document
            .querySelectorAll(".col-workcenter, .col-workcenter-desc")
            .forEach((el) => {
                // gunakan style agar tidak bergantung ke Tailwind di runtime
                el.style.display = "none";
            });
    }
    /* === END hide Work Center === */

    // === SEARCH/FILTER berbasis data-search + quick date filters ===
    const normTxt = (s) => String(s || "").toLowerCase();

    // Quick date filter state
    let dateFilterMode = "none"; // 'none' | 'today' | 'outgoing' | 'period'
    let qCurrent = ""; // current search query (lowercased)
    let pfYMD = ""; // period from yyyymmdd
    let ptYMD = ""; // period to yyyymmdd

    function ymdToday() {
        const d = new Date();
        return `${d.getFullYear()}${String(d.getMonth() + 1).padStart(
            2,
            "0"
        )}${String(d.getDate()).padStart(2, "0")}`;
    }

    function rowPassesDateFilter(tr) {
        if (dateFilterMode === "none") return true;

        const ss = (tr.dataset.ssavd || "").replace(/\D/g, "");
        const ee = (tr.dataset.sssld || "").replace(/\D/g, "");
        if (!ss && !ee) return false;

        if (dateFilterMode === "today") {
            const t = ymdToday();
            return ss === t; // SSAVD persis hari ini
        }

        if (dateFilterMode === "outgoing") {
            const t = ymdToday();
            const s = ss || t;
            const e = ee || t;
            return s <= t && t <= e; // di rentang
        }

        if (dateFilterMode === "period") {
            if (!pfYMD || !ptYMD) return true; // belum pilih -> tampilkan semua
            const s = ss || "00000000";
            const e = ee || "99991231";
            // tampilkan jika overlap antara [s,e] dan [pf,pt]
            return !(e < pfYMD || s > ptYMD);
        }

        return true;
    }

    function visibleRowCheckboxes() {
        return Array.from(tableBody.querySelectorAll("tr"))
            .filter((tr) => tr.style.display !== "none")
            .map((tr) => tr.querySelector(".row-checkbox"))
            .filter(Boolean);
    }
    function updateConfirmButtonState() {
        const count = visibleRowCheckboxes().filter((cb) => cb.checked).length;
        selectedCountSpan.textContent = count;
        confirmButton.disabled = count === 0;
    }

    // ============ MOBILE ROW DETAIL POPUP ============
    const rowDetailModal = document.getElementById("row-detail-modal");
    const rowDetailBody = document.getElementById("row-detail-body");
    const rowDetailClose = document.getElementById("row-detail-close");
    const rowDetailCancel = document.getElementById("row-detail-cancel");
    const rowDetailSave = document.getElementById("row-detail-save");
    const rowDetailSelect = document.getElementById("row-detail-select");

    let currentRow = null; // <tr> aktif saat modal

    function ymdToDMY(ymd) {
        return ymd && /^\d{8}$/.test(ymd)
            ? `${ymd.slice(6, 8)}/${ymd.slice(4, 6)}/${ymd.slice(0, 4)}`
            : "-";
    }

    // buka modal untuk 1 row
    function openRowDetail(tr) {
        currentRow = tr;

        // ambil data dari dataset + elemen input qty
        const qtyInput = tr.querySelector('input[name="QTY_SPX"]');
        const cb = tr.querySelector(".row-checkbox");
        const data = {
            aufnr: tr.dataset.aufnr || "-",
            vornr: tr.dataset.vornr || "-",
            wc: tr.dataset.arbpl0 || "-",
            ltxa1: tr.dataset.ltxa1 || "",
            maktx: tr.dataset.maktx || "-",
            maktx0: tr.dataset.maktx0 || "-",
            matnrx: tr.dataset.matnrx || "-",
            soitem: tr.dataset.soitem || "-",
            pernr: tr.dataset.pernr || "-",
            sname: tr.dataset.sname || "-",
            dispo: tr.dataset.dispo || "-",
            steus: tr.dataset.steus || "-",
            ltimex: tr.dataset.ltimex || "-",
            ssavd: ymdToDMY((tr.dataset.ssavd || "").replace(/\D/g, "")),
            sssld: ymdToDMY((tr.dataset.sssld || "").replace(/\D/g, "")),
            qtyspk: tr.dataset.qtyspk || "0",
            meinh: tr.dataset.meinh || "ST",
            qtycur: qtyInput ? qtyInput.value || "0" : "0",
            max: qtyInput ? qtyInput.dataset.max || "0" : "0",
            checked: cb ? cb.checked : false,
        };

        const unitNamePopup = getUnitName(data.meinh);
        // isi konten modal
        rowDetailBody.innerHTML = `
    <div class="grid grid-cols-2 gap-3">
      <div>
        <div class="text-[11px] text-slate-500">PRO</div>
        <div class="font-semibold font-mono">${esc(data.aufnr)}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Work Center</div>
        <div class="font-semibold">
    ${esc(data.wc + (data.ltxa1 ? " / " + data.ltxa1 : ""))}
  </div>
      </div>

      <div>
        <div class="text-[11px] text-slate-500">Material</div>
        <div class="font-semibold">${esc(data.matnrx)}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Description</div>
        <div class="font-semibold">${esc(data.maktx)}</div>
      </div>

      <div>
        <div class="text-[11px] text-slate-500">Deskripsi FG</div>
        <div class="font-semibold">${esc(data.maktx0 || "-")}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Sales Order / Item</div>
        <div class="font-semibold font-mono">${esc(data.soitem)}</div>
      </div>

      <div>
        <div class="text-[11px] text-slate-500">Start Date</div>
        <div class="font-semibold">${esc(data.ssavd)}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Finish Date</div>
        <div class="font-semibold">${esc(data.sssld)}</div>
      </div>

      <div>
        <div class="text-[11px] text-slate-500">Qty PRO</div>
        <div class="font-semibold">${esc(data.qtyspk)}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Menit</div>
        <div class="font-semibold">${esc(data.ltimex)}</div>
      </div>

      <div>
        <div class="text-[11px] text-slate-500">MRP</div>
        <div class="font-semibold">${esc(data.dispo)}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Control Key</div>
        <div class="font-semibold">${esc(data.steus)}</div>
      </div>

      <div>
        <div class="text-[11px] text-slate-500">NIK Operator</div>
        <div class="font-semibold">${esc(data.pernr)}</div>
      </div>
      <div>
        <div class="text-[11px] text-slate-500">Nama Operator</div>
        <div class="font-semibold">${esc(data.sname)}</div>
      </div>
    </div>

    <div class="mt-3">
  <label class="text-[11px] text-slate-500 block mb-1">
    Qty Input (${esc(unitNamePopup)})
  </label>
  <input id="row-detail-qty" type="number"
         inputmode="${
             (data.meinh || "").toUpperCase() === "M3" ? "decimal" : "numeric"
         }"
         class="w-full px-3 py-2 rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-500 font-mono"
         step="${(data.meinh || "").toUpperCase() === "M3" ? "0.001" : "1"}"
         min="0"
         placeholder="${esc(data.max)}"
         value="${esc(data.qtycur)}"
         data-max="${esc(data.max)}"
         data-meinh="${esc(data.meinh)}">
  <div class="mt-1 text-[11px] text-slate-500">
    Maks: <b>${esc(data.max)}</b> (${esc(unitNamePopup)})
  </div>
</div>
  `;
        const modalQty = document.getElementById("row-detail-qty");
        const maxAllow = parseFloat(modalQty.dataset.max || "0") || 0;
        const unit = String(modalQty.dataset.meinh || "ST").toUpperCase();

        // auto-clear "0" saat fokus
        modalQty.addEventListener("focus", function () {
            if (this.value === "0") this.value = "";
        });

        // validasi realtime: jangan lebih dari max
        modalQty.addEventListener("input", function () {
            const v = parseFloat(String(this.value).replace(",", "."));
            if (!isNaN(v) && v > maxAllow && !isWarningOpen) {
                warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
                pendingResetInput = this; // biar tombol OK bisa reset/fokus balik
                isWarningOpen = true;
                // pastikan modal warning muncul di atas (lihat CSS z-index di bawah)
                warningModal.classList.remove("hidden");
                this.blur(); // tutup keypad agar pengguna melihat peringatan
            }
        });

        // normalisasi saat blur (ikut aturan sama spt tabel)
        modalQty.addEventListener("blur", function () {
            if (this.value.trim() === "") this.value = "0";
            let v = parseFloat(String(this.value).replace(",", "."));
            if (isNaN(v) || v < 0) v = 0;
            if (
                unit === "ST" ||
                unit === "PC" ||
                unit === "PCS" ||
                unit === "EA"
            ) {
                v = Math.floor(v);
            } else if (unit === "M3") {
                v = Math.round(v * 1000) / 1000;
            }
            // biarkan v apa adanya di sini; kalau >max user akan melihat warning saat klik Simpan
            this.value = String(v);
        });

        const inputModal = rowDetailBody.querySelector("#row-detail-qty");

        // 1) Auto-clear "0" saat fokus (biar user tidak perlu hapus manual)
        inputModal.addEventListener("focus", function () {
            if (this.value === "0") this.value = "";
            // Select seluruh isi kalau bukan "0"
            this.select && this.value && this.select();
        });

        // 2) Validasi real-time: peringatkan jika > Maks
        inputModal.addEventListener("input", function () {
            if (this.value === "" || this.value === "-" || this.value === ".")
                return;

            const v = parseFloat(String(this.value).replace(",", "."));
            const maxAllow = parseFloat(this.dataset.max || "0");

            if (!Number.isNaN(v) && v > maxAllow && !isWarningOpen) {
                warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
                pendingResetInput = this; // supaya saat OK kita reset
                isWarningOpen = true;
                warningModal.classList.remove("hidden");
                this.blur();
            }
        });

        // 3) Normalisasi saat blur (bulatkan & clamp, serta larang negatif)
        inputModal.addEventListener("blur", function () {
            if (this.value.trim() === "") this.value = "0";

            let v = parseFloat(this.value.replace(",", ".") || "0");
            if (Number.isNaN(v)) v = 0;

            const maxAllow = parseFloat(this.dataset.max || "0");
            if (v > maxAllow || v < 0) {
                if (!isWarningOpen) {
                    warningMessage.textContent =
                        v > maxAllow
                            ? `Nilai tidak boleh melebihi batas: ${maxAllow}.`
                            : "Nilai tidak boleh negatif.";
                    pendingResetInput = this;
                    isWarningOpen = true;
                    warningModal.classList.remove("hidden");
                }
                return;
            }

            const u = (this.dataset.meinh || "ST").toUpperCase();
            if (["ST", "PC", "PCS", "EA"].includes(u)) v = Math.floor(v);
            else if (u === "M3") v = Math.round(v * 1000) / 1000;

            this.value = String(v);
        });

        // 4) Enter = klik "Simpan" (biar cepat)
        inputModal.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                rowDetailSave?.click();
            }
        });

        // label tombol pilih
        rowDetailSelect.textContent = data.checked
            ? "Batalkan Pilih"
            : "Pilih Item Ini";

        // tampilkan modal
        rowDetailModal.classList.remove("hidden");
    }

    // close helper
    function closeRowDetail() {
        rowDetailModal.classList.add("hidden");
        currentRow = null;
    }

    // klik pada row (khusus mobile & bukan klik ke elemen interaktif)
    tableBody.addEventListener("click", (e) => {
        if (!isMobile()) return;

        const tag = e.target.tagName.toLowerCase();
        const isInteractive =
            ["input", "button", "label", "svg", "path"].includes(tag) ||
            e.target.closest("button");
        if (isInteractive) return;

        const tr = e.target.closest("tr");
        if (!tr) return;

        openRowDetail(tr);
    });

    // tombol tutup
    [rowDetailClose, rowDetailCancel].forEach((btn) =>
        btn?.addEventListener("click", closeRowDetail)
    );

    // tombol pilih/batal pilih
    rowDetailSelect.addEventListener("click", () => {
        if (!currentRow) return;
        const cb = currentRow.querySelector(".row-checkbox");
        if (!cb) return;
        cb.checked = !cb.checked;

        // sinkronkan state Select All & tombol konfirmasi
        const vis = visibleRowCheckboxes();
        const visChecked = vis.filter((x) => x.checked).length;
        selectAll.checked = vis.length > 0 && visChecked === vis.length;
        selectAll.indeterminate = visChecked > 0 && visChecked < vis.length;
        updateConfirmButtonState();

        rowDetailSelect.textContent = cb.checked
            ? "Batalkan Pilih"
            : "Pilih Item Ini";
    });

    // tombol simpan (Qty Input)
    rowDetailSave.addEventListener("click", () => {
        if (!currentRow) return;
        const inputModal = document.getElementById("row-detail-qty");
        const rowInput = currentRow.querySelector('input[name="QTY_SPX"]');
        if (!rowInput || !inputModal) return;

        let v = parseFloat(String(inputModal.value).replace(",", "."));
        if (isNaN(v)) v = 0;

        const maxAllow =
            parseFloat(inputModal.dataset.max || rowInput.dataset.max || "0") ||
            0;
        const u = String(
            inputModal.dataset.meinh || rowInput.dataset.meinh || "ST"
        ).toUpperCase();

        if (u === "ST" || u === "PC" || u === "PCS" || u === "EA")
            v = Math.floor(v);
        else if (u === "M3") v = Math.round(v * 1000) / 1000;

        if (v <= 0) {
            warningMessage.textContent = "Kuantitas harus lebih dari 0.";
            isWarningOpen = true;
            pendingResetInput = inputModal;
            warningModal.classList.remove("hidden");
            return;
        }

        if (v > maxAllow) {
            warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
            isWarningOpen = true;
            pendingResetInput = inputModal;
            warningModal.classList.remove("hidden");
            return;
        }

        // valid → salin ke input baris & tutup popup
        rowInput.value = String(v);
        closeRowDetail();
    });

    function applyFilters() {
        let shown = 0;
        tableBody.querySelectorAll("tr").forEach((tr) => {
            const searchHit =
                qCurrent.length === 0
                    ? true
                    : (tr.dataset.search || "").includes(qCurrent);
            const dateHit = rowPassesDateFilter(tr);
            const hit = searchHit && dateHit;
            tr.style.display = hit ? "" : "none";
            if (hit) shown++;
        });
        shownCountEl.textContent = shown;
        const vis = visibleRowCheckboxes();
        const visChecked = vis.filter((cb) => cb.checked).length;
        selectAll.checked = vis.length > 0 && visChecked === vis.length;
        selectAll.indeterminate = visChecked > 0 && visChecked < vis.length;
        updateConfirmButtonState();
    }

    // search input
    function filterRows(q) {
        qCurrent = normTxt(q).trim();
        applyFilters();
    }
    filterRows("");
    searchInput?.addEventListener("input", () => {
        const q = searchInput.value;
        filterRows(q);
        clearSearch.classList.toggle("hidden", q.length === 0);
    });
    clearSearch?.addEventListener("click", () => {
        searchInput.value = "";
        filterRows("");
        clearSearch.classList.add("hidden");
        searchInput.focus();
    });

    // --- Default: mode-based quick filter
    if (isWCMode) {
        // WC mode => Outgoing otomatis
        dateFilterMode = "outgoing";
        setActive(fltOutgoing);
        periodPicker?.classList.add("hidden");
    } else {
        // PRO mode => All date (seperti sebelumnya)
        dateFilterMode = "none";
        setActive(fltAllDate);
        periodPicker?.classList.add("hidden");
    }
    applyFilters(); // hitung ulang tampilan & counter

    // ===== Quick Date Filter handlers =====
    function setActive(btn) {
        [fltToday, fltOutgoing, fltPeriod, fltAllDate].forEach((b) => {
            if (!b) return;
            b.classList.remove(
                "bg-emerald-600",
                "text-white",
                "border-emerald-600"
            );
            b.classList.add("border-slate-300");
        });
        if (btn) {
            btn.classList.remove("border-slate-300");
            btn.classList.add(
                "bg-emerald-600",
                "text-white",
                "border-emerald-600"
            );
        }
    }

    fltToday?.addEventListener("click", () => {
        dateFilterMode = "today";
        setActive(fltToday);
        periodPicker?.classList.add("hidden");
        applyFilters();
    });

    fltOutgoing?.addEventListener("click", () => {
        dateFilterMode = "outgoing";
        setActive(fltOutgoing);
        periodPicker?.classList.add("hidden");
        applyFilters();
    });

    fltPeriod?.addEventListener("click", () => {
        dateFilterMode = "period";
        setActive(fltPeriod);
        periodPicker?.classList.remove("hidden");
    });

    applyPeriod?.addEventListener("click", () => {
        pfYMD = toYYYYMMDD(periodFrom?.value || "");
        ptYMD = toYYYYMMDD(periodTo?.value || "");
        if (pfYMD && ptYMD && pfYMD > ptYMD) [pfYMD, ptYMD] = [ptYMD, pfYMD];
        applyFilters();
    });

    // === NEW: All date (tampilkan semua) ===
    fltAllDate?.addEventListener("click", () => {
        dateFilterMode = "none";
        pfYMD = "";
        ptYMD = "";
        setActive(fltAllDate);
        periodPicker?.classList.add("hidden");
        applyFilters();
    });

    clearFilterBtn?.addEventListener("click", () => {
        dateFilterMode = "none";
        pfYMD = "";
        ptYMD = "";
        setActive(fltAllDate || null); // kalau tombol ada, jadikan aktif; kalau tidak, kosongkan
        periodPicker?.classList.add("hidden");
        applyFilters();
    });

    // === Checkbox & tombol konfirmasi ===
    selectAll.addEventListener("change", () => {
        const vis = visibleRowCheckboxes();
        vis.forEach((cb) => (cb.checked = selectAll.checked));
        updateConfirmButtonState();
    });
    document.addEventListener("change", (e) => {
        if (!e.target.classList.contains("row-checkbox")) return;
        const vis = visibleRowCheckboxes();
        const visChecked = vis.filter((cb) => cb.checked).length;
        selectAll.checked = vis.length > 0 && visChecked === vis.length;
        selectAll.indeterminate = visChecked > 0 && visChecked < vis.length;
        updateConfirmButtonState();
    });
    updateConfirmButtonState();

    // === Validasi input QTY_SPX ===
    document.querySelectorAll('input[name="QTY_SPX"]').forEach((input) => {
        input.addEventListener("focus", function () {
            if (this.value === "0") this.value = "";
        });
        input.addEventListener("input", function () {
            if (this.value === "" || this.value === "-" || this.value === ".")
                return;
            const v = parseFloat(String(this.value).replace(",", "."));
            const maxAllow = parseFloat(this.dataset.max || "0");
            if (!isNaN(v) && v > maxAllow && !isWarningOpen) {
                warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
                pendingResetInput = this;
                isWarningOpen = true;
                warningModal.classList.remove("hidden");
                this.blur();
            }
        });
        input.addEventListener("blur", function () {
            if (this.value.trim() === "") this.value = "0";
            let v = parseFloat(this.value.replace(",", ".") || "0");
            if (isNaN(v)) v = 0;
            const maxAllow = parseFloat(this.dataset.max || "0");
            if (v > maxAllow || v < 0) {
                if (!isWarningOpen) {
                    warningMessage.textContent =
                        v > maxAllow
                            ? `Nilai tidak boleh melebihi batas: ${maxAllow}.`
                            : "Nilai tidak boleh negatif.";
                    pendingResetInput = this;
                    isWarningOpen = true;
                    warningModal.classList.remove("hidden");
                }
                return;
            }
            const u = (this.dataset.meinh || "ST").toUpperCase();
            if (u === "ST" || u === "PC" || u === "PCS" || u === "EA")
                v = Math.floor(v);
            else if (u === "M3") v = Math.round(v * 1000) / 1000;
            this.value = String(v);
        });
    });
    warningOkButton.addEventListener("click", () => {
        if (isResultWarning) {
            goScanWithPernr();
            return;
        }
        if (pendingResetInput) {
            pendingResetInput.value = "0";
            pendingResetInput.focus();
            if (pendingResetInput.select) pendingResetInput.select();
            pendingResetInput = null;
        }
        isWarningOpen = false;
        warningModal.classList.add("hidden");
    });

    // === Validasi & Kirim konfirmasi ===
    confirmButton.addEventListener("click", () => {
        const budatRaw = (budatInput?.value || "").trim();
        if (!budatRaw) {
            warningMessage.textContent = "Posting Date wajib diisi.";
            warningModal.classList.remove("hidden");
            return;
        }
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(budatRaw);
        if (!m) {
            warningMessage.textContent = "Format Posting Date tidak valid.";
            warningModal.classList.remove("hidden");
            return;
        }
        const budatDate = new Date(+m[1], +m[2] - 1, +m[3]);
        budatDate.setHours(0, 0, 0, 0);
        const todayLocal = new Date();
        todayLocal.setHours(0, 0, 0, 0);
        if (budatDate > todayLocal) {
            warningMessage.textContent =
                "Posting Date tidak boleh melebihi hari ini.";
            warningModal.classList.remove("hidden");
            return;
        }

        const items = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        ).map((cb) => {
            const row = cb.closest("tr");
            const qtyInput = row.querySelector('input[name="QTY_SPX"]');
            return {
                row,
                qty: parseFloat(qtyInput.value || "0"),
                max: parseFloat(qtyInput.dataset.max || "0"),
                aufnr: row.dataset.aufnr,
                arbpl0: row.dataset.arbpl0,
                maktx: row.dataset.maktx,
            };
        });

        const counts = items.reduce(
            (acc, it) => ((acc[it.aufnr] = (acc[it.aufnr] || 0) + 1), acc),
            {}
        );
        const duplicates = Object.entries(counts).filter(([, n]) => n > 1);
        if (duplicates.length) {
            warningMessage.innerHTML = `Tidak dapat mengonfirmasi beberapa baris untuk PRO (AUFNR) yang sama secara bersamaan.<br><br>Duplikat terpilih:<ul class="list-disc pl-5 mt-1">${duplicates
                .map(
                    ([a, n]) =>
                        `<li><span class="font-mono">${a}</span> &times; ${n} baris</li>`
                )
                .join(
                    ""
                )}</ul><br>Silakan konfirmasi satu per satu untuk setiap PRO.`;
            warningModal.classList.remove("hidden");
            return;
        }
        if (items.some((x) => x.qty <= 0)) {
            warningMessage.textContent =
                "Kuantitas konfirmasi harus lebih dari 0.";
            warningModal.classList.remove("hidden");
            return;
        }
        const invalidMax = items.find((x) => x.qty > x.max);
        if (invalidMax) {
            errorMessage.textContent = `Isi kuantitas valid (>0 & ≤ ${invalidMax.max}) untuk semua item yang dipilih.`;
            errorModal.classList.remove("hidden");
            return;
        }

        const confirmationList = document.getElementById("confirmation-list");
        confirmationList.innerHTML = items
            .map(
                (
                    x
                ) => `<li class="flex justify-between items-center text-sm font-semibold text-slate-700">
        <div class="flex-1 pr-3"><span class="font-mono">${x.aufnr}</span> / <span class="font-mono">${x.arbpl0}</span> / ${x.maktx}</div>
        <span class="font-mono">${x.qty}</span></li>`
            )
            .join("");
        document.getElementById("confirm-modal").classList.remove("hidden");
    });

    let pendingShowSuccess = null;
    document.getElementById("yes-button").addEventListener("click", () => {
        const selected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );
        if (!selected.length) {
            document.getElementById("confirm-modal").classList.add("hidden");
            return;
        }

        // Validasi BUDAT (YYYY-MM-DD) tidak melewati hari ini
        const budatRaw = (
            document.getElementById("budat-input")?.value || ""
        ).trim();
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(budatRaw);
        if (!m) {
            const warningModal = document.getElementById("warning-modal");
            const warningMessage = document.getElementById("warning-message");
            warningMessage.textContent =
                "Posting Date wajib & format harus yyyy-mm-dd.";
            warningModal.classList.remove("hidden");
            return;
        }
        const budatDate = new Date(+m[1], +m[2] - 1, +m[3]);
        budatDate.setHours(0, 0, 0, 0);
        const todayLocal = new Date();
        todayLocal.setHours(0, 0, 0, 0);
        if (budatDate > todayLocal) {
            const warningModal = document.getElementById("warning-modal");
            const warningMessage = document.getElementById("warning-message");
            warningMessage.textContent =
                "Posting Date tidak boleh melebihi hari ini.";
            warningModal.classList.remove("hidden");
            return;
        }

        // Siapkan payload untuk endpoint ASYNC
        const pickedBudat = budatRaw.replace(/\D/g, ""); // YYYYMMDD
        const items = selected.map((cb) => {
            const row = cb.closest("tr");
            const qty = parseFloat(
                row.querySelector('input[name="QTY_SPX"]').value || "0"
            );
            return {
                aufnr: row.dataset.aufnr || "",
                vornr: row.dataset.vornr || "",
                pernr: row.dataset.pernr || "", // NIK
                operator_name: row.dataset.sname || null, // Nama operator
                qty_confirm: qty, // Qty konfirmasi (input user)
                qty_pro: parseFloat(row.dataset.qtyspk || "0"), // Qty PRO
                meinh: row.dataset.meinh || "ST",
                arbpl0: row.dataset.arbpl0 || null,
                charg: row.dataset.charg || null,
            };
        });

        // Tutup modal; JANGAN tampilkan loading apapun
        document.getElementById("confirm-modal").classList.add("hidden");

        // Kirim ke queue (biarkan berjalan di background) — gunakan keepalive agar tidak batal saat redirect
        const payload = { budat: pickedBudat, items };
        fetch("/api/yppi019/confirm-async", {
            method: "POST",
            keepalive: true,
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": CSRF,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(payload),
        }).catch(() => {
            /* abaikan error kirim */
        });

        // LANGSUNG balik ke halaman /scan (bawa pernr jika ada)
        window.location.href = (function () {
            const baseScan =
                document.querySelector('meta[name="scan-url"]')?.content ||
                document.getElementById("back-link")?.getAttribute("href") ||
                document
                    .getElementById("scan-again-link")
                    ?.getAttribute("href") ||
                "/scan";
            const pernr =
                new URLSearchParams(location.search).get("pernr") || "";
            return pernr
                ? `${baseScan}?pernr=${encodeURIComponent(pernr)}`
                : baseScan;
        })();
    });

    // === Close handlers ===
    cancelButton.addEventListener("click", () =>
        confirmModal.classList.add("hidden")
    );

    errorOkButton.addEventListener("click", () => {
        if (isResultError) {
            goScanWithPernr();
            return;
        }
        errorModal.classList.add("hidden");
    });

    successOkButton.addEventListener("click", () => {
        goScanWithPernr();
    });
});

// ===== Guard tombol "Ya" agar tidak double-submit =====
(() => {
    function initYesButtonGuard() {
        const modal = document.getElementById("confirm-modal");
        const yesBtn = document.getElementById("yes-button");
        const cancelBtn = document.getElementById("cancel-button");
        if (!modal || !yesBtn) return;

        const originalHTML = yesBtn.innerHTML;

        function disableYes() {
            if (yesBtn.disabled) return;
            yesBtn.disabled = true;
            yesBtn.setAttribute("aria-busy", "true");
            yesBtn.classList.add("opacity-60", "cursor-not-allowed");
            // spinner kecil + label
            yesBtn.innerHTML =
                '<svg class="w-4 h-4 mr-2 inline-block animate-spin" viewBox="0 0 24 24" fill="none">' +
                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>' +
                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>' +
                "</svg>Memproses…";
        }
        function enableYes() {
            yesBtn.disabled = false;
            yesBtn.removeAttribute("aria-busy");
            yesBtn.classList.remove("opacity-60", "cursor-not-allowed");
            yesBtn.innerHTML = originalHTML;
        }

        // 1) Disable segera saat diklik
        yesBtn.addEventListener("click", disableYes, { capture: true });
        // 2) Re-enable jika pengguna batal
        if (cancelBtn) cancelBtn.addEventListener("click", enableYes);
        // 3) Reset setiap kali modal muncul lagi
        const obs = new MutationObserver(() => {
            if (!modal.classList.contains("hidden")) enableYes();
        });
        obs.observe(modal, { attributes: true, attributeFilter: ["class"] });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initYesButtonGuard);
    } else {
        initYesButtonGuard();
    }
})();
