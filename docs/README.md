# Aqwam URL Shortener - Sistem Telegram URL Shortener

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.0-red" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0+-orange" alt="MySQL">
  <img src="https://img.shields.io/badge/Redis-6.0+-green" alt="Redis">
  <img src="https://img.shields.io/badge/Bot-@pemendeklinkbot-blue" alt="Telegram Bot">
</p>

## 📖 Overview

Aqwam URL Shortener adalah sistem pemendek URL yang terintegrasi dengan Telegram Bot, memungkinkan pengguna membuat link pendek dengan mudah melalui Telegram. Sistem ini dilengkapi dengan analytics lengkap, admin dashboard, dan performa tinggi untuk redirect cepat.

## 🚀 Fitur Utama

### Telegram Bot Features
- **Bot Name**: `@pemendeklinkbot`
- **Pembuatan Link**: Kirim URL langsung atau gunakan command `/short`
- **Custom Alias**: Buat link pendek dengan nama kustom
- **Statistik**: Lihat statistik link dengan command `/stats`
- **User Dashboard**: Lihat semua link yang telah dibuat
- **Rate Limiting**: Perlindungan dari spam dan abuse

### Web Features
- **Redirect Cepat**: Response time <100ms untuk cached results
- **Analytics Lengkap**: Tracking lokasi, device, browser, dan referer
- **Preview Page**: Halaman preview dengan statistik detail
- **Admin Dashboard**: Manajemen link dan analytics dengan Filament
- **Security**: Rate limiting, input validation, dan redirect loop prevention

### Performance & Scalability
- **Caching Strategy**: Redis cache untuk performa tinggi
- **Queue System**: Asynchronous processing untuk analytics
- **Database Optimization**: Indexing yang optimal untuk query cepat
- **Negative Caching**: Cache negatif untuk link tidak ditemukan

## 🏗️ Arsitektur Sistem

```
┌─────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Telegram   │    │                 │    │                 │
│    Bot      │◄──►│   Laravel App    │◄──►│  Admin Dashboard│
│             │    │                 │    │    (Filament)   │
└─────────────┘    │                 │    │                 │
                   │                 │    └─────────────────┘
                   │                 │
                   │                 │    ┌─────────────────┐
                   │                 │◄──►│                 │
                   │                 │    │   MySQL Database│
                   │                 │    │                 │
                   │                 │    └─────────────────┘
                   │                 │
                   │                 │    ┌─────────────────┐
                   │                 │◄──►│                 │
                   │                 │    │      Redis      │
                   │                 │    │   (Cache/Queue) │
                   └─────────────────┘    └─────────────────┘
```

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 11
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0+
- **Cache/Queue**: Redis 6.0+
- **Admin Panel**: Filament 4.3

### Libraries & Packages
- **Telegram Bot**: `irazasyed/telegram-bot-sdk`
- **User Agent Detection**: `jenssegers/agent`
- **GeoIP Detection**: `geoip2/geoip2`
- **Redis Client**: `predis/predis`

### Frontend
- **Admin UI**: Filament (Tailwind CSS)
- **Preview Page**: Vanilla HTML/CSS/JavaScript
- **Charts**: Custom JavaScript charts

## 📱 Penggunaan Telegram Bot

### Commands yang Tersedia
```
/start      - Mulai bot dan tampilkan pesan selamat datang
/help       - Tampilkan bantuan dan daftar perintah
/short      - Buat link pendek dengan format: /short [URL] [alias]
/stats      - Lihat statistik link Anda
/mylinks    - Lihat semua link yang Anda buat
/popular    - Lihat link populer
```

### Cara Membuat Link Pendek

#### 1. Random Short Code
```
https://example.com/very/long/url
```
Bot akan merespons dengan:
```
✅ Link berhasil dibuat!

🔗 Short URL: aqwam.id/abc123
🌐 Original URL: https://example.com/very/long/url
```

#### 2. Custom Alias
```
https://google.com search
```
Bot akan merespons dengan:
```
✅ Link berhasil dibuat!

🔗 Short URL: aqwam.id/search
🌐 Original URL: https://google.com
🏷️ Custom Alias: search
```

#### 3. Menggunakan Command
```
/short https://example.com myalias
```

## 🌐 Web Interface

### Redirect URL
- **Endpoint**: `GET /{short_code}`
- **Response**: HTTP 301 redirect ke URL asli
- **Performance**: <100ms untuk cached results

### Preview Page
- **Endpoint**: `GET /preview/{short_code}`
- **Features**: Analytics lengkap dengan charts dan statistik
- **Auto-redirect**: Otomatis redirect setelah 10 detik

### Admin Dashboard
- **URL**: `/admin`
- **Features**: 
  - Manajemen links (CRUD)
  - Analytics dashboard
  - User management
  - Activity logs
  - Real-time statistics

## 📊 Analytics & Tracking

### Data yang Ditrack
- **Geolocation**: Negara dan kota (GeoIP2)
- **Device**: Tipe device, browser, OS
- **Traffic**: Referer domain dan sumber traffic
- **Time**: Timestamp dan hourly/daily patterns

### Statistik yang Tersedia
- Total clicks dan unique clicks
- Clicks per hari/minggu/bulan
- Geographic distribution
- Device dan browser breakdown
- Traffic sources analysis

## 🔧 Installation & Setup

### Requirements
- PHP 8.2+
- MySQL 8.0+ atau MariaDB 10.3+
- Redis 6.0+
- Composer
- Node.js & NPM (untuk development)

### Local Development Setup
```bash
# Clone repository
git clone <repository-url>
cd aqwam

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed

# Setup webhook
php artisan telegram:setup-webhook

# Start development server
php artisan serve
```

## 🚀 Deployment

### Production Environment
- **Domain**: `aqwam.id`
- **Bot Token**: `8552109110:AAHJHMIBm_ai5v0Kti9DqXHTs4kQxqdBKf8`
- **SSL**: Required untuk Telegram webhook

### Server Requirements
- **Web Server**: Nginx atau Apache
- **PHP**: 8.2+ dengan extensions:
  - `php-fpm`
  - `php-mysql`
  - `php-redis`
  - `php-curl`
  - `php-gd`
  - `php-json`
  - `php-mbstring`
  - `php-xml`
- **Database**: MySQL 8.0+
- **Cache**: Redis 6.0+

## 📝 Environment Configuration

### Required Variables
```env
# Application
APP_NAME=AQWAM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aqwam.id

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aqwam_production
DB_USERNAME=aqwam_user
DB_PASSWORD=secure_password

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Telegram
TELEGRAM_BOT_TOKEN=8552109110:xxxxxxxxxxxxxxx
TELEGRAM_WEBHOOK_URL=https://aqwam.id/api/telegram/webhook
TELEGRAM_WEBHOOK_SECRET=your_webhook_secret

# Domain Configuration
PRODUCTION_APP_URL=https://aqwam.id
```

## 🔒 Security Features

### Input Validation
- URL validation dengan regex strict
- Custom alias validation (alphanumeric, hyphen, underscore)
- SQL injection prevention dengan Eloquent ORM
- XSS protection dengan input sanitization

### Rate Limiting
- Telegram Bot: 5 requests/menit per user
- Redirect: 30 requests/menit per IP
- Preview: 10 requests/menit per IP

### Webhook Security
- Secret token validation
- HTTPS requirement untuk production
- Request origin validation

## 📈 Performance Optimization

### Caching Strategy
- **Positive Cache**: Link aktif di-cache 30 hari
- **Negative Cache**: Link tidak ditemukan di-cache 5 menit
- **Cache Warming**: Popular links di-preload
- **Cache Invalidation**: Otomatis saat link berubah

### Database Optimization
- Proper indexing pada short_code dan timestamps
- Query optimization dengan eager loading
- Connection pooling untuk high traffic

### Queue System
- Asynchronous click logging
- Non-blocking analytics processing
- Queue monitoring dan retry mechanism

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/TelegramBotTest.php

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- Telegram Bot functionality
- URL shortening logic
- Redirect performance
- Analytics tracking
- Admin dashboard features

## 📚 Documentation

- [API Documentation](./API_DOCUMENTATION.md)
- [Telegram Bot Documentation](./TELEGRAM_BOT_DOCUMENTATION.md)
- [Redirect Service Documentation](./REDIRECT_SERVICE_DOCUMENTATION.md)
- [Architecture Documentation](./arsitektur-telegram-url-shortener.md)
- [Deployment Guide](./DEPLOYMENT.md)
- [User Guide](./USER_GUIDE.md)
- [Troubleshooting](./TROUBLESHOOTING.md)

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Telegram Bot**: @pemendeklinkbot
- **Admin Dashboard**: https://aqwam.id/admin
- **Issues**: Report via GitHub Issues
- **Email**: support@aqwam.id

---

**Built with ❤️ using Laravel and Telegram Bot API**
