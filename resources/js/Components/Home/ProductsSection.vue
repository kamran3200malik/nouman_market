<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(price || 0);
};

const activeTab = ref('all');

const filterTabs = [
    { id: 'all', label: 'All Essentials', icon: '✨' },
    { id: 'hair', label: 'Hair & Serums', icon: '💇‍♀️' },
    { id: 'skin', label: 'Skincare & Glow', icon: '✨' },
    { id: 'organic', label: 'Organic Elixirs', icon: '🌿' },
];

const fallbackProducts = [
    {
        id: 1,
        name: 'Organic Moroccan Argan Hair Serum',
        slug: 'organic-argan-hair-oil',
        brand: 'Maison Luxe Haircare',
        category: 'hair',
        description: 'Deep nourishing elixir for frizzy hair, heat protection & intense diamond gloss.',
        price: 3450,
        original_price: 3950,
        image: 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=600&q=80',
        rating: 4.95,
        reviewsCount: 124,
        tag: 'Best Seller',
        badgeColor: 'bg-rose-600 text-white',
        size: '100 ml / 3.4 oz',
    },
    {
        id: 2,
        name: 'Gold Radiance Vitamin C Brightening Serum',
        slug: 'gold-radiance-serum',
        brand: 'Aura Beauté Derm',
        category: 'skin',
        description: 'Potent antioxidant booster for instant glass skin glow and dark spot defense.',
        price: 2890,
        original_price: 3400,
        image: 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=600&q=80',
        rating: 4.88,
        reviewsCount: 98,
        tag: 'Trending',
        badgeColor: 'bg-amber-500 text-white',
        size: '50 ml / 1.7 oz',
    },
    {
        id: 3,
        name: 'Damascus Rose Hydrating Mist & Floral Toner',
        slug: 'rosewater-hydrating-mist',
        brand: 'Botanica Essence',
        category: 'organic',
        description: 'Pure cold-distilled Damascus rosewater with hyaluronic acid for 24h dewy hydration.',
        price: 1850,
        original_price: 2200,
        image: 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=600&q=80',
        rating: 4.92,
        reviewsCount: 142,
        tag: 'Organic 100%',
        badgeColor: 'bg-emerald-600 text-white',
        size: '120 ml / 4.0 oz',
    },
    {
        id: 4,
        name: 'Keratin Silk Repair Intensive Hair Mask',
        slug: 'keratin-silk-hair-mask',
        brand: 'Salon Pro Formulas',
        category: 'hair',
        description: 'Deep reconstructive salon treatment for chemically treated and heat damaged cuticles.',
        price: 4200,
        original_price: 4800,
        image: 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&w=600&q=80',
        rating: 4.89,
        reviewsCount: 76,
        tag: 'Salon Grade',
        badgeColor: 'bg-purple-600 text-white',
        size: '250 ml / 8.5 oz',
    },
];

const allProducts = computed(() => {
    if (props.products && props.products.length > 0) {
        return props.products.slice(0, 8).map((p, idx) => {
            const fallback = fallbackProducts[idx % fallbackProducts.length];
            return {
                id: p.id,
                name: p.name,
                slug: p.slug || `product-${p.id}`,
                brand: p.artist_profile?.business_name || fallback.brand,
                category: idx % 2 === 0 ? 'hair' : 'skin',
                description: p.description || fallback.description,
                price: Number(p.price),
                original_price: p.discount_price ? Number(p.price) : Number(p.price) * 1.15,
                image: storageUrl(p.image, fallback.image),
                rating: Number(p.rating || fallback.rating),
                reviewsCount: p.reviews_count || fallback.reviewsCount,
                tag: p.is_trending ? 'Trending' : fallback.tag,
                badgeColor: fallback.badgeColor,
                size: p.volume || fallback.size,
            };
        });
    }
    return fallbackProducts;
});

const filteredProducts = computed(() => {
    if (activeTab.value === 'all') {
        return allProducts.value.slice(0, 4);
    }
    const filtered = allProducts.value.filter(p => p.category === activeTab.value);
    return filtered.length > 0 ? filtered.slice(0, 4) : allProducts.value.slice(0, 4);
});

const addedProducts = ref({});
const buyProduct = (product) => {
    addedProducts.value[product.id] = true;
    setTimeout(() => {
        addedProducts.value[product.id] = false;
    }, 2000);
    router.visit(route('products.index', { highlight: product.id }));
};
</script>

<template>
    <!-- 4. LUXURY COSMETICS & CARE BOUTIQUE -->
    <section class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-3xl p-6 sm:p-9 bg-gradient-to-br from-white/95 via-rose-50/30 to-amber-50/20 backdrop-blur-xl border border-rose-200/70 shadow-xl space-y-6 overflow-hidden">
            <!-- Background Ambient Glow Blobs -->
            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-rose-400/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"></div>

            <!-- Section Header -->
            <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-500/10 via-rose-500/10 to-pink-500/10 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-800 border border-emerald-200/70 shadow-xs backdrop-blur-md">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-white text-[9px] font-black">✓</span>
                        <span>100% Admin-Approved Beauty Boutique</span>
                    </div>
                    <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
                        Shop Beauty & Hair Care Essentials
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl">
                        Salon-grade hair serums, organic botanical oils, and glowing skincare treatments verified and approved by admin.
                    </p>
                </div>

                <!-- Explore Store Button -->
                <div class="flex items-center gap-3 self-start md:self-end">
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/90 px-4 py-2.5 text-xs font-bold text-rose-700 border border-rose-200/80 shadow-xs hover:bg-rose-50 hover:border-rose-300 hover:shadow-md transition-all duration-200 group cursor-pointer"
                    >
                        <span>Explore Full Boutique</span>
                        <span class="group-hover:translate-x-1 transition-transform duration-200 text-rose-600">&rarr;</span>
                    </Link>
                </div>
            </div>

            <!-- Category Filter Tabs -->
            <div class="relative z-10 flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="tab in filterTabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all duration-300 cursor-pointer shadow-2xs"
                    :class="activeTab === tab.id
                        ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white shadow-md shadow-rose-500/25 scale-[1.02]'
                        : 'bg-white/80 text-slate-600 hover:text-slate-900 hover:bg-white border border-rose-100/80 hover:border-pink-200'"
                >
                    <span>{{ tab.icon }}</span>
                    <span>{{ tab.label }}</span>
                </button>
            </div>

            <!-- Products Grid (4 items - 2 columns on mobile for modern boutique UX) -->
            <div class="relative z-10 grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="group relative rounded-2xl sm:rounded-3xl bg-white/95 backdrop-blur-xl p-3 sm:p-4 border border-rose-100/90 shadow-sm hover:shadow-2xl hover:shadow-rose-500/10 hover:border-rose-300 transition-all duration-300 flex flex-col justify-between space-y-2.5 sm:space-y-3.5 hover:-translate-y-1.5 overflow-hidden"
                >
                    <!-- Product Image Container -->
                    <div class="relative aspect-square w-full rounded-xl sm:rounded-2xl overflow-hidden bg-stone-100 shadow-inner">
                        <img
                            :src="product.image"
                            :alt="product.name"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                            loading="lazy"
                        />
                        
                        <!-- Floating Admin-Approved Badge -->
                        <span class="absolute top-2 left-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-extrabold uppercase tracking-wider shadow-sm backdrop-blur-md bg-emerald-600/95 text-white">
                            <span>✓</span>
                            <span>Best</span>
                        </span>

                        <!-- Rating Pill -->
                        <div class="absolute bottom-2 left-2 inline-flex items-center gap-1 bg-black/60 backdrop-blur-md px-1.5 sm:px-2 py-0.5 rounded-lg border border-white/20 text-white text-[9px] sm:text-[10px] font-bold">
                            <span class="text-amber-400">★</span>
                            <span>{{ product.rating.toFixed(1) }}</span>
                            <span class="text-slate-300 font-normal hidden sm:inline">({{ product.reviewsCount }})</span>
                        </div>
                    </div>

                    <!-- Product Body -->
                    <div class="space-y-0.5 sm:space-y-1">
                        <p class="text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider text-rose-600 truncate">
                            {{ product.brand }}
                        </p>
                        <h4 class="font-serif text-xs sm:text-sm font-bold text-slate-900 group-hover:text-rose-600 transition-colors line-clamp-1">
                            {{ product.name }}
                        </h4>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 line-clamp-2 leading-relaxed hidden sm:block">
                            {{ product.description }}
                        </p>
                    </div>

                    <!-- Price & Buy Action Row -->
                    <div class="pt-2 sm:pt-3 border-t border-rose-100/80 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2">
                        <div>
                            <span class="text-[9px] text-slate-400 line-through block" v-if="product.original_price > product.price">
                                {{ formatPrice(product.original_price) }}
                            </span>
                            <p class="font-serif text-xs sm:text-base font-extrabold text-rose-700">
                                {{ formatPrice(product.price) }}
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="buyProduct(product)"
                            :class="addedProducts[product.id] 
                                ? 'bg-emerald-600 text-white' 
                                : 'bg-gradient-to-r from-slate-900 to-slate-800 hover:from-rose-600 hover:to-pink-600 text-white shadow-sm hover:shadow-md hover:shadow-rose-600/20'"
                            class="w-full sm:w-auto rounded-xl px-2.5 sm:px-4 py-1.5 sm:py-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider transition-all duration-300 cursor-pointer hover:scale-103 active:scale-98 text-center"
                        >
                            {{ addedProducts[product.id] ? '✓ Added' : 'Buy Now' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Trust Bar Strip -->
            <div class="relative z-10 pt-4 border-t border-rose-100/80 grid grid-cols-1 sm:grid-cols-3 gap-3 text-center sm:text-left">
                <div class="flex items-center justify-center sm:justify-start gap-2 text-xs font-semibold text-slate-700">
                    <span class="text-base">🚚</span>
                    <span>Express Delivery Across Pakistan</span>
                </div>
                <div class="flex items-center justify-center sm:justify-start gap-2 text-xs font-semibold text-slate-700">
                    <span class="text-base">🛡️</span>
                    <span>100% Authentic & Salon Certified</span>
                </div>
                <div class="flex items-center justify-center sm:justify-start gap-2 text-xs font-semibold text-slate-700">
                    <span class="text-base">💳</span>
                    <span>Cash on Delivery & Secure Escrow</span>
                </div>
            </div>
        </div>
    </section>
</template>

