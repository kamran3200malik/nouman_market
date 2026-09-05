<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductOrderController extends Controller
{
    /**
     * Display a listing of product marketplace orders for Admin CRM.
     */
    public function index(Request $request)
    {
        $query = ProductOrder::with(['items.product', 'user:id,name,email']);

        // Search by order number, customer name, phone, or address
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('order_status', $status);
            }
        }

        // City Filter
        if ($city = $request->input('city')) {
            if ($city !== 'all') {
                $query->where('city', $city);
            }
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        // KPI Summary Stats
        $stats = [
            'total_orders' => ProductOrder::count(),
            'pending_orders' => ProductOrder::where('order_status', 'pending')->count(),
            'processing_orders' => ProductOrder::where('order_status', 'processing')->count(),
            'shipped_orders' => ProductOrder::where('order_status', 'shipped')->count(),
            'delivered_orders' => ProductOrder::where('order_status', 'delivered')->count(),
            'total_revenue' => (float) ProductOrder::whereNotIn('order_status', ['cancelled'])->sum('total_amount'),
        ];

        // Available Cities
        $cities = ProductOrder::distinct()->pluck('city')->filter()->values();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'cities' => $cities,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
                'city' => $request->input('city', 'all'),
            ],
        ]);
    }

    /**
     * Show order details.
     */
    public function show(ProductOrder $order)
    {
        $order->load(['items.product', 'user']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Update the specified product order status and tracking details.
     */
    public function updateStatus(Request $request, ProductOrder $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'required|string|in:unpaid,paid,refunded,failed',
            'tracking_number' => 'nullable|string|max:255',
            'courier_name' => 'nullable|string|max:255',
        ]);

        $order->update($validated);

        return back()->with('success', "Order #{$order->order_number} updated to " . ucfirst($validated['order_status']) . '.');
    }

    /**
     * Delete an order record.
     */
    public function destroy(ProductOrder $order)
    {
        $orderNumber = $order->order_number;
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', "Order #{$orderNumber} deleted successfully.");
    }
}
