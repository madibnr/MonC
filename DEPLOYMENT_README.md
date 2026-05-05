# MONC - Deployment ke aaPanel

Dokumentasi lengkap untuk deployment aplikasi MONC ke server production menggunakan aaPanel.

## 📚 Dokumentasi

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Panduan lengkap step-by-step deployment
- **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - Checklist singkat untuk quick reference
- **[deploy.sh](deploy.sh)** - Script otomatis untuk deployment/update

## 🚀 Quick Start

### 1. Persiapan Lokal
```bash
# Backup database
mysqldump -u root -p monc > monc_backup.sql

# Commit semua perubahan
git add .
git commit -m "Prepare for deployment"
git push origin main
```

### 2. Setup Server (Sekali Saja)
```bash
# Install aaPanel
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh
sudo bash install.sh aapanel

# Install: Nginx, PHP 8.2, MySQL 8.0, Redis, Supervisor, PM2
# Via aaPanel → App Store
```

### 3. Deploy Project
```bash
# Clone project
cd /www/wwwroot/monc.yourdomain.com
git clone https://github.com/your-repo/monc.git .

# Setup environment
cp .env.production.example .env
nano .env  # Edit konfigurasi

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup Laravel
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache

# Setup go2rtc
cd bin
wget https://github.com/AlexxIT/go2rtc/releases/download/v1.9.14/go2rtc_linux_amd64
mv go2rtc_linux_amd64 go2rtc
chmod +x go2rtc
pm2 start go2rtc --name monc-go2rtc -- -config go2rtc.yaml
pm2 save

# Set permissions
chown -R www:www /www/wwwroot/monc.yourdomain.com
chmod -R 775 storage bootstrap/cache
```

### 4. Konfigurasi Services
- **Queue Worker:** Setup via aaPanel → Supervisor (lihat `supervisor.conf.example`)
- **Scheduler:** Setup cron job via aaPanel → Cron
- **Nginx:** Update config (lihat `nginx.conf.example`)
- **SSL:** Setup via aaPanel → SSL → Let's Encrypt

### 5. Verifikasi
```bash
# Test website
curl -I https://monc.yourdomain.com

# Test go2rtc
curl http://127.0.0.1:1984/api

# Check services
supervisorctl status monc-queue-worker
pm2 status monc-go2rtc
```

## 🔄 Update/Redeploy

Gunakan script otomatis:
```bash
cd /www/wwwroot/monc.yourdomain.com
bash deploy.sh
```

Atau manual:
```bash
php artisan down
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
supervisorctl restart monc-queue-worker
pm2 restart monc-go2rtc
php artisan up
```

## 📋 Requirements

### Server
- Ubuntu 20.04/22.04 atau CentOS 7/8
- RAM: 4GB minimum (8GB+ recommended)
- Storage: 50GB SSD minimum
- CPU: 2 cores minimum (4+ recommended)

### Software
- aaPanel (latest version)
- PHP 8.2 atau 8.3
- MySQL 8.0 atau MariaDB 10.6+
- Nginx atau Apache
- Redis
- Supervisor
- PM2

### Ports
- 80 (HTTP)
- 443 (HTTPS)
- 1984 (go2rtc API)
- 8555 (WebRTC)

## 🔧 Configuration Files

- `.env.production.example` - Production environment template
- `nginx.conf.example` - Nginx configuration template
- `supervisor.conf.example` - Supervisor configuration template
- `deploy.sh` - Deployment automation script

## 🐛 Troubleshooting

### 500 Internal Server Error
```bash
chown -R www:www /www/wwwroot/monc.yourdomain.com
chmod -R 775 storage bootstrap/cache
php artisan cache:clear
php artisan config:clear
```

### Queue Not Processing
```bash
supervisorctl restart monc-queue-worker
tail -f /tmp/monc-queue-worker.log
```

### go2rtc Not Starting
```bash
pm2 restart monc-go2rtc
pm2 logs monc-go2rtc
```

### Stream Tidak Muncul
1. Check go2rtc: `curl http://127.0.0.1:1984/api`
2. Check firewall: Port 1984 dan 8555 terbuka
3. Check browser console untuk error
4. Verify NVR accessible dari server

## 📞 Support

Jika mengalami masalah:
1. Check logs:
   - Laravel: `storage/logs/laravel.log`
   - Nginx: `/www/wwwlogs/monc.yourdomain.com.error.log`
   - go2rtc: `storage/logs/go2rtc.log`
   - Supervisor: `/tmp/monc-queue-worker.log`

2. Baca dokumentasi lengkap di [DEPLOYMENT.md](DEPLOYMENT.md)

3. Check checklist di [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)

## 🔒 Security

- Selalu gunakan HTTPS (SSL)
- Set `APP_DEBUG=false` di production
- Gunakan strong password untuk database
- Enable firewall
- Regular security updates
- Setup backup otomatis

## 📝 Notes

- Backup database dan files sebelum update
- Test di staging environment jika memungkinkan
- Monitor logs setelah deployment
- Keep credentials secure (jangan commit .env ke git)

---

**Last Updated:** 2026-04-28
