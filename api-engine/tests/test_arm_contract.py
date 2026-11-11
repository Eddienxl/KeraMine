"""Uji kontrak numerik FastAPI mining_service terhadap arm_fixture.json (toleransi <= 1e-6)."""
from __future__ import annotations

import json
import sys
from pathlib import Path

import pandas as pd

ROOT = Path(__file__).resolve().parent.parent.parent
sys.path.insert(0, str(ROOT / "api-engine"))

from mining_service import compute_sota_rules  # noqa: E402


def run_contract_test() -> None:
    fixture_path = ROOT / "data" / "clean" / "arm_fixture.json"
    csv_path = ROOT / "data" / "clean" / "transactions_clean.csv"

    fixture = json.loads(fixture_path.read_text(encoding="utf-8"))
    df_tx = pd.read_csv(csv_path)

    rules_df, meta = compute_sota_rules(
        df_tx=df_tx,
        basket_type=fixture["basket_type"],
        item_level=fixture["item_level"],
        algorithm="apriori",
        min_support=fixture["min_support"],
        min_confidence=fixture["min_confidence"],
    )

    assert meta["total_baskets"] == fixture["total_baskets"], f"Basket mismatch: {meta['total_baskets']}"
    assert len(rules_df) == fixture["total_rules_at_level"], f"Rules count mismatch: {len(rules_df)}"

    by_id = rules_df.set_index("id").to_dict(orient="index")
    tol = fixture["tolerance"]
    max_diff = 0.0

    for ref in fixture["top_10_reference_rules"]:
        rid = ref["id"]
        assert rid in by_id, f"Rule {rid} ({ref['antecedents_str']} -> {ref['consequents_str']}) tidak ditemukan!"
        got = by_id[rid]
        for field in [
            "support",
            "confidence",
            "lift",
            "leverage",
            "conviction",
            "kulczynski",
            "zhang_metric",
            "avg_qty_dus",
            "utility_score",
            "p_value_fisher",
            "q_value_bh",
        ]:
            diff = abs(float(got[field]) - float(ref[field]))
            max_diff = max(max_diff, diff)
            assert diff <= tol, f"Parity FAIL on {rid}.{field}: got={got[field]} ref={ref[field]} diff={diff}"

    print(
        f"[PASS] Kontrak ARM Fixture lolos 10/10 aturan referensi | "
        f"Total rules={len(rules_df)} | Max diff={max_diff:.3e} <= {tol:.1e} | "
        f"Exec time={meta['exec_time_ms']} ms ({meta['engine_backend']})"
    )


if __name__ == "__main__":
    run_contract_test()
