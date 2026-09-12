<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';

const props = defineProps({
    orders: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            active_orders: 0,
            delivered_orders: 0,
            cancelled_orders: 0,
            total_spent: 0,
        })
    },
    filters: {
        type: Object,
        default: () => ({ status: 'all', search: '' })
    }
});

const searchInput = ref(props.filters?.search || '');
const activeStatus = ref(props.filters?.status || 'all');
const cancellingOrderId = ref(null);
const cancelReason = ref('');
const isCancelling = ref(false);

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(price || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const filterByStatus = (status) => {
    activeStatus.value = status;
    applyFilter();
};

const applyFilter = () => {
    router.get(route('customer.orders.index'), {
        status: activeStatus.value !== 'all' ? activeStatus.value : undefined,
        search: searchInput.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'pending':
            return { label: 'Pending Approval', bg: 'bg-amber-50 text-amber-800 border-amber-200', dot: 'bg-amber-500' };
        case 'confirmed':
            return { label: 'Order Confirmed', bg: 'bg-blue-50 text-blue-800 border-blue-200', dot: 'bg-blue-500' };
        case 'processing':
            return { label: 'Packing Order', bg: 'bg-purple-50 text-purple-800 border-purple-200', dot: 'bg-purple-500' };
        case 'dispatched':
            return { label: 'Out for Delivery', bg: 'bg-indigo-50 text-indigo-800 border-indigo-200', dot: 'bg-indigo-500' };
        case 'delivered':
            return { label: 'Delivered', bg: 'bg-emerald-50 text-emerald-800 border-emerald-200', dot: 'bg-emerald-500' };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-rose-50 text-rose-800 border-rose-200', dot: 'bg-rose-500' };
        default:
            return { label: status, bg: 'bg-slate-50 text-slate-800 border-slate-200', dot: 'bg-slate-500' };
    }
};

const getItemStatusBadge = (status) => {
    switch (status) {
        case 'pending':
            return { label: 'Pending', bg: 'bg-amber-100/80 text-amber-800 border-amber-200' };
        case 'confirmed':
            return { label: 'Confirmed', bg: 'bg-blue-100/80 text-blue-800 border-blue-200' };
        case 'processing':
            return { label: 'Packing', bg: 'bg-purple-100/80 text-purple-800 border-purple-200' };
        case 'dispatched':
            return { label: 'Dispatched', bg: 'bg-indigo-100/80 text-indigo-800 border-indigo-200' };
        case 'delivered':
            return { label: 'Delivered', bg: 'bg-emerald-100/80 text-emerald-800 border-emerald-200' };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-rose-100/80 text-rose-800 border-rose-200' };
        default:
            return { label: status || 'Pending', bg: 'bg-slate-100 text-slate-800 border-slate-200' };
    }
};

const openCancelModal = (orderId) => {
    cancellingOrderId.value = orderId;
    cancelReason.value = 'Changed my mind / ordered by mistake';
};

const submitCancel = () => {
    if (!cancellingOrderId.value) return;
    isCancelling.value = true;
    router.post(route('customer.orders.cancel', cancellingOrderId.value), {
        reason: cancelReason.value
    }, {
        onFinish: () => {
            isCancelling.value = false;
            cancellingOrderId.value = null;
        }
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="My Product Orders - VIP Client Lounge" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- 1. VIP HERO HEADER -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-pink-950 to-[#190615] p-6 sm:p-8 text-white shadow-xl border border-pink-900/40">
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-rose-600/10 blur-3xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 h-64 w-64 rounded-full bg-pink-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-pink-500/20 text-pink-300 border border-pink-500/40 shadow-xs">
                            <span>🛍️</span>
                            <span>Authentic Cosmetics & Care</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold font-serif tracking-tight text-white flex items-center gap-2">
                            <span>My Product Orders</span>
                            <span class="text-xl">📦</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-pink-200/80 max-w-xl leading-relaxed">
                            Track real-time courier shipment status of your cosmetics, serums, fragrances, and skincare kits.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link
                            :href="route('products.index')"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 text-white text-xs sm:text-sm font-bold shadow-lg shadow-pink-950/40 hover:scale-102 transition-all cursor-pointer"
                        >
                            <span>🛍️</span>
                            <span>Shop Beauty Catalog</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. ORDER METRIC STATS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Stat 1 -->
                <div class="p-5 rounded-3xl bg-white border border-pink-100/90 shadow-xs hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Orders</span>
                        <div class="h-10 w-10 rounded-2xl bg-pink-50 border border-pink-200/80 flex items-center justify-center text-lg shadow-xs">
                            <span>📦</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl sm:text-3xl font-bold text-slate-900">{{ stats.total_orders }}</div>
                        <p class="text-[11px] font-semibold text-slate-400 mt-0.5">Lifetime purchases</p>
                    </div>
                </div>

                <!-- Stat 2 -->
                <div class="p-5 rounded-3xl bg-white border border-pink-100/90 shadow-xs hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Shipments</span>
                        <div class="h-10 w-10 rounded-2xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-lg shadow-xs">
                            <span>🚚</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl sm:text-3xl font-bold text-indigo-700">{{ stats.active_orders }}</div>
                        <p class="text-[11px] font-semibold text-indigo-400 mt-0.5">In transit or packing</p>
                    </div>
                </div>

                <!-- Stat 3 -->
                <div class="p-5 rounded-3xl bg-white border border-pink-100/90 shadow-xs hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Delivered</span>
                        <div class="h-10 w-10 rounded-2xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-lg shadow-xs">
                            <span>✓</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl sm:text-3xl font-bold text-emerald-700">{{ stats.delivered_orders }}</div>
                        <p class="text-[11px] font-semibold text-emerald-400 mt-0.5">Successfully received</p>
                    </div>
                </div>

                <!-- Stat 4 -->
                <div class="p-5 rounded-3xl bg-white border border-pink-100/90 shadow-xs hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Spent</span>
                        <div class="h-10 w-10 rounded-2xl bg-rose-50 border border-rose-200/80 flex items-center justify-center text-lg shadow-xs">
                            <span>💎</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-bold font-serif text-slate-900 truncate">{{ formatPrice(stats.total_spent) }}</div>
                        <p class="text-[11px] font-semibold text-rose-500 mt-0.5">On beauty products</p>
                    </div>
                </div>
            </div>

            <!-- 3. FILTER TABS & SEARCH BAR -->
            <div class="rounded-3xl bg-white p-5 border border-pink-100 shadow-xs space-y-4">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                        <button
                            type="button"
                            @click="filterByStatus('all')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                            :class="activeStatus === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            All Orders ({{ stats.total_orders }})
                        </button>
                        <button
                            type="button"
                            @click="filterByStatus('active')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                            :class="activeStatus === 'active' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                        >
                            Active Shipments ({{ stats.active_orders }})
                        </button>
                        <button
                            type="button"
                            @click="filterByStatus('delivered')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                            :class="activeStatus === 'delivered' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                        >
                            Delivered ({{ stats.delivered_orders }})
                        </button>
                        <button
                            type="button"
                            @click="filterByStatus('cancelled')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                            :class="activeStatus === 'cancelled' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                        >
                            Cancelled ({{ stats.cancelled_orders }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative min-w-[240px]">
                        <input
                            v-model="searchInput"
                            @keyup.enter="applyFilter"
                            type="text"
                            placeholder="Search by Order # or item..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:bg-white focus:outline-hidden"
                        />
                        <button
                            @click="applyFilter"
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-xs font-bold"
                        >
                            🔍
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. ORDERS LISTING STREAM -->
            <div v-if="orders.data && orders.data.length > 0" class="space-y-4">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="rounded-3xl bg-white border border-pink-100 shadow-xs hover:shadow-md transition-all p-5 sm:p-6 space-y-5"
                >
                    <!-- Order Top Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-pink-50 gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-600 text-white flex items-center justify-center text-xl shadow-xs shrink-0">
                                📦
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-serif text-base font-bold text-slate-900">
                                        Order #{{ order.order_number }}
                                    </h3>
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="getStatusBadge(order.order_status).bg"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="getStatusBadge(order.order_status).dot"></span>
                                        <span>{{ getStatusBadge(order.order_status).label }}</span>
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Placed on {{ formatDate(order.created_at) }} • Payment: <span class="uppercase font-semibold text-slate-600">{{ order.payment_method === 'cod' ? 'Cash on Delivery' : 'Bank Transfer' }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="text-left sm:text-right">
                            <span class="text-xs text-slate-400 block">Total Amount</span>
                            <span class="font-serif text-lg font-bold text-rose-600">{{ formatPrice(order.total_amount) }}</span>
                        </div>
                    </div>

                    <!-- Items Preview Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center gap-3 p-2.5 rounded-2xl bg-pink-50/20 border border-pink-100/70"
                        >
                            <img
                                :src="item.image_url || 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=120&q=80'"
                                :alt="item.product_name"
                                class="h-12 w-12 rounded-xl object-cover ring-1 ring-pink-100 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 truncate">{{ item.product_name }}</h4>
                                <p class="text-[10px] text-slate-400 truncate">{{ item.product_brand || 'Beauty Collection' }}</p>
                                <div class="flex items-center justify-between text-[11px] mt-0.5">
                                    <span class="text-slate-500 font-semibold">Qty: {{ item.quantity }}</span>
                                    <span class="font-bold text-slate-800">{{ formatPrice(item.total_price || item.unit_price) }}</span>
                                </div>
                                <div v-if="item.item_fulfillment_status" class="mt-1 flex items-center gap-1">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider border shadow-2xs"
                                        :class="getItemStatusBadge(item.item_fulfillment_status).bg"
                                    >
                                        {{ getItemStatusBadge(item.item_fulfillment_status).label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery & Action Footer -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-4 border-t border-slate-100 gap-3 text-xs">
                        <div class="flex items-center gap-2 text-slate-500 min-w-0">
                            <span>📍</span>
                            <span class="truncate max-w-md">Deliver to: <strong class="text-slate-700">{{ order.customer_name }}</strong>, {{ order.delivery_address }}, {{ order.city }}</span>
                        </div>

                        <div class="flex items-center gap-2.5 shrink-0">
                            <!-- Cancel Action if eligible -->
                            <button
                                v-if="order.order_status === 'pending' || order.order_status === 'confirmed'"
                                @click="openCancelModal(order.id)"
                                type="button"
                                class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 transition cursor-pointer"
                            >
                                Cancel Order
                            </button>

                            <!-- WhatsApp Tracking Hotline -->
                            <a
                                :href="`https://wa.me/923001234567?text=Salam!%20I%20am%20checking%20status%20for%20Order%20%23${order.order_number}.`"
                                target="_blank"
                                class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs border border-emerald-200 transition flex items-center gap-1.5"
                            >
                                <span>💬</span>
                                <span>Track on WhatsApp</span>
                            </a>

                            <!-- View Order Details -->
                            <Link
                                :href="route('customer.orders.show', order.id)"
                                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-rose-600 text-white font-bold text-xs shadow-xs hover:scale-102 transition"
                            >
                                View Order & Invoice &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <AppPagination :links="orders.links" class="pt-4" />
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-3xl bg-white p-12 border border-pink-100 shadow-xs text-center space-y-4">
                <div class="h-20 w-20 mx-auto rounded-3xl bg-gradient-to-tr from-rose-50 to-pink-100 flex items-center justify-center text-4xl shadow-inner">
                    🛍️
                </div>
                <div class="space-y-1.5">
                    <h3 class="text-lg font-bold font-serif text-slate-900">No Product Orders Found</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                        You haven't placed any merchandise orders yet. Explore our verified luxury cosmetics, clinical skincare kits, and haircare essentials.
                    </p>
                </div>
                <Link
                    :href="route('products.index')"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 text-white text-xs font-bold shadow-md hover:scale-102 transition"
                >
                    <span>✨</span>
                    <span>Shop Beauty Products Now</span>
                </Link>
            </div>
        </div>

        <!-- CANCEL ORDER CONFIRMATION MODAL -->
        <div v-if="cancellingOrderId" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 space-y-4 text-left">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="h-10 w-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-lg">
                        ⚠️
                    </div>
                    <div>
                        <h4 class="font-serif text-base font-bold text-slate-900">Cancel Order</h4>
                        <p class="text-[11px] text-slate-400">Are you sure you want to cancel this order?</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">Cancellation Reason</label>
                    <select v-model="cancelReason" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs">
                        <option value="Changed my mind / ordered by mistake">Changed my mind / ordered by mistake</option>
                        <option value="Need to change delivery address">Need to change delivery address</option>
                        <option value="Ordered duplicate items">Ordered duplicate items</option>
                        <option value="Delivery timeline is too long">Delivery timeline is too long</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                    <button
                        @click="cancellingOrderId = null"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition"
                    >
                        Keep Order
                    </button>
                    <button
                        @click="submitCancel"
                        :disabled="isCancelling"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition disabled:opacity-50"
                    >
                        {{ isCancelling ? 'Cancelling...' : 'Confirm Cancellation' }}
                    </button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
