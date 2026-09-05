<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    artist: {
        type: Object,
        required: true
    },
    isFavorite: {
        type: Boolean,
        default: false
    },
    viewMode: {
        type: String,
        default: 'grid' // 'grid' | 'list' | 'compact'
    }
});

const emit = defineEmits(['quick-view', 'toggle-favorite']);

const activeImageIndex = ref(0);
const defaultCover = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80';
const defaultAvatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=160&q=80';

const allImages = computed(() => {
    const list = [];
    if (props.artist.cover_image) {
        list.push(storageUrl(props.artist.cover_image, defaultCover));
    }
    if (props.artist.portfolios && props.artist.portfolios.length > 0) {
        props.artist.portfolios.forEach(p => {
            const path = p.image_path || p.file_path || p.url;
            if (path) {
                list.push(storageUrl(path, defaultCover));
            }
        });
    }
    if (list.length === 0) {
        list.push(defaultCover);
    }
    return list;
});

const nextImage = (e) => {
    e.preventDefault();
    e.stopPropagation();
    activeImageIndex.value = (activeImageIndex.value + 1) % allImages.value.length;
};

const prevImage = (e) => {
    e.preventDefault();
    e.stopPropagation();
    activeImageIndex.value = (activeImageIndex.value - 1 + allImages.value.length) % allImages.value.length;
};

const setImage = (index, e) => {
    e.preventDefault();
    e.stopPropagation();
    activeImageIndex.value = index;
};

const getAvatarImage = (artist) => {
    const img = artist?.user?.avatar_url || artist?.user?.avatar || artist?.profile_image_url || artist?.profile_image;
    if (!img) {
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(getDisplayName(artist))}&background=e11d48&color=fff&bold=true`;
    }
    return storageUrl(img, defaultAvatar);
};

const getCityName = (city) => {
    if (!city) return '';
    if (typeof city === 'object') return city.name || '';
    return String(city);
};

const getAreaName = (area) => {
    if (!area) return '';
    if (typeof area === 'object') return area.name || '';
    return String(area);
};

const getLocationText = (artist) => {
    const cityName = getCityName(artist?.city) || (artist?.user && getCityName(artist.user.city)) || '';
    const areaName = getAreaName(artist?.area) || (artist?.user && getAreaName(artist.user.area)) || '';

    if (cityName && areaName) {
        return `${cityName}, ${areaName}`;
    }
    return cityName || areaName || 'Pakistan';
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

const getStartingPrice = (artist) => {
    return artist?.services_min_price || artist?.starting_price || (artist?.services && artist.services[0]?.price) || 2500;
};

const getRating = (artist) => {
    const r = parseFloat(artist?.rating_avg || artist?.rating);
    return isNaN(r) || r <= 0 ? 5.0 : r;
};

const getReviewsCount = (artist) => {
    return artist?.review_count ?? artist?.reviews_count ?? (Array.isArray(artist?.reviews) ? artist.reviews.length : 0);
};

const getDisplayName = (artist) => {
    return artist?.business_name || artist?.name || (artist?.user && artist.user.name) || 'Beauty Studio';
};

const getProfessionalTypeLabel = (type) => {
    if (!type) return 'Luxury Salon';
    return type.replace(/_/g, ' ');
};

const toggleWishlist = (e) => {
    e.preventDefault();
    e.stopPropagation();
    emit('toggle-favorite', props.artist.id);
};

const openQuickView = (e) => {
    e.preventDefault();
    e.stopPropagation();
    emit('quick-view', props.artist);
};

const profileUrl = computed(() => {
    return route('artists.show', props.artist.slug || props.artist.id);
});

const bookingUrl = computed(() => {
    return route('bookings.create', { artist: props.artist.id });
});
</script>

<template>
    <!-- ============================================== -->
    <!-- 1. GRID VIEW LAYOUT                            -->
    <!-- ============================================== -->
    <div
        v-if="viewMode === 'grid'"
        class="group relative flex flex-col rounded-3xl bg-white border border-rose-100/90 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 overflow-hidden font-sans"
    >
        <!-- MULTI-IMAGE CAROUSEL BANNER -->
        <div class="relative h-60 w-full bg-slate-950 overflow-hidden">
            <Link :href="profileUrl" class="block h-full w-full">
                <img
                    :src="allImages[activeImageIndex]"
                    :alt="getDisplayName(artist)"
                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    loading="lazy"
                    @error="$event.target.src = defaultCover"
                />
            </Link>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-black/20 pointer-events-none"></div>

            <!-- Top Left Status Badges -->
            <div class="absolute top-3.5 left-3.5 flex flex-wrap items-center gap-1.5 z-10">
                <span
                    v-if="artist.is_featured"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md backdrop-blur-md"
                >
                    <span>👑</span> Featured
                </span>
                <span
                    v-if="artist.is_verified"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/95 text-blue-600 shadow-md backdrop-blur-md"
                >
                    <span>✓</span> Verified Pro
                </span>
            </div>

            <!-- Top Right Heart Favorite Button & Quick View -->
            <div class="absolute top-3.5 right-3.5 flex items-center gap-1.5 z-10">
                <button
                    type="button"
                    @click="openQuickView"
                    title="Quick Look"
                    class="h-8 w-8 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center text-xs backdrop-blur-md border border-white/20 transition cursor-pointer shadow-md active:scale-95"
                >
                    <span>👁️</span>
                </button>
                <button
                    type="button"
                    @click="toggleWishlist"
                    title="Save Studio"
                    class="h-8 w-8 rounded-full bg-white/95 hover:bg-white text-rose-600 flex items-center justify-center text-sm backdrop-blur-md border border-white/40 transition hover:scale-110 active:scale-95 cursor-pointer shadow-md"
                >
                    <span :class="isFavorite ? 'text-rose-600 scale-110' : 'text-slate-400'">{{ isFavorite ? '❤️' : '🤍' }}</span>
                </button>
            </div>

            <!-- Carousel Next / Prev Controls (Visible on hover if >1 image) -->
            <div v-if="allImages.length > 1" class="absolute inset-x-2 top-1/2 -translate-y-1/2 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity z-10 pointer-events-none">
                <button
                    type="button"
                    @click="prevImage"
                    class="h-7 w-7 rounded-full bg-black/60 hover:bg-black/90 text-white flex items-center justify-center text-xs pointer-events-auto backdrop-blur-xs transition cursor-pointer"
                >
                    ‹
                </button>
                <button
                    type="button"
                    @click="nextImage"
                    class="h-7 w-7 rounded-full bg-black/60 hover:bg-black/90 text-white flex items-center justify-center text-xs pointer-events-auto backdrop-blur-xs transition cursor-pointer"
                >
                    ›
                </button>
            </div>

            <!-- Image Dots Indicator -->
            <div v-if="allImages.length > 1" class="absolute bottom-11 inset-x-0 flex items-center justify-center gap-1 z-10">
                <button
                    v-for="(_, idx) in allImages"
                    :key="idx"
                    type="button"
                    @click="setImage(idx, $event)"
                    class="h-1.5 rounded-full transition-all"
                    :class="activeImageIndex === idx ? 'w-4 bg-white shadow' : 'w-1.5 bg-white/50'"
                ></button>
            </div>

            <!-- Bottom Inside Banner: Rating & In-Studio/Home Badges -->
            <div class="absolute bottom-3 left-3.5 right-3.5 flex items-center justify-between z-10">
                <div class="flex items-center gap-1.5 bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full border border-white/20 text-white text-xs font-bold shadow-xs">
                    <span class="text-amber-400">★</span>
                    <span>{{ Number(getRating(artist)).toFixed(1) }}</span>
                    <span class="text-[10px] text-slate-300 font-normal">({{ getReviewsCount(artist) }})</span>
                </div>

                <div class="flex items-center gap-1 text-[10px] font-bold text-white">
                    <span v-if="artist.home_service_available || artist.home_service" class="px-2 py-0.5 rounded-full bg-pink-600/80 backdrop-blur-md border border-pink-400/40">
                        🏡 Home Visit
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-md border border-white/20">
                        🏪 Studio
                    </span>
                </div>
            </div>
        </div>

        <!-- CARD BODY -->
        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
            <!-- Studio Identity & Location -->
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="relative shrink-0">
                        <img
                            :src="getAvatarImage(artist)"
                            :alt="getDisplayName(artist)"
                            class="h-12 w-12 rounded-2xl object-cover border-2 border-rose-200 shadow-xs"
                            @error="$event.target.src = defaultAvatar"
                        />
                        <span v-if="artist.is_verified" class="absolute -bottom-1 -right-1 h-4 w-4 bg-blue-600 text-white rounded-full flex items-center justify-center text-[9px] border-2 border-white">✓</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="profileUrl"
                            class="text-base font-bold text-slate-900 hover:text-glam-700 transition truncate block group-hover:text-glam-800"
                        >
                            {{ getDisplayName(artist) }}
                        </Link>
                        <div class="flex items-center gap-2 text-xs text-slate-500 truncate pt-0.5">
                            <span class="truncate flex items-center gap-1">
                                <span class="text-rose-500 text-[11px]">📍</span>
                                {{ getLocationText(artist) }}
                            </span>
                            <span v-if="artist.years_of_experience" class="text-slate-300">&bull;</span>
                            <span v-if="artist.years_of_experience" class="text-slate-600 font-semibold shrink-0">{{ artist.years_of_experience }}y Exp</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed pt-0.5">
                    {{ artist.bio || 'Premium verified beauty salon offering signature makeovers, bridal art, and VIP skincare treatments.' }}
                </p>
            </div>

            <!-- Services Strip with Prices -->
            <div v-if="artist.services && artist.services.length > 0" class="space-y-1">
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-for="service in artist.services.slice(0, 3)"
                        :key="service.id"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-50/70 border border-rose-100 text-[11px] font-semibold text-slate-800"
                    >
                        <span>{{ service.name }}</span>
                        <span class="text-rose-600 font-extrabold">&bull; {{ formatPrice(service.discount_price || service.price) }}</span>
                    </span>
                </div>
            </div>

            <!-- Footer: Starting Price & CTAs -->
            <div class="pt-3.5 border-t border-rose-100/80 flex items-center justify-between gap-2">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Starting From</span>
                    <span class="text-base font-extrabold text-glam-800 tracking-tight">
                        {{ formatPrice(getStartingPrice(artist)) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="profileUrl"
                        class="px-3 py-2 rounded-2xl bg-rose-50 hover:bg-rose-100 text-glam-800 text-xs font-bold border border-rose-200/80 transition"
                    >
                        Details
                    </Link>
                    <Link
                        :href="bookingUrl"
                        class="inline-flex items-center gap-1 px-4 py-2 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white font-bold text-xs shadow-md shadow-pink-950/20 hover:scale-[1.02] active:scale-[0.98] transition"
                    >
                        <span>Book</span>
                        <span>&rarr;</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. WIDE LIST VIEW LAYOUT (DOSSIER ROW)         -->
    <!-- ============================================== -->
    <div
        v-else
        class="group relative flex flex-col md:flex-row rounded-3xl bg-white border border-rose-100/90 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden font-sans"
    >
        <!-- Media Banner Column -->
        <div class="relative w-full md:w-80 h-56 md:h-auto bg-slate-950 shrink-0 overflow-hidden">
            <Link :href="profileUrl" class="block h-full w-full">
                <img
                    :src="allImages[activeImageIndex]"
                    :alt="getDisplayName(artist)"
                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                    loading="lazy"
                    @error="$event.target.src = defaultCover"
                />
            </Link>
            <div class="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-slate-950/80 via-slate-950/20 to-transparent pointer-events-none"></div>

            <!-- Status Badges -->
            <div class="absolute top-3.5 left-3.5 flex flex-wrap gap-1.5 z-10">
                <span v-if="artist.is_featured" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-500 text-white shadow-md">
                    👑 Featured
                </span>
                <span v-if="artist.is_verified" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-white/95 text-blue-600 shadow-md">
                    ✓ Verified
                </span>
            </div>

            <!-- Heart / Favorite Button -->
            <button
                type="button"
                @click="toggleWishlist"
                class="absolute top-3.5 right-3.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 backdrop-blur-md text-white border border-white/20 transition hover:scale-110 active:scale-95 cursor-pointer"
            >
                <span :class="isFavorite ? 'text-rose-500 scale-110' : 'text-white'">♥</span>
            </button>

            <!-- Bottom Tag -->
            <div class="absolute bottom-3 left-3.5 z-10 flex items-center gap-1.5">
                <div class="flex items-center gap-1 bg-black/60 backdrop-blur-md px-2.5 py-1 rounded-full text-white text-xs font-bold border border-white/20">
                    <span class="text-amber-400">★</span>
                    <span>{{ Number(getRating(artist)).toFixed(1) }}</span>
                    <span class="text-[10px] text-slate-300">({{ getReviewsCount(artist) }} reviews)</span>
                </div>
            </div>
        </div>

        <!-- Content Column -->
        <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
            <div class="space-y-2.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <img
                            :src="getAvatarImage(artist)"
                            class="h-12 w-12 rounded-2xl object-cover border-2 border-rose-200 shadow-xs shrink-0"
                            @error="$event.target.src = defaultAvatar"
                        />
                        <div>
                            <Link
                                :href="profileUrl"
                                class="text-lg font-bold text-slate-900 hover:text-glam-700 transition"
                            >
                                {{ getDisplayName(artist) }}
                            </Link>
                            <p class="text-xs text-slate-500 flex items-center gap-1">
                                <span class="text-rose-500">📍</span>
                                <span>{{ getLocationText(artist) }}</span>
                                <span v-if="artist.years_of_experience" class="text-slate-300">&bull;</span>
                                <span v-if="artist.years_of_experience" class="font-medium text-slate-600">{{ artist.years_of_experience }}+ Years Experience</span>
                            </p>
                        </div>
                    </div>

                    <span class="inline-flex self-start sm:self-auto items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 capitalize">
                        {{ getProfessionalTypeLabel(artist.professional_type) }}
                    </span>
                </div>

                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed line-clamp-2">
                    {{ artist.bio || 'Premium salon providing professional makeup, hairstyling, manicure, and aesthetic beauty treatments.' }}
                </p>

                <!-- Services Matrix -->
                <div v-if="artist.services && artist.services.length > 0" class="flex flex-wrap gap-2 pt-1">
                    <span
                        v-for="service in artist.services.slice(0, 4)"
                        :key="service.id"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50/60 border border-rose-100 text-xs font-semibold text-slate-800"
                    >
                        <span>💅 {{ service.name }}</span>
                        <span class="text-rose-600 font-bold">&bull; {{ formatPrice(service.discount_price || service.price) }}</span>
                    </span>
                </div>
            </div>

            <!-- Footer Strip -->
            <div class="pt-4 border-t border-rose-100 flex items-center justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Starting From</span>
                    <span class="text-xl font-extrabold text-glam-800">
                        {{ formatPrice(getStartingPrice(artist)) }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openQuickView"
                        class="px-4 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-100 text-glam-800 text-xs font-bold border border-rose-200 transition cursor-pointer"
                    >
                        Quick Look
                    </button>
                    <Link
                        :href="profileUrl"
                        class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white font-bold text-xs shadow-md shadow-pink-950/20 hover:scale-102 transition"
                    >
                        View Full Menu &rarr;
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
