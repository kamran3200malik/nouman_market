<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import Swal from 'sweetalert2';

const page = usePage();

const props = defineProps({
    sellers: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_requests: 0,
            pending_requests: 0,
            approved_sellers: 0,
            rejected_sellers: 0,
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

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

watch(() => page.props.flash?.success, (msg) => {
    if (msg) showToast(msg, 'success');
});
watch(() => page.props.flash?.error, (msg) => {
    if (msg) showToast(msg, 'error');
});

const searchInput = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'all');

const applyFilters = () => {
    router.get(route('admin.products.sellers'), {
        search: searchInput.value || undefined,
        status: activeStatus.value !== 'all' ? activeStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const setStatusFilter = (status) => {
    activeStatus.value = status;
    applyFilters();
};

const formatDate = (dateString) => {
    if (!dateString) return 'Not signed';
    const d = new Date(dateString);
    return d.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Authorize Modal
const showAuthorizeModal = ref(false);
const selectedArtist = ref(null);
const authorizeRate = ref(10.00);
const isAuthorizing = ref(false);

const openAuthorizeModal = (artist) => {
    selectedArtist.value = artist;
    authorizeRate.value = parseFloat(artist.product_commission_rate || 10.00);
    showAuthorizeModal.value = true;
};

const submitAuthorization = () => {
    if (!selectedArtist.value) return;
    isAuthorizing.value = true;
    router.post(route('admin.products.sellers.authorize', selectedArtist.value.id), {
        commission_rate: authorizeRate.value
    }, {
        onSuccess: () => {
            isAuthorizing.value = false;
            showAuthorizeModal.value = false;
            showToast(`Authorized ${selectedArtist.value.business_name} to sell products! 🔔 Notification sent.`, 'success');
        },
        onError: (errors) => {
            isAuthorizing.value = false;
            showToast(Object.values(errors)[0] || 'Authorization failed', 'error');
        }
    });
};

// Reject Modal
const showRejectModal = ref(false);
const rejectionReason = ref('');
const isRejecting = ref(false);

const openRejectModal = (artist) => {
    selectedArtist.value = artist;
    rejectionReason.value = 'Incomplete payout information or unverified vendor credentials.';
    showRejectModal.value = true;
};

const submitRejection = () => {
    if (!selectedArtist.value) return;
    isRejecting.value = true;
    router.post(route('admin.products.sellers.reject', selectedArtist.value.id), {
        rejection_reason: rejectionReason.value
    }, {
        onSuccess: () => {
            isRejecting.value = false;
            showRejectModal.value = false;
            showToast(`Seller request rejected. Vendor has been notified.`, 'info');
        },
        onError: () => {
            isRejecting.value = false;
            showToast('Failed to reject', 'error');
        }
    });
};

const statusTabs = [
    { value: 'all', label: 'All Requests' },
    { value: 'pending', label: 'Pending Review', countKey: 'pending_requests' },
    { value: 'approved', label: 'Authorized Sellers', countKey: 'approved_sellers' },
    { value: 'rejected', label: 'Rejected', countKey: 'rejected_sellers' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Vendor & Seller Authorization CRM" />

        <div class="space-y-6">
            <!-- Header section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Vendor Management</span>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">Vendor & Seller Authorizations</h1>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Review vendor digital commission agreements, set platform commission rates, and authorize merchants to list beauty products.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.products.orders')"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 shadow-2xs hover:bg-slate-50 hover:text-rose-600 transition"
                    >
                        <span>📦</span>
                        <span>Product Orders</span>
                    </Link>
                    <Link
                        :href="route('admin.products.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-rose-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition"
                    >
                        <span>🧴</span>
                        <span>Product Catalog</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Applications</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 text-sm">📝</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-slate-900 mt-2">{{ stats.total_requests }}</p>
                    <span class="text-[10px] text-slate-400">Seller agreement submissions</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600">Pending Authorization</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 text-sm">⏳</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-amber-600 mt-2">{{ stats.pending_requests }}</p>
                    <span class="text-[10px] text-amber-600 font-medium">Requires admin sign-off</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600">Authorized Sellers</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 text-sm">✓</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-emerald-600 mt-2">{{ stats.approved_sellers }}</p>
                    <span class="text-[10px] text-emerald-600 font-medium">Active product vendors</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Rejected / Suspended</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 text-sm">✕</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-rose-600 mt-2">{{ stats.rejected_sellers }}</p>
                    <span class="text-[10px] text-rose-600 font-medium">Applications declined</span>
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

            <!-- Search Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                <div class="relative w-full sm:w-96">
                    <input
                        v-model="searchInput"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by store name, vendor, email, phone, or bank title..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white focus:ring-0"
                    />
                </div>
            </div>

            <!-- Sellers Table -->
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Vendor / Store Name</th>
                                <th class="px-4 py-3.5">Owner & Location</th>
                                <th class="px-4 py-3.5">Bank Payout Info</th>
                                <th class="px-4 py-3.5">Commission Agreement</th>
                                <th class="px-4 py-3.5 text-center">Seller Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="artist in sellers.data"
                                :key="artist.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <!-- Store / Vendor Name -->
                                <td class="px-5 py-4">
                                    <div class="font-serif font-bold text-slate-900 text-sm">
                                        {{ artist.seller_store_name || artist.business_name }}
                                    </div>
                                    <span class="text-[10px] text-slate-400">
                                        {{ artist.business_name }}
                                    </span>
                                </td>

                                <!-- Owner & City -->
                                <td class="px-4 py-4">
                                    <div class="min-w-0 max-w-xs">
                                        <span class="font-bold text-slate-900 block">{{ artist.user?.name }}</span>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                            <span>{{ artist.user?.email }}</span>
                                            <span>•</span>
                                            <span class="text-rose-600 font-semibold">{{ artist.city?.name || 'Pakistan' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Bank Info -->
                                <td class="px-4 py-4">
                                    <div class="min-w-0 max-w-xs">
                                        <span class="font-semibold text-slate-800 block">{{ artist.seller_bank_name || 'Bank Not Set' }}</span>
                                        <span class="text-[11px] text-slate-500 block">Title: {{ artist.seller_bank_title || '-' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ artist.seller_bank_account || '-' }}</span>
                                    </div>
                                </td>

                                <!-- Agreement Date & Commission -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">
                                        {{ parseFloat(artist.product_commission_rate || 10).toFixed(0) }}% Platform Fee
                                    </div>
                                    <span class="text-[10px] text-slate-400 block">
                                        Signed: {{ formatDate(artist.seller_agreement_signed_at) }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span
                                        :class="artist.product_seller_status === 'approved' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : (artist.product_seller_status === 'pending' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-rose-100 text-rose-800 border-rose-200')"
                                        class="inline-block rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider border shadow-2xs"
                                    >
                                        {{ artist.product_seller_status === 'approved' ? 'Authorized Seller' : (artist.product_seller_status === 'pending' ? 'Pending Review' : 'Rejected') }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openAuthorizeModal(artist)"
                                            type="button"
                                            class="rounded-lg bg-slate-900 hover:bg-emerald-600 text-white px-3 py-1.5 font-bold transition text-xs cursor-pointer"
                                        >
                                            {{ artist.product_seller_status === 'approved' ? 'Edit Commission' : 'Authorize Seller' }}
                                        </button>
                                        <button
                                            v-if="artist.product_seller_status !== 'rejected'"
                                            @click="openRejectModal(artist)"
                                            type="button"
                                            class="rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 font-bold transition text-xs cursor-pointer"
                                            title="Reject / Suspend"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="sellers.data.length === 0" class="text-center py-16 space-y-2">
                        <span class="text-3xl block">📝</span>
                        <p class="text-sm font-semibold text-slate-700">No seller requests found</p>
                        <p class="text-xs text-slate-400">When vendors apply to sell their beauty products and sign the agreement, they appear here.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="sellers.links && sellers.links.length > 3" class="p-4 border-t border-slate-100 flex justify-center">
                    <AppPagination :links="sellers.links" />
                </div>
            </div>
        </div>

        <!-- AUTHORIZE MODAL -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showAuthorizeModal && selectedArtist"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 text-left space-y-4"
                >
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Marketplace Authorization</span>
                        <h3 class="font-serif text-lg font-bold text-slate-900">Authorize {{ selectedArtist.business_name }}</h3>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200 text-xs space-y-2 text-slate-700">
                        <p><strong>Store Name:</strong> {{ selectedArtist.seller_store_name || selectedArtist.business_name }}</p>
                        <p><strong>Owner:</strong> {{ selectedArtist.user?.name }} ({{ selectedArtist.user?.email }})</p>
                        <p><strong>Bank Account:</strong> {{ selectedArtist.seller_bank_name }} - {{ selectedArtist.seller_bank_account }} ({{ selectedArtist.seller_bank_title }})</p>
                        <p><strong>Agreement Signed At:</strong> {{ formatDate(selectedArtist.seller_agreement_signed_at) }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Set Platform Commission Rate (%) *</label>
                        <input
                            v-model="authorizeRate"
                            required
                            type="number"
                            step="0.5"
                            min="0"
                            max="100"
                            placeholder="10.00"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-rose-500 focus:bg-white"
                        />
                        <span class="text-[11px] text-slate-400 mt-1 block">Standard rate is 10.00% (Vendor receives 90.00% on each product sale).</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                        <button
                            @click="showAuthorizeModal = false; selectedArtist = null;"
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            :disabled="isAuthorizing"
                            @click="submitAuthorization"
                            type="button"
                            class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition cursor-pointer disabled:opacity-50"
                        >
                            {{ isAuthorizing ? 'Authorizing...' : 'Authorize Seller' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- REJECT MODAL -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showRejectModal && selectedArtist"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 text-left space-y-4"
                >
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Decline Application</span>
                        <h3 class="font-serif text-lg font-bold text-slate-900">Decline {{ selectedArtist.business_name }}</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Rejection / Suspension *</label>
                        <textarea
                            v-model="rejectionReason"
                            required
                            rows="3"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            @click="showRejectModal = false; selectedArtist = null;"
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            :disabled="isRejecting"
                            @click="submitRejection"
                            type="button"
                            class="rounded-xl bg-rose-600 hover:bg-rose-700 px-5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-md transition cursor-pointer disabled:opacity-50"
                        >
                            {{ isRejecting ? 'Submitting...' : 'Confirm Reject' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AdminLayout>
</template>
