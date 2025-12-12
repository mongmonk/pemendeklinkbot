# 🚨 PERBAIKAN ERROR 403 FORBIDDEN PADA WEBHOOK TELEGRAM

## 📋 **Update Diagnosis**
Bagus! Path root sudah benar `/var/www/html/aqwam.id`, tapi sekarang muncul **Error 403 Forbidden**. Ini adalah kemajuan yang menunjukkan Nginx sudah menemukan file tapi tidak punya izin akses.

## 🔍 **Kemungkinan Penyebab Error 403:**

### **1. File/Folder Permissions (Most Likely)**
- Nginx tidak bisa membaca file Laravel
- User/owner tidak sesuai

### **2. SELinux Issues (Common di Server)**
- SELinux memblokir akses Nginx ke file

### **3. PHP-FPM Permission**
- Socket PHP-FPM tidak bisa diakses Nginx

### **4. Index File Missing**
- `index.php` tidak ada atau tidak bisa diakses

## 🔧 **Solusi Perbaikan Lengkap**

### **Step 1: Cek Current Permissions**
```bash
# Cek ownership folder utama
ls -la /var/www/html/aqwam.id/

# Cek ownership file index.php
ls -la /var/www/html/aqwam.id/index.php

# Cek Nginx user
ps aux | grep nginx
# atau
ps aux | grep www-data
```

### **Step 2: Fix Permissions (CRITICAL)**
```bash
# Set ownership yang benar
sudo chown -R www-data:www-data /var/www/html/aqwam.id/

# Set permissions folder
sudo find /var/www/html/aqwam.id/ -type d -exec chmod 755 {} \;

# Set permissions file
sudo find /var/www/html/aqwam.id/ -type f -exec chmod 644 {} \;

# Special permissions untuk Laravel
sudo chmod -R 777 /var/www/html/aqwam.id/storage/
sudo chmod -R 777 /var/www/html/aqwam.id/bootstrap/cache/

# Pastikan index.php executable
sudo chmod 644 /var/www/html/aqwam.id/index.php
```

### **Step 3: Fix SELinux (Jika Aktif)**
```bash
# Cek status SELinux
sestatus

# Jika enforcing, fix SELinux context
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_sendmail 1
sudo setsebool -P httpd_execmem 1

# Set context yang benar
sudo semanage fcontext -a -t httpd_sys_content_t "/var/www/html/aqwam.id(/.*)?"
sudo restorecon -Rv /var/www/html/aqwam.id/

# Atau disable SELinux sementara untuk testing
sudo setenforce 0
```

### **Step 4: Fix PHP-FPM Configuration**
```bash
# Cek PHP-FPM user
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Pastikan user dan group benar:
user = www-data
group = www-data

# Cek socket permissions
ls -la /var/run/php/

# Fix socket permissions
sudo chmod 666 /var/run/php/php8.2-fpm.sock
```

### **Step 5: Update Nginx Configuration**
```nginx
server {
    listen 443 ssl http2;
    server_name aqwam.id www.aqwam.id;

    # Root directory (SUDAH BENAR)
    root /var/www/html/aqwam.id;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/aqwam.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aqwam.id/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline' 'unsafe-eval'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Laravel Configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration - TAMBAHKAN INI
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;  # ← GANTI realpath_root
        include fastcgi_params;
        
        # Tambahkan parameter ini
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param SERVER_NAME $host;
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_SCHEME https;
        
        # Optimasi untuk Laravel
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }

    # Static Assets dengan Cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
        access_log off;
        gzip_static on;
    }

    # Vite build assets
    location ~* /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        brotli_static on;
        gzip_static on;
    }

    # Security
    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ ^/(storage|bootstrap|config|database|artisan|composer\.|package\.|\.git) {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/aqwam.id.access.log combined;
    error_log /var/log/nginx/aqwam.id.error.log;
}
```

### **Step 6: Test dan Restart Services**
```bash
# Test Nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

# Cek status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
```

### **Step 7: Debug Error 403**
```bash
# Cek Nginx error log
sudo tail -f /var/log/nginx/aqwam.id.error.log

# Cek PHP-FPM log
sudo tail -f /var/log/php8.2-fpm.log

# Test dengan curl
curl -I https://aqwam.id/
curl -I https://aqwam.id/api/telegram/webhook
```

## 🚨 **Quick Fix (Coba Ini Dulu):**
```bash
# Execute semua fix sekaligus
sudo chown -R www-data:www-data /var/www/html/aqwam.id/
sudo find /var/www/html/aqwam.id/ -type d -exec chmod 755 {} \;
sudo find /var/www/html/aqwam.id/ -type f -exec chmod 644 {} \;
sudo chmod -R 777 /var/www/html/aqwam.id/storage/
sudo chmod -R 777 /var/www/html/aqwam.id/bootstrap/cache/
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

## ✅ **Expected Result:**
Setelah perbaikan:
- `curl https://aqwam.id/` → `200 OK`
- `curl https://aqwam.id/api/telegram/webhook` → `200 OK`
- Webhook Telegram berfungsi tanpa "Connection refused"

## 📞 **Jika Masih 403:**
1. Cek error log: `sudo tail -f /var/log/nginx/aqwam.id.error.log`
2. Cek SELinux: `sestatus`
3. Cek PHP-FPM: `sudo systemctl status php8.2-fpm`
4. Test langsung ke PHP: `curl https://aqwam.id/index.php`