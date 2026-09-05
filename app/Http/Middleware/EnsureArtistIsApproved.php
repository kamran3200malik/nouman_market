<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureArtistIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If user is admin, allow through
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $artistProfile = $user->artistProfile;

        // 1. If no artist profile exists or status is pending/rejected -> send to pending approval page
        if (!$artistProfile || in_array($artistProfile->approval_status, ['pending', 'rejected'])) {
            if ($request->routeIs('artist.pending-approval') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('artist.pending-approval');
        }

        // If visiting pending approval page when approved/suspended, redirect to dashboard
        if ($request->routeIs('artist.pending-approval')) {
            return redirect()->route('artist.dashboard');
        }

        // 2. If artist is suspended or inactive: Allow viewing dashboard/records (GET),
        // but block all creation, edit, update, and delete actions (POST, PUT, PATCH, DELETE)
        $isRestricted = !$user->is_active || 
                        !$artistProfile->is_active || 
                        $artistProfile->approval_status === 'suspended';

        if ($isRestricted) {
            $allowedMethods = ['GET', 'HEAD', 'OPTIONS'];
            $allowedRoutes = ['logout', 'artist.messages.send', 'artist.messages.mark-read'];

            if (!in_array($request->method(), $allowedMethods) && !$request->routeIs(...$allowedRoutes)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your account is currently suspended or inactive. Creating, editing, or deleting items is restricted.',
                    ], 403);
                }

                return redirect()->back()->with('error', 'Your account is currently suspended or inactive. Creating, editing, and deleting listings is disabled. Please contact support.');
            }
        }

        return $next($request);
    }
}
