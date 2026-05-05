# MonC Services - Quick Start Guide

## Services yang Perlu Berjalan

MonC memerlukan 4 service utama:

1. **Laravel (Web Server)** - Port 80
2. **go2rtc (Streaming)** - Port 1984
3. **AI Microservice (Python)** - Port 8100
4. **Queue Worker (Laravel)** - Background process
5. **Scheduler (Laravel)** - Background process (untuk AI plate recognition)

---

## 1. Start Laravel Web Server

**Via Laragon:**
- Klik "Start All" di Laragon
- Laravel akan berjalan di `http://monc.test`

**Via Artisan (alternatif):**
```bash
php artisan serve
```

---

## 2. Start go2rtc (Streaming Service)

**Windows:**
```bash
cd bin
start go2rtc.exe -config go2rtc.yaml
```

**Atau via PM2 (recommended untuk production):**
```bash
pm2 start go2rtc.exe --name monc-go2rtc -- -config go2rtc.yaml
pm2 save
```

**Cek status:**
```bash
curl http://127.0.0.1:1984/api
```

---

## 3. Start AI Microservice (Python)

**Pertama kali - Install dependencies:**
```bash
cd ai-service
pip install -r requirements.txt
```

**Start service:**
```bash
# Windows - Double click:
start-ai-service.bat

# Atau manual:
python main.py
```

**Catatan:**
- First run akan download EasyOCR model (~1 GB) - tunggu 1-2 menit
- Service akan berjalan di `http://127.0.0.1:8100`

**Cek status:**
```bash
curl http://127.0.0.1:8100/api/health
```

Response:
```json
{
  "status": "ok",
  "service": "monc-ai",
  "version": "1.0.0",
  "model_loaded": true
}
```

---

## 4. Start Queue Worker

Queue worker memproses background jobs (plate recognition, alerts, dll).

**Windows:**
```bash
# Buka terminal baru
php artisan queue:work --timeout=120
```

**Via PM2 (recommended):**
```bash
pm2 start "php artisan queue:work --timeout=120" --name monc-queue
pm2 save
```

**Catatan:**
- Jangan tutup terminal/window
- Restart setelah update code: `php artisan queue:restart`

---

## 5. Start Scheduler

Scheduler menjalankan AI plate recognition setiap 5 detik.

**Windows:**
```bash
# Buka terminal baru
php artisan schedule:work
```

**Via PM2 (recommended):**
```bash
pm2 start "php artisan schedule:work" --name monc-scheduler
pm2 save
```

---

## Quick Start - All Services

**Windows (Manual):**

1. Start Laragon → "Start All"
2. Double-click `ai-service/start-ai-service.bat`
3. Buka terminal 1: `php artisan queue:work --timeout=120`
4. Buka terminal 2: `php artisan schedule:work`
5. go2rtc akan auto-start saat pertama kali akses `/live`

**Windows (PM2 - Recommended):**

```bash
# Install PM2 (sekali saja)
npm install -g pm2

# Start all services
pm2 start ecosystem.config.js
pm2 save
pm2 startup

# Cek status
pm2 status
```

---

## Monitoring Services

**Cek semua service:**
```bash
# Laravel
curl http://monc.test

# go2rtc
curl http://127.0.0.1:1984/api

# AI Service
curl http://127.0.0.1:8100/api/health

# Queue Worker
php artisan queue:failed  # Lihat failed jobs

# Scheduler
tail -f storage/logs/laravel.log | grep "ProcessPlateRecognitionJob"
```

**PM2 Monitoring:**
```bash
pm2 status          # Status semua service
pm2 logs            # Lihat logs real-time
pm2 logs monc-ai    # Logs specific service
pm2 monit           # Dashboard monitoring
```

---

## Troubleshooting

### AI Service tidak start

**Error: "Module not found"**
```bash
cd ai-service
pip install -r requirements.txt
```

**Error: "Port 8100 already in use"**
```bash
# Windows - kill process
netstat -ano | findstr :8100
taskkill /PID <PID> /F
```

### Queue Worker tidak memproses jobs

```bash
# Restart worker
php artisan queue:restart

# Cek failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Plate Recognition tidak jalan

1. Cek AI service: `curl http://127.0.0.1:8100/api/health`
2. Cek queue worker: `php artisan queue:work` (harus running)
3. Cek scheduler: `php artisan schedule:work` (harus running)
4. Cek camera settings: AI harus enabled di `/cameras/{id}/ai-settings`
5. Cek logs: `tail -f storage/logs/laravel.log`

### go2rtc tidak bisa stream

```bash
# Restart go2rtc
taskkill /IM go2rtc.exe /F
cd bin
start go2rtc.exe -config go2rtc.yaml

# Atau via PM2
pm2 restart monc-go2rtc
```

---

## Stop All Services

**Manual:**
- Close semua terminal windows
- Stop Laragon
- `taskkill /IM go2rtc.exe /F`
- `taskkill /IM python.exe /F` (hati-hati, akan kill semua Python)

**PM2:**
```bash
pm2 stop all
pm2 delete all  # Untuk remove dari PM2
```

---

## Auto-start on Boot (Production)

**Windows - Task Scheduler:**
1. Buat batch file `start-monc.bat`:
```batch
@echo off
cd C:\laragon\www\monc
start /min php artisan queue:work --timeout=120
start /min php artisan schedule:work
cd ai-service
start /min python main.py
```

2. Task Scheduler → Create Task → Trigger: At startup → Action: Run `start-monc.bat`

**PM2 (Recommended):**
```bash
pm2 startup
pm2 save
```

PM2 akan auto-start semua service saat boot.

---

## Service Status Dashboard

Buka browser: `http://monc.test/system/status` (jika ada halaman status)

Atau manual check:
```bash
echo "=== MonC Services Status ==="
curl -s http://monc.test > nul && echo "[OK] Laravel" || echo "[FAIL] Laravel"
curl -s http://127.0.0.1:1984/api > nul && echo "[OK] go2rtc" || echo "[FAIL] go2rtc"
curl -s http://127.0.0.1:8100/api/health > nul && echo "[OK] AI Service" || echo "[FAIL] AI Service"
```

---

## Logs Location

- **Laravel:** `storage/logs/laravel.log`
- **go2rtc:** `storage/logs/go2rtc.log`
- **AI Service:** Console output (atau redirect ke file)
- **Queue Worker:** Console output
- **Scheduler:** `storage/logs/laravel.log`

**Tail logs:**
```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 50

# Git Bash
tail -f storage/logs/laravel.log
```
