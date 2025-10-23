"""
PENTING
php artisan queue:table
php artisan queue:failed-table
php artisan queue:work --queue=default -v
php artisan queue:restart
"""
# yppi019.py — FINAL (per-AUFNR advisory lock) + FIX "Unread result found" + KDAUF/KDPOS + LTIME/LTIMEX (minutes)
import os, re, json, logging, decimal, datetime, base64
from typing import Any, Dict, List, Optional, Tuple

from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import mysql.connector
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError
from flask import jsonify, request

load_dotenv()
app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/": {"origins": ""}})

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[logging.FileHandler("yppi019_service.log"), logging.StreamHandler()],
)
logger = logging.getLogger(__name__)

HTTP_HOST = os.getenv("HTTP_HOST", "127.0.0.1")
HTTP_PORT = int(os.getenv("HTTP_PORT", "5036"))
RFC_Y = "Z_FM_YPPI019"      # READ
RFC_C = "Z_RFC_CONFIRMASI"  # CONFIRM
RFC_H = "Z_FM_YPPR062"      # HASIL (baru)

CONFIRM_LOG_KEEP_DAYS = int(os.getenv("CONFIRM_LOG_KEEP_DAYS", "0"))

# ---------------- MySQL ----------------
def connect_mysql():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "yppi019"),
        port=int(os.getenv("DB_PORT", "3306")),
    )

# -------- Advisory lock helpers (connection-scoped) --------
def _fetch_scalar(cur):
    row = cur.fetchone()
    if row is None:
        val = None
    else:
        val = next(iter(row.values())) if isinstance(row, dict) else row[0]

    # Drain sisa baris (kalau ada) + sisa result set (kalau ada)
    try:
        cur.fetchall()
    except Exception:
        pass
    try:
        while cur.nextset():
            cur.fetchall()
    except Exception:
        pass
    return val


def acquire_mutex(cur, key: str, timeout: int = 8):
    """Ambil mutex bernama yppi019:<key>. Timeout detik."""
    cur.execute("SELECT GET_LOCK(%s, %s)", (f"yppi019:{key}", timeout))
    got = _fetch_scalar(cur)
    try:
        got = int(got)
    except (TypeError, ValueError):
        got = 0
    if got != 1:
        raise RuntimeError(f"Resource busy for key={key}")

def release_mutex(cur, key: str):
    """Lepas mutex bernama yppi019:<key> (idempotent)."""
    try:
        cur.execute("SELECT RELEASE_LOCK(%s)", (f"yppi019:{key}",))
        _fetch_scalar(cur)  # consume result; cegah "Unread result found"
    except Exception:
        pass

# ---------------- HTTP helper: 423 Locked (baru) ----------------
def locked_response(resource_type: str, resource_id: str, retry_after_sec: int = 2):
    """
    Kembalikan 423 Locked dengan pesan ramah saat resource sedang dikunci.
    resource_type: mis. "AUFNR"
    resource_id  : mis. "000001234567"
    """
    payload = {
        "ok": False,
        "error": "Sedang diproses oleh user lain. Coba lagi sebentar.",
        "error_code": "AUFNR_LOCKED",
        "resource": {"type": resource_type, "id": resource_id},
    }
    resp = jsonify(payload)
    resp.status_code = 423  # Locked
    resp.headers["Retry-After"] = str(retry_after_sec)
    return resp

# ---------------- SAP ----------------
def connect_sap(username: Optional[str] = None, password: Optional[str] = None) -> Connection:
    user = (username or "").strip()
    passwd = (password or "").strip()
    if not user or not passwd:
        raise ValueError("Missing SAP username/password (must be sent via headers or JSON).")
    ashost = (os.getenv("SAP_ASHOST", "192.168.254.154") or "").strip()
    sysnr  = (os.getenv("SAP_SYSNR", "01") or "").strip()
    client = (os.getenv("SAP_CLIENT", "300") or "").strip()
    lang   = (os.getenv("SAP_LANG", "EN") or "").strip()
    missing = [k for k, v in {"SAP_ASHOST": ashost, "SAP_SYSNR": sysnr, "SAP_CLIENT": client, "SAP_LANG": lang}.items() if not v]
    if missing:
        raise ValueError(f"SAP connection fields empty: {', '.join(missing)}. Check .env")
    logger.info("SAP connect -> ashost=%s sysnr=%s client=%s lang=%s user=%s", ashost, sysnr, client, lang, user)
    return Connection(user=user, passwd=passwd, ashost=ashost, sysnr=sysnr, client=client, lang=lang)

# ---------------- Kredensial helper ----------------
def _try_basic_auth_header() -> Optional[Tuple[str, str]]:
    
    auth = request.headers.get("Authorization") or ""
    if auth.startswith("Basic "):
        try:
            decoded = base64.b64decode(auth.split(" ", 1)[1]).decode("utf-8", "ignore")
            if ":" in decoded:
                u, p = decoded.split(":", 1)
                u, p = (u or "").strip(), (p or "").strip()
                if u and p: return u, p
        except Exception: pass
    return None

def get_credentials_from_request() -> Tuple[str, str]:
    u = (request.headers.get("X-SAP-Username") or "").strip()
    p = (request.headers.get("X-SAP-Password") or "").strip()
    if u and p: return u, p
    basic = _try_basic_auth_header()
    if basic: return basic
    try:
        if request.is_json:
            body = request.get_json(silent=True) or {}
            u2 = (body.get("username") or body.get("sap_id") or "").strip()
            p2 = (body.get("password") or "").strip()
            if u2 and p2: return u2, p2
    except Exception: pass
    raise ValueError("SAP credentials not found (headers or JSON).")

# ---------------- Utils ----------------
def parse_num(x: Any) -> Optional[float]:
    if x is None or x == "": return None
    if isinstance(x, (int, float, decimal.Decimal)): return float(x)
    s = str(x).strip()
    if s.count(",") > 0 and s.count(".") == 0: s = s.replace(".", "").replace(",", ".")
    else: s = s.replace(",", "")
    try: return float(s)
    except Exception: return None

def parse_date(v: Any) -> Optional[str]:
    if not v: return None
    if isinstance(v, (datetime.date, datetime.datetime)): return v.strftime("%d-%m-%Y")
    s = str(v).strip()
    if m := re.match(r"^(\d{2})\.(\d{2})\.(\d{4})$", s): return f"{m.groups()[2]}-{m.groups()[1]}-{m.groups()[0]}"
    if m2 := re.match(r"^(\d{4})(\d{2})(\d{2})$", s): return f"{m2.groups()[0]}-{m2.groups()[1]}-{m2.groups()[2]}"
    return s

def pad_vornr(v: Any) -> str:
    s = str(v or "").strip()
    if not s: return ""
    try: return f"{int(float(s)):04d}"
    except Exception: return s.zfill(4)

def pad_aufnr(v: Any) -> str:
    s = re.sub(r"\D", "", str(v or ""))
    return s.zfill(12) if s else ""

# --- NEW: zero-pad item SO 6 digit (000010, 000020, ...) ---
def pad_kdpos(v: Any) -> str:
    s = str(v or "").strip()
    if not s:
        return ""
    try:
        return f"{int(float(s)):06d}"
    except Exception:
        return s.zfill(6)

def to_jsonable(o: Any) -> Any:
    if isinstance(o, (str, int, float, bool, type(None))): return o
    if isinstance(o, decimal.Decimal): return float(o)
    if isinstance(o, (datetime.date, datetime.datetime)): return o.isoformat()
    if isinstance(o, dict): return {k: to_jsonable(v) for k, v in o.items()}
    if isinstance(o, (list, tuple, set)): return [to_jsonable(x) for x in o]
    return str(o)

def normalize_uom(meinh: Any) -> str:
    u = str(meinh or "").strip().upper()
    return "PC" if u == "ST" else u

def humanize_rfc_error(err: Exception) -> str:
    s = str(err or "")
    m = re.search(r'message=([^[]+)', s)
    if m:
        return m.group(1).strip()
    m2 = re.search(r'MESSAGE\s*[:=]\s*([^\n]+)', s, flags=re.IGNORECASE)
    if m2:
        return m2.group(1).strip()
    return s.strip()

# ---------------- DDL & Persist ----------------
def ensure_tables():
    with connect_mysql() as db:
        with db.cursor() as cur:
            cur.execute("""
            CREATE TABLE IF NOT EXISTS yppi019_data (
              id BIGINT AUTO_INCREMENT PRIMARY KEY,
              AUFNR VARCHAR(20) NOT NULL,
              VORNRX VARCHAR(10) NULL,
              PERNR VARCHAR(20) NULL,
              ARBPL0 VARCHAR(40) NULL,
              DISPO VARCHAR(10) NULL,
              STEUS VARCHAR(8) NULL,
              WERKS VARCHAR(10) NULL,
              -- NEW: Sales Order & Item
              KDAUF VARCHAR(20) NULL,
              KDPOS VARCHAR(10) NULL,
              CHARG VARCHAR(20) NULL,
              MATNRX VARCHAR(40) NULL,
              MAKTX VARCHAR(200) NULL,
              -- ➕ FG (level-0) dari SAP
              MATNR0 VARCHAR(40) NULL,
              MATTX0 VARCHAR(200) NULL,
              MEINH VARCHAR(10) NULL,
              QTY_SPK DECIMAL(18,3) NULL,
              WEMNG DECIMAL(18,3) NULL,
              QTY_SPX DECIMAL(18,3) NULL,
              LTXA1 VARCHAR(200) NULL,
              SNAME VARCHAR(100) NULL,
              GSTRP DATE NULL,
              GLTRP DATE NULL,
              SSAVD DATE NULL,
              SSSLD DATE NULL,
              -- NEW: start/finish time in minutes (as numeric)
              LTIME  DECIMAL(18,3) NULL,
              LTIMEX DECIMAL(18,3) NULL,
              ISDZ VARCHAR(20) NULL,
              IEDZ VARCHAR(20) NULL,
              RAW_JSON JSON NOT NULL,
              fetched_at DATETIME NOT NULL,
              UNIQUE KEY uniq_key (AUFNR, VORNRX, CHARG, ARBPL0),
              KEY idx_aufnr (AUFNR),
              KEY idx_pernr (PERNR),
              KEY idx_arbpl (ARBPL0),
              KEY idx_steus (STEUS),
              KEY idx_werks (WERKS),
              KEY idx_kdauf (KDAUF),
              KEY idx_kdpos (KDPOS)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            """)
            cur.execute("""
            CREATE TABLE IF NOT EXISTS yppi019_confirm_log (
              id BIGINT AUTO_INCREMENT PRIMARY KEY, AUFNR VARCHAR(20) NOT NULL, VORNR VARCHAR(10) NULL,
              PERNR VARCHAR(20) NULL, PSMNG DECIMAL(18,3) NULL, MEINH VARCHAR(10) NULL, GSTRP DATE NULL,
              GLTRP DATE NULL, BUDAT DATE NULL, SAP_RETURN JSON NULL, created_at DATETIME NOT NULL,
              KEY idx_aufnr (AUFNR)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            """)
            cur.execute("""
            CREATE TABLE IF NOT EXISTS yppi019_backdate_log (
              id BIGINT AUTO_INCREMENT PRIMARY KEY,
              AUFNR     VARCHAR(20) NOT NULL,
              VORNR     VARCHAR(10) NULL,
              PERNR     VARCHAR(20) NULL,
              QTY       DECIMAL(18,3) NULL,
              MEINH     VARCHAR(10) NULL,
              BUDAT     DATE NOT NULL,
              TODAY     DATE NOT NULL,
              ARBPL0    VARCHAR(40) NULL,
              MAKTX     VARCHAR(200) NULL,
              SAP_RETURN JSON NULL,
              CONFIRMED_AT DATETIME NULL,
              CREATED_AT   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_aufnr (AUFNR),
              KEY idx_budat (BUDAT),
              KEY idx_pernr (PERNR)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            """)
            # --- Lightweight migration if the columns already exist / or DB created earlier
            try:
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN LTIME DECIMAL(18,3) NULL AFTER SSSLD")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN LTIMEX DECIMAL(18,3) NULL AFTER LTIME")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN MATNR0 VARCHAR(40) NULL AFTER MAKTX")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN MATTX0 VARCHAR(200) NULL AFTER MATNR0")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD KEY idx_matnr0 (MATNR0)")
            except Exception:
                pass

            db.commit()

def map_tdata1_row(r: Dict[str, Any]) -> Dict[str, Any]:
    return {
        "AUFNR": (r.get("AUFNR") or "").strip(),
        "VORNRX": pad_vornr(r.get("VORNRX") or r.get("VORNR") or ""),
        "PERNR": (str(r.get("PERNR") or "").strip() or None),
        "ARBPL0": (r.get("ARBPL0") or r.get("ARBPL") or None),
        "DISPO": r.get("DISPO"),
        "STEUS": r.get("STEUS"),
        "WERKS": r.get("WERKS"),
        # NEW: Sales Order & Item dari SAP jika tersedia
        "KDAUF": (r.get("KDAUF") or None),
        "KDPOS": (pad_kdpos(r.get("KDPOS")) or None),

        "CHARG": (str(r.get("CHARG") or "").strip()),
        "MATNRX": r.get("MATNRX"),
        "MAKTX": r.get("MAKTX"),
        # ➕ FG (level-0)
        "MATNR0": r.get("MATNR0"),
        "MATTX0": r.get("MATTX0"),
        "MEINH": r.get("MEINH"),
        "QTY_SPK": parse_num(r.get("QTY_SPK")),
        "WEMNG": parse_num(r.get("WEMNG")),
        "QTY_SPX": parse_num(r.get("QTY_SPX")),
        "LTXA1": r.get("LTXA1"),
        "SNAME": r.get("SNAME"),
        "GSTRP": parse_date(r.get("GSTRP")),
        "GLTRP": parse_date(r.get("GLTRP")),
        # ➕ BARU: tanggal start/finish dari SAP (format apa pun → dinormalisasi oleh parse_date)
        "SSAVD": parse_date(r.get("SSAVD")),
        "SSSLD": parse_date(r.get("SSSLD")),
        # ➕ BARU: waktu (menit, numeric) — langsung parse ke float
        "LTIME":  parse_num(r.get("LTIME")),
        "LTIMEX": parse_num(r.get("LTIMEX")),
        "ISDZ": r.get("ISDZ"),
        "IEDZ": r.get("IEDZ"),
        "RAW_JSON": json.dumps(to_jsonable(r), ensure_ascii=False),
        "fetched_at": datetime.datetime.now(),
    }


# ---------------- READ from SAP & sync ----------------
def fetch_from_sap(sap: Connection, aufnr: Optional[str], pernr: Optional[str], arbpl: Optional[str], werks: Optional[str]) -> List[Dict[str, Any]]:
    def _call(args):
        logger.info("Calling %s with %s", RFC_Y, args)
        res = sap.call(RFC_Y, **args)
        rows = [map_tdata1_row(r) for r in (res.get("T_DATA1", []) or [])]
        ret = res.get("RETURN") or res.get("T_MESSAGES") or []
        # log hanya E/A/W (Error/Abort/Warning), abaikan S/I
        bad = [m for m in ret if str(m.get("TYPE", "")).upper() in ("E", "A", "W")]
        if bad:
            logger.warning("RETURN/MESSAGES: %s", to_jsonable(bad))

        logger.info("Result %s: %d row(s)", RFC_Y, len(rows))
        return rows
    
    args = {}
    if aufnr:
        args["IV_AUFNR"] = pad_aufnr(aufnr)
    elif arbpl and werks:
        args["IV_ARBPL"] = str(arbpl)
        args["IV_WERKS"] = str(werks)
    else:
        logger.warning("fetch_from_sap called with insufficient primary parameters.")
        return []

    if pernr: args["IV_PERNR"] = str(pernr)
    if "IV_AUFNR" in args:
        if arbpl: args["IV_ARBPL"] = str(arbpl)
        if werks: args["IV_WERKS"] = str(werks)

    
    rows = _call(args)  # biarkan error propagate ke caller
    if pernr:
        for r in rows:
            if not r.get("PERNR"): r["PERNR"] = pernr
    return rows

# ---------- granular per-AUFNR sync with advisory locks ----------
def sync_from_sap(
    username: Optional[str],
    password: Optional[str],
    aufnr: Optional[str] = None,
    pernr: Optional[str] = None,
    arbpl: Optional[str] = None,
    werks: Optional[str] = None,
) -> Dict[str, Any]:
    """
    Sinkron dari SAP ke MySQL (granular per-AUFNR) + dukungan kolom FG:
      - MATNR0 (kode FG)
      - MATTX0 (deskripsi FG)

    Perilaku:
      1) Panggil RFC read, kelompokkan hasil per AUFNR
      2) (Mode WC) Hapus AUFNR 'stale' untuk kombinasi ARBPL0+WERKS+PERNR
      3) Per AUFNR: kunci advisory (GET_LOCK), hapus snapshot lama,
         INSERT ... ON DUPLICATE KEY UPDATE baris terbaru (dengan MATNR0/MATTX0)
    """
    ensure_tables()

    # --- Pastikan kolom baru ada (migrasi ringan, idempotent) ---
    try:
        with connect_mysql() as _db_mig:
            with _db_mig.cursor() as _cur_mig:
                # Beberapa MySQL belum punya "ADD COLUMN IF NOT EXISTS"
                # jadi kita pakai try/except biar idempotent.
                try:
                    _cur_mig.execute(
                        "ALTER TABLE yppi019_data ADD COLUMN MATNR0 VARCHAR(40) NULL AFTER MATNRX"
                    )
                except Exception:
                    pass
                try:
                    _cur_mig.execute(
                        "ALTER TABLE yppi019_data ADD COLUMN MATTX0 VARCHAR(200) NULL AFTER MAKTX"
                    )
                except Exception:
                    pass
            _db_mig.commit()
    except Exception:
        # Jangan gagalkan sync hanya karena migrasi kolom gagal
        logger.exception("Lightweight migration for MATNR0/MATTX0 failed (ignored)")

    # --- Ambil dari SAP ---
    sap = None
    try:
        try:
            sap = connect_sap(username, password)
            rows = fetch_from_sap(sap, aufnr, pernr, arbpl, werks)
            n_received = len(rows)
        finally:
            if sap:
                sap.close()

        if n_received == 0:
            return {
                "ok": True,
                "received": 0,
                "saved": 0,
                "wiped": 0,
                "prev_count": 0,
                "note": "no data from SAP; local data untouched",
            }

        # --- Tambahkan MATNR0/MATTX0 ke setiap row (kalau ada di SAP/RAW_JSON) ---
        for r in rows:
            # Kalau sudah ada, biarkan; kalau tidak, coba ambil dari RAW_JSON
            if r.get("MATNR0") is None or r.get("MATNR0") == "":
                try:
                    raw = json.loads(r.get("RAW_JSON") or "{}")
                    # preferensi: MATNR0 bila ada; fallback MATNR / MATNRX
                    r["MATNR0"] = (raw.get("MATNR0") or raw.get("MATNR") or raw.get("MATNRX") or None)
                except Exception:
                    r.setdefault("MATNR0", None)
            if r.get("MATTX0") is None or r.get("MATTX0") == "":
                try:
                    raw = json.loads(r.get("RAW_JSON") or "{}")
                    # preferensi: MATTX0 bila ada; fallback MAKTX
                    r["MATTX0"] = (raw.get("MATTX0") or raw.get("MAKTX") or None)
                except Exception:
                    r.setdefault("MATTX0", None)

        # Kelompokkan per AUFNR
        by_aufnr: Dict[str, List[Dict[str, Any]]] = {}
        for rr in rows:
            a = (rr.get("AUFNR") or "").strip()
            if not a:
                continue
            by_aufnr.setdefault(a, []).append(rr)

        # --- Purge AUFNR stale (mode WC) ---
        if arbpl and werks:
            db_select = None
            cur_select = None
            try:
                db_select = connect_mysql()
                cur_select = db_select.cursor()
                cur_select.execute(
                    "SELECT DISTINCT AUFNR FROM yppi019_data WHERE ARBPL0=%s AND WERKS=%s AND PERNR=%s",
                    (arbpl, werks, pernr or ""),
                )
                existing = {row[0] for row in cur_select.fetchall()}
            finally:
                if cur_select:
                    cur_select.close()
                if db_select:
                    db_select.close()

            now_have = set(by_aufnr.keys())
            stale = sorted(existing - now_have)
            if stale:
                db_stale = None
                cur_stale = None
                try:
                    db_stale = connect_mysql()
                    cur_stale = db_stale.cursor()
                    for a in stale:
                        try:
                            acquire_mutex(cur_stale, f"aufnr:{a}", timeout=2)
                        except RuntimeError:
                            continue
                        try:
                            db_stale.start_transaction()
                            cur_stale.execute(
                                "DELETE FROM yppi019_data WHERE AUFNR=%s AND ARBPL0=%s AND WERKS=%s AND PERNR=%s",
                                (a, arbpl, werks, pernr or ""),
                            )
                            db_stale.commit()
                        except Exception:
                            db_stale.rollback()
                            raise
                        finally:
                            release_mutex(cur_stale, f"aufnr:{a}")
                finally:
                    if cur_stale:
                        cur_stale.close()
                    if db_stale:
                        db_stale.close()

        saved_total = wiped_total = prev_total = 0

        # --- Simpan per AUFNR (advisory lock) ---
        db_main = None
        cur_main = None
        try:
            db_main = connect_mysql()
            cur_main = db_main.cursor()

            for a in sorted(by_aufnr.keys()):
                try:
                    acquire_mutex(cur_main, f"aufnr:{a}", timeout=10)
                except RuntimeError:
                    return {
                        "ok": False,
                        "error": "Sedang diproses oleh user lain. Coba lagi sebentar.",
                        "error_code": "AUFNR_LOCKED",
                        "busy_aufnr": a,
                    }
                try:
                    db_main.start_transaction()

                    # hitung snapshot lama
                    cur_main.execute("SELECT COUNT(*) FROM yppi019_data WHERE AUFNR=%s", (a,))
                    prev_count = (cur_main.fetchone() or [0])[0]

                    # clear snapshot lama (dipersempit bila WC+WERKS diberikan)
                    if arbpl and werks:
                        cur_main.execute(
                            "DELETE FROM yppi019_data WHERE AUFNR=%s AND ARBPL0=%s AND WERKS=%s",
                            (a, arbpl, werks),
                        )
                    else:
                        cur_main.execute("DELETE FROM yppi019_data WHERE AUFNR=%s", (a,))
                    wiped = cur_main.rowcount

                    # upsert fresh rows (dengan MATNR0, MATTX0)
                    cur_main.executemany(
                        """
INSERT INTO yppi019_data
 (AUFNR,VORNRX,PERNR,ARBPL0,DISPO,STEUS,WERKS,
  KDAUF,KDPOS,
  CHARG,MATNRX,MAKTX,MEINH,
  QTY_SPK,WEMNG,QTY_SPX,LTXA1,SNAME,
  GSTRP,GLTRP,SSAVD,SSSLD,LTIME,LTIMEX,
  ISDZ,IEDZ,RAW_JSON,fetched_at,
  MATNR0,MATTX0)
VALUES
 (%(AUFNR)s,%(VORNRX)s,%(PERNR)s,%(ARBPL0)s,%(DISPO)s,%(STEUS)s,%(WERKS)s,
  %(KDAUF)s,%(KDPOS)s,
  %(CHARG)s,%(MATNRX)s,%(MAKTX)s,%(MEINH)s,
  %(QTY_SPK)s,%(WEMNG)s,%(QTY_SPX)s,%(LTXA1)s,%(SNAME)s,
  %(GSTRP)s,%(GLTRP)s,%(SSAVD)s,%(SSSLD)s,%(LTIME)s,%(LTIMEX)s,
  %(ISDZ)s,%(IEDZ)s,%(RAW_JSON)s,%(fetched_at)s,
  %(MATNR0)s,%(MATTX0)s)
ON DUPLICATE KEY UPDATE
  PERNR=VALUES(PERNR),
  ARBPL0=VALUES(ARBPL0),
  DISPO=VALUES(DISPO),
  STEUS=VALUES(STEUS),
  WERKS=VALUES(WERKS),
  KDAUF=VALUES(KDAUF),
  KDPOS=VALUES(KDPOS),
  CHARG=VALUES(CHARG),
  MATNRX=VALUES(MATNRX),
  MAKTX=VALUES(MAKTX),
  MEINH=VALUES(MEINH),
  QTY_SPK=VALUES(QTY_SPK),
  WEMNG=VALUES(WEMNG),
  LTXA1=VALUES(LTXA1),
  SNAME=VALUES(SNAME),
  GSTRP=VALUES(GSTRP),
  GLTRP=VALUES(GLTRP),
  SSAVD=VALUES(SSAVD),
  SSSLD=VALUES(SSSLD),
  LTIME=VALUES(LTIME),
  LTIMEX=VALUES(LTIMEX),
  ISDZ=VALUES(ISDZ),
  IEDZ=VALUES(IEDZ),
  RAW_JSON=VALUES(RAW_JSON),
  fetched_at=VALUES(fetched_at),
  MATNR0=VALUES(MATNR0),
  MATTX0=VALUES(MATTX0),
  QTY_SPX=CASE
    WHEN VALUES(QTY_SPX) IS NULL THEN QTY_SPX
    WHEN QTY_SPX IS NULL THEN VALUES(QTY_SPX)
    ELSE LEAST(QTY_SPX, VALUES(QTY_SPX))
  END
                        """,
                        by_aufnr[a],
                    )

                    saved = cur_main.rowcount
                    db_main.commit()

                    wiped_total += wiped
                    prev_total += prev_count
                    saved_total += saved
                except Exception:
                    db_main.rollback()
                    raise
                finally:
                    release_mutex(cur_main, f"aufnr:{a}")
        finally:
            if cur_main:
                cur_main.close()
            if db_main:
                db_main.close()

        return {
            "ok": True,
            "received": n_received,
            "saved": saved_total,
            "wiped": wiped_total,
            "prev_count": prev_total,
            "note": "replaced with fresh data from SAP (per-AUFNR locked, with MATNR0/MATTX0)",
        }

    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
        logger.exception("SAP error in sync_from_sap")
        return {"ok": False, "error": humanize_rfc_error(e)}
    except Exception as e:
        logger.exception("Generic error in sync_from_sap")
        return {"ok": False, "error": str(e)}

# ---------------- HTTP endpoints ----------------
@app.get("/")
def root():
    return ("OK - endpoints: GET /api/yppi019 | POST /api/yppi019/sync | ...", 200, {"Content-Type":"text/plain"})

@app.post("/api/yppi019/login")
@app.post("/api/sap-login")
def sap_login():
    try:
        u, p = get_credentials_from_request()
        with connect_sap(u, p) as conn: conn.ping()
        return jsonify({"status": "connected"}), 200
    except ValueError as ve: return jsonify({"error": str(ve)}), 401
    except Exception as e: logger.exception("SAP login failed"); return jsonify({"error": str(e)}), 401

@app.get("/api/yppi019")
def api_get_yppi019():
    """
    GET /api/yppi019
    Selalu kembalikan SEMUA data yang match filter (tanpa limit),
    sekalipun client mengirim ?limit=...
    Filter: aufnr, vornrx, charg, steus, pernr, arbpl, werks
    Kondisi stok wajib:
      (IFNULL(QTY_SPX,0) > 0 AND IFNULL(WEMNG,0) < IFNULL(QTY_SPK,0))
    """
    ensure_tables()

    get = request.args.get
    params = {
        "AUFNR":  get("aufnr"),
        "VORNRX": get("vornrx"),
        "CHARG":  get("charg"),
        "STEUS":  get("steus"),
        "PERNR":  get("pernr"),
        "ARBPL0": get("arbpl"),
        "WERKS":  get("werks"),
    }

    # WHERE
    where_parts, args = [], []
    for k, v in params.items():
        if v not in (None, ""):
            where_parts.append(f"{k}=%s")
            args.append(v)

    where_parts.append("(IFNULL(QTY_SPX, 0) > 0 AND IFNULL(WEMNG, 0) < IFNULL(QTY_SPK, 0))")
    where_sql = " WHERE " + " AND ".join(where_parts) if where_parts else ""

    # ORDER BY (mengikuti logika bawaan)
    if params.get("AUFNR") and not params.get("ARBPL0"):
        order_sql = " ORDER BY VORNRX ASC"
    else:
        order_sql = " ORDER BY AUFNR ASC, VORNRX ASC"

    base_from = "FROM yppi019_data"
    data_sql  = f"SELECT * {base_from}{where_sql}{order_sql}"     # <— TANPA LIMIT
    count_sql = f"SELECT COUNT(*) AS total {base_from}{where_sql}"

    with connect_mysql() as db:
        with db.cursor(dictionary=True) as cur:
            cur.execute(count_sql, tuple(args))
            total = int((cur.fetchone() or {"total": 0})["total"])

            cur.execute(data_sql, tuple(args))
            rows = cur.fetchall() or []

    return jsonify({
        "ok": True,
        "filters": {k: v for k, v in params.items() if v not in (None, "")},
        "total": total,          # jumlah match (tanpa limit)
        "returned": len(rows),   # harus = total
        "rows": to_jsonable(rows),
    })

@app.post("/api/yppi019/sync")
def api_sync():
    try:
        u, p = get_credentials_from_request()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    body  = request.get_json(force=True) or {}
    aufnr = (body.get("aufnr") or "").strip()
    pernr = (body.get("pernr") or "").strip()
    arbpl = (body.get("arbpl") or "").strip()
    werks = (body.get("werks") or "").strip()

    if not pernr:
        return jsonify({"ok": False, "error": "pernr wajib diisi"}), 400
    if not aufnr and not arbpl:
        return jsonify({"ok": False, "error": "aufnr atau arbpl wajib diisi"}), 400
    if arbpl and not werks:
        return jsonify({"ok": False, "error": "jika arbpl diisi, werks wajib"}), 400

    res = sync_from_sap(u, p, aufnr=aufnr, pernr=pernr, arbpl=arbpl, werks=werks)

    if res.get("ok") and int(res.get("received") or 0) == 0:
        res["teco_possible"] = True
        res["refreshed"] = False
        res.setdefault("message", "Data Tidak Ditemukan")
        return jsonify(to_jsonable(res)), 404

    # Map khusus: kalau sync gagal karena AUFNR sedang terkunci, balas 423 (bukan 500)
    if not res.get("ok") and res.get("error_code") == "AUFNR_LOCKED":
        res["refreshed"] = False
        return jsonify(to_jsonable(res)), 423

    status = 200 if res.get("ok") else 500
    res["refreshed"] = bool(res.get("ok"))
    return jsonify(to_jsonable(res)), status



# --- helper kecil (taruh sekali saja di file ini, di atas route) ---
def _is_message_table(rows) -> bool:
    """
    Deteksi tabel pesan (BAPIRET2-like). Jika true, jangan dipakai sebagai data.
    """
    if not isinstance(rows, list) or not rows:
        return False
    first = rows[0]
    if not isinstance(first, dict):
        return False
    msg_keys = {
        "TYPE","ID","NUMBER","MESSAGE","PARAMETER","FIELD","SYSTEM",
        "LOG_MSG_NO","LOG_NO","ROW","MESSAGE_V1","MESSAGE_V2","MESSAGE_V3","MESSAGE_V4"
    }
    keys = {str(k).upper() for k in first.keys()}
    return keys.issubset(msg_keys)


# --- ENDPOINT FINAL: /api/yppi019/hasil ---
@app.get("/api/yppi019/hasil")
def api_hasil():
    """
    GET /api/yppi019/hasil?pernr=XXXXXX&budat=YYYYMMDD[&aufnr=XXXXXXXXXXXX]
    ... (dokumentasi lainnya) ...
    """
    try:
        username, password = get_credentials_from_request()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    pernr = (request.args.get("pernr") or "").strip()
    budat = (request.args.get("budat") or "").strip().replace("-", "")
    aufnr = (request.args.get("aufnr") or "").strip()  # opsional

    if not pernr or not re.match(r"^\d{8}$", budat):
        return jsonify({"ok": False, "error": "param pernr & budat(YYYYMMDD) wajib"}), 400

    # 1. Deklarasikan variabel 'sap' di luar blok try
    sap = None 
    try:
        # 2. Buka koneksi secara manual
        sap = connect_sap(username, password) 
        
        args = {"P_PERNR": pernr, "P_BUDAT": budat}
        if aufnr:
            # zero-pad 12 digit kalau user mengirimkan PRO
            args["P_AUFNR"] = pad_aufnr(aufnr)

        logger.info("Calling Z_FM_YPPR062 with %s", args)
        res = sap.call("Z_FM_YPPR062", **args)

        # Ambil tabel pasti sesuai metadata
        rows = res.get("T_DATA1", []) or []
        messages = res.get("RETURN", []) or []

        # ===== Tambahan LOG =====
        logger.info(
            "Z_FM_YPPR062 -> rows=%d, messages=%d",
            len(rows), len(messages)
        )
        if rows and isinstance(rows, list) and isinstance(rows[0], dict):
            sample_keys = list(rows[0].keys())[:8]
            sample_row  = {k: rows[0].get(k) for k in sample_keys}
            logger.debug("Z_FM_YPPR062 first_row_keys=%s sample=%s", sample_keys, sample_row)
        if messages:
            kinds = [str(m.get("TYPE", "")).upper() for m in messages]
            logger.info("Z_FM_YPPR062 message_types=%s", ",".join(kinds))

        # Jika RETURN ada TYPE E/A, anggap error dari SAP
        err = next((m for m in messages if str(m.get("TYPE", "")).upper() in ("E", "A")), None)
        if err:
            msg = err.get("MESSAGE") or str(err)
            logger.warning("Z_FM_YPPR062 returned error: %s", msg)
            # 'finally' akan berjalan sebelum return ini
            return jsonify({"ok": False, "error": msg, "messages": to_jsonable(messages)}), 502

        # Jangan kirim tabel pesan sebagai data
        if _is_message_table(rows):
            rows = []

        payload = {
            "ok": True,
            "rows": to_jsonable(rows),
            "messages": to_jsonable(messages),
            "count": len(rows),
        }
        # 'finally' akan berjalan sebelum return ini
        return jsonify(payload), 200

    except (ABAPApplicationError, ABAPRuntimeError, CommunicationError, LogonError) as e:
        # 'finally' akan berjalan sebelum return ini
        return jsonify({"ok": False, "error": str(e)}), 500
    except Exception as e:
        logger.exception("Error api_hasil")
        # 'finally' akan berjalan sebelum return ini
        return jsonify({"ok": False, "error": str(e)}), 500
    finally:
        # 3. Blok 'finally' ini akan SELALU dieksekusi
        if sap:
            # 4. Tutup koneksi jika koneksi berhasil dibuat
            sap.close()

# ---------------- Confirm & Other Endpoints ----------------
@app.post("/api/yppi019/confirm")
def api_confirm():
    try:
        u, p = get_credentials_from_request()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    b = request.get_json(force=True) or {}

    aufnr  = (str(b.get("aufnr") or "").strip())
    vornr  = pad_vornr(b.get("vornr"))
    pernr  = (str(b.get("pernr") or "").strip())
    budat  = (str(b.get("budat") or "").strip())
    qty_in = parse_num(b.get("psmng"))

    if not (aufnr and vornr and pernr and budat and qty_in is not None and qty_in > 0):
        return jsonify({"ok": False, "error": "Parameter tidak valid atau psmng <= 0"}), 400

    # 1. Deklarasikan db dan cur sebagai None di luar try
    db = None
    cur = None
    try:
        # 2. Buka koneksi dan cursor MySQL secara manual
        db = connect_mysql()
        cur = db.cursor(dictionary=True, buffered=True)

        coarse_key = f"aufnr:{aufnr}"
        fine_key   = f"aufnr:{aufnr}:vornr:{vornr}"

        # 1) coarse lock
        try:
            acquire_mutex(cur, coarse_key, timeout=8)
        except RuntimeError:
            # 'finally' utama akan menutup db dan cur
            return locked_response("AUFNR", aufnr)

        # 2) fine lock
        try:
            acquire_mutex(cur, fine_key, timeout=8)
        except RuntimeError:
            release_mutex(cur, coarse_key) # lepas coarse lock dulu
            # 'finally' utama akan menutup db dan cur
            return locked_response("AUFNR+VORNR", f"{aufnr}/{vornr}")

        # Deklarasikan variabel ini agar bisa diakses di 'return'
        sap_ret = None
        latest_row = None
        
        try: # Ini adalah try untuk 'finally' pelepas mutex
            db.start_transaction()

            # (opsional) pembeda tambahan
            charg  = (str(b.get("charg")  or "").strip())
            arbpl0 = (str(b.get("arbpl0") or b.get("arbpl") or "").strip())

            where = ["AUFNR=%s", "VORNRX=%s"]
            args  = [aufnr, vornr]
            if charg:
                where.append("CHARG=%s");  args.append(charg)
            if arbpl0:
                where.append("ARBPL0=%s"); args.append(arbpl0)

            cur.execute(f"""
                SELECT id, QTY_SPK, WEMNG, QTY_SPX, MEINH
                FROM yppi019_data
                WHERE {" AND ".join(where)}
                FOR UPDATE
            """, tuple(args))
            rows = cur.fetchall()  # habiskan result set

            if not rows:
                db.rollback()
                return jsonify({"ok": False, "error": "Data operation tidak ditemukan"}), 404
            if len(rows) > 1 and not (charg or arbpl0):
                db.rollback()
                return jsonify({"ok": False, "error": "Data tidak unik (>1 baris). Sertakan CHARG/ARBPL0."}), 409

            row_db  = rows[0]
            qty_spk = float(row_db.get("QTY_SPK") or 0.0)
            wemng   = float(row_db.get("WEMNG")   or 0.0)
            qty_spx = float(row_db.get("QTY_SPX") or 0.0)

            sisa_spk = max(qty_spk - wemng, 0.0)
            sisa_spx = max(qty_spx, 0.0)

            if qty_in > sisa_spk:
                db.rollback()
                return jsonify({"ok": False, "error": f"Input melebihi QTY_SPK sisa ({sisa_spk})."}), 400
            if qty_in > sisa_spx:
                db.rollback()
                return jsonify({"ok": False, "error": f"Input melebihi QTY_SPX sisa ({sisa_spx})."}), 400

            meinh_req = normalize_uom(b.get("meinh") or row_db.get("MEINH"))
            if meinh_req in {"PC", "EA", "PCS", "UNIT"}:
                psmng_str = str(int(round(qty_in)))
            else:
                psmng_str = f"{qty_in:.3f}".replace(".", ",")

            # 3. Deklarasikan koneksi SAP
            sap = None
            try:
                # 4. Buka koneksi SAP secara manual
                sap = connect_sap(u, p)
                sap_ret = sap.call(
                    RFC_C,
                    IV_AUFNR=pad_aufnr(aufnr),
                    IV_VORNR=vornr,
                    IV_PERNR=pernr,
                    IV_PSMNG=psmng_str,
                    IV_MEINH=meinh_req,
                    IV_GSTRP=str(b.get("gstrp") or ""),
                    IV_GLTRP=str(b.get("gltrp") or ""),
                    IV_BUDAT=budat,
                )

                def _collect_msgs(ret):
                    msgs = []
                    if isinstance(ret, dict):
                        for k, v in ret.items():
                            if isinstance(v, list):
                                msgs += [x for x in v if isinstance(x, dict)]
                    return msgs

                msgs = _collect_msgs(sap_ret)
                err = next((m for m in msgs if str(m.get("TYPE","")).upper() in ("E","A")), None)
                if err:
                    db.rollback()
                    return jsonify({"ok": False,
                                    "error": err.get("MESSAGE") or "SAP returned error",
                                    "sap_return": to_jsonable(sap_ret)}), 500

            except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
                db.rollback()
                logger.exception("SAP error api_confirm")
                return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
            finally:
                # 5. Tutup koneksi SAP jika berhasil dibuka
                if sap:
                    sap.close()

            cur.execute(
                """
                INSERT INTO yppi019_confirm_log
                  (AUFNR, VORNR, PERNR, PSMNG, MEINH, GSTRP, GLTRP, BUDAT, SAP_RETURN, created_at)
                VALUES
                  (%s,    %s,    %s,    %s,    %s,    %s,    %s,    %s,    %s,         %s)
                """,
                (
                    aufnr,
                    vornr,
                    pernr,
                    qty_in,
                    meinh_req,
                    parse_date(b.get("gstrp")),
                    parse_date(b.get("gltrp")),
                    parse_date(budat),
                    json.dumps(to_jsonable(sap_ret), ensure_ascii=False),
                    datetime.datetime.now(),
                ),
            )

            cur.execute(
                """
                UPDATE yppi019_data
                SET WEMNG = IFNULL(WEMNG,0) + %s,
                    QTY_SPX = GREATEST(IFNULL(QTY_SPX,0) - %s, 0),
                    fetched_at = NOW()
                WHERE id=%s
                  AND (IFNULL(QTY_SPK,0) - IFNULL(WEMNG,0)) >= %s
                  AND IFNULL(QTY_SPX,0) >= %s
                """,
                (qty_in, qty_in, row_db["id"], qty_in, qty_in),
            )

            if cur.rowcount != 1:
                db.rollback()
                return jsonify({"ok": False, "error": "Gagal update; sisa sudah berubah, silakan refresh"}), 409

            cur.execute("DELETE FROM yppi019_confirm_log WHERE DATE(created_at) < CURDATE()")
            db.commit()

            cur.execute("SELECT * FROM yppi019_data WHERE id=%s", (row_db["id"],))
            latest_row = cur.fetchone()

        finally:
            # lepas fine lock dulu, lalu coarse lock
            try:
                release_mutex(cur, fine_key)
            finally:
                release_mutex(cur, coarse_key)

        return jsonify(
            {
                "ok": True,
                "sap_return": to_jsonable(sap_ret),
                "refreshed": {"ok": True, "mode": "local_update"},
                "row": to_jsonable(latest_row),
            }
        ), 200

    except Exception as e:
        logger.exception("Error api_confirm")
        try:
            # Coba rollback jika db berhasil terkoneksi
            if db:
                db.rollback()
        except Exception:
            pass
        return jsonify({"ok": False, "error": str(e)}), 500
    finally:
        # 6. Selalu tutup cursor dan koneksi MySQL
        if cur:
            cur.close()
        if db:
            db.close()


@app.post("/api/yppi019/backdate-log")
def api_backdate_log():
    b = request.get_json(force=True) or {}
    for k in ("aufnr", "budat", "today"):
        if not str(b.get(k) or "").strip():
            return jsonify({"ok": False, "error": f"missing field: {k}"}), 400

    aufnr   = str(b.get("aufnr")).strip()
    vornr   = (str(b.get("vornr") or "").strip() or None)
    pernr   = (str(b.get("pernr") or "").strip() or None)
    qty     = parse_num(b.get("qty"))
    meinh   = (str(b.get("meinh") or "").strip() or None)
    budat_s = str(b.get("budat")).strip()
    today_s = str(b.get("today")).strip()
    arbpl0  = (str(b.get("arbpl0") or "").strip() or None)
    maktx   = (str(b.get("maktx") or "").strip() or None)
    sap_ret = b.get("sap_return")
    confirmed_at_s = (str(b.get("confirmed_at") or "").strip() or None)

    from datetime import datetime, date
    try:
        budat_dt: date = datetime.strptime(budat_s, "%Y%m%d").date()
        today_dt: date = datetime.strptime(today_s, "%Y%m%d").date()
    except Exception:
        return jsonify({"ok": False, "error": "invalid date format (expected YYYYMMDD)"}), 400

    if budat_dt > today_dt:
        return jsonify({"ok": False, "error": "BUDAT cannot be in the future"}), 422
    if budat_dt == today_dt:
        return jsonify({"ok": True, "skipped": True})

    confirmed_dt = None
    if confirmed_at_s:
        try:
            confirmed_dt = datetime.fromisoformat(confirmed_at_s.replace("Z", "+00:00"))
        except Exception:
            confirmed_dt = None

    # 1. Deklarasikan db dan cur sebagai None di luar try
    db = None
    cur = None
    try:
        # 2. Buka koneksi dan cursor secara manual
        db = connect_mysql()
        cur = db.cursor()
        
        cur.execute(
            """
            INSERT INTO yppi019_backdate_log
              (AUFNR, VORNR, PERNR, QTY, MEINH, BUDAT, TODAY, ARBPL0, MAKTX, SAP_RETURN, CONFIRMED_AT)
            VALUES
              (%s,    %s,    %s,    %s,  %s,    %s,   %s,    %s,     %s,    %s,         %s)
            """,
            (
                aufnr, vornr, pernr, qty, meinh,
                budat_dt, today_dt,
                arbpl0, maktx,
                json.dumps(to_jsonable(sap_ret), ensure_ascii=False) if sap_ret is not None else None,
                confirmed_dt
            ),
        )
        cur.execute("""
        DELETE b
        FROM yppi019_backdate_log b
        JOIN (
            SELECT id FROM (
                SELECT id
                FROM yppi019_backdate_log
                ORDER BY COALESCE(CONFIRMED_AT, CREATED_AT) DESC, id DESC
                LIMIT 18446744073709551615 OFFSET 50
            ) t
        ) old ON old.id = b.id
        """)
        db.commit()
        
        # 'finally' akan berjalan setelah return ini
        return jsonify({"ok": True}), 200
    
    except Exception as e:
        logger.exception("Error api_backdate_log")
        try:
            # Coba rollback jika terjadi error sebelum commit
            if db:
                db.rollback()
        except Exception:
            pass # Abaikan error pada rollback
        
        # 'finally' akan berjalan setelah return ini
        return jsonify({"ok": False, "error": str(e)}), 500
    
    finally:
        # 3. Selalu tutup cursor dan koneksi
        if cur:
            cur.close()
        if db:
            db.close()

@app.get("/api/yppi019/backdate-history")
def api_backdate_history():
    ensure_tables()
    pernr = (request.args.get("pernr") or "").strip()
    aufnr = (request.args.get("aufnr") or "").strip()
    limit = request.args.get("limit", type=int) or 50
    order = (request.args.get("order") or "desc").lower()
    order_sql = "ASC" if order == "asc" else "DESC"
    limit = max(1, min(int(limit), 500))

    # 1. Deklarasikan db dan cur sebagai None di luar try
    db = None
    cur = None
    try:
        # 2. Buka koneksi dan cursor secara manual
        db = connect_mysql()
        cur = db.cursor(dictionary=True)

        sql = "SELECT * FROM yppi019_backdate_log"
        where, args = [], []
        if pernr:
            where.append("PERNR=%s"); args.append(pernr)
        if aufnr:
            where.append("AUFNR=%s"); args.append(aufnr)
        if where:
            sql += " WHERE " + " AND ".join(where)
        sql += f" ORDER BY COALESCE(CONFIRMED_AT, CREATED_AT) {order_sql} LIMIT %s"
        args.append(limit)
        
        cur.execute(sql, tuple(args))
        rows = cur.fetchall()
        
        # 'finally' akan berjalan sebelum return ini
        return jsonify({"ok": True, "rows": to_jsonable(rows)}), 200
    
    except Exception as e:
        logger.exception("Error api_backdate_history")
        # 'finally' akan berjalan sebelum return ini
        return jsonify({"ok": False, "error": str(e)}), 500
    
    finally:
        # 3. Blok 'finally' akan selalu dieksekusi
        #    Tutup cursor dulu, baru koneksi
        if cur:
            cur.close()
        if db:
            db.close()

if __name__ == "__main__":
    ensure_tables()
    app.run(host=HTTP_HOST, port=HTTP_PORT, debug=False, use_reloader=False, threaded=True)