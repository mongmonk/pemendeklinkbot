<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;

class Admin extends Authenticatable implements FilamentUser, HasAvatar
{
    use Notifiable;

    protected $fillable = [
        'telegram_user_id',
        'username',
        'password_hash',
        'email',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function links(): HasMany
    {
        return $this->hasMany(Link::class, 'telegram_user_id', 'telegram_user_id');
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $name = trim(collect([$this->username])->implode(' '));
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get total clicks from all links
     */
    public function getTotalClicksAttribute(): int
    {
        return $this->links()->sum('clicks');
    }

    /**
     * Get total links count
     */
    public function getTotalLinksAttribute(): int
    {
        return $this->links()->count();
    }

    /**
     * Get today's links
     */
    public function getTodayLinksAttribute(): int
    {
        return $this->links()->whereDate('created_at', today())->count();
    }

    /**
     * Get most popular link
     */
    public function getMostPopularLinkAttribute(): ?Link
    {
        return $this->links()->orderBy('clicks', 'desc')->first();
    }

    /**
     * Scope active admins
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by username
     */
    public function scopeByUsername($query, string $username)
    {
        return $query->where('username', 'like', "%{$username}%");
    }
}
