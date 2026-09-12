<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';


const props = defineProps({
    customers: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
            new_this_month: 0,
        }),
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const actionLoading = ref(false);
const activeCustomer = ref(null);
const actionType = ref(''); // 'status' | 'delete'
const showActionModal = ref(false);

const filterStatuses = [
    { key: '', label: 'All Customers', count: props.stats?.total ?? 0 },
    { key: 'active', label: 'Active', count: props.stats?.active ?? 0 },
    { key: 'inactive', label: 'Inactive', count: props.stats?.inactive ?? 0 },
];

const applyFilters = () => {
    router.get(
        route('admin.customers.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const setStatus = (statusKey) => {
    statusFilter.value = statusKey;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    router.get(route('admin.customers.index'));
};

const openActionModal = (customer, type) => {
    activeCustomer.value = customer;
    actionType.value = type;
    showActionModal.value = true;
};

const closeActionModal = () => {
    showActionModal.value = false;
    activeCustomer.value = null;
    actionType.value = '';
};

const submitAction = () => {
    if (!activeCustomer.value) return;
    actionLoading.value = true;

    if (actionType.value === 'status') {
        const nextStatus = !activeCustomer.value.is_active;
        const customerName = activeCustomer.value.name;
        router.post(
            route('admin.customers.update-status', activeCustomer.value.id),
            { is_active: nextStatus },
            {
                preserveScroll: true,
                onSuccess: () => {
                    actionLoading.value = false;
                    closeActionModal();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Customer '${customerName}' ${nextStatus ? 'activated' : 'deactivated'} successfully!`,
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
                onError: () => {
                    actionLoading.value = false;
                },
                onFinish: () => {
                    actionLoading.value = false;
                    closeActionModal();
                },
            }
        );
    } else if (actionType.value === 'delete') {
        const customerName = activeCustomer.value.name;
        router.delete(route('admin.customers.destroy', activeCustomer.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                actionLoading.value = false;
                closeActionModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Customer '${customerName}' deleted successfully!`,
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onError: () => {
                actionLoading.value = false;
            },
            onFinish: () => {
                actionLoading.value = false;
                closeActionModal();
            },
        });
    }
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
</script>

<template>
    <AdminLayout>
        <Head title="Customer Directory - Admin Portal" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Community & Client Management
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        Customer Accounts
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Manage registered client accounts, monitor purchase history, and control access.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200/80 hover:bg-slate-50 shadow-xs transition-all"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                    <Link
                        :href="route('admin.users.index', { role: 'customer' })"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-sm transition-all"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Add Customer</span>
                    </Link>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Clients</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats?.total ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">All registered accounts</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Accounts</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ stats?.active ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Eligible to book</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Restricted</p>
                    <p class="text-2xl font-extrabold text-rose-600 mt-1">{{ stats?.inactive ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Deactivated accounts</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">New This Month</p>
                    <p class="text-2xl font-extrabold text-violet-600 mt-1">+{{ stats?.new_this_month ?? 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Recent registrations</p>
                </div>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                <button
                    v-for="item in filterStatuses"
                    :key="item.key"
                    @click="setStatus(item.key)"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 border"
                    :class="[
                        statusFilter === item.key
                            ? 'bg-slate-900 text-white border-slate-900 shadow-md shadow-slate-900/10 scale-102'
                            : 'bg-white text-slate-600 border-slate-200/80 hover:bg-slate-50 hover:text-slate-900'
                    ]"
                >
                    <span>{{ item.label }}</span>
                    <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                        :class="[
                            statusFilter === item.key
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-600'
                        ]"
                    >
                        {{ item.count }}
                    </span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="rounded-3xl bg-white p-4 sm:p-5 shadow-sm border border-slate-200/80">
                <div class="relative flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by customer name, @username, email address, or phone..."
                        class="w-full pl-10 pr-24 py-2.5 rounded-2xl border border-slate-200 bg-slate-50/50 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all outline-none"
                        @keyup.enter="applyFilters"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <div class="absolute right-2 top-1.5 flex items-center gap-1">
                        <button
                            v-if="search"
                            @click="search = ''; applyFilters()"
                            class="p-1.5 text-slate-400 hover:text-slate-600"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button
                            @click="applyFilters"
                            class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all"
                        >
                            Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Customers Data Table -->
            <div class="rounded-3xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/70 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-5">Customer</th>
                                <th class="py-3.5 px-4">Contact</th>
                                <th class="py-3.5 px-4">Engagement</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Joined</th>
                                <th class="py-3.5 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                class="hover:bg-slate-50/70 transition-colors group"
                            >
                                <!-- Customer Identity -->
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3.5">
                                        <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-600 flex items-center justify-center font-extrabold text-white text-sm shrink-0 shadow-xs">
                                            {{ customer.name?.charAt(0)?.toUpperCase() || 'C' }}
                                        </div>
                                        <div class="min-w-0">
                                            <Link
                                                :href="route('admin.customers.show', customer.id)"
                                                class="font-bold text-slate-900 group-hover:text-rose-600 transition-colors truncate block max-w-[180px]"
                                            >
                                                {{ customer.name }}
                                            </Link>
                                            <p class="text-xs text-slate-400 truncate">
                                                @{{ customer.username || `user${customer.id}` }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact Info -->
                                <td class="py-4 px-4 text-xs">
                                    <p class="font-medium text-slate-800 truncate max-w-[180px]">{{ customer.email }}</p>
                                    <p class="text-slate-400 mt-0.5">{{ customer.phone || 'No phone recorded' }}</p>
                                </td>

                                <!-- Engagement Stats -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 font-bold">
                                            {{ customer.product_orders_count || customer.orders_count || 0 }} Orders
                                        </span>
                                        <span class="text-slate-400">•</span>
                                        <span class="text-slate-600 font-medium">
                                            {{ customer.reviews_count || 0 }} Reviews
                                        </span>
                                    </div>
                                </td>

                                <!-- Account Status -->
                                <td class="py-4 px-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border ring-1 ring-inset"
                                        :class="customer.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/10' : 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-500/10'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="customer.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ customer.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <!-- Joined Date -->
                                <td class="py-4 px-4 text-xs text-slate-500 whitespace-nowrap">
                                    {{ formatDate(customer.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="route('admin.customers.show', customer.id)"
                                            class="px-2.5 py-1 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors"
                                        >
                                            View
                                        </Link>

                                        <button
                                            @click="openActionModal(customer, 'status')"
                                            class="px-2.5 py-1 rounded-xl text-xs font-bold transition-colors"
                                            :class="customer.is_active ? 'bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white'"
                                        >
                                            {{ customer.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>

                                        <button
                                            @click="openActionModal(customer, 'delete')"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Delete Customer Account"
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

                <!-- Empty State -->
                <div v-if="customers.data.length === 0" class="py-16 text-center px-4">
                    <div class="h-16 w-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No Customers Found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        No customer accounts match your search criteria. Try modifying your keywords or clearing the filters.
                    </p>
                    <button
                        @click="resetFilters"
                        class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 transition-colors"
                    >
                        Clear Search
                    </button>
                </div>

                <!-- Pagination Footer -->
                <div v-if="customers.links && customers.total > 0" class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/40">
                    <div class="text-xs text-slate-500">
                        Showing <span class="font-bold text-slate-700">{{ customers.from || 0 }}</span> to <span class="font-bold text-slate-700">{{ customers.to || 0 }}</span> of <span class="font-bold text-slate-700">{{ customers.total }}</span> clients
                    </div>

                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in customers.links"
                            :key="idx"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                                link.active
                                    ? 'bg-rose-600 text-white shadow-sm shadow-rose-500/20'
                                    : 'bg-white text-slate-700 border border-slate-200/80 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Confirmation Modal -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showActionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-2xl flex items-center justify-center shrink-0"
                            :class="actionType === 'status' ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'"
                        >
                            <svg v-if="actionType === 'status'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                {{ actionType === 'status' ? (activeCustomer?.is_active ? 'Deactivate Customer' : 'Activate Customer') : 'Delete Customer Account' }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                {{ activeCustomer?.name }} ({{ activeCustomer?.email }})
                            </p>
                        </div>
                    </div>

                    <div v-if="actionType === 'status'">
                        <p class="text-sm text-slate-600">
                            {{ activeCustomer?.is_active
                                ? 'Deactivating this account will prevent the customer from logging in and placing new product orders.'
                                : 'Activating this account will restore customer access and allow shopping orders to be placed.' }}
                        </p>
                    </div>

                    <div v-else-if="actionType === 'delete'">
                        <p class="text-sm text-rose-600 font-medium">
                            Warning: Deleting this user will remove their profile and associated customer data. This action is permanent.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            @click="closeActionModal"
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
                            :disabled="actionLoading"
                        >
                            Cancel
                        </button>

                        <button
                            @click="submitAction"
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white shadow-md transition-all"
                            :class="actionType === 'status' ? 'bg-slate-900 hover:bg-slate-800' : 'bg-red-600 hover:bg-red-700'"
                            :disabled="actionLoading"
                        >
                            {{ actionLoading ? 'Processing...' : 'Confirm' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AdminLayout>
</template>
