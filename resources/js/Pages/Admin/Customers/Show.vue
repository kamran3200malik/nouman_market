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

const activeTab = ref('bookings'); // 'bookings' | 'reviews' | 'favorites' | 'info'
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
        case 'completed':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/10';
        case 'confirmed':
            return 'bg-blue-50 text-blue-700 border-blue-200 ring-blue-500/10';
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-500/10';
        case 'cancelled':
            return 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-500/10';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${customer.name} - Customer Dossier`" />

        <div class="space-y-6">
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
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-3xl bg-gradient-to-tr from-rose-500 to-pink-600 flex items-center justify-center font-extrabold text-white text-2xl shadow-md shrink-0 ring-4 ring-slate-100">
                            {{ customer.name?.charAt(0)?.toUpperCase() || 'C' }}
                        </div>

                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                                    {{ customer.name }}
                                </h1>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border ring-1 ring-inset"
                                    :class="customer.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/10' : 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-500/10'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="customer.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    {{ customer.is_active ? 'Active Account' : 'Deactivated' }}
                                </span>
                            </div>

                            <p class="text-xs sm:text-sm text-slate-500 flex flex-wrap items-center gap-3">
                                <span>@{{ customer.username || `user${customer.id}` }}</span>
                                <span>•</span>
                                <span>{{ customer.email }}</span>
                                <span>•</span>
                                <span>{{ customer.phone || 'No phone' }}</span>
                            </p>

                            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs text-slate-400">
                                <span>Member since {{ formatDate(customer.created_at) }}</span>
                                <span v-if="customer.city">• {{ customer.city?.name || customer.city }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <button
                            @click="showStatusModal = true"
                            class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all shadow-xs"
                            :class="customer.is_active ? 'bg-orange-50 hover:bg-orange-600 text-orange-700 hover:text-white border border-orange-200' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/20'"
                        >
                            {{ customer.is_active ? 'Deactivate Account' : 'Activate Account' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 Summary KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Spent</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ formatPrice(totalSpent) }}</p>
                    <p class="text-xs text-slate-400 mt-1">Completed bookings volume</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Bookings</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ customer.bookings?.length || 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">All appointments</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Reviews Written</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ customer.reviews?.length || 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Salon feedback ratings</p>
                </div>

                <div class="rounded-3xl bg-white p-5 shadow-sm border border-slate-200/80">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Saved Favorites</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ customer.favorites?.length || 0 }}</p>
                    <p class="text-xs text-slate-400 mt-1">Bookmarked artists</p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-slate-200 flex gap-2 overflow-x-auto pb-px">
                <button
                    v-for="tab in [
                        { key: 'bookings', label: `Appointments (${customer.bookings?.length || 0})` },
                        { key: 'reviews', label: `Reviews (${customer.reviews?.length || 0})` },
                        { key: 'favorites', label: `Saved Salons (${customer.favorites?.length || 0})` },
                    ]"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition-all whitespace-nowrap"
                    :class="[
                        activeTab === tab.key
                            ? 'border-rose-600 text-rose-600'
                            : 'border-transparent text-slate-500 hover:text-slate-900'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB 1: Bookings History -->
            <div v-if="activeTab === 'bookings'" class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Appointment History</h2>

                <div v-if="customer.bookings && customer.bookings.length > 0" class="divide-y divide-slate-100">
                    <div
                        v-for="booking in customer.bookings"
                        :key="booking.id"
                        class="py-4 flex items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm shrink-0">
                                {{ booking.service?.name?.charAt(0) || 'S' }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 truncate">{{ booking.service?.name || 'Custom Service' }}</p>
                                <p class="text-xs text-slate-400 mt-0.5 truncate">
                                    Salon: <strong class="text-slate-600">{{ booking.artist_profile?.business_name || 'Salon Partner' }}</strong> • Date: {{ formatDate(booking.date || booking.booking_date) }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold text-slate-900">{{ formatPrice(booking.total_amount) }}</p>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize mt-1"
                                :class="getStatusBadge(booking.status)"
                            >
                                {{ booking.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center text-xs text-slate-400">
                    No appointments booked by this customer yet.
                </div>
            </div>

            <!-- TAB 2: Reviews Written -->
            <div v-else-if="activeTab === 'reviews'" class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200/80 space-y-4">
                <h2 class="text-base font-bold text-slate-900">Customer Ratings & Reviews</h2>

                <div v-if="customer.reviews && customer.reviews.length > 0" class="divide-y divide-slate-100">
                    <div
                        v-for="review in customer.reviews"
                        :key="review.id"
                        class="py-4 space-y-1.5"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ review.artist_profile?.business_name || 'Salon Partner' }}</p>
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
                <h2 class="text-base font-bold text-slate-900">Saved & Favorite Salons</h2>

                <div v-if="customer.favorites && customer.favorites.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="fav in customer.favorites"
                        :key="fav.id"
                        class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3.5"
                    >
                        <img
                            :src="fav.artist_profile?.profile_image || `https://ui-avatars.com/api/?name=${encodeURIComponent(fav.artist_profile?.business_name || 'Salon')}&background=f43f5e&color=fff`"
                            :alt="fav.artist_profile?.business_name"
                            class="h-12 w-12 rounded-2xl object-cover ring-2 ring-white shadow-xs shrink-0"
                        />
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate">{{ fav.artist_profile?.business_name || 'Salon Partner' }}</p>
                            <p class="text-xs text-slate-400 capitalize truncate">{{ fav.artist_profile?.professional_type || 'Professional' }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center text-xs text-slate-400">
                    No saved salons.
                </div>
            </div>
        </div>

        <!-- Status Modal -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showStatusModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                {{ customer.is_active ? 'Deactivate Customer' : 'Activate Customer' }}
                            </h3>
                            <p class="text-xs text-slate-500">{{ customer.name }} ({{ customer.email }})</p>
                        </div>
                    </div>

                    <p class="text-sm text-slate-600">
                        {{ customer.is_active
                            ? 'Are you sure you want to deactivate this customer account? They will not be able to log in or book appointments.'
                            : 'Are you sure you want to activate this customer account? They will be allowed to log in and book appointments.' }}
                    </p>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            @click="showStatusModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
                            :disabled="actionLoading"
                        >
                            Cancel
                        </button>
                        <button
                            @click="toggleStatus"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-colors"
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
