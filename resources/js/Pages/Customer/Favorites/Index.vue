<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import ArtistCard from '@/Components/ArtistCard.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    favorites: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({ total_saved: 0, cities_count: 0 })
    },
    cities: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({ search: '', city_id: '' })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedCity = ref(props.filters.city_id || '');
const viewMode = ref('grid'); // 'grid' | 'list'

// Quick-View Modal state
const isQuickViewOpen = ref(false);
const quickViewArtist = ref(null);

let searchTimeout = null;
const handleFilterChange = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('customer.favorites.index'), {
            search: searchQuery.value || undefined,
            city_id: selectedCity.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
};

watch(searchQuery, handleFilterChange);
watch(selectedCity, handleFilterChange);

const openQuickView = (artist) => {
    quickViewArtist.value = artist;
    isQuickViewOpen.value = true;
};

const closeQuickView = () => {
    isQuickViewOpen.value = false;
    quickViewArtist.value = null;
};

const removeFavorite = (artistId) => {
    Swal.fire({
        title: 'Remove from Wishlist?',
        text: 'Are you sure you want to remove this salon from your saved favorites?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Keep it',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('customer.favorites.destroy', artistId), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed',
                        text: 'Salon removed from your favorites wishlist.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            });
        }
    });
};

const formatPrice = (price) => {
    const val = parseFloat(price);
    if (isNaN(val) || val <= 0) return 'PKR 2,500';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(val);
};
</script>

<template>
    <Head title="My Saved Salons & Wishlist" />

    <CustomerLayout>
        <div class="space-y-6 sm:space-y-8">
            <!-- 1. LUXURY HEADER BANNER -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#240c1d] via-[#35102a] to-[#1e0817] p-6 sm:p-8 text-white shadow-xl border border-pink-900/50">
                <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-rose-500/20 blur-3xl pointer-events-none"></div>
                <div class="absolute left-1/3 -bottom-16 h-64 w-64 rounded-full bg-pink-400/15 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-pink-500/20 px-3.5 py-1 text-xs font-bold text-pink-300 border border-pink-500/30 backdrop-blur-sm">
                            <span>💖</span>
                            <span>Curated Wishlist & Saved Studios</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-2">
                            <span>My Saved Salons & Wishlist</span>
                            <span class="text-xl">✨</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-pink-200/80 max-w-2xl">
                            Your personalized vanity collection of favorite beauticians, makeover studios, and hair stylists saved for upcoming occasions, weddings, and beauty appointments.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link
                            :href="route('artists.index')"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white text-sm font-bold shadow-lg shadow-pink-950/40 hover:scale-[1.02] transition-all cursor-pointer"
                        >
                            <span>🔍</span>
                            <span>Explore More Salons</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. QUICK STATS BAR -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Saved Studios</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.total_saved || 0 }}</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl border border-rose-100">
                        ❤️
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Cities Covered</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.cities_count || 0 }}</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-2xl border border-purple-100">
                        📍
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Verified Pros</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1">100%</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl border border-emerald-100">
                        🛡️
                    </div>
                </div>
            </div>

            <!-- 3. SEARCH & FILTER STRIP -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white/80 backdrop-blur-md p-4 rounded-3xl border border-pink-100 shadow-xs">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search saved salons or services..."
                            class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-xl border border-pink-200 bg-pink-50/40 focus:bg-white focus:border-glam-500 focus:ring-2 focus:ring-pink-200 transition"
                        />
                        <span class="absolute left-3.5 top-3 text-slate-400 text-xs">🔍</span>
                    </div>

                    <!-- City Filter Dropdown -->
                    <select
                        v-model="selectedCity"
                        class="w-full sm:w-48 py-2.5 px-3 text-xs sm:text-sm rounded-xl border border-pink-200 bg-pink-50/40 focus:bg-white focus:border-glam-500 focus:ring-2 focus:ring-pink-200 transition"
                    >
                        <option value="">All Cities</option>
                        <option v-for="city in cities" :key="city.id" :value="city.id">
                            📍 {{ city.name }}
                        </option>
                    </select>
                </div>

                <!-- View Switcher -->
                <div class="flex items-center gap-2 self-end md:self-auto">
                    <div class="bg-pink-50 p-1 rounded-xl border border-pink-200/80 flex items-center gap-1">
                        <button
                            type="button"
                            @click="viewMode = 'grid'"
                            class="p-2 rounded-lg text-xs font-bold transition cursor-pointer"
                            :class="viewMode === 'grid' ? 'bg-white text-glam-800 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            title="Grid Layout"
                        >
                            ⊞ Grid
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'list'"
                            class="p-2 rounded-lg text-xs font-bold transition cursor-pointer"
                            :class="viewMode === 'list' ? 'bg-white text-glam-800 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            title="List Layout"
                        >
                            ☰ List
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. FAVORITES CARDS GRID / LIST -->
            <div v-if="favorites.data && favorites.data.length > 0">
                <div
                    :class="viewMode === 'grid' 
                        ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7' 
                        : 'space-y-4 sm:space-y-6'"
                >
                    <ArtistCard
                        v-for="fav in favorites.data"
                        :key="fav.id"
                        :artist="fav.artist_profile"
                        :is-favorite="true"
                        :view-mode="viewMode"
                        @toggle-favorite="removeFavorite(fav.id)"
                        @quick-view="openQuickView"
                    />
                </div>

                <!-- Pagination -->
                <div v-if="favorites.links && favorites.links.length > 3" class="pt-8 flex justify-center">
                    <div class="flex items-center gap-1.5 bg-white p-2 rounded-2xl border border-pink-100 shadow-xs">
                        <template v-for="(link, idx) in favorites.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition"
                                :class="link.active 
                                    ? 'bg-gradient-to-r from-glam-600 to-rose-600 text-white shadow-xs' 
                                    : 'text-slate-600 hover:bg-pink-50'"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- 5. EMPTY STATE -->
            <div v-else class="rounded-3xl bg-white border border-pink-100/90 shadow-sm p-12 text-center max-w-2xl mx-auto space-y-5">
                <div class="h-20 w-20 mx-auto rounded-3xl bg-gradient-to-tr from-pink-100 via-rose-100 to-pink-50 border border-pink-200/80 flex items-center justify-center text-3xl shadow-inner">
                    <span>💖</span>
                </div>
                <div class="space-y-1.5">
                    <h3 class="text-lg font-bold text-slate-900">Your Wishlist is Empty</h3>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                        {{ searchQuery || selectedCity 
                            ? 'No saved studios match your filter criteria. Try adjusting your search keyword or city.' 
                            : 'You haven’t saved any beauty salons or makeup artists yet. Tap the heart icon on any salon profile to save it here for fast booking.' }}
                    </p>
                </div>
                <div class="pt-2">
                    <Link
                        :href="route('artists.index')"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white text-xs sm:text-sm font-bold shadow-md shadow-pink-900/20 hover:scale-[1.02] transition cursor-pointer"
                    >
                        <span>✨</span>
                        <span>Browse Verified Beauty Studios</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- 6. QUICK-VIEW MODAL DRAWER                                          -->
        <!-- ==================================================================== -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="isQuickViewOpen && quickViewArtist" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="max-w-2xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl border border-pink-200 flex flex-col max-h-[90vh]">
                        <!-- Drawer Header -->
                        <div class="relative h-44 bg-slate-950 overflow-hidden">
                            <img
                                :src="storageUrl(quickViewArtist.cover_image, 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80')"
                                :alt="quickViewArtist.business_name"
                                class="h-full w-full object-cover opacity-80"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

                            <button
                                type="button"
                                @click="closeQuickView"
                                class="absolute top-3 right-3 h-8 w-8 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center text-sm border border-white/20 transition cursor-pointer"
                            >
                                ✕
                            </button>

                            <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="storageUrl(quickViewArtist.user?.avatar, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150')"
                                        class="h-14 w-14 rounded-2xl object-cover border-2 border-white shadow-md"
                                    />
                                    <div class="text-white">
                                        <h3 class="text-lg font-bold">{{ quickViewArtist.business_name || 'Beauty Salon' }}</h3>
                                        <p class="text-xs text-pink-200">📍 {{ quickViewArtist.city?.name || 'Pakistan' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full text-white text-xs font-bold border border-white/20">
                                    <span class="text-amber-400">★</span>
                                    <span>{{ Number(quickViewArtist.rating_avg || 5.0).toFixed(1) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Drawer Content -->
                        <div class="p-6 overflow-y-auto space-y-5 flex-1">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-pink-800 mb-1">About Salon</h4>
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    {{ quickViewArtist.bio || 'Premium verified beauty salon offering signature makeovers, bridal art, and skincare treatments.' }}
                                </p>
                            </div>

                            <!-- Services Strip -->
                            <div v-if="quickViewArtist.services && quickViewArtist.services.length > 0">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-pink-800 mb-2">Available Services & Rates</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div
                                        v-for="serv in quickViewArtist.services"
                                        :key="serv.id"
                                        class="p-2.5 rounded-xl bg-pink-50/50 border border-pink-100 flex items-center justify-between text-xs"
                                    >
                                        <span class="font-bold text-slate-800">{{ serv.name }}</span>
                                        <span class="font-extrabold text-glam-800">{{ formatPrice(serv.discount_price || serv.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Drawer Footer -->
                        <div class="p-4 border-t border-pink-100 bg-pink-50/30 flex items-center justify-between gap-3">
                            <button
                                type="button"
                                @click="router.post(route('customer.messages.store'), { artist_profile_id: quickViewArtist.id, message: 'Hello! I am inquiring about your salon availability.' })"
                                class="px-4 py-2.5 rounded-xl border border-pink-200 bg-white hover:bg-pink-50 text-glam-800 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>💬</span>
                                <span>Message Salon</span>
                            </button>

                            <Link
                                :href="route('artists.show', quickViewArtist.slug || quickViewArtist.id)"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white text-xs sm:text-sm font-bold shadow-md shadow-pink-950/20 transition hover:scale-102"
                            >
                                <span>View Studio & Book</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </CustomerLayout>
</template>
