"""
KeraMine Analysis Lab — Aplikasi Streamlit Interaktif (Fase 6.2)
SOTA Multi-Level Association Rule Mining & Real-Time n8n/Supabase Monitor
"""
from __future__ import annotations

import json
import math
import os
from pathlib import Path
from typing import Any

import httpx
import networkx as nx
import numpy as np
import pandas as pd
import plotly.express as px
import plotly.graph_objects as go
import streamlit as st

from i18n import inject_theme_css, t

st.set_page_config(
    page_title="KeraMine Analysis Lab — SOTA ARM & n8n Pipeline",
    page_icon="K",
    layout="wide",
    initial_sidebar_state="expanded",
)

ROOT_DIR = Path(__file__).resolve().parent.parent
CLEAN_DIR = ROOT_DIR / "data" / "clean"
ENV_PATH = ROOT_DIR / ".env"


def load_env_map() -> dict[str, str]:
    env: dict[str, str] = {}
    if ENV_PATH.exists():
        for line in ENV_PATH.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            env[k.strip()] = v.strip()
    return env


ENV = load_env_map()
SUPABASE_REST = ENV.get("SUPABASE_REST_URL", "").rstrip("/")
SUPABASE_PUB_KEY = ENV.get("SUPABASE_PUBLISHABLE_KEY", "")
FASTAPI_BASE = f"http://127.0.0.1:{ENV.get('FASTAPI_PORT', '8001')}"
N8N_WEBHOOK_URL = f"http://127.0.0.1:{ENV.get('N8N_PORT', '5678')}/webhook/keramine-ingest"


@st.cache_data(show_spinner=False)
def load_local_transactions() -> pd.DataFrame:
    return pd.read_csv(CLEAN_DIR / "transactions_clean.csv")


@st.cache_data(show_spinner=False)
def load_local_returns() -> pd.DataFrame:
    return pd.read_csv(CLEAN_DIR / "returns_log.csv")


@st.cache_data(show_spinner=False)
def load_audit_summary() -> dict[str, Any]:
    return json.loads((CLEAN_DIR / "cleaning_audit_summary.json").read_text(encoding="utf-8"))


@st.cache_data(show_spinner=False)
def load_bench_summary() -> dict[str, Any]:
    return json.loads((CLEAN_DIR / "sota_benchmark_summary.json").read_text(encoding="utf-8"))


@st.cache_data(show_spinner=False)
def load_temporal_eval() -> dict[str, Any]:
    return json.loads((CLEAN_DIR / "temporal_eval_report.json").read_text(encoding="utf-8"))


@st.cache_data(show_spinner=False)
def load_fixture() -> dict[str, Any]:
    return json.loads((CLEAN_DIR / "arm_fixture.json").read_text(encoding="utf-8"))


@st.cache_data(show_spinner=False)
def load_store_profiles() -> list[dict[str, Any]]:
    return json.loads((CLEAN_DIR / "store_profiles.json").read_text(encoding="utf-8"))


@st.cache_data(show_spinner=False)
def load_local_rules() -> pd.DataFrame:
    df_r = pd.read_csv(CLEAN_DIR / "mined_rules_catalog.csv")
    if "antecedents" in df_r.columns:
        df_r["antecedents_list"] = df_r["antecedents"].apply(lambda s: json.loads(s) if isinstance(s, str) else s)
        df_r["consequents_list"] = df_r["consequents"].apply(lambda s: json.loads(s) if isinstance(s, str) else s)
    return df_r


def fetch_supabase_table(table: str, params: dict[str, str]) -> list[dict] | None:
    if not SUPABASE_REST or not SUPABASE_PUB_KEY:
        return None
    try:
        headers = {
            "apikey": SUPABASE_PUB_KEY,
            "Authorization": f"Bearer {SUPABASE_PUB_KEY}",
        }
        with httpx.Client(timeout=8.0) as client:
            resp = client.get(f"{SUPABASE_REST}/{table}", params=params, headers=headers)
            resp.raise_for_status()
            return resp.json()
    except Exception:
        return None


def fetch_rules_live_or_local(use_live: bool) -> tuple[pd.DataFrame, str]:
    if use_live:
        rows = fetch_supabase_table(
            "keramine_rules",
            {
                "select": "*",
                "order": "is_significant.desc,utility_score.desc,lift.desc",
                "limit": "1000",
            },
        )
        if rows:
            df_r = pd.DataFrame(rows)
            df_r["antecedents_list"] = df_r["antecedents"]
            df_r["consequents_list"] = df_r["consequents"]
            df_r["is_fdr_significant"] = df_r["q_value_bh"] < 0.10
            return df_r, "Supabase Cloud (Live)"
    return load_local_rules(), "Artefak Lokal (data/clean)"


def build_plotly_network(df_rules: pd.DataFrame, tokens: dict[str, str], max_edges: int = 25) -> go.Figure:
    sub = df_rules.sort_values(by=["utility_score", "lift"], ascending=False).head(max_edges)
    G = nx.DiGraph()
    for _, r in sub.iterrows():
        a = r["antecedents_str"]
        c = r["consequents_str"]
        G.add_node(a, dus=G.nodes.get(a, {}).get("dus", 0) + float(r["avg_qty_dus"]))
        G.add_node(c, dus=G.nodes.get(c, {}).get("dus", 0) + float(r["avg_qty_dus"]))
        G.add_edge(
            a,
            c,
            lift=float(r["lift"]),
            conf=float(r["confidence"]),
            zhang=float(r["zhang_metric"]),
            dus=float(r["avg_qty_dus"]),
            sig=bool(r["is_significant"]),
        )

    pos = nx.spring_layout(G, seed=42, k=1.35)
    edge_traces = []
    for u, v, d in G.edges(data=True):
        x0, y0 = pos[u]
        x1, y1 = pos[v]
        color = tokens["accent"] if d["sig"] else tokens["text_muted"]
        width = max(1.2, min(5.0, d["lift"] * 0.7))
        edge_traces.append(
            go.Scatter(
                x=[x0, x1, None],
                y=[y0, y1, None],
                mode="lines",
                line=dict(width=width, color=color),
                opacity=0.65,
                hoverinfo="text",
                text=(
                    f"{u} -> {v}<br>"
                    f"Confidence: {d['conf']*100:.1f}% | Lift: {d['lift']:.2f}x<br>"
                    f"Zhang: {d['zhang']:+.3f} | Avg Volume: {d['dus']:.1f} DUS"
                ),
                showlegend=False,
            )
        )

    node_x, node_y, node_text, node_size, node_hover = [], [], [], [], []
    for node in G.nodes():
        x, y = pos[node]
        deg = G.degree(node)
        node_x.append(x)
        node_y.append(y)
        node_text.append(node)
        node_size.append(16 + deg * 4)
        node_hover.append(f"<b>{node}</b><br>Koneksi Aturan: {deg}")

    node_trace = go.Scatter(
        x=node_x,
        y=node_y,
        mode="markers+text",
        text=node_text,
        textposition="top center",
        hovertext=node_hover,
        hoverinfo="text",
        marker=dict(
            size=node_size,
            color=tokens["primary"],
            line=dict(width=1.5, color=tokens["surface"]),
        ),
        showlegend=False,
    )

    fig = go.Figure(data=edge_traces + [node_trace])
    fig.update_layout(
        template=tokens["plotly_template"],
        paper_bgcolor=tokens["surface"],
        plot_bgcolor=tokens["surface"],
        font=dict(color=tokens["text"]),
        height=500,
        margin=dict(l=15, r=15, t=35, b=15),
        xaxis=dict(showgrid=False, zeroline=False, showticklabels=False),
        yaxis=dict(showgrid=False, zeroline=False, showticklabels=False),
        title="Interactive Association Network (Hijau/Cyan = Lolos Uji Fisher p < 0.05)",
    )
    return fig


def main() -> None:
    with st.sidebar:
        st.markdown("### KeraMine Analysis Lab")
        c1, c2 = st.columns(2)
        with c1:
            lang = st.selectbox("Bahasa", ["id", "en"], format_func=lambda x: "ID" if x == "id" else "EN")
        with c2:
            theme = st.selectbox(
                "Tema",
                ["light", "dark"],
                format_func=lambda x: "Light (C)" if x == "light" else "Dark (B)",
            )
        tokens = inject_theme_css(theme)

        st.markdown("---")
        use_live_sb = st.toggle("Baca Rules Live dari Supabase", value=True)
        basket_filter = st.selectbox(
            "Unit Keranjang (Basket)",
            ["store_month", "store_week"],
            format_func=lambda x: "Toko + Bulan (Utama)" if x == "store_month" else "Toko + Minggu (Taktis)",
        )
        level_filter = st.selectbox(
            "Level Hierarki Item",
            ["SERI", "MERK_UKURAN", "MERK", "UKURAN", "NAMABARA"],
            index=0,
        )
        only_sig = st.checkbox("Hanya Lolos Fisher (p < 0.05)", value=False)
        only_fdr = st.checkbox("Hanya Lolos FDR-BH (q < 0.10)", value=False)
        min_lift = st.slider("Minimum Lift", 0.5, 6.0, 1.0, 0.1)
        min_zhang = st.slider("Minimum Zhang's Metric", -0.5, 0.9, 0.0, 0.05)

        st.markdown("---")
        st.markdown("**Tautan Ekosistem**")
        st.markdown("- Laravel Portal B2B: `http://127.0.0.1:8000`")
        st.markdown("- FastAPI Docs: `http://127.0.0.1:8001/docs`")
        st.markdown("- n8n Editor: `http://localhost:5678`")

    st.title(t("title", lang))
    st.caption(t("subtitle", lang))

    df_tx = load_local_transactions()
    df_ret = load_local_returns()
    audit = load_audit_summary()
    bench = load_bench_summary()
    temporal = load_temporal_eval()
    fixture = load_fixture()
    profiles = load_store_profiles()
    df_rules_all, rules_source = fetch_rules_live_or_local(use_live_sb)

    # Filter rules sesuai sidebar
    mask = (
        (df_rules_all["basket_type"] == basket_filter)
        & (df_rules_all["item_level"] == level_filter)
        & (df_rules_all["lift"] >= min_lift)
        & (df_rules_all["zhang_metric"] >= min_zhang)
    )
    if only_sig:
        mask &= df_rules_all["is_significant"].astype(bool)
    if only_fdr and "q_value_bh" in df_rules_all.columns:
        mask &= df_rules_all["q_value_bh"] < 0.10
    df_filtered = df_rules_all.loc[mask].copy()

    # KPI Top Ribbon
    k1, k2, k3, k4, k5 = st.columns(5)
    kpis = [
        ("Transaksi Positif", f"{len(df_tx):,}", f"+{len(df_ret)} baris QTY=0 terpisah"),
        ("Total Volume Kardus", f"{int(df_tx['qty'].sum()):,} DUS", f"Avg {audit['avg_qty_dus_per_line']} DUS/baris"),
        ("Keranjang Utama", "3,949 Toko-Bulan", "92.53% multi-item (vs NOTA 0.01%)"),
        ("Rules Terfilter", f"{len(df_filtered)}", f"Dari {len(df_rules_all)} total ({rules_source})"),
        (
            "Max Zhang / Lift",
            f"{df_filtered['zhang_metric'].max():+.3f}" if not df_filtered.empty else "—",
            f"Max Lift {df_filtered['lift'].max():.2f}x" if not df_filtered.empty else "Sesuaikan filter",
        ),
    ]
    for col, (lbl, val, sub) in zip([k1, k2, k3, k4, k5], kpis):
        col.markdown(
            f'<div class="km-card"><div class="km-kpi-label">{lbl}</div>'
            f'<div class="km-kpi-value">{val}</div>'
            f'<div class="km-kpi-sub">{sub}</div></div>',
            unsafe_allow_html=True,
        )

    tab1, tab2, tab3, tab4, tab5 = st.tabs(
        [t("tab_eda", lang), t("tab_explorer", lang), t("tab_rec", lang), t("tab_sota", lang), t("tab_live", lang)]
    )

    # =========================================================================
    # TAB 1: EXECUTIVE EDA & FORENSIK DATASET
    # =========================================================================
    with tab1:
        st.subheader("Audit Forensik Granularitas Keranjang & Distribusi B2B Keramik")
        df_gran = pd.DataFrame(audit["basket_granularity_comparison"])
        c_left, c_right = st.columns(2)
        with c_left:
            fig_gran = px.bar(
                df_gran,
                x="definisi_keranjang",
                y="rasio_multi_item_pct",
                text="rasio_multi_item_pct",
                color="rasio_multi_item_pct",
                color_continuous_scale="Viridis",
                template=tokens["plotly_template"],
                title="Rasio Multi-Item (>= 2 Produk) per Definisi Keranjang",
            )
            fig_gran.update_traces(texttemplate="%{text:.2f}%", textposition="outside")
            fig_gran.update_layout(paper_bgcolor=tokens["surface"], plot_bgcolor=tokens["surface"], yaxis_range=[0, 115])
            st.plotly_chart(fig_gran, width="stretch")
        with c_right:
            size_df = df_tx.groupby("ukuran", as_index=False)["qty"].sum().sort_values("qty", ascending=True)
            fig_size = px.bar(
                size_df,
                x="qty",
                y="ukuran",
                orientation="h",
                text="qty",
                template=tokens["plotly_template"],
                title="Volume Distribusi Kardus (DUS) per Dimensi Ukuran",
                color_discrete_sequence=[tokens["primary"]],
            )
            fig_size.update_traces(texttemplate="%{text:,}", textposition="outside")
            fig_size.update_layout(paper_bgcolor=tokens["surface"], plot_bgcolor=tokens["surface"])
            st.plotly_chart(fig_size, width="stretch")

        c_pof, c_ret = st.columns(2)
        with c_pof:
            df_pof = pd.DataFrame(audit["pof_summary"])
            st.markdown("**Ringkasan Kinerja 4 Tenaga Penjual (`POF`)**")
            st.dataframe(df_pof, width="stretch", hide_index=True)
        with c_ret:
            st.markdown(f"**Log Audit Transaksi Retur / Nol (`QTY = 0`: {len(df_ret)} baris)**")
            st.dataframe(df_ret[["tgl", "nota", "nama_toko", "namabara", "merk_clean", "pof"]].head(10), width="stretch", hide_index=True)

    # =========================================================================
    # TAB 2: MULTI-LEVEL RULE EXPLORER & NETWORK GRAPH
    # =========================================================================
    with tab2:
        st.subheader(f"Eksplorasi Rules: {basket_filter} | Level {level_filter}")
        if df_filtered.empty:
            st.warning("Tidak ada aturan yang memenuhi ambang filter saat ini. Turunkan Minimum Lift atau matikan filter ketat di sidebar.")
        else:
            max_edges = st.slider("Jumlah Edge Maksimum di Network Graph (anti-padat)", 5, min(60, len(df_filtered)), min(25, len(df_filtered)))
            c_g1, c_g2 = st.columns([1.15, 1])
            with c_g1:
                st.plotly_chart(build_plotly_network(df_filtered, tokens, max_edges=max_edges), width="stretch")
            with c_g2:
                fig_sc = px.scatter(
                    df_filtered,
                    x=df_filtered["support"] * 100,
                    y=df_filtered["confidence"] * 100,
                    color="zhang_metric",
                    size="avg_qty_dus",
                    hover_name="antecedents_str",
                    hover_data={"consequents_str": True, "lift": ":.2f", "p_value_fisher": ":.4g"},
                    color_continuous_scale="Tealgrn",
                    template=tokens["plotly_template"],
                    labels={"x": "Support (%)", "y": "Confidence (%)", "zhang_metric": "Zhang's Metric"},
                    title="Peta Support vs Confidence (Warna = Zhang, Ukuran = Avg DUS)",
                )
                fig_sc.update_layout(height=500, paper_bgcolor=tokens["surface"], plot_bgcolor=tokens["surface"])
                st.plotly_chart(fig_sc, width="stretch")

            cols_disp = [
                "antecedents_str", "consequents_str", "n_ab", "support", "confidence",
                "lift", "zhang_metric", "kulczynski", "avg_qty_dus", "utility_score",
                "p_value_fisher", "q_value_bh", "is_significant",
            ]
            st.dataframe(
                df_filtered[cols_disp].sort_values(by=["is_significant", "utility_score", "lift"], ascending=[False, False, False]),
                width="stretch",
                hide_index=True,
            )

    # =========================================================================
    # TAB 3: SMART RECOMMENDER & INSPEKTOR KONTINGENSI 2x2
    # =========================================================================
    with tab3:
        st.subheader("Rekomendasi Bundling B2B & Transparansi Tabel Kontingensi 2x2")
        prof_map = {p["nama_toko"]: p for p in profiles}
        col_s1, col_s2 = st.columns(2)
        with col_s1:
            sel_store = st.selectbox("Pilih Toko Pelanggan B2B (237 Toko)", sorted(prof_map.keys()))
            st_prof = prof_map[sel_store]
            st.info(
                f"**{sel_store}** | Sales Dominan (`POF`): **{st_prof['dominant_pof']}** | "
                f"Keranjang Bulanan: **{st_prof['total_baskets_month']}** | Total Order: **{int(st_prof['total_qty_dus']):,} DUS**"
            )
        with col_s2:
            item_col_map = {"MERK": "merk_clean", "UKURAN": "ukuran", "MERK_UKURAN": "merk_ukuran", "SERI": "seri", "NAMABARA": "namabara"}
            icol = item_col_map[level_filter]
            all_items_lv = sorted(df_tx[icol].astype(str).unique().tolist())
            default_item = "DIEGO" if "DIEGO" in all_items_lv else all_items_lv[0]
            sel_item = st.selectbox(f"Pilih Item Pemicu ({level_filter})", all_items_lv, index=all_items_lv.index(default_item))

        # Cari aturan cocok lalu lengkapi dengan Volume Popularity Fallback
        sub_lv = df_rules_all[(df_rules_all["basket_type"] == basket_filter) & (df_rules_all["item_level"] == level_filter)].copy()
        matched = sub_lv[sub_lv["antecedents_list"].apply(lambda a: sel_item in a)].sort_values(
            by=["is_significant", "utility_score", "lift"], ascending=[False, False, False]
        )

        st.markdown(f"#### Rekomendasi Paket Pasangan untuk `{sel_item}`")
        shown = set([sel_item])
        rec_count = 0
        for _, r in matched.head(5).iterrows():
            for cons in r["consequents_list"]:
                if cons in shown:
                    continue
                shown.add(cons)
                rec_count += 1
                badge = '<span class="km-badge-sig">Significant Rule (Fisher p &lt; 0.05)</span>' if r["is_significant"] else '<span class="km-badge-exp">Exploratory ARM Rule</span>'
                st.markdown(
                    f'<div class="km-card"><b>#{rec_count} &rarr; Tawarkan: {cons}</b> &nbsp; {badge}<br>'
                    f'<small>Confidence: <b>{r["confidence"]*100:.1f}%</b> &bull; Lift: <b>{r["lift"]:.2f}x</b> &bull; '
                    f'Zhang: <b>{r["zhang_metric"]:+.3f}</b> &bull; Avg Volume: <b>{r["avg_qty_dus"]:.1f} DUS</b> &bull; '
                    f'Fisher p-value: <b>{r["p_value_fisher"]:.4g}</b></small></div>',
                    unsafe_allow_html=True,
                )
                with st.expander(f"Lihat Transparansi Kontingensi 2x2: {sel_item} -> {cons}"):
                    N_b = int(r["total_baskets"])
                    n_a = int(r["n_a"])
                    n_b = int(r["n_b"])
                    n_ab = int(r["n_ab"])
                    c_tab = pd.DataFrame(
                        {
                            f"Memuat {cons}": [n_ab, n_b - n_ab, n_b],
                            f"Tidak Memuat {cons}": [n_a - n_ab, N_b - n_a - n_b + n_ab, N_b - n_b],
                            "Total": [n_a, N_b - n_a, N_b],
                        },
                        index=[f"Memuat {sel_item}", f"Tidak Memuat {sel_item}", "Total Keranjang"],
                    )
                    st.dataframe(c_tab, width="stretch")

        if rec_count < 5:
            pop_series = df_tx.groupby(icol)["qty"].sum().sort_values(ascending=False)
            for name, qty_sum in pop_series.items():
                if name not in shown:
                    shown.add(name)
                    rec_count += 1
                    st.markdown(
                        f'<div class="km-card"><b>#{rec_count} &rarr; Tawarkan: {name}</b> &nbsp; '
                        f'<span class="km-badge-pop">Volume Popularity Fallback (Top DUS)</span><br>'
                        f'<small>Total Distribusi Historis: <b>{int(qty_sum):,} DUS</b> (ditampilkan transparan karena aturan spesifik &lt; 5)</small></div>',
                        unsafe_allow_html=True,
                    )
                if rec_count >= 5:
                    break

    # =========================================================================
    # TAB 4: SOTA BENCHMARK & EVALUASI TEMPORAL
    # =========================================================================
    with tab4:
        st.subheader("Benchmark Komputasi Apriori vs FP-Growth vs FP-Max & Validasi Kontrak")
        df_b = pd.DataFrame(bench["algorithm_benchmark_store_month"])
        c_b1, c_b2 = st.columns(2)
        with c_b1:
            fig_bm = px.bar(
                df_b.melt(id_vars="item_level", value_vars=["apriori_ms", "fpgrowth_ms", "fpmax_ms"], var_name="Algoritma", value_name="Milidetik"),
                x="item_level",
                y="Milidetik",
                color="Algoritma",
                barmode="group",
                template=tokens["plotly_template"],
                title="Waktu Eksekusi (ms) — Apriori Vektorisasi vs FP-Tree",
            )
            fig_bm.update_layout(paper_bgcolor=tokens["surface"], plot_bgcolor=tokens["surface"])
            st.plotly_chart(fig_bm, width="stretch")
        with c_b2:
            fig_pr = px.bar(
                df_b,
                x="item_level",
                y="fpmax_pruning_pct",
                text="fpmax_pruning_pct",
                template=tokens["plotly_template"],
                color_discrete_sequence=[tokens["accent"]],
                title="Persentase Pemangkasan Itemset Redundan oleh FP-Max (%)",
            )
            fig_pr.update_traces(texttemplate="%{text:.2f}%", textposition="outside")
            fig_pr.update_layout(paper_bgcolor=tokens["surface"], plot_bgcolor=tokens["surface"], yaxis_range=[0, 95])
            st.plotly_chart(fig_pr, width="stretch")

        st.markdown("**Evaluasi Prediktif Out-of-Time (Train Agu 2022–Sep 2023 vs Test Okt–Des 2023)**")
        st.dataframe(pd.DataFrame(temporal["evaluations"]), width="stretch", hide_index=True)

        st.markdown("**Kontrak Paritas Numerik (`arm_fixture.json` — Top-10 Acuan `SERI`)**")
        st.dataframe(
            pd.DataFrame(fixture["top_10_reference_rules"])[
                ["antecedents_str", "consequents_str", "support", "confidence", "lift", "zhang_metric", "utility_score", "p_value_fisher", "q_value_bh"]
            ],
            width="stretch",
            hide_index=True,
        )

    # =========================================================================
    # TAB 5: LIVE PIPELINE N8N, FASTAPI & SUPABASE MONITOR
    # =========================================================================
    with tab5:
        st.subheader("Monitor Pipeline Auto-Realtime (n8n + FastAPI + Supabase)")
        c_st1, c_st2, c_st3 = st.columns(3)
        api_health = None
        try:
            with httpx.Client(timeout=3.0) as client:
                api_health = client.get(f"{FASTAPI_BASE}/api/health").json()
        except Exception:
            api_health = None

        runs_live = fetch_supabase_table("keramine_mining_runs", {"select": "*", "order": "created_at.desc", "limit": "15"})

        c_st1.metric("Supabase Cloud", "ONLINE (odtxjphk...)" if runs_live is not None else "OFFLINE / FALLBACK")
        c_st2.metric("FastAPI Engine (:8001)", "ONLINE" if api_health and api_health.get("status") == "ok" else "BELUM AKTIF")
        c_st3.metric("Total Riwayat Mining Run", len(runs_live) if runs_live else len(bench["mining_runs_summary"]))

        st.markdown("#### Simulasi Transaksi Masuk Real-Time (Demo UAS)")
        col_sim1, col_sim2 = st.columns([2, 1])
        with col_sim1:
            with st.form("sim_form"):
                sim_toko = st.text_input("Nama Toko B2B", value="KUNCI SUKSES, TOKO")
                sim_pof = st.selectbox("Sales (POF)", ["DIAN", "ROBBY", "JONI", "BARI"])
                sim_bundle = st.text_input("Paket Seri Dibeli Bersamaan (pisahkan koma)", value="DIEGO, GRANIT")
                sim_qty = st.number_input("Kuantitas per Item (DUS)", min_value=5, max_value=500, value=75)
                sim_target = st.radio(
                    "Jalur Eksekusi Pipeline",
                    ["n8n Webhook (:5678) -> FastAPI -> Supabase", "Langsung FastAPI (:8001/api/simulate-transactions)"],
                    horizontal=True,
                )
                submitted = st.form_submit_button("Kirim Transaksi Simulasi & Mining Ulang")

            if submitted:
                seris = [s.strip().upper() for s in sim_bundle.split(",") if s.strip()]
                payload = {
                    "target_toko": sim_toko,
                    "target_pof": sim_pof,
                    "bundle_seris": seris,
                    "qty_per_item": int(sim_qty),
                    "repeat_baskets": 1,
                    "auto_remine": True,
                }
                target_url = N8N_WEBHOOK_URL if sim_target.startswith("n8n") else f"{FASTAPI_BASE}/api/simulate-transactions"
                try:
                    with httpx.Client(timeout=20.0) as client:
                        r = client.post(target_url, json=payload)
                        r.raise_for_status()
                    st.success(f"Pipeline berhasil dijalankan melalui `{target_url}`! Response:")
                    st.json(r.json())
                    st.cache_data.clear()
                except Exception as exc:
                    st.error(f"Gagal memanggil `{target_url}`: {exc}. Pastikan FastAPI (dan n8n jika dipilih) sudah dijalankan.")

        with col_sim2:
            st.markdown("**Kontrol Kebersihan Data Demo**")
            st.caption("Menghapus hanya baris simulasi (`source_batch != historical_xlsx`) dan mengembalikan 784 rules acuan asli.")
            if st.button("Reset Demo Data (1-Klik)", type="secondary", width="stretch"):
                try:
                    with httpx.Client(timeout=30.0) as client:
                        res = client.post(f"{FASTAPI_BASE}/api/reset-demo")
                        res.raise_for_status()
                    st.success("Data demo berhasil di-reset bersih ke 15.986 transaksi asli!")
                    st.json(res.json())
                    st.cache_data.clear()
                except Exception as exc:
                    st.error(f"Reset gagal (pastikan FastAPI :8001 aktif): {exc}")

        if runs_live:
            st.markdown("#### Log Eksekusi Live dari Supabase (`keramine_mining_runs`)")
            st.dataframe(pd.DataFrame(runs_live), width="stretch", hide_index=True)


if __name__ == "__main__":
    main()

