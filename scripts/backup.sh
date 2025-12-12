#!/bin/bash

# Aqwam URL Shortener - Backup Script
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
DEPLOY_PATH="/var/www/aqwam"
BACKUP_PATH="/var/backups/aqwam"
LOG_FILE="/var/log/backup.log"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

# Logging function
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> $LOG_FILE
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" >> $LOG_FILE
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
        exit 1
    fi
}

# Create backup directory
create_backup_dir() {
    log "Creating backup directory..."
    
    mkdir -p "$BACKUP_PATH"
    chmod 755 "$BACKUP_PATH"
    
    log "Backup directory created: $BACKUP_PATH"
}

# Backup database
backup_database() {
    log "Starting database backup..."
    
    if [[ ! -f "$DEPLOY_PATH/.env" ]]; then
        error "Environment file not found: $DEPLOY_PATH/.env"
        exit 1
    fi
    
    # Load database configuration
    source "$DEPLOY_PATH/.env"
    
    if [[ -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
        error "Database configuration not found in .env file"
        exit 1
    fi
    
    # Create database backup
    DB_BACKUP_FILE="${BACKUP_PATH}/db_backup_${TIMESTAMP}.sql.gz"
    
    if mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$DB_BACKUP_FILE"; then
        log "Database backup completed: $DB_BACKUP_FILE"
        
        # Verify backup file
        if [[ -f "$DB_BACKUP_FILE" && -s "$DB_BACKUP_FILE" ]]; then
            BACKUP_SIZE=$(du -h "$DB_BACKUP_FILE" | cut -f1)
            log "Backup size: $BACKUP_SIZE"
        else
            error "Database backup file is empty or not created"
            exit 1
        fi
    else
        error "Database backup failed"
        exit 1
    fi
}

# Backup application files
backup_application() {
    log "Starting application backup..."
    
    APP_BACKUP_FILE="${BACKUP_PATH}/app_backup_${TIMESTAMP}.tar.gz"
    
    # Create application backup excluding sensitive and temporary files
    tar -czf "$APP_BACKUP_FILE" \
        --exclude='.env' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/testing/*' \
        --exclude='storage/app/public/*' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='.git' \
        --exclude='storage/app/geoip/*.mmdb' \
        -C "$DEPLOY_PATH" .
    
    if [[ $? -eq 0 ]]; then
        log "Application backup completed: $APP_BACKUP_FILE"
        
        # Verify backup file
        if [[ -f "$APP_BACKUP_FILE" && -s "$APP_BACKUP_FILE" ]]; then
            BACKUP_SIZE=$(du -h "$APP_BACKUP_FILE" | cut -f1)
            log "Backup size: $BACKUP_SIZE"
        else
            error "Application backup file is empty or not created"
            exit 1
        fi
    else
        error "Application backup failed"
        exit 1
    fi
}

# Backup configuration files
backup_config() {
    log "Starting configuration backup..."
    
    CONFIG_BACKUP_FILE="${BACKUP_PATH}/config_backup_${TIMESTAMP}.tar.gz"
    
    # Backup configuration files
    tar -czf "$CONFIG_BACKUP_FILE" \
        -C /etc \
        nginx/sites-available/aqwam.id \
        nginx/sites-enabled/aqwam.id \
        php/8.2/fpm/pool.d/www.conf \
        supervisor/conf.d/aqwam-worker.conf \
        mysql/mysql.conf.d/mysqld.cnf \
        redis/redis.conf 2>/dev/null || true
    
    if [[ $? -eq 0 ]]; then
        log "Configuration backup completed: $CONFIG_BACKUP_FILE"
    else
        warning "Configuration backup failed (some files may not exist)"
    fi
}

# Backup SSL certificates
backup_ssl() {
    log "Starting SSL backup..."
    
    SSL_BACKUP_FILE="${BACKUP_PATH}/ssl_backup_${TIMESTAMP}.tar.gz"
    
    # Backup SSL certificates if they exist
    if [[ -d "/etc/letsencrypt/live/aqwam.id" ]]; then
        tar -czf "$SSL_BACKUP_FILE" -C /etc letsencrypt/live/aqwam.id letsencrypt/archive/aqwam.id 2>/dev/null || true
        
        if [[ $? -eq 0 ]]; then
            log "SSL backup completed: $SSL_BACKUP_FILE"
        else
            warning "SSL backup failed"
        fi
    else
        info "SSL certificates not found, skipping SSL backup"
    fi
}

# Verify backups
verify_backups() {
    log "Verifying backup files..."
    
    local error_count=0
    
    # Check database backup
    DB_BACKUP_FILE="${BACKUP_PATH}/db_backup_${TIMESTAMP}.sql.gz"
    if [[ ! -f "$DB_BACKUP_FILE" || ! -s "$DB_BACKUP_FILE" ]]; then
        error "Database backup verification failed"
        ((error_count++))
    fi
    
    # Check application backup
    APP_BACKUP_FILE="${BACKUP_PATH}/app_backup_${TIMESTAMP}.tar.gz"
    if [[ ! -f "$APP_BACKUP_FILE" || ! -s "$APP_BACKUP_FILE" ]]; then
        error "Application backup verification failed"
        ((error_count++))
    fi
    
    # Check configuration backup
    CONFIG_BACKUP_FILE="${BACKUP_PATH}/config_backup_${TIMESTAMP}.tar.gz"
    if [[ ! -f "$CONFIG_BACKUP_FILE" || ! -s "$CONFIG_BACKUP_FILE" ]]; then
        warning "Configuration backup verification failed"
    fi
    
    # Check SSL backup
    SSL_BACKUP_FILE="${BACKUP_PATH}/ssl_backup_${TIMESTAMP}.tar.gz"
    if [[ ! -f "$SSL_BACKUP_FILE" || ! -s "$SSL_BACKUP_FILE" ]]; then
        warning "SSL backup verification failed"
    fi
    
    if [[ $error_count -gt 0 ]]; then
        error "Backup verification failed with $error_count errors"
        exit 1
    else
        log "All backups verified successfully"
    fi
}

# Cleanup old backups
cleanup_old_backups() {
    log "Cleaning up old backups (retention: $RETENTION_DAYS days)..."
    
    local deleted_count=0
    
    # Remove old database backups
    while IFS= read -r -d '' file; do
        rm -f "$file"
        ((deleted_count++))
        info "Deleted old database backup: $(basename "$file")"
    done < <(find "$BACKUP_PATH" -name "db_backup_*.sql.gz" -mtime +$RETENTION_DAYS -print0 2>/dev/null)
    
    # Remove old application backups
    while IFS= read -r -d '' file; do
        rm -f "$file"
        ((deleted_count++))
        info "Deleted old application backup: $(basename "$file")"
    done < <(find "$BACKUP_PATH" -name "app_backup_*.tar.gz" -mtime +$RETENTION_DAYS -print0 2>/dev/null)
    
    # Remove old configuration backups
    while IFS= read -r -d '' file; do
        rm -f "$file"
        ((deleted_count++))
        info "Deleted old configuration backup: $(basename "$file")"
    done < <(find "$BACKUP_PATH" -name "config_backup_*.tar.gz" -mtime +$RETENTION_DAYS -print0 2>/dev/null)
    
    # Remove old SSL backups
    while IFS= read -r -d '' file; do
        rm -f "$file"
        ((deleted_count++))
        info "Deleted old SSL backup: $(basename "$file")"
    done < <(find "$BACKUP_PATH" -name "ssl_backup_*.tar.gz" -mtime +$RETENTION_DAYS -print0 2>/dev/null)
    
    log "Cleanup completed. Deleted $deleted_count old backup files."
}

# Generate backup report
generate_report() {
    log "Generating backup report..."
    
    REPORT_FILE="${BACKUP_PATH}/backup_report_${TIMESTAMP}.txt"
    
    cat > "$REPORT_FILE" << EOF
Aqwam URL Shortener - Backup Report
=====================================
Backup Date: $(date)
Timestamp: $TIMESTAMP
Server: $(hostname)

Backup Files:
-------------
Database: db_backup_${TIMESTAMP}.sql.gz
Application: app_backup_${TIMESTAMP}.tar.gz
Configuration: config_backup_${TIMESTAMP}.tar.gz
SSL: ssl_backup_${TIMESTAMP}.tar.gz

Backup Sizes:
-------------
EOF
    
    # Add file sizes to report
    for file in "${BACKUP_PATH}"/*_${TIMESTAMP}.*; do
        if [[ -f "$file" ]]; then
            SIZE=$(du -h "$file" | cut -f1)
            FILENAME=$(basename "$file")
            echo "$FILENAME: $SIZE" >> "$REPORT_FILE"
        fi
    done
    
    cat >> "$REPORT_FILE" << EOF

System Information:
------------------
OS: $(uname -a)
Disk Usage: $(df -h / | tail -1)
Memory Usage: $(free -h | grep Mem)
Uptime: $(uptime)

Backup Retention:
-----------------
Older than $RETENTION_DAYS days will be automatically deleted.

Generated by: Aqwam Backup Script v1.0
EOF
    
    log "Backup report generated: $REPORT_FILE"
}

# Send notification (optional)
send_notification() {
    local status="$1"
    local message="$2"
    
    # Send email notification if configured
    if command -v mail &> /dev/null && [[ -n "$ADMIN_EMAIL" ]]; then
        echo "$message" | mail -s "Aqwam Backup $status" "$ADMIN_EMAIL"
        log "Email notification sent to: $ADMIN_EMAIL"
    fi
    
    # Send Telegram notification if configured
    if command -v curl &> /dev/null && [[ -n "$TELEGRAM_BOT_TOKEN" && -n "$TELEGRAM_CHAT_ID" ]]; then
        curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
            -d "chat_id=${TELEGRAM_CHAT_ID}" \
            -d "text=${message}" \
            -d "parse_mode=HTML"
        log "Telegram notification sent"
    fi
}

# Main backup function
perform_backup() {
    log "Starting backup process..."
    
    check_root
    create_backup_dir
    
    # Perform backups
    backup_database
    backup_application
    backup_config
    backup_ssl
    
    # Verify backups
    verify_backups
    
    # Generate report
    generate_report
    
    # Cleanup old backups
    cleanup_old_backups
    
    log "Backup process completed successfully!"
    
    # Send success notification
    send_notification "SUCCESS" "✅ Aqwam backup completed successfully at $(date)"
}

# Restore function
restore_backup() {
    local backup_timestamp="$1"
    
    if [[ -z "$backup_timestamp" ]]; then
        error "Backup timestamp is required for restore"
        echo "Usage: $0 restore <timestamp>"
        echo "Available backups:"
        ls -la "$BACKUP_PATH" | grep -E "(db_backup|app_backup|config_backup)" | awk '{print $9}' | cut -d'_' -f2-3 | cut -d'.' -f1 | sort -u
        exit 1
    fi
    
    log "Starting restore process for backup: $backup_timestamp"
    
    # Restore database
    DB_BACKUP_FILE="${BACKUP_PATH}/db_backup_${backup_timestamp}.sql.gz"
    if [[ -f "$DB_BACKUP_FILE" ]]; then
        log "Restoring database..."
        
        source "$DEPLOY_PATH/.env"
        gunzip < "$DB_BACKUP_FILE" | mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
        
        if [[ $? -eq 0 ]]; then
            log "Database restored successfully"
        else
            error "Database restore failed"
            exit 1
        fi
    else
        error "Database backup file not found: $DB_BACKUP_FILE"
        exit 1
    fi
    
    # Restore application
    APP_BACKUP_FILE="${BACKUP_PATH}/app_backup_${backup_timestamp}.tar.gz"
    if [[ -f "$APP_BACKUP_FILE" ]]; then
        log "Restoring application files..."
        
        # Backup current application
        mv "$DEPLOY_PATH" "${DEPLOY_PATH}_backup_$(date +%Y%m%d_%H%M%S)"
        
        # Extract backup
        mkdir -p "$DEPLOY_PATH"
        tar -xzf "$APP_BACKUP_FILE" -C "$DEPLOY_PATH"
        
        # Set permissions
        chown -R www-data:www-data "$DEPLOY_PATH"
        chmod -R 755 "$DEPLOY_PATH"
        chmod -R 775 "$DEPLOY_PATH/storage"
        chmod -R 775 "$DEPLOY_PATH/bootstrap/cache"
        
        # Install dependencies
        cd "$DEPLOY_PATH"
        sudo -u www-data composer install --no-dev --optimize-autoloader
        sudo -u www-data npm install --production
        sudo -u www-data npm run build
        
        # Clear and cache
        sudo -u www-data php artisan config:clear
        sudo -u www-data php artisan route:clear
        sudo -u www-data php artisan view:clear
        sudo -u www-data php artisan cache:clear
        
        log "Application restored successfully"
    else
        error "Application backup file not found: $APP_BACKUP_FILE"
        exit 1
    fi
    
    # Restart services
    log "Restarting services..."
    systemctl restart php8.2-fpm
    systemctl reload nginx
    supervisorctl restart aqwam-worker:*
    
    log "Restore process completed successfully!"
    
    # Send restore notification
    send_notification "RESTORE" "🔄 Aqwam backup restored successfully at $(date)"
}

# List available backups
list_backups() {
    log "Listing available backups..."
    
    echo "Available Backups:"
    echo "=================="
    
    find "$BACKUP_PATH" -name "db_backup_*.sql.gz" -type f -printf "%T@ %p\n" | sort -n | while read timestamp file; do
        backup_date=$(date -d "@${timestamp%.*}" '+%Y-%m-%d %H:%M:%S')
        backup_name=$(basename "$file" | sed 's/db_backup_//' | sed 's/.sql.gz//')
        echo "$backup_name - $backup_date"
    done
}

# Handle script arguments
case "${1:-backup}" in
    "backup")
        perform_backup
        ;;
    "restore")
        restore_backup "$2"
        ;;
    "list")
        list_backups
        ;;
    "cleanup")
        cleanup_old_backups
        ;;
    *)
        echo "Usage: $0 {backup|restore|list|cleanup}"
        echo "  backup   - Perform full backup"
        echo "  restore  - Restore from backup (requires timestamp)"
        echo "  list     - List available backups"
        echo "  cleanup  - Clean up old backups"
        echo ""
        echo "Examples:"
        echo "  $0 backup"
        echo "  $0 restore 20241215_143022"
        echo "  $0 list"
        echo "  $0 cleanup"
        exit 1
        ;;
esac