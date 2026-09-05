<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Product Orders & Marketplace statistics for Customer
        $recentOrders = ProductOrder::where('user_id', $user->id)
            ->with(['items.product'])
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'total_orders' => ProductOrder::where('user_id', $user->id)->count(),
            'active_orders' => ProductOrder::where('user_id', $user->id)->whereNotIn('order_status', ['delivered', 'cancelled'])->count(),
            'delivered_orders' => ProductOrder::where('user_id', $user->id)->where('order_status', 'delivered')->count(),
            'total_spent' => (float) ProductOrder::where('user_id', $user->id)->where('payment_status', 'paid')->sum('total_amount'),
            'wishlist_count' => Favorite::where('user_id', $user->id)->count(),
            'reviews_count' => Review::where('user_id', $user->id)->count(),
        ];

        $recommendedProducts = Product::active()
            ->trending()
            ->latest()
            ->limit(4)
            ->get();

        return Inertia::render('Customer/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'recommendedProducts' => $recommendedProducts,
        ]);
    }

    public function wishlist()
    {
        $user = Auth::user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['product'])
            ->latest()
            ->paginate(12);

        return Inertia::render('Customer/Wishlist', [
            'favorites' => $favorites,
        ]);
    }

    public function reviews()
    {
        $user = Auth::user();

        $reviews = Review::where('user_id', $user->id)
            ->with(['product'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Reviews', [
            'reviews' => $reviews,
        ]);
    }

    public function profile()
    {
        return Inertia::render('Customer/Profile', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $user->update($request->only(['name', 'email', 'phone', 'city', 'address']));

        return back()->with('success', 'Profile updated successfully.');
    }
}
