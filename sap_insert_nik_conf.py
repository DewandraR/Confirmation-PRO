#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Batch insert PERNR (NIK) into SAP RFC: Z_RFC_INSERT_NIK_CONF

RFC signature (from your JSON):
IMPORTS:
  - DELETE_FLAG (CHAR1) optional
  - PERNR      (PERNR_D) optional
  - WERKS      (WERKS_D) optional
EXPORTS:
  - MESSAGE (STRING)
  - STATUS  (CHAR1)

Features:
- Input MANY PERNR in one run (comma/space/newline separated OR from file)
- WERKS set once per run, applied to all PERNR
- Counts: total input, duplicates, unique processed, success, failed
- Optional CSV output with per-PERNR result
- Easy to change PERNR list and WERKS via CLI flags or editing defaults

Usage examples:
  python sap_insert_nik_conf.py --werks 3000 --pernr "10007861,10008274 10008193"
  python sap_insert_nik_conf.py --werks 3000 --pernr-file pernr.txt
  python sap_insert_nik_conf.py --werks 3000 --pernr-file pernr.txt --out results.csv
  python sap_insert_nik_conf.py --werks 3000 --pernr "10007861" --delete

Env vars supported:
  SAP_ASHOST, SAP_SYSNR, SAP_CLIENT, SAP_LANG, SAP_USER, SAP_PASS

Cara Pakai Cepat
A) Input langsung (sekali jalan)
python sap_insert_nik_conf.py --werks 3000 --pernr "10007861 10008274 10008193 10008267 10008273 10006631 10008268"

B) Input dari file (rekomendasi untuk “banyak sekali”)

Buat pernr.txt:

10007861
10008274
10008193
10008267
10008273
10006631
10008268


Jalankan:

python sap_insert_nik_conf.py --werks 3000 --pernr-file pernr.txt --out hasil.csv

C) Mode delete (mengirim DELETE_FLAG='X')
python sap_insert_nik_conf.py --werks 3000 --pernr-file pernr.txt --delete --out hasil_delete.csv

4) Catatan penting (biar “benar-benar jalan”)

RFC Anda return STATUS (CHAR1). Script menganggap sukses kalau STATUS termasuk S,0,1.
Kalau di sistem Anda sukses = 'Y' atau yang lain, tinggal set:

python sap_insert_nik_conf.py --werks 3000 --pernr-file pernr.txt --success-status "S,Y"


Script menghapus duplikat dari input (tidak dipanggil 2x) tapi tetap dihitung sebagai duplikat.
"""

import os
import re
import sys
import csv
import argparse
from datetime import datetime
from collections import Counter
from typing import List, Dict, Tuple, Optional

# ---- SAP RFC lib (pyrfc) ----
try:
    from pyrfc import (
        Connection,
        ABAPApplicationError,
        ABAPRuntimeError,
        CommunicationError,
        LogonError,
    )
except ImportError as e:
    print(
        "ERROR: pyrfc is not installed or cannot be imported.\n"
        "Install pyrfc + SAP NetWeaver RFC SDK first.\n"
        "pip install pyrfc\n"
        f"Details: {e}",
        file=sys.stderr,
    )
    sys.exit(2)


RFC_NAME = "Z_RFC_INSERT_NIK_CONF"


def build_sap_conn_params() -> Dict[str, str]:
    """
    Build SAP connection parameters using your provided defaults and env overrides.
    """
    return {
        "ashost": os.environ.get("SAP_ASHOST", "192.168.254.154"),
        "sysnr": os.environ.get("SAP_SYSNR", "01"),
        "client": os.environ.get("SAP_CLIENT", "300"),
        "lang": os.environ.get("SAP_LANG", "EN"),
        "user": os.environ.get("SAP_USER", "auto_email"),
        "passwd": os.environ.get("SAP_PASS", "11223344"),
    }


def parse_pernr_text(text: str) -> List[str]:
    """
    Parse a blob of text into a list of tokens that look like PERNR candidates.
    Accepts comma/semicolon/space/newline separated.
    """
    if not text:
        return []
    # Split by commas, semicolons, whitespace
    parts = re.split(r"[,\s;]+", text.strip())
    return [p for p in parts if p.strip()]


def read_pernr_file(path: str) -> List[str]:
    """
    Read PERNR list from file. Supports comma/space/newline too.
    """
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()
    return parse_pernr_text(content)


def normalize_pernr(pernr: str, zfill_len: int = 8) -> str:
    """
    Normalize PERNR:
    - keep digits only
    - if numeric and shorter than zfill_len, left pad with zeros
    SAP PERNR_D commonly length=8 numeric.
    """
    raw = pernr.strip()
    digits = re.sub(r"\D", "", raw)
    if not digits:
        return ""  # invalid
    if len(digits) < zfill_len:
        digits = digits.zfill(zfill_len)
    return digits


def normalize_werks(werks: str, zfill_len: int = 4) -> str:
    """
    Normalize WERKS:
    - keep alnum only (WERKS usually numeric 4 char, but can be alnum in some setups)
    - if purely digits and shorter than 4, zfill
    """
    w = werks.strip()
    if not w:
        return ""
    w2 = re.sub(r"[^0-9A-Za-z]", "", w)
    if w2.isdigit() and len(w2) < zfill_len:
        w2 = w2.zfill(zfill_len)
    return w2


def is_success_status(status: str, success_set: Tuple[str, ...]) -> bool:
    """
    Decide success based on STATUS char.
    Default success_set includes common values: ('S','0','1')
    You can change via CLI.
    """
    if status is None:
        return False
    s = str(status).strip().upper()
    return s in {x.strip().upper() for x in success_set}


def call_rfc_insert(
    conn: Connection,
    pernr: str,
    werks: str,
    delete_flag: str = "",
) -> Dict[str, str]:
    """
    Call RFC Z_RFC_INSERT_NIK_CONF for a single PERNR.
    Returns dict with keys: PERNR, WERKS, DELETE_FLAG, STATUS, MESSAGE
    """
    # RFC expects import params PERNR, WERKS, DELETE_FLAG (optional)
    params = {
        "PERNR": pernr,
        "WERKS": werks,
    }
    if delete_flag:
        params["DELETE_FLAG"] = delete_flag

    result = conn.call(RFC_NAME, **params)

    # result should have exports: STATUS, MESSAGE
    status = (result.get("STATUS") or "").strip()
    message = (result.get("MESSAGE") or "").strip()

    return {
        "PERNR": pernr,
        "WERKS": werks,
        "DELETE_FLAG": delete_flag,
        "STATUS": status,
        "MESSAGE": message,
    }


def write_csv(path: str, rows: List[Dict[str, str]]) -> None:
    """
    Write results to CSV.
    """
    fieldnames = ["PERNR", "WERKS", "DELETE_FLAG", "STATUS", "MESSAGE"]
    with open(path, "w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=fieldnames)
        w.writeheader()
        for r in rows:
            w.writerow({k: (r.get(k) or "") for k in fieldnames})


def main():
    parser = argparse.ArgumentParser(
        description="Batch insert PERNR (NIK) into SAP RFC Z_RFC_INSERT_NIK_CONF"
    )

    parser.add_argument(
        "--werks",
        default="3000",
        help="WERKS for all PERNR in this run (default: 3000)",
    )

    parser.add_argument(
        "--pernr",
        default="",
        help="PERNR list (comma/space/newline separated). Example: \"10007861,10008274 10008193\"",
    )

    parser.add_argument(
        "--pernr-file",
        default="",
        help="Path to text file containing PERNR list (any separators)",
    )

    parser.add_argument(
        "--delete",
        action="store_true",
        help="If set, send DELETE_FLAG='X' (optional RFC parameter)",
    )

    parser.add_argument(
        "--out",
        default="",
        help="Optional output CSV path, e.g. results.csv",
    )

    parser.add_argument(
        "--success-status",
        default="S,0,1",
        help="Comma-separated STATUS values considered SUCCESS (default: S,0,1). Adjust to your RFC convention.",
    )

    parser.add_argument(
        "--continue-on-error",
        action="store_true",
        help="Continue processing next PERNR even if a call errors (recommended for big batches)",
    )

    parser.add_argument(
        "--show-duplicates",
        action="store_true",
        help="Print list of duplicate PERNR found in input",
    )

    args = parser.parse_args()

    werks = normalize_werks(args.werks)
    if not werks:
        print("ERROR: WERKS is empty/invalid.", file=sys.stderr)
        sys.exit(1)

    # Collect PERNRs
    pernr_tokens: List[str] = []

    if args.pernr_file:
        if not os.path.exists(args.pernr_file):
            print(f"ERROR: pernr-file not found: {args.pernr_file}", file=sys.stderr)
            sys.exit(1)
        pernr_tokens.extend(read_pernr_file(args.pernr_file))

    if args.pernr:
        pernr_tokens.extend(parse_pernr_text(args.pernr))

    # If user didn't provide any input, use your example list as fallback (easy to edit)
    if not pernr_tokens:
        pernr_tokens = [
            "10007861",
            "10008274",
            "10008193",
            "10008267",
            "10008273",
            "10006631",
            "10008268",
        ]
        print("INFO: No PERNR input provided; using built-in example list.\n", file=sys.stderr)

    # Normalize PERNRs
    normalized: List[str] = []
    invalid: List[str] = []
    for t in pernr_tokens:
        n = normalize_pernr(t)
        if n:
            normalized.append(n)
        else:
            invalid.append(t)

    total_input = len(pernr_tokens)
    total_valid = len(normalized)

    # Duplicate analysis on normalized list
    counter = Counter(normalized)
    duplicates = sorted([p for p, c in counter.items() if c > 1])
    duplicate_count = sum((counter[p] - 1) for p in duplicates)

    unique_pernrs = sorted(counter.keys())  # unique normalized PERNRs
    unique_count = len(unique_pernrs)

    # Print pre-summary
    print("=== INPUT SUMMARY ===")
    print(f"WERKS (applied to all): {werks}")
    print(f"Total tokens provided: {total_input}")
    print(f"Valid PERNR after normalization: {total_valid}")
    if invalid:
        print(f"Invalid tokens skipped: {len(invalid)} -> {invalid}")

    print(f"Unique PERNR to process: {unique_count}")
    print(f"Duplicate occurrences: {duplicate_count}")
    if args.show_duplicates and duplicates:
        print(f"Duplicate PERNR list: {duplicates}")
    print()

    # Prepare SAP connection
    conn_params = build_sap_conn_params()

    # Optional: show sanitized conn info (do not print password)
    safe_params = {k: v for k, v in conn_params.items() if k != "passwd"}
    print("=== SAP CONNECTION ===")
    print(f"Conn params: {safe_params}")
    print(f"RFC Name: {RFC_NAME}")
    print()

    delete_flag = "X" if args.delete else ""

    # Execute calls
    results: List[Dict[str, str]] = []
    success = 0
    failed = 0
    error_calls = 0

    success_set = tuple(x.strip() for x in args.success_status.split(",") if x.strip())

    try:
        conn = Connection(**conn_params)
    except (CommunicationError, LogonError) as e:
        print(f"ERROR: Failed to connect to SAP: {e}", file=sys.stderr)
        sys.exit(3)

    start_ts = datetime.now()

    print("=== PROCESSING ===")
    for i, pernr in enumerate(unique_pernrs, start=1):
        try:
            r = call_rfc_insert(conn, pernr=pernr, werks=werks, delete_flag=delete_flag)

            ok = is_success_status(r.get("STATUS", ""), success_set)
            if ok:
                success += 1
            else:
                failed += 1

            results.append(r)

            # Minimal progress line (safe for big batches)
            print(
                f"[{i}/{unique_count}] PERNR={pernr} STATUS={r.get('STATUS','')} MESSAGE={r.get('MESSAGE','')}"
            )

        except (ABAPApplicationError, ABAPRuntimeError) as e:
            error_calls += 1
            failed += 1
            err_row = {
                "PERNR": pernr,
                "WERKS": werks,
                "DELETE_FLAG": delete_flag,
                "STATUS": "EX",
                "MESSAGE": f"ABAP_ERROR: {e}",
            }
            results.append(err_row)
            print(f"[{i}/{unique_count}] PERNR={pernr} ERROR={e}", file=sys.stderr)
            if not args.continue_on_error:
                print("Stopping because --continue-on-error is NOT set.", file=sys.stderr)
                break

        except (CommunicationError, LogonError) as e:
            error_calls += 1
            failed += 1
            err_row = {
                "PERNR": pernr,
                "WERKS": werks,
                "DELETE_FLAG": delete_flag,
                "STATUS": "COM",
                "MESSAGE": f"CONNECTION_ERROR: {e}",
            }
            results.append(err_row)
            print(f"[{i}/{unique_count}] PERNR={pernr} CONNECTION ERROR={e}", file=sys.stderr)
            if not args.continue_on_error:
                print("Stopping because --continue-on-error is NOT set.", file=sys.stderr)
                break

        except Exception as e:
            error_calls += 1
            failed += 1
            err_row = {
                "PERNR": pernr,
                "WERKS": werks,
                "DELETE_FLAG": delete_flag,
                "STATUS": "UK",
                "MESSAGE": f"UNKNOWN_ERROR: {repr(e)}",
            }
            results.append(err_row)
            print(f"[{i}/{unique_count}] PERNR={pernr} UNKNOWN ERROR={repr(e)}", file=sys.stderr)
            if not args.continue_on_error:
                print("Stopping because --continue-on-error is NOT set.", file=sys.stderr)
                break

    end_ts = datetime.now()
    elapsed = (end_ts - start_ts).total_seconds()

    try:
        conn.close()
    except Exception:
        pass

    # Output CSV if requested
    if args.out:
        try:
            write_csv(args.out, results)
            print(f"\nCSV saved: {args.out}")
        except Exception as e:
            print(f"\nWARNING: Failed to write CSV: {e}", file=sys.stderr)

    # Final summary
    processed = len(results)  # includes errors if any; equals number of unique processed attempts
    print("\n=== FINAL SUMMARY ===")
    print(f"WERKS: {werks}")
    print(f"Total input tokens: {total_input}")
    print(f"Valid PERNR tokens: {total_valid}")
    print(f"Unique PERNR planned: {unique_count}")
    print(f"Duplicate occurrences (skipped): {duplicate_count}")
    print(f"Processed (attempted calls): {processed}")
    print(f"Success: {success}")
    print(f"Failed: {failed}")
    print(f"Error calls (exceptions): {error_calls}")
    print(f"Elapsed seconds: {elapsed:.2f}")

    # Exit code: 0 if all success, 1 if any failed
    sys.exit(0 if failed == 0 else 1)


if __name__ == "__main__":
    main()
