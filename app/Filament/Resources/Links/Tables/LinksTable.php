<?php

namespace App\Filament\Resources\Links\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('short_code')
                    ->label('Kode Pendek')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Kode pendek disalin!')
                    ->copyMessageDuration(1500),
                TextColumn::make('short_url')
                    ->label('URL Pendek')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('URL pendek disalin!')
                    ->copyMessageDuration(1500)
                    ->limit(30),
                TextColumn::make('long_url')
                    ->label('URL Asli')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('clicks')
                    ->label('Total Klik')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('disabled')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->sortable(),
                IconColumn::make('is_custom')
                    ->label('Kustom')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Link Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('disabled', false))
                    ->toggle(),
                Filter::make('disabled')
                    ->label('Link Nonaktif')
                    ->query(fn (Builder $query): Builder => $query->where('disabled', true))
                    ->toggle(),
                Filter::make('custom')
                    ->label('Link Kustom')
                    ->query(fn (Builder $query): Builder => $query->where('is_custom', true))
                    ->toggle(),
                Filter::make('random')
                    ->label('Link Acak')
                    ->query(fn (Builder $query): Builder => $query->where('is_custom', false))
                    ->toggle(),
                SelectFilter::make('telegram_user_id')
                    ->label('Pengguna Telegram')
                    ->relationship('admin', 'username')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('view_analytics')
                    ->label('Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record): string => route('filament.admin.resources.links.analytics', $record)),
                Action::make('toggle_status')
                    ->label(fn ($record): string => $record->disabled ? 'Aktifkan' : 'Nonaktifkan')
                    ->icon(fn ($record): string => $record->disabled ? 'heroicon-o-check' : 'heroicon-o-x-mark')
                    ->color(fn ($record): string => $record->disabled ? 'success' : 'danger')
                    ->action(function ($record) {
                        if ($record->disabled) {
                            $record->enable();
                        } else {
                            $record->disable('Dinonaktifkan oleh admin');
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Perubahan Status')
                    ->modalDescription('Apakah Anda yakin ingin mengubah status link ini?'),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Link')
                    ->modalDescription('Apakah Anda yakin ingin menghapus link ini? Tindakan ini tidak dapat dibatalkan.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Link Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus link yang dipilih? Tindakan ini tidak dapat dibatalkan.'),
                ]),
            ])
            ->emptyStateHeading('Tidak ada link ditemukan')
            ->emptyStateDescription('Mulai dengan membuat link baru.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Link Baru')
                    ->url(route('filament.admin.resources.links.create'))
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }
}
