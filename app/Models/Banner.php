<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'subtitle',
        'tag',
        'image',
        'link_url',
        'button_text',
        'price',
        'position',
        'product_id',
        'category_id',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
        'target_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1200&q=80';
        }
        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }
        return asset('storage/' . ltrim(preg_replace('#^/?storage/#', '', $this->image), '/'));
    }

    public function getTargetUrlAttribute(): string
    {
        if ($this->product_id) {
            return route('products.index', ['highlight' => $this->product_id]);
        }
        if ($this->category_id) {
            return route('products.index', ['category' => $this->category_id]);
        }
        if (!empty($this->link_url)) {
            return $this->link_url;
        }
        return route('products.index');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }
}
