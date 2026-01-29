"""
PENTING
php artisan queue:table
php artisan queue:failed-table
php artisan queue:work --queue=default -v
php artisan queue:restart
"""
# yppi019.py — FINAL (per-AUFNR advisory lock) + FIX "Unread result found"
# + KDAUF/KDPOS + LTIME/LTIMEX (minutes) + NEW: STATS/STATS2

import os, re, json, logging, decimal, base64
from typing import Any, Dict, List, Optional, Tuple

from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import mysql.connector
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError
from contextlib import closing
from datetime import datetime, timedelta, date

load_dotenv()
STRICT_PRECHECK = str(os.getenv("STRICT_PRECHECK", "0")).strip().lower() in ("1", "true", "yes")
app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/": {"origins": ""}})

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[logging.FileHandler("yppi019_service.log"), logging.StreamHandler()],
)
logger = logging.getLogger(__name__)

HTTP_HOST = os.getenv("HTTP_HOST", "127.0.0.1")
HTTP_PORT = int(os.getenv("HTTP_PORT", "5005"))
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
    payload = {
        "ok": False,
        "error": "Sedang diproses oleh user lain. Coba lagi sebentar.",
        "error_code": "AUFNR_LOCKED",
        "resource": {"type": resource_type, "id": resource_id},
    }
    resp = jsonify(payload)
    resp.status_code = 423
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
        except Exception:
            pass
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
    except Exception:
        pass
    raise ValueError("SAP credentials not found (headers or JSON).")

# ---------------- Utils ----------------
def parse_num(x: Any) -> Optional[float]:
    if x is None or x == "": return None
    if isinstance(x, (int, float, decimal.Decimal)): return float(x)
    s = str(x).strip()
    if s.count(",") > 0 and s.count(".") == 0:
        s = s.replace(".", "").replace(",", ".")
    else:
        s = s.replace(",", "")
    try: return float(s)
    except Exception: return None

def parse_date(v: Any) -> Optional[str]:
    if not v:
        return None
    if isinstance(v, (date, datetime)):
        return v.strftime("%Y-%m-%d")

    s = str(v).strip()
    # dd.mm.yyyy -> yyyy-mm-dd
    m = re.match(r"^(\d{2})\.(\d{2})\.(\d{4})$", s)
    if m:
        dd, mm, yyyy = m.groups()
        return f"{yyyy}-{mm}-{dd}"

    # yyyymmdd -> yyyy-mm-dd
    m2 = re.match(r"^(\d{4})(\d{2})(\d{2})$", s)
    if m2:
        yyyy, mm, dd = m2.groups()
        return f"{yyyy}-{mm}-{dd}"

    # yyyy-mm-dd (atau bentuk lain) biarkan
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
    if isinstance(o, (date, datetime)): return o.isoformat()
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
    with closing(connect_mysql()) as db:
        with closing(db.cursor()) as cur:
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
              MAKTX0 VARCHAR(200) NULL,
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
              -- ✅ NEW RFC FIELD:
              STATS  VARCHAR(40) NULL,
              STATS2 VARCHAR(40) NULL,
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

            # --- Lightweight migration idempotent (kolom lama) ---
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
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN MAKTX0 VARCHAR(200) NULL AFTER MATNR0")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD KEY idx_matnr0 (MATNR0)")
            except Exception:
                pass

            # ✅ NEW: add STATS & STATS2 if missing (idempotent)
            try:
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN STATS VARCHAR(40) NULL AFTER LTIMEX")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD COLUMN STATS2 VARCHAR(40) NULL AFTER STATS")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD KEY idx_stats (STATS)")
            except Exception:
                pass
            try:
                cur.execute("ALTER TABLE yppi019_data ADD KEY idx_stats2 (STATS2)")
            except Exception:
                pass

            db.commit()

def map_tdata1_row(r: Dict[str, Any]) -> Dict[str, Any]:
    return {
        "AUFNR": pad_aufnr(r.get("AUFNR")),
        "VORNRX": pad_vornr(r.get("VORNRX") or r.get("VORNR") or ""),
        "PERNR": (str(r.get("PERNR") or "").strip() or None),
        "ARBPL0": (r.get("ARBPL0") or r.get("ARBPL") or None),
        "DISPO": r.get("DISPO"),
        "STEUS": r.get("STEUS"),
        "WERKS": r.get("WERKS"),
        "KDAUF": (r.get("KDAUF") or None),
        "KDPOS": (pad_kdpos(r.get("KDPOS")) or None),
        "CHARG": (str(r.get("CHARG") or "").strip()),
        "MATNRX": r.get("MATNRX"),
        "MAKTX": r.get("MAKTX"),
        "MATNR0": r.get("MATNR0"),
        "MAKTX0": r.get("MAKTX0"),
        "MEINH": r.get("MEINH"),
        "QTY_SPK": parse_num(r.get("QTY_SPK")),
        "WEMNG": parse_num(r.get("WEMNG")),
        "QTY_SPX": parse_num(r.get("QTY_SPX")),
        "LTXA1": r.get("LTXA1"),
        "SNAME": r.get("SNAME"),
        "GSTRP": parse_date(r.get("GSTRP")),
        "GLTRP": parse_date(r.get("GLTRP")),
        "SSAVD": parse_date(r.get("SSAVD")),
        "SSSLD": parse_date(r.get("SSSLD")),
        "LTIME":  parse_num(r.get("LTIME")),
        "LTIMEX": parse_num(r.get("LTIMEX")),
        "STATS":  (r.get("STATS") or None),
        "STATS2": (r.get("STATS2") or None),
        "ISDZ": r.get("ISDZ"),
        "IEDZ": r.get("IEDZ"),
        "RAW_JSON": json.dumps(to_jsonable(r), ensure_ascii=False),
        "fetched_at": datetime.now(),
    }

# ---------------- READ from SAP & sync ----------------
def fetch_from_sap(sap: Connection, aufnr: Optional[str], pernr: Optional[str], arbpl: Optional[str], werks: Optional[str]) -> List[Dict[str, Any]]:
    def _call(args):
        logger.info("Calling %s with %s", RFC_Y, args)
        res = sap.call(RFC_Y, **args)
        rows = [map_tdata1_row(r) for r in (res.get("T_DATA1", []) or [])]
        ret = res.get("RETURN") or res.get("T_MESSAGES") or []
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

    rows = _call(args)
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
    ensure_tables()

    # --- Pastikan kolom baru ada (migrasi ringan, idempotent) ---
    try:
        with closing(connect_mysql()) as _db_mig:
            with closing(_db_mig.cursor()) as _cur_mig:
                try:
                    _cur_mig.execute("ALTER TABLE yppi019_data ADD COLUMN MATNR0 VARCHAR(40) NULL AFTER MATNRX")
                except Exception:
                    pass
                try:
                    _cur_mig.execute("ALTER TABLE yppi019_data ADD COLUMN MAKTX0 VARCHAR(200) NULL AFTER MAKTX")
                except Exception:
                    pass
                # ✅ NEW: STATS & STATS2
                try:
                    _cur_mig.execute("ALTER TABLE yppi019_data ADD COLUMN STATS VARCHAR(40) NULL AFTER LTIMEX")
                except Exception:
                    pass
                try:
                    _cur_mig.execute("ALTER TABLE yppi019_data ADD COLUMN STATS2 VARCHAR(40) NULL AFTER STATS")
                except Exception:
                    pass
            _db_mig.commit()
    except Exception:
        logger.exception("Lightweight migration for MATNR0/MAKTX0/STATS failed (ignored)")

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

        # --- Tambahkan MATNR0/MAKTX0 ke setiap row (kalau ada di SAP/RAW_JSON) ---
        for r in rows:
            if r.get("MATNR0") is None or r.get("MATNR0") == "":
                try:
                    raw = json.loads(r.get("RAW_JSON") or "{}")
                    r["MATNR0"] = (raw.get("MATNR0") or raw.get("MATNR") or raw.get("MATNRX") or None)
                except Exception:
                    r.setdefault("MATNR0", None)
            if r.get("MAKTX0") is None or r.get("MAKTX0") == "":
                try:
                    raw = json.loads(r.get("RAW_JSON") or "{}")
                    r["MAKTX0"] = (raw.get("MAKTX0") or raw.get("MAKTX") or None)
                except Exception:
                    r.setdefault("MAKTX0", None)

            # ✅ Pastikan key STATS/STATS2 selalu ada (biar executemany aman)
            r.setdefault("STATS", None)
            r.setdefault("STATS2", None)

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

                    cur_main.execute("SELECT COUNT(*) FROM yppi019_data WHERE AUFNR=%s", (a,))
                    prev_count = (cur_main.fetchone() or [0])[0]

                    if arbpl and werks:
                        cur_main.execute(
                            "DELETE FROM yppi019_data WHERE AUFNR=%s AND ARBPL0=%s AND WERKS=%s",
                            (a, arbpl, werks),
                        )
                    else:
                        cur_main.execute("DELETE FROM yppi019_data WHERE AUFNR=%s", (a,))
                    wiped = cur_main.rowcount

                    cur_main.executemany(
                        """
INSERT INTO yppi019_data
 (AUFNR,VORNRX,PERNR,ARBPL0,DISPO,STEUS,WERKS,
  KDAUF,KDPOS,
  CHARG,MATNRX,MAKTX,MEINH,
  QTY_SPK,WEMNG,QTY_SPX,LTXA1,SNAME,
  GSTRP,GLTRP,SSAVD,SSSLD,LTIME,LTIMEX,
  STATS,STATS2,
  ISDZ,IEDZ,RAW_JSON,fetched_at,
  MATNR0,MAKTX0)
VALUES
 (%(AUFNR)s,%(VORNRX)s,%(PERNR)s,%(ARBPL0)s,%(DISPO)s,%(STEUS)s,%(WERKS)s,
  %(KDAUF)s,%(KDPOS)s,
  %(CHARG)s,%(MATNRX)s,%(MAKTX)s,%(MEINH)s,
  %(QTY_SPK)s,%(WEMNG)s,%(QTY_SPX)s,%(LTXA1)s,%(SNAME)s,
  %(GSTRP)s,%(GLTRP)s,%(SSAVD)s,%(SSSLD)s,%(LTIME)s,%(LTIMEX)s,
  %(STATS)s,%(STATS2)s,
  %(ISDZ)s,%(IEDZ)s,%(RAW_JSON)s,%(fetched_at)s,
  %(MATNR0)s,%(MAKTX0)s)
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
  STATS=VALUES(STATS),
  STATS2=VALUES(STATS2),
  ISDZ=VALUES(ISDZ),
  IEDZ=VALUES(IEDZ),
  RAW_JSON=VALUES(RAW_JSON),
  fetched_at=VALUES(fetched_at),
  MATNR0=VALUES(MATNR0),
  MAKTX0=VALUES(MAKTX0),
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
            "note": "replaced with fresh data from SAP (per-AUFNR locked, with MATNR0/MAKTX0 + STATS/STATS2)",
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
        conn = connect_sap(u, p)
        try:
            conn.ping()
        finally:
            conn.close()
        return jsonify({"status": "connected"}), 200
    except ValueError as ve:
        return jsonify({"error": str(ve)}), 401
    except Exception as e:
        logger.exception("SAP login failed")
        return jsonify({"error": str(e)}), 401

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

    where_parts, args = [], []
    for k, v in params.items():
        if v not in (None, ""):
            where_parts.append(f"{k}=%s")
            args.append(v)

    where_parts.append("(IFNULL(QTY_SPX, 0) > 0 AND IFNULL(WEMNG, 0) < IFNULL(QTY_SPK, 0))")
    where_sql = " WHERE " + " AND ".join(where_parts) if where_parts else ""

    if params.get("AUFNR") and not params.get("ARBPL0"):
        order_sql = " ORDER BY VORNRX ASC"
    else:
        order_sql = " ORDER BY AUFNR ASC, VORNRX ASC"

    base_from = "FROM yppi019_data"
    data_sql  = f"SELECT * {base_from}{where_sql}{order_sql}"
    count_sql = f"SELECT COUNT(*) AS total {base_from}{where_sql}"

    with closing(connect_mysql()) as db:
        with closing(db.cursor(dictionary=True)) as cur:
            cur.execute(count_sql, tuple(args))
            total = int((cur.fetchone() or {"total": 0})["total"])

            cur.execute(data_sql, tuple(args))
            rows = cur.fetchall() or []

    return jsonify({
        "ok": True,
        "filters": {k: v for k, v in params.items() if v not in (None, "")},
        "total": total,
        "returned": len(rows),
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

    if not res.get("ok") and res.get("error_code") == "AUFNR_LOCKED":
        res["refreshed"] = False
        return jsonify(to_jsonable(res)), 423

    status = 200 if res.get("ok") else 500
    res["refreshed"] = bool(res.get("ok"))
    return jsonify(to_jsonable(res)), status

# --- helper kecil (taruh sekali saja di file ini, di atas route) ---
def _is_message_table(rows) -> bool:
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
    try:
        username, password = get_credentials_from_request()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    def parse_aufnr_list_from_query():
        vals = []
        vals += request.args.getlist("aufnr")
        vals += request.args.getlist("aufnrs")
        vals += request.args.getlist("pro")

        out = []
        for v in vals:
            for tok in re.split(r"[\s,]+", (v or "").strip()):
                a = pad_aufnr(tok)
                if a:
                    out.append(a)

        seen = set()
        uniq = []
        for a in out:
            if a not in seen:
                seen.add(a)
                uniq.append(a)
        return uniq

    pernr = (request.args.get("pernr") or "").strip()
    budat = (request.args.get("budat") or "").strip().replace("-", "")
    dispo = (request.args.get("dispo") or "").strip()
    werks = (request.args.get("werks") or "").strip()

    aufnr_list = parse_aufnr_list_from_query()

    if aufnr_list:
        sap = None
        try:
            sap = connect_sap(username, password)

            merged_rows = []
            all_msgs = []

            for a in aufnr_list:
                args = {"P_AUFNR": a}
                logger.info("Calling Z_FM_YPPR062 (PRO MULTI) with %s", args)
                res = sap.call("Z_FM_YPPR062", **args)

                rows = res.get("T_DATA1", []) or []
                msgs = res.get("RETURN", []) or []
                all_msgs += [m for m in msgs if isinstance(m, dict)]

                err = next((m for m in msgs if str(m.get("TYPE", "")).upper() in ("E", "A")), None)
                if err:
                    return jsonify({
                        "ok": False,
                        "error": err.get("MESSAGE") or str(err),
                        "messages": to_jsonable(all_msgs),
                        "mode": "AUFNR_MULTI",
                        "aufnrs": aufnr_list,
                    }), 502

                if _is_message_table(rows):
                    rows = []
                merged_rows.extend(rows)

            return jsonify({
                "ok": True,
                "mode": "AUFNR_MULTI",
                "aufnrs": aufnr_list,
                "rows": to_jsonable(merged_rows),
                "count": len(merged_rows),
                "messages": to_jsonable(all_msgs),
            }), 200

        except (ABAPApplicationError, ABAPRuntimeError, CommunicationError, LogonError) as e:
            return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
        except Exception as e:
            logger.exception("Error api_hasil (AUFNR_MULTI)")
            return jsonify({"ok": False, "error": str(e)}), 500
        finally:
            if sap:
                sap.close()

    if not re.match(r"^\d{8}$", budat):
        return jsonify({"ok": False, "error": "param budat(YYYYMMDD) wajib"}), 400

    has_pernr = bool(pernr)
    has_mrp   = bool(dispo and werks)
    if not has_pernr and not has_mrp:
        return jsonify({"ok": False, "error": "Isi pernr ATAU pilih dispo+werks"}), 400

    sap = None
    try:
        sap = connect_sap(username, password)

        args = {"P_BUDAT": budat}
        if has_pernr:
            args["P_PERNR"] = pernr
        if has_mrp:
            args["P_DISPO"] = dispo
            args["P_WERKS"] = werks

        logger.info("Calling Z_FM_YPPR062 (BUDAT) with %s", args)
        res = sap.call("Z_FM_YPPR062", **args)

        rows = res.get("T_DATA1", []) or []
        messages = res.get("RETURN", []) or []

        err = next((m for m in messages if str(m.get("TYPE", "")).upper() in ("E", "A")), None)
        if err:
            msg = err.get("MESSAGE") or str(err)
            return jsonify({"ok": False, "error": msg, "messages": to_jsonable(messages)}), 502

        if _is_message_table(rows):
            rows = []

        return jsonify({
            "ok": True,
            "mode": "BUDAT",
            "rows": to_jsonable(rows),
            "messages": to_jsonable(messages),
            "count": len(rows),
        }), 200

    except (ABAPApplicationError, ABAPRuntimeError, CommunicationError, LogonError) as e:
        return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
    except Exception as e:
        logger.exception("Error api_hasil (BUDAT)")
        return jsonify({"ok": False, "error": str(e)}), 500
    finally:
        if sap:
            sap.close()

def _ymd_clean(s: str) -> str:
    return re.sub(r'\D', '', (s or '').strip())

@app.get("/api/yppi019/hasil-range")
def api_hasil_range():
    try:
        username, password = get_credentials_from_request()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    pernr = (request.args.get("pernr") or "").strip()
    frm   = _ymd_clean(request.args.get("from") or request.args.get("budat_from") or request.args.get("date_from") or "")
    to    = _ymd_clean(request.args.get("to")   or request.args.get("budat_to")   or request.args.get("date_to")   or "")
    aufnr = (request.args.get("aufnr") or "").strip()
    dispo = (request.args.get("dispo") or "").strip()
    werks = (request.args.get("werks") or "").strip()

    if aufnr:
        sap = None
        try:
            sap = connect_sap(username, password)
            args = {"P_AUFNR": pad_aufnr(aufnr)}
            logger.info("Calling Z_FM_YPPR062 (PRO via hasil-range) with %s", args)
            res = sap.call("Z_FM_YPPR062", **args)

            rows = res.get("T_DATA1", []) or []
            msgs = res.get("RETURN", []) or []

            err = next((m for m in msgs if str(m.get("TYPE","")).upper() in ("E","A")), None)
            if err:
                return jsonify({"ok": False, "error": err.get("MESSAGE") or str(err), "messages": to_jsonable(msgs)}), 502

            if _is_message_table(rows):
                rows = []

            return jsonify({
                "ok": True,
                "mode": "AUFNR",
                "count": len(rows),
                "rows": to_jsonable(rows),
                "messages": to_jsonable(msgs),
            }), 200

        except (ABAPApplicationError, ABAPRuntimeError, CommunicationError, LogonError) as e:
            return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
        except Exception as e:
            logger.exception("Error api_hasil_range (PRO)")
            return jsonify({"ok": False, "error": str(e)}), 500
        finally:
            if sap:
                sap.close()

    if not re.match(r"^\d{8}$", frm) or not re.match(r"^\d{8}$", to):
        return jsonify({"ok": False, "error": "param from/to(YYYYMMDD) wajib"}), 400

    has_pernr = bool(pernr)
    has_mrp   = bool(dispo and werks)
    if not has_pernr and not has_mrp:
        return jsonify({"ok": False, "error": "Isi pernr ATAU pilih dispo+werks"}), 400

    d1 = datetime.strptime(frm, "%Y%m%d").date()
    d2 = datetime.strptime(to,  "%Y%m%d").date()
    if d1 > d2:
        d1, d2 = d2, d1

    MAX_DAYS = int(os.getenv("HASIL_RANGE_MAX_DAYS", "62"))
    span = (d2 - d1).days + 1
    if span > MAX_DAYS:
        return jsonify({"ok": False, "error": f"Rentang terlalu panjang (> {MAX_DAYS} hari)"}), 413

    sap = None
    all_rows: List[Dict[str, Any]] = []
    all_msgs: List[Dict[str, Any]] = []

    try:
        sap = connect_sap(username, password)
        cur = d1
        while cur <= d2:
            ymd = cur.strftime("%Y%m%d")
            args = {"P_BUDAT": ymd}

            if has_pernr:
                args["P_PERNR"] = pernr
            if has_mrp:
                args["P_DISPO"] = dispo
                args["P_WERKS"] = werks

            if aufnr:
                args["P_AUFNR"] = pad_aufnr(aufnr)

            res = sap.call("Z_FM_YPPR062", **args)
            rows = res.get("T_DATA1", []) or []
            msgs = res.get("RETURN", []) or []
            all_msgs += [m for m in msgs if isinstance(m, dict)]

            err = next((m for m in msgs if str(m.get("TYPE","")).upper() in ("E","A")), None)
            if err:
                return jsonify({"ok": False, "error": err.get("MESSAGE") or str(err), "messages": to_jsonable(all_msgs)}), 502

            if _is_message_table(rows):
                rows = []

            for r in rows:
                try:
                    r["BUDAT"] = ymd
                except Exception:
                    pass

            all_rows.extend(rows)
            cur += timedelta(days=1)

        return jsonify({
            "ok": True,
            "from": d1.strftime("%Y%m%d"),
            "to":   d2.strftime("%Y%m%d"),
            "count": len(all_rows),
            "rows": to_jsonable(all_rows),
            "messages": to_jsonable(all_msgs),
        }), 200

    except (ABAPApplicationError, ABAPRuntimeError, CommunicationError, LogonError) as e:
        return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
    except Exception as e:
        logger.exception("Error api_hasil_range")
        return jsonify({"ok": False, "error": str(e)}), 500
    finally:
        if sap:
            sap.close()

# ---------------- Confirm & Other Endpoints ----------------

def _is_bad_000(msg: str) -> bool:
    # contoh msg jelek: "00000000000000000001 confirmations are incorrect..."
    return (msg or "").strip().startswith("000")


def pick_best_sap_message(sap_ret: Any) -> str:
    if not isinstance(sap_ret, dict):
        return ""

    # pesan yang lebih manusiawi biasanya ada di EV_MSG
    ev_msg = str(sap_ret.get("EV_MSG") or "").strip()

    def ok(msg: Any) -> Optional[str]:
        s = str(msg or "").strip()
        if not s:
            return None
        # kalau message diawali 000... dan EV_MSG ada -> pakai EV_MSG
        if _is_bad_000(s) and ev_msg:
            return ev_msg
        return s

    # 1) prioritas: DETAIL_RETURN error dulu
    det = sap_ret.get("DETAIL_RETURN")
    if isinstance(det, list):
        for m in det:
            if isinstance(m, dict) and str(m.get("TYPE","")).upper() in ("E","A") and m.get("MESSAGE"):
                return ok(m.get("MESSAGE")) or ""
        # kalau ga ada error, ambil message pertama yang ada
        for m in det:
            if isinstance(m, dict) and m.get("MESSAGE"):
                return ok(m.get("MESSAGE")) or ""

    # 2) cari table message lain (RETURN/ET_RETURN/apa pun)
    for _, v in sap_ret.items():
        if isinstance(v, list) and v and isinstance(v[0], dict):
            for m in v:
                t = str(m.get("TYPE","")).upper()
                if t in ("E","A") and m.get("MESSAGE"):
                    return ok(m.get("MESSAGE")) or ""
            for m in v:
                if isinstance(m, dict) and m.get("MESSAGE"):
                    return ok(m.get("MESSAGE")) or ""

    # 3) fallback key umum
    for key in ("EV_MSG", "MESSAGE", "MSG", "EV_MESSAGE", "EV_TEXT"):
        out = ok(sap_ret.get(key))
        if out:
            return out

    return ""

def fetch_one_op_from_sap(
    username: str,
    password: str,
    aufnr: str,
    vornr: str,
    pernr: str = "",
    charg: str = "",
    arbpl0: str = "",
    werks: str = "",
) -> Optional[Dict[str, Any]]:
    sap = None
    try:
        sap = connect_sap(username, password)

        rows = fetch_from_sap(
            sap,
            aufnr=aufnr,
            pernr=(pernr or None),
            arbpl=(arbpl0 or None),
            werks=(werks or None),
        )

        cand: List[Dict[str, Any]] = []
        for r in rows:
            if pad_aufnr(r.get("AUFNR")) != pad_aufnr(aufnr):
                continue
            if pad_vornr(r.get("VORNRX") or r.get("VORNR")) != pad_vornr(vornr):
                continue
            if charg and str(r.get("CHARG") or "").strip() != charg:
                continue
            if arbpl0 and str(r.get("ARBPL0") or "").strip() != arbpl0:
                continue
            cand.append(r)

        if not cand:
            return None
        if len(cand) > 1:
            raise RuntimeError("SAP row tidak unik. Sertakan CHARG/ARBPL0.")
        return cand[0]

    finally:
        if sap:
            sap.close()


# route (DECORATOR HARUS DI SINI)
@app.post("/api/yppi019/confirm")
def api_confirm():
    try:
        u, p = get_credentials_from_request()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    b = request.get_json(force=True) or {}

    # --- normalize input (WAJIB) ---
    aufnr = pad_aufnr(b.get("aufnr"))
    vornr = pad_vornr(b.get("vornr"))
    pernr = (str(b.get("pernr") or "").strip())
    budat_raw = str(b.get("budat") or "").strip()
    budat = re.sub(r"\D", "", budat_raw)  # pastikan YYYYMMDD untuk SAP
    qty_in = parse_num(b.get("psmng"))

    wi_mode = bool(b.get("wi_mode"))

    if not aufnr or not vornr or not pernr or not budat or qty_in is None or qty_in <= 0:
        return jsonify({"ok": False, "error": "Parameter tidak valid atau psmng <= 0"}), 400
    if not re.match(r"^\d{8}$", budat):
        return jsonify({"ok": False, "error": "budat wajib format YYYYMMDD"}), 400

    db = None
    cur = None

    try:
        db = connect_mysql()
        # buffered=True supaya aman kalau ada fetch/nextset dll
        cur = db.cursor(dictionary=True, buffered=True)

        coarse_key = f"aufnr:{aufnr}"
        fine_key   = f"aufnr:{aufnr}:vornr:{vornr}"

        try:
            acquire_mutex(cur, coarse_key, timeout=8)
        except RuntimeError:
            return locked_response("AUFNR", aufnr)

        try:
            acquire_mutex(cur, fine_key, timeout=8)
        except RuntimeError:
            release_mutex(cur, coarse_key)
            return locked_response("AUFNR+VORNR", f"{aufnr}/{vornr}")

        sap_ret = None
        latest_row = None

        try:
            db.start_transaction()

            charg  = (str(b.get("charg") or "").strip())
            arbpl0 = (str(b.get("arbpl0") or b.get("arbpl") or "").strip())

            # --- find row local (kalau ada) ---
            where = ["AUFNR=%s", "VORNRX=%s"]
            args  = [aufnr, vornr]
            if charg:
                where.append("CHARG=%s");  args.append(charg)
            if arbpl0:
                where.append("ARBPL0=%s"); args.append(arbpl0)

            cur.execute(f"""
                SELECT id, QTY_SPK, WEMNG, QTY_SPX, MEINH, WERKS, ARBPL0, CHARG
                FROM yppi019_data
                WHERE {" AND ".join(where)}
                FOR UPDATE
            """, tuple(args))
            rows = cur.fetchall() or []

            row_db = None
            if rows:
                if len(rows) > 1 and not (charg or arbpl0):
                    db.rollback()
                    return jsonify({"ok": False, "error": "Data tidak unik (>1 baris). Sertakan CHARG/ARBPL0."}), 409
                row_db = rows[0]
            else:
                if not wi_mode:
                    db.rollback()
                    return jsonify({"ok": False, "error": "Data operation tidak ditemukan (silakan sync/refresh)"}), 404

            # --- tentukan UOM ---
            if row_db is not None:
                meinh_req = normalize_uom(b.get("meinh") or row_db.get("MEINH"))
            else:
                meinh_req = normalize_uom(b.get("meinh"))

            if not meinh_req:
                db.rollback()
                return jsonify({"ok": False, "error": "MEINH tidak ditemukan"}), 400

            # --- qty rules: untuk unit integer jangan di-round (WAJIB) ---
            integer_uoms = {"PC", "EA", "PCS", "UNIT"}
            qty_final = float(qty_in)

            if meinh_req in integer_uoms:
                if abs(qty_final - int(qty_final)) > 1e-9:
                    db.rollback()
                    return jsonify({"ok": False, "error": f"Untuk MEINH {meinh_req}, qty harus bilangan bulat."}), 400
                qty_final = float(int(qty_final))
                psmng_str = str(int(qty_final))
            else:
                qty_final = float(f"{qty_final:.3f}")
                psmng_str = f"{qty_final:.3f}".replace(".", ",")

            # --- VALIDASI pakai snapshot local, tapi kalau mismatch → refresh ke SAP ---
            if row_db is not None:
                qty_spk = float(row_db.get("QTY_SPK") or 0.0)
                wemng   = float(row_db.get("WEMNG")   or 0.0)
                qty_spx = float(row_db.get("QTY_SPX") or 0.0)

                sisa_spk = max(qty_spk - wemng, 0.0)
                sisa_spx = max(qty_spx, 0.0)

                need_refresh = (qty_final > sisa_spk) or (qty_final > sisa_spx) or bool(b.get("force_refresh"))

                if need_refresh:
                    sap_row = fetch_one_op_from_sap(
                        u, p,
                        aufnr=aufnr,
                        vornr=vornr,
                        pernr=pernr,
                        charg=(charg or str(row_db.get("CHARG") or "").strip()),
                        arbpl0=(arbpl0 or str(row_db.get("ARBPL0") or "").strip()),
                        werks=str(row_db.get("WERKS") or ""),
                    )

                    if sap_row:
                        qty_spk = float(sap_row.get("QTY_SPK") or 0.0)
                        wemng   = float(sap_row.get("WEMNG")   or 0.0)
                        qty_spx = float(sap_row.get("QTY_SPX") or 0.0)
                        meinh_s = normalize_uom(sap_row.get("MEINH") or row_db.get("MEINH"))

                        cur.execute(
                            """
                            UPDATE yppi019_data
                            SET QTY_SPK=%s, WEMNG=%s, QTY_SPX=%s, MEINH=%s, fetched_at=NOW()
                            WHERE id=%s
                            """,
                            (qty_spk, wemng, qty_spx, meinh_s, row_db["id"]),
                        )

                        sisa_spk = max(qty_spk - wemng, 0.0)
                        sisa_spx = max(qty_spx, 0.0)

                ex_spk = qty_final > sisa_spk
                ex_spx = qty_final > sisa_spx

                if (ex_spk or ex_spx) and STRICT_PRECHECK:
                    db.rollback()
                    return jsonify({
                        "ok": False,
                        "error": (
                            f"Input melebihi sisa. SPK_sisa={sisa_spk}, SPX_sisa={sisa_spx}"
                        ),
                        "debug": {
                            "qty_input": qty_final,
                            "sisa_spk": sisa_spk,
                            "sisa_spx": sisa_spx,
                            "source": "precheck",
                        }
                    }), 400

                if ex_spk or ex_spx:
                    logger.warning(
                        "PRECHECK exceeded but allowed (STRICT_PRECHECK=0). qty=%s sisa_spk=%s sisa_spx=%s aufnr=%s vornr=%s",
                        qty_final, sisa_spk, sisa_spx, aufnr, vornr
                    )

            # --- CALL SAP CONFIRM ---
            sap = None
            try:
                sap = connect_sap(u, p)
                sap_ret = sap.call(
                    RFC_C,
                    IV_AUFNR=aufnr,
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
                        for _, v in ret.items():
                            if isinstance(v, list):
                                msgs += [x for x in v if isinstance(x, dict)]
                    return msgs

                msgs = _collect_msgs(sap_ret)
                best_msg = pick_best_sap_message(sap_ret)
                err = next((m for m in msgs if str(m.get("TYPE", "")).upper() in ("E", "A")), None)

                if err:
                    try:
                        sap.call("BAPI_TRANSACTION_ROLLBACK")
                    except Exception:
                        pass
                    db.rollback()
                    return jsonify({
                        "ok": False,
                        "error": best_msg or "SAP returned error",
                        "sap_return": to_jsonable(sap_ret),
                    }), 500

                # commit SAP
                sap.call("BAPI_TRANSACTION_COMMIT", WAIT="X")

            except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
                db.rollback()
                logger.exception("SAP error api_confirm")
                return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
            finally:
                if sap:
                    sap.close()

            # --- LOG confirm (pakai qty_final, bukan qty_in) ---
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
                    qty_final,
                    meinh_req,
                    parse_date(b.get("gstrp")),
                    parse_date(b.get("gltrp")),
                    parse_date(budat),  # yyyymmdd -> yyyy-mm-dd
                    json.dumps(to_jsonable(sap_ret), ensure_ascii=False),
                    datetime.now(),
                ),
            )

            # --- AFTER COMMIT: refresh lagi dari SAP lalu SET DB = SAP (anti mismatch cancel) ---
            if row_db is not None:
                sap_row2 = fetch_one_op_from_sap(
                    u, p,
                    aufnr=aufnr,
                    vornr=vornr,
                    pernr=pernr,
                    charg=(charg or str(row_db.get("CHARG") or "").strip()),
                    arbpl0=(arbpl0 or str(row_db.get("ARBPL0") or "").strip()),
                    werks=str(row_db.get("WERKS") or ""),
                )

                if sap_row2:
                    cur.execute(
                        """
                        UPDATE yppi019_data
                        SET QTY_SPK=%s, WEMNG=%s, QTY_SPX=%s, MEINH=%s, fetched_at=NOW()
                        WHERE id=%s
                        """,
                        (
                            float(sap_row2.get("QTY_SPK") or 0.0),
                            float(sap_row2.get("WEMNG")   or 0.0),
                            float(sap_row2.get("QTY_SPX") or 0.0),
                            normalize_uom(sap_row2.get("MEINH") or row_db.get("MEINH")),
                            row_db["id"],
                        ),
                    )
                else:
                    # fallback: kalau SAP row ga ketemu (rare), minimal update aritmatika lokal
                    cur.execute(
                        """
                        UPDATE yppi019_data
                        SET WEMNG = IFNULL(WEMNG,0) + %s,
                            QTY_SPX = GREATEST(IFNULL(QTY_SPX,0) - %s, 0),
                            fetched_at = NOW()
                        WHERE id=%s
                        """,
                        (qty_final, qty_final, row_db["id"]),
                    )

                # cleanup log
                if CONFIRM_LOG_KEEP_DAYS and CONFIRM_LOG_KEEP_DAYS > 0:
                    cur.execute(
                        "DELETE FROM yppi019_confirm_log WHERE created_at < (NOW() - INTERVAL %s DAY)",
                        (CONFIRM_LOG_KEEP_DAYS,),
                    )
                else:
                    cur.execute("DELETE FROM yppi019_confirm_log WHERE DATE(created_at) < CURDATE()")

                db.commit()

                cur.execute("SELECT * FROM yppi019_data WHERE id=%s", (row_db["id"],))
                latest_row = cur.fetchone()

            else:
                # wi_mode tanpa local row
                if CONFIRM_LOG_KEEP_DAYS and CONFIRM_LOG_KEEP_DAYS > 0:
                    cur.execute(
                        "DELETE FROM yppi019_confirm_log WHERE created_at < (NOW() - INTERVAL %s DAY)",
                        (CONFIRM_LOG_KEEP_DAYS,),
                    )
                else:
                    cur.execute("DELETE FROM yppi019_confirm_log WHERE DATE(created_at) < CURDATE()")
                db.commit()

        finally:
            try:
                release_mutex(cur, fine_key)
            finally:
                release_mutex(cur, coarse_key)

        return jsonify({
            "ok": True,
            "sap_return": to_jsonable(sap_ret),
            "row": to_jsonable(latest_row),
        }), 200

    except Exception as e:
        logger.exception("Error api_confirm")
        try:
            if db:
                db.rollback()
        except Exception:
            pass
        return jsonify({"ok": False, "error": str(e)}), 500

    finally:
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

    if maktx is None or not maktx or arbpl0 is None or not arbpl0:
        try:
            with closing(connect_mysql()) as _db, closing(_db.cursor(dictionary=True)) as _cur:
                _cur.execute(
                    """
                    SELECT
                        COALESCE(MAKTX, MAKTX0) AS NAME,
                        ARBPL0
                    FROM yppi019_data
                    WHERE AUFNR=%s AND (%s IS NULL OR VORNRX=%s)
                    ORDER BY fetched_at DESC
                    LIMIT 1
                    """,
                    (aufnr, vornr, vornr),
                )
                snap = _cur.fetchone()
                if snap:
                    if (maktx is None or not maktx) and snap.get("NAME"):
                        maktx = str(snap["NAME"]).strip() or None
                    if (arbpl0 is None or not arbpl0) and snap.get("ARBPL0"):
                        arbpl0 = str(snap["ARBPL0"]).strip() or None
        except Exception:
            pass

    db = None
    cur = None
    try:
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
        db.commit()
        return jsonify({"ok": True}), 200

    except Exception as e:
        logger.exception("Error api_backdate_log")
        try:
            if db:
                db.rollback()
        except Exception:
            pass
        return jsonify({"ok": False, "error": str(e)}), 500

    finally:
        if cur:
            cur.close()
        if db:
            db.close()

@app.get("/api/yppi019/backdate-history")
def api_backdate_history():
    ensure_tables()
    pernr = (request.args.get("pernr") or "").strip()
    aufnr = (request.args.get("aufnr") or "").strip()
    limit = request.args.get("limit", type=int)
    order = (request.args.get("order") or "desc").lower()
    order_sql = "ASC" if order == "asc" else "DESC"

    db = None
    cur = None
    try:
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
        sql += f" ORDER BY COALESCE(CONFIRMED_AT, CREATED_AT) {order_sql}"
        if isinstance(limit, int) and limit > 0:
            sql += " LIMIT %s"
            args.append(limit)

        cur.execute(sql, tuple(args))
        rows = cur.fetchall()
        return jsonify({"ok": True, "rows": to_jsonable(rows)}), 200

    except Exception as e:
        logger.exception("Error api_backdate_history")
        return jsonify({"ok": False, "error": str(e)}), 500

    finally:
        if cur:
            cur.close()
        if db:
            db.close()

if __name__ == "__main__":
    ensure_tables()
    app.run(host=HTTP_HOST, port=HTTP_PORT, debug=False, use_reloader=False, threaded=True)        