<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'username',
        'avatar',
        'rating',
        'reputation',
        'is_banned',
        'is_frozen',
        'banned_at',
        'ban_reason',
        'last_activity_at',
        'phone',
        'bio',
        'settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rating' => 'decimal:2',
            'is_banned' => 'boolean',
            'is_frozen' => 'boolean',
            'banned_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    // Relationships
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function ordersAsBuyer(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function ordersAsSeller(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller' || $this->role === 'admin';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer' || $this->role === 'admin';
    }

    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() && !$this->is_banned && !$this->is_frozen;
    }

    public function isActive(): bool
    {
        return !$this->is_banned && !$this->is_frozen;
    }

    public function ban(string $reason): void
    {
        $this->update([
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => $reason,
        ]);
    }

    public function unban(): void
    {
        $this->update([
            'is_banned' => false,
            'banned_at' => null,
            'ban_reason' => null,
        ]);
    }

    public function freeze(): void
    {
        $this->update(['is_frozen' => true]);
    }

    public function unfreeze(): void
    {
        $this->update(['is_frozen' => false]);
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
