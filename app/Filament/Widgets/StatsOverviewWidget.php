<?php

namespace App\Filament\Widgets;

use App\Models\Link;
use App\Models\ClickLog;
use App\Models\Admin;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalLinks = Link::count();
        $activeLinks = Link::where('disabled', false)->count();
        $disabledLinks = Link::where('disabled', true)->count();
        $totalClicks = Link::sum('clicks');
        $todayClicks = ClickLog::whereDate('timestamp', today())->count();
        $weekClicks = ClickLog::whereBetween('timestamp', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $monthClicks = ClickLog::whereBetween('timestamp', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $totalAdmins = Admin::count();
        $activeAdmins = Admin::where('is_active', true)->count();

        return [
            Stat::make('Total Link', number_format($totalLinks))
                ->description("{$activeLinks} aktif, {$disabledLinks} nonaktif")
                ->descriptionIcon('heroicon-m-link')
                ->chart([7, 12, 10, 14, 15, 18, 20])
                ->color('primary'),
            
            Stat::make('Total Klik', number_format($totalClicks))
                ->description("{$todayClicks} hari ini")
                ->descriptionIcon('heroicon-m-mouse-pointer')
                ->chart([12, 15, 10, 18, 20, 25, 30])
                ->color('success'),
            
            Stat::make('Klik Minggu Ini', number_format($weekClicks))
                ->description("{$monthClicks} bulan ini")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart([30, 45, 60, 80, 95, 110, 125])
                ->color('warning'),
            
            Stat::make('Total Admin', number_format($totalAdmins))
                ->description("{$activeAdmins} aktif")
                ->descriptionIcon('heroicon-m-users')
                ->chart([2, 3, 3, 4, 4, 5, 5])
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}