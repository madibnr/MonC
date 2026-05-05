# MONC Deployment Checklist - Quick Reference

## Pre-Deployment (Di Lokal)

- [ ] Test semua fitur berjalan normal
- [ ] Backup database: `mysqldump -u root -p monc > monc_backup.sql`
- [ ] Commit semua perubahan ke Git
- [ ] Update `.env.example` dengan konfigurasi terbaru
- [ ] Dokumentasikan perubahan di CHANGELOG

## Server Setup (Sekali Saja)

- [ ] Install aaPanel
- [ ] Install: Nginx, PHP 8.2, MySQL 8.0, Redis, Supervisor, PM2
- [ ] Install PHP extensions: opcache, redis, mysqli, pdo_mysql, mbstring, xml, curl, zip, gd, bcmath, fileinfo, exif
- [ ] Konfigurasi PHP: upload_max_filesize=100M, memory_limit=512M
- [ ] Buat database: `monc_production`
- [ ] Buat website di aaPanel
- [ ] Setup SSL certificate (Let's Encrypt)
- [ ] Buka firewall: Port 80, 443, 1984, 8555

## Deployment Steps

### 1. Upload Project
```bash
# Via Git (Recommended)
cd /www/wwwroot/monc.yourdomain.com
git clone https://github.com/your-repo/monc.git .

# Atau upload via FTP/SFTP
```

### 2. Setup Environment
```bash
cp .env.example .env
nano .env  # Edit konfigurasi
php artisan key:generate
```

### 3. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 4. Import Database
```bash
mysql -u monc_user -p monc_production < monc_backup.sql
```

### 5. Setup Permissions
```bash
chown -R www:www .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### 6. Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 7. Setup go2rtc
```bash
cd bin
wget https://github.com/AlexxIT/go2rtc/releases/download/v1.9.14/go2rtc_linux_amd64
mv go2rtc_linux_amd64 go2rtc
chmod +x go2rtc

# Start dengan PM2
pm2 start go2rtc --name monc-go2rtc -- -config go2rtc.yaml
pm2 save
pm2 startup
```

### 8. Setup Queue Worker (Supervisor)
Di aaPanel → Supervisor → Add:
- Name: `monc-queue-worker`
- Command: `/usr/bin/php /www/wwwroot/monc.yourdomain.com/artisan queue:work --sleep=3 --tries=3`
- User: `www`
- Auto Start: Yes

### 9. Setup Scheduler (Cron)
Di aaPanel → Cron → Add:
- Period: Every minute (`* * * * *`)
- Script: `cd /www/wwwroot/monc.yourdomain.com && php artisan schedule:run`

### 10. Configure Nginx
Update nginx config dengan reverse proxy untuk go2rtc (lihat DEPLOYMENT.md)

## Post-Deployment Verification

- [ ] Website accessible: `curl -I https://monc.yourdomain.com`
- [ ] go2rtc running: `curl http://127.0.0.1:1984/api`
- [ ] Queue worker running: `supervisorctl status monc-queue-worker`
- [ ] Scheduler working: `php artisan schedule:run`
- [ ] Login ke aplikasi
- [ ] Test live monitoring
- [ ] Test playback
- [ ] Check logs: `tail -f storage/logs/laravel.log`

## Common Issues & Quick Fixes

### 500 Error
```bash
chown -R www:www /www/wwwroot/monc.yourdomain.com
chmod -R 775 storage bootstrap/cache
php artisan cache:clear
php artisan config:clear
```

### Queue Not Working
```bash
supervisorctl restart monc-queue-worker
tail -f /tmp/monc-queue-worker.log
```

### go2rtc Not Starting
```bash
pm2 restart monc-go2rtc
pm2 logs monc-go2rtc
netstat -tulpn | grep 1984
```

### Stream Tidak Muncul
1. Check go2rtc: `curl http://127.0.0.1:1984/api`
2. Check firewall: Port 1984 dan 8555 terbuka
3. Check browser console
4. Verify NVR accessible

## Update/Redeploy

```bash
cd /www/wwwroot/monc.yourdomain.com
php artisan down
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart monc-queue-worker
pm2 restart monc-go2rtc
php artisan up
```

Atau gunakan script: `bash deploy.sh`

## Important URLs

- **Website:** https://monc.yourdomain.com
- **aaPanel:** http://YOUR_SERVER_IP:7800
- **go2rtc API:** http://127.0.0.1:1984/api
- **phpMyAdmin:** Via aaPanel → Database

## Important Paths

- **Project:** `/www/wwwroot/monc.yourdomain.com`
- **Logs:** `/www/wwwroot/monc.yourdomain.com/storage/logs`
- **Nginx Logs:** `/www/wwwlogs/monc.yourdomain.com.log`
- **go2rtc Binary:** `/www/wwwroot/monc.yourdomain.com/bin/go2rtc`

## Support

Jika ada masalah, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Nginx error log: `/www/wwwlogs/monc.yourdomain.com.error.log`
3. go2rtc log: `storage/logs/go2rtc.log`
4. Supervisor log: `/tmp/monc-queue-worker.log`
