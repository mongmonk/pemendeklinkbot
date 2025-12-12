# Dokumentasi Telegram Bot - Aqwam URL Shortener

## Overview

Bot Telegram untuk URL Shortener Aqwam dengan nama `pemendeklinkbot` yang memungkinkan pengguna membuat link pendek dengan mudah melalui Telegram.

## Fitur

### Command Dasar

1. **`/start`** - Menampilkan pesan selamat datang dan instruksi penggunaan
2. **`/help`** - Menampilkan daftar semua command yang tersedia
3. **`/short [URL]`** - Membuat short link random dari URL yang diberikan
4. **`/short [URL] [custom_alias]`** - Membuat short link dengan custom alias
5. **`/stats`** - Menampilkan statistik semua link yang Anda buat
6. **`/stats [short_code]`** - Menampilkan statistik link tertentu
7. **`/mylinks`** - Menampilkan semua link yang Anda buat
8. **`/popular`** - Menampilkan link populer

### Cara Penggunaan

#### Membuat Link Pendek Random
```
/short https://example.com/very/long/url
```
Bot akan merespons dengan:
```
✅ Link berhasil dibuat!

🔗 Short URL: aqwam.id/abc123
🌐 Original URL: https://example.com/very/long/url

💡 Tips: Anda dapat melihat statistik link dengan /stats abc123
```

#### Membuat Link dengan Custom Alias
```
/short https://google.com search
```
Bot akan merespons dengan:
```
✅ Link berhasil dibuat!

🔗 Short URL: aqwam.id/search
🌐 Original URL: https://google.com
🏷️ Custom Alias: search

💡 Tips: Anda dapat melihat statistik link dengan /stats search
```

#### Input Langsung URL
Anda juga bisa langsung mengirim URL tanpa command:
```
https://example.com
```
Atau dengan custom alias:
```
https://example.com myalias
```

#### Melihat Statistik
Statistik semua link Anda:
```
/stats
```

Statistik link tertentu:
```
/stats abc123
```

Bot akan merespons dengan:
```
📊 Statistik Link: `abc123`

🔗 Short URL: aqwam.id/abc123
🌐 Original URL: https://example.com
👁️ Total Klik: 25
👥 Klik Unik: 20
📅 Klik Hari Ini: 5
📈 Status: Aktif
```

## Validasi dan Error Handling

### Validasi URL
- URL harus dimulai dengan `http://` atau `https://`
- URL tidak boleh mengarah ke localhost atau IP private
- Format URL harus valid

### Validasi Custom Alias
- Maksimal 15 karakter
- Hanya boleh mengandung huruf, angka, hyphen (-), dan underscore (_)
- Tidak boleh sudah digunakan oleh link lain

### Error Messages
- **URL tidak valid**: "❌ URL tidak valid. Pastikan URL dimulai dengan http:// atau https://"
- **Custom alias tidak valid**: "❌ Custom alias tidak valid. Hanya huruf, angka, hyphen, dan underscore yang diperbolehkan (maksimal 15 karakter)"
- **Custom alias sudah digunakan**: "❌ Kode short sudah digunakan"
- **Format perintah salah**: "❌ Format perintah salah. Gunakan: `/short [URL]` atau `/short [URL] [custom_alias]`"

## Rate Limiting

Bot memiliki rate limiting untuk mencegah spam:
- Maksimal 5 permintaan per menit per user
- Jika melebihi batas, bot akan merespons: "⚠️ Terlalu banyak permintaan! Silakan tunggu sebentar sebelum mencoba lagi."

## Setup Webhook

### Menggunakan Command Artisan

1. **Setup webhook dengan URL default**:
```bash
php artisan telegram:setup-webhook
```

2. **Setup webhook dengan URL kustom**:
```bash
php artisan telegram:setup-webhook --url=https://your-domain.com/api/telegram/webhook
```

3. **Menghapus webhook**:
```bash
php artisan telegram:setup-webhook --unset
```

### Menggunakan API Endpoint

1. **Setup webhook**:
```
GET /api/telegram/set-webhook?url=https://your-domain.com/api/telegram/webhook
```

2. **Melihat info webhook**:
```
GET /api/telegram/webhook-info
```

## Konfigurasi

### Environment Variables
Tambahkan ke file `.env`:
```env
TELEGRAM_BOT_TOKEN=8552109110:AAHJHMIBm_ai5v0Kti9DqXHTs4kQxqdBKf8
TELEGRAM_WEBHOOK_URL=https://your-domain.com/api/telegram/webhook
TELEGRAM_WEBHOOK_SECRET=your_webhook_secret
TELEGRAM_RATE_LIMIT_ENABLED=true
TELEGRAM_RATE_LIMIT_ATTEMPTS=5
TELEGRAM_RATE_LIMIT_MINUTES=1
```

### Konfigurasi Domain
Pastikan konfigurasi domain di `config/domain.php`:
```php
return [
    'production' => 'https://aqwam.id',
    'local' => 'http://localhost:8000',
];
```

## Testing

### Menjalankan Tests
```bash
php artisan test tests/Feature/TelegramBotTest.php
```

### Coverage Testing
```bash
php artisan test --coverage tests/Feature/TelegramBotTest.php
```

## Security

1. **Webhook Secret**: Gunakan webhook secret untuk validasi request
2. **URL Validation**: Bot memvalidasi URL untuk mencegah link berbahaya
3. **Rate Limiting**: Mencegah spam dan abuse
4. **Input Validation**: Semua input user divalidasi sebelum diproses

## Troubleshooting

### Webhook Tidak Berfungsi
1. Pastikan URL webhook dapat diakses publik
2. Periksa konfigurasi SSL (HTTPS required untuk production)
3. Verifikasi bot token benar
4. Cek log Laravel untuk error

### Bot Tidak Merespons
1. Periksa apakah webhook sudah diatur dengan benar
2. Verifikasi URL webhook aktif dan dapat menerima POST request
3. Cek rate limiting configuration
4. Pastikan bot tidak diblokir oleh user

### Error saat Membuat Link
1. Periksa format URL yang dimasukkan
2. Verifikasi custom alias belum digunakan
3. Pastikan custom alias mengikuti format yang diizinkan
4. Cek log untuk error detail

## Monitoring

### Log Monitoring
Monitor log Laravel untuk error:
```bash
tail -f storage/logs/laravel.log | grep "Telegram"
```

### Webhook Info
Periksa status webhook:
```bash
php artisan telegram:setup-webhook
```

## Deployment

### Production Deployment
1. Set webhook URL ke production domain
2. Pastikan SSL certificate valid
3. Konfigurasi environment variables
4. Test semua command functionality
5. Monitor rate limiting performance

### Staging Deployment
1. Gunakan staging environment untuk testing
2. Setup webhook dengan staging URL
3. Test dengan real user scenarios
4. Verifikasi error handling

## API Reference

### Webhook Endpoint
```
POST /api/telegram/webhook
```

### Set Webhook
```
GET /api/telegram/set-webhook?url={webhook_url}
```

### Get Webhook Info
```
GET /api/telegram/webhook-info
```

## Support

Untuk pertanyaan atau masalah, hubungi:
- Admin Bot
- Documentation: [Link ke dokumentasi]
- Issue Tracker: [Link ke issue tracker]