<x-filament-panels::page>
    <form wire:submit="applyFilters">
        {{ $this->form }}
    </form>

    <div class="space-y-6">
        <!-- Link Information -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-filament::field.wrapper>
                        <x-filament::field.label>Kode Pendek</x-filament::field.label>
                        <div class="flex items-center gap-2">
                            <x-filament::input value="{{ $record->short_code }}" readonly />
                            <x-filament::button size="sm" 
                                onclick="navigator.clipboard.writeText('{{ $record->short_code }}')"
                                x-tooltip="Salin">
                                <x-filament::icon icon="heroicon-o-clipboard-document" />
                            </x-filament::button>
                        </div>
                    </x-filament::field.wrapper>
                </div>
                <div>
                    <x-filament::field.wrapper>
                        <x-filament::field.label>URL Pendek</x-filament::field.label>
                        <div class="flex items-center gap-2">
                            <x-filament::input value="{{ $record->short_url }}" readonly class="flex-1" />
                            <x-filament::button size="sm"
                                onclick="navigator.clipboard.writeText('{{ $record->short_url }}')"
                                x-tooltip="Salin">
                                <x-filament::icon icon="heroicon-o-clipboard-document" />
                            </x-filament::button>
                        </div>
                    </x-filament::field.wrapper>
                </div>
                <div class="md:col-span-2">
                    <x-filament::field.wrapper>
                        <x-filament::field.label>URL Asli</x-filament::field.label>
                        <x-filament::input value="{{ $record->long_url }}" readonly />
                    </x-filament::field.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Klik</p>
                        <p class="text-2xl font-bold">{{ $this->getTotalClicks() }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-mouse-pointer" class="text-blue-600 dark:text-blue-300" />
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pengunjung Unik</p>
                        <p class="text-2xl font-bold">{{ $this->getUniqueClicks() }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-users" class="text-green-600 dark:text-green-300" />
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Klik Hari Ini</p>
                        <p class="text-2xl font-bold">{{ $this->getTodayClicks() }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="text-purple-600 dark:text-purple-300" />
                    </div>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <p class="text-2xl font-bold">
                            @if($record->disabled)
                                <span class="text-red-600">Dinonaktifkan</span>
                            @else
                                <span class="text-green-600">Aktif</span>
                            @endif
                        </p>
                    </div>
                    <div class="p-3 {{ $record->disabled ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' }} rounded-lg">
                        <x-filament::icon icon="{{ $record->disabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle' }}" 
                            class="{{ $record->disabled ? 'text-red-600 dark:text-red-300' : 'text-green-600 dark:text-green-300' }}" />
                    </div>
                </div>
            </x-filament::card>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Clicks Over Time Chart -->
            <x-filament::section heading="Grafik Klik Per Waktu">
                <div class="h-64">
                    @php
                        $clicksOverTime = $this->getClicksOverTime();
                        $labels = array_keys($clicksOverTime);
                        $data = array_values($clicksOverTime);
                    @endphp
                    <canvas id="clicksChart"></canvas>
                </div>
            </x-filament::section>

            <!-- Device Breakdown -->
            <x-filament::section heading="Breakdown Perangkat">
                <div class="h-64">
                    @php
                        $clicksByDevice = $this->getClicksByDevice();
                        $deviceLabels = array_keys($clicksByDevice);
                        $deviceData = array_values($clicksByDevice);
                    @endphp
                    <canvas id="deviceChart"></canvas>
                </div>
            </x-filament::section>
        </div>

        <!-- Top Countries & Browsers -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Countries -->
            <x-filament::section heading="Negara Teratas">
                <div class="space-y-2">
                    @php
                        $clicksByCountry = $this->getClicksByCountry();
                    @endphp
                    @if(count($clicksByCountry) > 0)
                        @foreach($clicksByCountry as $country => $count)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                <span class="font-medium">{{ $country ?? 'Unknown' }}</span>
                                <span class="text-sm text-gray-500">{{ $count }} klik</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 text-center py-4">Belum ada data</p>
                    @endif
                </div>
            </x-filament::section>

            <!-- Top Browsers -->
            <x-filament::section heading="Browser Teratas">
                <div class="space-y-2">
                    @php
                        $clicksByBrowser = $this->getClicksByBrowser();
                    @endphp
                    @if(count($clicksByBrowser) > 0)
                        @foreach($clicksByBrowser as $browser => $count)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                <span class="font-medium">{{ $browser }}</span>
                                <span class="text-sm text-gray-500">{{ $count }} klik</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 text-center py-4">Belum ada data</p>
                    @endif
                </div>
            </x-filament::section>
        </div>

        <!-- Recent Clicks Table -->
        <x-filament::section heading="50 Klik Terakhir">
            {{ $this->table }}
        </x-filament::section>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Clicks Over Time Chart
            const clicksCtx = document.getElementById('clicksChart').getContext('2d');
            new Chart(clicksCtx, {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: [{
                        label: 'Jumlah Klik',
                        data: @json($data),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Device Breakdown Chart
            const deviceCtx = document.getElementById('deviceChart').getContext('2d');
            new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($deviceLabels),
                    datasets: [{
                        data: @json($deviceData),
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(34, 197, 94)',
                            'rgb(251, 146, 60)',
                            'rgb(168, 85, 247)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        </script>
    @endpush
</x-filament-panels::page>