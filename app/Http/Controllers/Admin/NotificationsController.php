<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ArtistProfile;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total_customers' => User::role('customer')->count(),
            'total_artists' => ArtistProfile::where('approval_status', 'approved')->where('is_active', true)->count(),
            'subscribed_artists' => ArtistProfile::where('billing_model', 'subscription')->whereIn('subscription_status', ['active', 'trial'])->count(),
            'total_users' => User::count(),
            'total_notifications_sent' => DB::table('notifications')->count(),
        ];

        // Fetch recent notifications sent across the system
        $recentNotifications = DB::table('notifications')
            ->select('id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($n) {
                $data = json_decode($n->data, true) ?: [];
                $user = User::select('id', 'name', 'email', 'avatar')->find($n->notifiable_id);
                return [
                    'id' => $n->id,
                    'type' => class_basename($n->type),
                    'title' => $data['title'] ?? 'System Notification',
                    'message' => $data['message'] ?? '',
                    'action_url' => $data['action_url'] ?? null,
                    'severity' => $data['type'] ?? 'info',
                    'recipient' => $user ? [
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ] : null,
                    'is_read' => !empty($n->read_at),
                    'created_at' => $n->created_at,
                ];
            });

        return Inertia::render('Admin/Notifications/Index', [
            'stats' => $stats,
            'recentNotifications' => $recentNotifications,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.notifications.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'target_type' => 'required|in:all,customers,artists,subscribers',
            'category' => 'nullable|string|in:announcement,promotion,maintenance,alert',
        ]);

        $usersQuery = User::query();
        
        if ($request->target_type === 'customers') {
            $usersQuery->role('customer');
        } elseif ($request->target_type === 'artists') {
            $usersQuery->role('artist');
        } elseif ($request->target_type === 'subscribers') {
            $usersQuery->whereHas('artistProfile', function ($q) {
                $q->where('billing_model', 'subscription')
                  ->whereIn('subscription_status', ['active', 'trial']);
            });
        }

        $recipients = $usersQuery->get();
        $count = $recipients->count();

        foreach ($recipients as $user) {
            $user->notify(new SystemNotification($request->title, $request->message));
        }

        return back()->with('success', "Notification broadcast sent successfully to {$count} recipient(s).");
    }
}
