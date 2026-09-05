<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'storage_url' => asset('storage'),
            'asset_url' => asset(''),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar,
                    'phone' => $request->user()->phone,
                    'address' => $request->user()->address,
                    'city' => $request->user()->city,
                    'area' => $request->user()->area,
                    'is_active' => (bool)$request->user()->is_active,
                    'is_verified' => (bool)$request->user()->is_verified,
                    'roles' => $request->user()->roles->pluck('name'),
                    'artist_profile' => $request->user()->artistProfile,
                    'unread_notifications_count' => $request->user()->unreadNotifications()->count(),
                    'notifications' => $request->user()->notifications()->latest()->take(10)->get()->map(function ($n) {
                        return [
                            'id' => $n->id,
                            'title' => $n->data['title'] ?? 'Notification',
                            'message' => $n->data['message'] ?? '',
                            'type' => $n->data['type'] ?? 'info',
                            'action_url' => $n->data['action_url'] ?? null,
                            'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,
                            'created_at' => $n->created_at ? $n->created_at->diffForHumans() : '',
                        ];
                    }),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'site_settings' => function () {
                $all = \App\Models\Setting::getAllSettings();
                return [
                    'site_name' => $all['site_name'] ?? 'beautysalonbook',
                    'site_tagline' => $all['site_tagline'] ?? 'Verified Salons & Artist Guild',
                    'site_description' => $all['site_description'] ?? 'The premier verified salon marketplace and automated studio CRM suite empowering beauty artists across Pakistan.',
                    'contact_email' => $all['contact_email'] ?? 'support@beautybook.pk',
                    'contact_phone' => $all['contact_phone'] ?? '+92 (300) 123-4567',
                    'support_whatsapp' => $all['support_whatsapp'] ?? '+923001234567',
                    'office_address' => $all['office_address'] ?? 'Suite 402, Luxury Commercial Hub, Gulberg III, Lahore, Pakistan',
                    'social_instagram' => $all['social_instagram'] ?? 'https://instagram.com/beautybook.pk',
                    'social_facebook' => $all['social_facebook'] ?? 'https://facebook.com/beautybook.pk',
                    'social_tiktok' => $all['social_tiktok'] ?? 'https://tiktok.com/@beautybook.pk',
                    'social_youtube' => $all['social_youtube'] ?? '',
                ];
            },
        ];
    }
}
