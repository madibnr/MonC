# Deployment Guide - MONC ke aaPanel

## Prerequisites

### 1. Server Requirements
- **OS:** Ubuntu 20.04/22.04 LTS atau CentOS 7/8
- **RAM:** Minimum 4GB (Recommended 8GB+)
- **Storage:** Minimum 50GB SSD
- **CPU:** Minimum 2 cores (Recommended 4+ cores)
- **Network:** Port 80, 443, 1984 (go2rtc), 8555 (WebRTC) harus terbuka

### 2. aaPanel Requirements
- aaPanel versi terbaru
- PHP 8.2 atau 8.3
- MySQL 8.0 atau MariaDB 10.6+
- Nginx atau Apache
- Supervisor (untuk queue worker)
- Redis (optional, untuk cache dan session)

---

## Langkah 1: Persiapan Server

### 1.1 Install aaPanel
```bash
# Ubuntu/Debian
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && sudo bash install.sh aapanel

# CentOS
wget -O install.sh http://www.aapanel.com/script/install_6.0_en.sh && sudo bash install.sh aapanel
```

### 1.2 Login ke aaPanel
- Akses: `http://YOUR_SERVER_IP:7800`
- Login dengan kredensial yang ditampilkan setelah instalasi
- **PENTING:** Ubah password default segera!

### 1.3 Install Software Stack via aaPanel
1. Buka **App Store**
2. Install:
   - **Nginx** (atau Apache)
   - **PHP 8.2** atau **8.3**
   - **MySQL 8.0** (atau MariaDB 10.6+)
   - **Redis** (optional tapi recommended)
   - **Supervisor**
   - **PM2** (untuk go2rtc process management)

### 1.4 Install PHP Extensions
Di aaPanel → **App Store** → **PHP 8.2** → **Settings** → **Install Extensions**:

**Required:**
- `opcache`
- `redis` (jika pakai Redis)
- `mysqli`
- `pdo_mysql`
- `mbstring`
- `xml`
- `curl`
- `zip`
- `gd`
- `bcmath`
- `fileinfo`
- `exif`

**Optional (untuk performa):**
- `imagick`
- `intl`

### 1.5 Konfigurasi PHP
Di aaPanel → **App Store** → **PHP 8.2** → **Settings** → **Configuration File**:

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

---

## Langkah 2: Persiapan Database

### 2.1 Buat Database
Di aaPanel → **Database** → **Add Database**:
- Database Name: `monc_production`
- Username: `monc_user`
- Password: (generate strong password)
- Access: `localhost`

### 2.2 Export Database dari Lokal
Di komputer lokal (Laragon):
```bash
cd C:\laragon\www\monc
php artisan db:export monc_backup.sql
```

Atau via phpMyAdmin/MySQL:
```bash
mysqldump -u root -p monc > monc_backup.sql
```

### 2.3 Import ke Server
Upload `monc_backup.sql` ke server, lalu:
```bash
mysql -u monc_user -p monc_production < monc_backup.sql
```

Atau via aaPanel → **Database** → **phpMyAdmin** → Import

---

## Langkah 3: Upload Project

### 3.1 Buat Website di aaPanel
Di aaPanel → **Website** → **Add Site**:
- Domain: `monc.yourdomain.com` (atau IP server)
- Root Directory: `/www/wwwroot/monc.yourdomain.com`
- PHP Version: `PHP-82` atau `PHP-83`
- Database: Pilih database yang sudah dibuat

### 3.2 Upload Files
**Opsi A: Via FTP/SFTP (Recommended)**
1. Install FileZilla atau WinSCP
2. Connect ke server (SFTP port 22)
3. Upload semua file dari `C:\laragon\www\monc` ke `/www/wwwroot/monc.yourdomain.com`

**Opsi B: Via Git (Lebih Baik)**
```bash
# Di server
cd /www/wwwroot/monc.yourdomain.com
git clone https://github.com/your-repo/monc.git .
```

**Opsi C: Via aaPanel File Manager**
1. Zip project di lokal
2. Upload via aaPanel → **Files**
3. Extract di server

### 3.3 Set Permissions
```bash
cd /www/wwwroot/monc.yourdomain.com
chown -R www:www .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

---

## Langkah 4: Konfigurasi Laravel

### 4.1 Install Composer Dependencies
```bash
cd /www/wwwroot/monc.yourdomain.com
composer install --optimize-autoloader --no-dev
```

### 4.2 Setup Environment File
```bash
cp .env.example .env
nano .env
```

Edit `.env`:
```env
APP_NAME="MONC"
APP_ENV=production
APP_KEY=  # akan di-generate
APP_DEBUG=false
APP_URL=https://monc.yourdomain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monc_production
DB_USERNAME=monc_user
DB_PASSWORD=your_strong_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Go2RTC Configuration
GO2RTC_API_URL=http://127.0.0.1:1984
GO2RTC_BINARY_PATH=/www/wwwroot/monc.yourdomain.com/bin/go2rtc
GO2RTC_CONFIG_PATH=/www/wwwroot/monc.yourdomain.com/bin/go2rtc.yaml
```

### 4.3 Generate Application Key
```bash
php artisan key:generate
```

### 4.4 Run Migrations (jika ada perubahan)
```bash
php artisan migrate --force
```

### 4.5 Optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 4.6 Link Storage
```bash
php artisan storage:link
```

---

## Langkah 5: Setup go2rtc

### 5.1 Download go2rtc Binary untuk Linux
```bash
cd /www/wwwroot/monc.yourdomain.com/bin
wget https://github.com/AlexxIT/go2rtc/releases/download/v1.9.14/go2rtc_linux_amd64
mv go2rtc_linux_amd64 go2rtc
chmod +x go2rtc
```

### 5.2 Verifikasi go2rtc.yaml
```bash
nano /www/wwwroot/monc.yourdomain.com/bin/go2rtc.yaml
```

Pastikan konfigurasi sesuai:
```yaml
api:
  listen: ":1984"

rtsp:
  listen: ":8554"

webrtc:
  listen: ":8555"
  candidates:
    - YOUR_SERVER_PUBLIC_IP:8555
    - stun:stun.l.google.com:19302

streams: {}
```

### 5.3 Setup go2rtc sebagai Service dengan PM2
```bash
# Install PM2 jika belum
npm install -g pm2

# Start go2rtc
cd /www/wwwroot/monc.yourdomain.com/bin
pm2 start go2rtc --name monc-go2rtc -- -config go2rtc.yaml

# Save PM2 configuration
pm2 save

# Setup PM2 startup
pm2 startup
# Jalankan command yang ditampilkan
```

### 5.4 Verifikasi go2rtc Running
```bash
curl http://127.0.0.1:1984/api
# Harus return JSON response
```

---

## Langkah 6: Setup Queue Worker

### 6.1 Buat Supervisor Config
Di aaPanel → **App Store** → **Supervisor** → **Add**:

**Name:** `monc-queue-worker`

**Run Directory:** `/www/wwwroot/monc.yourdomain.com`

**Start Command:**
```bash
/usr/bin/php /www/wwwroot/monc.yourdomain.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=120
```

**Process Count:** `1` (atau lebih jika traffic tinggi)

**Auto Start:** `Yes`

**Auto Restart:** `Yes`

**User:** `www`

Klik **Confirm** → **Start**

### 6.2 Setup Laravel Scheduler
Tambahkan cron job di aaPanel → **Cron**:

**Type:** Shell Script

**Name:** `monc-scheduler`

**Period:** Every minute (`* * * * *`)

**Script:**
```bash
cd /www/wwwroot/monc.yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
```

---

## Langkah 7: Konfigurasi Web Server

### 7.1 Nginx Configuration
Di aaPanel → **Website** → **monc.yourdomain.com** → **Settings** → **Configuration File**:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name monc.yourdomain.com;
    
    root /www/wwwroot/monc.yourdomain.com/public;
    index index.php index.html;

    # SSL Configuration (jika sudah setup SSL)
    ssl_certificate /www/server/panel/vhost/cert/monc.yourdomain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/monc.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Client Max Body Size (untuk upload video)
    client_max_body_size 100M;

    # Timeout settings
    proxy_connect_timeout 600;
    proxy_send_timeout 600;
    proxy_read_timeout 600;
    send_timeout 600;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # go2rtc reverse proxy
    location /go2rtc/ {
        proxy_pass http://127.0.0.1:1984/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /www/wwwlogs/monc.yourdomain.com.log;
    error_log /www/wwwlogs/monc.yourdomain.com.error.log;
}
```

### 7.2 Restart Nginx
```bash
nginx -t  # Test configuration
systemctl restart nginx
```

---

## Langkah 8: Setup SSL Certificate (Recommended)

### 8.1 Via aaPanel (Let's Encrypt)
Di aaPanel → **Website** → **monc.yourdomain.com** → **SSL** → **Let's Encrypt**:
1. Centang domain dan www subdomain
2. Klik **Apply**
3. Enable **Force HTTPS**

### 8.2 Update .env
```env
APP_URL=https://monc.yourdomain.com
```

### 8.3 Clear Cache
```bash
php artisan config:cache
```

---

## Langkah 9: Firewall Configuration

### 9.1 Buka Port yang Diperlukan
Di aaPanel → **Security**:

| Port | Protocol | Description |
|------|----------|-------------|
| 80 | TCP | HTTP |
| 443 | TCP | HTTPS |
| 1984 | TCP | go2rtc API |
| 8555 | TCP/UDP | WebRTC |
| 554 | TCP | RTSP (jika NVR di luar network) |

### 9.2 Konfigurasi Firewall Server (iptables/ufw)
```bash
# Ubuntu (ufw)
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 1984/tcp
ufw allow 8555/tcp
ufw allow 8555/udp
ufw reload

# CentOS (firewalld)
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=443/tcp
firewall-cmd --permanent --add-port=1984/tcp
firewall-cmd --permanent --add-port=8555/tcp
firewall-cmd --permanent --add-port=8555/udp
firewall-cmd --reload
```

---

## Langkah 10: Testing & Verification

### 10.1 Test Website
```bash
curl -I https://monc.yourdomain.com
# Harus return 200 OK
```

### 10.2 Test go2rtc
```bash
curl http://127.0.0.1:1984/api
# Harus return JSON
```

### 10.3 Test Queue Worker
```bash
php artisan queue:work --once
# Harus berjalan tanpa error
```

### 10.4 Test Scheduler
```bash
php artisan schedule:run
# Harus menjalankan scheduled tasks
```

### 10.5 Check Logs
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/go2rtc.log
tail -f /www/wwwlogs/monc.yourdomain.com.error.log
```

### 10.6 Test Live Monitoring
1. Login ke aplikasi
2. Buka halaman Live Monitoring
3. Coba play 1 kamera
4. Coba double-click untuk focus mode
5. Verifikasi stream berjalan lancar

---

## Langkah 11: Optimasi Production

### 11.1 Enable OPcache
Di aaPanel → **PHP 8.2** → **Settings** → **Configuration File**:
```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 11.2 Setup Redis Cache
```bash
php artisan cache:clear
php artisan config:cache
```

### 11.3 Setup Log Rotation
```bash
nano /etc/logrotate.d/monc
```

```
/www/wwwroot/monc.yourdomain.com/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www www
    sharedscripts
}
```

### 11.4 Setup Backup
Di aaPanel → **Cron** → **Add**:

**Database Backup (Daily):**
```bash
mysqldump -u monc_user -p'password' monc_production | gzip > /www/backup/monc_db_$(date +\%Y\%m\%d).sql.gz
```

**Files Backup (Weekly):**
```bash
tar -czf /www/backup/monc_files_$(date +\%Y\%m\%d).tar.gz /www/wwwroot/monc.yourdomain.com/storage
```

---

## Langkah 12: Monitoring & Maintenance

### 12.1 Setup Monitoring
Di aaPanel → **Monitor**:
- Enable CPU, RAM, Disk monitoring
- Set alert thresholds

### 12.2 Regular Maintenance Tasks

**Daily:**
- Check error logs
- Monitor disk space
- Verify queue worker running

**Weekly:**
- Review performance metrics
- Check database size
- Clean old logs

**Monthly:**
- Update dependencies: `composer update`
- Update aaPanel: aaPanel → **Settings** → **Update**
- Review security patches

### 12.3 Performance Monitoring
```bash
# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Monitor go2rtc
curl http://127.0.0.1:1984/api/streams
```

---

## Troubleshooting

### Issue: 500 Internal Server Error
**Solution:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check permissions
chown -R www:www /www/wwwroot/monc.yourdomain.com
chmod -R 775 storage bootstrap/cache

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Queue Not Processing
**Solution:**
```bash
# Restart supervisor
supervisorctl restart monc-queue-worker

# Check supervisor logs
tail -f /tmp/monc-queue-worker.log
```

### Issue: go2rtc Not Starting
**Solution:**
```bash
# Check if port 1984 is in use
netstat -tulpn | grep 1984

# Restart go2rtc
pm2 restart monc-go2rtc

# Check logs
pm2 logs monc-go2rtc
```

### Issue: Stream Tidak Muncul
**Solution:**
1. Verifikasi go2rtc running: `curl http://127.0.0.1:1984/api`
2. Check firewall: Port 1984 dan 8555 terbuka
3. Check browser console untuk error
4. Verifikasi NVR accessible dari server

### Issue: High CPU Usage
**Solution:**
1. Limit concurrent streams di frontend
2. Gunakan sub stream untuk monitoring
3. Increase server resources
4. Enable Redis cache

---

## Security Checklist

- [ ] Change default aaPanel password
- [ ] Enable SSL/HTTPS
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong database password
- [ ] Enable firewall
- [ ] Disable directory listing
- [ ] Setup fail2ban (optional)
- [ ] Regular security updates
- [ ] Backup database dan files
- [ ] Monitor access logs

---

## Post-Deployment Checklist

- [ ] Website accessible via domain
- [ ] SSL certificate installed dan valid
- [ ] Database connected
- [ ] go2rtc running dan accessible
- [ ] Queue worker running
- [ ] Scheduler running (cron job)
- [ ] Live monitoring berfungsi
- [ ] Playback berfungsi
- [ ] AI analytics berfungsi (jika enabled)
- [ ] User dapat login
- [ ] Permissions correct
- [ ] Logs writable
- [ ] Backup configured
- [ ] Monitoring setup

---

## Support & Resources

- **aaPanel Documentation:** https://doc.aapanel.com/
- **Laravel Deployment:** https://laravel.com/docs/deployment
- **go2rtc Documentation:** https://github.com/AlexxIT/go2rtc
- **Nginx Configuration:** https://nginx.org/en/docs/

---

## Notes

- Selalu backup sebelum update
- Test di staging environment dulu jika memungkinkan
- Monitor logs setelah deployment
- Dokumentasikan setiap perubahan konfigurasi
- Keep credentials secure (gunakan .env, jangan commit ke git)

---

**Last Updated:** 2026-04-28
**Version:** 1.0
