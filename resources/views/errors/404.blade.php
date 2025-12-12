<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Tidak Ditemukan - 404</title>
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
            color: #dc3545;
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
        .search-box {
            margin: 30px 0;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .search-input {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            width: 250px;
            transition: border-color 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .search-btn {
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .search-btn:hover {
            background-color: #1d4ed8;
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
        <div class="error-icon">🔍</div>
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Link Tidak Ditemukan</h2>
        <p class="error-description">
            Maaf, link yang Anda cari tidak ditemukan atau telah dihapus. 
            Link mungkin sudah kadaluarsa atau alamat yang Anda masukkan salah.
        </p>
        
        <div class="search-box">
            <input type="text" 
                   class="search-input" 
                   id="shortCodeInput" 
                   placeholder="Masukkan kode pendek..." 
                   maxlength="15">
            <button class="search-btn" onclick="searchLink()">Cari</button>
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
        function searchLink() {
            const shortCode = document.getElementById('shortCodeInput').value.trim();
            if (shortCode) {
                window.location.href = `{{ config('domain.production') }}/${shortCode}`;
            }
        }
        
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '{{ config('domain.production') }}';
            }
        }
        
        // Allow search on Enter key
        document.getElementById('shortCodeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchLink();
            }
        });
        
        // Focus on search input
        document.getElementById('shortCodeInput').focus();
    </script>
</body>
</html>