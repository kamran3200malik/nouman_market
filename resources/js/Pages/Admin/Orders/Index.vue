<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            pending_orders: 0,
            processing_orders: 0,
            shipped_orders: 0,
            delivered_orders: 0,
            total_revenue: 0,
        }),
    },
    cities: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: 'all',
            city: 'all',
        }),
    },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const city = ref(props.filters.city || 'all');

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
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const applyFilters = () => {
    router.get(
        route('admin.orders.index'),
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
            city: city.value !== 'all' ? city.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([status, city], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    city.value = 'all';
    applyFilters();
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

const getPaymentBadge = (payStatus) => {
    switch (payStatus) {
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

const deleteOrder = (order) => {
    if (confirm(`Are you sure you want to delete order #${order.order_number}? This cannot be undone.`)) {
        router.delete(route('admin.orders.destroy', order.id));
    }
};
</script>

<template>
    <Head title="Orders Management - Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 font-serif">Product Orders</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage customer purchases, dispatch fulfillment, and delivery tracking.</p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                    <div class="text-xs text-gray-400 font-semibold uppercase">Total Orders</div>
                    <div class="text-xl font-bold text-gray-900 mt-1">{{ stats.total_orders }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                    <div class="text-xs text-amber-600 font-semibold uppercase">Pending</div>
                    <div class="text-xl font-bold text-amber-600 mt-1">{{ stats.pending_orders }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                    <div class="text-xs text-blue-600 font-semibold uppercase">Processing</div>
                    <div class="text-xl font-bold text-blue-600 mt-1">{{ stats.processing_orders }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                    <div class="text-xs text-indigo-600 font-semibold uppercase">Shipped</div>
                    <div class="text-xl font-bold text-indigo-600 mt-1">{{ stats.shipped_orders }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                    <div class="text-xs text-emerald-600 font-semibold uppercase">Delivered</div>
                    <div class="text-xl font-bold text-emerald-600 mt-1">{{ stats.delivered_orders }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm text-center">
                    <div class="text-xs text-gray-400 font-semibold uppercase">Revenue</div>
                    <div class="text-base font-bold text-gray-900 mt-1">{{ formatPrice(stats.total_revenue) }}</div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by Order #, Customer Name, Phone, Address..."
                        class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:border-pink-500 focus:ring-pink-500"
                    />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <!-- Status Filter -->
                    <select
                        v-model="status"
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 focus:bg-white focus:border-pink-500"
                    >
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <!-- City Filter -->
                    <select
                        v-if="cities.length > 0"
                        v-model="city"
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 focus:bg-white focus:border-pink-500"
                    >
                        <option value="all">All Cities</option>
                        <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                    </select>

                    <button
                        v-if="search || status !== 'all' || city !== 'all'"
                        @click="resetFilters"
                        class="px-3 py-2 text-xs font-semibold text-gray-500 hover:text-rose-600 transition-colors"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div v-if="orders.data.length === 0" class="py-16 text-center text-gray-400 text-sm">
                    No orders match your filter criteria.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50/80 text-gray-500 uppercase tracking-wider text-xs font-semibold border-b border-gray-100">
                            <tr>
                                <th class="py-3.5 px-4">Order #</th>
                                <th class="py-3.5 px-4">Customer</th>
                                <th class="py-3.5 px-4">Destination</th>
                                <th class="py-3.5 px-4">Items</th>
                                <th class="py-3.5 px-4">Total</th>
                                <th class="py-3.5 px-4">Payment</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-900">#{{ order.order_number }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ formatDate(order.created_at) }}</div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-semibold text-gray-900">{{ order.customer_name }}</div>
                                    <div class="text-xs text-gray-500">{{ order.customer_phone }}</div>
                                </td>
                                <td class="py-4 px-4 text-xs text-gray-600">
                                    <div>{{ order.city || 'Pakistan' }}</div>
                                    <div class="text-gray-400 truncate max-w-[150px]">{{ order.shipping_address }}</div>
                                </td>
                                <td class="py-4 px-4 text-xs font-medium text-gray-700">
                                    {{ order.items?.length || 1 }} product(s)
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-900">{{ formatPrice(order.total_amount) }}</div>
                                    <div class="text-[11px] text-gray-400 uppercase font-semibold">
                                        {{ order.payment_method === 'cod' ? 'Cash on Delivery' : order.payment_method }}
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded text-xs font-semibold border capitalize"
                                        :class="getPaymentBadge(order.payment_status)"
                                    >
                                        {{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border capitalize"
                                        :class="getStatusBadge(order.order_status)"
                                    >
                                        {{ order.order_status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Link
                                            :href="route('admin.orders.show', order.id)"
                                            class="px-3 py-1 rounded-lg bg-pink-50 text-pink-700 hover:bg-pink-100 text-xs font-semibold transition-colors"
                                        >
                                            Details
                                        </Link>
                                        <button
                                            @click="deleteOrder(order)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Delete order"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div v-if="orders.links && orders.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between text-xs">
                    <div class="text-gray-500">
                        Showing {{ orders.from || 0 }} to {{ orders.to || 0 }} of {{ orders.total }} orders
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in orders.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg border font-semibold transition-colors"
                                :class="link.active ? 'bg-pink-600 text-white border-pink-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 rounded-lg border border-gray-100 text-gray-300 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
