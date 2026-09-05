<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;

class LogAuthEvents
{
    /**
     * Handle user login
     */
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            // Update last_login_at timestamp without triggering another updated event log
            try {
                $event->user->timestamps = false;
                $event->user->update(['last_login_at' => now()]);
                $event->user->timestamps = true;
            } catch (\Throwable $e) {}

            ActivityLogger::logLogin($event->user);
        }
    }

    /**
     * Handle user logout
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            ActivityLogger::logLogout($event->user);
        }
    }

    /**
     * Handle failed login
     */
    public function handleFailed(Failed $event): void
    {
        $identifier = $event->credentials['login'] 
            ?? $event->credentials['email'] 
            ?? $event->credentials['username'] 
            ?? $event->credentials['phone'] 
            ?? 'Unknown';

        ActivityLogger::logFailedLogin((string) $identifier, 'Invalid credentials');
    }

    /**
     * Handle user registration
     */
    public function handleRegistered(Registered $event): void
    {
        if ($event->user instanceof User) {
            ActivityLogger::logRegistered($event->user);
        }
    }

    /**
     * Handle password reset
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            ActivityLogger::log('password_reset', 'Auth', "Password was reset for user {$event->user->name} ({$event->user->email})", [
                'user' => $event->user,
                'entity_type' => User::class,
                'entity_id' => $event->user->id,
                'entity_name' => $event->user->name,
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Registered::class => 'handleRegistered',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
