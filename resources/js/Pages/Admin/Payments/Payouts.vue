<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    payouts: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_disbursed: 0,
            pending_disbursement: 0,
            pending_count: 0,
            completed_count: 0,
            rejected_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchInput = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const loading = ref(false);
const selectedPayout = ref(null);

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
    });
};

const applyFilters = () => {
    loading.value = true;
    router.get(
        route('admin.payments.payouts'),
        {
            search: searchInput.value || undefined,
            status: statusFilter.value || undefined,
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

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff`);
};

const approvePayout = (payout) => {
    Swal.fire({
        title: 'Approve & Disburse Payout?',
        text: `Authorize withdrawal of ${formatPrice(payout.amount)} to "${payout.artist_profile?.business_name || 'Salon'}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Approve Payout',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.payments.approve-payout', payout.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Payout marked as completed/disbursed!',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

const rejectPayout = (payout) => {
    Swal.fire({
        title: 'Reject Payout Request?',
        text: 'Enter reason for rejection (e.g. invalid bank account, KYC unverified):',
        input: 'textarea',
        inputPlaceholder: 'Reason for rejection...',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Reject Request',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a rejection reason.';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.payments.reject-payout', payout.id), {
                reason: result.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: 'Payout request rejected',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Salon Payouts & Withdrawals | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- PAGE TITLE & SUB-NAVIGATION -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">
                        Salon Payouts & Disbursements
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Review, authorize, and disburse withdrawal requests to salon studios and beauty stylists.
                    </p>
                </div>

                <!-- Sub-nav tabs -->
                <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-rose-100 shadow-xs self-start sm:self-auto">
                    <Link
                        :href="route('admin.payments.index')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-rose-50/60 transition-all"
                    >
                        💳 Customer Payments
                    </Link>
                    <Link
                        :href="route('admin.payments.payouts')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-glam-600 text-white shadow-xs"
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

            <!-- STATS RIBBON -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-3xl bg-white border border-emerald-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Total Disbursed</p>
                        <h3 class="text-2xl font-serif font-bold text-slate-900 mt-1">{{ formatPrice(stats.total_disbursed) }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">{{ stats.completed_count }} Completed Transfers</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        ✓
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white border border-amber-200/80 shadow-xs flex items-center justify-between" :class="stats.pending_count > 0 ? 'bg-amber-50/20 ring-2 ring-amber-400/30' : ''">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Approval</p>
                            <span v-if="stats.pending_count > 0" class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        </div>
                        <h3 class="text-2xl font-serif font-bold text-amber-950 mt-1">{{ formatPrice(stats.pending_disbursement) }}</h3>
                        <p class="text-[10px] text-amber-700 font-medium mt-0.5">{{ stats.pending_count }} Requests Awaiting</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl shadow-xs">
                        ⏳
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rejected Requests</p>
                        <h3 class="text-2xl font-serif font-bold text-slate-900 mt-1">{{ stats.rejected_count }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Declined Disbursals</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-xs">
                        ✕
                    </div>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[240px] relative">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Search by payout reference or salon name..."
                        class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                        @keyup.enter="applyFilters"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        🔍
                    </div>
                </div>

                <select
                    v-model="statusFilter"
                    class="py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                    @change="applyFilters"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">⏳ Pending Approval</option>
                    <option value="completed">✓ Completed / Disbursed</option>
                    <option value="rejected">✕ Rejected</option>
                </select>
            </div>

            <!-- PAYOUTS TABLE -->
            <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <div v-if="payouts.data && payouts.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4 sm:px-6">Ref #</th>
                                <th class="py-3.5 px-4">Salon Studio</th>
                                <th class="py-3.5 px-4">Amount</th>
                                <th class="py-3.5 px-4">Disbursement Method</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4">Requested On</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr v-for="payout in payouts.data" :key="payout.id" class="hover:bg-rose-50/30 transition-colors">
                                <td class="py-4 px-4 sm:px-6 font-mono font-bold text-slate-900 whitespace-nowrap">
                                    #{{ payout.reference_number || ('PAYOUT-' + payout.id) }}
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <img
                                            :src="getAvatar(payout.artist_profile?.profile_image, payout.artist_profile?.business_name)"
                                            :alt="payout.artist_profile?.business_name"
                                            class="w-8 h-8 rounded-full object-cover ring-1 ring-rose-200"
                                        />
                                        <div>
                                            <p class="font-bold text-slate-800 text-xs">{{ payout.artist_profile?.business_name || 'Salon' }}</p>
                                            <p class="text-[10px] text-slate-400">📍 {{ payout.artist_profile?.city?.name || 'Pakistan' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 font-serif font-bold text-sm text-slate-900 whitespace-nowrap">
                                    {{ formatPrice(payout.amount) }}
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 uppercase">
                                        {{ payout.payment_method || 'Bank Transfer' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <span
                                        v-if="payout.status === 'completed'"
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        ✓ Disbursed
                                    </span>
                                    <span
                                        v-else-if="payout.status === 'pending'"
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse"
                                    >
                                        ⏳ Pending
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                                    >
                                        ✕ Rejected
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap">
                                    {{ formatDate(payout.created_at) }}
                                </td>
                                <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="payout.status === 'pending'"
                                            type="button"
                                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition cursor-pointer"
                                            @click="approvePayout(payout)"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            v-if="payout.status === 'pending'"
                                            type="button"
                                            class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition cursor-pointer"
                                            @click="rejectPayout(payout)"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-12">
                    <AppEmptyState
                        icon="🏦"
                        title="No payout requests found"
                        description="There are currently no payout requests matching your status filter."
                    />
                </div>

                <div v-if="payouts.links && payouts.links.length > 3" class="p-4 border-t border-rose-100">
                    <AppPagination :links="payouts.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
