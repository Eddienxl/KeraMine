"""FastAPI Rule Engine API untuk KeraMine (terhubung ke n8n, Supabase, Laravel & Streamlit)."""
from __future__ import annotations

import hashlib
from datetime import datetime, timezone
from pathlib import Path
from typing import Literal

import pandas as pd
from fastapi import FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from mining_service import ITEM_LEVEL_TO_COL, compute_sota_rules
from supabase_client import SupabaseRestClient

ROOT_DIR = Path(__file__).resolve().parent.parent
CLEAN_CSV_PATH = ROOT_DIR / "data" / "clean" / "transactions_clean.csv"
RULES_CSV_PATH = ROOT_DIR / "data" / "clean" / "mined_rules_catalog.csv"

app = FastAPI(
    title="KeraMine SOTA Association Rule Engine API",
    version="1.0.0",
    description="Rule Engine microservice untuk pipeline real-time n8n, Laravel B2B Portal, dan Streamlit Analysis Lab.",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

sb = SupabaseRestClient()


def load_transactions_df(source: str = "auto") -> tuple[pd.DataFrame, str]:
    """Memuat transaksi positif dari Supabase (prioritas bila auto/supabase) atau fallback CSV lokal."""
    if source in ("auto", "supabase") and sb.configured:
        try:
            rows = sb.fetch_all_positive_transactions()
            if rows:
                return pd.DataFrame(rows), "supabase"
        except Exception:
            if source == "supabase":
                raise
    if CLEAN_CSV_PATH.exists():
        return pd.read_csv(CLEAN_CSV_PATH), "local_csv"
    raise HTTPException(status_code=500, detail="Sumber transaksi bersih tidak tersedia di Supabase maupun lokal.")


class MineRequest(BaseModel):
    basket_type: Literal["store_month", "store_week"] = "store_month"
    item_level: Literal["MERK", "UKURAN", "MERK_UKURAN", "SERI", "NAMABARA"] = "SERI"
    algorithm: Literal["apriori", "fpgrowth"] = "apriori"
    min_support: float | None = Field(default=None, ge=0.0001, le=1.0)
    min_confidence: float | None = Field(default=None, ge=0.01, le=1.0)
    pof_filter: str | None = None
    triggered_by: str = "api_request"
    persist_to_supabase: bool = True
    data_source: Literal["auto", "supabase", "local_csv"] = "auto"


class SimulatedOrderItem(BaseModel):
    nama_toko: str = "KUNCI SUKSES, TOKO"
    pof: str = "DIAN"
    namabara: str = "DAKOTA BROWN 40X40"
    merk_clean: str = "PICASSO"
    ukuran: str = "40X40"
    seri: str = "DAKOTA"
    qty: int = Field(default=60, ge=1)


class SimulateBatchRequest(BaseModel):
    year_month: str | None = None
    tgl: str | None = None
    target_toko: str = "KUNCI SUKSES, TOKO"
    target_pof: str = "DIAN"
    bundle_seris: list[str] = Field(default_factory=lambda: ["DAKOTA", "NEO"])
    qty_per_item: int = Field(default=65, ge=1)
    repeat_baskets: int = Field(default=1, ge=1, le=20)
    auto_remine: bool = True


@app.get("/api/health")
def health():
    local_exists = CLEAN_CSV_PATH.exists()
    sb_ok = False
    latest_run = None
    if sb.configured:
        try:
            runs = sb.select("keramine_mining_runs", {"select": "id,triggered_by,item_level,rules_found,created_at", "order": "created_at.desc"}, limit=1)
            sb_ok = True
            latest_run = runs[0] if runs else None
        except Exception:
            sb_ok = False
    return {
        "status": "ok",
        "supabase_configured": sb.configured,
        "supabase_reachable": sb_ok,
        "local_clean_csv": local_exists,
        "latest_run": latest_run,
    }


@app.post("/api/mine")
def mine_rules(req: MineRequest):
    df_tx, used_source = load_transactions_df(req.data_source)
    rules_df, meta = compute_sota_rules(
        df_tx=df_tx,
        basket_type=req.basket_type,
        item_level=req.item_level,
        algorithm=req.algorithm,
        min_support=req.min_support,
        min_confidence=req.min_confidence,
        pof_filter=req.pof_filter,
    )

    run_id = None
    if req.persist_to_supabase and sb.configured and not req.pof_filter:
        run_id = sb.insert_mining_run(
            {
                "triggered_by": req.triggered_by,
                "basket_type": req.basket_type,
                "item_level": req.item_level,
                "algorithm": req.algorithm,
                "min_support": meta["min_support"],
                "min_confidence": meta["min_confidence"],
                "total_baskets": meta["total_baskets"],
                "rules_found": meta["rules_found"],
                "significant_rules": meta["significant_rules"],
                "exec_time_ms": meta["exec_time_ms"],
                "notes": f"source={used_source} | engine={meta.get('engine_backend')}",
            }
        )
        if not rules_df.empty:
            records = []
            for r in rules_df.to_dict(orient="records"):
                records.append(
                    {
                        "id": r["id"],
                        "run_id": run_id,
                        "basket_type": r["basket_type"],
                        "item_level": r["item_level"],
                        "antecedents": r["antecedents"],
                        "consequents": r["consequents"],
                        "antecedents_str": r["antecedents_str"],
                        "consequents_str": r["consequents_str"],
                        "n_a": int(r["n_a"]),
                        "n_b": int(r["n_b"]),
                        "n_ab": int(r["n_ab"]),
                        "total_baskets": int(r["total_baskets"]),
                        "support": float(r["support"]),
                        "confidence": float(r["confidence"]),
                        "lift": float(r["lift"]),
                        "leverage": float(r["leverage"]),
                        "conviction": float(r["conviction"]),
                        "kulczynski": float(r["kulczynski"]),
                        "zhang_metric": float(r["zhang_metric"]),
                        "total_qty_dus": float(r["total_qty_dus"]),
                        "avg_qty_dus": float(r["avg_qty_dus"]),
                        "utility_score": float(r["utility_score"]),
                        "p_value_fisher": float(r["p_value_fisher"]),
                        "q_value_bh": float(r["q_value_bh"]),
                        "is_significant": bool(r["is_significant"]),
                    }
                )
            sb.upsert_rows("keramine_rules", records, on_conflict="id")

    top_rules = []
    if not rules_df.empty:
        top_df = rules_df.sort_values(
            by=["is_significant", "utility_score", "lift"],
            ascending=[False, False, False],
        ).head(15)
        top_rules = top_df.to_dict(orient="records")

    return {
        "status": "success",
        "run_id": run_id,
        "data_source": used_source,
        "meta": meta,
        "top_rules": top_rules,
    }


@app.post("/api/simulate-transactions")
def simulate_transactions(req: SimulateBatchRequest):
    """Menyuntikkan transaksi simulasi ke Supabase (untuk demo live n8n/Laravel)."""
    if not sb.configured:
        raise HTTPException(status_code=503, detail="Supabase belum terkonfigurasi di .env")

    now = datetime.now(timezone.utc)
    tgl_str = req.tgl or now.strftime("%Y-%m-%d")
    ym_str = req.year_month or now.strftime("%Y-%m")
    yw_str = now.strftime("%G-W%V")
    batch_tag = f"n8n_sim_{now.strftime('%Y%m%d%H%M%S')}"

    inserted_rows = []
    for rep in range(req.repeat_baskets):
        toko_name = req.target_toko if rep == 0 else f"{req.target_toko} (CAB-{rep})"
        for idx, seri_name in enumerate(req.bundle_seris):
            seri_clean = seri_name.strip().upper()
            namabara = f"{seri_clean} SIMULATED 40X40"
            nota = f"SIM*{now.strftime('%H%M')}.{rep:02d}{idx:02d}"
            raw_key = f"{batch_tag}|{rep}|{idx}|{toko_name}|{namabara}|{now.isoformat()}"
            row_hash = hashlib.sha256(raw_key.encode("utf-8")).hexdigest()[:32]
            inserted_rows.append(
                {
                    "source_row_hash": row_hash,
                    "nota": nota,
                    "tgl": tgl_str,
                    "year_month": ym_str,
                    "year_week": yw_str,
                    "nama_toko": toko_name,
                    "kode_toko": 9900 + rep,
                    "namabara": namabara,
                    "kodebara": 99000 + idx,
                    "merk_raw": "PICASSO",
                    "merk_clean": "PICASSO",
                    "ukuran": "40X40",
                    "seri": seri_clean,
                    "merk_ukuran": "PICASSO_40X40",
                    "qty": req.qty_per_item,
                    "satuan": "DUS",
                    "pof": req.target_pof.upper(),
                    "is_return": False,
                    "source_batch": batch_tag,
                }
            )

    inserted_count = sb.upsert_rows("keramine_transactions", inserted_rows, on_conflict="source_row_hash")
    remine_result = None
    if req.auto_remine:
        remine_result = mine_rules(
            MineRequest(
                basket_type="store_month",
                item_level="SERI",
                algorithm="apriori",
                triggered_by="n8n_realtime_sim",
                persist_to_supabase=True,
                data_source="supabase",
            )
        )

    return {
        "status": "success",
        "batch_tag": batch_tag,
        "inserted_transactions": inserted_count,
        "remine": remine_result["meta"] if remine_result else None,
        "top_rules_after": remine_result["top_rules"][:5] if remine_result else [],
    }


@app.get("/api/recommend")
def recommend(
    item: str | None = Query(default=None, description="Item pemicu, mis. DIEGO atau PICASSO_40X40"),
    nama_toko: str | None = Query(default=None, description="Opsional: nama toko B2B"),
    basket_type: Literal["store_month", "store_week"] = "store_month",
    item_level: Literal["MERK", "UKURAN", "MERK_UKURAN", "SERI", "NAMABARA"] = "SERI",
    top_k: int = Query(default=5, ge=1, le=20),
):
    """Mengembalikan rekomendasi bundling berbasis aturan signifikan + fallback popularitas transparan."""
    item_col = ITEM_LEVEL_TO_COL[item_level]
    triggers: list[str] = []
    if item:
        triggers.append(item.strip().upper())

    store_profile = None
    if nama_toko and sb.configured:
        profs = sb.select("keramine_store_profiles", {"nama_toko": f"eq.{nama_toko.upper()}"}, limit=1)
        if profs:
            store_profile = profs[0]
            if not triggers:
                field = "top_seris" if item_level == "SERI" else ("top_merks" if item_level == "MERK" else "top_ukurans")
                triggers = [x["name"] for x in (store_profile.get(field) or [])[:3]]

    recommendations: list[dict] = []
    seen_targets: set[str] = set(triggers)

    if sb.configured and triggers:
        rules = sb.select(
            "keramine_rules",
            {
                "basket_type": f"eq.{basket_type}",
                "item_level": f"eq.{item_level}",
                "order": "is_significant.desc,utility_score.desc,lift.desc",
            },
            limit=500,
        )
        for r in rules:
            ants = set(r.get("antecedents") or [])
            if any(t in ants for t in triggers):
                for c in (r.get("consequents") or []):
                    if c not in seen_targets:
                        seen_targets.add(c)
                        recommendations.append(
                            {
                                "recommended_item": c,
                                "source_type": "Significant Association Rule (Fisher p < 0.05)"
                                if r.get("is_significant")
                                else "Exploratory Association Rule",
                                "rule_id": r["id"],
                                "antecedents_str": r["antecedents_str"],
                                "confidence": r["confidence"],
                                "lift": r["lift"],
                                "zhang_metric": r["zhang_metric"],
                                "avg_qty_dus": r["avg_qty_dus"],
                                "utility_score": r["utility_score"],
                                "p_value_fisher": r["p_value_fisher"],
                            }
                        )
                        if len(recommendations) >= top_k:
                            break
            if len(recommendations) >= top_k:
                break

    # Fallback popularitas transparan bila kurang dari top_k
    if len(recommendations) < top_k and CLEAN_CSV_PATH.exists():
        df_local = pd.read_csv(CLEAN_CSV_PATH)
        pop = df_local.groupby(item_col)["qty"].sum().sort_values(ascending=False)
        for name, qty_sum in pop.items():
            if name not in seen_targets:
                seen_targets.add(name)
                recommendations.append(
                    {
                        "recommended_item": name,
                        "source_type": "Volume Popularity Fallback (Top DUS)",
                        "rule_id": None,
                        "antecedents_str": ", ".join(triggers) if triggers else "GLOBAL",
                        "confidence": None,
                        "lift": None,
                        "zhang_metric": None,
                        "avg_qty_dus": round(float(qty_sum) / max(1, df_local[df_local[item_col] == name]["year_month"].nunique()), 2),
                        "utility_score": None,
                        "p_value_fisher": None,
                    }
                )
                if len(recommendations) >= top_k:
                    break

    return {
        "triggers": triggers,
        "nama_toko": nama_toko,
        "basket_type": basket_type,
        "item_level": item_level,
        "store_profile": store_profile,
        "recommendations": recommendations,
    }


@app.get("/api/version")
def api_version():
    return {"service": "keramine-api-engine", "version": "1.0.0", "schema_version": "20261110162650"}


@app.post("/api/reset-demo")
def reset_demo():
    """Menghapus hanya baris simulasi (source_batch != historical_xlsx) & mengembalikan rules acuan Colab 02."""
    if not sb.configured:
        raise HTTPException(status_code=503, detail="Supabase belum terkonfigurasi.")
    deleted_tx = sb.delete_where("keramine_transactions", {"source_batch": "neq.historical_xlsx"})
    deleted_runs = sb.delete_where("keramine_mining_runs", {"triggered_by": "neq.colab_seed"})
    # Hitung ulang rules SERI & MERK_UKURAN dari 15.840 baris asli atau restore dari katalog bersih
    restored_rules = 0
    if RULES_CSV_PATH.exists():
        import json as _json

        sb.delete_where("keramine_rules", {"id": "neq.__never__"})
        df_r = pd.read_csv(RULES_CSV_PATH)
        runs = sb.select("keramine_mining_runs", {"select": "id,basket_type,item_level"}, limit=20)
        run_map = {(r["basket_type"], r["item_level"]): r["id"] for r in runs}
        recs = []
        for r in df_r.to_dict(orient="records"):
            recs.append(
                {
                    "id": str(r["id"]),
                    "run_id": run_map.get((r["basket_type"], r["item_level"])),
                    "basket_type": str(r["basket_type"]),
                    "item_level": str(r["item_level"]),
                    "antecedents": _json.loads(r["antecedents"]),
                    "consequents": _json.loads(r["consequents"]),
                    "antecedents_str": str(r["antecedents_str"]),
                    "consequents_str": str(r["consequents_str"]),
                    "n_a": int(r["n_a"]),
                    "n_b": int(r["n_b"]),
                    "n_ab": int(r["n_ab"]),
                    "total_baskets": int(r["total_baskets"]),
                    "support": float(r["support"]),
                    "confidence": float(r["confidence"]),
                    "lift": float(r["lift"]),
                    "leverage": float(r["leverage"]),
                    "conviction": float(r["conviction"]) if pd.notna(r["conviction"]) else 999.0,
                    "kulczynski": float(r["kulczynski"]),
                    "zhang_metric": float(r["zhang_metric"]),
                    "total_qty_dus": float(r["total_qty_dus"]),
                    "avg_qty_dus": float(r["avg_qty_dus"]),
                    "utility_score": float(r["utility_score"]),
                    "p_value_fisher": float(r["p_value_fisher"]),
                    "q_value_bh": float(r["q_value_bh"]),
                    "is_significant": bool(r["is_significant"]),
                }
            )
        restored_rules = sb.upsert_rows("keramine_rules", recs, on_conflict="id")

    return {
        "status": "reset_complete",
        "deleted_simulated_transactions": deleted_tx,
        "deleted_non_seed_runs": deleted_runs,
        "restored_rules": restored_rules,
    }

