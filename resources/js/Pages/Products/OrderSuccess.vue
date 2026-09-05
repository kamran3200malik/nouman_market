<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    order: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <PublicLayout>
        <Head title="Order Confirmed - Luxe Beauty Market" />

        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Success Card -->
            <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-pink-100 text-center space-y-6">
                <!-- Checkmark Icon -->
                <div class="h-20 w-20 rounded-full bg-emerald-50 border-4 border-emerald-200 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-md">
                    ✓
                </div>

                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-0.5 text-[11px] font-black uppercase tracking-wider text-emerald-800">
                        <span>ORDER PLACED SUCCESSFULLY</span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-serif font-black text-slate-900">
                        Thank You for Your Order!
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                        Your order has been received and is being prepared for express delivery. We've sent a confirmation to your email.
                    </p>
                </div>

                <!-- Order Reference Number -->
                <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/80 inline-block text-left w-full max-w-md">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-500 font-medium">Order Reference:</span>
                        <span class="font-mono font-bold text-rose-600 text-sm">{{ order.order_number }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">Payment Method:</span>
                        <span class="font-bold text-slate-900 uppercase text-[11px]">{{ order.payment_method }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs mt-1">
                        <span class="text-slate-500 font-medium">Total Amount:</span>
                        <span class="font-black text-slate-900 text-sm">PKR {{ Number(order.total_amount).toLocaleString() }}</span>
                    </div>
                </div>

                <!-- Items Ordered Summary -->
                <div class="space-y-3 text-left border-t border-slate-100 pt-6">
                    <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Ordered Items</h3>
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="py-3 flex items-center justify-between gap-4"
                        >
                            <div class="flex items-center gap-3">
                                <img
                                    :src="item.image_url"
                                    :alt="item.product_name"
                                    class="h-12 w-12 rounded-xl object-cover bg-slate-50 shrink-0 border border-slate-100"
                                />
                                <div>
                                    <h4 class="font-bold text-xs text-slate-900">{{ item.product_name }}</h4>
                                    <span class="text-[11px] text-slate-400">Qty: {{ item.quantity }} × PKR {{ Number(item.unit_price).toLocaleString() }}</span>
                                </div>
                            </div>
                            <span class="font-bold text-xs text-slate-900 shrink-0">
                                PKR {{ Number(item.total_price).toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-left text-xs space-y-1">
                    <span class="font-bold text-slate-900">Shipping Address:</span>
                    <p class="text-slate-600">{{ order.customer_name }} ({{ order.customer_phone }})</p>
                    <p class="text-slate-600">{{ order.shipping_address }}, {{ order.city }}</p>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
                    <Link
                        :href="route('products.index')"
                        class="px-6 py-3 rounded-full bg-slate-900 hover:bg-rose-600 text-white font-bold text-xs uppercase tracking-wider shadow-md transition"
                    >
                        Continue Shopping
                    </Link>
                    <Link
                        :href="route('customer.orders.index')"
                        class="px-6 py-3 rounded-full border border-slate-300 hover:bg-slate-100 text-slate-800 font-bold text-xs uppercase tracking-wider transition"
                    >
                        View My Orders
                    </Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
