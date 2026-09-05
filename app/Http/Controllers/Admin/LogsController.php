<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,email,avatar')
            ->latest('id');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Action filter
        if ($request->filled('action') && $request->action !== 'all') {
            $query->forAction($request->action);
        }

        // Module filter
        if ($request->filled('module') && $request->module !== 'all') {
            $query->forModule($request->module);
        }

        // User role filter
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('user_role', 'like', "%{$request->role}%");
        }

        // User ID filter
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Calculate Overview KPI Stats
        $today = Carbon::today();
        $totalLogs = ActivityLog::count();
        $todayLogs = ActivityLog::whereDate('created_at', $today)->count();
        $todayLogins = ActivityLog::whereDate('created_at', $today)->where('action', 'login')->count();
        $todayFailedLogins = ActivityLog::whereDate('created_at', $today)->where('action', 'failed_login')->count();
        $todayCrud = ActivityLog::whereDate('created_at', $today)->whereIn('action', ['create', 'update', 'delete', 'status_change'])->count();
        $activeUsersToday = ActivityLog::whereDate('created_at', $today)->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        // Dynamic lists for filters
        $modules = ActivityLog::select('module')->distinct()->pluck('module')->filter()->values();
        $actions = ActivityLog::select('action')->distinct()->pluck('action')->filter()->values();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'module', 'role', 'user_id', 'date_from', 'date_to']),
            'stats' => [
                'total_logs' => $totalLogs,
                'today_logs' => $todayLogs,
                'today_logins' => $todayLogins,
                'today_failed_logins' => $todayFailedLogins,
                'today_crud' => $todayCrud,
                'active_users_today' => $activeUsersToday,
            ],
            'modulesList' => $modules,
            'actionsList' => $actions,
        ]);
    }

    public function show(ActivityLog $log)
    {
        $log->load('user:id,name,email,avatar');
        return response()->json($log);
    }

    public function destroy(ActivityLog $log)
    {
        $id = $log->id;
        $desc = $log->description;
        $log->delete();

        // Log deletion of audit record (by Admin)
        ActivityLogger::log('delete', 'System', "Admin deleted Activity Log #{$id} ({$desc})");

        return redirect()->back()->with('success', "Log entry #{$id} deleted successfully.");
    }

    public function clear(Request $request)
    {
        $request->validate([
            'period' => 'required|string|in:all,7_days,30_days,90_days,180_days',
        ]);

        $period = $request->input('period');
        $query = ActivityLog::query();

        if ($period === '7_days') {
            $query->where('created_at', '<', now()->subDays(7));
            $message = 'Logs older than 7 days have been cleared.';
        } elseif ($period === '30_days') {
            $query->where('created_at', '<', now()->subDays(30));
            $message = 'Logs older than 30 days have been cleared.';
        } elseif ($period === '90_days') {
            $query->where('created_at', '<', now()->subDays(90));
            $message = 'Logs older than 90 days have been cleared.';
        } elseif ($period === '180_days') {
            $query->where('created_at', '<', now()->subDays(180));
            $message = 'Logs older than 180 days have been cleared.';
        } else {
            $message = 'All system activity logs have been cleared.';
        }

        $deletedCount = $query->delete();

        ActivityLogger::log('delete', 'System', "Admin purged {$deletedCount} activity logs (Period: {$period})");

        return redirect()->back()->with('success', "{$deletedCount} logs purged. {$message}");
    }

    public function export(Request $request)
    {
        $query = ActivityLog::latest('id');

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('action') && $request->action !== 'all') {
            $query->forAction($request->action);
        }
        if ($request->filled('module') && $request->module !== 'all') {
            $query->forModule($request->module);
        }
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('user_role', 'like', "%{$request->role}%");
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'activity-logs-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            // CSV Header
            fputcsv($handle, ['ID', 'Date & Time', 'Action', 'Module', 'User Name', 'User Email', 'Role', 'IP Address', 'Description', 'Entity Type', 'Entity ID', 'URL', 'Method']);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->action,
                        $log->module,
                        $log->user_name,
                        $log->user_email,
                        $log->user_role,
                        $log->ip_address,
                        $log->description,
                        $log->entity_type,
                        $log->entity_id,
                        $log->url,
                        $log->method,
                    ]);
                }
            });

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
