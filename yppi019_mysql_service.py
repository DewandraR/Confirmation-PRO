# api.py — yppi019_mysql_service (versi diperbaiki)
import os, re, json, logging, decimal, datetime
from typing import Any, Dict, List, Optional, Tuple

from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import mysql.connector
from mysql.connector import errorcode
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError

load_dotenv()
app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/*": {"origins": "*"}})

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[logging.FileHandler("yppi019_service.log"), logging.StreamHandler()],
)
logger = logging.getLogger(__name__)

HTTP_HOST = os.getenv("HTTP_HOST", "127.0.0.1")
HTTP_PORT = int(os.getenv("HTTP_PORT", "5051"))
RFC_Y = "Z_FM_YPPI019"      # READ
RFC_C = "Z_RFC_CONFIRMASI"  # CONFIRM

# ---------------- MySQL ----------------
def connect_mysql():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_DATABASE", "yppi019"),
        port=int(os.getenv("DB_PORT", "3306")),
    )

# ---------------- SAP ----------------
def connect_sap(username: Optional[str] = None, password: Optional[str] = None) -> Connection:
    user = (username or os.getenv("SAP_USERNAME", "auto_email")).strip()
    passwd = (password or os.getenv("SAP_PASSWORD", "11223344")).strip()
    ashost = (os.getenv("SAP_ASHOST", "192.168.254.154") or "").strip()
    sysnr = (os.getenv("SAP_SYSNR", "01") or "").strip()
    client = (os.getenv("SAP_CLIENT", "300") or "").strip()
    lang = (os.getenv("SAP_LANG", "EN") or "").strip()
    missing = [k for k, v in {"user":user,"passwd":passwd,"SAP_ASHOST":ashost,"SAP_SYSNR":sysnr,"SAP_CLIENT":client,"SAP_LANG":lang}.items() if not v]
    if missing:
        raise ValueError(f"SAP logon fields empty: {', '.join(missing)}. Check .env")
    logger.info("SAP connect -> ashost=%s sysnr=%s client=%s lang=%s user=%s", ashost, sysnr, client, lang, user)
    return Connection(user=user, passwd=passwd, ashost=ashost, sysnr=sysnr, client=client, lang=lang)

def get_credentials_from_headers() -> Tuple[str, str]:
    u = request.headers.get("X-SAP-Username")
    p = request.headers.get("X-SAP-Password")
    if not u or not p:
        raise ValueError("SAP credentials not found in headers.")
    return u, p

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
    if isinstance(v, (datetime.date, datetime.datetime)):
        return v.strftime("%Y-%m-%d")
    s = str(v).strip()
    m = re.match(r"^(\d{2})\.(\d{2})\.(\d{4})$", s)
    if m: d,mn,y = m.groups(); return f"{y}-{mn}-{d}"
    m2 = re.match(r"^(\d{4})(\d{2})(\d{2})$", s)
    if m2: y,mn,d = m2.groups(); return f"{y}-{mn}-{d}"
    return s

def pad_vornr(v: Any) -> str:
    s = str(v or "").strip()
    if not s: return ""
    try: return f"{int(float(s)):04d}"
    except Exception: return s.zfill(4)

# AUFNR 12 digit numeric
def pad_aufnr(v: Any) -> str:
    s = re.sub(r"\D", "", str(v or ""))
    return s.zfill(12) if s else ""

def fmt_qty_str(x: Any, decimals: int = 3, trim_zeros: bool = True) -> str:
    if x is None or x == "": return "0"
    sx = str(x)
    if sx.count(",") > 0 and sx.count(".") == 0:
        sx = sx.replace(".", "").replace(",", ".")
    try:
        q = float(sx)
        s = f"{q:.{decimals}f}"
        return s.rstrip("0").rstrip(".") if trim_zeros else s
    except Exception:
        return str(x)

def to_jsonable(o: Any) -> Any:
    if o is None: return None
    if isinstance(o, (str,int,float,bool)): return o
    if isinstance(o, decimal.Decimal): return float(o)
    if isinstance(o, (datetime.date, datetime.datetime)): return o.isoformat()
    if isinstance(o, dict): return {k: to_jsonable(v) for k,v in o.items()}
    if isinstance(o, (list,tuple,set)): return [to_jsonable(x) for x in o]
    return str(o)

def normalize_uom(meinh: Any) -> str:
    u = str(meinh or "").strip().upper()
    return "PC" if u == "ST" else u

# ---------------- DDL ----------------
DDL_DATA = """
CREATE TABLE IF NOT EXISTS yppi019_data (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  AUFNR VARCHAR(20) NOT NULL,
  VORNRX VARCHAR(10) NULL,
  PERNR VARCHAR(20) NULL,
  ARBPL0 VARCHAR(40) NULL,
  DISPO VARCHAR(10) NULL,
  STEUS VARCHAR(8) NULL,
  WERKS VARCHAR(10) NULL,
  CHARG VARCHAR(20) NULL,
  MATNRX VARCHAR(40) NULL,
  MAKTX VARCHAR(200) NULL,
  MEINH VARCHAR(10) NULL,
  QTY_SPK DECIMAL(18,3) NULL,
  WEMNG DECIMAL(18,3) NULL,
  QTY_SPX DECIMAL(18,3) NULL,
  LTXA1 VARCHAR(200) NULL,
  SNAME VARCHAR(100) NULL,
  GSTRP DATE NULL,
  GLTRP DATE NULL,
  ISDZ VARCHAR(20) NULL,
  IEDZ VARCHAR(20) NULL,
  RAW_JSON JSON NOT NULL,
  fetched_at DATETIME NOT NULL,
  UNIQUE KEY uniq_key (AUFNR, VORNRX, CHARG, ARBPL0),
  KEY idx_aufnr (AUFNR),
  KEY idx_pernr (PERNR),
  KEY idx_arbpl (ARBPL0),
  KEY idx_steus (STEUS)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
"""
DDL_CONFIRM = """
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
"""

def ensure_tables():
    db = connect_mysql(); cur = db.cursor()
    try:
        cur.execute(DDL_DATA)
        cur.execute(DDL_CONFIRM)
        db.commit()
    finally:
        cur.close(); db.close()

# ---------------- Persist ----------------
UPSERT = """
INSERT INTO yppi019_data
 (AUFNR,VORNRX,PERNR,ARBPL0,DISPO,STEUS,WERKS,CHARG,MATNRX,MAKTX,MEINH,
  QTY_SPK,WEMNG,QTY_SPX,LTXA1,SNAME,GSTRP,GLTRP,ISDZ,IEDZ,RAW_JSON,fetched_at)
VALUES
 (%(AUFNR)s,%(VORNRX)s,%(PERNR)s,%(ARBPL0)s,%(DISPO)s,%(STEUS)s,%(WERKS)s,%(CHARG)s,%(MATNRX)s,%(MAKTX)s,%(MEINH)s,
  %(QTY_SPK)s,%(WEMNG)s,%(QTY_SPX)s,%(LTXA1)s,%(SNAME)s,%(GSTRP)s,%(GLTRP)s,%(ISDZ)s,%(IEDZ)s,%(RAW_JSON)s,%(fetched_at)s)
ON DUPLICATE KEY UPDATE
  PERNR  = VALUES(PERNR),
  ARBPL0 = VALUES(ARBPL0),
  DISPO  = VALUES(DISPO),
  STEUS  = VALUES(STEUS),
  WERKS  = VALUES(WERKS),
  CHARG  = VALUES(CHARG),
  MATNRX = VALUES(MATNRX),
  MAKTX  = VALUES(MAKTX),
  MEINH  = VALUES(MEINH),
  QTY_SPK = VALUES(QTY_SPK),
  WEMNG   = VALUES(WEMNG),
  QTY_SPX = CASE
              WHEN VALUES(QTY_SPX) IS NULL THEN QTY_SPX
              WHEN QTY_SPX IS NULL           THEN VALUES(QTY_SPX)
              ELSE LEAST(QTY_SPX, VALUES(QTY_SPX))
            END,
  LTXA1   = VALUES(LTXA1),
  SNAME   = VALUES(SNAME),
  GSTRP   = VALUES(GSTRP),
  GLTRP   = VALUES(GLTRP),
  ISDZ    = VALUES(ISDZ),
  IEDZ    = VALUES(IEDZ),
  RAW_JSON  = VALUES(RAW_JSON),
  fetched_at= VALUES(fetched_at)
"""

def map_tdata1_row(r: Dict[str, Any]) -> Dict[str, Any]:
    return {
        "AUFNR": (r.get("AUFNR") or "").strip(),
        "VORNRX": pad_vornr(r.get("VORNRX") or r.get("VORNR") or ""),
        "PERNR": (str(r.get("PERNR") or "").strip() or None),
        "ARBPL0": (r.get("ARBPL0") or r.get("ARBPL") or None),
        "DISPO": r.get("DISPO"),
        "STEUS": r.get("STEUS"),
        "WERKS": r.get("WERKS"),
        "CHARG": (str(r.get("CHARG") or "").strip()),
        "MATNRX": r.get("MATNRX"),
        "MAKTX": r.get("MAKTX"),
        "MEINH": r.get("MEINH"),
        "QTY_SPK": parse_num(r.get("QTY_SPK")),
        "WEMNG": parse_num(r.get("WEMNG")),
        "QTY_SPX": parse_num(r.get("QTY_SPX")),
        "LTXA1": r.get("LTXA1"),
        "SNAME": r.get("SNAME"),
        "GSTRP": parse_date(r.get("GSTRP")),
        "GLTRP": parse_date(r.get("GLTRP")),
        "ISDZ": r.get("ISDZ"),
        "IEDZ": r.get("IEDZ"),
        "RAW_JSON": json.dumps(to_jsonable(r), ensure_ascii=False),
        "fetched_at": datetime.datetime.now(),
    }

def save_rows(rows: List[Dict[str, Any]]) -> int:
    if not rows: return 0
    db = connect_mysql(); cur = db.cursor()
    try:
        cur.executemany(UPSERT, rows)
        db.commit()
        return cur.rowcount
    finally:
        cur.close(); db.close()

# ---------------- Owner helpers ----------------
def get_existing_pernr_for_aufnr(aufnr: str) -> Optional[str]:
    db = connect_mysql(); cur = db.cursor()
    try:
        cur.execute("""SELECT PERNR FROM yppi019_data
                         WHERE AUFNR=%s AND PERNR IS NOT NULL AND PERNR<>'' LIMIT 1""", (aufnr,))
        row = cur.fetchone()
        return str(row[0]) if row and row[0] is not None else None
    finally:
        try: cur.close()
        except: pass
        connect_mysql().close()

def ensure_owner_for_aufnr_if_empty(aufnr: str, pernr: str) -> None:
    db = connect_mysql(); cur = db.cursor()
    try:
        cur.execute("""SELECT COUNT(*) FROM yppi019_data
                         WHERE AUFNR=%s AND PERNR IS NOT NULL AND PERNR<>''""", (aufnr,))
        if (cur.fetchone() or [0])[0] > 0: return
        cur.execute("""UPDATE yppi019_data SET PERNR=%s
                         WHERE AUFNR=%s AND (PERNR IS NULL OR PERNR='')""", (pernr, aufnr))
        db.commit()
    finally:
        try: cur.close()
        except: pass
        db.close()

# ---------------- READ from SAP & save ----------------
def fetch_one_aufnr(sap: Connection, aufnr: str, pernr: Optional[str], arbpl: Optional[str]) -> List[Dict[str, Any]]:
    def _call(args):
        logger.info("Calling %s with %s", RFC_Y, args)
        res = sap.call(RFC_Y, **args)
        rows = [map_tdata1_row(r) for r in (res.get("T_DATA1", []) or [])]
        ret = res.get("RETURN") or res.get("T_MESSAGES") or []
        if ret:
            try: logger.info("RETURN/MESSAGES: %s", json.dumps(to_jsonable(ret), ensure_ascii=False))
            except Exception: logger.info("RETURN/MESSAGES: %s", str(ret))
        logger.info("Result %s: %d row(s)", RFC_Y, len(rows))
        return rows

    base = {"IV_AUFNR": pad_aufnr(aufnr)}
    combos: List[Dict[str, Any]] = []
    if pernr: combos.append({**base, "IV_PERNR": str(pernr)})
    if arbpl: combos.append({**base, "IV_ARBPL": str(arbpl)})
    combos.append(base)

    logger.info("Fetch combos tried (in order): %s", combos)
    rows: List[Dict[str, Any]] = []
    for args in combos:
        try:
            rows = _call(args)
            if rows: break
        except Exception:
            logger.exception("READ failed with args=%s", args)
            continue
    if pernr:
        for r in rows: r["PERNR"] = pernr
    return rows

def sync_from_sap(aufnr: str, username: Optional[str], password: Optional[str],
                  pernr: Optional[str] = None, arbpl: Optional[str] = None) -> Dict[str, Any]:
    ensure_tables()
    sap = None; db = None
    try:
        sap = connect_sap(username, password)
        rows = fetch_one_aufnr(sap, aufnr, pernr, arbpl)
        n_received = len(rows)

        db = connect_mysql(); cur = db.cursor()
        try:
            cur.execute("SELECT COUNT(*) FROM yppi019_data WHERE AUFNR=%s", (aufnr,))
            prev_count = (cur.fetchone() or [0])[0]
            cur.execute("DELETE FROM yppi019_data WHERE AUFNR=%s", (aufnr,))
            db.commit()
            wiped = cur.rowcount
        finally:
            try: cur.close()
            except: pass

        saved = 0
        if n_received > 0:
            saved = save_rows(rows)

        try:
            if db: db.close()
        except: pass

        return {"ok": True, "received": n_received, "saved": int(saved),
                "wiped": int(wiped), "prev_count": int(prev_count),
                "note": ("no data from SAP; local cleared" if n_received == 0 else "replaced with fresh data")}
    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
        logger.exception("SAP error sync_from_sap")
        return {"ok": False, "error": str(e)}
    except Exception as e:
        logger.exception("Error sync_from_sap")
        return {"ok": False, "error": str(e)}
    finally:
        if sap:
            try: sap.close()
            except: pass
        if db:
            try: db.close()
            except: pass

# ---------------- HTTP endpoints ----------------
@app.get("/")
def root():
    return ("OK - endpoints: GET /api/yppi019 | POST /api/yppi019/sync | "
            "POST /api/yppi019/sync_bulk | POST /api/yppi019/confirm | "
            "POST /api/yppi019/update-qty-spx", 200, {"Content-Type":"text/plain"})

@app.post("/api/yppi019/login")
def api_login():
    try:
        u,p = get_credentials_from_headers()
        c = connect_sap(u,p); c.ping()
        return jsonify({"status":"connected"})
    except ValueError as ve:
        return jsonify({"error":str(ve)}), 401
    except Exception as e:
        return jsonify({"error":str(e)}), 401

@app.get("/api/yppi019")
def api_get():
    ensure_tables()
    aufnr  = request.args.get("aufnr")
    vornrx = request.args.get("vornrx")
    charg  = request.args.get("charg")
    steus  = request.args.get("steus")
    pernr  = request.args.get("pernr")
    limit  = request.args.get("limit", type=int) or 100

    db = connect_mysql(); cur = db.cursor(dictionary=True)
    try:
        sql = "SELECT * FROM yppi019_data"
        cond, args = [], []
        if aufnr:  cond.append("AUFNR=%s");  args.append(aufnr)
        if vornrx: cond.append("VORNRX=%s"); args.append(vornrx)
        if charg:  cond.append("CHARG=%s");  args.append(charg)
        if steus:  cond.append("STEUS=%s");  args.append(steus)
        if pernr:  cond.append("PERNR=%s");  args.append(pernr)
        if cond: sql += " WHERE " + " AND ".join(cond)
        sql += " ORDER BY id DESC LIMIT %s"; args.append(limit)
        cur.execute(sql, tuple(args))
        return jsonify({"ok": True, "rows": to_jsonable(cur.fetchall())})
    finally:
        cur.close(); db.close()

@app.post("/api/yppi019/sync")
def api_sync():
    # 1) ambil kredensial SAP
    try:
        u, p = get_credentials_from_headers()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    # 2) ambil body
    body  = request.get_json(force=True) or {}
    aufnr = (body.get("aufnr") or "").strip()
    pernr = (body.get("pernr") or "").strip()
    arbpl = (body.get("arbpl") or "").strip() or None
    if not aufnr:
        return jsonify({"ok": False, "error": "aufnr wajib diisi"}), 400
    if not pernr:
        return jsonify({"ok": False, "error": "pernr wajib diisi"}), 400

    # 3) cek owner
    owner = get_existing_pernr_for_aufnr(aufnr)
    if owner and owner != pernr:
        return jsonify({
            "ok": False,
            "error": f"AUFNR {aufnr} sudah terdaftar oleh PERNR {owner}. Gunakan ID yang sama."
        }), 409

    # 4) tarik terbaru dari SAP
    res = sync_from_sap(aufnr, u, p, pernr, arbpl)

    # 4a) tandai kemungkinan TECO jika sukses tetapi tidak ada baris yang diterima
    if res.get("ok") and int(res.get("received", 0) or 0) == 0:
        res["teco_possible"] = True

    # 5) pastikan owner terisi kalau kosong
    try:
        ensure_owner_for_aufnr_if_empty(aufnr, pernr)
    except Exception:
        logger.exception("ensure_owner_for_aufnr_if_empty failed")

    # 6) response
    status = 200 if res.get("ok") else 500
    res["refreshed"] = bool(res.get("ok"))
    return jsonify(to_jsonable(res)), status


@app.post("/api/yppi019/sync_bulk")
def api_sync_bulk():
    try:
        u,p = get_credentials_from_headers()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401
    body         = request.get_json(force=True) or {}
    aufnr_list   = body.get("aufnr_list") or []
    pernr        = (body.get("pernr") or "").strip()
    arbpl        = (body.get("arbpl") or "").strip() or None
    if not isinstance(aufnr_list, list) or not aufnr_list:
        return jsonify({"ok": False, "error": "aufnr_list harus berupa list dan tidak boleh kosong"}), 400
    if not pernr:
        return jsonify({"ok": False, "error": "pernr wajib diisi"}), 400

    allowed, errors = [], {}
    db = connect_mysql(); cur = db.cursor()
    try:
        for auf in aufnr_list:
            cur.execute("""SELECT PERNR FROM yppi019_data
                             WHERE AUFNR=%s AND PERNR IS NOT NULL AND PERNR<>'' LIMIT 1""", (auf,))
            row = cur.fetchone()
            owner = str(row[0]) if row and row[0] is not None else None
            if owner and owner != pernr: errors[auf] = f"AUFNR {auf} sudah terdaftar oleh PERNR {owner}"
            else: allowed.append(auf)
    finally:
        try: cur.close()
        except: pass
        db.close()
    if not allowed: return jsonify({"ok": False, "errors": errors}), 409

    total_received = 0; total_saved = 0; succ = []
    sap = None
    try:
        sap = connect_sap(u,p)
        for auf in allowed:
            try:
                rows = fetch_one_aufnr(sap, auf, pernr, arbpl)
                received, saved = len(rows), (save_rows(rows) if rows else 0)
                total_received += received; total_saved += saved
                succ.append({"aufnr": auf, "received": received, "saved": saved})
            except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
                errors[auf] = str(e); logger.exception("SAP error AUFNR=%s", auf)
            except Exception as e:
                errors[auf] = str(e); logger.exception("Error AUFNR=%s", auf)
        ok = len(errors)==0; status = 200 if ok else 207
        return jsonify({"ok": ok, "received": total_received, "saved": total_saved,
                        "successes": to_jsonable(succ), "errors": errors}), status
    finally:
        if sap:
            try: sap.close()
            except: pass

def http_status_from_sap_error(msg: str) -> int:
    m = (msg or "").upper()
    if "SIGNON_INCOMPL" in m or "LOGON_FAILURE" in m or "NAME_OR_PASSWORD_INCORRECT" in m: return 401
    if "NOT AUTHORIZATION" in m: return 403
    return 500

# ---------------- Alur BARU: confirm (PATCHED) ----------------
@app.post("/api/yppi019/confirm")
def api_confirm():
    try:
        u, p = get_credentials_from_headers()
    except ValueError as ve:
        return jsonify({"ok": False, "error": str(ve)}), 401

    b      = request.get_json(force=True) or {}
    aufnr  = str(b.get("aufnr") or "").strip()
    vornr  = pad_vornr(b.get("vornr"))
    pernr  = str(b.get("pernr") or "").strip()
    budat  = str(b.get("budat") or "").strip()
    gstrp  = str(b.get("gstrp") or "").strip()
    gltrp  = str(b.get("gltrp") or "").strip()
    arbpl  = str(b.get("arbpl") or "").strip() or None
    qty_in = parse_num(b.get("psmng"))

    if not (aufnr and vornr and pernr and budat and qty_in is not None):
        return jsonify({"ok": False, "error": "aufnr, vornr, pernr, budat, psmng wajib"}), 400
    if qty_in <= 0:
        return jsonify({"ok": False, "error": "psmng harus > 0"}), 400

    v_padded = vornr
    try:
        v_stripped = str(int(vornr))
    except Exception:
        v_stripped = vornr.lstrip("0") or "0"

    # Ambil baris target
    db = connect_mysql(); cur = db.cursor(dictionary=True)
    try:
        cur.execute(
            """
            SELECT id, CHARG, QTY_SPK, WEMNG, QTY_SPX, MEINH
              FROM yppi019_data
             WHERE AUFNR=%s
               AND (VORNRX=%s OR VORNRX=%s)
             ORDER BY id DESC
             LIMIT 1
            """,
            (aufnr, v_padded, v_stripped)
        )
        row_db = cur.fetchone()
        if not row_db:
            return jsonify({"ok": False, "error": "Data operation tidak ditemukan (cek AUFNR/VORNR)"}), 404

        qty_spk  = float(row_db.get("QTY_SPK") or 0.0)
        wemng    = float(row_db.get("WEMNG")   or 0.0)
        qty_spx  = float(row_db.get("QTY_SPX") or 0.0)
        meinh_db = row_db.get("MEINH") or ""
        sisa_spk = max(qty_spk - wemng, 0.0)

        if qty_in > sisa_spk:
            return jsonify({"ok": False, "error": f"Input melebihi QTY_SPK sisa ({sisa_spk})."}), 400
        if qty_in > qty_spx:
            return jsonify({"ok": False, "error": f"Input melebihi QTY_SPX sisa ({qty_spx})."}), 400
    finally:
        cur.close(); db.close()

    meinh_req = normalize_uom(b.get("meinh") or meinh_db)

    # >>>>> PATCH UTAMA: format IV_PSMNG untuk SAP
    integer_units = {"PC", "EA", "PCS", "UNIT"}  # ST sudah dinormalisasi ke PC
    if meinh_req in integer_units:
        psmng_str = str(int(round(qty_in)))        # contoh: 1
    else:
        psmng_str = f"{qty_in:.3f}".replace(".", ",") # contoh M3: "0,500"
    logger.info("IV_PSMNG (final) -> %s %s", psmng_str, meinh_req)
    # <<<<< END PATCH

    sap = None
    try:
        sap = connect_sap(u, p)
        sap_ret = sap.call(
            RFC_C,
            IV_AUFNR=pad_aufnr(aufnr),
            IV_VORNR=v_padded,
            IV_PERNR=pernr,
            IV_PSMNG=psmng_str,
            IV_MEINH=meinh_req,
            IV_GSTRP=gstrp,
            IV_GLTRP=gltrp,
            IV_BUDAT=budat,
        )

        db = connect_mysql(); cur = db.cursor()
        try:
            cur.execute(
                "INSERT INTO yppi019_confirm_log (AUFNR,VORNR,PERNR,PSMNG,MEINH,GSTRP,GLTRP,BUDAT,SAP_RETURN,created_at) "
                "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
                (
                    # ======================================================================================================
                    # PERUBAHAN: Menggunakan `qty_in` (float) untuk kolom PSMNG di DB, bukan `psmng_str` (string format SAP).
                    # ======================================================================================================
                    aufnr, v_padded, pernr, qty_in, meinh_req,
                    parse_date(gstrp), parse_date(gltrp), parse_date(budat),
                    json.dumps(to_jsonable(sap_ret), ensure_ascii=False),
                    datetime.datetime.now(),
                ),
            )
            db.commit()
        finally:
            try: cur.close()
            except: pass
            try: db.close()
            except: pass

        db = connect_mysql(); cur = db.cursor(dictionary=True)
        try:
            cur.execute(
                """
                UPDATE yppi019_data
                   SET WEMNG    = IFNULL(WEMNG,0) + %s,
                       QTY_SPX  = GREATEST(IFNULL(QTY_SPX,0) - %s, 0),
                       fetched_at = NOW()
                 WHERE id=%s
                """,
                (qty_in, qty_in, row_db["id"])
            )
            db.commit()
            cur.execute("SELECT * FROM yppi019_data WHERE id=%s", (row_db["id"],))
            latest_row = cur.fetchone()
        finally:
            try: cur.close()
            except: pass
            try: db.close()
            except: pass

        return jsonify({
            "ok": True,
            "sap_return": to_jsonable(sap_ret),
            "refreshed": {"ok": True, "mode": "local_update"},
            "row": to_jsonable(latest_row)
        }), 200

    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
        logger.exception("SAP error api_confirm")
        return jsonify({"ok": False, "error": str(e)}), http_status_from_sap_error(str(e))
    except Exception as e:
        logger.exception("Error api_confirm")
        return jsonify({"ok": False, "error": str(e)}), 500
    finally:
        if sap:
            try: sap.close()
            except: pass

# ---------------- Edit manual QTY_SPX ----------------
@app.post("/api/yppi019/update_qty_spx")
def api_update_qty_spx():
    ensure_tables()
    b = request.get_json(force=True) or {}
    aufnr  = (b.get("aufnr") or "").strip()
    vornrx = (b.get("vornrx") or "").strip()
    charg  = (b.get("charg") or "").strip()
    qty    = parse_num(b.get("qty_spx"))
    if not (aufnr and vornrx and charg and qty is not None):
        return jsonify({"ok": False, "error": "aufnr, vornrx, charg, qty_spx wajib"}), 400
    db = connect_mysql(); cur = db.cursor()
    try:
        cur.execute("""UPDATE yppi019_data SET QTY_SPX=%s
                         WHERE AUFNR=%s AND VORNRX=%s AND CHARG=%s LIMIT 1""",
                    (qty, aufnr, vornrx, charg))
        db.commit()
        return jsonify({"ok": True, "updated": cur.rowcount})
    finally:
        cur.close(); db.close()

# alias dengan dash
app.add_url_rule("/api/yppi019/update-qty-spx", view_func=api_update_qty_spx, methods=["POST"])

# ---------------- Main ----------------
if __name__ == "__main__":
    ensure_tables()
    app.run(host=HTTP_HOST, port=HTTP_PORT, debug=True)