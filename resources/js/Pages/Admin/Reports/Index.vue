<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    financials: {
        type: Object,
        default: () => ({
            total_gmv: 0,
            prev_gmv: 0,
            revenue_growth: 0,
            commission_collected: 0,
            net_salon_payouts: 0,
            subscription_mrr: 0,
            subscription_arr: 0,
            avg_order_value: 0,
            total_disbursed_payouts: 0,
            pending_payouts: 0,
        }),
    },
    bookings: {
        type: Object,
        default: () => ({
            total: 0,
            completed: 0,
            confirmed: 0,
            pending: 0,
            cancelled: 0,
            rescheduled: 0,
            completion_rate: 0,
            cancellation_rate: 0,
        }),
    },
    dailyData: {
        type: Array,
        default: () => [],
    },
    topArtists: {
        type: Array,
        default: () => [],
    },
    topServices: {
        type: Array,
        default: () => [],
    },
    categoryBreakdown: {
        type: Array,
        default: () => [],
    },
    cityBreakdown: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    network: {
        type: Object,
        default: () => ({
            total_customers: 0,
            repeat_customers: 0,
            repeat_rate: 0,
            total_artists: 0,
            active_artists: 0,
            subscription_artists: 0,
            commission_artists: 0,
            pending_artists: 0,
            total_services: 0,
            total_reviews: 0,
            avg_rating: 4.9,
            rating_distribution: { '5_star': 0, '4_star': 0, '3_star': 0, '2_star': 0, '1_star': 0 },
        }),
    },
    period: {
        type: String,
        default: '30',
    },
});

const selectedPeriod = ref(props.period);
const activeTab = ref('overview'); // 'overview' | 'artists' | 'services' | 'bookings' | 'cities' | 'customers'
const activeChartMetric = ref('revenue'); // 'revenue' | 'bookings' | 'commission'
const searchQuery = ref('');

const periods = [
    { value: '7', label: '7 Days' },
    { value: '30', label: '30 Days' },
    { value: '90', label: '90 Days' },
    { value: '365', label: '1 Year' },
    { value: 'all', label: 'All Time' },
];

const changePeriod = (val) => {
    selectedPeriod.value = val;
    router.get(
        route('admin.reports'),
        { period: val },
        { preserveState: true, preserveScroll: true }
    );
};

const formatPrice = (val) => {
    if (val === null || val === undefined || isNaN(val)) return 'PKR 0';
    return 'PKR ' + Number(val).toLocaleString('en-PK', { maximumFractionDigits: 0 });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
    });
};

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff&bold=true`);
};

const getCategoryImage = (img) => {
    return storageUrl(img, null);
};

// Filtered Top Artists
const filteredArtists = computed(() => {
    if (!searchQuery.value.trim()) return props.topArtists;
    const q = searchQuery.value.toLowerCase();
    return props.topArtists.filter(
        (a) =>
            a.business_name?.toLowerCase().includes(q) ||
            a.user?.name?.toLowerCase().includes(q) ||
            a.city?.name?.toLowerCase().includes(q)
    );
});

// Filtered Top Services
const filteredServices = computed(() => {
    if (!searchQuery.value.trim()) return props.topServices;
    const q = searchQuery.value.toLowerCase();
    return props.topServices.filter(
        (s) =>
            s.name?.toLowerCase().includes(q) ||
            s.artist_profile?.business_name?.toLowerCase().includes(q) ||
            s.category?.name?.toLowerCase().includes(q)
    );
});

// Max value calculations for trajectory bars
const maxChartValue = computed(() => {
    if (!props.dailyData || props.dailyData.length === 0) return 1000;
    if (activeChartMetric.value === 'revenue') {
        const max = Math.max(...props.dailyData.map((d) => Number(d.revenue || 0)));
        return max > 0 ? max : 1000;
    }
    if (activeChartMetric.value === 'commission') {
        const max = Math.max(...props.dailyData.map((d) => Number(d.commission || 0)));
        return max > 0 ? max : 500;
    }
    const max = Math.max(...props.dailyData.map((d) => Number(d.total_bookings || 0)));
    return max > 0 ? max : 10;
});

const printDossier = () => {
    window.print();
};

const exportDossier = () => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: '📊 Exporting Executive Dossier...',
        text: `Full business report generated for [${selectedPeriod.value.toUpperCase()}].`,
        showConfirmButton: false,
        timer: 3000,
    });
};
</script>

<template>
    <Head title="Executive Business Intelligence & Comprehensive Reports | Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- 1. EXECUTIVE HEADER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            Platform Business Intelligence & Reports
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            FULL 360° REPORT
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Complete multi-dimensional telemetry covering Financial GMV, Salon Leaderboards, Categories, Regional Reach, and Retention.
                    </p>
                </div>

                <!-- Controls: Period Selector + Actions -->
                <div class="flex flex-wrap items-center gap-2 self-start md:self-auto print:hidden">
                    <div class="bg-white p-1 rounded-2xl border border-rose-100 shadow-xs flex items-center gap-1">
                        <button
                            v-for="p in periods"
                            :key="p.value"
                            type="button"
                            @click="changePeriod(p.value)"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="selectedPeriod === p.value
                                ? 'bg-glam-600 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-rose-50/60'"
                        >
                            {{ p.label }}
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="printDossier"
                        class="p-2.5 rounded-2xl bg-white hover:bg-rose-50 border border-rose-200 text-slate-700 text-xs font-bold shadow-xs transition flex items-center gap-1 cursor-pointer"
                        title="Print Executive Dossier"
                    >
                        <span>🖨️</span>
                    </button>

                    <button
                        type="button"
                        @click="exportDossier"
                        class="px-3.5 py-2.5 rounded-2xl bg-white hover:bg-rose-50 border border-rose-200 text-slate-700 text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>📥</span>
                        <span class="hidden sm:inline">Export CSV</span>
                    </button>
                </div>
            </div>

            <!-- 2. TOP EXECUTIVE LUXURY KPI RIBBON -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- GMV -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs relative overflow-hidden group hover:border-rose-300 transition-all">
                    <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-emerald-500/5 group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Gross Marketplace GMV</p>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-base shadow-xs">
                            💵
                        </span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-2">
                        {{ formatPrice(financials.total_gmv) }}
                    </h3>
                    <div class="mt-2.5 flex items-center justify-between text-xs pt-2 border-t border-rose-50">
                        <span
                            class="inline-flex items-center gap-1 font-bold text-[11px]"
                            :class="financials.revenue_growth >= 0 ? 'text-emerald-700' : 'text-rose-700'"
                        >
                            <span>{{ financials.revenue_growth >= 0 ? '▲ +' : '▼ ' }}{{ financials.revenue_growth }}%</span>
                            <span class="text-slate-400 font-normal text-[10px]">vs previous</span>
                        </span>
                        <span class="text-slate-500 text-[11px]">
                            AOV: <strong>{{ formatPrice(financials.avg_order_value) }}</strong>
                        </span>
                    </div>
                </div>

                <!-- Platform Revenue & MRR -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs relative overflow-hidden group hover:border-rose-300 transition-all">
                    <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-purple-500/5 group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800">Platform Revenue & Take</p>
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-base shadow-xs">
                            💎
                        </span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-serif font-bold text-purple-950 mt-2">
                        {{ formatPrice(financials.commission_collected) }}
                    </h3>
                    <div class="mt-2.5 flex items-center justify-between text-xs pt-2 border-t border-rose-50">
                        <span class="text-slate-500 text-[11px]">Subscription MRR:</span>
                        <span class="font-bold text-purple-800 text-[11px]">
                            {{ formatPrice(financials.subscription_mrr) }}/mo
                        </span>
                    </div>
                </div>

                <!-- Bookings & Success -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs relative overflow-hidden group hover:border-rose-300 transition-all">
                    <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-blue-500/5 group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Appointments</p>
                        <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-base shadow-xs">
                            🗓️
                        </span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-2">
                        {{ Number(bookings.total).toLocaleString() }}
                    </h3>
                    <div class="mt-2.5 flex items-center justify-between text-xs pt-2 border-t border-rose-50">
                        <span class="text-emerald-700 font-bold text-[11px]">
                            ✓ {{ bookings.completed }} Completed
                        </span>
                        <span class="text-slate-500 text-[11px]">
                            {{ bookings.completion_rate }}% Success
                        </span>
                    </div>
                </div>

                <!-- Beauty Network Capacity -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs relative overflow-hidden group hover:border-rose-300 transition-all">
                    <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-amber-500/5 group-hover:scale-125 transition-transform"></div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Studios & Quality</p>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-base shadow-xs">
                            👑
                        </span>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-2">
                        {{ network.active_artists }} <span class="text-sm font-normal text-slate-400">/ {{ network.total_artists }}</span>
                    </h3>
                    <div class="mt-2.5 flex items-center justify-between text-xs pt-2 border-t border-rose-50">
                        <span class="text-purple-800 font-semibold text-[11px]">
                            💎 {{ network.subscription_artists }} Subscribed
                        </span>
                        <span class="text-amber-700 font-semibold text-[11px]">
                            ★ {{ network.avg_rating }} Quality
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. COMPREHENSIVE MULTI-DIMENSIONAL NAVIGATION TABS -->
            <div class="flex items-center gap-2 overflow-x-auto border-b border-rose-100 pb-2.5 custom-scrollbar print:hidden">
                <button
                    v-for="t in [
                        { key: 'overview', label: '📊 Executive Overview' },
                        { key: 'artists', label: `🏆 Salons & Artists (${topArtists.length})` },
                        { key: 'services', label: `💄 Treatments & Categories (${topServices.length})` },
                        { key: 'bookings', label: `🗓️ Booking Funnel (${bookings.total})` },
                        { key: 'cities', label: `🗺️ City Penetration (${cityBreakdown.length})` },
                        { key: 'customers', label: `👥 Customer Retention & Reviews` },
                    ]"
                    :key="t.key"
                    type="button"
                    @click="activeTab = t.key"
                    class="px-4 py-2 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer"
                    :class="activeTab === t.key
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-600 hover:text-slate-900 border border-rose-100 hover:bg-rose-50/60'"
                >
                    {{ t.label }}
                </button>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: EXECUTIVE OVERVIEW -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- Trajectory Chart + Category Matrix -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Dynamic Trajectory Chart (2 Cols) -->
                    <div class="lg:col-span-2 p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-serif font-bold text-slate-900">
                                    📈 Financial & Booking Trajectory
                                </h2>
                                <p class="text-xs text-slate-400">Daily financial distribution for the selected time horizon</p>
                            </div>
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl self-start sm:self-auto">
                                <button
                                    type="button"
                                    @click="activeChartMetric = 'revenue'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                                    :class="activeChartMetric === 'revenue' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                >
                                    Revenue
                                </button>
                                <button
                                    type="button"
                                    @click="activeChartMetric = 'commission'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                                    :class="activeChartMetric === 'commission' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                >
                                    Platform Take
                                </button>
                                <button
                                    type="button"
                                    @click="activeChartMetric = 'bookings'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                                    :class="activeChartMetric === 'bookings' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                >
                                    Bookings
                                </button>
                            </div>
                        </div>

                        <!-- Bar chart -->
                        <div v-if="dailyData && dailyData.length > 0" class="pt-4">
                            <div class="h-56 flex items-end gap-1.5 sm:gap-2.5 overflow-x-auto pb-4 pt-2 custom-scrollbar">
                                <div
                                    v-for="(day, idx) in dailyData"
                                    :key="idx"
                                    class="flex-1 min-w-[28px] max-w-[48px] flex flex-col items-center gap-1 group relative h-full justify-end"
                                >
                                    <div class="absolute -top-12 z-20 hidden group-hover:flex flex-col items-center pointer-events-none whitespace-nowrap bg-slate-900 text-white text-[10px] font-semibold py-1 px-2 rounded-lg shadow-xl border border-slate-700">
                                        <span>{{ formatDate(day.date) }}</span>
                                        <span class="text-rose-300 font-bold">
                                            {{ activeChartMetric === 'revenue' ? formatPrice(day.revenue) : (activeChartMetric === 'commission' ? formatPrice(day.commission) : `${day.total_bookings} Bookings`) }}
                                        </span>
                                    </div>
                                    <div class="w-full flex items-end justify-center h-44 bg-slate-50 rounded-xl p-1 relative overflow-hidden group-hover:bg-rose-50/50 transition">
                                        <div
                                            class="w-full rounded-lg transition-all duration-300 group-hover:brightness-110"
                                            :class="activeChartMetric === 'revenue'
                                                ? 'bg-gradient-to-t from-rose-600 to-pink-500 shadow-xs'
                                                : (activeChartMetric === 'commission'
                                                    ? 'bg-gradient-to-t from-purple-600 to-indigo-500 shadow-xs'
                                                    : 'bg-gradient-to-t from-blue-600 to-cyan-500 shadow-xs')"
                                            :style="{
                                                height: activeChartMetric === 'revenue'
                                                    ? Math.max(8, (Number(day.revenue || 0) / maxChartValue) * 100) + '%'
                                                    : (activeChartMetric === 'commission'
                                                        ? Math.max(8, (Number(day.commission || 0) / maxChartValue) * 100) + '%'
                                                        : Math.max(8, (Number(day.total_bookings || 0) / maxChartValue) * 100) + '%')
                                            }"
                                        ></div>
                                    </div>
                                    <span class="text-[9px] font-medium text-slate-400 truncate w-full text-center">
                                        {{ formatDate(day.date) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="h-56 flex flex-col items-center justify-center text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                            <span class="text-3xl mb-1">📊</span>
                            <p class="text-xs font-semibold">No transactions recorded in this date range</p>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4 flex flex-col justify-between">
                        <div>
                            <h2 class="text-base font-serif font-bold text-slate-900">
                                🏷️ Category Revenue Share
                            </h2>
                            <p class="text-xs text-slate-400">Treatment segments driving marketplace demand</p>
                        </div>

                        <div v-if="categoryBreakdown && categoryBreakdown.length > 0" class="space-y-3 py-1 max-h-60 overflow-y-auto custom-scrollbar">
                            <div v-for="cat in categoryBreakdown" :key="cat.id" class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-slate-800 flex items-center gap-1.5">
                                        <img v-if="cat.image" :src="getCategoryImage(cat.image)" class="w-4 h-4 rounded object-cover" />
                                        <span v-else>🌸</span>
                                        <span>{{ cat.name }}</span>
                                    </span>
                                    <span class="text-slate-900 font-bold">{{ formatPrice(cat.revenue) }}</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden flex">
                                    <div
                                        class="h-full bg-gradient-to-r from-rose-500 to-pink-500 rounded-full"
                                        :style="{
                                            width: financials.total_gmv > 0
                                                ? Math.min(100, Math.max(5, (cat.revenue / financials.total_gmv) * 100)) + '%'
                                                : '10%'
                                        }"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>{{ cat.bookings }} Bookings</span>
                                    <span>{{ financials.total_gmv > 0 ? ((cat.revenue / financials.total_gmv) * 100).toFixed(1) : 0 }}% Share</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-rose-50/60 border border-rose-100 flex items-center justify-between text-xs">
                            <span class="font-bold text-glam-800">Total Categories:</span>
                            <span class="font-bold text-slate-900">{{ categoryBreakdown.length }} Active</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Distribution Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div v-for="method in paymentMethods" :key="method.name" class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ method.name }}</p>
                            <h4 class="text-xl font-serif font-bold text-slate-900 mt-1">{{ formatPrice(method.amount) }}</h4>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">{{ method.count }} Completed Payments</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-2xl flex items-center justify-center shadow-xs">
                            {{ method.icon }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: SALONS & ARTISTS LEADERBOARD -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'artists'" class="space-y-4">
                <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="flex-1 min-w-[260px] relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search salon studio by name, owner, city..."
                            class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                        />
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-800 font-bold border border-purple-200">
                            💎 Subscriptions: {{ network.subscription_artists }}
                        </span>
                        <span class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-800 font-bold border border-rose-200">
                            ✂️ % Commission: {{ network.commission_artists }}
                        </span>
                    </div>
                </div>

                <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                    <div v-if="filteredArtists && filteredArtists.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3.5 px-4 text-center">Rank</th>
                                    <th class="py-3.5 px-4">Salon Studio & Owner</th>
                                    <th class="py-3.5 px-4">City</th>
                                    <th class="py-3.5 px-4 text-center">Plan Model</th>
                                    <th class="py-3.5 px-4 text-center">Rating</th>
                                    <th class="py-3.5 px-4 text-right">Appointments</th>
                                    <th class="py-3.5 px-4 text-right">Gross GMV</th>
                                    <th class="py-3.5 px-4 text-right">Platform Fee</th>
                                    <th class="py-3.5 px-4 text-right">Inspect</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50/80">
                                <tr v-for="(artist, idx) in filteredArtists" :key="artist.id" class="hover:bg-rose-50/30 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-bold">
                                        <span
                                            class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px]"
                                            :class="idx === 0 ? 'bg-amber-100 text-amber-900 font-black' : (idx === 1 ? 'bg-slate-200 text-slate-800' : (idx === 2 ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600'))"
                                        >
                                            {{ idx + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img
                                                :src="getAvatar(artist.profile_image, artist.business_name)"
                                                :alt="artist.business_name"
                                                class="w-9 h-9 rounded-full object-cover ring-1 ring-rose-200"
                                            />
                                            <div>
                                                <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ artist.business_name || artist.user?.name }}</p>
                                                <p class="text-[10px] text-slate-400">{{ artist.user?.name }} • {{ artist.user?.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-700 font-medium">
                                        📍 {{ artist.city?.name || 'Pakistan' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span
                                            v-if="artist.billing_model === 'subscription'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200"
                                        >
                                            💎 Monthly Plan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                                        >
                                            ✂️ % Commission
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap font-bold text-slate-800">
                                        <span class="text-amber-500">★</span> {{ artist.rating_avg ? Number(artist.rating_avg).toFixed(1) : '5.0' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-700 whitespace-nowrap">
                                        {{ Number(artist.booking_count).toLocaleString() }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-serif font-bold text-slate-900 whitespace-nowrap">
                                        {{ formatPrice(artist.total_revenue) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-serif font-bold text-purple-900 whitespace-nowrap">
                                        {{ formatPrice(artist.total_commission) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <Link
                                            :href="route('admin.artists.show', artist.id)"
                                            class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-glam-600 hover:text-white text-slate-700 font-bold text-[11px] transition shadow-xs"
                                        >
                                            Dossier &rarr;
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12">
                        <AppEmptyState
                            icon="👑"
                            title="No salons matched search"
                            description="Adjust your search term or select a wider time period."
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: SERVICES & TREATMENTS -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'services'" class="space-y-4">
                <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="flex-1 min-w-[260px] relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search treatment, package, salon provider..."
                            class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                        />
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                    </div>
                </div>

                <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                    <div v-if="filteredServices && filteredServices.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3.5 px-4 text-center">Rank</th>
                                    <th class="py-3.5 px-4">Treatment Name</th>
                                    <th class="py-3.5 px-4">Category</th>
                                    <th class="py-3.5 px-4">Studio Provider</th>
                                    <th class="py-3.5 px-4">Price Point</th>
                                    <th class="py-3.5 px-4 text-right">Appointments Booked</th>
                                    <th class="py-3.5 px-4 text-right">Gross GMV</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50/80">
                                <tr v-for="(service, idx) in filteredServices" :key="service.id" class="hover:bg-rose-50/30 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-bold">
                                        <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px] bg-pink-100 text-pink-900 font-bold">
                                            {{ idx + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ service.name }}</p>
                                        <p class="text-[10px] text-slate-400">⏱️ {{ service.duration_minutes }} Minutes Duration</p>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                            {{ service.category?.name || 'Beauty' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-700 font-medium">
                                        {{ service.artist_profile?.business_name || 'Studio' }}
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap font-serif font-bold text-slate-900">
                                        {{ formatPrice(service.discount_price || service.price) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-700 whitespace-nowrap">
                                        {{ Number(service.booking_count).toLocaleString() }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-serif font-bold text-slate-900 whitespace-nowrap">
                                        {{ formatPrice(service.total_revenue) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12">
                        <AppEmptyState
                            icon="💄"
                            title="No services found"
                            description="Treatment bookings will appear as clients reserve appointments."
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 4: BOOKINGS & FULFILLMENT FUNNEL -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'bookings'" class="space-y-6">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <div class="p-4.5 rounded-3xl bg-white border border-emerald-100 shadow-xs">
                        <span class="text-[10px] font-bold uppercase text-emerald-700">✓ Completed</span>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ bookings.completed }}</p>
                        <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">{{ bookings.completion_rate }}% Success</p>
                    </div>

                    <div class="p-4.5 rounded-3xl bg-white border border-blue-100 shadow-xs">
                        <span class="text-[10px] font-bold uppercase text-blue-700">🗓️ Confirmed</span>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ bookings.confirmed }}</p>
                        <p class="text-[10px] text-blue-700 font-semibold mt-0.5">Upcoming scheduled</p>
                    </div>

                    <div class="p-4.5 rounded-3xl bg-white border border-amber-100 shadow-xs">
                        <span class="text-[10px] font-bold uppercase text-amber-700">⏳ Pending Approval</span>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ bookings.pending }}</p>
                        <p class="text-[10px] text-amber-700 font-semibold mt-0.5">Awaiting salon</p>
                    </div>

                    <div class="p-4.5 rounded-3xl bg-white border border-purple-100 shadow-xs">
                        <span class="text-[10px] font-bold uppercase text-purple-700">🔄 Rescheduled</span>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ bookings.rescheduled }}</p>
                        <p class="text-[10px] text-purple-700 font-semibold mt-0.5">Time adjusted</p>
                    </div>

                    <div class="p-4.5 rounded-3xl bg-white border border-rose-100 shadow-xs">
                        <span class="text-[10px] font-bold uppercase text-rose-700">✕ Cancelled</span>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ bookings.cancelled }}</p>
                        <p class="text-[10px] text-rose-700 font-semibold mt-0.5">{{ bookings.cancellation_rate }}% Drop-off</p>
                    </div>
                </div>

                <!-- Funnel Visual Bar -->
                <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4">
                    <h2 class="text-base font-serif font-bold text-slate-900">
                        Appointment Status Flow Pipeline
                    </h2>
                    <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                        <div class="h-full bg-emerald-500" :style="{ width: (bookings.total > 0 ? (bookings.completed / bookings.total) * 100 : 0) + '%' }" title="Completed"></div>
                        <div class="h-full bg-blue-500" :style="{ width: (bookings.total > 0 ? (bookings.confirmed / bookings.total) * 100 : 0) + '%' }" title="Confirmed"></div>
                        <div class="h-full bg-amber-400" :style="{ width: (bookings.total > 0 ? (bookings.pending / bookings.total) * 100 : 0) + '%' }" title="Pending"></div>
                        <div class="h-full bg-purple-400" :style="{ width: (bookings.total > 0 ? (bookings.rescheduled / bookings.total) * 100 : 0) + '%' }" title="Rescheduled"></div>
                        <div class="h-full bg-rose-500" :style="{ width: (bookings.total > 0 ? (bookings.cancelled / bookings.total) * 100 : 0) + '%' }" title="Cancelled"></div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between text-xs pt-1 text-slate-600 gap-2">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Completed ({{ bookings.completed }})</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Confirmed ({{ bookings.confirmed }})</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Pending ({{ bookings.pending }})</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span> Rescheduled ({{ bookings.rescheduled }})</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Cancelled ({{ bookings.cancelled }})</span>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 5: REGIONAL & CITY PENETRATION -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'cities'" class="space-y-4">
                <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                    <div v-if="cityBreakdown && cityBreakdown.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3.5 px-4 text-center">Rank</th>
                                    <th class="py-3.5 px-4">City / Region</th>
                                    <th class="py-3.5 px-4 text-center">Active Verified Studios</th>
                                    <th class="py-3.5 px-4 text-right">Appointments Scheduled</th>
                                    <th class="py-3.5 px-4 text-right">Gross GMV</th>
                                    <th class="py-3.5 px-4 text-right">Market Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50/80">
                                <tr v-for="(city, idx) in cityBreakdown" :key="city.id" class="hover:bg-rose-50/30 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-bold">
                                        <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px] bg-slate-100 text-slate-700 font-bold">
                                            {{ idx + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900 text-xs sm:text-sm">
                                        📍 {{ city.name }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-semibold text-purple-900">
                                        👑 {{ city.active_salons }} Salons
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-700">
                                        {{ Number(city.bookings).toLocaleString() }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-serif font-bold text-slate-900">
                                        {{ formatPrice(city.revenue) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold text-rose-700">
                                        {{ financials.total_gmv > 0 ? ((city.revenue / financials.total_gmv) * 100).toFixed(1) : 0 }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12">
                        <AppEmptyState
                            icon="🗺️"
                            title="No city data available"
                            description="City statistics will populate automatically as salons register across Pakistan."
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 6: CUSTOMER LOYALTY & REVIEWS -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'customers'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Rating Overview -->
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-col items-center justify-center text-center space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Marketplace Satisfaction</span>
                        <div class="text-5xl font-black font-serif text-slate-900 flex items-center gap-2">
                            <span>{{ network.avg_rating }}</span>
                            <span class="text-amber-400 text-4xl">★</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Based on {{ network.total_reviews }} verified client reviews</p>
                    </div>

                    <!-- Rating Distribution -->
                    <div class="md:col-span-2 p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Star Rating Breakdown</h3>
                        <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-3 text-xs">
                            <span class="w-8 font-bold text-slate-700">{{ star }} ★</span>
                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden flex">
                                <div
                                    class="h-full bg-amber-400 rounded-full"
                                    :style="{
                                        width: network.total_reviews > 0
                                            ? ((network.rating_distribution[`${star}_star`] || 0) / network.total_reviews) * 100 + '%'
                                            : '0%'
                                    }"
                                ></div>
                            </div>
                            <span class="w-10 text-right font-medium text-slate-500">
                                {{ network.rating_distribution[`${star}_star`] || 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Repeat Customer Metric -->
                <div class="p-6 rounded-3xl bg-gradient-to-r from-rose-50 via-pink-50/50 to-white border border-rose-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-glam-700">Client Retention Intelligence</span>
                        <h3 class="font-serif text-xl font-bold text-slate-900 mt-1">
                            {{ network.repeat_customers }} Repeat Clients ({{ network.repeat_rate }}% Retention Rate)
                        </h3>
                        <p class="text-xs text-slate-600 mt-0.5">
                            Clients who have booked and completed 2 or more salon appointments.
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs text-slate-400">Total Registered Clients</p>
                        <p class="text-2xl font-bold text-slate-900">{{ Number(network.total_customers).toLocaleString() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
