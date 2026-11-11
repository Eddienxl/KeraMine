"""Mesin Komputasi SOTA Association Rule Mining untuk KeraMine (FastAPI)."""
from __future__ import annotations

import hashlib
import math
import time
from itertools import combinations
from typing import Literal

import numpy as np
import pandas as pd
from scipy.stats import fisher_exact

try:
    from mlxtend.frequent_patterns import apriori as mlx_apriori
    from mlxtend.frequent_patterns import association_rules as mlx_association_rules
    from mlxtend.frequent_patterns import fpgrowth as mlx_fpgrowth
    from mlxtend.preprocessing import TransactionEncoder

    HAS_MLXTEND = True
except ImportError:
    HAS_MLXTEND = False

ITEM_LEVEL_TO_COL = {
    "MERK": "merk_clean",
    "UKURAN": "ukuran",
    "MERK_UKURAN": "merk_ukuran",
    "SERI": "seri",
    "NAMABARA": "namabara",
}

DEFAULT_THRESHOLDS = {
    ("store_month", "MERK"): (0.0200, 0.15),
    ("store_month", "UKURAN"): (0.0200, 0.20),
    ("store_month", "MERK_UKURAN"): (0.0020, 0.10),
    ("store_month", "SERI"): (0.0015, 0.10),
    ("store_month", "NAMABARA"): (0.0010, 0.10),
    ("store_week", "MERK"): (0.0020, 0.05),
    ("store_week", "UKURAN"): (0.0100, 0.10),
    ("store_week", "MERK_UKURAN"): (0.0004, 0.05),
    ("store_week", "SERI"): (0.0003, 0.05),
    ("store_week", "NAMABARA"): (0.0003, 0.05),
}


def benjamini_hochberg(p_values: np.ndarray) -> np.ndarray:
    m = len(p_values)
    if m == 0:
        return np.array([], dtype=float)
    order = np.argsort(p_values)
    sorted_p = p_values[order]
    ranks = np.arange(1, m + 1, dtype=float)
    q_sorted = sorted_p * m / ranks
    q_sorted = np.minimum.accumulate(q_sorted[::-1])[::-1]
    q_sorted = np.clip(q_sorted, 0.0, 1.0)
    q_vals = np.empty(m, dtype=float)
    q_vals[order] = q_sorted
    return q_vals


def make_rule_id(basket_type: str, item_level: str, ant: list[str], con: list[str]) -> str:
    payload = f"{basket_type}|{item_level}|{' + '.join(ant)}->{' + '.join(con)}"
    return hashlib.sha256(payload.encode("utf-8")).hexdigest()[:32]


def _extract_candidate_rules_vectorized(
    onehot_np: np.ndarray,
    columns: list[str],
    min_support: float,
    min_confidence: float,
    max_len: int = 3,
) -> list[tuple[list[str], list[str]]]:
    """Ekstraksi cepat frequent itemsets (1..3 item) & rules dengan operasi matriks NumPy."""
    n_baskets = onehot_np.shape[0]
    min_cnt = math.ceil(min_support * n_baskets - 1e-12)
    X = onehot_np.astype(np.int32)
    col_counts = X.sum(axis=0)
    freq1_idx = np.where(col_counts >= min_cnt)[0]
    if len(freq1_idx) < 2:
        return []

    # 2-itemset via dot product matriks
    Xf = X[:, freq1_idx]
    cooc = Xf.T @ Xf
    freq_sets: dict[frozenset[int], int] = {
        frozenset([int(idx)]): int(col_counts[idx]) for idx in freq1_idx
    }

    freq2_pairs: list[tuple[int, int]] = []
    m = len(freq1_idx)
    for i in range(m):
        for j in range(i + 1, m):
            cnt = int(cooc[i, j])
            if cnt >= min_cnt:
                u, v = int(freq1_idx[i]), int(freq1_idx[j])
                freq2_pairs.append((u, v))
                freq_sets[frozenset([u, v])] = cnt

    # 3-itemset kandidat hanya dari pasangan yang sudah frequent
    if max_len >= 3 and len(freq2_pairs) >= 3:
        pair_set = {frozenset(p) for p in freq2_pairs}
        cand3: set[tuple[int, int, int]] = set()
        nodes = sorted({x for p in freq2_pairs for x in p})
        adj: dict[int, set[int]] = {u: set() for u in nodes}
        for u, v in freq2_pairs:
            adj[u].add(v)
            adj[v].add(u)
        for u, v in freq2_pairs:
            for w in adj[u] & adj[v]:
                if w > v:
                    if frozenset([u, w]) in pair_set and frozenset([v, w]) in pair_set:
                        cand3.add((u, v, w))
        for u, v, w in cand3:
            cnt = int((onehot_np[:, u] & onehot_np[:, v] & onehot_np[:, w]).sum())
            if cnt >= min_cnt:
                freq_sets[frozenset([u, v, w])] = cnt

    # Bangkitkan rules dari seluruh frequent itemsets berukuran >= 2
    candidate_rules: list[tuple[list[str], list[str]]] = []
    for fset, sup_cnt in freq_sets.items():
        if len(fset) < 2:
            continue
        items_sorted = sorted(fset)
        for r in range(1, len(items_sorted)):
            for ant_tuple in combinations(items_sorted, r):
                ant_fs = frozenset(ant_tuple)
                con_fs = fset - ant_fs
                ant_cnt = freq_sets.get(ant_fs)
                if not ant_cnt:
                    continue
                if (sup_cnt / ant_cnt) >= min_confidence - 1e-12:
                    ant_names = sorted(columns[idx] for idx in ant_fs)
                    con_names = sorted(columns[idx] for idx in con_fs)
                    candidate_rules.append((ant_names, con_names))
    return candidate_rules


def compute_sota_rules(
    df_tx: pd.DataFrame,
    basket_type: Literal["store_month", "store_week"] = "store_month",
    item_level: str = "SERI",
    algorithm: Literal["apriori", "fpgrowth"] = "apriori",
    min_support: float | None = None,
    min_confidence: float | None = None,
    pof_filter: str | None = None,
    max_len: int = 3,
) -> tuple[pd.DataFrame, dict]:
    """Menambang aturan asosiasi multi-level + metrik SOTA + Utility-QTY + Fisher-BH."""
    t0 = time.perf_counter()
    item_level = item_level.upper()
    if item_level not in ITEM_LEVEL_TO_COL:
        raise ValueError(f"item_level tidak valid: {item_level}")

    item_col = ITEM_LEVEL_TO_COL[item_level]
    df_work = df_tx.copy()
    if "is_return" in df_work.columns:
        df_work = df_work[~df_work["is_return"].astype(bool)].copy()
    else:
        df_work = df_work[pd.to_numeric(df_work["qty"], errors="coerce") > 0].copy()

    if pof_filter and pof_filter.upper() != "ALL":
        df_work = df_work[df_work["pof"].astype(str).str.upper() == pof_filter.upper()].copy()

    if df_work.empty:
        return pd.DataFrame(), {"total_baskets": 0, "exec_time_ms": 0.0, "rules_found": 0, "significant_rules": 0}

    basket_col = "year_month" if basket_type == "store_month" else "year_week"
    df_work["_basket_id"] = df_work["nama_toko"].astype(str) + "|" + df_work[basket_col].astype(str)
    df_work["_item"] = df_work[item_col].astype(str)

    if min_support is None or min_confidence is None:
        def_sup, def_conf = DEFAULT_THRESHOLDS.get((basket_type, item_level), (0.002, 0.10))
        min_support = def_sup if min_support is None else min_support
        min_confidence = def_conf if min_confidence is None else min_confidence

    grouped = df_work.groupby("_basket_id")["_item"].apply(lambda s: sorted(set(s)))
    basket_ids = grouped.index.tolist()
    transactions = grouped.tolist()
    N = len(basket_ids)

    all_items = sorted({it for tx in transactions for it in tx})
    col_to_idx = {col: idx for idx, col in enumerate(all_items)}
    onehot_np = np.zeros((N, len(all_items)), dtype=bool)
    for row_i, tx in enumerate(transactions):
        for it in tx:
            onehot_np[row_i, col_to_idx[it]] = True

    if HAS_MLXTEND:
        onehot_df = pd.DataFrame(onehot_np, index=basket_ids, columns=all_items)
        if algorithm == "fpgrowth":
            fi = mlx_fpgrowth(onehot_df, min_support=min_support, use_colnames=True, max_len=max_len)
        else:
            fi = mlx_apriori(
                onehot_df,
                min_support=min_support,
                use_colnames=True,
                max_len=max_len,
                low_memory=(len(all_items) > 100),
            )
        if fi.empty or (fi["itemsets"].apply(len) >= 2).sum() == 0:
            candidate_pairs = []
        else:
            rdf = mlx_association_rules(fi, metric="confidence", min_threshold=min_confidence)
            candidate_pairs = [
                (sorted(list(r["antecedents"])), sorted(list(r["consequents"])))
                for _, r in rdf.iterrows()
            ]
    else:
        candidate_pairs = _extract_candidate_rules_vectorized(
            onehot_np, all_items, min_support, min_confidence, max_len=max_len
        )

    if not candidate_pairs:
        elapsed_ms = (time.perf_counter() - t0) * 1000.0
        return pd.DataFrame(), {
            "total_baskets": N,
            "exec_time_ms": round(elapsed_ms, 2),
            "rules_found": 0,
            "significant_rules": 0,
        }

    # Matriks kuantitas QTY per (basket_id, item)
    qty_df = (
        df_work.groupby(["_basket_id", "_item"])["qty"]
        .sum()
        .unstack(fill_value=0.0)
        .reindex(index=basket_ids, columns=all_items, fill_value=0.0)
    )
    qty_np = qty_df.to_numpy(dtype=float)

    rows = []
    for ant, con in candidate_pairs:
        ant_idx = [col_to_idx[x] for x in ant]
        con_idx = [col_to_idx[x] for x in con]
        union_idx = ant_idx + con_idx

        has_a = onehot_np[:, ant_idx].all(axis=1)
        has_b = onehot_np[:, con_idx].all(axis=1)
        has_ab = has_a & has_b

        n_a = int(has_a.sum())
        n_b = int(has_b.sum())
        n_ab = int(has_ab.sum())

        p_a = n_a / N
        p_b = n_b / N
        p_ab = n_ab / N

        supp = p_ab
        conf = n_ab / n_a if n_a > 0 else 0.0
        lift = conf / p_b if p_b > 0 else 0.0
        lev = p_ab - (p_a * p_b)
        conv = (1.0 - p_b) / (1.0 - conf) if conf < 1.0 else 999.0
        kulc = 0.5 * ((n_ab / n_a if n_a > 0 else 0.0) + (n_ab / n_b if n_b > 0 else 0.0))

        denom_z = max(p_ab * (1.0 - p_a), p_a * (p_b - p_ab))
        zhang = (p_ab - p_a * p_b) / denom_z if denom_z > 0 else 0.0

        ab_qty = qty_np[has_ab][:, union_idx].sum(axis=1) if n_ab > 0 else np.array([0.0])
        total_qty_dus = float(ab_qty.sum())
        avg_qty_dus = float(ab_qty.mean()) if n_ab > 0 else 0.0
        utility_score = conf * max(0.0, zhang) * math.log10(1.0 + avg_qty_dus)

        _, p_val = fisher_exact(
            [[n_ab, n_a - n_ab], [n_b - n_ab, N - n_a - n_b + n_ab]],
            alternative="greater",
        )

        rows.append(
            {
                "id": make_rule_id(basket_type, item_level, ant, con),
                "basket_type": basket_type,
                "item_level": item_level,
                "antecedents": ant,
                "consequents": con,
                "antecedents_str": " + ".join(ant),
                "consequents_str": " + ".join(con),
                "n_a": n_a,
                "n_b": n_b,
                "n_ab": n_ab,
                "total_baskets": N,
                "support": supp,
                "confidence": conf,
                "lift": lift,
                "leverage": lev,
                "conviction": conv,
                "kulczynski": kulc,
                "zhang_metric": zhang,
                "total_qty_dus": total_qty_dus,
                "avg_qty_dus": avg_qty_dus,
                "utility_score": utility_score,
                "p_value_fisher": float(p_val),
            }
        )

    out_df = pd.DataFrame(rows)
    out_df["q_value_bh"] = benjamini_hochberg(out_df["p_value_fisher"].to_numpy())
    out_df["is_significant"] = out_df["p_value_fisher"] < 0.05
    elapsed_ms = (time.perf_counter() - t0) * 1000.0

    meta = {
        "total_baskets": N,
        "exec_time_ms": round(elapsed_ms, 2),
        "rules_found": int(len(out_df)),
        "significant_rules": int(out_df["is_significant"].sum()),
        "fdr_significant_rules": int((out_df["q_value_bh"] < 0.10).sum()),
        "engine_backend": "mlxtend" if HAS_MLXTEND else "numpy_vectorized",
        "min_support": float(min_support),
        "min_confidence": float(min_confidence),
    }
    return out_df, meta

