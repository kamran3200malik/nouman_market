<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'product_orders';

    protected $fillable = [
        'order_number',
        'user_id',
        'seller_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'order_status',
        'tracking_number',
        'courier_name',
    ];

    protected $appends = [
        'formatted_total',
        'formatted_subtotal',
        'status_badge',
        'items_count',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class, 'product_order_id');
    }

    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('order_status', ['delivered', 'cancelled']);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'PKR ' . number_format($this->total_amount, 0);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'PKR ' . number_format($this->subtotal, 0);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->order_status) {
            'pending' => ['label' => 'Pending Confirmation', 'class' => 'bg-amber-100 text-amber-800 border-amber-200'],
            'processing' => ['label' => 'Processing / Packing', 'class' => 'bg-blue-100 text-blue-800 border-blue-200'],
            'shipped' => ['label' => 'Shipped / In Transit', 'class' => 'bg-purple-100 text-purple-800 border-purple-200'],
            'delivered' => ['label' => 'Delivered', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-rose-100 text-rose-800 border-rose-200'],
            default => ['label' => ucfirst($this->order_status), 'class' => 'bg-slate-100 text-slate-800 border-slate-200'],
        };
    }
}
