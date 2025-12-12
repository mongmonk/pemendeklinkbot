<?php

namespace App\Filament\Resources\Links\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Link')
                    ->description('Masukkan detail untuk link pendek yang akan dibuat')
                    ->schema([
                        TextInput::make('long_url')
                            ->label('URL Asli')
                            ->required()
                            ->url()
                            ->placeholder('https://example.com/very-long-url')
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('short_code', Str::random(6))),
                        
                        Grid::make(2)
                            ->schema([
                                TextInput::make('short_code')
                                    ->label('Kode Pendek')
                                    ->required()
                                    ->placeholder('misal: promo2023')
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Kosongkan untuk generate otomatis')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state) {
                                            $set('is_custom', true);
                                        }
                                    }),
                                
                                Toggle::make('is_custom')
                                    ->label('Link Kustom')
                                    ->helperText('Aktifkan jika menggunakan kode pendek kustom')
                                    ->default(false),
                            ]),
                        
                        Select::make('telegram_user_id')
                            ->label('Pemilik Link')
                            ->relationship('admin', 'username')
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih admin yang memiliki link ini')
                            ->default(fn () => auth()->user()?->telegram_user_id),
                    ]),
                
                Section::make('Informasi Tambahan')
                    ->description('Informasi tambahan tentang link')
                    ->schema([
                        TextInput::make('clicks')
                            ->label('Jumlah Klik')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Akan terupdate otomatis saat link diklik'),
                        
                        Toggle::make('disabled')
                            ->label('Nonaktifkan Link')
                            ->helperText('Link yang dinonaktifkan tidak akan mengalihkan pengguna')
                            ->default(false),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Section::make('Pratinjau')
                    ->description('Pratinjau link yang akan dibuat')
                    ->schema([
                        Placeholder::make('preview')
                            ->label('URL Pendek')
                            ->content(function ($get) {
                                $shortCode = $get('short_code');
                                if (!$shortCode) return '-';
                                
                                $domain = app()->environment('production')
                                    ? config('domain.production')
                                    : config('domain.local');
                                    
                                return $domain . '/' . $shortCode;
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (callable $get) => $get('short_code')),
            ]);
    }
}
