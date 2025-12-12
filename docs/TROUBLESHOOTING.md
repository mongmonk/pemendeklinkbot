# Troubleshooting Guide & FAQ - Aqwam URL Shortener

## 📖 Overview

Guide lengkap untuk troubleshooting masalah umum yang mungkin terjadi pada sistem Aqwam URL Shortener, beserta FAQ (Frequently Asked Questions).

## 🔧 Common Issues & Solutions

### 1. Telegram Bot Issues

#### Bot Tidak Merespons
**Symptoms:**
- Bot tidak merespons pesan
- Pesan "Bot sedang sibuk"
- Timeout saat mengirim command

**Troubleshooting Steps:**
```bash
# 1. Check bot status
curl -X GET "https://api.telegram.org/bot8552109110:AAHJHMIBm_ai5v0Kti9DqXHTs4kQxqdBKf8/getMe"

# 2. Check webhook status
php artisan telegram:setup-webhook

# 3. Check webhook URL accessibility
curl -I https://aqwam.id/api/telegram/webhook

# 4. Check SSL certificate
openssl s_client -connect aqwam.id:443 -servername aqwam.id

# 5. Check Laravel logs
tail -f /var/www/aqwam/storage/logs/laravel.log | grep -i telegram
```

**Common Causes:**
- Webhook tidak terkonfigurasi dengan benar
- SSL certificate expired atau tidak valid
- Firewall memblokir akses ke webhook
- Bot token salah atau expired
- Rate limit terlampaui

**Solutions:**
```bash
# Reset webhook
php artisan telegram:setup-webhook --url=https://aqwam.id/api/telegram/webhook

# Clear bot cache
php artisan cache:clear

# Restart queue workers
supervisorctl restart aqwam-worker:*

# Check firewall rules
sudo ufw status
sudo iptables -L -n
```

#### Rate Limit Terlalu Ketat
**Symptoms:**
- Pesan "Terlalu banyak permintaan"
- Bot tidak merespons untuk beberapa menit
- Command tidak dieksekusi

**Troubleshooting:**
```bash
# Check rate limit configuration
grep -E "RATE_LIMIT" /var/www/aqwam/.env

# Check current rate limits in Redis
redis-cli keys "*rate_limit*"

# Clear rate limit cache
redis-cli flushdb
```

**Solution:**
```bash
# Update rate limit settings
# Edit .env file
TELEGRAM_RATE_LIMIT_ATTEMPTS=10  # Increase from 5 to 10
TELEGRAM_RATE_LIMIT_MINUTES=2   # Increase from 1 to 2

# Clear and restart
php artisan config:clear
php artisan config:cache
supervisorctl restart aqwam-worker:*
```

#### Custom Alias Tidak Berfungsi
**Symptoms:**
- Pesan "Custom alias tidak valid"
- Pesan "Kode short sudah digunakan"
- Alias tidak tersimpan

**Troubleshooting:**
```bash
# Check alias validation
php artisan tinker --execute="
echo App\Services\UrlShortenerService::isValidCustomCode('test-alias');
"

# Check if alias exists
php artisan tinker --execute="
echo App\Models\Link::where('short_code', 'test-alias')->exists();
"
```

**Solution:**
Pastikan custom alias memenuhi syarat:
- Maksimal 15 karakter
- Hanya huruf, angka, hyphen (-), dan underscore (_)
- Belum digunakan oleh link lain

### 2. Web Interface Issues

#### Redirect Tidak Berfungsi
**Symptoms:**
- 404 Not Found error
- Redirect loop
- Redirect terlalu lambat

**Troubleshooting:**
```bash
# 1. Check if short code exists
php artisan tinker --execute="
\$link = App\Models\Link::where('short_code', 'abc123')->first();
if (\$link) {
    echo 'Link found: ' . \$link->long_url;
} else {
    echo 'Link not found';
}
"

# 2. Check cache status
redis-cli get "short_url:abc123"

# 3. Check Nginx configuration
nginx -t
systemctl status nginx

# 4. Check Nginx logs
tail -f /var/log/nginx/aqwam.id.access.log
tail -f /var/log/nginx/aqwam.id.error.log
```

**Common Causes:**
- Short code tidak ada di database
- Cache tidak sinkron dengan database
- Nginx configuration error
- Database connection issue

**Solutions:**
```bash
# Clear specific cache
php artisan cache:forget "short_url:abc123"

# Clear all cache
php artisan cache:clear

# Warm up specific link
php artisan tinker --execute="
\$link = App\Models\Link::where('short_code', 'abc123')->first();
if (\$link) {
    \Cache::put('short_url:' . \$link->short_code, \$link->long_url, now()->addDays(30));
}
"

# Restart services
systemctl reload nginx
systemctl restart php8.2-fpm
```

#### Preview Page Tidak Muncul
**Symptoms:**
- 404 error saat akses preview
- Data analytics tidak muncul
- JavaScript error di browser

**Troubleshooting:**
```bash
# 1. Check route configuration
php artisan route:list | grep preview

# 2. Check view file exists
ls -la /var/www/aqwam/resources/views/preview.blade.php

# 3. Check permissions
ls -la /var/www/aqwam/storage/framework/cache/

# 4. Test preview endpoint
curl -v https://aqwam.id/preview/abc123
```

**Solutions:**
```bash
# Clear view cache
php artisan view:clear
php artisan view:cache

# Fix permissions
sudo chown -R www-data:www-data /var/www/aqwam/storage
sudo chmod -R 775 /var/www/aqwam/storage/framework

# Recompile assets
npm run build
php artisan asset:publish
```

### 3. Performance Issues

#### Response Time Lambat
**Symptoms:**
- Redirect >500ms
- Preview page loading lambat
- High CPU usage

**Troubleshooting:**
```bash
# 1. Check response time
curl -o /dev/null -s -w "%{time_total}\n" https://aqwam.id/abc123

# 2. Check cache hit ratio
php artisan tinker --execute="
\$hits = \Cache::get('cache_hits', 0);
\$misses = \Cache::get('cache_misses', 0);
echo 'Hit ratio: ' . (\$hits / (\$hits + \$misses)) * 100 . '%';
"

# 3. Check database queries
php artisan tinker --execute="
\DB::enableQueryLog();
\$link = \App\Models\Link::where('short_code', 'abc123')->first();
print_r(\DB::getQueryLog());
"

# 4. Check system resources
top -b -n1 | head -5
free -m
df -h
```

**Optimization Solutions:**
```bash
# Warm up cache
php artisan url:warm-cache --limit=200

# Optimize database
mysql -u root -p -e "OPTIMIZE TABLE links, click_logs, admins;"

# Check slow queries
tail -f /var/log/mysql/slow.log

# Enable OPcache
php -m | grep -i opcache

# Tune PHP-FPM
# Edit /etc/php/8.2/fpm/pool.d/www.conf
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 30
```

#### High Memory Usage
**Symptoms:**
- OOM killer errors
- System swap tinggi
- Services crash

**Troubleshooting:**
```bash
# Check memory usage
free -h
ps aux --sort=-%mem | head -10

# Check PHP memory limit
php -i | grep memory_limit

# Check MySQL memory usage
ps aux | grep mysql | grep -v grep

# Check Redis memory usage
redis-cli info memory
```

**Solutions:**
```bash
# Optimize PHP memory
# Edit php.ini
memory_limit = 256M
max_execution_time = 30

# Optimize MySQL memory
# Edit my.cnf
innodb_buffer_pool_size = 1G
innodb_log_file_size = 128M

# Optimize Redis memory
# Edit redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru

# Restart services
systemctl restart php8.2-fpm mysql redis-server
```

### 4. Database Issues

#### Connection Failed
**Symptoms:**
- "SQLSTATE[HY000] [2002] Connection refused"
- "SQLSTATE[HY000] [1045] Access denied"
- Timeout saat koneksi database

**Troubleshooting:**
```bash
# 1. Test database connection
mysql -u aqwam_user -p aqwam_production -e "SELECT 1;"

# 2. Check MySQL status
systemctl status mysql

# 3. Check MySQL logs
tail -f /var/log/mysql/error.log

# 4. Check network connectivity
telnet localhost 3306
netstat -tlnp | grep 3306
```

**Solutions:**
```bash
# Reset MySQL password
mysql -u root -p -e "
ALTER USER 'aqwam_user'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
"

# Update .env file
DB_PASSWORD=new_password

# Restart MySQL
systemctl restart mysql

# Test Laravel connection
php artisan tinker --execute="
try {
    \DB::connection()->getPdo();
    echo 'Database connection successful';
} catch (\Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage();
}
"
```

#### Slow Queries
**Symptoms:**
- Page loading lambat
- High CPU usage
- Database timeout

**Troubleshooting:**
```bash
# Enable slow query log
mysql -u root -p -e "
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';
"

# Check existing slow queries
mysqldumpslow /var/log/mysql/slow.log | head -10

# Analyze query performance
php artisan tinker --execute="
\DB::enableQueryLog();
\$links = \App\Models\Link::with('clickLogs')->limit(10)->get();
foreach (\DB::getQueryLog() as \$query) {
    echo \$query['time'] . 'ms - ' . \$query['query'] . PHP_EOL;
}
"
```

**Optimization:**
```sql
-- Add missing indexes
ALTER TABLE click_logs ADD INDEX idx_composite (short_code, timestamp);
ALTER TABLE links ADD INDEX idx_active_clicks (disabled, clicks);

-- Optimize existing queries
EXPLAIN SELECT * FROM links WHERE short_code = 'abc123' AND disabled = 0;
EXPLAIN SELECT COUNT(*) FROM click_logs WHERE short_code = 'abc123' AND timestamp > NOW() - INTERVAL 7 DAY;
```

### 5. Cache Issues

#### Redis Connection Failed
**Symptoms:**
- "Connection refused" untuk Redis
- Cache tidak berfungsi
- Performance degradation

**Troubleshooting:**
```bash
# Test Redis connection
redis-cli ping

# Check Redis status
systemctl status redis-server

# Check Redis logs
tail -f /var/log/redis/redis-server.log

# Check Redis configuration
redis-cli config get "*"
```

**Solutions:**
```bash
# Restart Redis
systemctl restart redis-server

# Check Redis memory usage
redis-cli info memory | grep used_memory_human

# Clear Redis cache
redis-cli flushdb

# Update Redis configuration
# Edit /etc/redis/redis.conf
bind 127.0.0.1
port 6379
timeout 0
```

#### Cache Invalidation
**Symptoms:**
- Data tidak up-to-date
- Link yang sudah dihapus masih dapat diakses
- Statistik tidak akurat

**Solutions:**
```bash
# Clear specific cache
php artisan cache:forget "short_url:abc123"

# Clear all cache
php artisan cache:clear

# Rebuild cache
php artisan url:warm-cache --limit=100

# Setup cache invalidation
php artisan tinker --execute="
// Clear cache when link is updated
App\Models\Link::updated(function (\$link) {
    \Cache::forget('short_url:' . \$link->short_code);
});

// Clear cache when link is deleted
App\Models\Link::deleted(function (\$link) {
    \Cache::forget('short_url:' . \$link->short_code);
});
"
```

## 📋 FAQ (Frequently Asked Questions)

### General Questions

**Q: Apakah Aqwam URL Shortener gratis?**
A: Ya, layanan ini sepenuhnya gratis untuk semua pengguna. Tidak ada biaya tersembunyi atau batasan penggunaan.

**Q: Apakah ada batasan jumlah link yang bisa dibuat?**
A: Tidak ada batasan jumlah link. Anda dapat membuat sebanyak mungkin link yang Anda butuhkan.

**Q: Apakah link akan kedaluwarsa?**
A: Tidak, link tidak akan kedaluwarsa secara otomatis. Link akan tetap aktif selama tidak dinonaktifkan secara manual.

**Q: Apakah data saya aman?**
A: Ya, kami mengenkripsi semua data sensitif dan menggunakan HTTPS untuk semua komunikasi. Data analytics disimpan anonim dan tidak dibagikan ke pihak ketiga.

**Q: Bagaimana cara menghapus link yang saya buat?**
A: Saat ini, link dapat dinonaktifkan melalui admin dashboard. Untuk penghapusan permanen, hubungi admin sistem.

### Telegram Bot Questions

**Q: Bot tidak merespons pesan saya?**
A: Beberapa kemungkinan:
1. Anda melebihi rate limit (5 pesan/menit)
2. Format URL tidak valid
3. Bot sedang mengalami gangguan
4. Koneksi internet tidak stabil

**Q: Mengapa custom alias saya ditolak?**
A: Custom alias ditolak jika:
- Lebih dari 15 karakter
- Mengandung karakter selain huruf, angka, hyphen, underscore
- Sudah digunakan oleh pengguna lain
- Mengandung kata yang tidak diizinkan

**Q: Apakah saya bisa mengedit link yang sudah dibuat?**
A: Saat ini, fitur edit hanya tersedia melalui admin dashboard. Bot tidak memiliki fitur edit untuk mencegah kebingungan.

**Q: Bagaimana cara melihat statistik detail?**
A: Gunakan command `/stats [kode_link]` atau akses halaman preview di `https://aqwam.id/preview/[kode_link]`

### Technical Questions

**Q: Mengapa redirect terasa lambat?**
A: Kemungkinan penyebab:
1. Cache miss (first time access)
2. Server sedang high load
3. Jaringan internet lambat
4. DNS resolution delay

Normal response time:
- Cache hit: <50ms
- Cache miss: <200ms
- Database query: <100ms

**Q: Apa yang terjadi jika server down?**
A: Sistem memiliki:
1. Auto-restart untuk critical services
2. Health check monitoring
3. Backup otomatis
4. Alert system untuk admin

**Q: Bagaimana cara report bug atau request fitur?**
A: Anda dapat:
1. Menghubungi @pemendeklinkbot di Telegram
2. Email ke support@aqwam.id
3. Report via GitHub Issues
4. Menghubungi admin dashboard

### Performance Questions

**Q: Berapa kapasitas maksimal sistem?**
A: Sistem dirancang untuk:
- 10,000+ requests/menit
- 100,000+ links aktif
- 1,000,000+ clicks/hari
- 99.9% uptime

**Q: Apakah sistem mendukung high availability?**
A: Ya, dengan setup yang tepat:
1. Load balancer
2. Multiple app servers
3. Database replication
4. Redis cluster
5. CDN integration

**Q: Bagaimana cara optimasi performa?**
A: Beberapa tips:
1. Gunakan custom alias yang mudah diingat
2. Cache frequently accessed links
3. Monitor analytics untuk pola penggunaan
4. Gunakan CDN untuk static assets
5. Optimize database queries

## 🚨 Emergency Procedures

### System Down

#### Immediate Response
1. **Check Health Status**: `curl https://aqwam.id/health-check.php`
2. **Check Services**: `systemctl status nginx php8.2-fpm mysql redis-server`
3. **Check Logs**: `tail -f /var/log/nginx/aqwam.id.error.log`
4. **Restart Services**: Jika perlu
5. **Notify Team**: Kirim alert ke semua tim

#### Recovery Commands
```bash
# Emergency restart all services
systemctl restart nginx php8.2-fpm mysql redis-server
supervisorctl restart aqwam-worker:*

# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Warm up critical caches
php artisan url:warm-cache --limit=50

# Check system status
/usr/local/bin/aqwam-emergency-recovery.sh
```

### Data Corruption

#### Detection Signs
- Analytics data tidak konsisten
- Link count tidak sesuai
- Database errors di logs
- Cache inconsistencies

#### Recovery Steps
1. **Stop Application**: `systemctl stop nginx php8.2-fpm`
2. **Backup Current**: `/usr/local/bin/backup.sh`
3. **Restore from Backup**: `/usr/local/bin/backup.sh restore [timestamp]`
4. **Verify Data**: Check konsistensi data
5. **Restart Services**: `systemctl start nginx php8.2-fpm`
6. **Monitor**: Watch untuk error berikutnya

## 📞 Contact Support

### When to Contact
- **Critical Issues**: System down, data corruption, security breach
- **Urgent Issues**: Performance degradation, feature not working
- **Normal Issues**: General questions, feature requests

### Contact Methods
- **Telegram**: @pemendeklinkbot
- **Email**: support@aqwam.id
- **Emergency**: +62-XXX-XXXX-XXXX (jika tersedia)
- **GitHub**: https://github.com/your-org/aqwam-url-shortener/issues

### Information to Provide
Saat menghubungi support, sediakan:
1. **Description**: Deskripsi detail masalah
2. **Steps to Reproduce**: Langkah-langkah untuk mereproduksi masalah
3. **Expected vs Actual**: Hasil yang diharapkan vs yang terjadi
4. **Environment**: Browser, OS, device yang digunakan
5. **Time**: Kapan masalah terjadi
6. **Error Messages**: Screenshot atau teks error lengkap
7. **User ID**: Telegram user ID (jika relevan)

## 📚 Additional Resources

### Documentation
- [API Documentation](./API_DOCUMENTATION.md)
- [Deployment Guide](./DEPLOYMENT.md)
- [User Guide](./USER_GUIDE.md)
- [Monitoring Guide](./MONITORING.md)

### Tools & Commands
- **Health Check**: `curl https://aqwam.id/health-check.php`
- **Log Viewer**: `tail -f /var/log/nginx/aqwam.id.error.log`
- **Cache Manager**: `php artisan cache:clear`
- **Queue Monitor**: `php artisan queue:monitor`
- **Database Backup**: `/usr/local/bin/backup.sh`

### Performance Monitoring
- **Response Time**: `curl -w "%{time_total}" -o /dev/null -s https://aqwam.id/`
- **System Load**: `top`, `htop`, `uptime`
- **Memory Usage**: `free -m`, `ps aux --sort=-%mem`
- **Disk Usage**: `df -h`, `du -sh`

---

*Troubleshooting guide ini akan diperbarui secara berkala sesuai dengan masalah yang ditemukan dan solusi yang dikembangkan.*