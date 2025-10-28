# yppi019wc.py — API khusus Work Center (ARBPL + WERKS)

import os, re, json, logging, decimal, datetime, base64
from typing import Any, Dict, List, Optional, Tuple

from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv

import mysql.connector
from contextlib import closing

# pyrfc & error classes
try:
    from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError
except Exception:  # fallback dev tanpa pyrfc
    class Connection:
        def __init__(self, **kw): ...
        def call(self, *a, **kw): return {}
        def close(self): ...
    class ABAPApplicationError(Exception): ...
    class ABAPRuntimeError(Exception): ...
    class LogonError(Exception): ...
    class CommunicationError(Exception): ...

load_dotenv()

app = Flask(__name__)
CORS(app, supports_credentials=True)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[logging.FileHandler("yppi019wc_service.log"), logging.StreamHandler()],
)
logger = logging.getLogger(__name__)

HTTP_HOST = os.getenv("HTTP_HOST", "127.0.0.1")
HTTP_PORT = int(os.getenv("HTTP_PORT", "5037"))

# RFC names (samakan dengan landscape Anda)
RFC_Y = os.getenv("RFC_Y_READ", "Z_FM_YPPI019")       # READ
RFC_C = os.getenv("RFC_Y_CONFIRM", "Z_RFC_CONFIRMASI")  # CONFIRM

# ---------------- MySQL ----------------
def connect_mysql():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", "singgampang"),
        database=os.getenv("DB_DATABASE", "yppi019"),
        port=int(os.getenv("DB_PORT", "3306")),
    )

# -------- Advisory lock helpers --------
def _fetch_scalar(cur):
    row = cur.fetchone()
    if row is None:
        val = None
    else:
        val = next(iter(row.values())) if isinstance(row, dict) else row[0]
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
    cur.execute("SELECT GET_LOCK(%s, %s)", (f"yppi019:{key}", timeout))
    got = _fetch_scalar(cur)
    try:
        got = int(got)
    except (TypeError, ValueError):
        got = 0
    if got != 1:
        raise RuntimeError(f"Resource busy for key={key}")

def release_mutex(cur, key: str):
    try:
        cur.execute("SELECT RELEASE_LOCK(%s)", (f"yppi019:{key}",))
        _fetch_scalar(cur)
    except Exception:
        pass

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
        raise ValueError("Missing SAP username/password (headers atau JSON).")
    ashost = (os.getenv("SAP_ASHOST", "192.168.254.154") or "").strip()
    sysnr  = (os.getenv("SAP_SYSNR", "01") or "").strip()
    client = (os.getenv("SAP_CLIENT", "300") or "").strip()
    lang   = (os.getenv("SAP_LANG", "EN") or "").strip()
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
    raise ValueError("SAP credentials not found (headers atau JSON).")

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
    if not v: return None
    if isinstance(v, (datetime.date, datetime.datetime)): return v.strftime("%Y-%m-%d")
    s = str(v).strip()
    if m := re.match(r"^(\d{2})\.(\d{2})\.(\d{4})$", s): return f"{m.groups()[2]}-{m.groups()[1]}-{m.groups()[0]}"
    if m2 := re.match(r"^(\d{4})(\d{2})(\d{2})$", s): return f"{m2.groups()[0]}-{m2.groups()[1]}-{m2.groups()[2]}"
    if m3 := re.match(r"^(\d{2})/(\d{2})/(\d{4})$", s): return f"{m3.groups()[2]}-{m3.groups()[1]}-{m3.groups()[0]}"
    return s

def pad_vornr(v: Any) -> str:
    s = str(v or "").strip()
    if not s: return ""
    try: return f"{int(float(s)):04d}"
    except Exception: return s.zfill(4)

def pad_aufnr(v: Any) -> str:
    s = re.sub(r"\D", "", str(v or ""))
    return s.zfill(12) if s else ""

def pad_kdpos(v: Any) -> str:
    s = str(v or "").strip()
    if not s: return ""
    try: return f"{int(float(s)):06d}"
    except Exception: return s.zfill(6)

def to_jsonable(o: Any) -> Any:
    if isinstance(o, (str, int, float, bool, type(None))): return o
    if isinstance(o, decimal.Decimal): return float(o)
    if isinstance(o, (datetime.date, datetime.datetime)): return o.isoformat()
    if isinstance(o, dict): return {k: to_jsonable(v) for k, v in o.items()}
    if isinstance(o, (list, tuple, set)): return [to_jsonable(x) for x in o]
    return str(o)

def normalize_uom(meinh: Any) -> str:
    u = str(meinh or "").strip().upper()
    return "PC" if u in {"ST", "EA", "PCS", "UNIT"} else u

def humanize_rfc_error(err: Exception) -> str:
    s = str(err or "")
    m = re.search(r'message=([^[]+)', s)
    if m: return m.group(1).strip()
    m2 = re.search(r'MESSAGE\s*[:=]\s*([^\n]+)', s, flags=re.IGNORECASE)
    if m2: return m2.group(1).strip()
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
              KDAUF VARCHAR(20) NULL,
              KDPOS VARCHAR(10) NULL,
              CHARG VARCHAR(20) NULL,
              MATNRX VARCHAR(40) NULL,
              MAKTX VARCHAR(200) NULL,
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
              id BIGINT AUTO_INCREMENT PRIMARY KEY,
              AUFNR VARCHAR(20) NOT NULL,
              VORNR VARCHAR(10) NULL,
              PERNR VARCHAR(20) NULL,
              PSMNG DECIMAL(18,3) NULL,
              MEINH VARCHAR(10) NULL,
              GSTRP DATE NULL,
              GLTRP DATE NULL,
              BUDAT DATE NULL,
              SAP_RETURN JSON NULL,
              created_at DATETIME NOT NULL,
              KEY idx_aufnr (AUFNR)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            """)
            db.commit()

# Map row SAP → schema lokal
def map_tdata1_row(r: Dict[str, Any]) -> Dict[str, Any]:
    return {
        "AUFNR": (r.get("AUFNR") or "").strip(),
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
        "ISDZ": r.get("ISDZ"),
        "IEDZ": r.get("IEDZ"),
        "RAW_JSON": json.dumps(to_jsonable(r), ensure_ascii=False),
        "fetched_at": datetime.datetime.now(),
    }

# ---------------- READ from SAP & sync (WC mode) ----------------
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

    if not (arbpl and werks):
        logger.warning("WC-mode: IV_ARBPL dan IV_WERKS wajib.")
        return []
    args = {"IV_ARBPL": str(arbpl), "IV_WERKS": str(werks)}
    if aufnr:
        args["IV_AUFNR"] = pad_aufnr(aufnr)
    if pernr:
        args["IV_PERNR"] = str(pernr)

    rows = _call(args)
    if pernr:
        for r in rows:
            if not r.get("PERNR"):
                r["PERNR"] = pernr
    return rows

def sync_from_sap(username: Optional[str], password: Optional[str],
                  aufnr: Optional[str] = None, pernr: Optional[str] = None,
                  arbpl: Optional[str] = None, werks: Optional[str] = None) -> Dict[str, Any]:

    ensure_tables()
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
            return {"ok": True, "received": 0, "saved": 0, "wiped": 0, "prev_count": 0, "note": "no data from SAP; local data untouched"}

        # Kelompokkan per AUFNR
        by_aufnr: Dict[str, List[Dict[str, Any]]] = {}
        for rr in rows:
            a = (rr.get("AUFNR") or "").strip()
            if not a:
                continue
            by_aufnr.setdefault(a, []).append(rr)

        # Purge AUFNR stale untuk kombinasi ARBPL0+WERKS+PERNR ini
        if arbpl and werks:
            db_select = connect_mysql()
            cur_select = db_select.cursor()
            try:
                cur_select.execute(
                    "SELECT DISTINCT AUFNR FROM yppi019_data WHERE ARBPL0=%s AND WERKS=%s AND IFNULL(PERNR,'')=%s",
                    (arbpl, werks, pernr or "")
                )
                existing = {row[0] for row in cur_select.fetchall()}
            finally:
                cur_select.close(); db_select.close()
            now_have = set(by_aufnr.keys())
            stale = sorted(existing - now_have)
            if stale:
                db_stale = connect_mysql()
                cur_stale = db_stale.cursor()
                try:
                    for a in stale:
                        try:
                            acquire_mutex(cur_stale, f"aufnr:{a}", timeout=2)
                        except RuntimeError:
                            continue
                        try:
                            db_stale.start_transaction()
                            cur_stale.execute(
                                "DELETE FROM yppi019_data WHERE AUFNR=%s AND ARBPL0=%s AND WERKS=%s AND IFNULL(PERNR,'')=%s",
                                (a, arbpl, werks, pernr or "")
                            )
                            db_stale.commit()
                        except Exception:
                            db_stale.rollback()
                        finally:
                            release_mutex(cur_stale, f"aufnr:{a}")
                finally:
                    cur_stale.close(); db_stale.close()

        saved_total = wiped_total = prev_total = 0

        # Simpan per AUFNR (advisory lock)
        db_main = connect_mysql()
        cur_main = db_main.cursor()
        try:
            for a in sorted(by_aufnr.keys()):
                try:
                    acquire_mutex(cur_main, f"aufnr:{a}", timeout=10)
                except RuntimeError:
                    return {"ok": False, "error": "Sedang diproses oleh user lain. Coba lagi sebentar.", "error_code": "AUFNR_LOCKED", "busy_aufnr": a}
                try:
                    db_main.start_transaction()

                    # hitung snapshot lama
                    cur_main.execute("SELECT COUNT(*) FROM yppi019_data WHERE AUFNR=%s AND ARBPL0=%s AND WERKS=%s", (a, arbpl, werks))
                    prev_count = (cur_main.fetchone() or [0])[0]

                    # clear snapshot lama untuk kombinasi ARBPL0+WERKS
                    cur_main.execute("DELETE FROM yppi019_data WHERE AUFNR=%s AND ARBPL0=%s AND WERKS=%s", (a, arbpl, werks))
                    wiped = cur_main.rowcount

                    # upsert fresh rows
                    cur_main.executemany(
                        """
INSERT INTO yppi019_data
 (AUFNR,VORNRX,PERNR,ARBPL0,DISPO,STEUS,WERKS,
  KDAUF,KDPOS,CHARG,MATNRX,MAKTX,MEINH,
  QTY_SPK,WEMNG,QTY_SPX,LTXA1,SNAME,
  GSTRP,GLTRP,SSAVD,SSSLD,LTIME,LTIMEX,
  ISDZ,IEDZ,RAW_JSON,fetched_at,
  MATNR0,MAKTX0)
VALUES
 (%(AUFNR)s,%(VORNRX)s,%(PERNR)s,%(ARBPL0)s,%(DISPO)s,%(STEUS)s,%(WERKS)s,
  %(KDAUF)s,%(KDPOS)s,%(CHARG)s,%(MATNRX)s,%(MAKTX)s,%(MEINH)s,
  %(QTY_SPK)s,%(WEMNG)s,%(QTY_SPX)s,%(LTXA1)s,%(SNAME)s,
  %(GSTRP)s,%(GLTRP)s,%(SSAVD)s,%(SSSLD)s,%(LTIME)s,%(LTIMEX)s,
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
            cur_main.close(); db_main.close()

        return {
            "ok": True,
            "received": n_received,
            "saved": saved_total,
            "wiped": wiped_total,
            "prev_count": prev_total,
            "note": "WC sync OK (per-AUFNR locked, ARBPL0+WERKS scoped)",
        }

    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
        logger.exception("SAP error in sync_from_sap (WC)")
        return {"ok": False, "error": humanize_rfc_error(e)}
    except Exception as e:
        logger.exception("Generic error in sync_from_sap (WC)")
        return {"ok": False, "error": str(e)}

# ---------------- HTTP endpoints (WC) ----------------
@app.get("/")
def root():
    return ("OK - WC endpoints: /api/yppi019wc/login, /api/yppi019wc/sync, /api/yppi019wc, /api/yppi019wc/confirm", 200, {"Content-Type":"text/plain"})

@app.post("/api/yppi019wc/login")
@app.post("/api/sap-login-wc")
def sap_login_wc():
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
        logger.exception("SAP login (WC) failed")
        return jsonify({"error": str(e)}), 401

@app.get("/api/yppi019wc")
def api_get_yppi019_wc():
    """
    GET /api/yppi019wc?arbpl=...&werks=...[&aufnr=...][&vornrx=...][&charg=...][&steus=...][&pernr=...]
    Wajib: arbpl, werks. Selalu kembalikan semua baris yang match (tanpa LIMIT)
    Syarat stok: (QTY_SPX > 0) dan (WEMNG < QTY_SPK)
    """
    ensure_tables()
    get = request.args.get
    arbpl = (get("arbpl") or "").strip()
    werks = (get("werks") or "").strip()
    if not (arbpl and werks):
        return jsonify({"ok": False, "error": "arbpl dan werks wajib"}), 400

    params = {
        "AUFNR":  get("aufnr"),
        "VORNRX": get("vornrx"),
        "CHARG":  get("charg"),
        "STEUS":  get("steus"),
        "PERNR":  get("pernr"),
        "ARBPL0": arbpl,
        "WERKS":  werks,
    }

    where_parts, args = [], []
    for k, v in params.items():
        if v not in (None, ""):
            where_parts.append(f"{k}=%s")
            args.append(v)

    where_parts.append("(IFNULL(QTY_SPX,0) > 0 AND IFNULL(WEMNG,0) < IFNULL(QTY_SPK,0))")
    where_sql = " WHERE " + " AND ".join(where_parts)

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

@app.post("/api/yppi019wc/sync")
def api_sync_wc():
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
    if not (arbpl and werks):
        return jsonify({"ok": False, "error": "arbpl dan werks wajib diisi untuk endpoint WC"}), 400

    res = sync_from_sap(u, p, aufnr=aufnr or None, pernr=pernr, arbpl=arbpl, werks=werks)

    if res.get("ok") and int(res.get("received") or 0) == 0:
        res["refreshed"] = False
        res.setdefault("message", "Data Tidak Ditemukan")
        return jsonify(to_jsonable(res)), 404

    if not res.get("ok") and res.get("error_code") == "AUFNR_LOCKED":
        res["refreshed"] = False
        return jsonify(to_jsonable(res)), 423

    status = 200 if res.get("ok") else 500
    res["refreshed"] = bool(res.get("ok"))
    return jsonify(to_jsonable(res)), status

@app.post("/api/yppi019wc/confirm")
def api_confirm_wc():
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
    arbpl0 = (str(b.get("arbpl0") or b.get("arbpl") or "").strip())
    werks  = (str(b.get("werks") or "").strip())

    if not (arbpl0 and werks):
        return jsonify({"ok": False, "error": "arbpl dan werks wajib diisi untuk confirm WC"}), 400
    if not (aufnr and vornr and pernr and budat and qty_in is not None and qty_in > 0):
        return jsonify({"ok": False, "error": "Parameter tidak valid atau psmng <= 0"}), 400

    db = None; cur = None
    try:
        db = connect_mysql(); cur = db.cursor(dictionary=True, buffered=True)
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

        sap_ret = None; latest_row = None
        try:
            db.start_transaction()
            charg = (str(b.get("charg")  or "").strip())

            where = ["AUFNR=%s", "VORNRX=%s", "ARBPL0=%s", "WERKS=%s"]
            args  = [aufnr, vornr, arbpl0, werks]
            if charg:
                where.append("CHARG=%s");  args.append(charg)

            cur.execute(f"""
                SELECT id, QTY_SPK, WEMNG, QTY_SPX, MEINH
                FROM yppi019_data
                WHERE {" AND ".join(where)}
                FOR UPDATE
            """, tuple(args))
            rows = cur.fetchall()

            if not rows:
                db.rollback()
                return jsonify({"ok": False, "error": "Data operation tidak ditemukan (WC)"}), 404
            if len(rows) > 1 and not charg:
                db.rollback()
                return jsonify({"ok": False, "error": "Data tidak unik (>1 baris). Sertakan CHARG."}), 409

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

            sap = None
            try:
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
                try:
                    commit_ret = sap.call("BAPI_TRANSACTION_COMMIT", WAIT="X")
                    logger.info("BAPI_TRANSACTION_COMMIT result: %s", commit_ret)
                except Exception as ce:
                    db.rollback()
                    logger.exception("Failed to commit SAP transaction")
                    return jsonify({"ok": False, "error": f"Gagal COMMIT di SAP: {str(ce)}"}), 500
            except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
                db.rollback()
                logger.exception("SAP error api_confirm_wc")
                return jsonify({"ok": False, "error": humanize_rfc_error(e)}), 500
            finally:
                try:
                    if sap: sap.close()
                except Exception:
                    pass

            cur.execute("""
                INSERT INTO yppi019_confirm_log
                (AUFNR, VORNR, PERNR, PSMNG, MEINH, GSTRP, GLTRP, BUDAT, SAP_RETURN, created_at)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
            """, (
                aufnr, vornr, pernr, qty_in, meinh_req,
                parse_date(b.get("gstrp")), parse_date(b.get("gltrp")), parse_date(budat),
                json.dumps(to_jsonable(sap_ret), ensure_ascii=False),
                datetime.datetime.now()
            ))

            cur.execute("""
                UPDATE yppi019_data
                SET WEMNG=IFNULL(WEMNG,0)+%s,
                    QTY_SPX=GREATEST(IFNULL(QTY_SPX,0)-%s,0),
                    fetched_at=NOW()
                WHERE id=%s
                  AND (IFNULL(QTY_SPK,0)-IFNULL(WEMNG,0)) >= %s
                  AND IFNULL(QTY_SPX,0) >= %s
            """, (qty_in, qty_in, row_db["id"], qty_in, qty_in))

            if cur.rowcount != 1:
                db.rollback()
                return jsonify({"ok": False, "error": "Gagal update; sisa sudah berubah, silakan refresh"}), 409

            db.commit()
            cur.execute("SELECT * FROM yppi019_data WHERE id=%s", (row_db["id"],))
            latest_row = cur.fetchone()

        finally:
            try:
                release_mutex(cur, fine_key)
            finally:
                release_mutex(cur, coarse_key)

        return jsonify({"ok": True, "sap_return": to_jsonable(sap_ret),
                        "refreshed": {"ok": True, "mode": "local_update"},
                        "row": to_jsonable(latest_row)}), 200

    except Exception as e:
        logger.exception("Error api_confirm_wc")
        try:
            if db: db.rollback()
        except Exception:
            pass
        return jsonify({"ok": False, "error": str(e)}), 500
    finally:
        try:
            if cur: cur.close()
        except Exception:
            pass
        try:
            if db: db.close()
        except Exception:
            pass

if __name__ == "__main__":
    ensure_tables()
    app.run(host=HTTP_HOST, port=HTTP_PORT, debug=False, use_reloader=False, threaded=True)
