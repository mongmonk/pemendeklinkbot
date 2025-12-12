#!/bin/bash

# Aqwam URL Shortener - Server Setup Script
# Version: 1.0
# Author: Aqwam Team

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="aqwam-url-shortener"
DEPLOY_USER="www-data"
DEPLOY_PATH="/var/www/aqwam"
BACKUP_PATH="/var/backups/aqwam"
LOG_FILE="/var/log/setup.log"

# Logging function
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> $LOG_FILE
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" >> $LOG_FILE
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $1" >> $LOG_FILE
}

info() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] INFO: $1" >> $LOG_FILE
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root"
    fi
}

# Update system
update_system() {
    log "Updating system packages..."
    
    apt update
    apt upgrade -y
    
    log "System updated successfully"
}

# Install required packages
install_packages() {
    log "Installing required packages..."
    
    # Install web server
    apt install -y nginx
    
    # Install PHP and extensions
    apt install -y php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl php8.2-gd php8.2-json php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-intl php8.2-zip
    
    # Install database
    apt install -y mysql-server
    
    # Install cache
    apt install -y redis-server
    
    # Install additional tools
    apt install -y curl wget git unzip supervisor certbot python3-certbot-nginx
    
    log "Packages installed successfully"
}

# Configure firewall
setup_firewall() {
    log "Configuring firewall..."
    
    # Enable UFW
    ufw --force enable
    
    # Allow SSH
    ufw allow OpenSSH
    
    # Allow HTTP/HTTPS
    ufw allow 'Nginx Full'
    
    # Enable firewall
    ufw --force enable
    
    log "Firewall configured successfully"
}

# Setup MySQL
setup_mysql() {
    log "Setting up MySQL..."
    
    # Secure MySQL
    mysql_secure_installation --use-default-answers
    
    # Create database and user
    mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS aqwam_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'aqwam_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';
GRANT ALL PRIVILEGES ON aqwam_production.* TO 'aqwam_user'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Optimize MySQL configuration
    cat > /etc/mysql/mysql.conf.d/aqwam.cnf << 'EOF'
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 200
query_cache_size = 64M
query_cache_type = 1
slow_query_log = /var/log/mysql/slow.log
long_query_time = 2
EOF
    
    # Restart MySQL
    systemctl restart mysql
    systemctl enable mysql
    
    log "MySQL configured successfully"
}

# Setup Redis
setup_redis() {
    log "Setting up Redis..."
    
    # Configure Redis
    cat > /etc/redis/redis.conf << 'EOF'
# Redis configuration for Aqwam URL Shortener

# Network
bind 127.0.0.1
port 6379
timeout 0

# Memory
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security
requirepass RedisPassword123!

# Logging
loglevel notice
logfile /var/log/redis/redis-server.log

# Performance
tcp-keepalive 300
tcp-backlog 511
EOF
    
    # Restart Redis
    systemctl restart redis-server
    systemctl enable redis-server
    
    log "Redis configured successfully"
}

# Setup PHP-FPM
setup_php() {
    log "Setting up PHP-FPM..."
    
    # Configure PHP-FPM
    cat > /etc/php/8.2/fpm/pool.d/www.conf << 'EOF'
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

# Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Timeouts
request_terminate_timeout = 300
request_slowlog_timeout = 10

# Logging
slowlog = /var/log/php/slow.log
access.log = /var/log/php/access.log
access.format = "%R - %u %t \"%m %r\" %s %O"

# PHP settings
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[post_max_size] = 20M
php_admin_value[upload_max_filesize] = 20M
EOF
    
    # Restart PHP-FPM
    systemctl restart php8.2-fpm
    systemctl enable php8.2-fpm
    
    log "PHP-FPM configured successfully"
}

# Setup Nginx
setup_nginx() {
    log "Setting up Nginx..."
    
    # Create Nginx configuration
    cat > /etc/nginx/sites-available/aqwam.id << 'EOF'
server {
    listen 80;
    server_name aqwam.id www.aqwam.id;
    return 301 https://\$server_name\$request_uri;
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
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
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
EOF
    
    # Enable site
    ln -s /etc/nginx/sites-available/aqwam.id /etc/nginx/sites-enabled/
    
    # Test configuration
    nginx -t
    
    # Restart Nginx
    systemctl restart nginx
    systemctl enable nginx
    
    log "Nginx configured successfully"
}

# Setup SSL Certificate
setup_ssl() {
    log "Setting up SSL certificate..."
    
    # Get SSL certificate
    certbot --nginx -d aqwam.id -d www.aqwam.id --non-interactive --agree-tos --email admin@aqwam.id
    
    # Setup auto-renewal
    echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
    
    log "SSL certificate configured successfully"
}

# Setup Supervisor
setup_supervisor() {
    log "Setting up Supervisor..."
    
    # Create supervisor configuration
    cat > /etc/supervisor/conf.d/aqwam-worker.conf << 'EOF'
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
EOF
    
    # Update supervisor
    supervisorctl reread
    supervisorctl update
    
    # Start workers
    supervisorctl start aqwam-worker:*
    
    log "Supervisor configured successfully"
}

# Setup directories
setup_directories() {
    log "Setting up directories..."
    
    # Create application directory
    mkdir -p "$DEPLOY_PATH"
    
    # Create backup directory
    mkdir -p "$BACKUP_PATH"
    
    # Create log directories
    mkdir -p /var/log/aqwam
    mkdir -p /var/log/nginx
    mkdir -p /var/log/php
    mkdir -p /var/log/mysql
    mkdir -p /var/log/redis
    mkdir -p /var/log/supervisor
    
    # Set permissions
    chown -R www-data:www-data "$DEPLOY_PATH"
    chmod -R 755 "$DEPLOY_PATH"
    chmod -R 775 "$DEPLOY_PATH/storage"
    chmod -R 775 "$DEPLOY_PATH/bootstrap/cache"
    
    log "Directories created and permissions set"
}

# Setup log rotation
setup_logrotate() {
    log "Setting up log rotation..."
    
    # Create logrotate configuration
    cat > /etc/logrotate.d/aqwam << 'EOF'
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
EOF
    
    log "Log rotation configured successfully"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring..."
    
    # Create monitoring script
    cat > /usr/local/bin/aqwam-monitor.sh << 'EOF'
#!/bin/bash
HEALTH_URL="https://aqwam.id/health-check.php"
LOG_FILE="/var/log/aqwam/monitoring.log"
ALERT_EMAIL="admin@aqwam.id"

RESPONSE=\$(curl -s "\$HEALTH_URL")
STATUS=\$(echo "\$RESPONSE" | jq -r '.status')

if [[ "\$STATUS" != "healthy" ]]; then
    echo "\$(date): Health check failed - \$RESPONSE" >> "\$LOG_FILE"
    echo "Aqwam system unhealthy: \$RESPONSE" | mail -s "Aqwam Health Alert" "\$ALERT_EMAIL"
fi
EOF
    
    chmod +x /usr/local/bin/aqwam-monitor.sh
    
    # Add to crontab
    echo "*/5 * * * * /usr/local/bin/aqwam-monitor.sh" | crontab -
    
    log "Monitoring configured successfully"
}

# Setup backup
setup_backup() {
    log "Setting up backup..."
    
    # Create backup script
    cat > /usr/local/bin/aqwam-backup.sh << 'EOF'
#!/bin/bash
BACKUP_PATH="/var/backups/aqwam"
TIMESTAMP=\$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "\$BACKUP_PATH"

# Backup database
mysqldump -u aqwam_user -p'SecurePassword123!' aqwam_production | gzip > "\$BACKUP_PATH/db_backup_\$TIMESTAMP.sql.gz"

# Backup application
tar -czf "\$BACKUP_PATH/app_backup_\$TIMESTAMP.tar.gz" -C /var/www/aqwam .

# Clean old backups (keep 7 days)
find "\$BACKUP_PATH" -name "*.gz" -mtime +7 -delete
find "\$BACKUP_PATH" -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: \$TIMESTAMP"
EOF
    
    chmod +x /usr/local/bin/aqwam-backup.sh
    
    # Add to crontab
    echo "0 2 * * * /usr/local/bin/aqwam-backup.sh" | crontab -
    
    log "Backup configured successfully"
}

# Main setup function
main() {
    log "Starting Aqwam URL Shortener server setup..."
    
    check_root
    update_system
    setup_directories
    install_packages
    setup_firewall
    setup_mysql
    setup_redis
    setup_php
    setup_nginx
    setup_ssl
    setup_supervisor
    setup_logrotate
    setup_monitoring
    setup_backup
    
    log "Server setup completed successfully!"
    log "Next steps:"
    log "1. Deploy application code to $DEPLOY_PATH"
    log "2. Configure environment file"
    log "3. Run database migrations"
    log "4. Start application services"
    log "5. Setup SSL certificate (if not using Let's Encrypt)"
    log "6. Test application functionality"
}

# Handle script arguments
case "${1:-setup}" in
    "setup")
        main
        ;;
    "mysql")
        setup_mysql
        ;;
    "redis")
        setup_redis
        ;;
    "php")
        setup_php
        ;;
    "nginx")
        setup_nginx
        ;;
    "ssl")
        setup_ssl
        ;;
    "supervisor")
        setup_supervisor
        ;;
    "monitoring")
        setup_monitoring
        ;;
    "backup")
        setup_backup
        ;;
    *)
        echo "Usage: $0 {setup|mysql|redis|php|nginx|ssl|supervisor|monitoring|backup}"
        echo "  setup     - Complete server setup"
        echo "  mysql     - Setup MySQL only"
        echo "  redis     - Setup Redis only"
        echo "  php       - Setup PHP-FPM only"
        echo "  nginx     - Setup Nginx only"
        echo "  ssl       - Setup SSL certificate only"
        echo "  supervisor - Setup Supervisor only"
        echo "  monitoring - Setup monitoring only"
        echo "  backup    - Setup backup only"
        exit 1
        ;;
esac