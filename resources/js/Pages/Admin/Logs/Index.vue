<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    logs: {
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
            total_logs: 0,
            today_logs: 0,
            today_logins: 0,
            today_failed_logins: 0,
            today_crud: 0,
            active_users_today: 0,
        }),
    },
    modulesList: {
        type: Array,
        default: () => [],
    },
    actionsList: {
        type: Array,
        default: () => [],
    },
});

// Filter states
const search = ref(props.filters.search || '');
const actionFilter = ref(props.filters.action || 'all');
const moduleFilter = ref(props.filters.module || 'all');
const roleFilter = ref(props.filters.role || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Inspection Modal state
const selectedLog = ref(null);
const showDetailModal = ref(false);

// Purge Modal state
const showPurgeModal = ref(false);
const purgePeriod = ref('30_days');
const purging = ref(false);

const quickTabs = computed(() => [
    { key: 'all', label: 'All Activity', count: props.stats?.total_logs ?? 0 },
    { key: 'login_logs', label: '🔑 Login & Auth', count: props.stats?.today_logins ?? 0 },
    { key: 'crud_creates', label: '➕ Created', count: null },
    { key: 'crud_updates', label: '✏️ Updated', count: null },
    { key: 'crud_deletes', label: '🗑️ Deleted', count: null },
    { key: 'status_changes', label: '🔄 Status Changed', count: null },
]);

const applyFilters = () => {
    router.get(
        route('admin.logs.index'),
        {
            search: search.value || undefined,
            action: actionFilter.value !== 'all' ? actionFilter.value : undefined,
            module: moduleFilter.value !== 'all' ? moduleFilter.value : undefined,
            role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const setQuickTab = (tabKey) => {
    actionFilter.value = tabKey;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    actionFilter.value = 'all';
    moduleFilter.value = 'all';
    roleFilter.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(route('admin.logs.index'));
};

const exportLogs = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (actionFilter.value !== 'all') params.append('action', actionFilter.value);
    if (moduleFilter.value !== 'all') params.append('module', moduleFilter.value);
    if (roleFilter.value !== 'all') params.append('role', roleFilter.value);
    if (dateFrom.value) params.append('date_from', dateFrom.value);
    if (dateTo.value) params.append('date_to', dateTo.value);

    window.location.href = `${route('admin.logs.export')}?${params.toString()}`;
};

const openDetailModal = (log) => {
    selectedLog.value = log;
    showDetailModal.value = true;
};

const deleteLog = (log) => {
    Swal.fire({
        title: 'Delete Activity Log?',
        text: `Are you sure you want to remove audit log #${log.id}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete log',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.logs.destroy', log.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Log entry deleted.',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
            });
        }
    });
};

const executePurge = () => {
    purging.value = true;
    router.post(
        route('admin.logs.clear'),
        { period: purgePeriod.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                purging.value = false;
                showPurgeModal.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Logs Purged',
                    text: 'Activity log records have been cleaned up according to your retention policy.',
                });
            },
            onError: () => {
                purging.value = false;
            },
        }
    );
};

const getActionBadgeClass = (action) => {
    switch (action) {
        case 'login':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'logout':
            return 'bg-slate-100 text-slate-600 border-slate-200';
        case 'failed_login':
            return 'bg-rose-50 text-rose-700 border-rose-200 font-bold';
        case 'registered':
            return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'password_reset':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'create':
            return 'bg-green-50 text-green-700 border-green-200';
        case 'update':
            return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'delete':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'status_change':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'backup_create':
        case 'backup_restore':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const getRoleBadgeClass = (role) => {
    if (!role) return 'bg-slate-100 text-slate-600 border-slate-200';
    const r = role.toLowerCase();
    if (r.includes('admin')) return 'bg-rose-50 text-rose-700 border-rose-200';
    if (r.includes('artist')) return 'bg-purple-50 text-purple-700 border-purple-200';
    if (r.includes('customer')) return 'bg-sky-50 text-sky-700 border-sky-200';
    if (r.includes('guest')) return 'bg-slate-100 text-slate-600 border-slate-200';
    return 'bg-slate-100 text-slate-600 border-slate-200';
};

const parseBrowserAgent = (agent) => {
    if (!agent) return 'Web Client';
    if (agent.includes('Firefox')) return 'Firefox';
    if (agent.includes('Edg/')) return 'MS Edge';
    if (agent.includes('Chrome')) return 'Chrome';
    if (agent.includes('Safari') && !agent.includes('Chrome')) return 'Safari';
    if (agent.includes('Postman')) return 'Postman API';
    if (agent.includes('Mobile') || agent.includes('Android') || agent.includes('iPhone')) return 'Mobile Browser';
    return 'Web Browser';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(date);
};

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return `${seconds}s ago`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
};

const formatJsonDisplay = (obj) => {
    if (!obj) return 'None';
    if (typeof obj === 'string') {
        try {
            return JSON.stringify(JSON.parse(obj), null, 2);
        } catch (e) {
            return obj;
        }
    }
    return JSON.stringify(obj, null, 2);
};
</script>

<template>
    <AdminLayout>
        <Head title="Activity & Audit Logs - Admin Portal" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            System Auditing & Security Logs
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        Activity & Audit Logs
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Real-time audit trail of all authentication sessions, CRUD mutations, status updates, and administrative activities across the marketplace.
                    </p>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <button
                        @click="exportLogs"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200/80 hover:bg-slate-50 hover:text-slate-900 shadow-xs transition-all cursor-pointer"
                        title="Download CSV report"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>

                    <button
                        @click="showPurgeModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-all shadow-xs cursor-pointer"
                        title="Clean older audit logs"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Purge Logs
                    </button>

                    <button
                        @click="applyFilters"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200/80 hover:bg-slate-50 shadow-xs transition-all cursor-pointer"
                        title="Refresh list"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Card 1: Total Logs -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Logs</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ stats.total_logs.toLocaleString() }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ stats.today_logs }} recorded today</p>
                </div>

                <!-- Card 2: Logins Today -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Logins Today</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ stats.today_logins }}</p>
                    <p class="text-xs text-slate-400 mt-1">Successful user sessions</p>
                </div>

                <!-- Card 3: Failed Logins -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Failed Logins</p>
                    <p class="text-2xl font-extrabold mt-1" :class="stats.today_failed_logins > 0 ? 'text-rose-600' : 'text-slate-900'">
                        {{ stats.today_failed_logins }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Security alerts today</p>
                </div>

                <!-- Card 4: CRUD Actions -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">CRUD Actions</p>
                    <p class="text-2xl font-extrabold text-sky-600 mt-1">{{ stats.today_crud }}</p>
                    <p class="text-xs text-slate-400 mt-1">Creates / Updates / Deletes</p>
                </div>

                <!-- Card 5: Active Users -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Users</p>
                    <p class="text-2xl font-extrabold text-purple-600 mt-1">{{ stats.active_users_today }}</p>
                    <p class="text-xs text-slate-400 mt-1">Unique accounts today</p>
                </div>

                <!-- Card 6: Modules Audited -->
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Audited Modules</p>
                    <p class="text-2xl font-extrabold text-teal-600 mt-1">{{ modulesList.length || 12 }}</p>
                    <p class="text-xs text-slate-400 mt-1">System entities tracked</p>
                </div>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
                <button
                    v-for="item in quickTabs"
                    :key="item.key"
                    @click="setQuickTab(item.key)"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 border cursor-pointer"
                    :class="[
                        actionFilter === item.key
                            ? 'bg-slate-900 text-white border-slate-900 shadow-md shadow-slate-900/10 scale-102'
                            : 'bg-white text-slate-600 border-slate-200/80 hover:bg-slate-50 hover:text-slate-900'
                    ]"
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.count !== null"
                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                        :class="[
                            actionFilter === item.key
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-600'
                        ]"
                    >
                        {{ item.count }}
                    </span>
                </button>
            </div>

            <!-- Comprehensive Search & Filter Bar -->
            <div class="rounded-3xl bg-white p-4 sm:p-5 shadow-sm border border-slate-200/80 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3">
                    <!-- Search Input -->
                    <div class="relative lg:col-span-4">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search description, user, IP, entity..."
                            class="w-full pl-10 pr-10 py-2.5 rounded-2xl border border-slate-200 bg-slate-50/50 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all outline-none"
                            @keyup.enter="applyFilters"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button
                            v-if="search"
                            @click="search = ''; applyFilters()"
                            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Module Select -->
                    <div class="lg:col-span-3">
                        <select
                            v-model="moduleFilter"
                            @change="applyFilters"
                            class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700 focus:bg-white focus:border-rose-500 outline-none cursor-pointer"
                        >
                            <option value="all">All Modules</option>
                            <option value="Auth">Auth & Sessions</option>
                            <option value="User">Users & Customers</option>
                            <option value="Product">Products & Catalog</option>
                            <option value="ProductOrder">Orders & Checkout</option>
                            <option value="Category">Categories</option>
                            <option value="Review">Product Reviews</option>
                            <option value="Banner">Hero Banners</option>
                            <option value="Page">CMS Pages</option>
                            <option value="Setting">System Settings</option>
                            <option value="System">System Actions</option>
                            <option v-for="mod in modulesList.filter(m => !['Auth','User','Product','ProductOrder','Category','Review','Banner','Page','Setting','System'].includes(m))" :key="mod" :value="mod">
                                {{ mod }}
                            </option>
                        </select>
                    </div>

                    <!-- Role Select -->
                    <div class="lg:col-span-2">
                        <select
                            v-model="roleFilter"
                            @change="applyFilters"
                            class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700 focus:bg-white focus:border-rose-500 outline-none cursor-pointer"
                        >
                            <option value="all">All Actor Roles</option>
                            <option value="admin">Admins</option>
                            <option value="artist">Artists</option>
                            <option value="customer">Customers</option>
                            <option value="guest">Guests</option>
                        </select>
                    </div>

                    <!-- Date Range & Actions -->
                    <div class="flex items-center gap-2 lg:col-span-3">
                        <input
                            v-model="dateFrom"
                            type="date"
                            @change="applyFilters"
                            class="w-full px-3 py-2 rounded-2xl border border-slate-200 bg-slate-50 text-xs text-slate-700 outline-none"
                            title="From Date"
                        />
                        <span class="text-xs text-slate-400">to</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            @change="applyFilters"
                            class="w-full px-3 py-2 rounded-2xl border border-slate-200 bg-slate-50 text-xs text-slate-700 outline-none"
                            title="To Date"
                        />
                        <button
                            @click="applyFilters"
                            class="px-4 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all shrink-0 cursor-pointer shadow-sm"
                        >
                            Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="rounded-3xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                <div v-if="logs.data && logs.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-5 py-4">Actor / User</th>
                                <th class="px-5 py-4">Action</th>
                                <th class="px-5 py-4">Module & Entity</th>
                                <th class="px-5 py-4">Description</th>
                                <th class="px-5 py-4">Client & IP</th>
                                <th class="px-5 py-4">Timestamp</th>
                                <th class="px-5 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-slate-50/80 transition-colors group"
                            >
                                <!-- Actor / User -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 overflow-hidden text-xs font-bold text-slate-700">
                                            <img
                                                v-if="log.user?.avatar_url"
                                                :src="log.user.avatar_url"
                                                alt="avatar"
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else>{{ log.user_name ? log.user_name.charAt(0).toUpperCase() : '?' }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate flex items-center gap-1.5">
                                                {{ log.user_name || 'System / Guest' }}
                                                <span
                                                    class="inline-block px-1.5 py-0.2 rounded text-[10px] font-semibold border"
                                                    :class="getRoleBadgeClass(log.user_role)"
                                                >
                                                    {{ log.user_role || 'guest' }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 truncate">
                                                {{ log.user_email || 'No email associated' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Action Badge -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-xl text-[11px] font-bold border uppercase tracking-wider"
                                        :class="getActionBadgeClass(log.action)"
                                    >
                                        {{ log.action.replace('_', ' ') }}
                                    </span>
                                </td>

                                <!-- Module & Entity -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div>
                                        <span class="inline-block px-2.5 py-0.5 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ log.module }}
                                        </span>
                                        <div v-if="log.entity_name" class="text-[11px] text-slate-500 font-mono mt-0.5 truncate max-w-[150px]" :title="log.entity_name">
                                            {{ log.entity_name }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="px-5 py-4">
                                    <div class="text-slate-800 line-clamp-2 text-xs leading-relaxed max-w-sm font-medium">
                                        {{ log.description }}
                                    </div>
                                    <div v-if="log.old_values || log.new_values" class="mt-1">
                                        <button
                                            @click="openDetailModal(log)"
                                            class="inline-flex items-center gap-1 text-[11px] text-rose-600 hover:text-rose-700 transition-colors font-bold cursor-pointer"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View change diff
                                        </button>
                                    </div>
                                </td>

                                <!-- Client & IP -->
                                <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                                    <div class="font-mono text-xs font-semibold text-slate-800">
                                        {{ log.ip_address || '—' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-[130px]" :title="log.user_agent">
                                        {{ parseBrowserAgent(log.user_agent) }}
                                    </div>
                                </td>

                                <!-- Timestamp -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-slate-800 font-bold">
                                        {{ formatTimeAgo(log.created_at) }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ formatDate(log.created_at) }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openDetailModal(log)"
                                            class="p-2 rounded-xl bg-slate-50 text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition-colors cursor-pointer"
                                            title="Inspect Audit Log"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteLog(log)"
                                            class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer"
                                            title="Delete Log"
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
                <div v-else class="py-16 px-4">
                    <AppEmptyState
                        title="No activity logs found"
                        description="Try adjusting your filters, search term, or date range to see logged events."
                    >
                        <template #actions>
                            <button
                                @click="resetFilters"
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm cursor-pointer"
                            >
                                Reset Filters
                            </button>
                        </template>
                    </AppEmptyState>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="logs.links && logs.links.length > 3"
                    class="px-5 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500"
                >
                    <div>
                        Showing <span class="font-bold text-slate-800">{{ logs.from || 0 }}</span> to
                        <span class="font-bold text-slate-800">{{ logs.to || 0 }}</span> of
                        <span class="font-bold text-slate-800">{{ logs.total }}</span> activity logs
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in logs.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all"
                            :class="[
                                link.active
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : link.url
                                        ? 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200/80'
                                        : 'bg-slate-50 text-slate-400 cursor-not-allowed border border-slate-200/50'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= LOG DETAIL & DIFF MODAL ======================= -->
        <div
            v-if="showDetailModal && selectedLog"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="showDetailModal = false"
        >
            <div class="bg-white border border-slate-200 rounded-3xl w-full max-w-3xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-3">
                        <span
                            class="px-3 py-1 rounded-xl text-xs font-bold border uppercase tracking-wider"
                            :class="getActionBadgeClass(selectedLog.action)"
                        >
                            {{ selectedLog.action }}
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                Audit Log #{{ selectedLog.id }}
                                <span class="text-xs font-normal text-slate-400">({{ selectedLog.module }})</span>
                            </h3>
                            <div class="text-xs text-slate-500">{{ formatDate(selectedLog.created_at) }}</div>
                        </div>
                    </div>
                    <button
                        @click="showDetailModal = false"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1 text-xs">
                    <!-- Description -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Event Description</div>
                        <div class="text-sm font-bold text-slate-900">{{ selectedLog.description }}</div>
                    </div>

                    <!-- Actor & Request Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Actor Info -->
                        <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Actor Details</div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Name:</span>
                                <span class="font-bold text-slate-900">{{ selectedLog.user_name || 'System' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Email:</span>
                                <span class="font-mono text-slate-700">{{ selectedLog.user_email || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Role:</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="getRoleBadgeClass(selectedLog.user_role)">
                                    {{ selectedLog.user_role || 'guest' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">User ID:</span>
                                <span class="font-mono text-slate-700">{{ selectedLog.user_id || 'N/A' }}</span>
                            </div>
                        </div>

                        <!-- Request Context -->
                        <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Request Context</div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">IP Address:</span>
                                <span class="font-mono font-bold text-emerald-700">{{ selectedLog.ip_address || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">HTTP Method:</span>
                                <span class="font-mono px-2 py-0.5 rounded bg-slate-200/80 text-slate-800 font-bold">{{ selectedLog.method || 'CLI' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Entity Target:</span>
                                <span class="font-mono text-slate-700">{{ selectedLog.entity_type ? selectedLog.entity_type.split('\\').pop() : '—' }} #{{ selectedLog.entity_id || '—' }}</span>
                            </div>
                            <div class="flex flex-col gap-1 pt-1">
                                <span class="text-slate-500">URL:</span>
                                <span class="font-mono text-[10px] text-slate-600 truncate bg-white border border-slate-200 px-2 py-1 rounded" :title="selectedLog.url">
                                    {{ selectedLog.url || '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- User Agent -->
                    <div v-if="selectedLog.user_agent" class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">User Agent</div>
                        <div class="font-mono text-[11px] text-slate-700 break-all">{{ selectedLog.user_agent }}</div>
                    </div>

                    <!-- Diff View: Old vs New Values -->
                    <div v-if="selectedLog.old_values || selectedLog.new_values" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Attribute Changes (Diff)</h4>
                            <span class="text-[11px] text-slate-400">Before & after states recorded</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Prior / Old Values -->
                            <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-4">
                                <div class="flex items-center justify-between text-rose-800 text-xs font-bold mb-2 pb-1.5 border-b border-rose-200">
                                    <span>🔻 Previous / Old State</span>
                                    <span>Before</span>
                                </div>
                                <pre class="font-mono text-[11px] text-rose-900 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-60">{{ formatJsonDisplay(selectedLog.old_values) }}</pre>
                            </div>

                            <!-- New Values -->
                            <div class="bg-emerald-50/60 border border-emerald-200 rounded-2xl p-4">
                                <div class="flex items-center justify-between text-emerald-800 text-xs font-bold mb-2 pb-1.5 border-b border-emerald-200">
                                    <span>🔺 New / Updated State</span>
                                    <span>After</span>
                                </div>
                                <pre class="font-mono text-[11px] text-emerald-900 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-60">{{ formatJsonDisplay(selectedLog.new_values) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between">
                    <button
                        @click="deleteLog(selectedLog); showDetailModal = false;"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                    >
                        Delete this log
                    </button>
                    <button
                        @click="showDetailModal = false"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer shadow-sm"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- ======================= PURGE LOGS MODAL ======================= -->
        <div
            v-if="showPurgeModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
            @click.self="showPurgeModal = false"
        >
            <div class="bg-white border border-slate-200 rounded-3xl w-full max-w-md p-6 space-y-5 shadow-2xl animate-in fade-in zoom-in-95">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Purge Activity Logs</h3>
                        <p class="text-xs text-slate-500">Clean historical log records to free up database storage.</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <label class="block font-bold text-slate-800">Select Retention Threshold</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 hover:border-slate-300 cursor-pointer transition-colors">
                            <input type="radio" v-model="purgePeriod" value="7_days" class="text-rose-600 focus:ring-rose-500" />
                            <div>
                                <div class="font-bold text-slate-900">Older than 7 days</div>
                                <div class="text-[11px] text-slate-500">Keep logs from the last week only</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 hover:border-slate-300 cursor-pointer transition-colors">
                            <input type="radio" v-model="purgePeriod" value="30_days" class="text-rose-600 focus:ring-rose-500" />
                            <div>
                                <div class="font-bold text-slate-900">Older than 30 days (Recommended)</div>
                                <div class="text-[11px] text-slate-500">Keep recent month activity logs</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 hover:border-slate-300 cursor-pointer transition-colors">
                            <input type="radio" v-model="purgePeriod" value="90_days" class="text-rose-600 focus:ring-rose-500" />
                            <div>
                                <div class="font-bold text-slate-900">Older than 90 days</div>
                                <div class="text-[11px] text-slate-500">Keep 3 months of audit trail</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-2xl bg-rose-50/60 border border-rose-200 hover:border-rose-300 cursor-pointer transition-colors">
                            <input type="radio" v-model="purgePeriod" value="all" class="text-rose-600 focus:ring-rose-500" />
                            <div>
                                <div class="font-bold text-rose-800">Purge ALL Logs (Warning)</div>
                                <div class="text-[11px] text-rose-600">Clear entire activity log database table</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        @click="showPurgeModal = false"
                        class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        @click="executePurge"
                        :disabled="purging"
                        class="px-5 py-2.5 rounded-2xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-500 disabled:opacity-50 transition-colors cursor-pointer shadow-sm flex items-center gap-1.5"
                    >
                        <span v-if="purging">Purging...</span>
                        <span v-else>Confirm & Purge</span>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
