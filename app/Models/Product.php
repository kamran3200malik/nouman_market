<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'category',
        'brand',
        'short_description',
        'description',
        'image',
        'price',
        'original_price',
        'cost_price',
        'rating',
        'reviews_count',
        'stock_quantity',
        'in_stock',
        'badge',
        'short_features',
        'usage_instructions',
        'ingredients',
        'is_featured',
        'is_trending',
        'is_active',
        'approval_status',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
        'discount_percentage',
        'seller_name',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'stock_quantity' => 'integer',
            'in_stock' => 'boolean',
            'short_features' => 'array',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function getSellerNameAttribute(): string
    {
        return $this->user?->shop_name ?? $this->user?->name ?? 'Luxe Market Official';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('approval_status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true)->where('stock_quantity', '>', 0);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80';
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . ltrim(preg_replace('#^/?storage/#', '', $this->image), '/'));
    }

    public function getDiscountPercentageAttribute(): int
    {
        if ($this->original_price && $this->price && $this->original_price > $this->price) {
            return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
        }
        return 0;
    }
}
