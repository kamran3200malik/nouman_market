<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_id',
        'user_id',
        'author_name',
        'author_email',
        'rating',
        'title',
        'comment',
        'verified_purchase',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
    ];

    protected $appends = ['reviewer_name'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReviewerNameAttribute(): string
    {
        return $this->user?->name ?? $this->author_name ?? 'Verified Buyer';
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
