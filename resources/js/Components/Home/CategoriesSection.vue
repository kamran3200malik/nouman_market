<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

// Category Metadata Registry with High-Resolution Curated Imagery, Icons, Gradients, and Subtitles
const categoryMetaMap = {
    // Makeup & Bridal
    'bridal-makeup': {
        icon: '👰',
        tag: 'Most Booked',
        badgeColor: 'bg-rose-500 text-white',
        image: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Barat, Walima & Nikkah Glam',
    },
    'party-makeup': {
        icon: '💄',
        tag: 'Trending',
        badgeColor: 'bg-pink-600 text-white',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Evening Glam & Soft Glow',
    },
    'makeup': {
        icon: '💄',
        tag: 'Haute Glam',
        badgeColor: 'bg-rose-500 text-white',
        image: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Bridal, Party & HD Artistry',
    },

    // Hair
    'hair-styling': {
        icon: '💇‍♀️',
        tag: 'Haute Coiffure',
        badgeColor: 'bg-violet-600 text-white',
        image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Waves, Updos & Blowouts',
    },
    'hair': {
        icon: '💇‍♀️',
        tag: 'Top Salons',
        badgeColor: 'bg-violet-600 text-white',
        image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Cuts, Colors, Keratin & Styling',
    },
    'hair-coloring': {
        icon: '🎨',
        tag: 'Color Bar',
        badgeColor: 'bg-indigo-600 text-white',
        image: 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Balayage, Highlights & Gloss',
    },

    // Mehndi & Henna
    'bridal-mehndi': {
        icon: '🌿',
        tag: 'Bridal Henna',
        badgeColor: 'bg-emerald-600 text-white',
        image: 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Intricate Heritage Henna Art',
    },
    'mehndi': {
        icon: '🌿',
        tag: 'Henna Artists',
        badgeColor: 'bg-emerald-600 text-white',
        image: 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Bridal, Arabic & Minimalist Art',
    },
    'arabic-mehndi': {
        icon: '🌿',
        tag: 'Trending',
        badgeColor: 'bg-teal-600 text-white',
        image: 'https://images.unsplash.com/photo-1596704017254-9b121068fb31?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Floral & Geometric Belts',
    },

    // Nails
    'nail-art': {
        icon: '💅',
        tag: 'Nail Studio',
        badgeColor: 'bg-rose-500 text-white',
        image: 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Gel Extensions & 3D Art',
    },
    'nails': {
        icon: '💅',
        tag: 'Nail Couture',
        badgeColor: 'bg-rose-500 text-white',
        image: 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Extensions, Gel Polish, Mani & Pedi',
    },

    // Skin & Spa
    'facial': {
        icon: '✨',
        tag: 'Hydra Glow',
        badgeColor: 'bg-amber-500 text-white',
        image: 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80',
        subtitle: 'HydraFacials & Deep Cleansing',
    },
    'skin': {
        icon: '✨',
        tag: 'Aesthetics',
        badgeColor: 'bg-amber-500 text-white',
        image: 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Facials, Peels & Skin Glow Spas',
    },

    // Brows & Lashes
    'brow-shaping': {
        icon: '👁️',
        tag: 'Arch Masters',
        badgeColor: 'bg-indigo-600 text-white',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
        subtitle: 'HD Threading & Lamination',
    },
    'brows': {
        icon: '👁️',
        tag: 'Brow Studio',
        badgeColor: 'bg-indigo-600 text-white',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Mapping, Threading & Tinting',
    },
    'lashes': {
        icon: '✨',
        tag: 'Lash Bar',
        badgeColor: 'bg-cyan-600 text-white',
        image: 'https://images.unsplash.com/photo-1583001931096-959e9a1a6223?auto=format&fit=crop&w=600&q=80',
        subtitle: 'Russian Volume & Lash Lifts',
    },
};

const fallbackImages = [
    'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1610992015732-2449b76344bc?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
];

const defaultFeatured = [
    { name: 'Bridal & Glam', slug: 'bridal-makeup', count: '45+ Artists', icon: '👰', tag: 'Most Booked', badgeColor: 'bg-rose-500 text-white', subtitle: 'Barat, Walima & Nikkah', image: 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80' },
    { name: 'Hair & Styling', slug: 'hair', count: '80+ Salons', icon: '💇‍♀️', tag: 'Haute Coiffure', badgeColor: 'bg-violet-600 text-white', subtitle: 'Cuts, Balayage & Updos', image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=600&q=80' },
    { name: 'Mehndi & Henna', slug: 'mehndi', count: '55+ Specialists', icon: '🌿', tag: 'Heritage Art', badgeColor: 'bg-emerald-600 text-white', subtitle: 'Bridal & Arabic Henna', image: 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?auto=format&fit=crop&w=600&q=80' },
    { name: 'Skin & Facials', slug: 'skin', count: '60+ Spas', icon: '✨', tag: 'Hydra Glow', badgeColor: 'bg-amber-500 text-white', subtitle: 'HydraFacial & Deep Glow', image: 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80' },
    { name: 'Nails & Care', slug: 'nails', count: '40+ Studios', icon: '💅', tag: 'Nail Couture', badgeColor: 'bg-pink-500 text-white', subtitle: 'Gel Extensions, Mani & Pedi', image: 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=600&q=80' },
    { name: 'Brows & Lashes', slug: 'brows', count: '35+ Studios', icon: '👁️', tag: 'Arch Masters', badgeColor: 'bg-indigo-600 text-white', subtitle: 'HD Threading & Lash Lift', image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80' },
];

// Show strictly top 6 categories on home page
const featuredCategories = computed(() => {
    if (props.categories && props.categories.length > 0) {
        return props.categories.slice(0, 6).map((cat, idx) => {
            const meta = categoryMetaMap[cat.slug] || {};
            const countNumber = cat.services_count || cat.artists_count || (20 + (idx * 9) % 70);

            return {
                id: cat.id || idx,
                name: cat.name,
                slug: cat.slug,
                countText: `${countNumber}+ Specialists`,
                icon: meta.icon || '✨',
                tag: meta.tag || 'Verified',
                badgeColor: meta.badgeColor || 'bg-rose-500 text-white',
                image: cat.image || meta.image || fallbackImages[idx % fallbackImages.length],
                subtitle: meta.subtitle || 'Bespoke Salon Treatments',
            };
        });
    }
    return defaultFeatured;
});
</script>

<template>
    <!-- LUXURY BROWSE BY BEAUTY CATEGORY (CURATED 6 CATEGORIES) -->
    <section class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
            <div>
                <div class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-rose-500/10 via-pink-500/10 to-amber-500/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-rose-700 border border-rose-200/70 shadow-xs backdrop-blur-md">
                    <span>🌸</span>
                    <span>Curated Taxonomy</span>
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1.5 tracking-tight">
                    Explore by Beauty Category
                </h2>
            </div>

            <!-- View All Categories Link -->
            <Link
                :href="route('services.index')"
                class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-rose-600 hover:text-rose-800 transition group cursor-pointer"
            >
                <span>View All Categories</span>
                <span class="group-hover:translate-x-1 transition-transform duration-200">&rarr;</span>
            </Link>
        </div>

        <!-- Curated 6-Category Grid (1 Clean Row on Desktop) -->
        <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-6">
            <Link
                v-for="cat in featuredCategories"
                :key="cat.slug || cat.name"
                :href="route('artists.index', { category: cat.slug })"
                class="group relative rounded-3xl bg-white/95 backdrop-blur-xl border border-pink-100/90 shadow-sm hover:shadow-xl hover:shadow-rose-500/10 hover:border-rose-300 transition-all duration-300 flex flex-col overflow-hidden cursor-pointer hover:-translate-y-1.5"
            >
                <!-- Card Image Banner with Overlay -->
                <div class="relative aspect-[4/3] w-full overflow-hidden bg-stone-100">
                    <img
                        :src="cat.image"
                        :alt="cat.name"
                        loading="lazy"
                        class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                    />
                    
                    <!-- Gradient Scrim for Contrast -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>

                    <!-- Floating Frosted Glass Icon Badge -->
                    <div class="absolute top-2.5 left-2.5 flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-white/85 backdrop-blur-md border border-white/60 shadow-xs text-sm sm:text-base group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                        {{ cat.icon }}
                    </div>

                    <!-- Category Status / Tag Pill -->
                    <div class="absolute top-2.5 right-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider backdrop-blur-md shadow-xs" :class="cat.badgeColor">
                            {{ cat.tag }}
                        </span>
                    </div>

                    <!-- Floating Specialist Count Badge on Image -->
                    <div class="absolute bottom-2 left-2.5 right-2.5 flex items-center justify-between text-white text-[11px] font-bold drop-shadow-sm">
                        <span class="inline-flex items-center gap-1 bg-black/40 backdrop-blur-md px-2 py-0.5 rounded-lg border border-white/20 text-[10px]">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            {{ cat.countText }}
                        </span>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-3 sm:p-3.5 flex flex-col justify-between flex-1 gap-2">
                    <div>
                        <h3 class="font-serif text-xs sm:text-sm font-bold text-slate-900 group-hover:text-rose-600 transition-colors line-clamp-1">
                            {{ cat.name }}
                        </h3>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                            {{ cat.subtitle }}
                        </p>
                    </div>

                    <!-- Bottom Action Link -->
                    <div class="pt-1 border-t border-rose-50 flex items-center justify-between text-[11px] font-bold text-rose-600 group-hover:text-rose-700">
                        <span class="text-[10px] font-semibold text-slate-400 group-hover:text-rose-500 transition-colors">Explore</span>
                        <span class="flex items-center justify-center h-5 w-5 rounded-full bg-rose-50 group-hover:bg-rose-600 group-hover:text-white transition-all duration-300 text-xs">
                            &rarr;
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    </section>
</template>



