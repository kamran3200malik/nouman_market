<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'entity_name',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by module
     */
    public function scopeForModule(Builder $query, ?string $module): Builder
    {
        if ($module && $module !== 'all') {
            return $query->where('module', $module);
        }
        return $query;
    }

    /**
     * Scope for filtering by action
     */
    public function scopeForAction(Builder $query, ?string $action): Builder
    {
        if ($action && $action !== 'all') {
            if ($action === 'login_logs') {
                return $query->whereIn('action', ['login', 'logout', 'failed_login', 'registered', 'password_reset']);
            }
            if ($action === 'crud_creates') {
                return $query->where('action', 'create');
            }
            if ($action === 'crud_updates') {
                return $query->where('action', 'update');
            }
            if ($action === 'crud_deletes') {
                return $query->where('action', 'delete');
            }
            if ($action === 'status_changes') {
                return $query->where('action', 'status_change');
            }
            return $query->where('action', $action);
        }
        return $query;
    }

    /**
     * Scope for searching across logs
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term && trim($term) !== '') {
            $term = trim($term);
            return $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                  ->orWhere('user_name', 'like', "%{$term}%")
                  ->orWhere('user_email', 'like', "%{$term}%")
                  ->orWhere('entity_name', 'like', "%{$term}%")
                  ->orWhere('ip_address', 'like', "%{$term}%")
                  ->orWhere('module', 'like', "%{$term}%")
                  ->orWhere('action', 'like', "%{$term}%");
            });
        }
        return $query;
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }
}
