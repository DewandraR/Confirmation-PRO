# api.py — yppi019_mysql_service (force per-user SAP credential from request)
import os, re, json, logging, decimal, datetime, base64
from typing import Any, Dict, List, Optional, Tuple

from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import mysql.connector
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
HTTP_PORT = int(os.getenv("HTTP_PORT", "5035"))
RFC_Y = "Z_FM_YPPI019"      # READ
RFC_C = "Z_RFC_CONFIRMASI"  # CONFIRM

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
    if isinstance(v, (datetime.date, datetime.datetime)): return v.strftime("%Y-%m-%d")
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

# ---------------- DDL & Persist ----------------
def ensure_tables():
    with connect_mysql() as db:
        with db.cursor() as cur:
            cur.execute("""
            CREATE TABLE IF NOT EXISTS yppi019_data (
              id BIGINT AUTO_INCREMENT PRIMARY KEY, AUFNR VARCHAR(20) NOT NULL, VORNRX VARCHAR(10) NULL,
              PERNR VARCHAR(20) NULL, ARBPL0 VARCHAR(40) NULL, DISPO VARCHAR(10) NULL, STEUS VARCHAR(8) NULL,
              WERKS VARCHAR(10) NULL, CHARG VARCHAR(20) NULL, MATNRX VARCHAR(40) NULL, MAKTX VARCHAR(200) NULL,
              MEINH VARCHAR(10) NULL, QTY_SPK DECIMAL(18,3) NULL, WEMNG DECIMAL(18,3) NULL, QTY_SPX DECIMAL(18,3) NULL,
              LTXA1 VARCHAR(200) NULL, SNAME VARCHAR(100) NULL, GSTRP DATE NULL, GLTRP DATE NULL,
              ISDZ VARCHAR(20) NULL, IEDZ VARCHAR(20) NULL, RAW_JSON JSON NOT NULL, fetched_at DATETIME NOT NULL,
              UNIQUE KEY uniq_key (AUFNR, VORNRX, CHARG, ARBPL0), KEY idx_aufnr (AUFNR), KEY idx_pernr (PERNR),
              KEY idx_arbpl (ARBPL0), KEY idx_steus (STEUS), KEY idx_werks (WERKS)
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

            # ▼▼▼ TAMBAHKAN BLOK BARU INI ▼▼▼
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
            # ▲▲▲ END BLOK BARU ▲▲▲

            db.commit()

def map_tdata1_row(r: Dict[str, Any]) -> Dict[str, Any]:
    return {"AUFNR": (r.get("AUFNR") or "").strip(), "VORNRX": pad_vornr(r.get("VORNRX") or r.get("VORNR") or ""),"PERNR": (str(r.get("PERNR") or "").strip() or None),"ARBPL0": (r.get("ARBPL0") or r.get("ARBPL") or None),"DISPO": r.get("DISPO"), "STEUS": r.get("STEUS"), "WERKS": r.get("WERKS"),"CHARG": (str(r.get("CHARG") or "").strip()), "MATNRX": r.get("MATNRX"), "MAKTX": r.get("MAKTX"),"MEINH": r.get("MEINH"), "QTY_SPK": parse_num(r.get("QTY_SPK")), "WEMNG": parse_num(r.get("WEMNG")),"QTY_SPX": parse_num(r.get("QTY_SPX")), "LTXA1": r.get("LTXA1"), "SNAME": r.get("SNAME"),"GSTRP": parse_date(r.get("GSTRP")), "GLTRP": parse_date(r.get("GLTRP")), "ISDZ": r.get("ISDZ"), "IEDZ": r.get("IEDZ"),"RAW_JSON": json.dumps(to_jsonable(r), ensure_ascii=False), "fetched_at": datetime.datetime.now(),}

def save_rows(rows: List[Dict[str, Any]]) -> int:
    if not rows: return 0
    with connect_mysql() as db:
        with db.cursor() as cur:
            cur.executemany("""
            INSERT INTO yppi019_data
             (AUFNR,VORNRX,PERNR,ARBPL0,DISPO,STEUS,WERKS,CHARG,MATNRX,MAKTX,MEINH,QTY_SPK,WEMNG,QTY_SPX,
              LTXA1,SNAME,GSTRP,GLTRP,ISDZ,IEDZ,RAW_JSON,fetched_at)
            VALUES (%(AUFNR)s,%(VORNRX)s,%(PERNR)s,%(ARBPL0)s,%(DISPO)s,%(STEUS)s,%(WERKS)s,%(CHARG)s,%(MATNRX)s,%(MAKTX)s,%(MEINH)s,
              %(QTY_SPK)s,%(WEMNG)s,%(QTY_SPX)s,%(LTXA1)s,%(SNAME)s,%(GSTRP)s,%(GLTRP)s,%(ISDZ)s,%(IEDZ)s,%(RAW_JSON)s,%(fetched_at)s)
            ON DUPLICATE KEY UPDATE
              PERNR=VALUES(PERNR), ARBPL0=VALUES(ARBPL0), DISPO=VALUES(DISPO), STEUS=VALUES(STEUS), WERKS=VALUES(WERKS),
              CHARG=VALUES(CHARG), MATNRX=VALUES(MATNRX), MAKTX=VALUES(MAKTX), MEINH=VALUES(MEINH), QTY_SPK=VALUES(QTY_SPK),
              WEMNG=VALUES(WEMNG), LTXA1=VALUES(LTXA1), SNAME=VALUES(SNAME), GSTRP=VALUES(GSTRP), GLTRP=VALUES(GLTRP),
              ISDZ=VALUES(ISDZ), IEDZ=VALUES(IEDZ), RAW_JSON=VALUES(RAW_JSON), fetched_at=VALUES(fetched_at),
              QTY_SPX=CASE WHEN VALUES(QTY_SPX) IS NULL THEN QTY_SPX WHEN QTY_SPX IS NULL THEN VALUES(QTY_SPX) ELSE LEAST(QTY_SPX, VALUES(QTY_SPX)) END
            """, rows)
            db.commit()
            return cur.rowcount

# ---------------- READ from SAP & sync ----------------
def fetch_from_sap(sap: Connection, aufnr: Optional[str], pernr: Optional[str], arbpl: Optional[str], werks: Optional[str]) -> List[Dict[str, Any]]:
    def _call(args):
        logger.info("Calling %s with %s", RFC_Y, args)
        res = sap.call(RFC_Y, **args)
        rows = [map_tdata1_row(r) for r in (res.get("T_DATA1", []) or [])]
        ret = res.get("RETURN") or res.get("T_MESSAGES") or []
        if ret: logger.info("RETURN/MESSAGES: %s", to_jsonable(ret))
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

    logger.info("Final fetch call arguments: %s", args)
    try:
        rows = _call(args)
    except Exception as e:
        logger.exception("READ failed with args=%s", args)
        rows = []
    
    if pernr:
        for r in rows:
            if not r.get("PERNR"): r["PERNR"] = pernr
    return rows

def sync_from_sap(username: Optional[str], password: Optional[str],
                  aufnr: Optional[str] = None, pernr: Optional[str] = None, 
                  arbpl: Optional[str] = None, werks: Optional[str] = None) -> Dict[str, Any]:
    ensure_tables()
    try:
        with connect_sap(username, password) as sap:
            rows = fetch_from_sap(sap, aufnr, pernr, arbpl, werks)
            n_received = len(rows)

        wiped, prev_count, saved = 0, 0, 0
        note = "no data from SAP; local data untouched"

        if n_received > 0:
            # PERBAIKAN: Hapus data lokal berdasarkan kunci sinkronisasi (AUFNR atau ARBPL/WERKS)
            # Ini memastikan data yang sudah dihapus di SAP juga akan hilang dari database lokal.
            with connect_mysql() as db:
                with db.cursor() as cur:
                 if aufnr:
                    cur.execute("SELECT COUNT(*) FROM yppi019_data WHERE AUFNR=%s", (aufnr,))
                    prev_count = (cur.fetchone() or [0])[0]
                    cur.execute("DELETE FROM yppi019_data WHERE AUFNR=%s", (aufnr,))
                    db.commit()
                    wiped = cur.rowcount
                 # TAMBAHKAN BLOK KONDISI INI
                 elif arbpl and werks: 
                    cur.execute("SELECT COUNT(*) FROM yppi019_data WHERE ARBPL0=%s AND WERKS=%s", (arbpl, werks))
                    prev_count = (cur.fetchone() or [0])[0]
                    cur.execute("DELETE FROM yppi019_data WHERE ARBPL0=%s AND WERKS=%s", (arbpl, werks))
                    db.commit()
                    wiped = cur.rowcount
                
            saved = save_rows(rows)
            note = "replaced with fresh data from SAP"

        return {
            "ok": True, 
            "received": n_received, 
            "saved": saved, 
            "wiped": wiped, 
            "prev_count": prev_count, 
            "note": note
        }

    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
        logger.exception("SAP error in sync_from_sap")
        return {"ok": False, "error": str(e)}
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
def api_get():
    ensure_tables()
    params = {"AUFNR": request.args.get("aufnr"), "VORNRX": request.args.get("vornrx"),"CHARG": request.args.get("charg"), "STEUS": request.args.get("steus"),"PERNR": request.args.get("pernr"), "ARBPL0": request.args.get("arbpl"),"WERKS": request.args.get("werks")}
    limit = request.args.get("limit", type=int) or 100
    with connect_mysql() as db:
        with db.cursor(dictionary=True) as cur:
            sql = "SELECT * FROM yppi019_data"
            # Ambil kondisi filter dinamis dari parameter request
            cond, args = [f"{k}=%s" for k, v in params.items() if v], [v for v in params.values() if v]

            # === PERUBAHAN DIMULAI DI SINI ===
            # Buat daftar untuk semua klausa WHERE
            where_clauses = cond
            
            # Tambahkan kondisi statis untuk hanya menampilkan data yang masih bisa diproses
            # (QTY_SPX > 0 DAN kuantitas yang sudah dikonfirmasi < kuantitas target)
            where_clauses.append("(IFNULL(QTY_SPX, 0) > 0 AND IFNULL(WEMNG, 0) < IFNULL(QTY_SPK, 0))")

            # Gabungkan semua kondisi menjadi satu string query
            if where_clauses: sql += " WHERE " + " AND ".join(where_clauses)
            # === PERUBAHAN SELESAI ===

            sql += " ORDER BY VORNRX ASC" if params.get("AUFNR") and not params.get("ARBPL0") else " ORDER BY AUFNR ASC, VORNRX ASC"
            sql += " LIMIT %s"; args.append(limit)
            cur.execute(sql, tuple(args))
            return jsonify({"ok": True, "rows": to_jsonable(cur.fetchall())})
        

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

    # === PERUBAHAN UTAMA: jika SAP mengembalikan 0 row, balas 404 ===
    if res.get("ok") and int(res.get("received") or 0) == 0:
        res["teco_possible"] = True
        res["refreshed"] = False
        # beri pesan ramah untuk modal FE
        res.setdefault("message", "Data Tidak Ditemukan")
        return jsonify(to_jsonable(res)), 404
    # === END PERUBAHAN ===

    status = 200 if res.get("ok") else 500
    res["refreshed"] = bool(res.get("ok"))
    return jsonify(to_jsonable(res)), status


# ---------------- Confirm & Other Endpoints ----------------
@app.post("/api/yppi019/confirm")
def api_confirm():
    try: u, p = get_credentials_from_request()
    except ValueError as ve: return jsonify({"ok": False, "error": str(ve)}), 401
    b = request.get_json(force=True) or {}
    aufnr, vornr, pernr, budat, qty_in = (str(b.get("aufnr") or "").strip(), pad_vornr(b.get("vornr")),str(b.get("pernr") or "").strip(), str(b.get("budat") or "").strip(), parse_num(b.get("psmng")))
    if not (aufnr and vornr and pernr and budat and qty_in is not None and qty_in > 0):
        return jsonify({"ok": False, "error": "Parameter tidak valid atau psmng <= 0"}), 400
    try:
        with connect_mysql() as db:
            with db.cursor(dictionary=True) as cur:
                cur.execute("SELECT id, QTY_SPK, WEMNG, QTY_SPX, MEINH FROM yppi019_data WHERE AUFNR=%s AND VORNRX=%s LIMIT 1", (aufnr, vornr))
                row_db = cur.fetchone()
                if not row_db: return jsonify({"ok": False, "error": "Data operation tidak ditemukan"}), 404
        qty_spk, wemng, qty_spx = float(row_db.get("QTY_SPK") or 0.0), float(row_db.get("WEMNG") or 0.0), float(row_db.get("QTY_SPX") or 0.0)
        if qty_in > max(qty_spk - wemng, 0.0): return jsonify({"ok": False, "error": f"Input melebihi QTY_SPK sisa ({max(qty_spk - wemng, 0.0)})."}), 400
        if qty_in > qty_spx: return jsonify({"ok": False, "error": f"Input melebihi QTY_SPX sisa ({qty_spx})."}), 400
        meinh_req = normalize_uom(b.get("meinh") or row_db.get("MEINH"))
        psmng_str = str(int(round(qty_in))) if meinh_req in {"PC", "EA", "PCS", "UNIT"} else f"{qty_in:.3f}".replace(".", ",")
        with connect_sap(u, p) as sap:
            sap_ret = sap.call(RFC_C, IV_AUFNR=pad_aufnr(aufnr), IV_VORNR=vornr, IV_PERNR=pernr, IV_PSMNG=psmng_str, IV_MEINH=meinh_req, IV_GSTRP=str(b.get("gstrp") or ""), IV_GLTRP=str(b.get("gltrp") or ""), IV_BUDAT=budat)
        with connect_mysql() as db:
            with db.cursor(dictionary=True) as cur:
                cur.execute("INSERT INTO yppi019_confirm_log (AUFNR,VORNR,PERNR,PSMNG,MEINH,GSTRP,GLTRP,BUDAT,SAP_RETURN,created_at) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)", (aufnr, vornr, pernr, qty_in, meinh_req, parse_date(b.get("gstrp")), parse_date(b.get("gltrp")), parse_date(budat), json.dumps(to_jsonable(sap_ret), ensure_ascii=False), datetime.datetime.now()))
                cur.execute("UPDATE yppi019_data SET WEMNG=IFNULL(WEMNG,0)+%s, QTY_SPX=GREATEST(IFNULL(QTY_SPX,0)-%s,0), fetched_at=NOW() WHERE id=%s", (qty_in, qty_in, row_db["id"]))
                cur.execute("DELETE FROM yppi019_confirm_log WHERE DATE(created_at) < CURDATE()")         
                db.commit() # Perintah commit akan menjalankan INSERT, UPDATE, dan DELETE sekaligus.
                cur.execute("SELECT * FROM yppi019_data WHERE id=%s", (row_db["id"],))
                latest_row = cur.fetchone()
        return jsonify({"ok": True, "sap_return": to_jsonable(sap_ret), "refreshed": {"ok": True, "mode": "local_update"}, "row": to_jsonable(latest_row)}), 200
    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as e:
        logger.exception("SAP error api_confirm")
        return jsonify({"ok": False, "error": str(e)}), 500
    except Exception as e:
        logger.exception("Error api_confirm")
        return jsonify({"ok": False, "error": str(e)}), 500

@app.post("/api/yppi019/backdate-log")
def api_backdate_log():
    """
    Simpan histori backdate (BUDAT ≠ today). Dipanggil FE setelah konfirmasi sukses.
    Payload (JSON):
      - aufnr (wajib), vornr, pernr, qty, meinh
      - budat (wajib, 'YYYYMMDD'), today (wajib, 'YYYYMMDD')
      - arbpl0, maktx, sap_return (opsional), confirmed_at (ISO8601, opsional)
    """
    b = request.get_json(force=True) or {}

    # Wajib
    for k in ("aufnr", "budat", "today"):
        if not str(b.get(k) or "").strip():
            return jsonify({"ok": False, "error": f"missing field: {k}"}), 400

    aufnr   = str(b.get("aufnr")).strip()
    vornr   = (str(b.get("vornr") or "").strip() or None)
    pernr   = (str(b.get("pernr") or "").strip() or None)
    qty     = parse_num(b.get("qty"))
    meinh   = (str(b.get("meinh") or "").strip() or None)
    budat_s = str(b.get("budat")).strip()   # 'YYYYMMDD'
    today_s = str(b.get("today")).strip()   # 'YYYYMMDD'
    arbpl0  = (str(b.get("arbpl0") or "").strip() or None)
    maktx   = (str(b.get("maktx") or "").strip() or None)
    sap_ret = b.get("sap_return")
    confirmed_at_s = (str(b.get("confirmed_at") or "").strip() or None)

    # --- Parse tanggal di PYTHON (hindari STR_TO_DATE di SQL) ---
    from datetime import datetime, date
    try:
        budat_dt: date = datetime.strptime(budat_s, "%Y%m%d").date()
        today_dt: date = datetime.strptime(today_s, "%Y%m%d").date()
    except Exception:
        return jsonify({"ok": False, "error": "invalid date format (expected YYYYMMDD)"}), 400

    # Guard (FE sudah memblok future-date, tapi kita validasi lagi)
    if budat_dt > today_dt:
        return jsonify({"ok": False, "error": "BUDAT cannot be in the future"}), 422
    if budat_dt == today_dt:
        # bukan backdate -> tidak disimpan
        return jsonify({"ok": True, "skipped": True})

    # Parse optional confirmed_at (ISO8601) ke datetime, kalau ada
    confirmed_dt = None
    if confirmed_at_s:
        try:
            # dukung ...Z
            confirmed_dt = datetime.fromisoformat(confirmed_at_s.replace("Z", "+00:00"))
        except Exception:
            confirmed_dt = None

    try:
        with connect_mysql() as db:
            with db.cursor() as cur:
                cur.execute(
                    """
                    INSERT INTO yppi019_backdate_log
                      (AUFNR, VORNR, PERNR, QTY, MEINH, BUDAT, TODAY, ARBPL0, MAKTX, SAP_RETURN, CONFIRMED_AT)
                    VALUES
                      (%s,    %s,    %s,    %s,  %s,    %s,   %s,    %s,    %s,    %s,         %s)
                    """,
                    (
                        aufnr, vornr, pernr, qty, meinh,
                        budat_dt, today_dt,
                        arbpl0, maktx,
                        json.dumps(to_jsonable(sap_ret), ensure_ascii=False) if sap_ret is not None else None,
                        confirmed_dt
                    ),
                )

                # ▼▼▼ BARU: Pangkas otomatis, sisakan 50 baris terbaru (global) ▼▼▼
                # Urutan "terbaru" ditentukan oleh COALESCE(CONFIRMED_AT, CREATED_AT) DESC lalu id DESC.
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
                # ▲▲▲ END BARU ▲▲▲

                db.commit()
        return jsonify({"ok": True}), 200
    except Exception as e:
        logger.exception("Error api_backdate_log")
        return jsonify({"ok": False, "error": str(e)}), 500


# -------- BARU: Endpoint untuk modal Histori Backdate --------
@app.get("/api/yppi019/backdate-history")
def api_backdate_history():
    """
    Ambil histori backdate terbaru.
    Query:
      - pernr (opsional tapi disarankan)
      - aufnr (opsional; jika dikirim, memfilter 1 PRO)
      - limit (default 50)
      - order = 'asc' | 'desc' (default 'desc')
    """
    ensure_tables()
    pernr = (request.args.get("pernr") or "").strip()
    aufnr = (request.args.get("aufnr") or "").strip()
    limit = request.args.get("limit", type=int) or 50
    order = (request.args.get("order") or "desc").lower()
    order_sql = "ASC" if order == "asc" else "DESC"
    # batasi limit agar aman
    limit = max(1, min(int(limit), 500))

    try:
        with connect_mysql() as db:
            with db.cursor(dictionary=True) as cur:
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
        return jsonify({"ok": True, "rows": to_jsonable(rows)}), 200
    except Exception as e:
        logger.exception("Error api_backdate_history")
        return jsonify({"ok": False, "error": str(e)}), 500
# -------- END BARU --------


@app.post("/api/yppi019/update_qty_spx")
def api_update_qty_spx():
    ensure_tables()
    b = request.get_json(force=True) or {}
    aufnr, vornrx, charg, qty = (b.get("aufnr") or "").strip(), (b.get("vornrx") or "").strip(), (b.get("charg") or "").strip(), parse_num(b.get("qty_spx"))
    if not (aufnr and vornrx and charg and qty is not None):
        return jsonify({"ok": False, "error": "aufnr, vornrx, charg, qty_spx wajib"}), 400
    with connect_mysql() as db:
        with db.cursor() as cur:
            cur.execute("UPDATE yppi019_data SET QTY_SPX=%s WHERE AUFNR=%s AND VORNRX=%s AND CHARG=%s LIMIT 1", (qty, aufnr, vornrx, charg))
            db.commit()
            return jsonify({"ok": True, "updated": cur.rowcount})

app.add_url_rule("/api/yppi019/update-qty-spx", view_func=api_update_qty_spx, methods=["POST"])

if __name__ == "__main__":
    ensure_tables()
    app.run(host=HTTP_HOST, port=HTTP_PORT, debug=True)
