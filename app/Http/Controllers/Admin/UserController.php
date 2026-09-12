<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of marketplace users with filters.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with(['roles'])
            ->withCount(['orders', 'reviews']);

        // Role filter
        if ($request->filled('role') && $request->role !== 'all') {
            $role = $request->role;
            $query->where(function ($q) use ($role) {
                $q->where('role', $role)
                  ->orWhereHas('roles', fn($rq) => $rq->where('name', $role));
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('shop_name', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->orWhereHas('roles', fn($q) => $q->where('name', 'admin'))->count(),
            'sellers' => User::where('role', 'seller')->orWhereHas('roles', fn($q) => $q->where('name', 'seller'))->count(),
            'customers' => User::where('role', 'customer')->orWhere(function ($sub) {
                $sub->whereNull('role')->doesntHave('roles');
            })->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created marketplace user (Admin, Seller, or Customer).
     */
    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role', 'customer');

        $rules = [
            'role' => ['required', 'string', Rule::in(['customer', 'seller', 'admin'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'shop_name' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $role) {
            Role::firstOrCreate(['name' => $role]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => $role,
                'is_active' => $validated['is_active'] ?? true,
                'email_verified_at' => now(),
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'] ?? null,
                'shop_name' => $validated['shop_name'] ?? null,
            ]);

            $user->assignRole($role);
        });

        return redirect()->back()->with('success', ucfirst($role) . " account created successfully!");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', Rule::in(['customer', 'seller', 'admin'])],
            'is_active' => ['boolean'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'shop_name' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
                'role' => $validated['role'] ?? $user->role,
                'is_active' => $validated['is_active'] ?? $user->is_active,
                'address' => $validated['address'] ?? $user->address,
                'city' => $validated['city'] ?? $user->city,
                'shop_name' => $validated['shop_name'] ?? $user->shop_name,
            ]);

            if (isset($validated['role'])) {
                Role::firstOrCreate(['name' => $validated['role']]);
                $user->syncRoles([$validated['role']]);
            }
        });

        return redirect()->back()->with('success', "User updated successfully!");
    }

    /**
     * Reset user's password directly from the Admin Panel.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', "Password for {$user->name} has been reset successfully!");
    }

    /**
     * Send a password reset link to user's email.
     */
    public function sendResetLink(User $user): RedirectResponse
    {
        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->back()->with('success', "Password reset link sent to {$user->email}!");
        }

        return redirect()->back()->with('error', __($status));
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', "You cannot deactivate your own administrative account.");
        }

        $user->update(['is_active' => !$user->is_active]);

        $statusText = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "User account {$statusText} successfully.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', "You cannot delete your own account.");
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->back()->with('success', "User {$userName} has been removed successfully.");
    }
}
