<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    payments: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_volume: 0,
            platform_commission: 0,
            artist_net: 0,
            pending_volume: 0,
            total_count: 0,
            completed_count: 0,
            pending_count: 0,
            failed_count: 0,
            refunded_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchInput = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const methodFilter = ref(props.filters.method || '');
const fromDate = ref(props.filters.from_date || '');
const toDate = ref(props.filters.to_date || '');
const loading = ref(false);

const selectedPayment = ref(null);
const actionLoading = ref(false);

const formatPrice = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'PKR 0';
    return 'PKR ' + Number(val).toLocaleString('en-PK', { maximumFractionDigits: 0 });
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

const filterStatuses = computed(() => [
    { key: '', label: 'All Transactions', count: props.stats?.total_count ?? 0 },
    { key: 'completed', label: '✓ Completed', count: props.stats?.completed_count ?? 0 },
    { key: 'pending', label: '⏳ Pending', count: props.stats?.pending_count ?? 0, highlight: (props.stats?.pending_count ?? 0) > 0 },
    { key: 'failed', label: '✕ Failed', count: props.stats?.failed_count ?? 0 },
    { key: 'refunded', label: '↩ Refunded', count: props.stats?.refunded_count ?? 0 },
]);

const methodOptions = [
    { value: '', label: '💳 All Methods' },
    { value: 'cash', label: 'Cash on Delivery (COD)' },
    { value: 'card', label: 'Credit / Debit Card' },
    { value: 'jazzcash', label: 'JazzCash' },
    { value: 'easypaisa', label: 'EasyPaisa' },
    { value: 'stripe', label: 'Stripe' },
    { value: 'online', label: 'Online Gateway' },
];

const applyFilters = () => {
    loading.value = true;
    router.get(
        route('admin.payments.index'),
        {
            search: searchInput.value || undefined,
            status: statusFilter.value || undefined,
            method: methodFilter.value || undefined,
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                loading.value = false;
            },
        }
    );
};

const setStatus = (statusKey) => {
    statusFilter.value = statusKey;
    applyFilters();
};

const clearFilters = () => {
    searchInput.value = '';
    statusFilter.value = '';
    methodFilter.value = '';
    fromDate.value = '';
    toDate.value = '';
    applyFilters();
};

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff`);
};

const markAsPaid = (payment) => {
    Swal.fire({
        title: 'Confirm Payment Received?',
        text: `Mark transaction #${payment.id} of ${formatPrice(payment.amount)} as completed?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Mark Paid',
    }).then((result) => {
        if (result.isConfirmed) {
            actionLoading.value = true;
            router.post(route('admin.payments.mark-paid', payment.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    actionLoading.value = false;
                    if (selectedPayment.value?.id === payment.id) {
                        selectedPayment.value.status = 'completed';
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Payment marked as completed!',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
                onError: () => {
                    actionLoading.value = false;
                }
            });
        }
    });
};

const refundPayment = (payment) => {
    Swal.fire({
        title: 'Issue Refund?',
        text: `Enter reason for refunding ${formatPrice(payment.amount)}:`,
        input: 'textarea',
        inputPlaceholder: 'Reason for refund...',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Process Refund',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a refund reason.';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            actionLoading.value = true;
            router.post(route('admin.payments.refund', payment.id), {
                reason: result.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    actionLoading.value = false;
                    if (selectedPayment.value?.id === payment.id) {
                        selectedPayment.value.status = 'refunded';
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: 'Payment marked as refunded',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
                onError: () => {
                    actionLoading.value = false;
                }
            });
        }
    });
};

const openDetailModal = (payment) => {
    selectedPayment.value = payment;
};

const closeDetailModal = () => {
    selectedPayment.value = null;
};
</script>

<template>
    <Head title="Payments & Transactions | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- PAGE TITLE & SUB-NAVIGATION -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">
                        Financial Operations & Transactions
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Monitor platform transaction volume, commission splits, salon payouts, and customer payment settlements.
                    </p>
                </div>

                <!-- Sub-nav tabs -->
                <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-rose-100 shadow-xs self-start sm:self-auto">
                    <Link
                        :href="route('admin.payments.index')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-glam-600 text-white shadow-xs"
                    >
                        💳 Customer Payments
                    </Link>
                    <Link
                        :href="route('admin.payments.payouts')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-rose-50/60 transition-all"
                    >
                        🏦 Salon Payouts
                    </Link>
                    <Link
                        :href="route('admin.payments.commissions')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-rose-50/60 transition-all"
                    >
                        ⚙️ Commission Rules
                    </Link>
                </div>
            </div>

            <!-- 1. FINANCIAL KPI SUMMARY CARDS (4 CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Gross Volume -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Volume Settled</p>
                        <h3 class="text-2xl font-serif font-bold text-slate-900 mt-1">{{ formatPrice(stats.total_volume) }}</h3>
                        <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">✓ {{ stats.completed_count }} Completed Trans.</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        💰
                    </div>
                </div>

                <!-- Platform Commission Revenue -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Platform Revenue (Fee)</p>
                        <h3 class="text-2xl font-serif font-bold text-glam-700 mt-1">{{ formatPrice(stats.platform_commission) }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Retained Marketplace Take</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-glam-700 flex items-center justify-center text-xl shadow-xs">
                        💎
                    </div>
                </div>

                <!-- Artist Net Payouts -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Salons Net Share</p>
                        <h3 class="text-2xl font-serif font-bold text-slate-900 mt-1">{{ formatPrice(stats.artist_net) }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Disbursed to Stylists</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-700 flex items-center justify-center text-xl shadow-xs">
                        ✂️
                    </div>
                </div>

                <!-- Pending Volume -->
                <div class="p-5 rounded-3xl bg-white border border-amber-200/80 shadow-xs flex items-center justify-between" :class="stats.pending_volume > 0 ? 'bg-amber-50/20 ring-2 ring-amber-400/30' : ''">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Settlements</p>
                            <span v-if="stats.pending_count > 0" class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        </div>
                        <h3 class="text-2xl font-serif font-bold text-amber-950 mt-1">{{ formatPrice(stats.pending_volume) }}</h3>
                        <p class="text-[10px] text-amber-700 font-medium mt-0.5">{{ stats.pending_count }} In Progress</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl shadow-xs">
                        ⏳
                    </div>
                </div>
            </div>

            <!-- 2. STATUS TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="status in filterStatuses"
                    :key="status.key"
                    type="button"
                    class="px-4 py-2 rounded-2xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
                    :class="statusFilter === status.key
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50/60'"
                    @click="setStatus(status.key)"
                >
                    <span>{{ status.label }}</span>
                    <span
                        class="px-1.5 py-0.5 rounded-full text-[10px]"
                        :class="statusFilter === status.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                    >
                        {{ status.count }}
                    </span>
                </button>
            </div>

            <!-- 3. FILTER & SEARCH TOOLBAR -->
            <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <!-- Search Input -->
                    <div class="sm:col-span-5 relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchInput"
                            type="text"
                            placeholder="Search by txn #, client, salon, treatment or booking #..."
                            class="w-full pl-10 pr-8 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 transition-all"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            v-if="searchInput"
                            type="button"
                            class="absolute inset-y-0 right-2.5 flex items-center text-slate-400 hover:text-slate-600"
                            @click="searchInput = ''; applyFilters();"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Payment Method Filter -->
                    <div class="sm:col-span-3">
                        <select
                            v-model="methodFilter"
                            class="w-full py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                            @change="applyFilters"
                        >
                            <option v-for="m in methodOptions" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="sm:col-span-4 flex items-center gap-2">
                        <input
                            v-model="fromDate"
                            type="date"
                            class="w-1/2 py-2 px-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                            @change="applyFilters"
                            title="From Date"
                        />
                        <span class="text-slate-400 text-xs">-</span>
                        <input
                            v-model="toDate"
                            type="date"
                            class="w-1/2 py-2 px-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                            @change="applyFilters"
                            title="To Date"
                        />
                    </div>
                </div>

                <!-- Reset tag -->
                <div v-if="searchInput || methodFilter || fromDate || toDate" class="flex items-center justify-between pt-2 border-t border-rose-50 text-xs">
                    <span class="text-slate-500">Filtered financial ledger</span>
                    <button
                        type="button"
                        class="text-glam-700 font-semibold hover:underline cursor-pointer"
                        @click="clearFilters"
                    >
                        Reset all filters
                    </button>
                </div>
            </div>

            <!-- 4. TRANSACTIONS LEDGER TABLE -->
            <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <!-- Loading overlay -->
                <div v-if="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <div class="w-8 h-8 border-3 border-glam-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div v-if="payments.data && payments.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4 sm:px-6">Transaction Ref</th>
                                <th class="py-3.5 px-4">Client</th>
                                <th class="py-3.5 px-4">Salon / Treatment</th>
                                <th class="py-3.5 px-4">Financial Split</th>
                                <th class="py-3.5 px-4">Method & Gateway</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr
                                v-for="payment in payments.data"
                                :key="payment.id"
                                class="hover:bg-rose-50/30 transition-colors"
                            >
                                <!-- Transaction Ref & Date -->
                                <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-bold text-slate-900 text-xs">
                                                #{{ payment.transaction_id || ('TXN-' + payment.id.toString().padStart(6, '0')) }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">
                                            {{ formatDate(payment.created_at) }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Customer Column -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <img
                                            :src="getAvatar(payment.booking?.customer?.avatar, payment.booking?.customer?.name)"
                                            :alt="payment.booking?.customer?.name"
                                            class="w-8 h-8 rounded-full object-cover ring-1 ring-rose-200"
                                        />
                                        <div>
                                            <p class="font-bold text-slate-800 text-xs">{{ payment.booking?.customer?.name || 'Customer' }}</p>
                                            <p class="text-[10px] text-slate-400">{{ payment.booking?.customer?.email || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Salon & Treatment -->
                                <td class="py-4 px-4">
                                    <div class="space-y-0.5 max-w-xs">
                                        <p class="font-bold text-slate-800 truncate">
                                            {{ payment.booking?.artist_profile?.business_name || payment.booking?.artist_profile?.user?.name || 'Salon Studio' }}
                                        </p>
                                        <p class="text-[11px] text-glam-700 font-medium truncate flex items-center gap-1">
                                            <span>💄</span>
                                            <span>{{ payment.booking?.service?.name || 'Treatment' }}</span>
                                            <span class="text-slate-400 font-mono">(#{{ payment.booking?.booking_number }})</span>
                                        </p>
                                    </div>
                                </td>

                                <!-- Financial Split (Gross / Platform / Net) -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs font-serif font-black text-slate-900">{{ formatPrice(payment.amount) }}</span>
                                            <span class="text-[10px] text-slate-400">(Gross)</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-2">
                                            <span class="text-rose-600 font-semibold">Fee: {{ formatPrice(payment.commission_amount || 0) }}</span>
                                            <span>•</span>
                                            <span class="text-emerald-700 font-semibold">Net: {{ formatPrice(payment.net_amount || payment.amount) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Method & Gateway -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wide">
                                            {{ payment.payment_method || 'Cash / COD' }}
                                        </span>
                                        <p v-if="payment.payment_gateway" class="text-[10px] text-slate-400 capitalize">
                                            Gateway: {{ payment.payment_gateway }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <span
                                        v-if="payment.status === 'completed'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        ✓ Completed
                                    </span>
                                    <span
                                        v-else-if="payment.status === 'pending'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse"
                                    >
                                        ⏳ Pending
                                    </span>
                                    <span
                                        v-else-if="payment.status === 'refunded'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200"
                                    >
                                        ↩ Refunded
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                                    >
                                        ✕ Failed
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Mark as Paid -->
                                        <button
                                            v-if="payment.status === 'pending'"
                                            type="button"
                                            class="p-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white transition-colors cursor-pointer"
                                            title="Mark Payment as Received / Completed"
                                            @click="markAsPaid(payment)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>

                                        <!-- Refund Button -->
                                        <button
                                            v-if="payment.status === 'completed'"
                                            type="button"
                                            class="p-2 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition-colors cursor-pointer"
                                            title="Issue Refund"
                                            @click="refundPayment(payment)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>

                                        <!-- Inspect Details Modal -->
                                        <button
                                            type="button"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors cursor-pointer"
                                            title="View Transaction Dossier"
                                            @click="openDetailModal(payment)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-else class="py-12">
                    <AppEmptyState
                        icon="💳"
                        title="No transactions found"
                        description="There are currently no transactions matching your status or search filters."
                        action-text="Reset Filters"
                        @action="clearFilters"
                    />
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="p-4 border-t border-rose-100">
                    <AppPagination :links="payments.links" />
                </div>
            </div>
        </div>

        <!-- 5. TRANSACTION DETAIL MODAL -->
        <div
            v-if="selectedPayment"
            class="fixed inset-0 z-50 overflow-y-auto bg-onyx-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeDetailModal"
        >
            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100 animate-in fade-in zoom-in duration-200">
                <!-- Header -->
                <div class="p-6 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-glam-700">Financial Ledger Entry</span>
                        <h3 class="font-serif text-xl font-bold text-slate-900 mt-0.5">
                            Transaction Dossier #{{ selectedPayment.transaction_id || selectedPayment.id }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-full bg-white text-slate-500 hover:text-slate-900 flex items-center justify-center shadow-xs cursor-pointer"
                        @click="closeDetailModal"
                    >
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Amount & Status Banner -->
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Gross Transaction Value</span>
                            <h2 class="text-3xl font-serif font-black text-slate-900 mt-0.5">
                                {{ formatPrice(selectedPayment.amount) }}
                            </h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                v-if="selectedPayment.status === 'completed'"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                            >
                                ✓ Paid & Settled
                            </span>
                            <span
                                v-else-if="selectedPayment.status === 'pending'"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200"
                            >
                                ⏳ Pending Settlement
                            </span>
                            <span
                                v-else-if="selectedPayment.status === 'refunded'"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200"
                            >
                                ↩ Refunded
                            </span>
                            <span
                                v-else
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200"
                            >
                                ✕ Payment Failed
                            </span>
                        </div>
                    </div>

                    <!-- Revenue Split Breakdown -->
                    <div class="p-5 rounded-2xl bg-rose-50/40 border border-rose-100 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Financial Revenue Split</span>
                        <div class="grid grid-cols-2 gap-4 pt-1">
                            <div class="p-3 bg-white rounded-xl border border-rose-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Marketplace Fee</span>
                                <p class="text-base font-serif font-bold text-rose-700 mt-0.5">
                                    {{ formatPrice(selectedPayment.commission_amount || 0) }}
                                </p>
                            </div>
                            <div class="p-3 bg-white rounded-xl border border-rose-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Salon Net Payout</span>
                                <p class="text-base font-serif font-bold text-emerald-700 mt-0.5">
                                    {{ formatPrice(selectedPayment.net_amount || selectedPayment.amount) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Client & Salon Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Customer -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Client Profile</span>
                            <div class="flex items-center gap-3 mt-2">
                                <img
                                    :src="getAvatar(selectedPayment.booking?.customer?.avatar, selectedPayment.booking?.customer?.name)"
                                    :alt="selectedPayment.booking?.customer?.name"
                                    class="w-10 h-10 rounded-full object-cover ring-2 ring-rose-200"
                                />
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ selectedPayment.booking?.customer?.name || 'Customer' }}</p>
                                    <p class="text-xs text-slate-500">{{ selectedPayment.booking?.customer?.email || 'N/A' }}</p>
                                    <p v-if="selectedPayment.booking?.customer?.phone" class="text-xs text-slate-500">{{ selectedPayment.booking?.customer?.phone }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Salon / Artist -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Salon Studio</span>
                            <div class="flex items-center gap-3 mt-2">
                                <img
                                    :src="getAvatar(selectedPayment.booking?.artist_profile?.profile_image, selectedPayment.booking?.artist_profile?.business_name)"
                                    :alt="selectedPayment.booking?.artist_profile?.business_name"
                                    class="w-10 h-10 rounded-full object-cover ring-2 ring-rose-200"
                                />
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ selectedPayment.booking?.artist_profile?.business_name || 'Salon Studio' }}</p>
                                    <p class="text-xs text-slate-500">📍 {{ selectedPayment.booking?.artist_profile?.city?.name || selectedPayment.booking?.artist_profile?.city || 'Pakistan' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Associated Appointment Details -->
                    <div v-if="selectedPayment.booking" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Linked Appointment</span>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Treatment</span>
                            <span class="font-bold text-slate-800">{{ selectedPayment.booking.service?.name || 'Beauty Treatment' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Booking Reference</span>
                            <span class="font-mono font-bold text-glam-700">#{{ selectedPayment.booking.booking_number }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Booking Status</span>
                            <span class="font-bold uppercase text-emerald-700">{{ selectedPayment.booking.status }}</span>
                        </div>
                    </div>

                    <!-- Notes / Logs -->
                    <div v-if="selectedPayment.notes" class="space-y-1.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Transaction Notes & Audit Log</label>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 whitespace-pre-line">
                            {{ selectedPayment.notes }}
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="p-5 border-t border-rose-100 bg-slate-50 flex items-center justify-between gap-3">
                    <button
                        v-if="selectedPayment.status === 'completed'"
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-purple-700 hover:bg-purple-100 transition cursor-pointer"
                        @click="refundPayment(selectedPayment)"
                    >
                        Issue Refund
                    </button>
                    <div v-else></div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="selectedPayment.status === 'pending'"
                            type="button"
                            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md transition cursor-pointer"
                            @click="markAsPaid(selectedPayment)"
                        >
                            ✓ Mark as Paid
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 transition cursor-pointer"
                            @click="closeDetailModal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
