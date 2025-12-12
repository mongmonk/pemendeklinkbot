# Monitoring & Maintenance Guide - Aqwam URL Shortener

## 📖 Overview

Guide lengkap untuk monitoring dan maintenance sistem Aqwam URL Shortener agar tetap berjalan optimal dan dapat mendeteksi masalah sejak dini.

## 🔍 System Monitoring

### 1. Health Check Endpoint

#### Implementation
Health check endpoint sudah tersedia di `/health-check.php`:

```php
<?php
// Health check implementation details
// Returns JSON with system status
{
    "status": "healthy|unhealthy",
    "checks": {
        "database": true|false,
        "redis": true|false,
        "cache": true|false,
        "queue": true|false
    },
    "timestamp": "2024-12-15T10:30:00Z"
}
```

#### Monitoring Setup
```bash
# Setup monitoring cron (every 5 minutes)
*/5 * * * * curl -s https://aqwam.id/health-check.php | jq '.status' | grep -q healthy || echo "Health check failed" | mail -s "Aqwam Health Alert" admin@aqwam.id

# Setup detailed monitoring script
cat > /usr/local/bin/aqwam-monitor.sh << 'EOF'
#!/bin/bash
HEALTH_URL="https://aqwam.id/health-check.php"
LOG_FILE="/var/log/aqwam/monitoring.log"
ALERT_EMAIL="admin@aqwam.id"

RESPONSE=$(curl -s "$HEALTH_URL")
STATUS=$(echo "$RESPONSE" | jq -r '.status')

if [[ "$STATUS" != "healthy" ]]; then
    echo "$(date): Health check failed - $RESPONSE" >> "$LOG_FILE"
    echo "Aqwam system unhealthy: $RESPONSE" | mail -s "Aqwam Health Alert" "$ALERT_EMAIL"
fi
EOF

chmod +x /usr/local/bin/aqwam-monitor.sh

# Add to crontab
echo "*/5 * * * * /usr/local/bin/aqwam-monitor.sh" | crontab -
```

### 2. Application Performance Monitoring

#### Key Metrics to Monitor
- **Response Time**: Target <100ms untuk cached redirects
- **Error Rate**: Target <1% dari total requests
- **Uptime**: Target 99.9%
- **Queue Processing**: Queue size <100 items
- **Cache Hit Ratio**: Target >90%

#### Monitoring Tools
```bash
# Setup log monitoring
tail -f /var/log/nginx/aqwam.id.access.log | grep -E "(HTTP 5[0-9][0-9]|HTTP 4[0-9][0-9])"

# Monitor slow queries
tail -f /var/log/mysql/slow.log

# Monitor Redis performance
redis-cli --latency-history -i 1

# Monitor queue size
php artisan queue:monitor
```

#### Performance Monitoring Script
```bash
cat > /usr/local/bin/aqwam-performance.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/performance.log"
ALERT_THRESHOLD=500  # Response time in ms

# Check average response time
AVG_RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' https://aqwam.id/health-check.php | awk '{printf "%.0f", $1*1000}')

if (( $(echo "$AVG_RESPONSE_TIME > $ALERT_THRESHOLD" | bc -l) )); then
    echo "$(date): High response time detected: ${AVG_RESPONSE_TIME}ms" >> "$LOG_FILE"
fi

# Check cache hit ratio
CACHE_STATS=$(php artisan cache:stats)
CACHE_HIT_RATIO=$(echo "$CACHE_STATS" | jq -r '.hit_ratio')

if (( $(echo "$CACHE_HIT_RATIO < 90" | bc -l) )); then
    echo "$(date): Low cache hit ratio: ${CACHE_HIT_RATIO}%" >> "$LOG_FILE"
fi
EOF

chmod +x /usr/local/bin/aqwam-performance.sh

# Add to crontab (every hour)
echo "0 * * * * /usr/local/bin/aqwam-performance.sh" | crontab -
```

### 3. Resource Monitoring

#### System Resources
```bash
# Monitor CPU usage
top -b -n1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1

# Monitor Memory usage
free -m | awk 'NR==2{printf "%.1f%%", $3*100/$2}'

# Monitor Disk usage
df -h / | awk 'NR==2{print $5}'

# Monitor Network I/O
iftop -t -s 1 -n 1
```

#### Resource Monitoring Script
```bash
cat > /usr/local/bin/aqwam-resources.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/resources.log"
CPU_THRESHOLD=80
MEMORY_THRESHOLD=80
DISK_THRESHOLD=90

# Get current metrics
CPU_USAGE=$(top -b -n1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
MEMORY_USAGE=$(free -m | awk 'NR==2{printf "%.0f", $3*100/$2}')
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | cut -d'%' -f1)

# Check thresholds and log
if (( $(echo "$CPU_USAGE > $CPU_THRESHOLD" | bc -l) )); then
    echo "$(date): High CPU usage: ${CPU_USAGE}%" >> "$LOG_FILE"
fi

if (( $(echo "$MEMORY_USAGE > $MEMORY_THRESHOLD" | bc -l) )); then
    echo "$(date): High memory usage: ${MEMORY_USAGE}%" >> "$LOG_FILE"
fi

if (( $(echo "$DISK_USAGE > $DISK_THRESHOLD" | bc -l) )); then
    echo "$(date): High disk usage: ${DISK_USAGE}%" >> "$LOG_FILE"
fi
EOF

chmod +x /usr/local/bin/aqwam-resources.sh

# Add to crontab (every 10 minutes)
echo "*/10 * * * * /usr/local/bin/aqwam-resources.sh" | crontab -
```

## 📊 Application Monitoring

### 1. Laravel Telescope (Optional)

#### Installation
```bash
# Install Telescope
composer require laravel/telescope

# Publish configuration
php artisan vendor:publish --tag=telescope-config

# Run migrations
php artisan migrate

# Add to config/app.php providers
'App\Providers\TelescopeServiceProvider',
```

#### Monitoring Dashboard
Access: `https://aqwam.id/telescope`

Features:
- Request monitoring
- Command monitoring
- Schedule monitoring
- Exception tracking
- Query performance

### 2. Custom Monitoring Dashboard

#### Real-time Statistics
```php
// Create custom dashboard endpoint
Route::get('/admin/dashboard/stats', function () {
    return [
        'total_links' => Link::count(),
        'active_links' => Link::where('disabled', false)->count(),
        'total_clicks' => Link::sum('clicks'),
        'today_clicks' => ClickLog::whereDate('timestamp', today())->count(),
        'queue_size' => Queue::size(),
        'cache_hit_ratio' => Cache::getHitRatio(),
        'active_users' => Cache::get('active_users_count', 0),
        'server_load' => sys_getloadavg()[0],
        'memory_usage' => memory_get_usage(true),
    ];
});
```

#### Analytics Monitoring
```bash
# Setup daily analytics report
cat > /usr/local/bin/aqwam-analytics.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/analytics.log"
REPORT_EMAIL="admin@aqwam.id"

# Get daily statistics
TOTAL_LINKS=$(php artisan tinker --execute="echo App\Models\Link::count();")
ACTIVE_LINKS=$(php artisan tinker --execute="echo App\Models\Link::where('disabled', false)->count();")
TOTAL_CLICKS=$(php artisan tinker --execute="echo App\Models\Link::sum('clicks');")
TODAY_CLICKS=$(php artisan tinker --execute="echo App\Models\ClickLog::whereDate('timestamp', today())->count();")

# Generate report
REPORT="Daily Analytics Report - $(date)
================================
Total Links: $TOTAL_LINKS
Active Links: $ACTIVE_LINKS
Total Clicks: $TOTAL_CLICKS
Today's Clicks: $TODAY_CLICKS

Generated by Aqwam Monitoring System"

echo "$(date): $REPORT" >> "$LOG_FILE"
echo "$REPORT" | mail -s "Aqwam Daily Analytics" "$REPORT_EMAIL"
EOF

chmod +x /usr/local/bin/aqwam-analytics.sh

# Add to crontab (daily at 9 AM)
echo "0 9 * * * /usr/local/bin/aqwam-analytics.sh" | crontab -
```

## 🔧 Maintenance Procedures

### 1. Regular Maintenance Tasks

#### Daily Tasks
```bash
# Create daily maintenance script
cat > /usr/local/bin/aqwam-daily-maintenance.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/maintenance.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Clear expired cache
log "Clearing expired cache entries"
php artisan cache:clear

# Optimize database
log "Optimizing database"
mysql -u root -p -e "OPTIMIZE TABLE links, click_logs, admins, activity_logs, jobs, failed_jobs;"

# Check queue health
log "Checking queue workers"
supervisorctl status aqwam-worker:*

# Warm up cache
log "Warming up cache"
php artisan url:warm-cache --limit=100

# Generate daily report
log "Generating daily report"
/usr/local/bin/aqwam-analytics.sh

log "Daily maintenance completed"
EOF

chmod +x /usr/local/bin/aqwam-daily-maintenance.sh

# Add to crontab (daily at 2 AM)
echo "0 2 * * * /usr/local/bin/aqwam-daily-maintenance.sh" | crontab -
```

#### Weekly Tasks
```bash
# Create weekly maintenance script
cat > /usr/local/bin/aqwam-weekly-maintenance.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/maintenance.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Update GeoIP database
log "Updating GeoIP database"
cd /var/www/aqwam
wget -q http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz
gunzip GeoLite2-City.mmdb.gz -c > storage/app/geoip/GeoLite2-City.mmdb
rm GeoLite2-City.mmdb.gz

# Clean up old logs
log "Cleaning up old logs"
find /var/log/aqwam -name "*.log" -mtime +30 -delete
find /var/www/aqwam/storage/logs -name "*.log" -mtime +7 -delete

# Database maintenance
log "Running database maintenance"
mysql -u root -p -e "
ANALYZE TABLE links, click_logs, admins, activity_logs, jobs, failed_jobs;
CHECK TABLE links, click_logs, admins, activity_logs, jobs, failed_jobs;
"

# Security audit
log "Running security audit"
fail2ban-client status

log "Weekly maintenance completed"
EOF

chmod +x /usr/local/bin/aqwam-weekly-maintenance.sh

# Add to crontab (Sunday at 3 AM)
echo "0 3 * * 0 /usr/local/bin/aqwam-weekly-maintenance.sh" | crontab -
```

#### Monthly Tasks
```bash
# Create monthly maintenance script
cat > /usr/local/bin/aqwam-monthly-maintenance.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/maintenance.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Full system backup
log "Creating full system backup"
/usr/local/bin/backup.sh

# Update dependencies
log "Updating dependencies"
cd /var/www/aqwam
composer update --no-dev
npm update

# Clear and rebuild cache
log "Rebuilding cache"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Performance analysis
log "Running performance analysis"
php artisan tinker --execute="
\$slowLinks = App\Models\Link::where('clicks', '<', 10)->get();
echo 'Links with low clicks: ' . \$slowLinks->count() . PHP_EOL;
"

log "Monthly maintenance completed"
EOF

chmod +x /usr/local/bin/aqwam-monthly-maintenance.sh

# Add to crontab (1st of month at 4 AM)
echo "0 4 1 * * /usr/local/bin/aqwam-monthly-maintenance.sh" | crontab -
```

### 2. Log Management

#### Log Rotation Setup
```bash
# Create logrotate configuration
cat > /etc/logrotate.d/aqwam << 'EOF'
/var/log/aqwam/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}

/var/www/aqwam/storage/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
EOF

# Test logrotate
logrotate -d /etc/logrotate.d/aqwam
```

#### Log Analysis
```bash
# Create log analysis script
cat > /usr/local/bin/aqwam-log-analysis.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/analysis.log"
ACCESS_LOG="/var/log/nginx/aqwam.id.access.log"
ERROR_LOG="/var/log/nginx/aqwam.id.error.log"
LARAVEL_LOG="/var/www/aqwam/storage/logs/laravel.log"

analyze() {
    echo "$(date): Analyzing logs for $1" >> "$LOG_FILE"
    
    case "$1" in
        "errors")
            # Analyze error patterns
            echo "Top 10 errors in last 24 hours:" >> "$LOG_FILE"
            tail -n 1000 "$ERROR_LOG" | grep -E "(HTTP 5[0-9][0-9]|HTTP 4[0-9][0-9])" | awk '{print $7}' | sort | uniq -c | sort -nr | head -10 >> "$LOG_FILE"
            ;;
        "slow")
            # Analyze slow requests
            echo "Top 10 slow requests:" >> "$LOG_FILE"
            tail -n 1000 "$ACCESS_LOG" | awk '$NF > 1.0 {print $NF, $7}' | sort -nr | head -10 >> "$LOG_FILE"
            ;;
        "traffic")
            # Analyze traffic patterns
            echo "Hourly traffic distribution:" >> "$LOG_FILE"
            tail -n 10000 "$ACCESS_LOG" | awk '{print $4}' | cut -d: -f2 | cut -d: -f1 | sort | uniq -c >> "$LOG_FILE"
            ;;
        "bots")
            # Analyze bot traffic
            echo "Top 10 bots:" >> "$LOG_FILE"
            tail -n 1000 "$ACCESS_LOG" | grep -i -E "(bot|crawler|spider)" | awk '{print $12}' | sort | uniq -c | sort -nr | head -10 >> "$LOG_FILE"
            ;;
    esac
}

# Run analysis
analyze "errors"
analyze "slow"
analyze "traffic"
analyze "bots"
EOF

chmod +x /usr/local/bin/aqwam-log-analysis.sh

# Add to crontab (daily at 6 AM)
echo "0 6 * * * /usr/local/bin/aqwam-log-analysis.sh" | crontab -
```

## 🚨 Alerting System

### 1. Multi-Channel Alerting

#### Email Alerts
```bash
# Setup email configuration
cat > /etc/aqwam/alerts.conf << 'EOF'
# Alert Configuration
ALERT_EMAIL="admin@aqwam.id"
SMTP_SERVER="smtp.gmail.com"
SMTP_PORT="587"
SMTP_USER="alerts@aqwam.id"
SMTP_PASS="your_app_password"

# Thresholds
CPU_THRESHOLD=80
MEMORY_THRESHOLD=80
DISK_THRESHOLD=90
RESPONSE_TIME_THRESHOLD=500
ERROR_RATE_THRESHOLD=5
EOF
```

#### Telegram Alerts
```bash
# Setup Telegram bot for alerts
cat > /usr/local/bin/aqwam-telegram-alert.sh << 'EOF'
#!/bin/bash
BOT_TOKEN="your_telegram_bot_token"
CHAT_ID="your_chat_id"
MESSAGE="$1"

curl -s -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
    -d "chat_id=${CHAT_ID}" \
    -d "text=${MESSAGE}" \
    -d "parse_mode=HTML"
EOF

chmod +x /usr/local/bin/aqwam-telegram-alert.sh
```

#### Slack Integration (Optional)
```bash
# Setup Slack webhook
cat > /usr/local/bin/aqwam-slack-alert.sh << 'EOF'
#!/bin/bash
WEBHOOK_URL="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
MESSAGE="$1"

curl -X POST -H 'Content-type: application/json' \
    --data "{\"text\":\"${MESSAGE}\"}" \
    "$WEBHOOK_URL"
EOF

chmod +x /usr/local/bin/aqwam-slack-alert.sh
```

### 2. Alert Conditions

#### Critical Alerts
- **System Down**: Health check fails for 3 consecutive checks
- **High Error Rate**: >5% error rate in 5 minutes
- **Database Down**: Database connection fails
- **Queue Stuck**: Queue size >1000 for 10 minutes
- **Disk Full**: Disk usage >95%

#### Warning Alerts
- **High CPU**: CPU usage >80% for 10 minutes
- **High Memory**: Memory usage >80% for 10 minutes
- **Slow Response**: Response time >500ms for 5 minutes
- **Low Cache Hit**: Cache hit ratio <85%

## 📈 Performance Optimization

### 1. Database Optimization

#### Query Optimization
```sql
-- Create indexes for better performance
CREATE INDEX idx_links_short_code_active ON links(short_code, disabled);
CREATE INDEX idx_click_logs_timestamp ON click_logs(timestamp);
CREATE INDEX idx_click_logs_short_code_timestamp ON click_logs(short_code, timestamp);

-- Optimize slow queries
EXPLAIN SELECT * FROM links WHERE short_code = 'abc123' AND disabled = 0;
EXPLAIN SELECT COUNT(*) FROM click_logs WHERE short_code = 'abc123' AND timestamp > NOW() - INTERVAL 7 DAY;
```

#### Database Maintenance
```bash
# Create optimization script
cat > /usr/local/bin/aqwam-db-optimization.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/database.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Analyze table statistics
log "Analyzing tables"
mysql -u root -p -e "ANALYZE TABLE links, click_logs, admins, activity_logs;"

# Optimize tables
log "Optimizing tables"
mysql -u root -p -e "OPTIMIZE TABLE links, click_logs, admins, activity_logs;"

# Check table fragmentation
log "Checking fragmentation"
mysql -u root -p -e "
SELECT table_name, ROUND(data_free/1024/1024, 2) AS data_free_mb 
FROM information_schema.tables 
WHERE table_schema = 'aqwam_production' AND data_free > 0;
" >> "$LOG_FILE"

# Update statistics
log "Updating statistics"
mysql -u root -p -e "
UPDATE links SET clicks = (
    SELECT COUNT(*) FROM click_logs 
    WHERE click_logs.short_code = links.short_code
) WHERE clicks != (
    SELECT COUNT(*) FROM click_logs 
    WHERE click_logs.short_code = links.short_code
);
"

log "Database optimization completed"
EOF

chmod +x /usr/local/bin/aqwam-db-optimization.sh

# Add to crontab (weekly on Sunday at 2 AM)
echo "0 2 * * 0 /usr/local/bin/aqwam-db-optimization.sh" | crontab -
```

### 2. Cache Optimization

#### Cache Strategy
```bash
# Create cache optimization script
cat > /usr/local/bin/aqwam-cache-optimization.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/cache.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Warm up popular links
log "Warming up cache"
php artisan url:warm-cache --limit=200

# Pre-cache analytics data
log "Pre-caching analytics"
php artisan tinker --execute="
\$popularLinks = App\Models\Link::orderBy('clicks', 'desc')->limit(50)->get();
foreach(\$popularLinks as \$link) {
    Cache::put('analytics_' . \$link->short_code, \$link->getAnalyticsData(), 3600);
}
"

# Cache hit ratio optimization
log "Optimizing cache hit ratio"
php artisan cache:stats | jq '.hit_ratio'

log "Cache optimization completed"
EOF

chmod +x /usr/local/bin/aqwam-cache-optimization.sh

# Add to crontab (every 6 hours)
echo "0 */6 * * * /usr/local/bin/aqwam-cache-optimization.sh" | crontab -
```

## 🔒 Security Monitoring

### 1. Intrusion Detection

#### Fail2Ban Configuration
```bash
# Create custom jail for Aqwam
cat > /etc/fail2ban/jail.d/aqwam.conf << 'EOF'
[aqwam-web]
enabled = true
port = http,https
filter = aqwam-web
logpath = /var/log/nginx/aqwam.id.error.log
maxretry = 5
findtime = 600
bantime = 3600

[aqwam-api]
enabled = true
port = http,https
filter = aqwam-api
logpath = /var/log/nginx/aqwam.id.access.log
maxretry = 10
findtime = 300
bantime = 1800
EOF

# Create filter for web attacks
cat > /etc/fail2ban/filter.d/aqwam-web.conf << 'EOF'
[Definition]
failregex = ^<HOST> -.*"(GET|POST).*HTTP.*" (400|401|403|404|500).*$
ignoreregex =
EOF

# Create filter for API abuse
cat > /etc/fail2ban/filter.d/aqwam-api.conf << 'EOF'
[Definition]
failregex = ^<HOST> -.*"POST /api/telegram/webhook".*" 429.*$
ignoreregex =
EOF

# Restart Fail2Ban
systemctl restart fail2ban
```

#### Security Audit Script
```bash
# Create security audit script
cat > /usr/local/bin/aqwam-security-audit.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/security.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Check for suspicious activity
log "Checking for suspicious activity"

# Check for multiple failed logins
FAILED_LOGINS=$(grep "authentication failed" /var/www/aqwam/storage/logs/laravel.log | wc -l)
if [[ $FAILED_LOGINS -gt 10 ]]; then
    log "High number of failed logins detected: $FAILED_LOGINS"
fi

# Check for unusual traffic patterns
UNUSUAL_IPS=$(tail -n 1000 /var/log/nginx/aqwam.id.access.log | awk '{print $1}' | sort | uniq -c | awk '$1 > 100 {print $2}')
if [[ -n "$UNUSUAL_IPS" ]]; then
    log "Unusual traffic from IP: $UNUSUAL_IPS"
fi

# Check file integrity
log "Checking file integrity"
find /var/www/aqwam -type f -name "*.php" -exec md5sum {} \; > /tmp/current_hashes.md5
if [[ -f /var/www/aqwam/baseline_hashes.md5 ]]; then
    DIFF=$(diff /var/www/aqwam/baseline_hashes.md5 /tmp/current_hashes.md5)
    if [[ -n "$DIFF" ]]; then
        log "File integrity check failed: $DIFF"
    fi
fi
mv /tmp/current_hashes.md5 /var/www/aqwam/baseline_hashes.md5

log "Security audit completed"
EOF

chmod +x /usr/local/bin/aqwam-security-audit.sh

# Add to crontab (daily at 5 AM)
echo "0 5 * * * /usr/local/bin/aqwam-security-audit.sh" | crontab -
```

## 📋 Monitoring Checklist

### Daily Checklist
- [ ] Health checks passing
- [ ] Error rate <1%
- [ ] Response time <100ms
- [ ] Queue processing normally
- [ ] Cache hit ratio >90%
- [ ] Disk usage <80%
- [ ] Memory usage <80%
- [ ] CPU usage <80%
- [ ] Backups completed successfully
- [ ] No security alerts

### Weekly Checklist
- [ ] Review performance metrics
- [ ] Analyze error patterns
- [ ] Check security logs
- [ ] Update GeoIP database
- [ ] Optimize database
- [ ] Clean up old logs
- [ ] Review backup retention
- [ ] Test restore procedures

### Monthly Checklist
- [ ] Full system backup
- [ ] Update dependencies
- [ ] Security audit
- [ ] Performance review
- [ ] Capacity planning
- [ ] Documentation update
- [ ] Disaster recovery test

## 🆘 Emergency Procedures

### 1. System Down

#### Immediate Actions
1. Check health endpoint: `curl https://aqwam.id/health-check.php`
2. Check service status: `systemctl status nginx php8.2-fpm mysql redis-server`
3. Check logs: `tail -f /var/log/nginx/aqwam.id.error.log`
4. Restart services if needed

#### Recovery Steps
```bash
# Quick recovery script
cat > /usr/local/bin/aqwam-emergency-recovery.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/emergency.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

log "Starting emergency recovery"

# Check and restart services
if ! systemctl is-active --quiet nginx; then
    log "Restarting nginx"
    systemctl restart nginx
fi

if ! systemctl is-active --quiet php8.2-fpm; then
    log "Restarting PHP-FPM"
    systemctl restart php8.2-fpm
fi

if ! systemctl is-active --quiet mysql; then
    log "Restarting MySQL"
    systemctl restart mysql
fi

if ! systemctl is-active --quiet redis-server; then
    log "Restarting Redis"
    systemctl restart redis-server
fi

# Restart queue workers
log "Restarting queue workers"
supervisorctl restart aqwam-worker:*

# Clear cache
log "Clearing cache"
php artisan cache:clear

# Warm up cache
log "Warming up cache"
php artisan url:warm-cache --limit=50

log "Emergency recovery completed"
EOF

chmod +x /usr/local/bin/aqwam-emergency-recovery.sh
```

### 2. Data Corruption

#### Detection
- Database inconsistencies
- Missing cache entries
- Incorrect analytics data

#### Recovery
```bash
# Data recovery script
cat > /usr/local/bin/aqwam-data-recovery.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/aqwam/recovery.log"

log() {
    echo "$(date): $1" >> "$LOG_FILE"
}

# Restore from latest backup
log "Starting data recovery from backup"
LATEST_BACKUP=$(ls -t /var/backups/aqwam/db_backup_*.sql.gz | head -1)
gunzip < "$LATEST_BACKUP" | mysql -u root -p aqwam_production

# Recalculate statistics
log "Recalculating statistics"
php artisan tinker --execute="
App\Models\Link::chunk(100, function(\$links) {
    foreach(\$links as \$link) {
        \$link->clicks = \$link->clickLogs()->count();
        \$link->save();
    }
});
"

# Rebuild cache
log "Rebuilding cache"
php artisan cache:clear
php artisan url:warm-cache --limit=100

log "Data recovery completed"
EOF

chmod +x /usr/local/bin/aqwam-data-recovery.sh
```

---

*Monitoring dan maintenance guide ini akan diperbarui secara berkala sesuai dengan perkembangan sistem.*