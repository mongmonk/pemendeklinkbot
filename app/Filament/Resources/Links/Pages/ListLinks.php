<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Resources\Links\LinkResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;

class ListLinks extends ListRecords
{
    protected static string $resource = LinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Link Baru')
                ->icon('heroicon-o-plus'),
            Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // TODO: Implement export functionality
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('disabled', false)),
            'disabled' => Tab::make('Nonaktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('disabled', true)),
            'custom' => Tab::make('Kustom')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_custom', true)),
            'popular' => Tab::make('Populer')
                ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('clicks', 'desc')->limit(50)),
        ];
    }
}
