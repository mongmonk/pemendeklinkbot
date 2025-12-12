# 🚨 PERBAIKAN ERROR "DIRECTORY INDEX IS FORBIDDEN"

## 📋 **Diagnosis Presisi**
Error log menunjukkan: `directory index of "/var/www/html/aqwam.id/" is forbidden`

Ini adalah **masalah spesifik Nginx** yang sangat umum terjadi pada Laravel!

## 🔍 **Root Cause Exact:**
Nginx tidak punya izin untuk **membaca directory listing** atau **index.php tidak ditemukan** di path yang benar.

## 🔧 **Solusi Tepat (Berdasarkan Error Log)**

### **Step 1: Cek Keberadaan File Index**
```bash
# Cek apakah index.php ada di path yang benar
ls -la /var/www/html/aqwam.id/index.php

# Jika tidak ada, cek struktur folder
ls -la /var/www/html/aqwam.id/
ls -la /var/www/html/aqwam.id/public/
```

### **Step 2: Fix Konfigurasi Nginx (CRITICAL)**
```nginx
server {
    listen 443 ssl http2;
    server_name aqwam.id www.aqwam.id;

    # PERBAIKAN: Pastikan path ke public folder Laravel
    root /var/www/html/aqwam.id/public;  # ← HARUSNYA INI
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

    # Laravel Configuration - TAMBAHKAN INI
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;  # ← PASTIKAN INI
        include fastcgi_params;
        
        # Tambahkan parameter Laravel
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

### **Step 3: Fix Permissions (Jika Masih Error)**
```bash
# Set ownership yang benar untuk public folder
sudo chown -R www-data:www-data /var/www/html/aqwam.id/public/
sudo chmod -R 755 /var/www/html/aqwam.id/public/

# Pastikan index.php readable
sudo chmod 644 /var/www/html/aqwam.id/public/index.php

# Jika index.php tidak ada di public, copy dari root
sudo cp /var/www/html/aqwam.id/index.php /var/www/html/aqwam.id/public/
sudo chown www-data:www-data /var/www/html/aqwam.id/public/index.php
```

### **Step 4: Cek Struktur Laravel**
```bash
# Verifikasi struktur Laravel yang benar
ls -la /var/www/html/aqwam.id/public/
ls -la /var/www/html/aqwam.id/

# Seharusnya ada:
# /var/www/html/aqwam.id/public/index.php
# /var/www/html/aqwam.id/public/.htaccess
# /var/www/html/aqwam.id/public/css/
# /var/www/html/aqwam.id/public/js/
```

### **Step 5: Test dan Restart**
```bash
# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Test langsung
curl -I https://aqwam.id/
curl -I https://aqwam.id/api/telegram/webhook
```

## 🚨 **Quick Fix (Execute Ini):**
```bash
# Jika index.php tidak di public folder:
sudo mkdir -p /var/www/html/aqwam.id/public/
sudo cp /var/www/html/aqwam.id/index.php /var/www/html/aqwam.id/public/
sudo cp /var/www/html/aqwam.id/.htaccess /var/www/html/aqwam.id/public/ 2>/dev/null || echo "No .htaccess found"
sudo chown -R www-data:www-data /var/www/html/aqwam.id/public/
sudo chmod -R 755 /var/www/html/aqwam.id/public/

# Update Nginx config untuk menggunakan /public
sudo nano /etc/nginx/sites-available/aqwam.id
# Ganti root ke: root /var/www/html/aqwam.id/public;

# Restart Nginx
sudo systemctl reload nginx
```

## 🔍 **Debug Commands:**
```bash
# Cek error log real-time
sudo tail -f /var/log/nginx/aqwam.id.error.log

# Cek apakah index.php ada dan readable
ls -la /var/www/html/aqwam.id/public/index.php

# Test dengan verbose curl
curl -v https://aqwam.id/ 2>&1 | grep -E "(403|200|index.php)"

# Cek Nginx configuration aktif
sudo nginx -T | grep -A5 -B5 "root /var/www"
```

## ✅ **Expected Result:**
Setelah perbaikan:
- `curl https://aqwam.id/` → `200 OK` (Laravel welcome page)
- `curl https://aqwam.id/api/telegram/webhook` → `200 OK`
- Tidak ada lagi "directory index is forbidden"
- Webhook Telegram berfungsi normal

## 📞 **Jika Masih Error:**
1. Cek apakah Laravel di-deploy dengan benar
2. Verifikasi path deployment benar
3. Cek SELinux: `sudo setenforce 0` sementara untuk testing
4. Restart Nginx: `sudo systemctl restart nginx`