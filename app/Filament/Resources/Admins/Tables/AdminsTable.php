<?php

namespace App\Filament\Resources\Admins\Tables;

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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('telegram_user_id')
                    ->label('Telegram ID')
                    ->numeric()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Telegram ID disalin!')
                    ->copyMessageDuration(1500),
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Username disalin!')
                    ->copyMessageDuration(1500),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('Tidak ada email'),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                TextColumn::make('total_links')
                    ->label('Total Link')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->links()->count()),
                TextColumn::make('total_clicks')
                    ->label('Total Klik')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->total_clicks),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Admin Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),
                Filter::make('inactive')
                    ->label('Admin Nonaktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', false))
                    ->toggle(),
            ])
            ->actions([
                Action::make('view_links')
                    ->label('Lihat Link')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record): string => route('filament.admin.resources.links.index', ['telegram_user_id' => $record->telegram_user_id]))
                    ->openUrlInNewTab(),
                Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->action(function ($record) {
                        $newPassword = Str::random(12);
                        $record->password_hash = bcrypt($newPassword);
                        $record->save();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Password berhasil direset')
                            ->body("Password baru untuk {$record->username}: {$newPassword}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password')
                    ->modalDescription('Apakah Anda yakin ingin mereset password admin ini? Password baru akan ditampilkan.')
                    ->modalSubmitActionLabel('Reset Password'),
                ToggleColumn::make('is_active')
                    ->label('Status'),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Admin')
                    ->modalDescription('Apakah Anda yakin ingin menghapus admin ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Hapus'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Admin Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus admin yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Hapus'),
                ]),
            ])
            ->emptyStateHeading('Tidak ada admin ditemukan')
            ->emptyStateDescription('Mulai dengan membuat admin baru.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Admin Baru')
                    ->url(route('filament.admin.resources.admins.create'))
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }
}
