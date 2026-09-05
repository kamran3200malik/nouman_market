<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});

const form = ref({
    order_status: props.order.order_status,
    payment_status: props.order.payment_status,
    tracking_number: props.order.tracking_number || '',
    courier_name: props.order.courier_name || '',
});

const isUpdating = ref(false);

const formatPrice = (price) => {
    if (price === null || price === undefined || isNaN(price)) return 'PKR 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const updateOrderStatus = () => {
    isUpdating.value = true;
    router.post(route('admin.orders.update-status', props.order.id), form.value, {
        preserveScroll: true,
        onFinish: () => {
            isUpdating.value = false;
        },
    });
};

const getStatusBadge = (orderStatus) => {
    switch (orderStatus) {
        case 'pending':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'processing':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'shipped':
            return 'bg-indigo-100 text-indigo-800 border-indigo-200';
        case 'delivered':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'cancelled':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <Head :title="`Order #${order.order_number} - Admin`" />

    <AdminLayout>
        <div class="space-y-6 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.orders.index')"
                        class="p-2 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-gray-900 font-serif">Order #{{ order.order_number }}</h1>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border capitalize"
                                :class="getStatusBadge(order.order_status)"
                            >
                                {{ order.order_status }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Placed on {{ formatDate(order.created_at) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        onclick="window.print()"
                        class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Invoice
                    </button>
                </div>
            </div>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Order Items & Financials (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Items -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 font-bold text-gray-900 text-sm">
                            Ordered Products ({{ order.items?.length || 0 }})
                        </div>
                        <div class="divide-y divide-gray-50">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="p-6 flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-4 min-w-0">
                                    <img
                                        :src="storageUrl(item.product?.image)"
                                        :alt="item.product_name"
                                        class="w-16 h-16 rounded-xl object-cover bg-gray-50 border border-gray-100 shrink-0"
                                    />
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-gray-900 text-sm truncate">{{ item.product_name }}</h4>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            Quantity: <span class="font-bold text-gray-800">{{ item.quantity }}</span> × {{ formatPrice(item.unit_price) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-gray-900 text-sm">{{ formatPrice(item.subtotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="bg-gray-50/70 p-6 border-t border-gray-100 space-y-2 text-xs">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span class="font-medium text-gray-900">{{ formatPrice(order.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Shipping Fee</span>
                                <span class="font-medium text-gray-900">{{ formatPrice(order.shipping_fee) }}</span>
                            </div>
                            <div v-if="order.discount_amount > 0" class="flex justify-between text-emerald-600">
                                <span>Discount</span>
                                <span>- {{ formatPrice(order.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-200">
                                <span>Grand Total</span>
                                <span class="text-pink-600 font-serif">{{ formatPrice(order.total_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Notes -->
                    <div v-if="order.notes" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Customer Order Notes</h3>
                        <p class="text-sm text-gray-700 italic bg-amber-50/50 p-4 rounded-xl border border-amber-100/70">
                            "{{ order.notes }}"
                        </p>
                    </div>
                </div>

                <!-- Right: Status Control & Shipping Address (1 col) -->
                <div class="space-y-6">
                    <!-- Status & Fulfillment Control -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3">Order Fulfillment & Status</h3>

                        <form @submit.prevent="updateOrderStatus" class="space-y-4 text-xs">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Order Status</label>
                                <select
                                    v-model="form.order_status"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-pink-500"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Payment Status</label>
                                <select
                                    v-model="form.payment_status"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-pink-500"
                                >
                                    <option value="unpaid">Unpaid</option>
                                    <option value="paid">Paid</option>
                                    <option value="refunded">Refunded</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Courier / Logistics Provider</label>
                                <input
                                    v-model="form.courier_name"
                                    type="text"
                                    placeholder="e.g., TCS, Leopards, Trax"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:border-pink-500"
                                />
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Tracking Number</label>
                                <input
                                    v-model="form.tracking_number"
                                    type="text"
                                    placeholder="e.g., TRK-98721948"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:border-pink-500"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="isUpdating"
                                class="w-full py-2.5 px-4 rounded-xl bg-pink-600 hover:bg-pink-500 text-white font-semibold text-xs shadow-md shadow-pink-600/20 transition-all disabled:opacity-50"
                            >
                                {{ isUpdating ? 'Saving...' : 'Update Order Status' }}
                            </button>
                        </form>
                    </div>

                    <!-- Shipping & Recipient Details -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-3 text-xs">
                        <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3">Delivery Address</h3>

                        <div class="space-y-2">
                            <div>
                                <span class="text-gray-400 block text-[11px] uppercase font-semibold">Recipient</span>
                                <span class="font-bold text-gray-900 text-sm">{{ order.customer_name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[11px] uppercase font-semibold">Phone</span>
                                <span class="font-medium text-gray-800">{{ order.customer_phone }}</span>
                            </div>
                            <div v-if="order.customer_email">
                                <span class="text-gray-400 block text-[11px] uppercase font-semibold">Email</span>
                                <span class="font-medium text-gray-800">{{ order.customer_email }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[11px] uppercase font-semibold">City</span>
                                <span class="font-medium text-gray-800">{{ order.city || 'Pakistan' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[11px] uppercase font-semibold">Address</span>
                                <span class="font-medium text-gray-800">{{ order.shipping_address }}</span>
                            </div>
                            <div v-if="order.postal_code">
                                <span class="text-gray-400 block text-[11px] uppercase font-semibold">Postal Code</span>
                                <span class="font-medium text-gray-800">{{ order.postal_code }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
