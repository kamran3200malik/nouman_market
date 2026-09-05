<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ArtistCard from '@/Components/ArtistCard.vue';

const props = defineProps({
    featuredArtists: {
        type: Array,
        default: () => [],
    },
});

const activeFilter = ref('all');

const filterTabs = [
    { id: 'all', label: 'All Studios', icon: '✨' },
    { id: 'bridal', label: 'Bridal Artists', icon: '👰' },
    { id: 'home', label: 'Home Visit Available', icon: '🏡' },
    { id: 'top_rated', label: 'Top Rated (5.0★)', icon: '⭐' },
];

const displayedArtists = computed(() => {
    if (!props.featuredArtists || props.featuredArtists.length === 0) return [];
    
    if (activeFilter.value === 'bridal') {
        const bridalList = props.featuredArtists.filter(a => 
            (a.bio && a.bio.toLowerCase().includes('bridal')) ||
            (a.business_name && a.business_name.toLowerCase().includes('bridal')) ||
            (a.services && a.services.some(s => s.name && s.name.toLowerCase().includes('bridal')))
        );
        return bridalList.length > 0 ? bridalList.slice(0, 4) : props.featuredArtists.slice(0, 4);
    }
    if (activeFilter.value === 'home') {
        const homeList = props.featuredArtists.filter(a => a.home_service_available || a.home_service);
        return homeList.length > 0 ? homeList.slice(0, 4) : props.featuredArtists.slice(0, 4);
    }
    if (activeFilter.value === 'top_rated') {
        return [...props.featuredArtists].sort((a, b) => (b.rating_avg || 5) - (a.rating_avg || 5)).slice(0, 4);
    }
    
    return props.featuredArtists.slice(0, 4);
});

// Curated demo cards if database has fewer items
const fallbackStudios = [
    {
        id: 'fb-1',
        business_name: 'Maison De Beauté Haute Salon',
        slug: 'maison-de-beaute',
        city: { name: 'Karachi' },
        area: { name: 'Clifton Block 4' },
        rating_avg: 4.98,
        reviews_count: 84,
        starting_price: 4500,
        is_featured: true,
        is_verified: true,
        home_service_available: true,
        cover_image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80',
        user: { avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80' },
        bio: 'Award-winning bridal studio specializing in signature airbrush artistry, customized skin therapies, and couture hairstyling.',
        services: [
            { id: 1, name: 'Signature Bridal HD', price: 35000 },
            { id: 2, name: 'Hydra Glow Polish', price: 6500 },
            { id: 3, name: 'Hollywood Waves', price: 4500 },
        ],
    },
    {
        id: 'fb-2',
        business_name: 'L’Aura Bridal Atelier & Spa',
        slug: 'laura-bridal-atelier',
        city: { name: 'Lahore' },
        area: { name: 'Gulberg III' },
        rating_avg: 5.0,
        reviews_count: 112,
        starting_price: 3500,
        is_featured: true,
        is_verified: true,
        home_service_available: false,
        cover_image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80',
        user: { avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=200&q=80' },
        bio: 'Premier luxury salon destination for Barat & Walima glam, balayage hair color artistry, and Russian gel manicures.',
        services: [
            { id: 4, name: 'Barat Royal Glam', price: 32000 },
            { id: 5, name: 'Balayage Blonde Glow', price: 14500 },
            { id: 6, name: 'Russian Gel Nail Art', price: 3500 },
        ],
    },
    {
        id: 'fb-3',
        business_name: 'Glamour & Co. Aesthetic Lounge',
        slug: 'glamour-and-co',
        city: { name: 'Islamabad' },
        area: { name: 'F-7 Markaz' },
        rating_avg: 4.95,
        reviews_count: 67,
        starting_price: 5000,
        is_featured: true,
        is_verified: true,
        home_service_available: true,
        cover_image: 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=800&q=80',
        user: { avatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=200&q=80' },
        bio: 'Bespoke aesthetic clinic offering organic HydraFacials, micro-threading, and celebrity party makeovers with VIP suites.',
        services: [
            { id: 7, name: 'Celebrity Party Look', price: 12000 },
            { id: 8, name: 'Deep Hydra Derm Facial', price: 8500 },
            { id: 9, name: 'Lash Russian Volume', price: 5000 },
        ],
    },
    {
        id: 'fb-4',
        business_name: 'Royal Henna & Hair Sanctuary',
        slug: 'royal-henna-sanctuary',
        city: { name: 'Karachi' },
        area: { name: 'DHA Phase 6' },
        rating_avg: 4.92,
        reviews_count: 95,
        starting_price: 2800,
        is_featured: true,
        is_verified: true,
        home_service_available: true,
        cover_image: 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?auto=format&fit=crop&w=800&q=80',
        user: { avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=200&q=80' },
        bio: 'Masters of intricate Rajasthani & Arabic bridal henna, traditional dupatta draping, and organic keratin hair infusions.',
        services: [
            { id: 10, name: 'Full Bridal Henna Set', price: 18000 },
            { id: 11, name: 'Organic Keratin Spa', price: 9500 },
            { id: 12, name: 'Arabic Floral Henna', price: 2800 },
        ],
    },
];
</script>

<template>
    <!-- 3. LUXURY TOP-RATED SALONS & MASTER ARTISTS -->
    <section class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-500/10 via-rose-500/10 to-amber-500/10 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-800 border border-emerald-200/70 shadow-xs backdrop-blur-md">
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-white text-[9px] font-black">✓</span>
                    <span>100% Admin-Approved & Verified Studios</span>
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
                    Top-Rated Salons & Master Stylists
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl">
                    Hand-picked beauty studios, celebrity bridal stylists, and aesthetic lounges verified and approved by admin with Escrow protection.
                </p>
            </div>

            <!-- View All Link -->
            <div class="flex items-center gap-3 self-start md:self-end">
                <Link
                    :href="route('artists.index')"
                    class="inline-flex items-center gap-2 rounded-xl bg-white/90 px-4 py-2.5 text-xs font-bold text-rose-700 border border-rose-200/80 shadow-xs hover:bg-rose-50 hover:border-rose-300 hover:shadow-md transition-all duration-200 group cursor-pointer"
                >
                    <span>View All Artists ({{ featuredArtists.length || '120+' }})</span>
                    <span class="group-hover:translate-x-1 transition-transform duration-200 text-rose-600">&rarr;</span>
                </Link>
            </div>
        </div>

        <!-- Filter Quick-Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-none mb-6">
            <button
                v-for="tab in filterTabs"
                :key="tab.id"
                type="button"
                @click="activeFilter = tab.id"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all duration-300 cursor-pointer shadow-2xs"
                :class="activeFilter === tab.id
                    ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white shadow-md shadow-rose-500/25 scale-[1.02]'
                    : 'bg-white/80 text-slate-600 hover:text-slate-900 hover:bg-white border border-rose-100/80 hover:border-pink-200'"
            >
                <span>{{ tab.icon }}</span>
                <span>{{ tab.label }}</span>
            </button>
        </div>

        <!-- Active Artists Grid -->
        <div v-if="displayedArtists.length > 0" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <ArtistCard
                v-for="artist in displayedArtists"
                :key="artist.id"
                :artist="artist"
            />
        </div>

        <!-- Curated Fallback Grid if Empty -->
        <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <ArtistCard
                v-for="artist in fallbackStudios"
                :key="artist.id"
                :artist="artist"
            />
        </div>
    </section>
</template>

