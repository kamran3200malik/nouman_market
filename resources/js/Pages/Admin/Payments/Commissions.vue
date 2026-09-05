<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    commissions: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    artists: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            commission_artists: 0,
            subscription_artists: 0,
            hybrid_artists: 0,
            active_subscriptions: 0,
            mrr: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const activeSection = ref('artists'); // 'artists' | 'rules'
const artistSearch = ref(props.filters.artist_search || '');
const billingModelFilter = ref(props.filters.billing_model || '');
const typeFilter = ref(props.filters.type || '');
const selectedArtist = ref(null);
const saving = ref(false);

// Edit Form
const billingForm = ref({
    billing_model: 'commission',
    commission_rate: 10.0,
    subscription_plan_name: 'Pro Salon',
    subscription_monthly_fee: 3000,
    subscription_status: 'active',
    subscription_expires_at: '',
    subscription_auto_renew: true,
});

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

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff`);
};

const applyArtistFilters = () => {
    router.get(
        route('admin.payments.commissions'),
        {
            artist_search: artistSearch.value || undefined,
            billing_model: billingModelFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const openBillingModal = (artist) => {
    selectedArtist.value = artist;
    billingForm.value = {
        billing_model: artist.billing_model || 'commission',
        commission_rate: artist.commission_rate !== null ? Number(artist.commission_rate) : 10.0,
        subscription_plan_name: artist.subscription_plan_name || 'Pro Salon Plan',
        subscription_monthly_fee: artist.subscription_monthly_fee !== null ? Number(artist.subscription_monthly_fee) : 3000,
        subscription_status: artist.subscription_status || 'inactive',
        subscription_expires_at: artist.subscription_expires_at ? artist.subscription_expires_at.split('T')[0] : '',
        subscription_auto_renew: artist.subscription_auto_renew !== undefined ? Boolean(artist.subscription_auto_renew) : true,
    };
};

const closeBillingModal = () => {
    selectedArtist.value = null;
};

const saveBillingModel = () => {
    if (!selectedArtist.value) return;

    saving.value = true;
    router.post(route('admin.artists.update-billing-model', selectedArtist.value.id), billingForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false;
            closeBillingModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Salon monetization model updated successfully!',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onError: (err) => {
            saving.value = false;
            Swal.fire({
                icon: 'error',
                title: 'Failed to update model',
                text: Object.values(err)[0] || 'Please check input fields and try again.',
            });
        }
    });
};
</script>

<template>
    <Head title="Platform Monetization & Commission Rules | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- PAGE TITLE & SUB-NAVIGATION -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">
                        Monetization & Commission Rules
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Manage platform monetization options: choose between **Monthly Subscription** or **Percentage Commission** for each user/salon studio.
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
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-rose-50/60 transition-all"
                    >
                        🏦 Salon Payouts
                    </Link>
                    <Link
                        :href="route('admin.payments.commissions')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all bg-glam-600 text-white shadow-xs"
                    >
                        ⚙️ Monetization & Commission
                    </Link>
                </div>
            </div>

            <!-- MONETIZATION KPI SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Commission Salons -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">% Commission Model</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.commission_artists }} Salons</h3>
                        <p class="text-[10px] text-slate-500 font-medium mt-0.5">Pay-per-booking fee</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-glam-700 flex items-center justify-center text-xl shadow-xs">
                        ✂️
                    </div>
                </div>

                <!-- Subscription Salons -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Monthly Subscriptions</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-glam-700 mt-1">{{ stats.subscription_artists }} Salons</h3>
                        <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">✓ {{ stats.active_subscriptions }} Active (0% Commission)</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shadow-xs">
                        💎
                    </div>
                </div>

                <!-- Monthly Recurring Revenue -->
                <div class="p-5 rounded-3xl bg-white border border-emerald-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Subscription MRR</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-emerald-950 mt-1">{{ formatPrice(stats.mrr) }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Monthly Recurring Revenue</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        📈
                    </div>
                </div>

                <!-- Global Take Rate -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Default Global Rate</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">10.0%</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Applied to standard bookings</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                        ⚡
                    </div>
                </div>
            </div>

            <!-- SECTION SWITCHER TABS -->
            <div class="flex items-center gap-2 border-b border-rose-100 pb-3">
                <button
                    type="button"
                    class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer"
                    :class="activeSection === 'artists'
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50/60'"
                    @click="activeSection = 'artists'"
                >
                    <span>👥 Per-Salon Monetization Model</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeSection === 'artists' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                        {{ artists.total || artists.data.length }}
                    </span>
                </button>

                <button
                    type="button"
                    class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer"
                    :class="activeSection === 'rules'
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50/60'"
                    @click="activeSection = 'rules'"
                >
                    <span>📑 Category Overrides & Platform Rules</span>
                </button>
            </div>

            <!-- 1. PER-SALON MONETIZATION TABLE -->
            <div v-if="activeSection === 'artists'" class="space-y-4">
                <!-- Filters -->
                <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[240px] relative">
                        <input
                            v-model="artistSearch"
                            type="text"
                            placeholder="Search salon studio by name, owner, or email..."
                            class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                            @keyup.enter="applyArtistFilters"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            🔍
                        </div>
                    </div>

                    <select
                        v-model="billingModelFilter"
                        class="py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                        @change="applyArtistFilters"
                    >
                        <option value="">All Monetization Models</option>
                        <option value="subscription">💎 Monthly Subscription (0% Commission)</option>
                        <option value="commission">✂️ Percentage Commission</option>
                        <option value="hybrid">⚡ Hybrid Model</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                    <div v-if="artists.data && artists.data.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-rose-50/50 border-b border-rose-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3.5 px-4 sm:px-6">Salon Studio</th>
                                    <th class="py-3.5 px-4">Billing Model</th>
                                    <th class="py-3.5 px-4">Commission Rate</th>
                                    <th class="py-3.5 px-4">Monthly Subscription</th>
                                    <th class="py-3.5 px-4 text-center">Subscription Status</th>
                                    <th class="py-3.5 px-4">Renewal / Expiry</th>
                                    <th class="py-3.5 px-4 sm:px-6 text-right">Configure</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50/80">
                                <tr v-for="artist in artists.data" :key="artist.id" class="hover:bg-rose-50/30 transition-colors">
                                    <!-- Salon Column -->
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img
                                                :src="getAvatar(artist.profile_image, artist.business_name)"
                                                :alt="artist.business_name"
                                                class="w-9 h-9 rounded-full object-cover ring-1 ring-rose-200"
                                            />
                                            <div>
                                                <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ artist.business_name || artist.user?.name }}</p>
                                                <p class="text-[10px] text-slate-400">📍 {{ artist.city?.name || 'Pakistan' }} • {{ artist.user?.email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Billing Model Pill -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <span
                                            v-if="artist.billing_model === 'subscription'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200"
                                        >
                                            💎 Monthly Subscription
                                        </span>
                                        <span
                                            v-else-if="artist.billing_model === 'hybrid'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                                        >
                                            ⚡ Hybrid (Fee + %)
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                                        >
                                            ✂️ % Commission
                                        </span>
                                    </td>

                                    <!-- Commission Rate Column -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div v-if="artist.billing_model === 'subscription' && (artist.subscription_status === 'active' || artist.subscription_status === 'trial')">
                                            <span class="font-bold text-emerald-700 text-xs">0.0% (Subscribed)</span>
                                            <p class="text-[10px] text-slate-400">100% payout to salon</p>
                                        </div>
                                        <div v-else>
                                            <span class="font-serif font-bold text-slate-900 text-sm">
                                                {{ Number(artist.commission_rate !== null ? artist.commission_rate : 10.0).toFixed(1) }}%
                                            </span>
                                            <p class="text-[10px] text-slate-400">Per treatment booking</p>
                                        </div>
                                    </td>

                                    <!-- Subscription Fee -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div v-if="artist.billing_model === 'subscription' || artist.billing_model === 'hybrid'">
                                            <span class="font-serif font-bold text-slate-900 text-xs">
                                                {{ formatPrice(artist.subscription_monthly_fee || 3000) }}/mo
                                            </span>
                                            <p class="text-[10px] text-slate-400 font-medium">{{ artist.subscription_plan_name || 'Pro Plan' }}</p>
                                        </div>
                                        <span v-else class="text-slate-400 italic">None (Free Plan)</span>
                                    </td>

                                    <!-- Subscription Status -->
                                    <td class="py-4 px-4 text-center whitespace-nowrap">
                                        <span
                                            v-if="artist.subscription_status === 'active'"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                        >
                                            ✓ Active
                                        </span>
                                        <span
                                            v-else-if="artist.subscription_status === 'trial'"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200"
                                        >
                                            ★ Free Trial
                                        </span>
                                        <span
                                            v-else-if="artist.subscription_status === 'expired'"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                                        >
                                            ✕ Expired
                                        </span>
                                        <span
                                            v-else
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600"
                                        >
                                            Inactive
                                        </span>
                                    </td>

                                    <!-- Renewal Date -->
                                    <td class="py-4 px-4 whitespace-nowrap text-slate-500">
                                        <span v-if="artist.subscription_expires_at">
                                            {{ formatDate(artist.subscription_expires_at) }}
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>

                                    <!-- Configure Action -->
                                    <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-glam-600 hover:text-white text-slate-700 font-bold text-xs transition cursor-pointer shadow-xs"
                                            @click="openBillingModal(artist)"
                                        >
                                            ⚙️ Change Plan
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12">
                        <AppEmptyState
                            icon="👥"
                            title="No salons found"
                            description="There are currently no salon studios matching your search criteria."
                        />
                    </div>

                    <div v-if="artists.links && artists.links.length > 3" class="p-4 border-t border-rose-100">
                        <AppPagination :links="artists.links" />
                    </div>
                </div>
            </div>

            <!-- 2. CATEGORY & GLOBAL RULES SECTION -->
            <div v-else class="space-y-4">
                <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                    <div v-if="commissions.data && commissions.data.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-rose-50/50 border-b border-rose-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <th class="py-3.5 px-4 sm:px-6">Rule Type</th>
                                    <th class="py-3.5 px-4">Target Scope</th>
                                    <th class="py-3.5 px-4">Default Commission Rate (%)</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50/80">
                                <tr v-for="c in commissions.data" :key="c.id" class="hover:bg-rose-50/30 transition-colors">
                                    <td class="py-4 px-4 sm:px-6 capitalize font-bold text-slate-800">
                                        {{ c.commission_type }}
                                    </td>
                                    <td class="py-4 px-4 text-slate-700">
                                        <span v-if="c.commission_type === 'global'" class="font-bold text-glam-700">Universal Marketplace Default</span>
                                        <span v-else-if="c.category">{{ c.category.name }} Category</span>
                                        <span v-else-if="c.artist_profile">{{ c.artist_profile?.business_name }}</span>
                                        <span v-else class="text-slate-400">All</span>
                                    </td>
                                    <td class="py-4 px-4 font-serif font-bold text-slate-900 text-sm">
                                        {{ Number(c.commission_rate).toFixed(1) }}%
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span
                                            :class="c.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                            class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                                        >
                                            {{ c.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12">
                        <AppEmptyState
                            icon="⚙️"
                            title="Universal 10% Marketplace Rate Active"
                            description="All standard pay-per-booking appointments default to the global 10% commission fee."
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. CONFIGURE SALON BILLING MODEL MODAL -->
        <div
            v-if="selectedArtist"
            class="fixed inset-0 z-50 overflow-y-auto bg-onyx-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeBillingModal"
        >
            <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100 animate-in fade-in zoom-in duration-200">
                <!-- Header -->
                <div class="p-6 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                    <div class="flex items-center gap-3">
                        <img
                            :src="getAvatar(selectedArtist.profile_image, selectedArtist.business_name)"
                            :alt="selectedArtist.business_name"
                            class="w-10 h-10 rounded-full object-cover ring-2 ring-rose-200"
                        />
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-glam-700">Monetization Plan Configuration</span>
                            <h3 class="font-serif text-lg font-bold text-slate-900 mt-0.5">
                                {{ selectedArtist.business_name || selectedArtist.user?.name }}
                            </h3>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-full bg-white text-slate-500 hover:text-slate-900 flex items-center justify-center shadow-xs cursor-pointer"
                        @click="closeBillingModal"
                    >
                        ✕
                    </button>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="saveBillingModel" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Choose Plan Type (3 Cards) -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Select Monetization Model</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Percentage Commission Option -->
                            <div
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                                :class="billingForm.billing_model === 'commission'
                                    ? 'border-rose-500 bg-rose-50/40 shadow-xs'
                                    : 'border-slate-200 hover:border-slate-300 bg-white'"
                                @click="billingForm.billing_model = 'commission'"
                            >
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg">✂️</span>
                                        <input
                                            type="radio"
                                            value="commission"
                                            v-model="billingForm.billing_model"
                                            class="text-rose-600 focus:ring-rose-500"
                                        />
                                    </div>
                                    <h4 class="font-bold text-slate-900 text-sm mt-2">% Commission Model</h4>
                                    <p class="text-xs text-slate-500 mt-1">Pay-per-booking. Deduct percentage fee from each completed appointment.</p>
                                </div>
                            </div>

                            <!-- Monthly Subscription Option -->
                            <div
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                                :class="billingForm.billing_model === 'subscription'
                                    ? 'border-purple-500 bg-purple-50/40 shadow-xs'
                                    : 'border-slate-200 hover:border-slate-300 bg-white'"
                                @click="billingForm.billing_model = 'subscription'; billingForm.commission_rate = 0;"
                            >
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg">💎</span>
                                        <input
                                            type="radio"
                                            value="subscription"
                                            v-model="billingForm.billing_model"
                                            class="text-purple-600 focus:ring-purple-500"
                                        />
                                    </div>
                                    <h4 class="font-bold text-slate-900 text-sm mt-2">Monthly Subscription</h4>
                                    <p class="text-xs text-slate-500 mt-1">Flat monthly fee. Salon pays PKR/mo and keeps 100% of booking earnings (0% Commission).</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields based on Plan -->
                    <!-- 1. Subscription Details -->
                    <div v-if="billingForm.billing_model === 'subscription' || billingForm.billing_model === 'hybrid'" class="p-4.5 rounded-2xl bg-purple-50/50 border border-purple-200 space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-900">Subscription Plan Settings</span>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Monthly Plan Name</label>
                                <input
                                    v-model="billingForm.subscription_plan_name"
                                    type="text"
                                    placeholder="e.g. Pro Salon Plan"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-purple-200 text-xs bg-white text-slate-900 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Monthly Fee (PKR)</label>
                                <input
                                    v-model="billingForm.subscription_monthly_fee"
                                    type="number"
                                    min="0"
                                    step="100"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-purple-200 text-xs bg-white text-slate-900 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Subscription Status</label>
                                <select
                                    v-model="billingForm.subscription_status"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-purple-200 text-xs bg-white text-slate-900 focus:ring-2 focus:ring-purple-500"
                                >
                                    <option value="active">✓ Active Subscription</option>
                                    <option value="trial">★ Free Trial</option>
                                    <option value="expired">✕ Expired</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Expires / Renews On</label>
                                <input
                                    v-model="billingForm.subscription_expires_at"
                                    type="date"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-purple-200 text-xs bg-white text-slate-900 focus:ring-2 focus:ring-purple-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 2. Commission Percentage Setting -->
                    <div class="p-4.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Per-Booking Commission Cut</span>
                            <span v-if="billingForm.billing_model === 'subscription'" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                0% for Subscribed Salons
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex-1 relative">
                                <input
                                    v-model="billingForm.commission_rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.5"
                                    class="w-full p-2.5 pr-8 rounded-xl border border-slate-300 text-xs bg-white text-slate-900 focus:ring-2 focus:ring-glam-500"
                                />
                                <span class="absolute inset-y-0 right-3 flex items-center text-slate-400 text-xs font-bold">%</span>
                            </div>
                            <span class="text-xs text-slate-500">
                                {{ billingForm.billing_model === 'subscription' ? 'Set to 0% to waive booking commission.' : 'Default marketplace rate is 10%.' }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="pt-4 border-t border-rose-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition cursor-pointer"
                            @click="closeBillingModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-glam-600 hover:bg-glam-700 shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <span v-if="saving">Saving...</span>
                            <span v-else>Save Monetization Plan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
