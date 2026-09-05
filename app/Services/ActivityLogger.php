<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * General purpose activity logger
     */
    public static function log(string $action, string $module, string $description, array $options = []): ?ActivityLog
    {
        try {
            $user = $options['user'] ?? Auth::user();
            $userId = $user?->id ?? $options['user_id'] ?? null;
            
            $userName = $options['user_name'] ?? ($user ? ($user->name ?? 'User #' . $user->id) : ($userId ? 'User #' . $userId : 'System / Guest'));
            $userEmail = $options['user_email'] ?? $user?->email ?? null;
            
            // Determine role
            $userRole = $options['user_role'] ?? null;
            if (!$userRole && $user) {
                if (method_exists($user, 'roles') && $user->roles()->exists()) {
                    $userRole = $user->roles->pluck('name')->implode(', ');
                } elseif (isset($user->role)) {
                    $userRole = $user->role;
                } else {
                    $userRole = 'user';
                }
            }
            if (!$userRole) {
                $userRole = 'guest';
            }

            return ActivityLog::create([
                'user_id'     => $userId,
                'user_name'   => $userName,
                'user_email'  => $userEmail,
                'user_role'   => $userRole,
                'action'      => $action,
                'module'      => $module,
                'entity_type' => $options['entity_type'] ?? null,
                'entity_id'   => isset($options['entity_id']) ? (string) $options['entity_id'] : null,
                'entity_name' => $options['entity_name'] ?? null,
                'description' => $description,
                'old_values'  => $options['old_values'] ?? null,
                'new_values'  => $options['new_values'] ?? null,
                'ip_address'  => $options['ip_address'] ?? Request::ip(),
                'user_agent'  => $options['user_agent'] ?? Request::userAgent(),
                'url'         => $options['url'] ?? (Request::isMethod('cli') ? 'CLI' : Request::fullUrl()),
                'method'      => $options['method'] ?? (Request::isMethod('cli') ? 'CLI' : Request::method()),
            ]);
        } catch (\Throwable $e) {
            // Never break runtime execution if logging fails
            \Illuminate\Support\Facades\Log::error('ActivityLogger failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log user login
     */
    public static function logLogin(User $user): ?ActivityLog
    {
        $roleName = 'User';
        if (method_exists($user, 'roles') && $user->roles()->exists()) {
            $roleName = $user->roles->pluck('name')->implode(', ');
        } elseif (isset($user->role)) {
            $roleName = ucfirst($user->role);
        }

        return self::log('login', 'Auth', "User {$user->name} ({$roleName}) logged in successfully.", [
            'user' => $user,
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'entity_name' => $user->name,
            'new_values' => [
                'email' => $user->email,
                'username' => $user->username ?? null,
                'role' => $roleName,
                'time' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Log user logout
     */
    public static function logLogout(User $user): ?ActivityLog
    {
        return self::log('logout', 'Auth', "User {$user->name} logged out.", [
            'user' => $user,
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'entity_name' => $user->name,
        ]);
    }

    /**
     * Log failed login attempt
     */
    public static function logFailedLogin(string $identifier, ?string $reason = null): ?ActivityLog
    {
        $desc = "Failed login attempt for identifier: {$identifier}";
        if ($reason) {
            $desc .= " ({$reason})";
        }

        return self::log('failed_login', 'Auth', $desc, [
            'user_name' => 'Unknown Guest',
            'user_email' => filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : null,
            'user_role' => 'guest',
            'new_values' => [
                'attempted_identifier' => $identifier,
                'reason' => $reason,
                'time' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Log user registration
     */
    public static function logRegistered(User $user): ?ActivityLog
    {
        return self::log('registered', 'Auth', "New user registered: {$user->name} ({$user->email})", [
            'user' => $user,
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'entity_name' => $user->name,
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'username' => $user->username ?? null,
            ]
        ]);
    }

    /**
     * Log model lifecycle events (create, update, delete)
     */
    public static function logModelEvent(string $event, Model $model, ?string $description = null): ?ActivityLog
    {
        // Ignore ActivityLog model itself to prevent infinite loop
        if ($model instanceof ActivityLog) {
            return null;
        }

        $moduleName = class_basename($model);
        $entityName = self::extractEntityName($model);
        $entityId = (string) $model->getKey();

        $sensitiveFields = ['password', 'remember_token', 'token', 'two_factor_secret', 'two_factor_recovery_codes'];

        $oldValues = null;
        $newValues = null;

        if ($event === 'create') {
            $action = 'create';
            $desc = $description ?: "Created {$moduleName}: {$entityName}";
            $rawAttributes = $model->getAttributes();
            foreach ($sensitiveFields as $field) {
                unset($rawAttributes[$field]);
            }
            $newValues = $rawAttributes;
        } elseif ($event === 'update') {
            $dirty = $model->getDirty();
            foreach ($sensitiveFields as $field) {
                unset($dirty[$field]);
            }

            if (empty($dirty)) {
                return null; // No meaningful changes to log
            }

            // Check if it's primarily a status change
            $isStatusOnly = count($dirty) === 1 && (isset($dirty['status']) || isset($dirty['is_active']) || isset($dirty['is_approved']) || isset($dirty['approval_status']));
            $action = $isStatusOnly ? 'status_change' : 'update';

            $desc = $description ?: ($isStatusOnly ? "Updated status for {$moduleName}: {$entityName}" : "Updated {$moduleName}: {$entityName}");

            $old = [];
            $new = [];
            foreach ($dirty as $key => $newValue) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $newValue;
            }
            $oldValues = $old;
            $newValues = $new;
        } elseif ($event === 'delete') {
            $action = 'delete';
            $desc = $description ?: "Deleted {$moduleName}: {$entityName}";
            $rawAttributes = $model->getOriginal();
            foreach ($sensitiveFields as $field) {
                unset($rawAttributes[$field]);
            }
            $oldValues = $rawAttributes;
        } else {
            $action = $event;
            $desc = $description ?: ucfirst($event) . " {$moduleName}: {$entityName}";
        }

        return self::log($action, $moduleName, $desc, [
            'entity_type' => get_class($model),
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Get human readable entity name for a model
     */
    protected static function extractEntityName(Model $model): string
    {
        if (isset($model->name) && is_string($model->name)) {
            return $model->name;
        }
        if (isset($model->title) && is_string($model->title)) {
            return $model->title;
        }
        if (isset($model->business_name) && is_string($model->business_name)) {
            return $model->business_name;
        }
        if (isset($model->booking_number) && is_string($model->booking_number)) {
            return 'Booking #' . $model->booking_number;
        }
        if (isset($model->order_number) && is_string($model->order_number)) {
            return 'Order #' . $model->order_number;
        }
        if (isset($model->key) && is_string($model->key)) {
            return $model->key;
        }
        if (isset($model->email) && is_string($model->email)) {
            return $model->email;
        }

        return class_basename($model) . ' #' . $model->getKey();
    }
}
