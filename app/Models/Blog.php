<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Blog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'image',
        'gallery_images',
        'author_name',
        'tags',
        'read_time',
        'is_published',
        'is_featured',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'views_count',
    ];

    protected $appends = [
        'image_url',
        'gallery_images_data',
        'formatted_date',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'tags' => 'array',
            'gallery_images' => 'array',
            'read_time' => 'integer',
            'views_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the full URL for the primary cover image
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return Storage::url($this->image);
    }

    /**
     * Resolve full URLs for all area gallery images
     */
    public function getGalleryImagesDataAttribute(): array
    {
        $images = $this->gallery_images ?? [];
        if (!is_array($images)) {
            return [];
        }

        return array_map(function ($item) {
            if (is_string($item)) {
                $url = (str_starts_with($item, 'http://') || str_starts_with($item, 'https://')) ? $item : Storage::url($item);
                return [
                    'url' => $url,
                    'path' => $item,
                    'caption' => '',
                    'area' => 'gallery',
                ];
            }

            $path = $item['path'] ?? $item['url'] ?? '';
            $url = $item['url'] ?? '';
            if (empty($url) && !empty($path)) {
                $url = (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) ? $path : Storage::url($path);
            }

            return [
                'url' => $url,
                'path' => $path,
                'caption' => $item['caption'] ?? '',
                'area' => $item['area'] ?? 'gallery',
            ];
        }, $images);
    }

    /**
     * Formatted published or created date
     */
    public function getFormattedDateAttribute(): string
    {
        $date = $this->published_at ?? $this->created_at;
        return $date ? $date->format('M d, Y') : '';
    }

    /**
     * Calculate estimated reading time in minutes based on content
     */
    public static function estimateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = (int) ceil($wordCount / 200);
        return max(1, $minutes);
    }

    /**
     * Scope: Published only
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: Featured spotlight
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Search
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('excerpt', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%")
              ->orWhere('author_name', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    /**
     * Increment view counter safely
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }
}
