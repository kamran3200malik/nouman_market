<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProductOrderController extends Controller
{
    /**
     * Store a newly placed product order from the marketplace checkout.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullName' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'paymentMethod' => 'required|string|in:cash_on_delivery,cod,card,bank',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.name' => 'required|string|max:255',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.brand' => 'nullable|string',
            'items.*.image' => 'nullable|string',
        ]);

        try {
            $user = Auth::user() ?? $request->user();
            $userId = $user?->id;
            $userEmail = $user?->email;

            $order = DB::transaction(function () use ($validated, $userId, $userEmail) {
                $subtotal = 0;
                $lineItemsData = [];

                foreach ($validated['items'] as $itemData) {
                    $itemTotal = $itemData['price'] * $itemData['quantity'];
                    $subtotal += $itemTotal;

                    // Deduct stock if product exists in DB
                    $product = Product::find($itemData['id']);
                    $sellerId = $product?->user_id;

                    if ($product) {
                        $newStock = max(0, $product->stock_quantity - $itemData['quantity']);
                        $product->stock_quantity = $newStock;
                        if ($newStock <= 0) {
                            $product->in_stock = false;
                        }
                        $product->save();
                    }

                    $lineItemsData[] = [
                        'product_id' => $itemData['id'],
                        'seller_id' => $sellerId,
                        'product_name' => $itemData['name'],
                        'product_image' => $itemData['image'] ?? ($product?->image ?? null),
                        'unit_price' => $itemData['price'],
                        'quantity' => $itemData['quantity'],
                        'total_price' => $itemTotal,
                    ];
                }

                // Dynamic Shipping Calculation from Admin Settings
                $standardFee = (float) Setting::getValue('shipping_fee_standard', 250);
                $freeThreshold = (float) Setting::getValue('shipping_free_threshold', 5000);
                $freeEnabled = Setting::getValue('shipping_free_enabled', '1') === '1';

                $shippingFee = ($subtotal === 0 || ($freeEnabled && $subtotal >= $freeThreshold)) ? 0 : $standardFee;
                $totalAmount = $subtotal + $shippingFee;

                $paymentMethod = in_array($validated['paymentMethod'], ['cod', 'cash_on_delivery']) ? 'cash_on_delivery' : 'card';

                $order = ProductOrder::create([
                    'user_id' => $userId,
                    'customer_name' => $validated['fullName'],
                    'customer_phone' => $validated['phone'],
                    'customer_email' => $validated['email'] ?? ($userEmail ?? 'guest@luxemarket.pk'),
                    'city' => $validated['city'],
                    'shipping_address' => $validated['address'],
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => 0,
                    'total_amount' => $totalAmount,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentMethod === 'card' ? 'paid' : 'unpaid',
                    'order_status' => 'pending',
                ]);

                foreach ($lineItemsData as $lineItem) {
                    $lineItem['product_order_id'] = $order->id;
                    ProductOrderItem::create($lineItem);
                }

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Your order has been placed successfully!',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'formatted_total' => $order->formatted_total,
                    'items_count' => $order->items_count,
                    'customer_name' => $order->customer_name,
                    'city' => $order->city,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error processing product order checkout: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete your order at this moment. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function success(string $orderNumber)
    {
        $order = ProductOrder::where('order_number', $orderNumber)
            ->with(['items.product'])
            ->firstOrFail();

        return Inertia::render('Products/OrderSuccess', [
            'order' => $order,
        ]);
    }
}
