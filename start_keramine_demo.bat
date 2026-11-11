@echo off
title KeraMine B2B Demo Launcher - UAS ADAMIN
cd /d "%~dp0"

echo ==============================================================
echo   KeraMine B2B - Multi-Level SOTA Association Rule Mining
echo   1-Click Full Stack Demo Launcher (UAS Data Mining)
echo ==============================================================
echo.

echo [1/5] Menjalankan 9Router AI Gateway (:20128)...
start "KeraMine - 9Router (:20128)" cmd /k "9router --no-browser --skip-update -l"

echo [2/5] Menjalankan FastAPI SOTA Rule Engine (:8001)...
start "KeraMine - FastAPI (:8001)" cmd /k "cd /d "%~dp0api-engine" && py -m uvicorn main:app --host 127.0.0.1 --port 8001"

echo [3/5] Menjalankan Laravel Portal B2B (:8000)...
start "KeraMine - Laravel B2B (:8000)" cmd /k "cd /d "%~dp0keramine-laravel" && php artisan serve --host=127.0.0.1 --port=8000"

echo [4/5] Menjalankan Streamlit Analysis Lab (:8501)...
start "KeraMine - Streamlit Lab (:8501)" cmd /k "cd /d "%~dp0keramine-lab" && py -m streamlit run app.py --server.port 8501"

echo [5/5] Menjalankan n8n Real-Time Automation (:5678)...
start "KeraMine - n8n (:5678)" cmd /k "npx --yes n8n start"

timeout /t 5 /nobreak >nul
start "" "http://127.0.0.1:8000"
start "" "http://127.0.0.1:8501"

echo.
echo Semua layanan KeraMine B2B telah diluncurkan:
echo   - Laravel Portal B2B   : http://127.0.0.1:8000
echo   - Streamlit Lab        : http://127.0.0.1:8501
echo   - FastAPI Rule Engine  : http://127.0.0.1:8001/docs
echo   - n8n Workflow Studio  : http://127.0.0.1:5678
echo   - 9Router Gateway      : http://127.0.0.1:20128
echo.
pause

