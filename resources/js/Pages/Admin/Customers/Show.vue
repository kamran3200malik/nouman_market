<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
    totalSpent: {
        type: Number,
        default: 0,
    },
});

const activeTab = ref('orders'); // 'orders' | 'reviews' | 'favorites'
const actionLoading = ref(false);
const showStatusModal = ref(false);

const toggleStatus = () => {
    actionLoading.value = true;
    router.post(
        route('admin.customers.update-status', props.customer.id),
        { is_active: !props.customer.is_active },
        {
            onFinish: () => {
                actionLoading.value = false;
                showStatusModal.value = false;
            },
        }
    );
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch (e) {
        return 'N/A';
    }
};

const formatPrice = (price) => {
    if (price === null || price === undefined || isNaN(price)) return 'PKR 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(parseFloat(price));
};

const getStatusBadge = (status) => {
    switch (status?.toLowerCase()) {
        case 'delivered':
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'processing':
        case 'confirmed':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'cancelled':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${customer.name} - Buyer Dossier`" />

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
                <Link :href="route('admin.customers.index')" class="hover:text-slate-600 transition-colors">
                    Customer Directory
                </Link>
                <span>/</span>
                <span class="text-slate-700">{{ customer.name }}</span>
            </div>

            <!-- Customer Hero Card -->
            <div class="relative overflow-hidden rounded-3xl bg-white p-6 sm:p-8 shadow-sm border border-slate-200/80">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div class="flex items-start sm:items-center gap-5">
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-3xl bg-gradient-to-tr from-rose-500 to-pink-600 flex items-center justify-center font-black text-white text-2xl shadow-md shrink-0 ring-4 ring-slate-100">
                            {{ customer.name?.charAt(0)?.toUpperCase() || 'C' }}
                        </div>

                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                    {{ customer.name }}
                                </h1>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold border"
                                    :class="customer.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="customer.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    <span>{{ customer.is_active ? 'Active Buyer' : 'Account Inactive' }}</span>
                                </span>
                            </div>

                            <p class="text-xs text-slate-500">
                                Member since {{ formatDate(customer.created_at) }} • {{ customer.city || 'Pakistan' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="toggleStatus"
                            :disabled="actionLoading"
                            class="px-4 py-2 rounded-2xl text-xs font-bold border transition-all cursor-pointer"
                            :class="customer.is_active ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                        >
                            {{ customer.is_active ? 'Deactivate Buyer' : 'Activate Buyer' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Ribbon -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Orders</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ customer.orders?.length || 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">E-Commerce orders</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Spent (Paid)</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ formatPrice(totalSpent) }}</p>
                    <p class="text-xs text-slate-400 mt-1">Completed purchases</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Reviews Written</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ customer.reviews?.length || 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Product ratings</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Saved Wishlist</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ customer.favorites?.length || 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Saved products</p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-slate-200 flex gap-2 overflow-x-auto pb-px">
                <button
                    v-for="tab in [
                        { key: 'orders', label: `Orders (${customer.orders?.length || 0})` },
                        { key: 'reviews', label: `Product Reviews (${customer.reviews?.length || 0})` },
                        { key: 'favorites', label: `Wishlist Items (${customer.favorites?.length || 0})` },
                    ]"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition-all whitespace-nowrap cursor-pointer"
                    :class="[
                        activeTab === tab.key
                            ? 'border-rose-600 text-rose-600'
                            : 'border-transparent text-slate-500 hover:text-slate-900'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB 1: Orders History -->
            <div v-if="activeTab === 'orders'" class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Customer Purchase History</h2>

                <div v-if="customer.orders && customer.orders.length > 0" class="divide-y divide-slate-100">
                    <div
                        v-for="order in customer.orders"
                        :key="order.id"
                        class="py-4 flex items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm shrink-0">
                                📦
                            </div>
                            <div class="min-w-0">
                                <Link :href="route('admin.orders.show', order.id)" class="text-sm font-bold text-slate-900 hover:text-rose-600 truncate block">
                                    Order #{{ order.order_number }}
                                </Link>
                                <p class="text-xs text-slate-400 mt-0.5 truncate">
                                    {{ order.items?.length || 1 }} Items • Date: {{ formatDate(order.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-slate-900">{{ formatPrice(order.total_amount) }}</p>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize mt-1"
                                :class="getStatusBadge(order.order_status)"
                            >
                                {{ order.order_status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center text-xs text-slate-400">
                    No orders placed by this customer yet.
                </div>
            </div>

            <!-- TAB 2: Reviews Written -->
            <div v-else-if="activeTab === 'reviews'" class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Product Ratings & Reviews</h2>

                <div v-if="customer.reviews && customer.reviews.length > 0" class="divide-y divide-slate-100">
                    <div
                        v-for="review in customer.reviews"
                        :key="review.id"
                        class="py-4 space-y-1.5"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ review.product?.name || 'Product Review' }}</p>
                                <div class="flex text-amber-400 text-xs mt-0.5">
                                    <span v-for="i in 5" :key="i">{{ i <= (review.rating || 5) ? '★' : '☆' }}</span>
                                </div>
                            </div>
                            <span class="text-xs text-slate-400">{{ formatDate(review.created_at) }}</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-600">{{ review.comment || 'No written feedback.' }}</p>
                    </div>
                </div>

                <div v-else class="py-12 text-center text-xs text-slate-400">
                    No reviews submitted by this customer yet.
                </div>
            </div>

            <!-- TAB 3: Saved Favorites -->
            <div v-else-if="activeTab === 'favorites'" class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Wishlist & Saved Products</h2>

                <div v-if="customer.favorites && customer.favorites.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="fav in customer.favorites"
                        :key="fav.id"
                        class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center gap-3"
                    >
                        <div v-if="fav.product" class="h-12 w-12 rounded-xl overflow-hidden bg-white border border-slate-200 shrink-0">
                            <img :src="fav.product.image_url" :alt="fav.product.name" class="h-full w-full object-cover" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ fav.product?.name || 'Saved Product' }}</p>
                            <p class="text-[11px] font-black text-rose-600">{{ formatPrice(fav.product?.price) }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center text-xs text-slate-400">
                    No items in wishlist yet.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
