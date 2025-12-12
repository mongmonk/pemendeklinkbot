#!/bin/bash

# Aqwam URL Shortener - Production Deployment Script
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
RELEASES_PATH="/var/www/releases"
LOG_FILE="/var/log/deploy.log"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RELEASE_PATH="${RELEASES_PATH}/${PROJECT_NAME}_${TIMESTAMP}"

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
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root"
        exit 1
    fi
}

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."
    
    # Check if required commands exist
    for cmd in git php composer npm nginx; do
        if ! command -v $cmd &> /dev/null; then
            error "Command '$cmd' is not installed"
            exit 1
        fi
    done
    
    # Check if directories exist
    if [[ ! -d "$DEPLOY_PATH" ]]; then
        error "Deploy path does not exist: $DEPLOY_PATH"
        exit 1
    fi
    
    # Check if we have write permissions
    if [[ ! -w "$DEPLOY_PATH" ]]; then
        error "No write permission to deploy path: $DEPLOY_PATH"
        exit 1
    fi
    
    log "Prerequisites check passed"
}

# Create backup
create_backup() {
    log "Creating backup..."
    
    # Create backup directories
    sudo mkdir -p "$BACKUP_PATH"
    sudo mkdir -p "$RELEASES_PATH"
    
    # Backup current deployment
    if [[ -d "$DEPLOY_PATH" ]]; then
        sudo cp -r "$DEPLOY_PATH" "${BACKUP_PATH}/backup_${TIMESTAMP}"
        log "Backup created: ${BACKUP_PATH}/backup_${TIMESTAMP}"
    fi
    
    # Backup database
    if command -v mysqldump &> /dev/null; then
        DB_NAME=$(grep DB_DATABASE "$DEPLOY_PATH/.env" | cut -d '=' -f2)
        DB_USER=$(grep DB_USERNAME "$DEPLOY_PATH/.env" | cut -d '=' -f2)
        DB_PASS=$(grep DB_PASSWORD "$DEPLOY_PATH/.env" | cut -d '=' -f2)
        
        if [[ -n "$DB_NAME" && -n "$DB_USER" ]]; then
            mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "${BACKUP_PATH}/db_backup_${TIMESTAMP}.sql.gz"
            log "Database backup created: ${BACKUP_PATH}/db_backup_${TIMESTAMP}.sql.gz"
        fi
    fi
}

# Clone repository
clone_repository() {
    log "Cloning repository..."
    
    # Create release directory
    sudo mkdir -p "$RELEASE_PATH"
    
    # Clone repository
    git clone https://github.com/your-username/aqwam-url-shortener.git "$RELEASE_PATH"
    
    # Set permissions
    sudo chown -R "$DEPLOY_USER:$DEPLOY_USER" "$RELEASE_PATH"
    sudo chmod -R 755 "$RELEASE_PATH"
    
    log "Repository cloned to: $RELEASE_PATH"
}

# Install dependencies
install_dependencies() {
    log "Installing dependencies..."
    
    cd "$RELEASE_PATH"
    
    # Install PHP dependencies
    sudo -u "$DEPLOY_USER" composer install --no-dev --optimize-autoloader --no-interaction
    
    # Install Node dependencies
    sudo -u "$DEPLOY_USER" npm install --production
    
    # Build assets
    sudo -u "$DEPLOY_USER" npm run build
    
    log "Dependencies installed"
}

# Configure environment
configure_environment() {
    log "Configuring environment..."
    
    # Copy environment file from current deployment
    if [[ -f "$DEPLOY_PATH/.env" ]]; then
        sudo cp "$DEPLOY_PATH/.env" "$RELEASE_PATH/.env"
        log "Environment file copied"
    else
        error "Environment file not found in current deployment"
        exit 1
    fi
    
    # Generate application key if not exists
    if ! grep -q "APP_KEY=" "$RELEASE_PATH/.env"; then
        sudo -u "$DEPLOY_USER" php artisan key:generate --force
        log "Application key generated"
    fi
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    cd "$RELEASE_PATH"
    sudo -u "$DEPLOY_USER" php artisan migrate --force --no-interaction
    
    log "Database migrations completed"
}

# Optimize application
optimize_application() {
    log "Optimizing application..."
    
    cd "$RELEASE_PATH"
    
    # Clear all caches
    sudo -u "$DEPLOY_USER" php artisan config:clear
    sudo -u "$DEPLOY_USER" php artisan route:clear
    sudo -u "$DEPLOY_USER" php artisan view:clear
    sudo -u "$DEPLOY_USER" php artisan cache:clear
    
    # Create new caches
    sudo -u "$DEPLOY_USER" php artisan config:cache
    sudo -u "$DEPLOY_USER" php artisan route:cache
    sudo -u "$DEPLOY_USER" php artisan view:cache
    
    # Optimize autoloader
    sudo -u "$DEPLOY_USER" composer dump-autoload --optimize
    
    log "Application optimized"
}

# Setup webhook
setup_webhook() {
    log "Setting up Telegram webhook..."
    
    cd "$RELEASE_PATH"
    sudo -u "$DEPLOY_USER" php artisan telegram:setup-webhook
    
    log "Telegram webhook configured"
}

# Warm up cache
warm_cache() {
    log "Warming up cache..."
    
    cd "$RELEASE_PATH"
    sudo -u "$DEPLOY_USER" php artisan url:warm-cache --limit=100
    
    log "Cache warmed up"
}

# Switch to new release
switch_release() {
    log "Switching to new release..."
    
    # Create symlink
    sudo ln -sfn "$RELEASE_PATH" "${DEPLOY_PATH}_new"
    
    # Atomic switch
    sudo mv "${DEPLOY_PATH}" "${DEPLOY_PATH}_old" 2>/dev/null || true
    sudo mv "${DEPLOY_PATH}_new" "$DEPLOY_PATH"
    
    # Remove old deployment
    if [[ -d "${DEPLOY_PATH}_old" ]]; then
        sudo rm -rf "${DEPLOY_PATH}_old"
        log "Old deployment removed"
    fi
    
    log "Switched to new release"
}

# Restart services
restart_services() {
    log "Restarting services..."
    
    # Restart PHP-FPM
    sudo systemctl restart php8.2-fpm
    
    # Restart Nginx
    sudo systemctl reload nginx
    
    # Restart queue workers
    sudo supervisorctl restart aqwam-worker:*
    
    log "Services restarted"
}

# Health check
health_check() {
    log "Performing health check..."
    
    # Wait for services to start
    sleep 10
    
    # Check if application is responding
    if curl -f -s "https://aqwam.id/health-check.php" > /dev/null; then
        log "Health check passed"
    else
        error "Health check failed"
        rollback
        exit 1
    fi
}

# Rollback function
rollback() {
    log "Rolling back deployment..."
    
    # Switch to backup
    if [[ -d "${BACKUP_PATH}/backup_${TIMESTAMP}" ]]; then
        sudo rm -rf "$DEPLOY_PATH"
        sudo mv "${BACKUP_PATH}/backup_${TIMESTAMP}" "$DEPLOY_PATH"
        
        # Restore database
        if [[ -f "${BACKUP_PATH}/db_backup_${TIMESTAMP}.sql.gz" ]]; then
            DB_NAME=$(grep DB_DATABASE "$DEPLOY_PATH/.env" | cut -d '=' -f2)
            DB_USER=$(grep DB_USERNAME "$DEPLOY_PATH/.env" | cut -d '=' -f2)
            DB_PASS=$(grep DB_PASSWORD "$DEPLOY_PATH/.env" | cut -d '=' -f2)
            
            gunzip < "${BACKUP_PATH}/db_backup_${TIMESTAMP}.sql.gz" | mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
            log "Database restored"
        fi
        
        # Restart services
        restart_services
        
        log "Rollback completed"
    else
        error "Backup not found for rollback"
        exit 1
    fi
}

# Cleanup old releases
cleanup() {
    log "Cleaning up old releases..."
    
    # Keep last 5 releases
    cd "$RELEASES_PATH"
    ls -t | tail -n +6 | xargs -r sudo rm -rf
    
    # Keep last 7 days of backups
    find "$BACKUP_PATH" -name "backup_*" -mtime +7 -exec sudo rm -rf {} \;
    find "$BACKUP_PATH" -name "db_backup_*" -mtime +7 -exec sudo rm -f {} \;
    
    log "Cleanup completed"
}

# Main deployment function
deploy() {
    log "Starting deployment of Aqwam URL Shortener..."
    
    check_root
    check_prerequisites
    
    # Create backup
    create_backup
    
    # Deploy new version
    clone_repository
    install_dependencies
    configure_environment
    run_migrations
    optimize_application
    setup_webhook
    warm_cache
    
    # Switch to new version
    switch_release
    restart_services
    
    # Verify deployment
    health_check
    
    # Cleanup
    cleanup
    
    log "Deployment completed successfully!"
    log "Release path: $RELEASE_PATH"
    log "Backup path: ${BACKUP_PATH}/backup_${TIMESTAMP}"
}

# Handle script arguments
case "${1:-deploy}" in
    "deploy")
        deploy
        ;;
    "rollback")
        rollback
        ;;
    "health")
        health_check
        ;;
    *)
        echo "Usage: $0 {deploy|rollback|health}"
        echo "  deploy   - Deploy new version"
        echo "  rollback - Rollback to previous version"
        echo "  health   - Perform health check"
        exit 1
        ;;
esac