<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrdersController extends Controller
{
    /**
     * Display a listing of customer product orders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = ProductOrder::where('user_id', $user->id)
            ->with(['items.product'])
            ->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->whereNotIn('order_status', ['delivered', 'cancelled']);
            } else {
                $query->where('order_status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('product_name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        $stats = [
            'total_orders' => ProductOrder::where('user_id', $user->id)->count(),
            'active_orders' => ProductOrder::where('user_id', $user->id)->whereNotIn('order_status', ['delivered', 'cancelled'])->count(),
            'delivered_orders' => ProductOrder::where('user_id', $user->id)->where('order_status', 'delivered')->count(),
            'cancelled_orders' => ProductOrder::where('user_id', $user->id)->where('order_status', 'cancelled')->count(),
            'total_spent' => (float) ProductOrder::where('user_id', $user->id)->where('order_status', '!=', 'cancelled')->sum('total_amount'),
        ];

        return Inertia::render('Customer/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Display the specified product order detail & invoice.
     */
    public function show(ProductOrder $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this order.');
        }

        $order->load(['items.product', 'user']);

        return Inertia::render('Customer/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Cancel an eligible order by the customer.
     */
    public function cancel(ProductOrder $order, Request $request)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($order->order_status, ['pending', 'processing'])) {
            return back()->with('error', 'Orders that are already shipped or delivered cannot be cancelled online. Please contact support.');
        }

        // Restore stock
        foreach ($order->items as $item) {
            if ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                    $product->update(['in_stock' => true]);
                }
            }
        }

        $order->update([
            'order_status' => 'cancelled',
        ]);

        return back()->with('success', "Order #{$order->order_number} has been cancelled successfully.");
    }
}
