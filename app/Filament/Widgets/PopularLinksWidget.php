<?php

namespace App\Filament\Widgets;

use App\Models\Link;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularLinksWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Link Populer (Top 10)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Link::query()
                    ->with('admin')
                    ->orderBy('clicks', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('short_code')
                    ->label('Kode Pendek')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Kode pendek disalin!')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('short_url')
                    ->label('URL Pendek')
                    ->limit(30)
                    ->copyable()
                    ->copyMessage('URL pendek disalin!')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('admin.username')
                    ->label('Pemilik')
                    ->placeholder('System')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('clicks')
                    ->label('Total Klik')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('disabled')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->alignCenter(),
            ])
            ->defaultSort('clicks', 'desc')
            ->paginated([5, 10, 25])
            ->poll('60s')
            ->striped();
    }
}