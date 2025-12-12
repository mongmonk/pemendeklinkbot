# Dokumentasi Layanan Redirect URL Aqwam

## Overview

Layanan redirect URL Aqwam adalah sistem pemendek link yang dioptimalkan untuk performa tinggi dengan fitur analytics lengkap. Sistem ini dirancang untuk memberikan respons cepat (<100ms untuk cached results) dengan monitoring dan keamanan yang baik.

## Fitur Utama

### 1. Redirect Cepat dengan Caching
- **Cache Strategy**: Menggunakan Laravel Cache dengan durasi 30 hari untuk link aktif
- **Negative Caching**: Cache negatif selama 5 menit untuk link tidak ditemukan
- **Cache Keys**: Format `short_url:{short_code}` untuk konsistensi
- **Cache Invalidation**: Otomatis saat link dinonaktifkan/dihapus

### 2. Analytics Lengkap
- **Device Detection**: Browser, OS, device type menggunakan jenssegers/agent
- **Geolocation**: Deteksi negara dan kota menggunakan GeoIP2
- **Referer Tracking**: Mencatat sumber traffic
- **Click Logging**: Asynchronous logging untuk tidak mengganggu performa redirect

### 3. Keamanan
- **Rate Limiting**: 
  - Redirect: 30 requests per menit per IP
  - Preview: 10 requests per menit per IP
- **Input Validation**: Validasi format short code (1-15 karakter, alphanumeric + underscore/hyphen)
- **Redirect Loop Prevention**: Deteksi dan pencegahan redirect loop
- **URL Validation**: Validasi URL untuk mencegah malicious links

### 4. Error Handling
- **404 Page**: Custom error page untuk link tidak ditemukan
- **410 Page**: Custom error page untuk link dinonaktifkan
- **429 Page**: Custom error page dengan countdown untuk rate limit
- **Graceful Degradation**: Redirect tetap berfungsi meskipun logging gagal

## Arsitektur

### Redirect Flow
```
User Request → Rate Limit Check → Short Code Validation → Cache Check
                ↓                                    ↓
            Return Error                            Cache Hit?
                ↓                                    ↓
            404/410/429                        ┌─────┐
                                                │ Yes │
                                                ↓    ↓
                                        Log Click Async  Return 301
                                                ↓    ↓
                                        Queue Job      User Redirect
```

### Cache Strategy
- **Positive Cache**: Link aktif di-cache selama 30 hari
- **Negative Cache**: Link tidak ditemukan di-cache selama 5 menit
- **Cache Warming**: Populer links di-preload ke cache
- **Cache Invalidation**: Otomatis saat ada perubahan status link

### Analytics Pipeline
```
Click Event → Collect Data → Queue Job → Database Storage → Reports
     ↓              ↓           ↓           ↓            ↓
Response <100ms  IP, UA,     Async      ClickLogs    Preview
                  GeoIP,      Processing  Table       Dashboard
                  Referer
```

## API Endpoints

### 1. Redirect Endpoint
- **URL**: `GET /{shortCode}`
- **Response**: HTTP 301 redirect ke URL asli
- **Rate Limit**: 30 requests/menit per IP
- **Cache**: 30 hari untuk link aktif, 5 menit untuk tidak ditemukan

### 2. Preview Endpoint
- **URL**: `GET /preview/{shortCode}`
- **Response**: HTML page dengan statistik lengkap
- **Rate Limit**: 10 requests/menit per IP
- **Features**: Analytics charts, recent clicks, device breakdown

## Performance Metrics

### Target Performance
- **Redirect Response Time**: <100ms (cached), <500ms (database)
- **Cache Hit Ratio**: >90%
- **Uptime**: 99.9%
- **Concurrent Users**: 1000+

### Monitoring
- **Response Time Logging**: Setiap request dicatat dengan waktu eksekusi
- **Cache Hit Monitoring**: Rasio cache hit/miss untuk optimasi
- **Error Rate Tracking**: Monitoring error rate dan jenis error
- **Rate Limit Tracking**: IP yang melebihi batas rate limit

## Security Features

### 1. Input Validation
```php
// Short code validation
- Length: 1-15 characters
- Allowed chars: a-z, A-Z, 0-9, -, _
- Regex: /^[a-zA-Z0-9_-]+$/
```

### 2. Rate Limiting
```php
// Redirect rate limit
RateLimiter::attempt('redirect:' . $ip, 30, callback, 60);

// Preview rate limit  
RateLimiter::attempt('preview:' . $ip, 10, callback, 60);
```

### 3. URL Validation
```php
// Security checks
- Scheme: http, https only
- No localhost/private IPs
- Filter malicious URLs
```

## Database Schema

### Links Table
```sql
- id (primary)
- short_code (string, unique, indexed)
- long_url (text)
- is_custom (boolean)
- telegram_user_id (bigint, indexed)
- clicks (integer, default 0)
- disabled (boolean, default false, indexed)
- disable_reason (text, nullable)
- disabled_at (timestamp, nullable)
- created_at, updated_at
```

### Click Logs Table
```sql
- id (primary)
- short_code (string, indexed, foreign key)
- ip_address (string, indexed)
- user_agent (text)
- referer (string)
- country (string, nullable)
- city (string, nullable)
- device_type (string, nullable)
- browser (string, nullable)
- browser_version (string, nullable)
- os (string, nullable)
- os_version (string, nullable)
- timestamp (timestamp, indexed)
```

## Cache Configuration

### Recommended Settings
```php
// config/cache.php
'default' => 'redis', // or 'memcached' for production

// Redis configuration
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'lock_connection' => 'default',
],
```

## Deployment Considerations

### 1. Production Setup
- Use Redis/Memcached for cache
- Configure GeoIP database
- Set up monitoring and alerting
- Configure rate limiting storage

### 2. Performance Optimization
- Enable OPcache for PHP
- Use CDN for static assets
- Configure database connection pooling
- Set up cron jobs for cache warming

### 3. Security Hardening
- Configure firewall rules
- Set up SSL certificates
- Implement IP whitelisting for admin
- Regular security updates

## Troubleshooting

### Common Issues
1. **Slow Redirects**: Check cache configuration
2. **High Error Rate**: Verify database connections
3. **Rate Limit Issues**: Check Redis/Memcached
4. **Analytics Not Working**: Verify queue system

### Debug Commands
```bash
# Check cache status
php artisan cache:clear

# Warm up cache
php artisan url:warm-cache

# Check queue processing
php artisan queue:work

# View logs
tail -f storage/logs/laravel.log
```

## Future Enhancements

### Planned Features
1. **Advanced Analytics**: Real-time dashboard
2. **A/B Testing**: Multiple URLs per short code
3. **QR Code Generation**: Automatic QR code creation
4. **API Rate Limiting**: Tiered rate limiting
5. **Geographic Redirect**: Different URLs by location

### Scalability Plans
1. **Horizontal Scaling**: Load balancer + multiple app servers
2. **Database Sharding**: Partition by short code ranges
3. **CDN Integration**: Edge caching for redirects
4. **Microservices**: Separate analytics service

## Support

### Contact Information
- **Telegram Bot**: @pemendeklinkbot
- **Documentation**: Available in repository
- **Issues**: Report via GitHub issues
- **Emergency**: Contact system administrator

---

*Dokumentasi ini akan diperbarui secara berkala sesuai dengan perkembangan sistem.*