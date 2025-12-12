<?php

namespace App\Filament\Widgets;

use App\Models\Link;
use App\Models\ClickLog;
use App\Models\Admin;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Aktivitas Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClickLog::query()
                    ->with('link')
                    ->latest('timestamp')
                    ->limit(50)
            )
            ->columns([
                Tables\Columns\TextColumn::make('timestamp')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->description(fn ($record): string => $record->timestamp->diffForHumans()),
                
                Tables\Columns\TextColumn::make('link.short_code')
                    ->label('Kode Pendek')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Kode pendek disalin!')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('IP Address disalin!')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('country')
                    ->label('Negara')
                    ->searchable()
                    ->placeholder('-'),
                
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Perangkat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'desktop' => 'primary',
                        'mobile' => 'success',
                        'tablet' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('browser')
                    ->label('Browser')
                    ->searchable()
                    ->placeholder('-'),
                
                Tables\Columns\TextColumn::make('referer_domain')
                    ->label('Sumber')
                    ->placeholder('Direct')
                    ->searchable(),
            ])
            ->defaultSort('timestamp', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->striped();
    }
}