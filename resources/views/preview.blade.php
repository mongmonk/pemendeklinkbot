<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Preview - {{ $link->short_code }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .short-url {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .long-url {
            font-size: 14px;
            color: #6c757d;
            word-break: break-all;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
        }
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .charts {
            margin-top: 30px;
        }
        .chart-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .list-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .progress-bar {
            background-color: #e9ecef;
            height: 8px;
            border-radius: 4px;
            margin-top: 5px;
        }
        .progress-fill {
            background-color: #2563eb;
            height: 100%;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
        }
        .redirect-btn {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 20px;
        }
        .redirect-btn:hover {
            background-color: #1d4ed8;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom-color: #2563eb;
            color: #2563eb;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .recent-clicks {
            max-height: 400px;
            overflow-y: auto;
        }
        .click-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .click-time {
            color: #6c757d;
            min-width: 150px;
        }
        .click-details {
            flex: 1;
            display: flex;
            gap: 15px;
        }
        .click-detail {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .badge {
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Link Preview</h1>
            <div class="short-url">{{ config('domain.production') }}/{{ $link->short_code }}</div>
            <div class="long-url">{{ $link->long_url }}</div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['total_clicks'] ?? 0 }}</div>
                <div class="stat-label">Total Klik</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['unique_clicks'] ?? 0 }}</div>
                <div class="stat-label">IP Unik</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['today_clicks'] ?? 0 }}</div>
                <div class="stat-label">Klik Hari Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $analytics['click_rate_per_day'] ?? 0 }}</div>
                <div class="stat-label">Rata-rata/Hari</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $link->created_at->diffForHumans() }}</div>
                <div class="stat-label">Dibuat</div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $link->short_url }}" class="redirect-btn">Lanjutkan ke Link</a>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showTab('analytics')">📊 Analytics</div>
            <div class="tab" onclick="showTab('recent')">🕐 Klik Terbaru</div>
        </div>

        <div id="analytics" class="tab-content active">
            <div class="two-column">
                @if(!empty($analytics['countries']))
                    <div class="chart-container">
                        <div class="chart-title">🌍 Negara Teratas</div>
                        @foreach($analytics['countries'] as $country)
                            <div class="list-item">
                                <span>{{ $country->country ?? 'Unknown' }}</span>
                                <span>{{ $country->count }} klik</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ ($country->count / ($analytics['countries']->first()->count) * 100) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($analytics['devices']))
                    <div class="chart-container">
                        <div class="chart-title">📱 Perangkat</div>
                        @foreach($analytics['devices'] as $device)
                            <div class="list-item">
                                <span>{{ $device->device_type ?? 'Unknown' }}</span>
                                <span>{{ $device->count }} klik</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ ($device->count / ($analytics['devices']->first()->count) * 100) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($analytics['browsers']))
                    <div class="chart-container">
                        <div class="chart-title">🌐 Browser</div>
                        @foreach($analytics['browsers'] as $browser)
                            <div class="list-item">
                                <span>{{ $browser->browser }} {{ $browser->browser_version }}</span>
                                <span>{{ $browser->count }} klik</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ ($browser->count / ($analytics['browsers']->first()->count) * 100) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($analytics['operating_systems']))
                    <div class="chart-container">
                        <div class="chart-title">💻 Sistem Operasi</div>
                        @foreach($analytics['operating_systems'] as $os)
                            <div class="list-item">
                                <span>{{ $os->os }} {{ $os->os_version }}</span>
                                <span>{{ $os->count }} klik</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ ($os->count / ($analytics['operating_systems']->first()->count) * 100) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($analytics['referer_domains']))
                    <div class="chart-container">
                        <div class="chart-title">🔗 Sumber Traffic</div>
                        @foreach($analytics['referer_domains'] as $referer)
                            <div class="list-item">
                                <span>{{ $referer->domain }}</span>
                                <span>{{ $referer->count }} klik</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ ($referer->count / ($analytics['referer_domains']->first()->count) * 100) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div id="recent" class="tab-content">
            <div class="chart-container">
                <div class="chart-title">🕐 50 Klik Terbaru</div>
                <div class="recent-clicks">
                    @if(!empty($recentClicks))
                        @foreach($recentClicks as $click)
                            <div class="click-item">
                                <div class="click-time">{{ $click['timestamp'] }}</div>
                                <div class="click-details">
                                    <div class="click-detail">
                                        <span>🌍</span>
                                        <span class="badge">{{ $click['country'] }}</span>
                                    </div>
                                    <div class="click-detail">
                                        <span>📱</span>
                                        <span class="badge">{{ $click['device_type'] }}</span>
                                    </div>
                                    <div class="click-detail">
                                        <span>🌐</span>
                                        <span class="badge">{{ $click['browser'] }}</span>
                                    </div>
                                    <div class="click-detail">
                                        <span>💻</span>
                                        <span class="badge">{{ $click['os'] }}</span>
                                    </div>
                                    <div class="click-detail">
                                        <span>🔗</span>
                                        <span class="badge">{{ $click['referer'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p style="text-align: center; color: #6c757d;">Belum ada klik yang tercatat</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Powered by Aqwam URL Shortener</p>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Auto redirect after 10 seconds
        setTimeout(function() {
            window.location.href = '{{ $link->short_url }}';
        }, 10000);
        
        // Countdown timer
        let seconds = 10;
        setInterval(function() {
            seconds--;
            const button = document.querySelector('.redirect-btn');
            if (seconds <= 0) {
                button.textContent = 'Mengalihkan...';
            } else {
                button.textContent = `Lanjutkan ke Link (${seconds})`;
            }
        }, 1000);
    </script>
</body>
</html>