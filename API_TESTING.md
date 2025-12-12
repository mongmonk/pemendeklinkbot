# API Testing Guide - Aqwam URL Shortener

## Testing Checklist

### 1. Web Routes Testing

#### Test Redirect Functionality
```bash
# Test dengan short code yang ada
curl -I "https://aqwam.test/abc12"
# Expected: HTTP 301 Redirect ke URL asli

# Test dengan short code yang tidak ada
curl -I "https://aqwam.test/notfound"
# Expected: HTTP 404 Not Found
```

#### Test Preview Functionality
```bash
# Test preview page
curl "https://aqwam.test/preview/abc12"
# Expected: HTML page dengan preview dan analytics
```

### 2. API Routes Testing

#### Test Webhook Info
```bash
curl -X GET "https://aqwam.test/api/telegram/webhook-info"
# Expected: JSON dengan webhook info
```

#### Test Set Webhook
```bash
curl -X GET "https://aqwam.test/api/telegram/set-webhook?url=https://aqwam.test/api/telegram/webhook"
# Expected: JSON success response
```

#### Test Webhook dengan Valid Update
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
      "text": "https://google.com"
    }
  }'
# Expected: JSON success response
```

#### Test Webhook dengan Custom Alias
```bash
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "message_id": 124,
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
      "text": "https://facebook.com my_fb_link"
    }
  }'
# Expected: JSON success response
```

#### Test Webhook dengan Invalid URL
```bash
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "message_id": 125,
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
      "text": "invalid-url"
    }
  }'
# Expected: JSON error response (bot akan reply dengan error message)
```

#### Test Webhook dengan Invalid Custom Alias
```bash
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "message_id": 126,
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
      "text": "https://twitter.com invalid@alias!"
    }
  }'
# Expected: JSON error response (bot akan reply dengan error message)
```

### 3. Rate Limiting Testing

#### Test Rate Limiting
```bash
# Kirim 6 request dalam 1 menit (melebihi limit)
for i in {1..6}; do
  curl -X POST "https://aqwam.test/api/telegram/webhook" \
    -H "Content-Type: application/json" \
    -d '{"message":{"text":"test"}}'
  echo "Request $i completed"
  sleep 0.1
done
# Expected: Request ke-6 harusnya mendapat 429 Too Many Requests
```

### 4. Database Testing

#### Check Links Table
```sql
-- Verifikasi link tersimpan dengan benar
SELECT * FROM links ORDER BY created_at DESC LIMIT 5;

-- Verifikasi custom alias
SELECT * FROM links WHERE is_custom = 1;

-- Verifikasi short code length (harus 5 karakter untuk random)
SELECT short_code, LENGTH(short_code) as length FROM links WHERE is_custom = 0;
```

#### Check Click Logs Table
```sql
-- Verifikasi click logs terbuat setelah redirect
SELECT * FROM click_logs ORDER BY timestamp DESC LIMIT 5;

-- Verifikasi relasi dengan links
SELECT l.short_code, l.long_url, c.ip_address, c.timestamp 
FROM links l 
JOIN click_logs c ON l.short_code = c.short_code 
ORDER BY c.timestamp DESC LIMIT 5;
```

### 5. Telegram Bot Commands Testing

#### Test Commands via Webhook
```bash
# Test /start command
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "/start",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'

# Test /help command
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "/help",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'

# Test /stats command
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "/stats",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'

# Test /mylinks command
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "/mylinks",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'

# Test /popular command
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "/popular",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'
```

### 6. Security Testing

#### Test SQL Injection Protection
```bash
# Test dengan malicious input
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "https://example.com'; DROP TABLE links; --",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'
# Expected: Should be handled safely, no SQL injection
```

#### Test XSS Protection
```bash
# Test dengan XSS payload
curl -X POST "https://aqwam.test/api/telegram/webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "text": "https://example.com <script>alert(\"xss\")</script>",
      "from": {"id": 123456789},
      "chat": {"id": 123456789}
    }
  }'
# Expected: Script should be sanitized/escaped
```

### 7. Performance Testing

#### Test Redirect Speed
```bash
# Measure redirect response time
time curl -I "https://aqwam.test/abc12"
# Expected: Should be fast (< 200ms)
```

#### Test Concurrent Requests
```bash
# Test 10 concurrent requests
for i in {1..10}; do
  curl -I "https://aqwam.test/abc12" &
done
wait
# Expected: All requests should complete successfully
```

## Expected Results Summary

### ✅ Success Indicators
- Webhook menerima update dan merespon dengan JSON success
- Short code tergenerate dengan 5 karakter untuk random links
- Custom alias valid tersimpan dengan benar
- Redirect berfungsi dengan HTTP 301
- Click logs terbuat dengan data lengkap (IP, user agent, etc.)
- Rate limiting berfungsi setelah 5 requests/minute
- Commands bot merespon dengan format yang benar

### ❌ Failure Indicators
- HTTP 500 errors
- Database query errors
- Short code duplikat
- Redirect tidak berfungsi
- Click logs tidak terbuat
- Rate limiting tidak berfungsi
- Invalid input tidak divalidasi

## Debugging Tips

1. **Check Laravel Logs**: `tail -f storage/logs/laravel.log`
2. **Check Database**: Query langsung ke database untuk verifikasi data
3. **Check Network**: Gunakan browser developer tools untuk inspect requests
4. **Check Telegram Bot API**: Verify bot token dan webhook setup
5. **Check Cache**: Clear cache jika ada yang tidak beres: `php artisan cache:clear`