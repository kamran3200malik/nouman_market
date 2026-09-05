<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderItem extends Model
{
    use HasFactory;

    protected $table = 'product_order_items';

    protected $fillable = [
        'product_order_id',
        'product_id',
        'seller_id',
        'product_name',
        'product_image',
        'unit_price',
        'quantity',
        'total_price',
    ];

    protected $appends = [
        'formatted_unit_price',
        'formatted_total_price',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'total_price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return 'PKR ' . number_format($this->unit_price, 0);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'PKR ' . number_format($this->total_price, 0);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->product_image) {
            return 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=300&q=80';
        }
        if (str_starts_with($this->product_image, 'http://') || str_starts_with($this->product_image, 'https://')) {
            return $this->product_image;
        }
        return asset('storage/' . ltrim(preg_replace('#^/?storage/#', '', $this->product_image), '/'));
    }
}
