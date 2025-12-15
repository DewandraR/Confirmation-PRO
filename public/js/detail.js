/* public/js/detail.js */
"use strict";

/* =========================
   GLOBALS / META
   ========================= */

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";

function getCurrentSapUser() {
    const el = document.querySelector('meta[name="sap-user"]');
    return (el?.content || "").trim();
}

// contoh: "kmi-u138" -> "KMI-U138"
const CUR_SAP_USER = getCurrentSapUser().toUpperCase();

// daftar SAP user yang TIDAK boleh backdate
const LOCK_BUDAT_USERS = [
    /*"KMI-U138", "KMI-U124"*/
];

// elemen global (dipakai helper di luar DOMContentLoaded)
let errorCopyWiButtonEl = null;

/* =======================
   TIMEOUT HELPERS
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
        return await fetch(url, { ...init, signal: ctl.signal });
    } finally {
        clearTimeout(t);
    }
}

// Helper request POST JSON (with timeout)
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

async function safeJson(res) {
    try {
        return await res.json();
    } catch {
        return {};
    }
}

/* =========================
   HELPERS UMUM
   ========================= */

const nz = (v) => {
    const s = (v ?? "").toString().trim();
    return s === "" || s === "-" ? null : s;
};

const normNik = (v) =>
    String(v || "")
        .trim()
        .replace(/^0+/, "");

const padVornr = (v) => String(parseInt(v || "0", 10)).padStart(4, "0");

// pad item SO ke 6 digit
const padKdpos = (v) => {
    const n = String(v ?? "").trim();
    if (!n) return "";
    const x = parseInt(n, 10);
    return Number.isFinite(x) ? String(x).padStart(6, "0") : n.padStart(6, "0");
};

// tampilkan tanpa leading zero (front-end only)
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

// format menit (angka) → up to 3 desimal, hilangkan nol di akhir
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

/* =========================
   SAP RETURN NORMALIZER
   ========================= */

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

// normalisasi pesan error server (hindari "0") + handle 423
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
        msg = j.error || "Sedang diproses oleh user lain. Coba lagi sebentar.";
    }
    if (!msg || /^\s*0\s*$/.test(String(msg))) {
        msg =
            status >= 500 ? "Kesalahan server. Coba lagi." : "Gagal diproses.";
    }
    return msg;
}

// deteksi kode WI dari pesan error & setup tombol copy
function extractWiCodeFromMessage(msg) {
    if (!msg) return null;
    const m = msg.match(/kode WI\s*"([^"]+)"/i);
    return m ? m[1] : null;
}

function prepareWiCopy(msg) {
    if (!errorCopyWiButtonEl) return;

    const wiCode = extractWiCodeFromMessage(msg);
    if (wiCode) {
        errorCopyWiButtonEl.dataset.wiCode = wiCode;
        errorCopyWiButtonEl.classList.remove("hidden");
    } else {
        errorCopyWiButtonEl.dataset.wiCode = "";
        errorCopyWiButtonEl.classList.add("hidden");
    }
}

/* =========================
   GLOBAL ERROR TRAP
   ========================= */
window.onerror = function (msg, src, line, col) {
    const em = document.getElementById("error-message");
    const mm = document.getElementById("error-modal");
    if (em && mm) {
        em.textContent = `Error JS: ${msg} (${line}:${col})`;
        mm.classList.remove("hidden");
    }
    console.error(msg, src, line, col);
};

/* =========================
   MAIN
   ========================= */
document.addEventListener("DOMContentLoaded", async () => {
    const isMobile = () => window.matchMedia("(max-width: 768px)").matches;

    /* ---------- URL Params ---------- */
    const p = new URLSearchParams(location.search);
    const rawList = p.get("aufnrs") || "";
    const single = p.get("aufnr") || "";

    // MULTI WI SUPPORT
    const wiRaw =
        p.get("wi_codes") || // format baru: wi_codes=code1,code2
        (p.getAll("wi_code") || []).join(" ") || // format lama: ?wi_code=a&wi_code=b
        p.get("wi_code") || // format lama: satu wi_code
        "";

    const WI_CODES = wiRaw
        .split(/[,\s]+/)
        .map((s) => s.trim())
        .filter(Boolean);

    const WI_CODE = WI_CODES[0] || ""; // kompatibel lama

    const IV_PERNR = (p.get("pernr") || "").trim();
    const IV_ARBPL = p.get("arbpl") || "";
    const IV_WERKS = p.get("werks") || "";

    // MODE flags (PENTING: dideklarasikan sebelum dipakai)
    const isWiMode = WI_CODES.length > 0;

    const isWCMode =
        rawList.trim() === "" &&
        single.trim() === "" &&
        !!IV_ARBPL &&
        !isWiMode;

    /* ---------- Helper URL /scan ---------- */
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

    /* ---------- Normalize AUFNR list ---------- */
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

    /* ---------- Elemen UI ---------- */
    const headAUFNR = document.getElementById("headAUFNR");
    const content = document.getElementById("content");
    const loading = document.getElementById("loading");
    const tableBody = document.getElementById("tableBody");
    const totalItems = document.getElementById("totalItems");
    const selectAll = document.getElementById("selectAll");
    const confirmButton = document.getElementById("confirm-button");
    const remarkButton = document.getElementById("remark-button");
    const selectedCountSpan = document.getElementById("selected-count");

    const confirmModal = document.getElementById("confirm-modal");
    const confirmationListEl = document.getElementById("confirmation-list");
    const yesButton = document.getElementById("yes-button");
    const cancelButton = document.getElementById("cancel-button");

    const errorModal = document.getElementById("error-modal");
    const errorMessage = document.getElementById("error-message");
    const errorOkButton = document.getElementById("error-ok-button");
    errorCopyWiButtonEl = document.getElementById("error-copy-wi-button");

    const warningModal = document.getElementById("warning-modal");
    const warningMessage = document.getElementById("warning-message");
    const warningList = document.getElementById("warning-list");
    const warningTitle = document.getElementById("warning-title");
    const warningHeader = document.getElementById("warning-header");
    const warningOkButton = document.getElementById("warning-ok-button");

    const successModal = document.getElementById("success-modal");
    const successList = document.getElementById("success-list");
    const successOkButton = document.getElementById("success-ok-button");

    // Tombol remark hanya WI mode
    if (remarkButton) remarkButton.classList.toggle("hidden", !isWiMode);

    /* ---------- WI mode: wajib NIK ---------- */
    if (isWiMode && !IV_PERNR) {
        const msg = "NIK wajib diisi untuk dokumen WI.";
        if (errorMessage) errorMessage.textContent = msg;
        prepareWiCopy("");

        if (loading) loading.classList.add("hidden");
        if (content) content.classList.remove("hidden");
        if (errorModal) errorModal.classList.remove("hidden");
        return;
    }

    /* ---------- BUDAT controls ---------- */
    const budatInput = document.getElementById("budat-input"); // hidden yyyy-mm-dd
    const budatInputText = document.getElementById("budat-input-text"); // visible dd/mm/yyyy
    const budatOpen = document.getElementById("budat-open");

    const isBudatLocked = LOCK_BUDAT_USERS.includes(CUR_SAP_USER) || isWiMode;

    if (isBudatLocked) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");
        const ymd = `${yyyy}-${mm}-${dd}`;

        if (budatInput) {
            budatInput.value = ymd;
            budatInput.setAttribute("max", ymd);
            budatInput.disabled = true;
        }
        if (budatInputText) {
            budatInputText.value = `${dd}/${mm}/${yyyy}`;
            budatInputText.readOnly = true;
            budatInputText.classList.add("bg-slate-100", "cursor-not-allowed");
        }
        if (budatOpen) {
            budatOpen.disabled = true;
            budatOpen.classList.add("opacity-60", "cursor-not-allowed");
        }
    }

    /* ---------- Search / Filter controls ---------- */
    const searchInput = document.getElementById("searchInput");
    const clearSearch = document.getElementById("clearSearch");
    const shownCountEl = document.getElementById("shownCount");

    // Quick date filter controls
    const fltToday = document.getElementById("fltToday");
    const fltOutgoing = document.getElementById("fltOutgoing");
    const fltPeriod = document.getElementById("fltPeriod");
    const fltAllDate = document.getElementById("fltAllDate");
    const periodPicker = document.getElementById("periodPicker");
    const periodFrom = document.getElementById("periodFrom");
    const periodTo = document.getElementById("periodTo");
    const applyPeriod = document.getElementById("applyPeriod");
    const clearFilterBtn = document.getElementById("clearFilter");

    // tombol yang tidak digunakan
    fltToday?.classList.add("hidden");
    fltPeriod?.classList.add("hidden");
    periodPicker?.classList.add("hidden");

    /* ---------- Header: PERNR/AUFNR/WC/WI ---------- */
    if (headAUFNR) {
        let headText = "-";
        if (isWiMode) headText = WI_CODES.join(", ");
        else if (AUFNRS.length > 0) headText = AUFNRS.join(", ");
        else if (IV_ARBPL) headText = `WC: ${IV_ARBPL}`;

        if (IV_PERNR) headText = `${IV_PERNR} / ${headText}`;
        headAUFNR.textContent = headText.replace(/ \/ -$/, "");
    }

    /* ---------- Flags ---------- */
    let pendingResetInput = null;
    let isWarningOpen = false;
    let isResultWarning = false;
    let isResultError = false;

    // untuk sukses modal: remark = stay, confirm = scan
    let successAction = "scan"; // "scan" | "stay"

    /* ---------- Warning helper ---------- */
    const warning = (msg) => {
        isResultWarning = false;
        if (warningTitle) warningTitle.textContent = "Peringatan";
        if (warningHeader) {
            warningHeader.className =
                "bg-gradient-to-r from-yellow-50 to-yellow-100 px-4 py-3 border-b border-yellow-200";
        }
        warningTitle?.classList.remove("text-red-800");
        warningTitle?.classList.add("text-yellow-800");
        if (warningMessage) {
            warningMessage.innerHTML = msg;
            warningMessage.classList.remove("hidden");
        }
        warningList?.classList.add("hidden");
        warningModal?.classList.remove("hidden");
    };

    function syncTextToHidden() {
        if (!budatInput || !budatInputText) return;
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
        if (!budatInput || !budatInputText) return;
        budatInputText.value = ymdToDmy(budatInput.value);
    }

    budatInputText?.addEventListener("blur", syncTextToHidden);
    budatInputText?.addEventListener("change", syncTextToHidden);
    budatInput?.addEventListener("change", syncHiddenToText);

    budatOpen?.addEventListener("click", (e) => {
        e.preventDefault();
        try {
            budatInput?.showPicker && budatInput.showPicker();
        } catch {}
    });

    syncHiddenToText();

    /* =========================
     FETCH DATA
     ========================= */
    let rowsAll = [];
    let failures = [];

    try {
        if (isWiMode) {
            // MODE WI: support banyak kode WI
            const wiResults = await Promise.allSettled(
                WI_CODES.map(async (code) => {
                    const url = `/api/wi/material?wi_code=${encodeURIComponent(
                        code
                    )}&pernr=${encodeURIComponent(IV_PERNR)}`;

                    const res = await fetchWithTimeout(url, {
                        headers: { Accept: "application/json" },
                    });
                    const json = await safeJson(res);

                    if (!res.ok) {
                        throw new Error(
                            (json && (json.error || json.message)) ||
                                `WI ${code}: HTTP ${res.status}`
                        );
                    }

                    const t = json.T_DATA1;
                    const arr = Array.isArray(t) ? t : t ? [t] : [];
                    // pastikan WI_CODE ada per row
                    return arr.map((o) => ({
                        ...o,
                        WI_CODE: o.WI_CODE || code,
                    }));
                })
            );

            wiResults.forEach((r, idx) => {
                if (r.status === "fulfilled") rowsAll = rowsAll.concat(r.value);
                else
                    failures.push(
                        `WI ${WI_CODES[idx]}: ${
                            r.reason?.message || "gagal diambil"
                        }`
                    );
            });
        } else if (AUFNRS.length > 0) {
            // PRO mode
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
                    const json = await safeJson(res);
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
            // WC mode
            const url = `/api/yppi019/material?arbpl=${encodeURIComponent(
                IV_ARBPL
            )}&werks=${encodeURIComponent(IV_WERKS)}&pernr=${encodeURIComponent(
                IV_PERNR
            )}&auto_sync=0`;

            const res = await fetchWithTimeout(url, {
                headers: { Accept: "application/json" },
            });
            const json = await safeJson(res);
            if (!res.ok)
                throw new Error(
                    json.error || json.message || `HTTP ${res.status}`
                );

            const t = json.T_DATA1;
            rowsAll = Array.isArray(t) ? t : t ? [t] : [];
        }
    } catch (e) {
        const msg =
            e?.name === "AbortError"
                ? "Waktu tunggu klien habis. Silakan coba lagi."
                : e?.message || "Gagal mengambil data";
        if (errorMessage) errorMessage.textContent = msg;
        prepareWiCopy(msg);
        errorModal?.classList.remove("hidden");
    } finally {
        loading?.classList.add("hidden");
        content?.classList.remove("hidden");
    }

    // FRONT-END FILTER: tampilkan hanya NIK yang diinput (khusus WI mode)
    if (isWiMode) {
        const want = normNik(IV_PERNR);
        if (!want) rowsAll = [];
        else {
            rowsAll = rowsAll.filter((r) => {
                const rowPernr = normNik(r.PERNR);
                return rowPernr && rowPernr === want;
            });
        }
    }

    const COLSPAN = isWiMode ? 20 : 19;

    if (!rowsAll.length) {
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="${COLSPAN}" class="px-4 py-8 text-center text-slate-500">Tidak ada data</td></tr>`;
        }
        if (totalItems) totalItems.textContent = "0";
        if (shownCountEl) shownCountEl.textContent = "0";
        return;
    }

    /* =========================
     SORT & RENDER
     ========================= */
    // Urut SSAVD ascending; kosong paling akhir. Tie: AUFNR, lalu VORNR numerik.
    rowsAll.sort((a, b) => {
        const sa = a.SSAVD ? toYYYYMMDD(a.SSAVD) : "99999999";
        const sb = b.SSAVD ? toYYYYMMDD(b.SSAVD) : "99999999";
        if (sa !== sb) return sa.localeCompare(sb);

        if ((a.AUFNR || "") !== (b.AUFNR || ""))
            return (a.AUFNR || "").localeCompare(b.AUFNR || "");
        const va = parseInt(a.VORNRX || a.VORNR || "0", 10) || 0;
        const vb = parseInt(b.VORNRX || b.VORNR || "0", 10) || 0;
        return va - vb;
    });

    if (totalItems) totalItems.textContent = String(rowsAll.length);

    const esc = (s) =>
        String(s ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");

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
            const wcInduk = r.ARBPL0 || r.ARBPL || IV_ARBPL || "-";
            const wcAnakRaw = String(
                r.WC_CHILD || r.child_workcenter || ""
            ).trim();
            const wcAnakView = wcAnakRaw || "No WC group";
            const wcWithDesc = ltxa1 ? `${wcInduk} / ${ltxa1}` : wcInduk;

            // plant
            const dispo = String(r.DISPO || "").toUpperCase();
            const werksRow = String(
                r.WERKS ?? r.PWERK ?? r.PLANT ?? IV_WERKS ?? ""
            ).replace(/^0+/, "");
            const shouldPrefillMax =
                ["WE1", "WE2", "WM1"].includes(dispo) && werksRow === "1000";
            const defaultQty = shouldPrefillMax ? maxAllow : 0;

            // normalisasi SSAVD/SSSLD
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

            const ltimexStr = fmtMinutes(r.LTIMEX);

            // SO/Item
            const kdpos6 = padKdpos(r.KDPOS);
            const kdposView = stripLeadingZeros(r.KDPOS);
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
                ltimexStr,
                r.KDAUF,
                r.KDPOS,
                kdpos6,
                kdposView,
                qtySPK,
                soItem,
                (r.KDAUF || "") + "/" + kdpos6,
                (r.KDAUF || "") + kdpos6,
                wcWithDesc,
            ]
                .map(toKey)
                .join(" ");

            const wiCodeRow = String(r.WI_CODE || "").trim(); // sudah di-inject saat fetch WI

            return `<tr class="odd:bg-white even:bg-slate-50 hover:bg-green-50/40 transition-colors"
        data-aufnr="${esc(r.AUFNR || "")}"
        data-vornr="${esc(vornr)}"
        data-pernr="${esc(r.PERNR || IV_PERNR || "")}"
        data-wi-code="${esc(wiCodeRow)}"
        data-meinh="${esc(r.MEINH || "ST")}"
        data-gstrp="${esc(toYYYYMMDD(r.GSTRP))}"
        data-gltrp="${esc(toYYYYMMDD(r.GLTRP))}"
        data-ssavd="${esc(ssavdYMD)}"
        data-sssld="${esc(sssldYMD)}"
        data-ltimex="${esc(ltimexStr)}"
        data-arbpl0="${esc(wcInduk)}"
        data-wc-induk="${esc(wcInduk)}"
        data-wc-anak="${esc(wcAnakView)}"
        data-ltxa1="${esc(r.LTXA1 || "-")}"
        data-charg="${esc(r.CHARG || "")}"
        data-maktx="${esc(r.MAKTX || "-")}"
        data-qtyspk="${esc(String(qtySPK))}"
        data-sname="${esc(r.SNAME || "")}"
        data-matnrx="${esc(r.MATNRX || "")}"
        data-maktx0="${esc(r.MAKTX0 || "")}"
        data-dispo="${esc(r.DISPO || "")}"
        data-steus="${esc(r.STEUS || "")}"
        data-soitem="${esc(soItem)}"
        data-kdauf="${esc(r.KDAUF || "")}"
        data-kdpos="${esc(r.KDPOS || "")}"
        data-werks="${esc(werksRow || "")}"
        data-search="${esc(searchStr)}">

        <td class="px-3 py-3 text-center sticky left-0 bg-inherit border-r border-slate-200">
          <input type="checkbox" class="row-checkbox w-4 h-4 text-green-600 rounded border-slate-300">
        </td>

        <td class="px-3 py-3 text-center bg-inherit border-r border-slate-200">
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-xs font-bold text-green-700 mx-auto">${
              i + 1
          }</div>
        </td>

        <td class="px-3 py-3 text-sm font-semibold text-slate-900">${esc(
            r.AUFNR || "-"
        )}</td>

        <td class="align-middle">
          <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center text-xs font-bold text-gray-700 mx-auto">
            ${Number.isFinite(qtySPK) ? esc(String(qtySPK)) : "-"}
          </div>
        </td>

        <td class="px-3 py-3 text-sm text-slate-700 text-center">
          <input type="number" name="QTY_SPX"
            class="w-28 px-2 py-1 text-center rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-green-400/40 focus:border-green-500 text-sm font-mono"
            value="${esc(String(defaultQty))}"
            placeholder="${esc(String(defaultQty))}"
            min="0"
            data-max="${esc(String(maxAllow))}"
            data-meinh="${esc(meinh)}"
            step="${meinh === "M3" ? "0.001" : "1"}"
            inputmode="${meinh === "M3" ? "decimal" : "numeric"}"
            title="Maks: ${esc(String(maxAllow))} (sisa SPK=${esc(
                String(sisaSPK)
            )}, sisa SPX=${esc(String(qtySPX))})" />
          <div class="mt-1 text-[11px] text-slate-400">Maks: <b>${esc(
              String(maxAllow)
          )}</b> (${esc(getUnitName(meinh))})</div>
        </td>

        ${
            isWiMode
                ? `<td class="px-3 py-3 text-sm text-slate-700 col-remark">
                <input type="text"
                  class="remark-input w-56 px-2 py-1 rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-500"
                  placeholder="Isi remark..."
                  maxlength="500">
              </td>`
                : ``
        }

        <td class="px-3 py-3 text-sm text-slate-700">${esc(
            ssavdDMY || "-"
        )}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(
            sssldDMY || "-"
        )}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(
            r.MAKTX0 || "-"
        )}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(
            r.MATNRX || "-"
        )}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(r.MAKTX || "-")}</td>

        <td class="px-3 py-3 text-sm text-slate-700">${esc(ltimexStr)}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(
            r.PERNR || IV_PERNR || "-"
        )}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(r.SNAME || "-")}</td>
        <td class="px-3 py-3 text-sm text-slate-700">${esc(r.DISPO || "-")}</td>

        <td class="px-3 py-3 text-sm text-slate-700 col-workcenter">
          ${isWiMode ? esc(wcInduk) : esc(wcWithDesc)}
        </td>

        <td class="px-3 py-3 text-sm text-slate-700 col-wc-anak">
          ${isWiMode ? esc(wcAnakView) : ""}
        </td>

        <td class="px-3 py-3 text-sm text-slate-700">${esc(r.STEUS || "-")}</td>
        <td class="px-3 py-3 text-sm text-slate-700 font-mono whitespace-nowrap">${esc(
            soItem
        )}</td>
      </tr>`;
        })
        .join("");

    // Header WC Induk/WC Anak
    const thWc = document.querySelector("th.col-workcenter");
    const thWcAnak = document.querySelector("th.col-wc-anak");
    const thRemark = document.querySelector("th.col-remark");

    if (isWiMode) {
        if (thWc) thWc.textContent = "WC Induk";
        if (thWcAnak) thWcAnak.classList.remove("hidden");
        document
            .querySelectorAll("td.col-wc-anak")
            .forEach((el) => (el.style.display = ""));
        // remark header
        if (thRemark) thRemark.classList.remove("hidden");
    } else {
        if (thWc) thWc.textContent = "Work Center";
        if (thWcAnak) thWcAnak.classList.add("hidden");
        document
            .querySelectorAll("td.col-wc-anak")
            .forEach((el) => (el.style.display = "none"));
        // remark header
        if (thRemark) thRemark.classList.add("hidden");
        document
            .querySelectorAll("td.col-remark")
            .forEach((el) => (el.style.display = "none"));
    }

    // WC mode: tampilkan " / LTXA1" di header (sebelah WC)
    if (isWCMode && headAUFNR) {
        const getOpDesc = (r) =>
            String(
                r.LTXA1 ?? r.ltxa1 ?? r.OPDESC ?? r.OPR_TXT ?? r.LTXA1X ?? ""
            ).trim();
        const uniqueDesc = [...new Set(rowsAll.map(getOpDesc).filter(Boolean))];
        if (uniqueDesc.length) {
            const first = uniqueDesc[0];
            const more =
                uniqueDesc.length > 1
                    ? ` (+${uniqueDesc.length - 1} lainnya)`
                    : "";
            const nikPrefix = IV_PERNR ? `${IV_PERNR} / ` : "";
            headAUFNR.textContent = `${nikPrefix}WC: ${IV_ARBPL} / ${first}${more}`;
        }
    }

    // WC mode: sembunyikan kolom Work Center
    if (isWCMode) {
        document
            .querySelectorAll(
                ".col-workcenter, .col-workcenter-desc, .col-wc-anak"
            )
            .forEach((el) => {
                el.style.display = "none";
            });
    }

    /* =========================
     SEARCH + DATE FILTER
     ========================= */
    const normTxt = (s) => String(s || "").toLowerCase();

    let dateFilterMode = "none"; // 'none' | 'today' | 'outgoing' | 'period'
    let qCurrent = "";
    let pfYMD = "";
    let ptYMD = "";

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
            return ss === t;
        }

        if (dateFilterMode === "outgoing") {
            const t = ymdToday();
            const s = ss || t;
            const e = ee || t;
            return s <= t && t <= e;
        }

        if (dateFilterMode === "period") {
            if (!pfYMD || !ptYMD) return true;
            const s = ss || "00000000";
            const e = ee || "99991231";
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

        if (remarkButton) {
            remarkButton.disabled = count === 0;
            // kalau non-WI mode, tetap hidden
            remarkButton.classList.toggle("hidden", !isWiMode);
        }
    }

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
        if (shownCountEl) shownCountEl.textContent = shown;

        const vis = visibleRowCheckboxes();
        const visChecked = vis.filter((cb) => cb.checked).length;
        if (selectAll) {
            selectAll.checked = vis.length > 0 && visChecked === vis.length;
            selectAll.indeterminate = visChecked > 0 && visChecked < vis.length;
        }
        updateConfirmButtonState();
    }

    function filterRows(q) {
        qCurrent = normTxt(q).trim();
        applyFilters();
    }

    // search input
    filterRows("");
    searchInput?.addEventListener("input", () => {
        const q = searchInput.value;
        filterRows(q);
        clearSearch?.classList.toggle("hidden", q.length === 0);
    });
    clearSearch?.addEventListener("click", () => {
        searchInput.value = "";
        filterRows("");
        clearSearch.classList.add("hidden");
        searchInput.focus();
    });

    // Default quick filter
    if (isWCMode) {
        dateFilterMode = "outgoing";
        setActive(fltOutgoing);
        periodPicker?.classList.add("hidden");
    } else {
        dateFilterMode = "none";
        setActive(fltAllDate);
        periodPicker?.classList.add("hidden");
    }
    applyFilters();

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

    fltOutgoing?.addEventListener("click", () => {
        dateFilterMode = "outgoing";
        setActive(fltOutgoing);
        periodPicker?.classList.add("hidden");
        applyFilters();
    });

    fltAllDate?.addEventListener("click", () => {
        dateFilterMode = "none";
        pfYMD = "";
        ptYMD = "";
        setActive(fltAllDate);
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

    clearFilterBtn?.addEventListener("click", () => {
        dateFilterMode = "none";
        pfYMD = "";
        ptYMD = "";
        setActive(fltAllDate || null);
        periodPicker?.classList.add("hidden");
        applyFilters();
    });

    /* =========================
     CHECKBOX HANDLING
     ========================= */
    selectAll?.addEventListener("change", () => {
        const vis = visibleRowCheckboxes();
        vis.forEach((cb) => (cb.checked = selectAll.checked));
        updateConfirmButtonState();
    });

    document.addEventListener("change", (e) => {
        if (!e.target.classList.contains("row-checkbox")) return;
        const vis = visibleRowCheckboxes();
        const visChecked = vis.filter((cb) => cb.checked).length;
        if (selectAll) {
            selectAll.checked = vis.length > 0 && visChecked === vis.length;
            selectAll.indeterminate = visChecked > 0 && visChecked < vis.length;
        }
        updateConfirmButtonState();
    });

    updateConfirmButtonState();

    /* =========================
     VALIDASI QTY INPUT
     ========================= */
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
                if (warningMessage)
                    warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
                pendingResetInput = this;
                isWarningOpen = true;
                warningModal?.classList.remove("hidden");
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
                    if (warningMessage)
                        warningMessage.textContent =
                            v > maxAllow
                                ? `Nilai tidak boleh melebihi batas: ${maxAllow}.`
                                : "Nilai tidak boleh negatif.";
                    pendingResetInput = this;
                    isWarningOpen = true;
                    warningModal?.classList.remove("hidden");
                }
                return;
            }

            const u = (this.dataset.meinh || "ST").toUpperCase();
            if (["ST", "PC", "PCS", "EA"].includes(u)) v = Math.floor(v);
            else if (u === "M3") v = Math.round(v * 1000) / 1000;

            this.value = String(v);
        });
    });

    warningOkButton?.addEventListener("click", () => {
        if (isResultWarning) {
            goScanWithPernr();
            return;
        }
        if (pendingResetInput) {
            pendingResetInput.value = "0";
            pendingResetInput.focus();
            pendingResetInput.select && pendingResetInput.select();
            pendingResetInput = null;
        }
        isWarningOpen = false;
        warningModal?.classList.add("hidden");
    });

    /* =========================
     REMARK FLOW (WI MODE ONLY)
     ========================= */
    let remarkBusy = false;

    remarkButton?.addEventListener("click", async () => {
        if (!isWiMode || remarkBusy) return;

        const selected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );
        if (!selected.length) return;

        // validasi wajib remark + qty > 0
        const items = [];
        const bad = [];

        selected.forEach((cb) => {
            const row = cb.closest("tr");
            if (!row) return;

            const qtyInput = row.querySelector('input[name="QTY_SPX"]');
            const remarkInput = row.querySelector(".remark-input");

            const remarkQty = parseFloat(qtyInput?.value || "0");
            const remark = (remarkInput?.value || "").trim();

            if (!remark || !(remarkQty > 0)) bad.push(row.dataset.aufnr || "-");

            items.push({
                wi_code: row.dataset.wiCode || "",
                aufnr: row.dataset.aufnr || "",
                vornr: row.dataset.vornr || "",
                nik: normNik(row.dataset.pernr || ""),

                operator_name: (row.dataset.sname || "").trim() || null,
                qty_pro: parseFloat(row.dataset.qtyspk || "0"),
                meinh: row.dataset.meinh || "ST",
                remark,
                remark_qty: remarkQty,

                // tambahan biar DB nggak kosong
                werks: row.dataset.werks || null,
                arbpl0: row.dataset.arbpl0 || null,
                matnrx: row.dataset.matnrx || null,
                maktx: row.dataset.maktx || null,
                maktx0: row.dataset.maktx0 || null,
                dispo: row.dataset.dispo || null,
                steus: row.dataset.steus || null,
                soitem: row.dataset.soitem || null,
                charg: row.dataset.charg || null,
                ssavd: row.dataset.ssavd || null,
                sssld: row.dataset.sssld || null,
                ltimex: row.dataset.ltimex || null,
            });
        });

        if (bad.length) {
            if (warningMessage)
                warningMessage.textContent =
                    "Remark wajib diisi dan Qty Input harus > 0 untuk semua item yang dipilih.";
            warningModal?.classList.remove("hidden");
            return;
        }

        // Kirim ke API baru (tetap di halaman detail)
        remarkBusy = true;
        remarkButton.disabled = true;
        remarkButton.classList.add("opacity-60", "cursor-not-allowed");

        try {
            const res = await apiPost("/api/yppi019/remark-async", { items });
            const json = await safeJson(res);

            if (res.status === 202 && Array.isArray(json.queued)) {
                successAction = "stay";
                if (successList) {
                    successList.innerHTML = `
                <li class="text-sm text-slate-700">
                    Remark masuk antrian: <b>${json.queued.length}</b> item.
                </li>`;
                }
                successModal?.classList.remove("hidden");

                // uncheck + clear remark
                selected.forEach((cb) => {
                    cb.checked = false;
                    const row = cb.closest("tr");
                    const ri = row?.querySelector(".remark-input");
                    if (ri) ri.value = "";
                });
                updateConfirmButtonState();

                return; // ✅ penting: stop di sini
            }

            // kalau bukan 202, anggap gagal
            const msg =
                json?.message ||
                json?.error ||
                (res.status === 422
                    ? "Validasi gagal (cek field wajib)."
                    : "") ||
                `Gagal mengirim remark. (HTTP ${res.status})`;

            if (errorMessage) errorMessage.textContent = msg;
            prepareWiCopy(msg);
            errorModal?.classList.remove("hidden");
            return;
        } catch (e) {
            const msg =
                e?.name === "AbortError"
                    ? "Waktu tunggu habis. Coba lagi."
                    : e?.message || "Gagal mengirim remark.";
            if (errorMessage) errorMessage.textContent = msg;
            prepareWiCopy(msg);
            errorModal?.classList.remove("hidden");
        } finally {
            remarkBusy = false;
            remarkButton.disabled = false;
            remarkButton.classList.remove("opacity-60", "cursor-not-allowed");
        }
    });

    /* =========================
     MOBILE ROW DETAIL POPUP
     ========================= */
    const rowDetailModal = document.getElementById("row-detail-modal");
    const rowDetailBody = document.getElementById("row-detail-body");
    const rowDetailClose = document.getElementById("row-detail-close");
    const rowDetailCancel = document.getElementById("row-detail-cancel");
    const rowDetailSave = document.getElementById("row-detail-save");
    const rowDetailSelect = document.getElementById("row-detail-select");

    let currentRow = null;

    function ymd8ToDMY(ymd8) {
        return ymd8 && /^\d{8}$/.test(ymd8)
            ? `${ymd8.slice(6, 8)}/${ymd8.slice(4, 6)}/${ymd8.slice(0, 4)}`
            : "-";
    }

    function openRowDetail(tr) {
        currentRow = tr;

        const qtyInput = tr.querySelector('input[name="QTY_SPX"]');
        const cb = tr.querySelector(".row-checkbox");
        const rowRemarkVal = (
            tr.querySelector(".remark-input")?.value || ""
        ).trim();

        const data = {
            aufnr: tr.dataset.aufnr || "-",
            vornr: tr.dataset.vornr || "-",
            wc: tr.dataset.arbpl0 || "-",
            wcInduk: tr.dataset.wcInduk || tr.dataset.arbpl0 || "-",
            wcAnak: tr.dataset.wcAnak || "",
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
            ssavd: ymd8ToDMY((tr.dataset.ssavd || "").replace(/\D/g, "")),
            sssld: ymd8ToDMY((tr.dataset.sssld || "").replace(/\D/g, "")),
            qtyspk: tr.dataset.qtyspk || "0",
            meinh: tr.dataset.meinh || "ST",
            qtycur: qtyInput ? qtyInput.value || "0" : "0",
            max: qtyInput ? qtyInput.dataset.max || "0" : "0",
            checked: cb ? cb.checked : false,
            remark: rowRemarkVal,
        };

        const unitNamePopup = getUnitName(data.meinh);

        const wcBlock = isWiMode
            ? `
        <div>
          <div class="text-[11px] text-slate-500">WC Induk</div>
          <div class="font-semibold">${esc(data.wcInduk)}</div>
        </div>
        <div>
          <div class="text-[11px] text-slate-500">WC Anak</div>
          <div class="font-semibold">${esc(data.wcAnak || "No WC group")}</div>
        </div>
      `
            : `
        <div>
          <div class="text-[11px] text-slate-500">Work Center</div>
          <div class="font-semibold">${esc(
              data.wc + (data.ltxa1 ? " / " + data.ltxa1 : "")
          )}</div>
        </div>
      `;

        rowDetailBody.innerHTML = `
      <div class="grid grid-cols-2 gap-3">
        <div>
          <div class="text-[11px] text-slate-500">PRO</div>
          <div class="font-semibold font-mono">${esc(data.aufnr)}</div>
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

        ${wcBlock}

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
        <label class="text-[11px] text-slate-500 block mb-1">Qty Input (${esc(
            unitNamePopup
        )})</label>
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
        <div class="mt-1 text-[11px] text-slate-500">Maks: <b>${esc(
            data.max
        )}</b> (${esc(unitNamePopup)})</div>
      </div>

      ${
          isWiMode
              ? `
        <div class="mt-3">
          <label class="text-[11px] text-slate-500 block mb-1">Remark</label>
          <input id="row-detail-remark" type="text"
            class="w-full px-3 py-2 rounded-md border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-500"
            placeholder="Isi remark..."
            maxlength="500"
            value="${esc(data.remark)}">
        </div>
      `
              : ``
      }
    `;

        const modalQty = document.getElementById("row-detail-qty");
        const maxAllow = parseFloat(modalQty?.dataset.max || "0") || 0;
        const unit = String(modalQty?.dataset.meinh || "ST").toUpperCase();

        // Auto-clear "0" saat fokus
        modalQty?.addEventListener("focus", function () {
            if (this.value === "0") this.value = "";
            this.select && this.value && this.select();
        });

        // Validasi realtime: jangan > max
        modalQty?.addEventListener("input", function () {
            if (this.value === "" || this.value === "-" || this.value === ".")
                return;
            const v = parseFloat(String(this.value).replace(",", "."));
            if (!isNaN(v) && v > maxAllow && !isWarningOpen) {
                if (warningMessage)
                    warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
                pendingResetInput = this;
                isWarningOpen = true;
                warningModal?.classList.remove("hidden");
                this.blur();
            }
        });

        // Normalisasi saat blur
        modalQty?.addEventListener("blur", function () {
            if (this.value.trim() === "") this.value = "0";
            let v = parseFloat(String(this.value).replace(",", "."));
            if (isNaN(v) || v < 0) v = 0;
            if (["ST", "PC", "PCS", "EA"].includes(unit)) v = Math.floor(v);
            else if (unit === "M3") v = Math.round(v * 1000) / 1000;
            this.value = String(v);
        });

        modalQty?.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                rowDetailSave?.click();
            }
        });

        // label tombol pilih
        if (rowDetailSelect) {
            rowDetailSelect.textContent = data.checked
                ? "Batalkan Pilih"
                : "Pilih Item Ini";
        }

        rowDetailModal?.classList.remove("hidden");
    }

    function closeRowDetail() {
        rowDetailModal?.classList.add("hidden");
        currentRow = null;
    }

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

    [rowDetailClose, rowDetailCancel].forEach((btn) =>
        btn?.addEventListener("click", closeRowDetail)
    );

    rowDetailSelect?.addEventListener("click", () => {
        if (!currentRow) return;
        const cb = currentRow.querySelector(".row-checkbox");
        if (!cb) return;
        cb.checked = !cb.checked;

        const vis = visibleRowCheckboxes();
        const visChecked = vis.filter((x) => x.checked).length;
        if (selectAll) {
            selectAll.checked = vis.length > 0 && visChecked === vis.length;
            selectAll.indeterminate = visChecked > 0 && visChecked < vis.length;
        }
        updateConfirmButtonState();

        rowDetailSelect.textContent = cb.checked
            ? "Batalkan Pilih"
            : "Pilih Item Ini";
    });

    rowDetailSave?.addEventListener("click", () => {
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

        if (["ST", "PC", "PCS", "EA"].includes(u)) v = Math.floor(v);
        else if (u === "M3") v = Math.round(v * 1000) / 1000;

        if (v <= 0) {
            if (warningMessage)
                warningMessage.textContent = "Kuantitas harus lebih dari 0.";
            isWarningOpen = true;
            pendingResetInput = inputModal;
            warningModal?.classList.remove("hidden");
            return;
        }
        if (v > maxAllow) {
            if (warningMessage)
                warningMessage.textContent = `Nilai tidak boleh melebihi batas: ${maxAllow}.`;
            isWarningOpen = true;
            pendingResetInput = inputModal;
            warningModal?.classList.remove("hidden");
            return;
        }

        // salin qty
        rowInput.value = String(v);

        // salin remark (WI mode)
        if (isWiMode) {
            const modalRemark = document.getElementById("row-detail-remark");
            const rowRemark = currentRow.querySelector(".remark-input");
            if (modalRemark && rowRemark)
                rowRemark.value = modalRemark.value.trim();
        }

        closeRowDetail();
    });

    /* =========================
     CONFIRM FLOW (tetap seperti sebelumnya)
     ========================= */
    confirmButton?.addEventListener("click", () => {
        const budatRaw = (budatInput?.value || "").trim();
        if (!budatRaw) {
            if (warningMessage)
                warningMessage.textContent = "Posting Date wajib diisi.";
            warningModal?.classList.remove("hidden");
            return;
        }
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(budatRaw);
        if (!m) {
            if (warningMessage)
                warningMessage.textContent = "Format Posting Date tidak valid.";
            warningModal?.classList.remove("hidden");
            return;
        }
        const budatDate = new Date(+m[1], +m[2] - 1, +m[3]);
        budatDate.setHours(0, 0, 0, 0);
        const todayLocal = new Date();
        todayLocal.setHours(0, 0, 0, 0);
        if (budatDate > todayLocal) {
            if (warningMessage)
                warningMessage.textContent =
                    "Posting Date tidak boleh melebihi hari ini.";
            warningModal?.classList.remove("hidden");
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

        // aturan lama: larang confirm banyak baris untuk AUFNR sama
        const counts = items.reduce(
            (acc, it) => ((acc[it.aufnr] = (acc[it.aufnr] || 0) + 1), acc),
            {}
        );
        const duplicates = Object.entries(counts).filter(([, n]) => n > 1);
        if (duplicates.length) {
            if (warningMessage)
                warningMessage.innerHTML = `Tidak dapat mengonfirmasi beberapa baris untuk PRO (AUFNR) yang sama secara bersamaan.<br><br>Duplikat terpilih:<ul class="list-disc pl-5 mt-1">${duplicates
                    .map(
                        ([a, n]) =>
                            `<li><span class="font-mono">${esc(
                                a
                            )}</span> &times; ${esc(String(n))} baris</li>`
                    )
                    .join(
                        ""
                    )}</ul><br>Silakan konfirmasi satu per satu untuk setiap PRO.`;
            warningModal?.classList.remove("hidden");
            return;
        }

        if (items.some((x) => x.qty <= 0)) {
            if (warningMessage)
                warningMessage.textContent =
                    "Kuantitas konfirmasi harus lebih dari 0.";
            warningModal?.classList.remove("hidden");
            return;
        }

        const invalidMax = items.find((x) => x.qty > x.max);
        if (invalidMax) {
            const msg = `Isi kuantitas valid (>0 & ≤ ${invalidMax.max}) untuk semua item yang dipilih.`;
            if (errorMessage) errorMessage.textContent = msg;
            prepareWiCopy(msg);
            errorModal?.classList.remove("hidden");
            return;
        }

        if (confirmationListEl) {
            confirmationListEl.innerHTML = items
                .map(
                    (
                        x
                    ) => `<li class="flex justify-between items-center text-sm font-semibold text-slate-700">
            <div class="flex-1 pr-3"><span class="font-mono">${esc(
                x.aufnr
            )}</span> / <span class="font-mono">${esc(x.arbpl0)}</span> / ${esc(
                        x.maktx
                    )}</div>
            <span class="font-mono">${esc(String(x.qty))}</span>
          </li>`
                )
                .join("");
        }

        confirmModal?.classList.remove("hidden");
    });

    yesButton?.addEventListener("click", () => {
        const selected = Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        );
        if (!selected.length) {
            confirmModal?.classList.add("hidden");
            return;
        }

        // Validasi budat
        const budatRaw = (budatInput?.value || "").trim();
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(budatRaw);
        if (!m) {
            if (warningMessage)
                warningMessage.textContent =
                    "Posting Date wajib & format harus yyyy-mm-dd.";
            warningModal?.classList.remove("hidden");
            return;
        }
        const budatDate = new Date(+m[1], +m[2] - 1, +m[3]);
        budatDate.setHours(0, 0, 0, 0);
        const todayLocal = new Date();
        todayLocal.setHours(0, 0, 0, 0);
        if (budatDate > todayLocal) {
            if (warningMessage)
                warningMessage.textContent =
                    "Posting Date tidak boleh melebihi hari ini.";
            warningModal?.classList.remove("hidden");
            return;
        }

        const pickedBudat = budatRaw.replace(/\D/g, "");

        const items = selected.map((cb) => {
            const row = cb.closest("tr");
            const qty = parseFloat(
                row.querySelector('input[name="QTY_SPX"]').value || "0"
            );

            const ssavd = (row.dataset.ssavd || "").replace(/\D/g, "") || null;
            const sssld = (row.dataset.sssld || "").replace(/\D/g, "") || null;

            return {
                aufnr: row.dataset.aufnr || "",
                vornr: row.dataset.vornr || "",
                wi_code: nz(row.dataset.wiCode) || null,
                pernr: row.dataset.pernr || "",
                operator_name: row.dataset.sname || null,
                qty_confirm: qty,
                qty_pro: parseFloat(row.dataset.qtyspk || "0"),
                meinh: row.dataset.meinh || "ST",
                arbpl0: nz(row.dataset.arbpl0),
                charg: row.dataset.charg || null,

                werks: row.dataset.werks || null,
                ltxa1: nz(row.dataset.ltxa1),
                matnrx: row.dataset.matnrx || null,
                maktx: nz(row.dataset.maktx),
                maktx0: nz(row.dataset.maktx0),
                dispo: row.dataset.dispo || null,
                steus: row.dataset.steus || null,

                soitem: row.dataset.soitem || null,
                kaufn: row.dataset.kdauf || null,
                kdpos: row.dataset.kdpos || null,

                ssavd,
                sssld,
                ltimex: (() => {
                    const v = nz(row.dataset.ltimex);
                    return v == null
                        ? null
                        : parseFloat(String(v).replace(",", "."));
                })(),

                gstrp: (row.dataset.gstrp || "").replace(/\D/g, "") || null,
                gltrp: (row.dataset.gltrp || "").replace(/\D/g, "") || null,
            };
        });

        confirmModal?.classList.add("hidden");

        const payload = {
            budat: pickedBudat,
            wi_code: WI_CODES.length === 1 ? WI_CODE : null,
            items,
        };

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
        }).catch(() => {});

        // redirect scan setelah confirm
        goScanWithPernr();
    });

    /* =========================
     CLOSE HANDLERS
     ========================= */
    cancelButton?.addEventListener("click", () =>
        confirmModal?.classList.add("hidden")
    );

    errorOkButton?.addEventListener("click", () => {
        if (isResultError) {
            goScanWithPernr();
            return;
        }
        errorModal?.classList.add("hidden");
    });

    errorCopyWiButtonEl?.addEventListener("click", async () => {
        const wiCode = errorCopyWiButtonEl.dataset.wiCode || "";
        if (!wiCode) return;
        try {
            await navigator.clipboard.writeText(wiCode);
            const oldText = errorCopyWiButtonEl.textContent;
            errorCopyWiButtonEl.textContent = "Tersalin";
            setTimeout(() => (errorCopyWiButtonEl.textContent = oldText), 1500);
        } catch {
            alert("Gagal menyalin kode WI. Silakan salin manual.");
        }
    });

    successOkButton?.addEventListener("click", () => {
        if (successAction === "stay") {
            successModal?.classList.add("hidden");
            return;
        }
        goScanWithPernr();
    });

    /* =========================
     GUARD "YA" (anti double submit)
     ========================= */
    (function initYesButtonGuard() {
        const modal = document.getElementById("confirm-modal");
        const yesBtn = document.getElementById("yes-button");
        const cancelBtn2 = document.getElementById("cancel-button");
        if (!modal || !yesBtn) return;

        const originalHTML = yesBtn.innerHTML;

        function disableYes() {
            if (yesBtn.disabled) return;
            yesBtn.disabled = true;
            yesBtn.setAttribute("aria-busy", "true");
            yesBtn.classList.add("opacity-60", "cursor-not-allowed");
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

        yesBtn.addEventListener("click", disableYes, { capture: true });
        cancelBtn2?.addEventListener("click", enableYes);

        const obs = new MutationObserver(() => {
            if (!modal.classList.contains("hidden")) enableYes();
        });
        obs.observe(modal, { attributes: true, attributeFilter: ["class"] });
    })();
});
