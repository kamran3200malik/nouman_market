<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    service: {
        type: Object,
        required: true
    },
    selectable: {
        type: Boolean,
        default: false
    },
    selected: {
        type: Boolean,
        default: false
    },
    viewMode: {
        type: String,
        default: 'grid' // 'grid' | 'list'
    }
});

const emit = defineEmits(['select', 'quick-view']);

const artist = computed(() => props.service.artist_profile || props.service.artistProfile);

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(price || 0);
};

const hasDiscount = computed(() => {
    return props.service.discount_price && Number(props.service.discount_price) < Number(props.service.price);
});

const discountPercentage = computed(() => {
    if (!hasDiscount.value) return 0;
    return Math.round((1 - props.service.discount_price / props.service.price) * 100);
});

const duration = computed(() => {
    return props.service.duration_minutes || props.service.duration || 45;
});

const isHomeAvailable = computed(() => {
    return props.service.home_service_available || props.service.home_service;
});

const isSalonAvailable = computed(() => {
    return props.service.salon_service_available !== undefined
        ? props.service.salon_service_available
        : (props.service.salon_service !== undefined ? props.service.salon_service : true);
});

const bookingUrl = computed(() => {
    const artistId = props.service.artist_profile_id || artist.value?.id;
    if (artistId) {
        return route('bookings.create', { service: props.service.id, artist: artistId });
    }
    return route('bookings.create', { service: props.service.id });
});

const artistProfileUrl = computed(() => {
    if (artist.value?.slug) {
        return route('artists.show', artist.value.slug);
    }
    return '#';
});

const getArtistAvatar = (art) => {
    if (!art) return null;
    const img = art.profile_image_url || art.profile_image || art.user?.avatar_url || art.user?.avatar;
    return storageUrl(img);
};
</script>

<template>
    <!-- LIST VIEW CARD -->
    <div
        v-if="viewMode === 'list'"
        :class="[
            'group relative flex flex-col md:flex-row items-stretch overflow-hidden rounded-3xl border transition-all duration-300',
            selectable ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-xl' : 'hover:shadow-2xl hover:-translate-y-1',
            selected
                ? 'ring-2 ring-glam-600 bg-glam-50/50 border-glam-300'
                : 'border-rose-100/80 bg-white/90 backdrop-blur-md shadow-sm'
        ]"
        @click="selectable ? emit('select', service) : null"
    >
        <!-- Media thumbnail -->
        <div class="relative md:w-72 shrink-0 aspect-[16/10] md:aspect-auto overflow-hidden bg-gradient-to-br from-rose-50 to-pink-100/60">
            <img
                v-if="service.image"
                :src="storageUrl(service.image)"
                :alt="service.name"
                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="h-full w-full flex flex-col items-center justify-center p-6 text-rose-300/80">
                <span class="text-4xl mb-2">✨</span>
                <span class="text-xs font-medium text-slate-400">Signature Treatment</span>
            </div>

            <div class="absolute inset-0 bg-gradient-to-t from-onyx-950/70 via-transparent to-transparent md:hidden"></div>

            <!-- Discount Pill -->
            <div v-if="hasDiscount" class="absolute top-3 left-3 z-10">
                <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-glam-600 to-pink-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-md">
                    <span>🔥</span> {{ discountPercentage }}% OFF
                </span>
            </div>

            <!-- Service Mode Badges -->
            <div class="absolute bottom-3 left-3 flex flex-wrap gap-1.5 z-10">
                <span
                    v-if="isHomeAvailable"
                    class="inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-semibold text-slate-800 backdrop-blur-md shadow-xs"
                >
                    🏡 Home Visit
                </span>
                <span
                    v-if="isSalonAvailable"
                    class="inline-flex items-center gap-1 rounded-full bg-champagne-500/95 px-2.5 py-0.5 text-[10px] font-semibold text-white backdrop-blur-md shadow-xs"
                >
                    🏛️ In Salon
                </span>
            </div>
        </div>

        <!-- Content Area -->
        <div class="flex flex-1 flex-col justify-between p-5 md:p-6">
            <div>
                <!-- Top Row: Artist/Studio info & Category -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                    <!-- Artist Studio Attribution -->
                    <div v-if="artist" class="flex items-center gap-2">
                        <Link
                            :href="artistProfileUrl"
                            class="flex items-center gap-2 group/artist text-slate-700 hover:text-glam-700 transition-colors"
                            @click.stop
                        >
                            <img
                                v-if="getArtistAvatar(artist)"
                                :src="getArtistAvatar(artist)"
                                :alt="artist.business_name || artist.user?.name"
                                class="w-6 h-6 rounded-full object-cover ring-1 ring-rose-200"
                            />
                            <div v-else class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[10px] font-bold">
                                {{ (artist.business_name || artist.user?.name || 'S').charAt(0) }}
                            </div>
                            <span class="text-xs font-semibold text-slate-800 group-hover/artist:text-glam-700 truncate max-w-[150px]">
                                {{ artist.business_name || artist.user?.name }}
                            </span>
                            <span v-if="artist.is_verified" class="text-emerald-600 text-xs font-bold" title="Verified Studio">✓</span>
                        </Link>

                        <span v-if="artist.city?.name || artist.city" class="text-slate-300 text-xs">•</span>
                        <span v-if="artist.city?.name || artist.city" class="text-xs text-slate-500 flex items-center gap-0.5">
                            📍 {{ artist.city?.name || artist.city }}
                        </span>
                    </div>

                    <!-- Category Badge -->
                    <span
                        v-if="service.category"
                        class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-medium text-rose-700 border border-rose-100"
                    >
                        {{ service.category.name }}
                    </span>
                </div>

                <!-- Service Title & Description -->
                <h3 class="font-serif text-lg md:text-xl font-bold text-slate-900 group-hover:text-glam-700 transition-colors">
                    {{ service.name }}
                </h3>
                <p class="mt-1.5 line-clamp-2 text-xs md:text-sm leading-relaxed text-slate-500">
                    {{ service.description || 'Professional luxury treatment crafted by verified beauty specialists.' }}
                </p>

                <!-- Duration & Specs -->
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100/80 px-2.5 py-1 font-medium text-slate-700">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ duration }} mins
                    </span>

                    <span v-if="service.booking_required" class="text-xs text-slate-400">
                        ⚡ Instant Confirmation
                    </span>

                    <span v-if="service.advance_payment_required" class="text-xs text-amber-700 font-medium bg-amber-50 px-2 py-0.5 rounded-md border border-amber-100">
                        Deposit: {{ formatPrice(service.advance_payment_amount) }}
                    </span>
                </div>
            </div>

            <!-- Bottom Price & Actions Bar -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-rose-100/70 pt-4">
                <div class="flex items-baseline gap-2">
                    <span class="text-xl md:text-2xl font-serif font-extrabold text-glam-700">
                        {{ formatPrice(hasDiscount ? service.discount_price : service.price) }}
                    </span>
                    <span v-if="hasDiscount" class="text-xs md:text-sm text-slate-400 line-through">
                        {{ formatPrice(service.price) }}
                    </span>
                </div>

                <!-- Action CTAs -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors"
                        @click.stop="emit('quick-view', service)"
                    >
                        Quick View
                    </button>

                    <template v-if="!selectable">
                        <Link
                            :href="bookingUrl"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-glam-600 to-pink-600 hover:from-glam-700 hover:to-pink-700 shadow-md shadow-glam-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                            @click.stop
                        >
                            <span>Book Now</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </template>
                    <template v-else>
                        <span class="text-xs font-semibold text-glam-700">
                            {{ selected ? '✓ Selected' : '+ Select' }}
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- GRID VIEW CARD -->
    <div
        v-else
        :class="[
            'market-card group relative flex flex-col justify-between w-full overflow-hidden rounded-3xl border text-left transition-all duration-300',
            selectable ? 'cursor-pointer hover:-translate-y-1 hover:shadow-xl' : 'hover:-translate-y-1.5 hover:shadow-2xl',
            selected
                ? 'ring-2 ring-glam-600 bg-glam-50/50 border-glam-300'
                : 'border-rose-100/80 bg-white/90 backdrop-blur-md shadow-sm'
        ]"
        @click="selectable ? emit('select', service) : null"
    >
        <!-- Top Media Section -->
        <div class="relative aspect-[16/10] w-full overflow-hidden bg-gradient-to-br from-rose-50 to-pink-100/60">
            <img
                v-if="service.image"
                :src="storageUrl(service.image)"
                :alt="service.name"
                class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-108"
                loading="lazy"
            />
            <div v-else class="h-full w-full flex flex-col items-center justify-center p-6 text-rose-300/80">
                <span class="text-4xl mb-1">✨</span>
                <span class="text-xs font-medium text-slate-400">Signature Treatment</span>
            </div>

            <!-- Soft gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-onyx-950/75 via-onyx-950/20 to-transparent"></div>

            <!-- Top Floating Badges -->
            <div class="absolute top-3 inset-x-3 flex items-start justify-between gap-2 z-10">
                <!-- Category Pill -->
                <span
                    v-if="service.category"
                    class="inline-flex items-center rounded-full bg-white/90 px-2.5 py-0.5 text-[11px] font-semibold text-slate-800 backdrop-blur-md shadow-sm"
                >
                    {{ service.category.name }}
                </span>
                <span v-else></span>

                <!-- Discount Badge -->
                <div v-if="hasDiscount">
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-gradient-to-r from-glam-600 to-pink-600 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-md">
                        🔥 {{ discountPercentage }}% OFF
                    </span>
                </div>
            </div>

            <!-- Bottom Floating Service Types & Duration -->
            <div class="absolute bottom-3 inset-x-3 flex items-center justify-between gap-2 z-10">
                <div class="flex flex-wrap gap-1">
                    <span
                        v-if="isHomeAvailable"
                        class="inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-medium text-slate-800 backdrop-blur-sm shadow-xs"
                    >
                        🏡 Home
                    </span>
                    <span
                        v-if="isSalonAvailable"
                        class="inline-flex items-center gap-1 rounded-full bg-champagne-500/90 px-2 py-0.5 text-[10px] font-medium text-white backdrop-blur-sm shadow-xs"
                    >
                        🏛️ Salon
                    </span>
                </div>

                <span class="inline-flex items-center gap-1 rounded-full bg-onyx-900/80 px-2 py-0.5 text-[10px] font-medium text-rose-100 backdrop-blur-sm">
                    ⏱️ {{ duration }}m
                </span>
            </div>
        </div>

        <!-- Body Details -->
        <div class="flex flex-1 flex-col justify-between p-5">
            <div class="space-y-2.5">
                <!-- Artist Attribution Row -->
                <div v-if="artist" class="flex items-center justify-between gap-2 text-xs">
                    <Link
                        :href="artistProfileUrl"
                        class="flex items-center gap-1.5 group/artist text-slate-600 hover:text-glam-700 transition-colors truncate"
                        @click.stop
                    >
                        <img
                            v-if="getArtistAvatar(artist)"
                            :src="getArtistAvatar(artist)"
                            :alt="artist.business_name || artist.user?.name"
                            class="w-5 h-5 rounded-full object-cover ring-1 ring-rose-200"
                        />
                        <div v-else class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[9px] font-bold">
                            {{ (artist.business_name || artist.user?.name || 'S').charAt(0) }}
                        </div>
                        <span class="font-medium text-slate-700 group-hover/artist:text-glam-700 truncate max-w-[130px]">
                            {{ artist.business_name || artist.user?.name }}
                        </span>
                        <span v-if="artist.is_verified" class="text-emerald-600 text-xs font-bold" title="Verified Studio">✓</span>
                    </Link>

                    <span v-if="artist.city?.name || artist.city" class="text-[11px] text-slate-400 truncate max-w-[80px]">
                        📍 {{ artist.city?.name || artist.city }}
                    </span>
                </div>

                <!-- Service Name & Description -->
                <div>
                    <h4 class="font-serif text-base font-bold text-slate-900 transition-colors group-hover:text-glam-700 line-clamp-1">
                        {{ service.name }}
                    </h4>
                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">
                        {{ service.description || 'Premium beauty service curated by top verified salon artists.' }}
                    </p>
                </div>
            </div>

            <!-- Price & Action Section -->
            <div class="mt-4 pt-3.5 border-t border-rose-100/70">
                <div class="flex items-end justify-between gap-2 mb-3">
                    <div>
                        <span class="block text-[10px] uppercase font-semibold tracking-wider text-slate-400">Treatment Rate</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="font-serif text-lg font-extrabold text-glam-700">
                                {{ formatPrice(hasDiscount ? service.discount_price : service.price) }}
                            </span>
                            <span v-if="hasDiscount" class="text-xs text-slate-400 line-through">
                                {{ formatPrice(service.price) }}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="text-xs font-semibold text-slate-600 hover:text-glam-700 underline underline-offset-2 transition-colors"
                        @click.stop="emit('quick-view', service)"
                    >
                        Details
                    </button>
                </div>

                <!-- CTA Action Button -->
                <template v-if="!selectable">
                    <Link
                        :href="bookingUrl"
                        class="w-full flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-glam-600 to-pink-600 hover:from-glam-700 hover:to-pink-700 shadow-md shadow-glam-500/20 transition-all hover:scale-[1.01] active:scale-[0.99]"
                        @click.stop
                    >
                        <span>Book Appointment</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </Link>
                </template>
                <template v-else>
                    <div
                        class="w-full py-2 px-3 rounded-xl text-center text-xs font-semibold transition-colors"
                        :class="selected ? 'bg-glam-600 text-white' : 'bg-rose-50 text-glam-700 hover:bg-rose-100'"
                    >
                        {{ selected ? '✓ Selected' : '+ Select Service' }}
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
