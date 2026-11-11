<?php

return [
    'fastapi_url' => rtrim((string) env('KERAMINE_FASTAPI_URL', 'http://127.0.0.1:8001'), '/'),
    'streamlit_url' => rtrim((string) env('KERAMINE_STREAMLIT_URL', 'http://127.0.0.1:8501'), '/'),
    'n8n_url' => rtrim((string) env('KERAMINE_N8N_URL', 'http://127.0.0.1:5678'), '/'),
    'n8n_webhook_url' => (string) env('KERAMINE_N8N_WEBHOOK_URL', 'http://127.0.0.1:5678/webhook/keramine-ingest'),
    'supabase_rest_url' => rtrim((string) env('SUPABASE_REST_URL', ''), '/'),
    'supabase_publishable_key' => (string) env('SUPABASE_PUBLISHABLE_KEY', ''),
    'supabase_secret_key' => (string) env('SUPABASE_SECRET_KEY', ''),
    'clean_data_dir' => (string) env('KERAMINE_CLEAN_DATA_DIR', dirname(base_path()) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'clean'),
    'ca_bundle' => (string) env('KERAMINE_CA_BUNDLE', storage_path('app' . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'cacert.pem')),
    'http_timeout' => (int) env('KERAMINE_HTTP_TIMEOUT', 12),
];
