# Panduan Pengguna - Aqwam URL Shortener

## 📖 Overview

Aqwam URL Shortener adalah layanan pemendek URL yang dapat diakses melalui Telegram Bot dan Web Dashboard. Panduan ini akan membantu Anda menggunakan semua fitur yang tersedia.

## 🤖 Telegram Bot Guide

### Memulai Bot

#### 1. Temukan Bot
- Cari bot dengan nama `@pemendeklinkbot` di Telegram
- Klik tombol "Start" atau ketik `/start`

#### 2. Pesan Selamat Datang
Bot akan merespons dengan pesan selamat datang:
```
👋 Selamat datang di Aqwam URL Shortener Bot!

Saya dapat membantu Anda membuat link pendek dengan mudah.

📝 Cara penggunaan:
1. Kirim URL kepada saya
2. Saya akan memberikan link pendek

🏷️ Custom Alias:
Format: https://example.com [alias]
Contoh: https://google.com search

🔧 Perintah yang tersedia:
/help - Tampilkan bantuan
/stats - Lihat statistik link Anda
/mylinks - Lihat semua link Anda
/popular - Lihat link populer

Kirim URL sekarang untuk mulai! 🚀
```

### 📝 Membuat Link Pendek

#### Metode 1: Kirim URL Langsung
Cukup kirim URL apa pun ke bot:
```
https://example.com/very/long/url/with/parameters
```

Bot akan merespons:
```
✅ Link berhasil dibuat!

🔗 Short URL: aqwam.id/abc123
🌐 Original URL: https://example.com/very/long/url/with/parameters

💡 Tips: Anda dapat melihat statistik link dengan /stats abc123
```

#### Metode 2: Custom Alias
Tambahkan alias setelah URL:
```
https://google.com search-engine
```

Bot akan merespons:
```
✅ Link berhasil dibuat!

🔗 Short URL: aqwam.id/search-engine
🌐 Original URL: https://google.com
🏷️ Custom Alias: search-engine

💡 Tips: Anda dapat melihat statistik link dengan /stats search-engine
```

#### Metode 3: Menggunakan Command /short
```
/short https://facebook.com fb
```

Atau tanpa custom alias:
```
/short https://twitter.com
```

### 📊 Melihat Statistik

#### Statistik Semua Link
Ketik:
```
/stats
```

Bot akan menampilkan:
```
📊 Statistik Link Anda

🔗 Total Link: 15
👁️ Total Klik: 234

📈 5 Link Teratas:
1. abc123 - 45 klik
2. search-engine - 38 klik
3. fb - 32 klik
4. twitter - 28 klik
5. news - 21 klik

📋 Lihat semua link: /mylinks
```

#### Statistik Link Spesifik
Ketik:
```
/stats abc123
```

Bot akan menampilkan:
```
📊 Statistik Link: `abc123`

🔗 Short URL: aqwam.id/abc123
🌐 Original URL: https://example.com/very/long/url
👁️ Total Klik: 45
👥 Klik Unik: 38
📅 Klik Hari Ini: 5
📈 Status: Aktif
```

### 📋 Daftar Link

#### Melihat Semua Link
Ketik:
```
/mylinks
```

Bot akan menampilkan:
```
📋 Link Anda:

1. abc123 - 45 klik
   aqwam.id/abc123
   📅 15 Des 2024

2. search-engine - 38 klik
   aqwam.id/search-engine
   📅 14 Des 2024

3. fb - 32 klik
   aqwam.id/fb
   📅 13 Des 2024
...
```

### 🔥 Link Populer

#### Melihat Link Populer
Ketik:
```
/popular
```

Bot akan menampilkan:
```
🔥 Link Populer:

1. promo - 1,234 klik
   aqwam.id/promo

2. news - 987 klik
   aqwam.id/news

3. tutorial - 654 klik
   aqwam.id/tutorial
...
```

### ⚙️ Perintah Lainnya

#### Bantuan
```
/help
```

#### Memulai Ulang
```
/start
```

## 🌐 Web Interface Guide

### Mengakses Short URL

#### Direct Redirect
Buka browser dan akses:
```
https://aqwam.id/abc123
```

Anda akan langsung di-redirect ke URL asli.

#### Preview Page
Buka browser dan akses:
```
https://aqwam.id/preview/abc123
```

Halaman preview akan menampilkan:
- **Informasi Link**: Short URL, original URL, tanggal pembuatan
- **Statistik**: Total klik, klik unik, klik hari ini
- **Analytics**: Negara, device, browser, OS
- **Klik Terbaru**: 50 klik terakhir dengan detail lengkap

### Fitur Preview Page

#### 📊 Analytics Tab
Menampilkan grafik dan statistik lengkap:
- **Negara Teratas**: 10 negara dengan klik terbanyak
- **Perangkat**: Distribusi device (Desktop, Mobile, Tablet)
- **Browser**: Browser yang digunakan pengunjung
- **Sistem Operasi**: OS pengunjung
- **Sumber Traffic**: Domain referer

#### 🕐 Klik Terbaru Tab
Menampilkan 50 klik terakhir dengan:
- **Waktu**: Timestamp klik
- **Lokasi**: Negara dan kota
- **Device**: Tipe device dan browser
- **OS**: Sistem operasi
- **Referer**: Sumber traffic

#### ⏱️ Auto-Redirect
Halaman preview akan otomatis redirect setelah 10 detik. Tombol akan menampilkan countdown.

## 🔧 Admin Dashboard Guide

### Mengakses Dashboard

1. Buka browser: `https://aqwam.id/admin`
2. Login dengan kredensial admin
3. Dashboard akan menampilkan overview sistem

### 📊 Dashboard Overview

#### Statistics Widget
Menampilkan 4 statistik utama:
- **Total Link**: Jumlah semua link (aktif & nonaktif)
- **Total Klik**: Total semua klik dengan grafik mingguan
- **Klik Minggu Ini**: Total klik minggu ini dengan grafik bulanan
- **Total Admin**: Jumlah admin yang terdaftar

#### Charts
Setiap widget memiliki chart mini yang menampilkan tren 7 hari terakhir.

### 🔗 Link Management

#### List Links
Akses menu **Links** untuk melihat semua link yang terdaftar.

#### Tabs
- **Semua**: Menampilkan semua link
- **Aktif**: Hanya link yang aktif
- **Nonaktif**: Link yang dinonaktifkan
- **Kustom**: Link dengan custom alias
- **Populer**: Link diurutkan berdasarkan jumlah klik

#### Create New Link
1. Klik tombol **"Buat Link Baru"**
2. Isi form:
   - **Short Code**: Kode pendek (kosongkan untuk auto-generate)
   - **Long URL**: URL asli yang akan dipendekkan
   - **Custom Alias**: Centang jika menggunakan custom alias
   - **Telegram User ID**: ID pengguna Telegram (opsional)
3. Klik **"Save"**

#### Edit Link
1. Klik icon edit pada baris link
2. Edit informasi yang diperlukan
3. Klik **"Save"**

#### Disable/Enable Link
1. Klik icon toggle pada baris link
2. Link akan dinonaktifkan/diaktifkan
3. Tambahkan alasan jika menonaktifkan

#### Delete Link
1. Klik icon delete pada baris link
2. Konfirmasi penghapusan
3. Link dan semua data klik akan dihapus

### 👥 Admin Management

#### List Admins
Akses menu **Admins** untuk mengelola admin users.

#### Tabs
- **Semua**: Menampilkan semua admin
- **Aktif**: Admin yang aktif
- **Nonaktif**: Admin yang dinonaktifkan

#### Create New Admin
1. Klik tombol **"Buat Admin Baru"**
2. Isi form:
   - **Telegram User ID**: ID Telegram admin
   - **Username**: Username untuk login
   - **Email**: Email admin (opsional)
   - **Password**: Password untuk login
   - **Is Active**: Status aktif admin
3. Klik **"Save"**

#### Edit Admin
1. Klik icon edit pada baris admin
2. Edit informasi yang diperlukan
3. Klik **"Save"**

### 📈 Analytics Dashboard

#### Link Analytics
1. Klik pada link di list untuk melihat detail
2. Halaman akan menampilkan:
   - **Overview**: Total klik, klik unik, klik hari ini
   - **Charts**: Grafik klik harian/mingguan/bulanan
   - **Geographic**: Peta distribusi negara
   - **Devices**: Pie chart device types
   - **Browsers**: Browser usage statistics
   - **Traffic Sources**: Referer domains

#### Real-time Updates
Dashboard diperbarui secara real-time untuk:
- New clicks
- Active users
- Popular links

## 🎯 Best Practices

### Membuat Link yang Efektif

#### Custom Alias yang Baik
- **Pendek dan Mudah Diingat**: `promo`, `sale`, `news`
- **Relevan dengan Konten**: `tutorial-laravel`, `guide-python`
- **Hanya Karakter Aman**: Huruf, angka, hyphen, underscore

#### URL yang Aman
- Gunakan HTTPS: `https://example.com`
- Hindari localhost/IP private
- Pastikan URL valid dan accessible

### Menggunakan Statistik

#### Monitoring Performance
- Pantau klik harian untuk mengukur engagement
- Identifikasi link yang paling populer
- Analisis pola traffic berdasarkan waktu

#### Optimasi Campaign
- Gunakan custom alias untuk campaign tracking
- Monitor geographic distribution untuk targeting
- Analisis device usage untuk optimization

### Security Tips

#### Link Privacy
- Jangan bagikan link sensitif secara publik
- Gunakan custom alias yang tidak mudah ditebak
- Monitor link yang tidak aktif

#### Admin Security
- Gunakan password yang kuat
- Enable 2FA jika tersedia
- Logout setelah menggunakan dashboard

## ❓ FAQ

### General Questions

**Q: Apakah Aqwam URL Shortener gratis?**
A: Ya, layanan ini gratis untuk semua pengguna.

**Q: Apakah ada batasan jumlah link?**
A: Tidak ada batasan jumlah link yang dapat dibuat.

**Q: Apakah link akan kedaluwarsa?**
A: Tidak, link akan aktif selama tidak dinonaktifkan.

### Telegram Bot Questions

**Q: Bot tidak merespons, apa yang salah?**
A: Pastikan:
- Koneksi internet stabil
- Bot tidak diblokir
- Format URL benar (http:// atau https://)

**Q: Custom alias tidak berfungsi?**
A: Pastikan:
- Alias belum digunakan
- Hanya karakter yang diizinkan (a-z, 0-9, -, _)
- Maksimal 15 karakter

**Q: Rate limit terlalu ketat?**
A: Rate limit adalah 5 request/menit untuk mencegah spam. Tunggu 1 menit untuk mencoba lagi.

### Web Interface Questions

**Q: Redirect terlalu lambat?**
A: Redirect biasanya <100ms. Jika lambat:
- Clear browser cache
- Cek koneksi internet
- Report ke admin

**Q: Preview page tidak menampilkan data?**
A: Pastikan:
- Link valid dan aktif
- Ada data klik yang tercatat
- JavaScript di-enable di browser

### Admin Dashboard Questions

**Q: Tidak bisa login ke dashboard?**
A: Periksa:
- Kredensial login benar
- Admin account aktif
- Browser mengizinkan cookies

**Q: Data tidak real-time?**
A: Dashboard update setiap 30 detik. Refresh halaman untuk data terbaru.

## 🆘 Bantuan & Support

### Contact Support
- **Telegram Bot**: @pemendeklinkbot
- **Email**: support@aqwam.id
- **Website**: https://aqwam.id

### Report Issues
- **Bug Report**: GitHub Issues
- **Feature Request**: GitHub Discussions
- **Security Issue**: security@aqwam.id

### Documentation
- **API Documentation**: [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
- **Deployment Guide**: [DEPLOYMENT.md](./DEPLOYMENT.md)
- **Troubleshooting**: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

---

*Panduan ini akan diperbarui secara berkala sesuai dengan perkembangan fitur.*