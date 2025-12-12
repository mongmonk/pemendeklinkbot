# 🚨 PERBAIKAN KONFIGURASI NGINX UNTUK WEBHOOK TELEGRAM

## 📋 **Diagnosis Masalah**
Berdasarkan analisis konfigurasi Nginx yang Anda berikan, saya menemukan **masalah kritis** yang menyebabkan webhook Telegram mengalami "Connection refused":

### **Root Cause:**
1. **Path deployment salah** - Konfigurasi menunjukkan `/var/www/html/aqwam` tapi mungkin tidak sesuai
2. **PHP-FPM socket path mungkin tidak valid** - `/var/run/php/php8.2-fpm.sock` 
3. **Environment Laravel tidak production** - .env masih menunjuk ke localhost

## 🔧 **Solusi Perbaikan**

### **1. Verifikasi Path Deployment**
```bash
# Cek apakah Laravel ada di path yang benar
ls -la /var/www/html/aqwam/
ls -la /var/www/html/aqwam/public/
ls -la /var/www/html/aqwam/index.php
```

### **2. Perbaiki Konfigurasi Nginx**
Tambahkan konfigurasi berikut ke server block Anda:

```nginx
server {
    listen 443 ssl http2;
    server_name aqwam.id www.aqwam.id;

    # Root directory - PASTIKAN BENAR
    root /var/www/html/aqwam/public;  # ← TAMBAHKAN /public
    index index.php index.html;

    # SSL Configuration (tetap sama)
    ssl_certificate /etc/letsencrypt/live/aqwam.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aqwam.id/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers (tetap sama)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline' 'unsafe-eval'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Laravel Configuration - PERBAIKAN
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration - VERIFIKASI SOCKET
    location ~ \.php$ {
        # Cek socket yang tersedia:
        # ls -la /var/run/php/
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;  # ← VERIFIKASI PATH INI
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
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

    # Static Assets dengan Cache (tetap sama)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
        access_log off;
        gzip_static on;
    }

    # Vite build assets (tetap sama)
    location ~* /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        brotli_static on;
        gzip_static on;
    }

    # Security (tetap sama)
    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ ^/(storage|bootstrap|config|database|artisan|composer\.|package\.|\.git) {
        deny all;
    }

    # Logging (tetap sama)
    access_log /var/log/nginx/aqwam.id.access.log combined;
    error_log /var/log/nginx/aqwam.id.error.log;
}
```

### **3. Verifikasi PHP-FPM**
```bash
# Cek status PHP-FPM
systemctl status php8.2-fpm

# Cek socket path
ls -la /var/run/php/

# Restart PHP-FPM jika perlu
systemctl restart php8.2-fpm
```

### **4. Update Environment Laravel**
```bash
# Copy production config
cp .env.production .env

# Generate key jika belum ada
php artisan key:generate

# Clear dan cache ulang
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan optimize
```

### **5. Test Konfigurasi Nginx**
```bash
# Test syntax
nginx -t

# Reload Nginx
systemctl reload nginx
```

### **6. Verifikasi Webhook**
```bash
# Test webhook endpoint
curl -X POST https://aqwam.id/api/telegram/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "connection"}' \
  -v

# Setup ulang webhook
php artisan telegram:setup-webhook
```

## 🚨 **Critical Points yang Harus Diperbaiki:**

1. **ROOT PATH**: Tambahkan `/public` → `/var/www/html/aqwam/public`
2. **PHP-FPM SOCKET**: Verifikasi path socket benar
3. **ENVIRONMENT**: Pastikan .env production digunakan
4. **PERMISSIONS**: Pastikan permission benar:
   ```bash
   chown -R www-data:www-data /var/www/html/aqwam
   chmod -R 755 /var/www/html/aqwam
   chmod -R 777 /var/www/html/aqwam/storage
   chmod -R 777 /var/www/html/aqwam/bootstrap/cache
   ```

## 📞 **Jika Masih Bermasalah:**

1. Cek error log: `tail -f /var/log/nginx/aqwam.id.error.log`
2. Cek PHP-FPM log: `tail -f /var/log/php8.2-fpm.log`
3. Test Laravel langsung: `curl https://aqwam.id/index.php`

## ✅ **Expected Result:**
Setelah perbaikan, webhook Telegram seharusnya:
- Mengembalikan `200 OK` bukan `404`
- Tidak ada lagi "Connection refused"
- Webhook info menunjukkan "Last Error: None"