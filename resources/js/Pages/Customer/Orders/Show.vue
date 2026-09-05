<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    }
});

const cancelling = ref(false);
const cancelReason = ref('');
const showCancelModal = ref(false);

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
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const steps = [
    { key: 'pending', label: 'Order Placed', desc: 'Received in queue' },
    { key: 'confirmed', label: 'Confirmed', desc: 'Approved by Hub' },
    { key: 'processing', label: 'Packing', desc: 'Readying package' },
    { key: 'dispatched', label: 'Out for Delivery', desc: 'With courier' },
    { key: 'delivered', label: 'Delivered', desc: 'Received by client' },
];

const currentStepIndex = computed(() => {
    if (props.order.order_status === 'cancelled') return -1;
    switch (props.order.order_status) {
        case 'pending': return 0;
        case 'confirmed': return 1;
        case 'processing': return 2;
        case 'dispatched': return 3;
        case 'delivered': return 4;
        default: return 0;
    }
});

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
            return { label: 'Pending Prep', bg: 'bg-amber-100/80 text-amber-800 border-amber-200' };
        case 'processing':
            return { label: 'Packing Item', bg: 'bg-purple-100/80 text-purple-800 border-purple-200' };
        case 'dispatched':
            return { label: 'In Transit / Dispatched', bg: 'bg-indigo-100/80 text-indigo-800 border-indigo-200' };
        case 'delivered':
            return { label: 'Delivered', bg: 'bg-emerald-100/80 text-emerald-800 border-emerald-200' };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-rose-100/80 text-rose-800 border-rose-200' };
        default:
            return { label: status || 'Pending', bg: 'bg-slate-100 text-slate-800 border-slate-200' };
    }
};

const printReceipt = () => {
    window.print();
};

const submitCancel = () => {
    cancelling.value = true;
    router.post(route('customer.orders.cancel', props.order.id), {
        reason: cancelReason.value || 'Cancelled by customer'
    }, {
        onFinish: () => {
            cancelling.value = false;
            showCancelModal.value = false;
        }
    });
};
</script>

<template>
    <CustomerLayout>
        <Head :title="`Order #${order.order_number} - VIP Invoice & Delivery Tracking`" />

        <div class="space-y-6 max-w-5xl mx-auto print:m-0 print:p-0">
            <!-- Navigation Back Bar (Hidden on Print) -->
            <div class="flex items-center justify-between gap-4 print:hidden">
                <Link
                    :href="route('customer.orders.index')"
                    class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-rose-600 transition"
                >
                    <span>&larr;</span>
                    <span>Back to My Product Orders</span>
                </Link>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="printReceipt"
                        type="button"
                        class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🖨️</span>
                        <span>Print Invoice</span>
                    </button>
                    <a
                        :href="`https://wa.me/923001234567?text=Salam!%20I%20am%20inquiring%20about%20Order%20%23${order.order_number}.`"
                        target="_blank"
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5"
                    >
                        <span>💬</span>
                        <span>WhatsApp Support</span>
                    </a>
                </div>
            </div>

            <!-- ORDER HEADER BANNER -->
            <div class="rounded-3xl bg-white p-6 sm:p-8 border border-pink-100 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-pink-50 gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="font-serif text-xl sm:text-2xl font-bold text-slate-900">
                                Order #{{ order.order_number }}
                            </h1>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border"
                                :class="getStatusBadge(order.order_status).bg"
                            >
                                <span class="h-2 w-2 rounded-full" :class="getStatusBadge(order.order_status).dot"></span>
                                <span>{{ getStatusBadge(order.order_status).label }}</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Placed on {{ formatDate(order.created_at) }} &bull; Payment Method: <strong class="uppercase text-slate-700">{{ order.payment_method === 'cod' ? 'Cash on Delivery' : 'Bank Transfer' }}</strong>
                        </p>
                    </div>

                    <div class="text-left sm:text-right">
                        <span class="text-xs text-slate-400 uppercase tracking-wider block">Grand Total</span>
                        <span class="font-serif text-2xl font-bold text-rose-600">{{ formatPrice(order.total_amount) }}</span>
                    </div>
                </div>

                <!-- LIVE SHIPMENT TRACKER STEPPER (If not cancelled) -->
                <div v-if="order.order_status !== 'cancelled'" class="py-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Shipment & Delivery Timeline</h3>
                    <div class="relative">
                        <div class="grid grid-cols-5 gap-2 text-center">
                            <div
                                v-for="(step, idx) in steps"
                                :key="step.key"
                                class="space-y-2 relative"
                            >
                                <!-- Step Circle Indicator -->
                                <div
                                    class="h-9 w-9 mx-auto rounded-2xl flex items-center justify-center text-xs font-bold transition-all shadow-xs"
                                    :class="idx <= currentStepIndex
                                        ? 'bg-gradient-to-tr from-rose-500 to-pink-600 text-white shadow-pink-200'
                                        : 'bg-slate-100 text-slate-400 border border-slate-200'"
                                >
                                    <span v-if="idx < currentStepIndex">✓</span>
                                    <span v-else>{{ idx + 1 }}</span>
                                </div>
                                <div class="space-y-0.5">
                                    <p
                                        class="text-xs font-bold"
                                        :class="idx <= currentStepIndex ? 'text-slate-900' : 'text-slate-400'"
                                    >
                                        {{ step.label }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 hidden sm:block">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancelled Notice if Cancelled -->
                <div v-else class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-3">
                    <span class="text-lg">🚫</span>
                    <div>
                        <p class="font-bold">This order has been cancelled.</p>
                        <p class="text-[11px] text-rose-600 mt-0.5">{{ order.cancellation_reason || 'Cancelled upon customer request.' }}</p>
                    </div>
                </div>
            </div>

            <!-- INVOICE LINE ITEMS TABLE -->
            <div class="rounded-3xl bg-white p-6 sm:p-8 border border-pink-100 shadow-xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-pink-50">
                    <h3 class="font-serif text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>🛍️</span>
                        <span>Itemized Invoice Breakdown</span>
                    </h3>
                    <span class="text-xs font-bold text-slate-500">{{ order.items?.length || 0 }} Item(s)</span>
                </div>

                <div class="divide-y divide-pink-50">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <img
                                :src="item.image_url || (item.product?.image_url) || 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=140&q=80'"
                                :alt="item.product_name"
                                class="h-14 w-14 rounded-2xl object-cover ring-1 ring-pink-100 shrink-0 shadow-xs"
                            />
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-900 truncate">{{ item.product_name }}</h4>
                                <p class="text-xs text-slate-400">
                                    {{ item.product_brand || 'Beauty Collection' }} &bull; Category: {{ item.product_category || 'Cosmetics' }}
                                </p>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <p v-if="item.artist_profile" class="text-[11px] text-pink-700 font-semibold">
                                        Sold by: {{ item.artist_profile?.business_name || item.artist_profile?.user?.name }}
                                    </p>
                                    <span
                                        v-if="item.item_fulfillment_status"
                                        class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border shadow-2xs"
                                        :class="getItemStatusBadge(item.item_fulfillment_status).bg"
                                    >
                                        {{ getItemStatusBadge(item.item_fulfillment_status).label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex sm:flex-col items-center sm:items-end justify-between text-right shrink-0">
                            <span class="text-xs text-slate-400">Qty: {{ item.quantity }} &times; {{ formatPrice(item.unit_price) }}</span>
                            <span class="text-sm font-bold text-slate-900 mt-0.5">{{ formatPrice(item.total_price) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Calculations Summary -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row justify-between gap-6 items-start">
                    <div class="text-xs text-slate-500 space-y-1">
                        <p><strong>Shipping Carrier:</strong> Standard Express Beauty Courier</p>
                        <p><strong>Payment Status:</strong> {{ order.payment_status ? order.payment_status.toUpperCase() : 'PENDING ON DELIVERY' }}</p>
                    </div>

                    <div class="w-full sm:w-64 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-900">{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Shipping Fee</span>
                            <span class="font-bold" :class="Number(order.shipping_fee) === 0 ? 'text-emerald-600' : 'text-slate-900'">
                                {{ Number(order.shipping_fee) === 0 ? 'FREE' : formatPrice(order.shipping_fee) }}
                            </span>
                        </div>
                        <div v-if="Number(order.discount_amount) > 0" class="flex justify-between text-emerald-600">
                            <span>Discount</span>
                            <span>-{{ formatPrice(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                            <span>Grand Total</span>
                            <span class="font-serif text-lg text-rose-600">{{ formatPrice(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SHIPPING & DELIVERY RECIPIENT DETAILS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Delivery Address Box -->
                <div class="rounded-3xl bg-white p-6 border border-pink-100 shadow-xs space-y-3">
                    <h4 class="font-serif text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>📍</span>
                        <span>Delivery Destination</span>
                    </h4>
                    <div class="space-y-1 text-xs text-slate-600">
                        <p class="font-bold text-slate-900 text-sm">{{ order.customer_name }}</p>
                        <p class="leading-relaxed">{{ order.delivery_address }}</p>
                        <p class="font-semibold text-slate-700">City: {{ order.city }}</p>
                    </div>
                </div>

                <!-- Contact & Notes Box -->
                <div class="rounded-3xl bg-white p-6 border border-pink-100 shadow-xs space-y-3">
                    <h4 class="font-serif text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>📞</span>
                        <span>Contact & Delivery Instructions</span>
                    </h4>
                    <div class="space-y-1 text-xs text-slate-600">
                        <p><strong>Phone:</strong> {{ order.customer_phone }}</p>
                        <p v-if="order.customer_email"><strong>Email:</strong> {{ order.customer_email }}</p>
                        <p v-if="order.customer_notes" class="pt-1 text-slate-500 italic">
                            "{{ order.customer_notes }}"
                        </p>
                        <p v-else class="text-slate-400">No special instructions provided.</p>
                    </div>
                </div>
            </div>

            <!-- ORDER FOOTER ACTIONS (Hidden on Print) -->
            <div class="rounded-3xl bg-white p-6 border border-pink-100 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4 print:hidden">
                <div class="text-xs text-slate-500">
                    Need help with your order? Our beauty concierge is available 24/7.
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="order.order_status === 'pending' || order.order_status === 'confirmed'"
                        @click="showCancelModal = true"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 transition cursor-pointer"
                    >
                        Cancel This Order
                    </button>
                    <Link
                        :href="route('products.index')"
                        class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-rose-600 text-white font-bold text-xs shadow-xs hover:scale-102 transition"
                    >
                        Continue Shopping &rarr;
                    </Link>
                </div>
            </div>
        </div>

        <!-- CANCEL ORDER CONFIRMATION MODAL -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 space-y-4 text-left">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="h-10 w-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-lg">
                        ⚠️
                    </div>
                    <div>
                        <h4 class="font-serif text-base font-bold text-slate-900">Cancel Order #{{ order.order_number }}</h4>
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
                        @click="showCancelModal = false"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition"
                    >
                        Keep Order
                    </button>
                    <button
                        @click="submitCancel"
                        :disabled="cancelling"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition disabled:opacity-50"
                    >
                        {{ cancelling ? 'Cancelling...' : 'Confirm Cancellation' }}
                    </button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
