# Sentinel Server Monitor — Development Docs

## Daftar Dokumen

| # | File | Isi |
|---|------|-----|
| 0 | `00-INDEX.md` | Index ini |
| 1 | `01-arsitektur-dan-code-design.md` | Arsitektur, pola, struktur folder, service container, DTO, enum |
| 2 | `02-database-schema.md` | Semua tabel, kolom, relasi, indeks, migration plan |
| 3 | `03-api-routes.md` | Semua route RESTful + Reverb websocket channel |
| 4 | `04-modul-monitoring.md` | Detail 13 modul monitoring: CPU/RAM/Disk s/d Network |
| 5 | `05-alert-dan-self-healing.md` | Alert engine, channel (Telegram/Discord/Email), self-healing rules |
| 6 | `06-keamanan.md` | Whitelist command, RBAC, audit log, rate limit, enkripsi |
| 7 | `07-v1-monitoring-dasar.md` | Sprint plan v1 — monitoring dasar |
| 8 | `08-v1.1-parser-log-502-analyzer.md` | Sprint plan v1.1 — log parser & 502 analyzer |
| 9 | `09-v1.2-self-healing-alert.md` | Sprint plan v1.2 — self healing & alert multi-channel |
| 10 | `10-v1.3-multi-server.md` | Sprint plan v1.3 — multi server support |
| 11 | `11-v2-ml-prediction.md` | Sprint plan v2 — ML failure prediction |
| 12 | `12-finalisasi.md` | Finalisasi: checklist, testing, deployment, handover |

## Cara Pakai

1. Baca `01` sampai `06` untuk memahami desain penuh.
2. Eksekusi per fase sesuai `07` → `11`.
3. Gunakan `12-finalisasi.md` sebagai checklist sebelum rilis.

## Versi & Target

| Versi | Fokus | Estimasi |
|-------|-------|----------|
| v1.0 | Monitoring dasar CPU/RAM/Disk/Nginx/PHP-FPM/PM2/Redis/MySQL/Queue | 4 minggu |
| v1.1 | Parser log & 502 error analyzer | 2 minggu |
| v1.2 | Self-healing & alert multi-channel (Telegram/Discord/Email) | 2 minggu |
| v1.3 | Multi-server support | 2 minggu |
| v2.0 | ML failure prediction | 4 minggu |
