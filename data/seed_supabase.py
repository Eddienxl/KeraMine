"""Seeder idempoten artefak data/clean/ ke Supabase PostgreSQL via PostgREST API."""
import json
import math
import os
import urllib.error
import urllib.request
from pathlib import Path

import pandas as pd

ROOT = Path(__file__).resolve().parent.parent
CLEAN_DIR = ROOT / "data" / "clean"
ENV_PATH = ROOT / ".env"


def load_env(path: Path) -> dict[str, str]:
    env: dict[str, str] = {}
    if not path.exists():
        return env
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        env[k.strip()] = v.strip()
    return env


def rest_request(
    base_url: str,
    secret_key: str,
    method: str,
    endpoint: str,
    payload=None,
    prefer: str | None = None,
):
    url = f"{base_url.rstrip('/')}/{endpoint.lstrip('/')}"
    headers = {
        "apikey": secret_key,
        "Authorization": f"Bearer {secret_key}",
        "Content-Type": "application/json",
        "User-Agent": "KeraMine-Seeder/1.0",
    }
    if prefer:
        headers["Prefer"] = prefer
    data = json.dumps(payload, allow_nan=False).encode("utf-8") if payload is not None else None
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=60) as resp:
            raw = resp.read().decode("utf-8")
            return resp.status, json.loads(raw) if raw else None
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"HTTP {e.code} on {method} {endpoint}: {body}") from e


def chunked(rows: list, size: int = 500):
    for i in range(0, len(rows), size):
        yield rows[i : i + size]


def sanitize_num(val, default=0.0):
    if val is None or (isinstance(val, float) and (math.isnan(val) or math.isinf(val))):
        return default
    return val


def main():
    env = load_env(ENV_PATH)
    rest_url = env.get("SUPABASE_REST_URL") or (env["SUPABASE_URL"].rstrip("/") + "/rest/v1")
    secret_key = env["SUPABASE_SECRET_KEY"]

    # 1. Seed keramine_store_profiles (237 toko)
    profiles = json.loads((CLEAN_DIR / "store_profiles.json").read_text(encoding="utf-8"))
    for batch in chunked(profiles, 200):
        rest_request(
            rest_url,
            secret_key,
            "POST",
            "keramine_store_profiles?on_conflict=nama_toko",
            batch,
            prefer="resolution=merge-duplicates,return=minimal",
        )
    print(f"[OK] Upserted {len(profiles)} store profiles.")

    # 2. Seed keramine_transactions (15.840 positif + 146 retur = 15.986 baris)
    df_pos = pd.read_csv(CLEAN_DIR / "transactions_clean.csv")
    df_ret = pd.read_csv(CLEAN_DIR / "returns_log.csv")
    df_tx = pd.concat([df_pos, df_ret], ignore_index=True)

    tx_records = []
    for r in df_tx.to_dict(orient="records"):
        tx_records.append(
            {
                "source_row_hash": str(r["source_row_hash"]),
                "nota": str(r["nota"]),
                "tgl": str(r["tgl"]),
                "year_month": str(r["year_month"]),
                "year_week": str(r["year_week"]),
                "nama_toko": str(r["nama_toko"]),
                "kode_toko": int(r["kode_toko"]) if pd.notna(r["kode_toko"]) else None,
                "namabara": str(r["namabara"]),
                "kodebara": int(r["kodebara"]) if pd.notna(r["kodebara"]) else None,
                "merk_raw": str(r["merk_raw"]),
                "merk_clean": str(r["merk_clean"]),
                "ukuran": str(r["ukuran"]),
                "seri": str(r["seri"]),
                "merk_ukuran": str(r["merk_ukuran"]),
                "qty": int(r["qty"]),
                "satuan": str(r["satuan"]),
                "pof": str(r["pof"]),
                "is_return": bool(r["is_return"]),
                "source_batch": str(r["source_batch"]),
            }
        )

    for idx, batch in enumerate(chunked(tx_records, 500), 1):
        rest_request(
            rest_url,
            secret_key,
            "POST",
            "keramine_transactions?on_conflict=source_row_hash",
            batch,
            prefer="resolution=merge-duplicates,return=minimal",
        )
        if idx % 10 == 0 or idx * 500 >= len(tx_records):
            print(f"  -> Transactions batch {idx} uploaded ({min(idx * 500, len(tx_records))}/{len(tx_records)})")
    print(f"[OK] Upserted {len(tx_records)} transactions.")

    # 3. Seed keramine_mining_runs (8 runs dari Colab 02) & dapatkan run_id map
    # Hapus colab_seed lama agar re-run seeder tidak menggandakan histori seed
    rest_request(rest_url, secret_key, "DELETE", "keramine_mining_runs?triggered_by=eq.colab_seed")
    bench = json.loads((CLEAN_DIR / "sota_benchmark_summary.json").read_text(encoding="utf-8"))
    run_rows = []
    for run in bench["mining_runs_summary"]:
        run_rows.append(
            {
                "triggered_by": "colab_seed",
                "basket_type": run["basket_type"],
                "item_level": run["item_level"],
                "algorithm": "fpgrowth",
                "min_support": float(run["min_support"]),
                "min_confidence": float(run["min_confidence"]),
                "total_baskets": int(run["total_baskets"]),
                "rules_found": int(run["rules_found"]),
                "significant_rules": int(run["fisher_p_lt_005"]),
                "exec_time_ms": float(run["exec_time_ms"]),
                "notes": f"Colab 02 initial seed | fdr_bh_q<0.10: {run['fdr_bh_q_lt_010']}",
            }
        )
    _, created_runs = rest_request(
        rest_url,
        secret_key,
        "POST",
        "keramine_mining_runs",
        run_rows,
        prefer="return=representation",
    )
    run_id_map = {(r["basket_type"], r["item_level"]): r["id"] for r in created_runs}
    print(f"[OK] Inserted {len(created_runs)} mining run records.")

    # 4. Seed keramine_rules (784 rules)
    df_rules = pd.read_csv(CLEAN_DIR / "mined_rules_catalog.csv")
    rule_records = []
    for r in df_rules.to_dict(orient="records"):
        rule_records.append(
            {
                "id": str(r["id"]),
                "run_id": run_id_map.get((r["basket_type"], r["item_level"])),
                "basket_type": str(r["basket_type"]),
                "item_level": str(r["item_level"]),
                "antecedents": json.loads(r["antecedents"]),
                "consequents": json.loads(r["consequents"]),
                "antecedents_str": str(r["antecedents_str"]),
                "consequents_str": str(r["consequents_str"]),
                "n_a": int(r["n_a"]),
                "n_b": int(r["n_b"]),
                "n_ab": int(r["n_ab"]),
                "total_baskets": int(r["total_baskets"]),
                "support": float(sanitize_num(r["support"])),
                "confidence": float(sanitize_num(r["confidence"])),
                "lift": float(sanitize_num(r["lift"])),
                "leverage": float(sanitize_num(r["leverage"])),
                "conviction": float(sanitize_num(r["conviction"], 999.0)),
                "kulczynski": float(sanitize_num(r["kulczynski"])),
                "zhang_metric": float(sanitize_num(r["zhang_metric"])),
                "total_qty_dus": float(sanitize_num(r["total_qty_dus"])),
                "avg_qty_dus": float(sanitize_num(r["avg_qty_dus"])),
                "utility_score": float(sanitize_num(r["utility_score"])),
                "p_value_fisher": float(sanitize_num(r["p_value_fisher"], 1.0)),
                "q_value_bh": float(sanitize_num(r["q_value_bh"], 1.0)),
                "is_significant": bool(r["is_significant"]),
            }
        )

    for batch in chunked(rule_records, 300):
        rest_request(
            rest_url,
            secret_key,
            "POST",
            "keramine_rules?on_conflict=id",
            batch,
            prefer="resolution=merge-duplicates,return=minimal",
        )
    print(f"[OK] Upserted {len(rule_records)} mined rules.")


if __name__ == "__main__":
    main()

