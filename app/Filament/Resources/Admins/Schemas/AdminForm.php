<?php

namespace App\Filament\Resources\Admins\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Admin')
                    ->description('Masukkan detail untuk akun admin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('telegram_user_id')
                                    ->label('Telegram User ID')
                                    ->required()
                                    ->numeric()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('ID unik dari Telegram user'),
                                
                                TextInput::make('username')
                                    ->label('Username')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Username untuk login ke dashboard'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Email untuk notifikasi'),
                                
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->rule(Password::default())
                                    ->helperText(fn (string $context): string => $context === 'edit' ? 'Kosongkan untuk tidak mengubah password' : 'Minimal 8 karakter'),
                            ]),
                    ]),
                
                Section::make('Status & Keamanan')
                    ->description('Konfigurasi status dan keamanan akun')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->helperText('Hanya admin aktif yang dapat mengakses dashboard')
                            ->default(true),
                        
                        Placeholder::make('last_login')
                            ->label('Terakhir Login')
                            ->content(fn ($record): string => $record?->updated_at?->diffForHumans() ?? '-')
                            ->visible(fn (string $context): bool => $context === 'edit'),
                    ])
                    ->collapsible()
                    ->collapsed(fn (string $context): bool => $context === 'create'),
                
                Section::make('Statistik Admin')
                    ->description('Ringkasan aktivitas admin')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('total_links')
                                    ->label('Total Link')
                                    ->content(fn ($record): string => $record?->total_links ?? '0'),
                                
                                Placeholder::make('total_clicks')
                                    ->label('Total Klik')
                                    ->content(fn ($record): string => number_format($record?->total_clicks ?? 0)),
                                
                                Placeholder::make('today_links')
                                    ->label('Link Hari Ini')
                                    ->content(fn ($record): string => $record?->today_links ?? '0'),
                            ]),
                    ])
                    ->visible(fn (string $context): bool => $context === 'edit')
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
