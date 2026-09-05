<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Get a setting value by key with optional fallback default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting || is_null($setting->value) || $setting->value === '') {
            return $default;
        }
        return $setting->value;
    }

    /**
     * Get all settings key-value dictionary.
     */
    public static function getAllSettings(): array
    {
        try {
            return static::pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
