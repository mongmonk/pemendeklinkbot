<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Dinonaktifkan - 410</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
        }
        .error-container {
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #ffc107;
            margin: 0;
            line-height: 1;
        }
        .error-title {
            font-size: 28px;
            color: #333;
            margin: 20px 0 10px;
        }
        .error-description {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .warning-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .warning-text {
            color: #856404;
            font-size: 14px;
        }
        .footer-text {
            margin-top: 30px;
            font-size: 14px;
            color: #6c757d;
        }
        @media (max-width: 600px) {
            .error-container {
                padding: 30px 20px;
            }
            .error-code {
                font-size: 80px;
            }
            .error-title {
                font-size: 24px;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn {
                width: 100%;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚫</div>
        <h1 class="error-code">410</h1>
        <h2 class="error-title">Link Telah Dinonaktifkan</h2>
        <p class="error-description">
            Link yang Anda coba akses telah dinonaktifkan oleh pemiliknya. 
            Ini bisa terjadi karena berbagai alasan seperti pelanggaran kebijakan, 
            penyalahgunaan, atau permintaan dari pemilik link.
        </p>
        
        <div class="warning-box">
            <div class="warning-title">
                ⚠️ Informasi Penting
            </div>
            <div class="warning-text">
                Link yang dinonaktifkan tidak dapat lagi diakses dan tidak akan 
                mengalihkan Anda ke tujuan semula. Jika Anda adalah pemilik link 
                dan ingin mengaktifkannya kembali, silakan hubungi admin.
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="{{ config('domain.production') }}" class="btn btn-primary">
                🏠 Halaman Utama
            </a>
            <button onclick="goBack()" class="btn btn-secondary">
                ← Kembali
            </button>
        </div>
        
        <p class="footer-text">
            Butuh bantuan? Hubungi admin bot Telegram @pemendeklinkbot
        </p>
    </div>

    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '{{ config('domain.production') }}';
            }
        }
    </script>
</body>
</html>