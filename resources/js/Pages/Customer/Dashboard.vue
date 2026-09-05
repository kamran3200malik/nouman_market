<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    recommendedProducts: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <CustomerLayout>
        <Head title="My Account Dashboard - Luxe Beauty Market" />

        <div class="space-y-8">
            <!-- Welcome Header -->
            <div class="rounded-3xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-pink-200">Customer Account</span>
                    <h1 class="text-2xl sm:text-3xl font-serif font-black">
                        Welcome Back, {{ $page.props.auth?.user?.name }}!
                    </h1>
                    <p class="text-xs text-pink-100">
                        Manage your orders, track deliveries, and discover curated skincare essentials.
                    </p>
                </div>
                <Link
                    :href="route('products.index')"
                    class="px-5 py-2.5 rounded-full bg-white text-rose-700 text-xs font-bold uppercase tracking-wider shadow-md hover:bg-rose-50 transition shrink-0"
                >
                    Browse Store &rarr;
                </Link>
            </div>

            <!-- Stats KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-5 shadow-xs border border-pink-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-medium">Total Orders</span>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_orders || 0 }}</p>
                    </div>
                    <span class="text-2xl">🛍️</span>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-xs border border-pink-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-medium">Active Deliveries</span>
                        <p class="text-2xl font-black text-rose-600 mt-1">{{ stats.active_orders || 0 }}</p>
                    </div>
                    <span class="text-2xl">🚚</span>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-xs border border-pink-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-medium">Wishlist Items</span>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.wishlist_count || 0 }}</p>
                    </div>
                    <span class="text-2xl">❤️</span>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-xs border border-pink-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 font-medium">Total Spent</span>
                        <p class="text-2xl font-black text-slate-900 mt-1">PKR {{ Number(stats.total_spent || 0).toLocaleString() }}</p>
                    </div>
                    <span class="text-2xl">💳</span>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-pink-100 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Recent Orders</h2>
                    <Link :href="route('customer.orders.index')" class="text-xs font-bold text-rose-600 hover:underline">
                        View All Orders &rarr;
                    </Link>
                </div>

                <div v-if="recentOrders.length" class="divide-y divide-slate-100">
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-xs text-slate-900">{{ order.order_number }}</span>
                                <span
                                    class="rounded-md px-2 py-0.5 text-[10px] font-black uppercase border"
                                    :class="order.status_badge?.class || 'bg-slate-100 text-slate-700'"
                                >
                                    {{ order.status_badge?.label || order.order_status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ order.items_count || order.items?.length }} items &bull; Placed on {{ new Date(order.created_at).toLocaleDateString() }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="font-black text-xs text-slate-900">
                                {{ order.formatted_total || `PKR ${Number(order.total_amount).toLocaleString()}` }}
                            </span>
                            <Link
                                :href="route('customer.orders.show', order.id)"
                                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-rose-600 text-white text-xs font-bold transition shadow-xs"
                            >
                                Details
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-xs text-slate-400">
                    No orders placed yet. Explore the shop to place your first luxury order!
                </div>
            </div>

            <!-- Recommended Products -->
            <div v-if="recommendedProducts.length" class="space-y-4">
                <h3 class="text-base font-bold text-slate-900">Recommended for You</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <Link
                        v-for="p in recommendedProducts"
                        :key="p.id"
                        :href="route('products.show', p.slug)"
                        class="p-4 rounded-2xl bg-white border border-pink-100 shadow-xs hover:border-rose-300 transition group flex flex-col justify-between"
                    >
                        <div class="aspect-square rounded-xl overflow-hidden bg-slate-50 mb-3">
                            <img :src="p.image_url" :alt="p.name" class="h-full w-full object-cover group-hover:scale-105 transition" />
                        </div>
                        <span class="text-[10px] font-bold text-rose-600 uppercase">{{ p.brand }}</span>
                        <h4 class="text-xs font-bold text-slate-900 line-clamp-2">{{ p.name }}</h4>
                        <span class="font-black text-xs text-slate-900 mt-2">PKR {{ Number(p.price).toLocaleString() }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
