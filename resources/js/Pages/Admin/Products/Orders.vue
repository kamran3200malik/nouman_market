<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import Swal from 'sweetalert2';

const page = usePage();

const props = defineProps({
    orders: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            pending_orders: 0,
            processing_orders: 0,
            dispatched_orders: 0,
            delivered_orders: 0,
            total_revenue: 0,
        })
    },
    cities: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Toast notification helper
const showToast = (title, icon = 'success') => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });
};

// Watch backend flash messages
watch(() => page.props.flash?.success, (msg) => {
    if (msg) showToast(msg, 'success');
});
watch(() => page.props.flash?.error, (msg) => {
    if (msg) showToast(msg, 'error');
});

// Search & Filter State
const searchInput = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'all');
const activeCity = ref(props.filters.city || 'all');
const activePaymentMethod = ref(props.filters.payment_method || 'all');

// Modal Detail State
const selectedOrder = ref(null);
const showDetailModal = ref(null);
const isUpdatingStatus = ref(false);

const updateForm = ref({
    order_status: 'pending',
    payment_status: 'pending',
    admin_notes: '',
    cancellation_reason: '',
});

// Delete Confirmation
const showDeleteModal = ref(false);
const orderToDelete = ref(null);
const isDeleting = ref(false);

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(price || 0);
};

const capitalize = (str) => {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return d.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const applyFilters = () => {
    router.get(route('admin.products.orders'), {
        search: searchInput.value || undefined,
        status: activeStatus.value !== 'all' ? activeStatus.value : undefined,
        city: activeCity.value !== 'all' ? activeCity.value : undefined,
        payment_method: activePaymentMethod.value !== 'all' ? activePaymentMethod.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const setStatusFilter = (status) => {
    activeStatus.value = status;
    applyFilters();
};

const openDetailModal = (order) => {
    selectedOrder.value = order;
    updateForm.value = {
        order_status: order.order_status,
        payment_status: order.payment_status,
        admin_notes: order.admin_notes || '',
        cancellation_reason: order.cancellation_reason || '',
    };
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedOrder.value = null;
};

const submitStatusUpdate = () => {
    if (!selectedOrder.value) return;
    const targetOrderNumber = selectedOrder.value.order_number;
    const targetStatus = capitalize(updateForm.value.order_status);

    isUpdatingStatus.value = true;
    router.patch(route('admin.products.orders.update-status', selectedOrder.value.id), updateForm.value, {
        onSuccess: () => {
            isUpdatingStatus.value = false;
            closeDetailModal();
            showToast(`Order #${targetOrderNumber} marked as ${targetStatus}! 🔔 Notification sent to customer.`, 'success');
        },
        onError: (errors) => {
            isUpdatingStatus.value = false;
            showToast(Object.values(errors)[0] || 'Failed to update order status.', 'error');
        }
    });
};

const confirmDelete = (order) => {
    orderToDelete.value = order;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!orderToDelete.value) return;
    const targetOrderNumber = orderToDelete.value.order_number;
    isDeleting.value = true;
    router.delete(route('admin.products.orders.destroy', orderToDelete.value.id), {
        onSuccess: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            orderToDelete.value = null;
            showToast(`Order #${targetOrderNumber} deleted.`, 'info');
        },
        onError: () => {
            isDeleting.value = false;
            showToast('Failed to delete order.', 'error');
        }
    });
};

const generateWhatsAppContactLink = (order) => {
    const rawPhone = (order.customer_phone || '').replace(/[^0-9]/g, '');
    let formattedPhone = rawPhone;
    if (formattedPhone.startsWith('0')) {
        formattedPhone = '92' + formattedPhone.substring(1);
    }
    const message = `Salam ${order.customer_name}! This is BeautyBook Luxe customer support regarding your order #${order.order_number} for PKR ${order.total_amount}.`;
    return `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
};

const statusTabs = [
    { value: 'all', label: 'All Orders' },
    { value: 'pending', label: 'Pending', countKey: 'pending_orders', color: 'bg-amber-500' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'processing', label: 'Packing', countKey: 'processing_orders', color: 'bg-purple-500' },
    { value: 'dispatched', label: 'Dispatched', countKey: 'dispatched_orders', color: 'bg-indigo-500' },
    { value: 'delivered', label: 'Delivered', countKey: 'delivered_orders', color: 'bg-emerald-500' },
    { value: 'cancelled', label: 'Cancelled' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Product Marketplace Orders CRM" />

        <div class="space-y-6">
            <!-- Header section & Sub-navigation -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Marketplace Logistics</span>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">Product Orders & Deliveries</h1>
                    <p class="text-xs sm:text-sm text-slate-500">Track and fulfill customer product orders, verify COD payments, and update dispatch logistics.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.products.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 shadow-2xs hover:bg-slate-50 hover:text-rose-600 transition"
                    >
                        <span>📦</span>
                        <span>Manage Products Catalog</span>
                    </Link>
                    <a
                        :href="route('products.index')"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-rose-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition"
                    >
                        <span>👁️</span>
                        <span>Live Store</span>
                    </a>
                </div>
            </div>

            <!-- KPI Metric Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Store Orders</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 text-sm">🛍️</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-slate-900 mt-2">{{ stats.total_orders }}</p>
                    <span class="text-[10px] text-slate-400">Recorded orders</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Pending Fulfillment</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 text-sm">⏳</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-amber-600 mt-2">{{ stats.pending_orders }}</p>
                    <span class="text-[10px] text-amber-600 font-medium">Needs packaging / confirmation</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">Dispatched / In Transit</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 text-sm">🚚</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-indigo-600 mt-2">{{ stats.dispatched_orders }}</p>
                    <span class="text-[10px] text-indigo-600 font-medium">With courier</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Store Sales Volume</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 text-sm">💰</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-emerald-600 mt-2">{{ formatPrice(stats.total_revenue) }}</p>
                    <span class="text-[10px] text-emerald-600 font-medium">Gross marketplace sales</span>
                </div>
            </div>

            <!-- Status Tabs Ribbon -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="setStatusFilter(tab.value)"
                    type="button"
                    :class="activeStatus === tab.value ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                    class="shrink-0 flex items-center gap-2 rounded-2xl px-4 py-2 text-xs font-bold uppercase tracking-wider transition cursor-pointer"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.countKey && stats[tab.countKey] > 0"
                        class="rounded-full px-2 py-0.2 text-[10px] font-black"
                        :class="activeStatus === tab.value ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-700'"
                    >
                        {{ stats[tab.countKey] }}
                    </span>
                </button>
            </div>

            <!-- Search & Secondary Filters -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                <div class="relative w-full sm:w-80">
                    <input
                        v-model="searchInput"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by Order #, Customer, Phone, or City..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white focus:ring-0"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select
                        v-model="activeCity"
                        @change="applyFilters"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-rose-500 focus:bg-white focus:ring-0"
                    >
                        <option value="all">All Cities</option>
                        <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                    </select>

                    <select
                        v-model="activePaymentMethod"
                        @change="applyFilters"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-rose-500 focus:bg-white focus:ring-0"
                    >
                        <option value="all">All Payment Methods</option>
                        <option value="cod">Cash on Delivery (COD)</option>
                        <option value="bank">Bank / Online Transfer</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Order Ref</th>
                                <th class="px-4 py-3.5">Customer & City</th>
                                <th class="px-4 py-3.5">Items Ordered</th>
                                <th class="px-4 py-3.5">Total & Payment</th>
                                <th class="px-4 py-3.5 text-center">Order Status</th>
                                <th class="px-4 py-3.5 text-center">Placed At</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <!-- Order Number -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-serif font-bold text-slate-900 text-sm">
                                        #{{ order.order_number }}
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        {{ order.items_count }} item(s)
                                    </span>
                                </td>

                                <!-- Customer details -->
                                <td class="px-4 py-4">
                                    <div class="min-w-0 max-w-xs">
                                        <h4 class="font-bold text-slate-900 truncate">{{ order.customer_name }}</h4>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                                            <span>{{ order.customer_phone }}</span>
                                            <span>•</span>
                                            <span class="font-semibold text-rose-600">{{ order.city }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Items Preview -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-1.5 overflow-x-auto max-w-xs py-1">
                                        <div
                                            v-for="item in order.items"
                                            :key="item.id"
                                            class="relative shrink-0 h-10 w-10 rounded-lg overflow-hidden border border-slate-200 group bg-slate-100"
                                            :title="`${item.product_name} (${item.quantity}x)`"
                                        >
                                            <img
                                                :src="item.image_url"
                                                :alt="item.product_name"
                                                class="h-full w-full object-cover"
                                                referrerpolicy="no-referrer"
                                                loading="lazy"
                                            />
                                            <span class="absolute bottom-0 right-0 bg-slate-950/90 text-white text-[8px] font-black px-1 rounded-tl">
                                                {{ item.quantity }}x
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Total & Payment -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="font-serif font-bold text-slate-900 text-sm">
                                        {{ order.formatted_total }}
                                    </span>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span
                                            :class="order.payment_method === 'cod' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200'"
                                            class="rounded px-1.5 py-0.2 text-[9px] font-bold uppercase border"
                                        >
                                            {{ order.payment_method === 'cod' ? 'COD' : 'Bank Transfer' }}
                                        </span>
                                        <span
                                            :class="order.payment_status === 'paid' ? 'text-emerald-600 font-bold' : 'text-slate-400'"
                                            class="text-[10px]"
                                        >
                                            • {{ capitalize(order.payment_status) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span
                                        :class="order.status_badge.class"
                                        class="inline-block rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider border shadow-2xs"
                                    >
                                        {{ order.status_badge.label }}
                                    </span>
                                </td>

                                <!-- Placed At -->
                                <td class="px-4 py-4 text-center whitespace-nowrap text-[11px] text-slate-500">
                                    {{ formatDate(order.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            :href="generateWhatsAppContactLink(order)"
                                            target="_blank"
                                            class="rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-2.5 py-1.5 font-bold transition text-xs flex items-center gap-1"
                                            title="Chat with customer on WhatsApp"
                                        >
                                            <span>💬</span>
                                            <span>WhatsApp</span>
                                        </a>
                                        <button
                                            @click="openDetailModal(order)"
                                            type="button"
                                            class="rounded-lg bg-slate-900 hover:bg-rose-600 text-white px-3 py-1.5 font-bold transition text-xs cursor-pointer"
                                        >
                                            Manage Order
                                        </button>
                                        <button
                                            @click="confirmDelete(order)"
                                            type="button"
                                            class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 px-2.5 py-1.5 font-bold transition text-xs cursor-pointer"
                                            title="Delete"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="orders.data.length === 0" class="text-center py-16 space-y-2">
                        <span class="text-3xl block">🛍️</span>
                        <p class="text-sm font-semibold text-slate-700">No product orders found</p>
                        <p class="text-xs text-slate-400">Incoming store orders placed by customers will show up here in real time.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="orders.links && orders.links.length > 3" class="p-4 border-t border-slate-100 flex justify-center">
                    <AppPagination :links="orders.links" />
                </div>
            </div>
        </div>

        <!-- ORDER DETAIL & STATUS MANAGEMENT MODAL -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDetailModal && selectedOrder"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-2xl rounded-3xl bg-white shadow-2xl overflow-hidden border border-slate-100 flex flex-col max-h-[92vh]"
                >
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Order Management</span>
                            <h3 class="font-serif text-lg font-bold text-slate-900">
                                Order #{{ selectedOrder.order_number }}
                            </h3>
                        </div>
                        <button
                            @click="closeDetailModal"
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 transition cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto space-y-5 text-xs text-left">
                        
                        <!-- Customer & Delivery Summary Card -->
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900 text-sm">Customer & Shipping Information</h4>
                                <a
                                    :href="generateWhatsAppContactLink(selectedOrder)"
                                    target="_blank"
                                    class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1 text-xs flex items-center gap-1 shadow-2xs"
                                >
                                    <span>💬</span>
                                    <span>Contact on WhatsApp</span>
                                </a>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-slate-700">
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Recipient Name</span>
                                    <span class="font-bold text-slate-900 text-xs">{{ selectedOrder.customer_name }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Phone Number</span>
                                    <span class="font-bold text-slate-900 text-xs">{{ selectedOrder.customer_phone }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">City</span>
                                    <span class="font-bold text-slate-900 text-xs">{{ selectedOrder.city }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Payment Method</span>
                                    <span class="font-bold text-slate-900 text-xs uppercase">{{ selectedOrder.payment_method === 'cod' ? 'Cash on Delivery' : 'Online / Bank' }}</span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Delivery Address</span>
                                    <p class="font-medium text-slate-800 text-xs leading-relaxed">{{ selectedOrder.delivery_address }}</p>
                                </div>
                                <div v-if="selectedOrder.customer_notes" class="sm:col-span-2 rounded-xl bg-amber-50 p-2.5 border border-amber-200/60 text-amber-900">
                                    <span class="font-bold block text-[10px] uppercase">Customer Order Note:</span>
                                    {{ selectedOrder.customer_notes }}
                                </div>
                            </div>
                        </div>

                        <!-- Ordered Items Breakdown -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Ordered Products</h4>
                            <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                                <div
                                    v-for="item in selectedOrder.items"
                                    :key="item.id"
                                    class="flex items-center justify-between p-3 bg-white"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <img
                                            :src="item.image_url"
                                            :alt="item.product_name"
                                            referrerpolicy="no-referrer"
                                            class="h-12 w-12 rounded-xl object-cover border border-slate-100 shrink-0 bg-slate-100"
                                        />
                                        <div class="min-w-0">
                                            <span class="text-[9px] font-bold uppercase text-rose-600">{{ item.product_brand }}</span>
                                            <h5 class="font-bold text-slate-900 text-xs truncate">{{ item.product_name }}</h5>
                                            <p class="text-[11px] text-slate-400">{{ item.formatted_unit_price }} × {{ item.quantity }}</p>
                                        </div>
                                    </div>
                                    <span class="font-serif font-bold text-slate-900 text-sm">
                                        {{ item.formatted_total_price }}
                                    </span>
                                </div>
                            </div>

                            <!-- Financial Summary -->
                            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/80 space-y-1.5 text-xs text-slate-600">
                                <div class="flex justify-between">
                                    <span>Products Subtotal</span>
                                    <span class="font-bold text-slate-900">{{ selectedOrder.formatted_subtotal }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Shipping Delivery Fee</span>
                                    <span class="font-bold" :class="selectedOrder.shipping_fee == 0 ? 'text-emerald-600' : 'text-slate-900'">
                                        {{ selectedOrder.shipping_fee == 0 ? 'FREE' : formatPrice(selectedOrder.shipping_fee) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm font-bold text-slate-900 pt-2 border-t border-slate-200">
                                    <span>Total Payable Amount</span>
                                    <span class="text-rose-600 font-serif text-base">{{ selectedOrder.formatted_total }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Dispatch Management Form -->
                        <form @submit.prevent="submitStatusUpdate" class="space-y-4 pt-2 border-t border-slate-100">
                            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Update Order & Fulfillment Status</h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Order Fulfillment Status *</label>
                                    <select
                                        v-model="updateForm.order_status"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-800 focus:border-rose-500 focus:bg-white"
                                    >
                                        <option value="pending">⏳ Pending Approval</option>
                                        <option value="confirmed">✓ Confirmed</option>
                                        <option value="processing">📦 Packing Order</option>
                                        <option value="dispatched">🚚 Dispatched / In Transit</option>
                                        <option value="delivered">🎉 Delivered to Customer</option>
                                        <option value="cancelled">✕ Cancelled</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Payment Status *</label>
                                    <select
                                        v-model="updateForm.payment_status"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-800 focus:border-rose-500 focus:bg-white"
                                    >
                                        <option value="pending">Pending Payment</option>
                                        <option value="paid">Paid & Verified</option>
                                        <option value="failed">Failed / Rejected</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Internal Admin / Courier Notes</label>
                                <textarea
                                    v-model="updateForm.admin_notes"
                                    rows="2"
                                    placeholder="e.g., Courier tracking #TCS-892189, dispatched via Leopard Courier Karachi hub..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                ></textarea>
                            </div>

                            <div v-if="updateForm.order_status === 'cancelled'">
                                <label class="block font-bold text-rose-700 mb-1">Cancellation Reason</label>
                                <input
                                    v-model="updateForm.cancellation_reason"
                                    type="text"
                                    placeholder="e.g., Customer requested cancellation / address unreachable"
                                    class="w-full rounded-xl border border-rose-200 bg-rose-50/50 px-3.5 py-2 text-xs text-rose-900 focus:border-rose-500 focus:bg-white"
                                />
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button
                                    @click="closeDetailModal"
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                >
                                    Close
                                </button>
                                <button
                                    :disabled="isUpdatingStatus"
                                    type="submit"
                                    class="rounded-xl bg-rose-600 hover:bg-rose-700 px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-rose-600/20 transition cursor-pointer disabled:opacity-50"
                                >
                                    {{ isUpdatingStatus ? 'Updating Status...' : 'Save Order Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>

        <!-- DELETE CONFIRMATION MODAL -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDeleteModal && orderToDelete"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 text-center space-y-4"
                >
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-600 text-2xl mx-auto">
                        🗑️
                    </div>
                    <h3 class="font-serif text-lg font-bold text-slate-900">Delete Store Order?</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Are you sure you want to permanently remove Order <span class="font-bold text-slate-800">#{{ orderToDelete.order_number }}</span>?
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <button
                            @click="showDeleteModal = false; orderToDelete = null;"
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            :disabled="isDeleting"
                            @click="executeDelete"
                            type="button"
                            class="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-md transition cursor-pointer disabled:opacity-50"
                        >
                            {{ isDeleting ? 'Deleting...' : 'Confirm Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AdminLayout>
</template>
