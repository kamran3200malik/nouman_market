<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = User::where(function ($q) {
            $q->where('role', 'customer')
              ->orWhere(function ($sub) {
                  $sub->whereNull('role')->orWhere('role', '!=', 'admin');
              });
        });

        $query = (clone $baseQuery)
            ->withCount(['orders', 'reviews', 'favorites']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'new_this_month' => (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function show(User $customer)
    {
        $customer->load([
            'orders' => function ($q) {
                $q->with('items.product')->latest()->limit(15);
            },
            'reviews.product',
            'favorites.product',
        ]);

        $totalSpent = $customer->orders()->where('payment_status', 'paid')->sum('total_amount') ?? 0;

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
            'totalSpent' => (float) $totalSpent,
        ]);
    }

    public function updateStatus(Request $request, User $customer)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $customer->update([
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer status updated successfully.');
    }

    public function destroy(User $customer)
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
