<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRevenue = ProductOrder::where('payment_status', 'paid')->sum('total_amount') ?? 0;
        $totalOrders = ProductOrder::count();
        $pendingOrders = ProductOrder::where('order_status', 'pending')->count();
        $totalProducts = Product::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalSellers = User::where('role', 'seller')->count();

        $stats = [
            'total_revenue' => (float) $totalRevenue,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_sellers' => $totalSellers,
            'total_categories' => Category::count(),
            'total_reviews' => Review::count(),
        ];

        // Recent Orders
        $recentOrders = ProductOrder::with(['items.product', 'user'])
            ->latest()
            ->limit(8)
            ->get();

        // Top Selling / Trending Products
        $topProducts = Product::active()
            ->orderBy('reviews_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit(6)
            ->get();

        // Low stock products alert
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)
            ->where('is_active', true)
            ->orderBy('stock_quantity', 'asc')
            ->limit(5)
            ->get();

        // Revenue Over Time (Last 7 Days)
        $revenueWeekly = ProductOrder::select(
            DB::raw('DATE(created_at) as label'),
            DB::raw('SUM(total_amount) as total'),
            DB::raw('COUNT(*) as orders_count')
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'revenueWeekly' => $revenueWeekly,
        ]);
    }
}
