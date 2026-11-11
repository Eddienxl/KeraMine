"""Kamus dwibahasa (ID/EN) & Theme CSS Opsi C (Light) / Opsi B (Dark) untuk KeraMine Lab."""
from __future__ import annotations

import streamlit as st

THEME_TOKENS = {
    "light": {
        "bg": "#F8FAFC",
        "surface": "#FFFFFF",
        "elevated": "#F1F5F9",
        "border": "#E2E8F0",
        "text": "#0F172A",
        "text_muted": "#64748B",
        "primary": "#4F46E5",
        "accent": "#10B981",
        "warning": "#F59E0B",
        "danger": "#EF4444",
        "plotly_template": "plotly_white",
    },
    "dark": {
        "bg": "#0F1117",
        "surface": "#1A1D27",
        "elevated": "#222634",
        "border": "#2A2F3E",
        "text": "#E6E8EE",
        "text_muted": "#94A3B8",
        "primary": "#8B5CF6",
        "accent": "#22D3EE",
        "warning": "#FBBF24",
        "danger": "#F87171",
        "plotly_template": "plotly_dark",
    },
}


def inject_theme_css(mode: str) -> dict[str, str]:
    tok = THEME_TOKENS.get(mode, THEME_TOKENS["light"])
    st.markdown(
        f"""
        <style>
        .stApp {{
            background-color: {tok['bg']};
            color: {tok['text']};
            font-family: 'Inter', system-ui, sans-serif;
        }}
        section[data-testid="stSidebar"] {{
            background-color: {tok['surface']};
            border-right: 1px solid {tok['border']};
        }}
        .km-card {{
            background-color: {tok['surface']};
            border: 1px solid {tok['border']};
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 12px;
        }}
        .km-kpi-label {{
            font-size: 12px;
            color: {tok['text_muted']};
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }}
        .km-kpi-value {{
            font-size: 24px;
            font-weight: 700;
            color: {tok['text']};
            font-variant-numeric: tabular-nums;
            margin-top: 4px;
        }}
        .km-kpi-sub {{
            font-size: 12px;
            color: {tok['accent']};
            margin-top: 2px;
        }}
        .km-badge-sig {{
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: rgba(16, 185, 129, 0.14);
            color: {tok['accent']};
            border: 1px solid {tok['accent']};
        }}
        .km-badge-exp {{
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: rgba(79, 70, 229, 0.14);
            color: {tok['primary']};
            border: 1px solid {tok['primary']};
        }}
        .km-badge-pop {{
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background-color: rgba(245, 158, 11, 0.15);
            color: {tok['warning']};
            border: 1px solid {tok['warning']};
        }}
        </style>
        """,
        unsafe_allow_html=True,
    )
    return tok


TRANSLATIONS = {
    "title": {
        "id": "KeraMine Analysis Lab — SOTA Association Rule Mining & Real-Time Pipeline",
        "en": "KeraMine Analysis Lab — SOTA Association Rule Mining & Real-Time Pipeline",
    },
    "subtitle": {
        "id": "Laboratorium Data Mining B2B Distribusi Keramik: Forensik Basket, Apriori/FP-Growth/FP-Max, Zhang's Metric, Utility-QTY, Uji Fisher-BH & Orkestrasi n8n",
        "en": "B2B Ceramic Distribution Data Mining Lab: Basket Forensics, Apriori/FP-Growth/FP-Max, Zhang's Metric, Utility-QTY, Fisher-BH & n8n Orchestration",
    },
    "tab_eda": {"id": "1. Forensik Data & EDA", "en": "1. Data Forensics & EDA"},
    "tab_explorer": {"id": "2. Rule Explorer & Network Graph", "en": "2. Rule Explorer & Network Graph"},
    "tab_rec": {"id": "3. Rekomendasi & Uji Kontingensi 2x2", "en": "3. Recommender & 2x2 Contingency"},
    "tab_sota": {"id": "4. SOTA Benchmark & Evaluasi", "en": "4. SOTA Benchmark & Evaluation"},
    "tab_live": {"id": "5. Pipeline Live n8n & Supabase", "en": "5. Live n8n & Supabase Pipeline"},
}


def t(key: str, lang: str = "id") -> str:
    return TRANSLATIONS.get(key, {}).get(lang, key)

