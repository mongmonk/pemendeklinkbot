# API Documentation - Aqwam URL Shortener

## Base URL
- Local: `https://aqwam.test`
- Production: `https://aqwam.id`

## Web Routes

### 1. Redirect URL
- **Endpoint**: `GET /{short_code}`
- **Description**: Redirect ke URL asli dari short code
- **Parameters**:
  - `short_code` (string): Short code yang akan di-redirect
- **Response**: HTTP 301 Redirect ke URL asli
- **Example**: `GET https://aqwam.test/abc12`

### 2. Preview URL
- **Endpoint**: `GET /preview/{short_code}`
- **Description**: Tampilkan preview halaman untuk short URL
- **Parameters**:
  - `short_code` (string): Short code yang akan dipreview
- **Response**: HTML page dengan preview dan analytics
- **Example**: `GET https://aqwam.test/preview/abc12`

## API Routes

### 1. Telegram Webhook
- **Endpoint**: `POST /api/telegram/webhook`
- **Description**: Menerima update dari Telegram Bot
- **Rate Limiting**: 5 requests per minute
- **Headers**:
  - `X-Telegram-Bot-Api-Secret-Token` (optional): Webhook secret token
- **Body**: JSON update dari Telegram
- **Response**: 
  ```json
  {
    "status": "success"
  }
  ```

### 2. Set Webhook
- **Endpoint**: `GET /api/telegram/set-webhook`
- **Description**: Setup webhook untuk Telegram Bot
- **Parameters**:
  - `url` (string, optional): Webhook URL
- **Response**: 
  ```json
  {
    "status": "success",
    "message": "Webhook berhasil diatur",
    "webhook_url": "https://your-domain.com/api/telegram/webhook"
  }
  ```

### 3. Get Webhook Info
- **Endpoint**: `GET /api/telegram/webhook-info`
- **Description**: Mendapatkan informasi webhook yang aktif
- **Response**: 
  ```json
  {
    "status": "success",
    "data": {
      "url": "https://your-domain.com/api/telegram/webhook",
      "has_custom_certificate": false,
      "pending_update_count": 0,
      "last_error_date": 0,
      "last_error_message": "",
      "max_connections": 40,
      "ip_address": "192.168.1.1"
    }
  }
  ```

## Telegram Bot Commands

### Available Commands
- `/start` - Mulai bot dan tampilkan pesan selamat datang
- `/help` - Tampilkan bantuan
- `/stats` - Lihat statistik link user
- `/mylinks` - Lihat semua link user
- `/popular` - Lihat link populer

### Creating Short Links

#### Format 1: Random Short Code
```
https://example.com/very/long/url
```

#### Format 2: Custom Alias
```
https://example.com/very/long/url myalias
```

**Custom Alias Rules:**
- Maksimal 15 karakter
- Hanya huruf, angka, hyphen (-), dan underscore (_)
- Tidak boleh duplikat dengan link yang sudah ada

## Error Handling

### Common Error Responses

#### Invalid URL
```json
{
  "status": "error",
  "message": "URL tidak valid"
}
```

#### Invalid Custom Alias
```json
{
  "status": "error",
  "message": "Custom alias tidak valid. Hanya huruf, angka, hyphen, dan underscore yang diperbolehkan (maksimal 15 karakter)"
}
```

#### Duplicate Alias
```json
{
  "status": "error",
  "message": "Kode short sudah digunakan"
}
```

#### Rate Limit Exceeded
```json
{
  "status": "error",
  "message": "Too many requests"
}
```

## Testing dengan curl

### Test Webhook Setup
```bash
curl -X GET "https://aqwam.test/api/telegram/set-webhook?url=https://your-domain.com/api/telegram/webhook"
```

### Test Webhook Info
```bash
curl -X GET "https://aqwam.test/api/telegram/webhook-info"
```

### Test Webhook (Simulate Telegram Update)
```bash
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "message_id": 123,
      "from": {
        "id": 123456789,
        "first_name": "Test User"
      },
      "chat": {
        "id": 123456789,
        "first_name": "Test User",
        "type": "private"
      },
      "date": 1640995200,
      "text": "https://example.com"
    }
  }'
```

## Database Schema

### Links Table
- `id` (bigint, primary key)
- `short_code` (string, 10 chars, unique)
- `long_url` (text)
- `is_custom` (boolean)
- `telegram_user_id` (bigint, nullable)
- `clicks` (integer, default 0)
- `created_at` (timestamp)
- `updated_at` (timestamp)

### Click Logs Table
- `id` (bigint, primary key)
- `short_code` (string, 10 chars, foreign key)
- `ip_address` (string, 45 chars)
- `user_agent` (text, nullable)
- `referer` (string, 500 chars, nullable)
- `country` (string, 2 chars, nullable)
- `city` (string, 100 chars, nullable)
- `device_type` (string, 50 chars, nullable)
- `browser` (string, 100 chars, nullable)
- `browser_version` (string, 50 chars, nullable)
- `os` (string, 100 chars, nullable)
- `os_version` (string, 50 chars, nullable)
- `timestamp` (timestamp)

### Admins Table
- `id` (bigint, primary key)
- `telegram_user_id` (bigint, unique)
- `username` (string, nullable)
- `password_hash` (string)
- `email` (string, nullable)
- `is_active` (boolean, default true)
- `created_at` (timestamp)
- `updated_at` (timestamp)

## Configuration

### Environment Variables
```env
TELEGRAM_BOT_TOKEN=8552109110:AAHJHMIBm_ai5v0Kti9DqXHTs4kQxqdBKf8
TELEGRAM_WEBHOOK_URL=https://aqwam.id/api/telegram/webhook
TELEGRAM_WEBHOOK_SECRET=your_secret_token
TELEGRAM_RATE_LIMIT_ENABLED=true
TELEGRAM_RATE_LIMIT_ATTEMPTS=5
TELEGRAM_RATE_LIMIT_MINUTES=1
```

### Domain Configuration
```php
// config/domain.php
return [
    'local' => 'http://aqwam.test',
    'production' => 'https://aqwam.id',
];