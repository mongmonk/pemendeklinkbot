# Deployment Guide - Aqwam URL Shortener

## 📋 Overview

Guide lengkap untuk deployment sistem Aqwam URL Shortener ke production environment dengan domain `aqwam.id`.

## 🏗️ Prerequisites

### Server Requirements

#### Minimum Requirements
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 50GB SSD
- **Network**: 100Mbps

#### Recommended Requirements
- **CPU**: 4 cores
- **RAM**: 8GB
- **Storage**: 100GB SSD
- **Network**: 1Gbps

### Software Requirements

#### Operating System
- Ubuntu 20.04 LTS atau lebih tinggi
- CentOS 8 atau lebih tinggi
- Debian 11 atau lebih tinggi

#### Web Server
- Nginx 1.18+ (recommended)
- Apache 2.4+ (alternative)

#### PHP
- PHP 8.2+ dengan extensions:
  ```bash
  php-fpm
  php-mysql
  php-redis
  php-curl
  php-gd
  php-json
  php-mbstring
  php-xml
  php-bcmath
  php-intl
  php-zip
  ```

#### Database
- MySQL 8.0+ atau MariaDB 10.3+
- Redis 6.0+

#### Additional Tools
- Composer 2.0+
- Node.js 18+ & NPM 8+
- Git 2.0+
- SSL Certificate (Let's Encrypt recommended)

## 🚀 Deployment Steps

### 1. Server Preparation

#### Update System
```bash
sudo apt update && sudo apt upgrade -y
```

#### Install Required Software
```bash
# Install Nginx
sudo apt install nginx -y

# Install PHP dan extensions
sudo apt install php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl php8.2-gd php8.2-json php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-intl php8.2-zip -y

# Install MySQL
sudo apt install mysql-server -y

# Install Redis
sudo apt install redis-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js & NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install Git
sudo apt install git -y
```

#### Configure Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 2. Database Setup

#### MySQL Configuration
```bash
# Secure MySQL
sudo mysql_secure_installation

# Create database dan user
sudo mysql -u root -p
```

```sql
CREATE DATABASE aqwam_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'aqwam_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON aqwam_production.* TO 'aqwam_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Optimize MySQL Configuration
Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 200
query_cache_size = 64M
query_cache_type = 1
```

### 3. Redis Configuration

#### Configure Redis
Edit `/etc/redis/redis.conf`:
```ini
# Memory
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password
```

#### Restart Redis
```bash
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 4. Application Deployment

#### Clone Repository
```bash
# Create application directory
sudo mkdir -p /var/www/aqwam
cd /var/www/aqwam

# Clone repository
git clone https://github.com/your-username/aqwam-url-shortener.git .

# Set permissions
sudo chown -R www-data:www-data /var/www/aqwam
sudo chmod -R 755 /var/www/aqwam
```

#### Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm install --production
npm run build
```

#### Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

Production `.env` configuration:
```env
APP_NAME=AQWAM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aqwam.id
APP_TIMEZONE=Asia/Jakarta

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aqwam_production
DB_USERNAME=aqwam_user
DB_PASSWORD=secure_password_here

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Telegram
TELEGRAM_BOT_TOKEN=8552109110:AAHJHMIBm_ai5v0Kti9DqXHTs4kQxqdBKf8
TELEGRAM_WEBHOOK_URL=https://aqwam.id/api/telegram/webhook
TELEGRAM_WEBHOOK_SECRET=your_webhook_secret_here
TELEGRAM_RATE_LIMIT_ENABLED=true
TELEGRAM_RATE_LIMIT_ATTEMPTS=5
TELEGRAM_RATE_LIMIT_MINUTES=1

# Session & File
SESSION_DRIVER=database
SESSION_LIFETIME=120
FILESYSTEM_DISK=local

# Log
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# Domain Configuration
PRODUCTION_APP_URL=https://aqwam.id
```

#### Database Migration
```bash
# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force
```

#### Optimize Application
```bash
# Clear and cache configuration
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 5. Web Server Configuration

#### Nginx Configuration
Create `/etc/nginx/sites-available/aqwam.id`:
```nginx
server {
    listen 80;
    server_name aqwam.id www.aqwam.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name aqwam.id www.aqwam.id;

    root /var/www/aqwam/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/aqwam.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aqwam.id/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Laravel Configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Increase timeout for long operations
        fastcgi_read_timeout 300;
    }

    # Static Files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Security
    location ~ /\.ht {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/aqwam.id.access.log;
    error_log /var/log/nginx/aqwam.id.error.log;
}
```

#### Enable Site
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/aqwam.id /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### 6. SSL Certificate Setup

#### Install Let's Encrypt
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d aqwam.id -d www.aqwam.id

# Setup auto-renewal
sudo crontab -e
```

Add this line for auto-renewal:
```cron
0 12 * * * /usr/bin/certbot renew --quiet
```

### 7. Queue Worker Setup

#### Create Supervisor Configuration
Create `/etc/supervisor/conf.d/aqwam-worker.conf`:
```ini
[program:aqwam-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/aqwam/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/aqwam-worker.log
stopwaitsecs=3600
```

#### Start Queue Worker
```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start worker
sudo supervisorctl start aqwam-worker:*
```

### 8. Telegram Bot Webhook Setup

#### Set Webhook
```bash
cd /var/www/aqwam

# Setup webhook
php artisan telegram:setup-webhook --url=https://aqwam.id/api/telegram/webhook
```

#### Verify Webhook
```bash
# Check webhook info
php artisan telegram:setup-webhook
```

### 9. Cache Warming

#### Warm Up Popular Links
```bash
# Warm up cache
php artisan url:warm-cache --limit=100
```

#### Setup Cache Warming Cron
```bash
sudo crontab -e
```

Add cache warming:
```cron
0 */6 * * * cd /var/www/aqwam && php artisan url:warm-cache --limit=100
```

### 10. Monitoring Setup

#### Setup Log Rotation
Create `/etc/logrotate.d/aqwam`:
```
/var/log/nginx/aqwam.id.* {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}

/var/log/supervisor/aqwam-worker.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        supervisorctl restart aqwam-worker:*
    endscript
}
```

#### Setup Application Monitoring
```bash
# Create monitoring directory
sudo mkdir -p /var/log/aqwam
sudo chown www-data:www-data /var/log/aqwam

# Setup Laravel logging
php artisan log:clear
```

## 🔧 Post-Deployment Configuration

### 1. Performance Optimization

#### PHP-FPM Configuration
Edit `/etc/php/8.2/fpm/pool.d/www.conf`:
```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

# Performance
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Timeout
request_terminate_timeout = 300
```

#### Restart PHP-FPM
```bash
sudo systemctl restart php8.2-fpm
```

### 2. Security Hardening

#### File Permissions
```bash
# Secure sensitive files
sudo chmod 600 /var/www/aqwam/.env
sudo chmod 600 /var/www/aqwam/storage/oauth-*.key

# Set proper ownership
sudo chown -R www-data:www-data /var/www/aqwam
sudo chmod -R 755 /var/www/aqwam
sudo chmod -R 775 /var/www/aqwam/storage
sudo chmod -R 775 /var/www/aqwam/bootstrap/cache
```

#### Fail2Ban Setup
```bash
# Install Fail2Ban
sudo apt install fail2ban -y

# Create Nginx filter
sudo nano /etc/fail2ban/filter.d/nginx-req-limit.conf
```

Add this content:
```ini
[Definition]
failregex = ^<HOST> -.*"(GET|POST).*HTTP.*" (404|429|500).*$
ignoreregex =
```

Create jail configuration `/etc/fail2ban/jail.local`:
```ini
[nginx-req-limit]
enabled = true
port = http,https
filter = nginx-req-limit
logpath = /var/log/nginx/aqwam.id.error.log
maxretry = 10
findtime = 600
bantime = 3600
```

```bash
# Restart Fail2Ban
sudo systemctl restart fail2ban
```

## 📊 Monitoring & Maintenance

### 1. Health Checks

#### Create Health Check Script
Create `/var/www/aqwam/health-check.php`:
```php
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = [
    'database' => false,
    'redis' => false,
    'cache' => false,
    'queue' => false,
];

try {
    \DB::connection()->getPdo();
    $status['database'] = true;
} catch (\Exception $e) {
    // Database connection failed
}

try {
    \Cache::store('redis')->put('health_check', 'ok', 60);
    $status['redis'] = \Cache::store('redis')->get('health_check') === 'ok';
} catch (\Exception $e) {
    // Redis connection failed
}

try {
    \Cache::put('health_check', 'ok', 60);
    $status['cache'] = \Cache::get('health_check') === 'ok';
} catch (\Exception $e) {
    // Cache failed
}

// Check queue worker
$status['queue'] = \Cache::get('queue_worker_heartbeat', false);

$allHealthy = array_reduce($status, function($carry, $item) {
    return $carry && $item;
}, true);

header('Content-Type: application/json');
echo json_encode([
    'status' => $allHealthy ? 'healthy' : 'unhealthy',
    'checks' => $status,
    'timestamp' => now()->toISOString()
]);
```

#### Setup Monitoring Cron
```bash
sudo crontab -e
```

Add health check:
```cron
*/5 * * * * curl -s https://aqwam.id/health-check.php | jq '.status' | grep -q healthy || echo "Health check failed" | mail -s "Aqwam Health Check Alert" admin@aqwam.id
```

### 2. Backup Strategy

#### Database Backup Script
Create `/var/www/aqwam/backup-database.sh`:
```bash
#!/bin/bash

BACKUP_DIR="/var/backups/aqwam"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="aqwam_production"
DB_USER="aqwam_user"
DB_PASS="secure_password_here"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Remove old backups (keep 7 days)
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete

echo "Database backup completed: db_backup_$DATE.sql.gz"
```

Make it executable:
```bash
sudo chmod +x /var/www/aqwam/backup-database.sh
```

Setup backup cron:
```cron
0 2 * * * /var/www/aqwam/backup-database.sh
```

#### Application Backup Script
Create `/var/www/aqwam/backup-app.sh`:
```bash
#!/bin/bash

BACKUP_DIR="/var/backups/aqwam"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/aqwam"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup application files (excluding sensitive data)
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz \
    --exclude='.env' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='node_modules' \
    --exclude='vendor' \
    -C $APP_DIR .

# Remove old backups (keep 7 days)
find $BACKUP_DIR -name "app_backup_*.tar.gz" -mtime +7 -delete

echo "Application backup completed: app_backup_$DATE.tar.gz"
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Webhook Not Working
```bash
# Check webhook status
php artisan telegram:setup-webhook

# Check SSL certificate
curl -I https://aqwam.id/api/telegram/webhook

# Check logs
tail -f /var/log/nginx/aqwam.id.error.log
tail -f /var/www/aqwam/storage/logs/laravel.log
```

#### 2. Slow Performance
```bash
# Check cache status
php artisan cache:status

# Clear cache
php artisan cache:clear

# Check queue worker
sudo supervisorctl status aqwam-worker:*

# Warm up cache
php artisan url:warm-cache --limit=100
```

#### 3. Database Connection Issues
```bash
# Test database connection
mysql -u aqwam_user -p aqwam_production

# Check MySQL status
sudo systemctl status mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log
```

#### 4. Redis Connection Issues
```bash
# Test Redis connection
redis-cli ping

# Check Redis status
sudo systemctl status redis-server

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

## 📋 Deployment Checklist

### Pre-Deployment Checklist
- [ ] Server requirements met
- [ ] SSL certificate ready
- [ ] Database credentials ready
- [ ] Redis password set
- [ ] Firewall configured
- [ ] Backup strategy planned

### Deployment Checklist
- [ ] Code deployed to `/var/www/aqwam`
- [ ] Dependencies installed
- [ ] Environment configured
- [ ] Database migrated
- [ ] Cache optimized
- [ ] Web server configured
- [ ] SSL certificate installed
- [ ] Queue worker started
- [ ] Webhook configured
- [ ] Cache warmed
- [ ] Monitoring setup

### Post-Deployment Checklist
- [ ] Health checks passing
- [ ] Performance optimized
- [ ] Security hardened
- [ ] Backup scripts running
- [ ] Log rotation configured
- [ ] Monitoring alerts working
- [ ] Documentation updated

## 🔄 Update Process

### Rolling Update Steps
1. Backup current version
2. Deploy new code to staging
3. Test staging environment
4. Switch traffic to new version
5. Monitor for issues
6. Rollback if necessary

### Zero-Downtime Deployment
```bash
# Create new release directory
mkdir -p /var/www/releases
cp -r /var/www/aqwam /var/www/releases/aqwam_$(date +%Y%m%d_%H%M%S)

# Update symlink
ln -sfn /var/www/releases/aqwam_$(date +%Y%m%d_%H%M%S) /var/www/aqwam_current

# Update Nginx to use new directory
sudo nano /etc/nginx/sites-available/aqwam.id
# Change root to /var/www/aqwam_current/public

# Reload Nginx
sudo nginx -t && sudo systemctl reload nginx
```

---

*Deployment guide ini akan diperbarui secara berkala sesuai dengan perkembangan sistem.*