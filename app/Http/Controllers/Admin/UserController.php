<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\ArtistProfile;
use App\Models\City;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the users with filters.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with(['roles', 'artistProfile'])
            ->withCount(['bookings', 'reviews']);

        // Role filter
        if ($request->filled('role') && $request->role !== 'all') {
            $role = $request->role;
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
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
                    ->orWhereHas('artistProfile', function ($sub) use ($search) {
                        $sub->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => User::count(),
            'admins' => User::role('admin')->count(),
            'artists' => User::role('artist')->count(),
            'customers' => User::where(function ($q) {
                $q->role('customer')->orWhere(function ($sub) {
                    $sub->doesntHave('roles')->doesntHave('artistProfile');
                });
            })->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        // Cities & Areas for forms
        $cities = City::active()->with(['areas' => function ($q) {
            $q->active()->orderBy('name');
        }])->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'stats' => $stats,
            'cities' => $cities,
        ]);
    }

    /**
     * Store a newly created user (Admin, Artist, or Customer) in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role', 'customer');

        $rules = [
            'role' => ['required', 'string', Rule::in(['customer', 'artist', 'admin'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'address' => ['nullable', 'string', 'max:500'],
        ];

        if ($role === 'artist') {
            $rules = array_merge($rules, [
                'business_name' => ['required', 'string', 'max:255'],
                'professional_type' => ['required', 'string'],
                'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
                'approval_status' => ['required', 'string', Rule::in(['approved', 'pending', 'suspended', 'rejected'])],
                'billing_model' => ['required', 'string', Rule::in(['commission', 'subscription', 'hybrid', 'free'])],
                'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'home_service_available' => ['nullable', 'boolean'],
            ]);
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $role) {
            // Ensure roles exist in DB
            Role::firstOrCreate(['name' => $role]);

            $cityName = isset($validated['city_id']) ? City::find($validated['city_id'])?->name : null;
            $areaName = isset($validated['area_id']) ? Area::find($validated['area_id'])?->name : null;

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'city' => $cityName,
                'area' => $areaName,
                'address' => $validated['address'] ?? null,
            ]);

            $user->assignRole($role);

            // If creating an Artist, initialize their ArtistProfile
            if ($role === 'artist') {
                $baseSlug = Str::slug($validated['business_name']);
                $slug = $baseSlug;
                $counter = 1;
                while (ArtistProfile::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                ArtistProfile::create([
                    'user_id' => $user->id,
                    'business_name' => $validated['business_name'],
                    'slug' => $slug,
                    'professional_type' => $validated['professional_type'] ?? 'beauty_salon',
                    'years_of_experience' => $validated['years_of_experience'] ?? 1,
                    'city_id' => $validated['city_id'] ?? null,
                    'area_id' => $validated['area_id'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'approval_status' => $validated['approval_status'] ?? 'approved',
                    'approved_at' => ($validated['approval_status'] ?? 'approved') === 'approved' ? now() : null,
                    'is_active' => $validated['is_active'] ?? true,
                    'is_verified' => true,
                    'billing_model' => $validated['billing_model'] ?? 'commission',
                    'commission_rate' => $validated['commission_rate'] ?? 10.00,
                    'home_service_available' => $validated['home_service_available'] ?? false,
                ]);
            }
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
            'is_active' => ['boolean'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'address' => ['nullable', 'string', 'max:500'],
            // Optional artist profile fields
            'business_name' => ['nullable', 'string', 'max:255'],
            'professional_type' => ['nullable', 'string'],
            'approval_status' => ['nullable', 'string', Rule::in(['approved', 'pending', 'suspended', 'rejected'])],
            'billing_model' => ['nullable', 'string', Rule::in(['commission', 'subscription', 'hybrid', 'free'])],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $cityName = isset($validated['city_id']) ? City::find($validated['city_id'])?->name : $user->city;
            $areaName = isset($validated['area_id']) ? Area::find($validated['area_id'])?->name : $user->area;

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
                'is_active' => $validated['is_active'] ?? $user->is_active,
                'address' => $validated['address'] ?? $user->address,
                'city' => $cityName,
                'area' => $areaName,
            ]);

            if ($user->artistProfile && isset($validated['business_name'])) {
                $user->artistProfile->update([
                    'business_name' => $validated['business_name'],
                    'professional_type' => $validated['professional_type'] ?? $user->artistProfile->professional_type,
                    'approval_status' => $validated['approval_status'] ?? $user->artistProfile->approval_status,
                    'billing_model' => $validated['billing_model'] ?? $user->artistProfile->billing_model,
                    'commission_rate' => $validated['commission_rate'] ?? $user->artistProfile->commission_rate,
                    'city_id' => $validated['city_id'] ?? $user->artistProfile->city_id,
                    'area_id' => $validated['area_id'] ?? $user->artistProfile->area_id,
                    'address' => $validated['address'] ?? $user->artistProfile->address,
                    'is_active' => $validated['is_active'] ?? $user->artistProfile->is_active,
                ]);
            }
        });

        return redirect()->back()->with('success', "User updated successfully!");
    }

    /**
     * Reset/Change user's password directly from the Admin Panel.
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

        if ($user->artistProfile) {
            $user->artistProfile->update(['is_active' => $user->is_active]);
        }

        // Send activation notification when account is activated
        if ($user->is_active) {
            try {
                if ($user->artistProfile || $user->hasRole('artist')) {
                    $user->notify(new \App\Notifications\ArtistActivated($user->artistProfile));
                } else {
                    $user->notify(new \App\Notifications\AccountActivated());
                }
            } catch (\Throwable $e) {
                // Log and continue if mail server is unreachable
            }
        }

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

        DB::transaction(function () use ($user) {
            if ($user->artistProfile) {
                $user->artistProfile->delete();
            }
            $user->delete();
        });

        return redirect()->back()->with('success', "User {$userName} has been removed successfully.");
    }
}
