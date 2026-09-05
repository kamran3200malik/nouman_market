<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'is_active',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'bio',
        'shop_name',
        'shop_slug',
        'shop_logo',
        'shop_banner',
        'shop_description',
        'seller_status',
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
            'is_active' => 'boolean',
        ];
    }

    protected $appends = ['avatar_url'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductOrder::class, 'user_id');
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(ProductOrder::class, 'seller_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->hasRole('admin');
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller' || $this->hasRole('seller');
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' || $this->hasRole('customer');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $val = $this->avatar;
        if (!$val) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=f43f5e&color=fff&bold=true';
        }
        if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
            return $val;
        }
        return asset('storage/' . ltrim(preg_replace('#^/?storage/#', '', $val), '/'));
    }
}
