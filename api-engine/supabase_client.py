"""Klien ringan Supabase PostgREST untuk membaca & melakukan upsert ke tabel keramine_*."""
from __future__ import annotations

import json
import math
import os
from pathlib import Path
from typing import Any

import httpx

ROOT_DIR = Path(__file__).resolve().parent.parent
ENV_PATH = ROOT_DIR / ".env"


def load_settings() -> dict[str, str]:
    settings: dict[str, str] = {}
    if ENV_PATH.exists():
        for line in ENV_PATH.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            settings[k.strip()] = v.strip()
    for k in [
        "SUPABASE_URL",
        "SUPABASE_REST_URL",
        "SUPABASE_PUBLISHABLE_KEY",
        "SUPABASE_SECRET_KEY",
    ]:
        if os.getenv(k):
            settings[k] = os.environ[k]
    return settings


def _clean_json_val(v: Any) -> Any:
    if isinstance(v, float) and (math.isnan(v) or math.isinf(v)):
        return 0.0
    return v


class SupabaseRestClient:
    """Pembungkus REST API Supabase berbasis httpx (HTTPS IPv4)."""

    def __init__(self) -> None:
        cfg = load_settings()
        base = cfg.get("SUPABASE_REST_URL") or (
            cfg.get("SUPABASE_URL", "").rstrip("/") + "/rest/v1"
        )
        self.rest_url = base.rstrip("/")
        self.secret_key = cfg.get("SUPABASE_SECRET_KEY", "")
        self.pub_key = cfg.get("SUPABASE_PUBLISHABLE_KEY", "")

    @property
    def configured(self) -> bool:
        return bool(self.rest_url and self.secret_key)

    def _headers(self, prefer: str | None = None) -> dict[str, str]:
        h = {
            "apikey": self.secret_key,
            "Authorization": f"Bearer {self.secret_key}",
            "Content-Type": "application/json",
            "User-Agent": "KeraMine-FastAPI/1.0",
        }
        if prefer:
            h["Prefer"] = prefer
        return h

    def select(self, table: str, params: dict[str, str] | None = None, limit: int = 1000) -> list[dict]:
        """Mengambil data dengan pagination otomatis bila melebihi 1000 baris."""
        if not self.configured:
            return []
        all_rows: list[dict] = []
        offset = 0
        page_size = min(limit, 1000)
        with httpx.Client(timeout=30.0) as client:
            while True:
                q = dict(params or {})
                headers = self._headers()
                headers["Range"] = f"{offset}-{offset + page_size - 1}"
                resp = client.get(f"{self.rest_url}/{table}", params=q, headers=headers)
                resp.raise_for_status()
                batch = resp.json()
                all_rows.extend(batch)
                if len(batch) < page_size or len(all_rows) >= limit:
                    break
                offset += page_size
        return all_rows[:limit]

    def fetch_all_positive_transactions(self) -> list[dict]:
        """Menarik seluruh transaksi positif (is_return=false) dari Supabase."""
        return self.select(
            "keramine_transactions",
            params={
                "select": "source_row_hash,nota,tgl,year_month,year_week,nama_toko,kode_toko,namabara,kodebara,merk_raw,merk_clean,ukuran,seri,merk_ukuran,qty,satuan,pof,is_return,source_batch",
                "is_return": "eq.false",
                "order": "id.asc",
            },
            limit=50000,
        )

    def upsert_rows(self, table: str, rows: list[dict], on_conflict: str, chunk_size: int = 300) -> int:
        if not self.configured or not rows:
            return 0
        total = 0
        with httpx.Client(timeout=60.0) as client:
            for i in range(0, len(rows), chunk_size):
                chunk = [
                    {k: _clean_json_val(v) for k, v in row.items()}
                    for row in rows[i : i + chunk_size]
                ]
                resp = client.post(
                    f"{self.rest_url}/{table}?on_conflict={on_conflict}",
                    content=json.dumps(chunk, allow_nan=False),
                    headers=self._headers("resolution=merge-duplicates,return=minimal"),
                )
                resp.raise_for_status()
                total += len(chunk)
        return total

    def insert_mining_run(self, run_payload: dict) -> str | None:
        if not self.configured:
            return None
        with httpx.Client(timeout=30.0) as client:
            resp = client.post(
                f"{self.rest_url}/keramine_mining_runs",
                content=json.dumps(run_payload, allow_nan=False),
                headers=self._headers("return=representation"),
            )
            resp.raise_for_status()
            data = resp.json()
            return data[0]["id"] if data else None

    def delete_where(self, table: str, params: dict[str, str]) -> int:
        if not self.configured:
            return 0
        with httpx.Client(timeout=30.0) as client:
            resp = client.delete(
                f"{self.rest_url}/{table}",
                params=params,
                headers=self._headers("return=representation"),
            )
            resp.raise_for_status()
            data = resp.json()
            return len(data) if isinstance(data, list) else 0

