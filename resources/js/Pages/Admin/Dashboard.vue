<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_orders: 0,
            pending_orders: 0,
            total_products: 0,
            total_customers: 0,
            total_sellers: 0,
            total_categories: 0,
            total_reviews: 0,
        }),
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    topProducts: {
        type: Array,
        default: () => [],
    },
    lowStockProducts: {
        type: Array,
        default: () => [],
    },
    revenueWeekly: {
        type: Array,
        default: () => [],
    },
});

const formatPrice = (price) => {
    if (price === null || price === undefined || isNaN(price)) return 'PKR 0';
    const numPrice = parseFloat(price);
    if (isNaN(numPrice)) return 'PKR 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(numPrice);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'pending':
            return { bg: 'bg-amber-100 text-amber-800 border-amber-200', text: 'Pending' };
        case 'processing':
            return { bg: 'bg-blue-100 text-blue-800 border-blue-200', text: 'Processing' };
        case 'shipped':
            return { bg: 'bg-indigo-100 text-indigo-800 border-indigo-200', text: 'Shipped' };
        case 'delivered':
            return { bg: 'bg-emerald-100 text-emerald-800 border-emerald-200', text: 'Delivered' };
        case 'cancelled':
            return { bg: 'bg-rose-100 text-rose-800 border-rose-200', text: 'Cancelled' };
        default:
            return { bg: 'bg-gray-100 text-gray-800 border-gray-200', text: status };
    }
};

const getPaymentBadge = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'unpaid':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'refunded':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
};
</script>

<template>
    <Head title="Marketplace Dashboard - Admin" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Welcome Header & Quick Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gradient-to-r from-rose-900 via-pink-900 to-purple-950 p-6 sm:p-8 rounded-3xl text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-pink-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-200 border border-rose-400/20 mb-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Store Overview
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-bold font-serif">Marketplace Command Center</h1>
                    <p class="text-rose-200/80 text-sm mt-1 max-w-xl">
                        Monitor live product orders, sales revenue, store inventory, and customer activity in real-time.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2.5 relative z-10">
                    <Link
                        :href="route('admin.products.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-pink-600 hover:bg-pink-500 text-white text-sm font-semibold shadow-lg shadow-pink-600/30 transition-all active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Product
                    </Link>
                    <Link
                        :href="route('admin.orders.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 text-sm font-semibold backdrop-blur-sm transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Manage Orders
                    </Link>
                </div>
            </div>

            <!-- Metric Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Revenue -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Sales Revenue</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ formatPrice(stats.total_revenue) }}</div>
                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                        <span class="text-emerald-600 font-medium">Paid Orders</span> across the marketplace
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Orders</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total_orders }}</div>
                    <div class="text-xs text-amber-600 font-medium mt-1">
                        {{ stats.pending_orders }} orders pending fulfillment
                    </div>
                </div>

                <!-- Total Products -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Products</span>
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total_products }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        Across {{ stats.total_categories }} categories
                    </div>
                </div>

                <!-- Registered Customers -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Customers</span>
                        <div class="w-10 h-10 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total_customers }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ stats.total_reviews }} verified reviews left
                    </div>
                </div>
            </div>

            <!-- Main Content: Recent Orders & Alerts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Orders Table (2 Cols) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Recent Customer Orders</h2>
                            <p class="text-xs text-gray-500">Live order stream and fulfillment queue</p>
                        </div>
                        <Link
                            :href="route('admin.orders.index')"
                            class="text-xs font-semibold text-pink-600 hover:text-pink-700 hover:underline"
                        >
                            View All Orders &rarr;
                        </Link>
                    </div>

                    <div v-if="recentOrders.length === 0" class="py-12 text-center text-gray-400 text-sm">
                        No orders recorded yet.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50/80 text-gray-500 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th class="py-3 px-3 rounded-l-lg">Order</th>
                                    <th class="py-3 px-3">Customer</th>
                                    <th class="py-3 px-3">Items</th>
                                    <th class="py-3 px-3">Total</th>
                                    <th class="py-3 px-3">Status</th>
                                    <th class="py-3 px-3 rounded-r-lg text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="py-3.5 px-3">
                                        <div class="font-bold text-gray-900">#{{ order.order_number }}</div>
                                        <div class="text-[11px] text-gray-400">{{ formatDate(order.created_at) }}</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-medium text-gray-900">{{ order.customer_name }}</div>
                                        <div class="text-[11px] text-gray-400">{{ order.city || 'Pakistan' }}</div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600">
                                        {{ order.items?.length || 1 }} item(s)
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="font-bold text-gray-900">{{ formatPrice(order.total_amount) }}</span>
                                        <div class="mt-0.5">
                                            <span
                                                class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold border"
                                                :class="getPaymentBadge(order.payment_status)"
                                            >
                                                {{ order.payment_status }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                                            :class="getStatusBadge(order.order_status).bg"
                                        >
                                            {{ getStatusBadge(order.order_status).text }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-right">
                                        <Link
                                            :href="route('admin.orders.show', order.id)"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg bg-pink-50 text-pink-700 hover:bg-pink-100 font-semibold transition-colors"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column: Low Stock Alerts & Top Products -->
                <div class="space-y-6">
                    <!-- Low Stock Inventory Alert -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                                Low Stock Alert
                            </h3>
                            <span class="text-xs text-rose-600 font-semibold">Inventory Alert</span>
                        </div>

                        <div v-if="lowStockProducts.length === 0" class="text-xs text-gray-400 py-4 text-center">
                            All products are sufficiently stocked.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="item in lowStockProducts"
                                :key="item.id"
                                class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50/50 border border-rose-100 text-xs"
                            >
                                <div class="truncate mr-2">
                                    <div class="font-semibold text-gray-900 truncate">{{ item.name }}</div>
                                    <div class="text-[11px] text-gray-500">{{ item.brand || item.category || 'Beauty' }}</div>
                                </div>
                                <span class="px-2 py-1 bg-rose-100 text-rose-800 rounded-lg font-bold shrink-0">
                                    {{ item.stock_quantity }} left
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Selling / Featured Products -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-gray-900">Top Rated Products</h3>
                            <Link :href="route('admin.products.index')" class="text-xs text-pink-600 hover:underline">
                                Catalog &rarr;
                            </Link>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="product in topProducts"
                                :key="product.id"
                                class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 transition-colors"
                            >
                                <img
                                    :src="storageUrl(product.image)"
                                    :alt="product.name"
                                    class="w-11 h-11 rounded-lg object-cover bg-gray-100 border border-gray-100 shrink-0"
                                />
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-semibold text-gray-900 truncate">{{ product.name }}</h4>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                        <span class="text-amber-500 font-bold">★ {{ product.rating }}</span>
                                        <span>•</span>
                                        <span>{{ formatPrice(product.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
