<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    trendingProducts: {
        type: Array,
        default: () => [],
    },
    featuredProducts: {
        type: Array,
        default: () => [],
    },
    newArrivals: {
        type: Array,
        default: () => [],
    },
    reviews: {
        type: Array,
        default: () => [],
    },
    latestBlogs: {
        type: Array,
        default: () => [],
    },
    brands: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const activeTab = ref('trending');
const activeHeroSlide = ref(0);
const quickSearch = ref('');
const notification = ref('');

// Auto-advance hero slides
setInterval(() => {
    if (props.banners.length > 1) {
        activeHeroSlide.value = (activeHeroSlide.value + 1) % props.banners.length;
    }
}, 6000);

const currentProducts = computed(() => {
    if (activeTab.value === 'featured') return props.featuredProducts;
    if (activeTab.value === 'new') return props.newArrivals;
    return props.trendingProducts;
});

const handleSearch = () => {
    if (quickSearch.value.trim()) {
        router.get(route('products.index'), { search: quickSearch.value.trim() });
    }
};

const addToCart = (product) => {
    try {
        let cart = JSON.parse(localStorage.getItem('beautybook_cart') || '[]');
        const index = cart.findIndex(item => item.id === product.id);
        if (index > -1) {
            cart[index].quantity = (cart[index].quantity || 1) + 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: Number(product.price),
                image: product.image_url,
                brand: product.brand,
                quantity: 1,
            });
        }
        localStorage.setItem('beautybook_cart', JSON.stringify(cart));
        window.dispatchEvent(new Event('cart-updated'));

        notification.value = `Added "${product.name}" to your shopping bag!`;
        setTimeout(() => {
            notification.value = '';
        }, 3000);
    } catch (e) {
        console.error(e);
    }
};
</script>

<template>
    <PublicLayout>
        <Head title="Luxe Beauty Market - 100% Genuine Cosmetics & Skincare" />

        <!-- Notification Toast -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div
                v-if="notification"
                class="fixed bottom-20 right-4 sm:bottom-8 sm:right-8 z-50 flex items-center gap-3 rounded-2xl bg-slate-900/95 text-white px-5 py-3.5 shadow-2xl border border-rose-500/30 backdrop-blur-xl max-w-md text-xs font-semibold"
            >
                <span class="text-base text-emerald-400">✓</span>
                <span class="flex-1">{{ notification }}</span>
                <Link :href="route('products.index', { cart: 'open' })" class="text-rose-400 hover:text-rose-300 underline font-bold uppercase tracking-wider text-[10px]">
                    View Bag
                </Link>
            </div>
        </transition>

        <div class="space-y-16 sm:space-y-24">
            <!-- 1. HERO SLIDER SECTION -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-3xl lg:rounded-4xl shadow-2xl border border-white/80 bg-slate-900 text-white min-h-[460px] sm:min-h-[520px] flex items-center">
                    <!-- Banner Slide Item -->
                    <div
                        v-for="(banner, idx) in (banners.length ? banners : [{ title: 'Elevate Your Natural Glow', subtitle: 'Explore 100% authentic international skincare & cosmetics.', tag: 'SPECIAL DEALS', image_url: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1600&q=85', button_text: 'Shop Now' }])"
                        :key="idx"
                        class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                        :class="activeHeroSlide === idx ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
                    >
                        <img
                            :src="banner.image_url || banner.image"
                            :alt="banner.title"
                            class="absolute inset-0 h-full w-full object-cover object-center transform scale-105 transition-transform duration-10000"
                        />
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/60 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent sm:hidden"></div>

                        <!-- Content Overlay -->
                        <div class="relative h-full flex flex-col justify-center max-w-2xl px-6 sm:px-12 lg:px-16 py-12 space-y-4 sm:space-y-6">
                            <div v-if="banner.tag" class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/30 backdrop-blur-md px-3.5 py-1 text-[11px] font-black uppercase tracking-widest text-pink-300 border border-pink-400/30 w-fit">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-400 animate-ping"></span>
                                <span>{{ banner.tag }}</span>
                            </div>

                            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-serif font-black tracking-tight leading-[1.1] text-white drop-shadow-md">
                                {{ banner.title }}
                            </h1>

                            <p class="text-xs sm:text-base text-slate-200 line-clamp-2 sm:line-clamp-3 leading-relaxed max-w-lg">
                                {{ banner.subtitle || 'Discover dermatologist-tested formulas and luxury cosmetics delivered with guaranteed authenticity.' }}
                            </p>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <Link
                                    :href="banner.target_url || route('products.index')"
                                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 px-7 py-3 text-xs sm:text-sm font-bold uppercase tracking-wider text-white shadow-xl shadow-rose-950/40 transition duration-300 hover:scale-105"
                                >
                                    <span>{{ banner.button_text || 'Shop The Collection' }}</span>
                                    <span>&rarr;</span>
                                </Link>
                                <Link
                                    :href="route('products.index')"
                                    class="inline-flex items-center gap-2 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md px-6 py-3 text-xs sm:text-sm font-bold uppercase tracking-wider text-white border border-white/30 transition"
                                >
                                    <span>Browse All</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Slide Navigation Dots -->
                    <div v-if="banners.length > 1" class="absolute bottom-6 right-6 sm:right-12 z-20 flex items-center gap-2">
                        <button
                            v-for="(_, bIdx) in banners"
                            :key="bIdx"
                            @click="activeHeroSlide = bIdx"
                            class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                            :class="activeHeroSlide === bIdx ? 'w-8 bg-rose-500' : 'w-2 bg-white/50 hover:bg-white'"
                            :aria-label="`Slide ${bIdx + 1}`"
                        ></button>
                    </div>
                </div>

                <!-- Hero Search & Category Pills Floating Ribbon -->
                <div class="mt-6 p-4 sm:p-5 rounded-3xl bg-white/90 backdrop-blur-xl border border-white/90 shadow-xl shadow-rose-950/5 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form @submit.prevent="handleSearch" class="relative w-full md:w-80">
                        <input
                            v-model="quickSearch"
                            type="text"
                            placeholder="Search by brand or product..."
                            class="w-full pl-9 pr-20 py-2.5 rounded-full text-xs bg-slate-50 border border-slate-200 focus:bg-white focus:border-rose-500 text-slate-900"
                        />
                        <span class="absolute left-3 top-3 text-slate-400 text-xs">🔍</span>
                        <button
                            type="submit"
                            class="absolute right-1.5 top-1.5 bottom-1.5 px-3 bg-slate-900 hover:bg-rose-600 text-white rounded-full text-[10px] font-bold uppercase transition"
                        >
                            Find
                        </button>
                    </form>

                    <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0 scrollbar-none text-xs">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 shrink-0">Explore:</span>
                        <Link :href="route('products.index', { category: 'skincare' })" class="px-3 py-1.5 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold border border-rose-200/60 shrink-0 transition">
                            ✨ Skincare
                        </Link>
                        <Link :href="route('products.index', { category: 'makeup-cosmetics' })" class="px-3 py-1.5 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold border border-pink-200/60 shrink-0 transition">
                            💄 Makeup
                        </Link>
                        <Link :href="route('products.index', { category: 'haircare-styling' })" class="px-3 py-1.5 rounded-full bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold border border-purple-200/60 shrink-0 transition">
                            💇‍♀️ Haircare
                        </Link>
                        <Link :href="route('products.index', { category: 'fragrances-perfumes' })" class="px-3 py-1.5 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold border border-amber-200/60 shrink-0 transition">
                            🌸 Perfumes
                        </Link>
                        <Link :href="route('products.index', { category: 'organic-herbal' })" class="px-3 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold border border-emerald-200/60 shrink-0 transition">
                            🌿 Organic
                        </Link>
                    </div>
                </div>
            </section>

            <!-- 2. FEATURED CATEGORIES SHOWCASE -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-700 border border-rose-200 mb-2">
                            <span>✨ CURATED DEPARTMENTS</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-black tracking-tight text-slate-900">
                            Shop by Category
                        </h2>
                    </div>
                    <Link :href="route('products.index')" class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                        <span>View All</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 sm:gap-6">
                    <Link
                        v-for="cat in categories"
                        :key="cat.id"
                        :href="route('products.index', { category: cat.slug })"
                        class="group relative overflow-hidden rounded-3xl bg-white p-4 shadow-sm border border-pink-100 hover:shadow-xl hover:border-rose-300 transition-all duration-300 flex flex-col items-center text-center cursor-pointer"
                    >
                        <div class="relative h-24 w-24 sm:h-28 sm:w-28 rounded-2xl overflow-hidden mb-3.5 bg-rose-50 group-hover:scale-105 transition-transform duration-300">
                            <img
                                :src="cat.image_url"
                                :alt="cat.name"
                                class="h-full w-full object-cover"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
                            <span v-if="cat.icon" class="absolute bottom-1.5 right-1.5 text-lg drop-shadow">
                                {{ cat.icon }}
                            </span>
                        </div>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-rose-600 transition truncate w-full">
                            {{ cat.name }}
                        </h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">
                            {{ cat.products_count ?? 10 }}+ Products
                        </p>
                    </Link>
                </div>
            </section>

            <!-- 3. PRODUCT SHOWCASE TABS (Trending, Bestsellers, New) -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-pink-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-pink-700 border border-pink-200 mb-2">
                            <span>🔥 HANDPICKED COLLECTION</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-black tracking-tight text-slate-900">
                            Trending & Bestselling Essentials
                        </h2>
                    </div>

                    <!-- Category Tab Switcher -->
                    <div class="flex items-center gap-1 rounded-full bg-slate-100 p-1 border border-slate-200 text-xs font-bold">
                        <button
                            @click="activeTab = 'trending'"
                            class="rounded-full px-4 py-1.5 transition-all cursor-pointer"
                            :class="activeTab === 'trending' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Trending Now
                        </button>
                        <button
                            @click="activeTab = 'featured'"
                            class="rounded-full px-4 py-1.5 transition-all cursor-pointer"
                            :class="activeTab === 'featured' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Bestsellers
                        </button>
                        <button
                            @click="activeTab = 'new'"
                            class="rounded-full px-4 py-1.5 transition-all cursor-pointer"
                            :class="activeTab === 'new' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            New Arrivals
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div
                        v-for="product in currentProducts"
                        :key="product.id"
                        class="group relative flex flex-col justify-between rounded-3xl bg-white p-4 shadow-sm border border-pink-100/90 hover:shadow-2xl hover:border-rose-300 transition-all duration-300"
                    >
                        <div>
                            <!-- Product Image & Badges -->
                            <div class="relative aspect-square w-full overflow-hidden rounded-2xl bg-slate-50 mb-3.5">
                                <Link :href="route('products.show', product.slug)">
                                    <img
                                        :src="product.image_url"
                                        :alt="product.name"
                                        class="h-full w-full object-cover object-center group-hover:scale-108 transition-transform duration-500"
                                    />
                                </Link>

                                <!-- Top Badges -->
                                <div class="absolute top-2.5 left-2.5 flex flex-col gap-1">
                                    <span v-if="product.badge" class="rounded-lg bg-rose-600 text-white text-[9px] font-black uppercase px-2 py-0.5 shadow-sm">
                                        {{ product.badge }}
                                    </span>
                                    <span v-if="product.discount_percentage > 0" class="rounded-lg bg-amber-500 text-white text-[9px] font-black px-2 py-0.5 shadow-sm">
                                        -{{ product.discount_percentage }}%
                                    </span>
                                </div>

                                <!-- Quick Add Overlay Button -->
                                <button
                                    @click="addToCart(product)"
                                    class="absolute bottom-2.5 inset-x-2.5 py-2.5 rounded-xl bg-slate-900/95 hover:bg-rose-600 text-white text-xs font-bold tracking-wide shadow-lg opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-200 cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <span>🛍️</span>
                                    <span>Quick Add to Bag</span>
                                </button>
                            </div>

                            <!-- Product Meta -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium">
                                    <span class="text-rose-500 font-bold uppercase tracking-wider text-[10px]">{{ product.brand || 'Luxe Care' }}</span>
                                    <div class="flex items-center gap-1 text-amber-500 font-bold">
                                        <span>★</span>
                                        <span>{{ product.rating || '4.9' }}</span>
                                        <span class="text-slate-400 font-normal">({{ product.reviews_count || 45 }})</span>
                                    </div>
                                </div>

                                <Link :href="route('products.show', product.slug)" class="block">
                                    <h3 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-2 group-hover:text-rose-600 transition leading-snug">
                                        {{ product.name }}
                                    </h3>
                                </Link>
                            </div>
                        </div>

                        <!-- Price & Action Footer -->
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-sm sm:text-base font-black text-slate-900">
                                        PKR {{ Number(product.price).toLocaleString() }}
                                    </span>
                                    <span v-if="product.original_price && product.original_price > product.price" class="text-[11px] text-slate-400 line-through">
                                        {{ Number(product.original_price).toLocaleString() }}
                                    </span>
                                </div>
                            </div>

                            <button
                                @click="addToCart(product)"
                                class="h-8 w-8 rounded-full bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 flex items-center justify-center text-xs transition cursor-pointer"
                                title="Add to Bag"
                            >
                                ＋
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-10">
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center gap-2 rounded-full border-2 border-slate-900 hover:bg-slate-900 hover:text-white px-8 py-3 text-xs font-bold uppercase tracking-wider text-slate-900 transition"
                    >
                        <span>Explore Full Marketplace Catalog ({{ stats.total_products || 25 }}+ Products)</span>
                        <span>&rarr;</span>
                    </Link>
                </div>
            </section>

            <!-- 4. LUXURY BRAND DIRECTORY -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-gradient-to-r from-rose-900 via-pink-900 to-slate-900 text-white p-8 sm:p-12 shadow-2xl relative overflow-hidden">
                    <div class="relative z-10 max-w-xl space-y-3">
                        <div class="inline-flex items-center gap-1 rounded-full bg-white/20 px-3 py-0.5 text-[10px] font-bold tracking-widest uppercase text-pink-200">
                            100% ORIGINAL SOURCED
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-serif font-black tracking-tight text-white">
                            World's Most Coveted Brands
                        </h2>
                        <p class="text-xs sm:text-sm text-pink-100 leading-relaxed">
                            From clinical skincare heroes to ultra-luxurious niche perfumes, discover authentic inventory with official batch verification.
                        </p>
                    </div>

                    <!-- Brand Badges Grid -->
                    <div class="relative z-10 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mt-8">
                        <Link
                            v-for="brand in (brands.length ? brands : ['The Ordinary', 'CeraVe', 'Fenty Beauty', 'Olaplex', 'COSRX', 'Maybelline', 'L\'Oréal Paris', 'Kayali', 'Maison Francis Kurkdjian', 'Sol de Janeiro'])"
                            :key="brand"
                            :href="route('products.index', { brand: brand })"
                            class="p-3.5 rounded-2xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-center font-bold text-xs text-white transition hover:scale-102"
                        >
                            {{ brand }}
                        </Link>
                    </div>
                </div>
            </section>

            <!-- 5. VERIFIED CUSTOMER EXPERIENCES -->
            <section v-if="reviews && reviews.length" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-xl mx-auto mb-10 space-y-2">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-800 border border-emerald-200">
                        <span>★ VERIFIED BUYER SATISFACTION</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                        Loved by 25,000+ Customers
                    </h2>
                    <p class="text-xs text-slate-500">
                        Real reviews from buyers across Pakistan experiencing authentic transformations.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="rev in reviews"
                        :key="rev.id"
                        class="rounded-3xl bg-white p-6 shadow-sm border border-pink-100 flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center gap-1 text-amber-400 text-sm">
                                <span v-for="s in (rev.rating || 5)" :key="s">★</span>
                            </div>
                            <h4 v-if="rev.title" class="font-bold text-slate-900 text-xs">
                                "{{ rev.title }}"
                            </h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                {{ rev.comment }}
                            </p>
                        </div>

                        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <div class="h-7 w-7 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-[11px]">
                                    {{ rev.author_name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-[11px]">{{ rev.author_name }}</p>
                                    <span class="text-[9px] text-emerald-600 font-semibold">✓ Verified Buyer</span>
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ rev.time_ago || 'Recently' }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. BEAUTY MAGAZINE & EDITORIAL GUIDES -->
            <section v-if="latestBlogs && latestBlogs.length" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-purple-700 border border-purple-200 mb-2">
                            <span>📖 BEAUTY MAGAZINE</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                            Expert Guides & Glow Routines
                        </h2>
                    </div>
                    <Link :href="route('blogs.index')" class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                        <span>Read All</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Link
                        v-for="blog in latestBlogs"
                        :key="blog.id"
                        :href="route('blogs.show', blog.slug)"
                        class="group overflow-hidden rounded-3xl bg-white shadow-sm border border-pink-100 hover:shadow-xl hover:border-rose-300 transition-all flex flex-col sm:flex-row"
                    >
                        <div class="sm:w-2/5 aspect-video sm:aspect-auto overflow-hidden bg-slate-100">
                            <img
                                :src="blog.image || 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80'"
                                :alt="blog.title"
                                class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                        </div>
                        <div class="sm:w-3/5 p-5 sm:p-6 flex flex-col justify-between space-y-3">
                            <div class="space-y-1.5">
                                <span class="text-[10px] font-black uppercase tracking-wider text-rose-600">
                                    {{ blog.category || 'Skincare Guide' }}
                                </span>
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base group-hover:text-rose-600 transition leading-snug">
                                    {{ blog.title }}
                                </h3>
                                <p class="text-xs text-slate-500 line-clamp-2">
                                    {{ blog.summary || 'Expert beauty insights and skincare routines.' }}
                                </p>
                            </div>
                            <span class="text-xs font-bold text-rose-600 flex items-center gap-1">
                                <span>Read Article</span>
                                <span>&rarr;</span>
                            </span>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
