# Panduan Menjalankan Pipeline Auto-Realtime n8n (KeraMine)

1. Jalankan server **FastAPI Rule Engine**:
   ```powershell
   py -m uvicorn main:app --app-dir "D:\TUGAS\SEMESTER 7\ADAMIN\api-engine" --host 127.0.0.1 --port 8001
   ```
2. Jalankan **n8n** lokal (Node.js 22 sudah terpasang):
   ```powershell
   npx n8n
   ```
3. Buka editor n8n di `http://localhost:5678`, pilih **Add Workflow $\rightarrow$ Import from File**, lalu pilih `n8n/keramine_realtime_pipeline.json`.
4. Aktifkan toggle **Active** di kanan atas editor n8n agar Webhook URL aktif di:
   `http://127.0.0.1:5678/webhook/keramine-ingest`
5. Picu langsung dari **Laravel Portal (`:8000`)** atau **Streamlit Tab 5 (`:8501`)** untuk melihat alur eksekusi *real-time* masuk ke Supabase!

