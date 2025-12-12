<?php

namespace App\Filament\Widgets;

use App\Models\ClickLog;
use Filament\Widgets\ChartWidget;

class ClicksChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return 'Statistik Klik (7 Hari Terakhir)';
    }

    protected function getData(): array
    {
        $data = ClickLog::select(
                \Illuminate\Support\Facades\DB::raw('DATE(timestamp) as date'),
                \Illuminate\Support\Facades\DB::raw('count(*) as count')
            )
            ->where('timestamp', '>=', now()->subDays(6))
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(timestamp)'))
            ->orderBy('date')
            ->get();

        $labels = [];
        $clicks = [];

        // Generate last 7 days labels
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('D');
            
            $dayData = $data->firstWhere('date', $date);
            $clicks[] = $dayData ? $dayData->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Klik',
                    'data' => $clicks,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}