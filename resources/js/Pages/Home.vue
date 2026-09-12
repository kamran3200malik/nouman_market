<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import HeroSection from '@/Components/Home/HeroSection.vue';

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
const notification = ref('');
const notificationType = ref('success');
const addingProductId = ref(null);
const newsletterEmail = ref('');
const newsletterSubscribed = ref(false);

const currentProducts = computed(() => {
    if (activeTab.value === 'featured') return props.featuredProducts;
    if (activeTab.value === 'new') return props.newArrivals;
    return props.trendingProducts;
});

const formatPrice = (price) => {
    if (!price && price !== 0) return 'PKR 0';
    return 'PKR ' + Number(price).toLocaleString('en-PK');
};

const addToCart = (product) => {
    try {
        addingProductId.value = product.id;
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
                brand: product.brand || 'Luxe Beauty',
                quantity: 1,
            });
        }
        localStorage.setItem('beautybook_cart', JSON.stringify(cart));
        window.dispatchEvent(new Event('cart-updated'));

        notificationType.value = 'success';
        notification.value = `Added "${product.name}" to your shopping bag!`;
        
        setTimeout(() => {
            addingProductId.value = null;
        }, 400);

        setTimeout(() => {
            notification.value = '';
        }, 3500);
    } catch (e) {
        console.error(e);
        addingProductId.value = null;
    }
};

const handleNewsletter = () => {
    if (newsletterEmail.value && newsletterEmail.value.includes('@')) {
        newsletterSubscribed.value = true;
        notificationType.value = 'vip';
        notification.value = '🎉 Welcome to the Luxe VIP Club! Use code LUXE500 for PKR 500 off.';
        setTimeout(() => {
            notification.value = '';
        }, 5000);
    }
};

const trustPerks = [
    {
        icon: '💎',
        title: '100% Genuine Guarantee',
        desc: 'Direct brand sourcing with authentic batch-code verification.',
        badge: 'Verified Authentic',
    },
    {
        icon: '🚀',
        title: 'Free Nationwide Delivery',
        desc: 'Complimentary shipping across Pakistan on orders over PKR 5,000.',
        badge: 'Express 24-48H',
    },
    {
        icon: '🛡️',
        title: 'Cash on Delivery & Cards',
        desc: 'Pay securely at your doorstep or via 256-bit encrypted card gateway.',
        badge: 'Buyer Protected',
    },
    {
        icon: '✨',
        title: '7-Day Hassle-Free Returns',
        desc: 'Dedicated beauty concierge with seamless returns on unopened items.',
        badge: 'Satisfaction First',
    },
];
</script>

<template>
    <PublicLayout>
        <Head title="Luxe Beauty Market - 100% Genuine Cosmetics, Skincare & Fragrances" />

        <!-- Floating Notification Toast -->
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
                class="fixed bottom-20 right-4 sm:bottom-8 sm:right-8 z-50 flex items-center gap-3.5 rounded-2xl bg-slate-900/95 text-white px-5 py-3.5 shadow-2xl border border-rose-500/30 backdrop-blur-xl max-w-md text-xs font-semibold"
            >
                <div class="h-7 w-7 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0 text-sm font-bold">
                    ✓
                </div>
                <span class="flex-1 leading-snug">{{ notification }}</span>
                <Link :href="route('products.index', { cart: 'open' })" class="text-rose-400 hover:text-rose-300 font-bold uppercase tracking-wider text-[11px] underline shrink-0">
                    View Bag
                </Link>
            </div>
        </transition>

        <div class="space-y-16 sm:space-y-24">
            <!-- 1. HERO SECTION (RESTORED ULTRA-LUXURY BILLBOARD + DUAL SPOTLIGHTS + PRODUCT STRIP) -->
            <HeroSection
                :banners="banners"
                :products="trendingProducts"
                :categories="categories"
            />

            <!-- 2. MARKETPLACE TRUST & VALUE PERKS BAR -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <div
                        v-for="(perk, idx) in trustPerks"
                        :key="idx"
                        class="group relative overflow-hidden rounded-3xl bg-white p-5 sm:p-6 shadow-sm border border-slate-100 hover:border-rose-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex items-start gap-4"
                    >
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-rose-50 to-pink-100 border border-rose-100 text-2xl flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:bg-rose-600 group-hover:text-white transition-all duration-300 shadow-2xs">
                            {{ perk.icon }}
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-rose-600 transition truncate">
                                    {{ perk.title }}
                                </h3>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                {{ perk.desc }}
                            </p>
                            <span class="inline-block text-[9px] font-extrabold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md mt-1">
                                {{ perk.badge }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. CURATED DEPARTMENTS / SHOP BY CATEGORY -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-100/80 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-700 border border-rose-200 mb-2">
                            <span>✨ CURATED DEPARTMENTS</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-black tracking-tight text-slate-900">
                            Shop by Beauty Category
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Explore top tier dermatological skincare, makeup essentials, haircare rituals, and luxury perfumes.
                        </p>
                    </div>
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-4 py-2 rounded-full border border-rose-200/80 transition-all shrink-0"
                    >
                        <span>Browse All Categories</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 sm:gap-5">
                    <Link
                        v-for="cat in categories"
                        :key="cat.id"
                        :href="route('products.index', { category: cat.slug })"
                        class="group relative overflow-hidden rounded-3xl bg-white p-3.5 sm:p-4 shadow-sm border border-slate-100 hover:shadow-xl hover:border-rose-300 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center cursor-pointer"
                    >
                        <!-- Category Thumbnail -->
                        <div class="relative h-28 w-28 sm:h-32 sm:w-32 rounded-2xl overflow-hidden mb-3 bg-gradient-to-tr from-rose-50 to-pink-50">
                            <img
                                :src="cat.image_url"
                                :alt="cat.name"
                                class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 via-transparent to-transparent opacity-60 group-hover:opacity-80 transition-opacity"></div>
                            
                            <!-- Category Floating Emoji Badge -->
                            <span v-if="cat.icon" class="absolute bottom-2 right-2 text-xl drop-shadow-md transform group-hover:scale-125 transition-transform">
                                {{ cat.icon }}
                            </span>
                        </div>

                        <!-- Category Meta -->
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-rose-600 transition truncate w-full">
                            {{ cat.name }}
                        </h3>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-[10px] font-semibold text-slate-400">
                                {{ cat.products_count ?? 12 }}+ Products
                            </span>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- 4. DUAL LUXURY EDITORIAL PROMO BANNERS -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Promo Card 1: Skincare Actives -->
                    <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-rose-950 to-slate-900 text-white p-8 sm:p-10 shadow-xl border border-rose-900/30 flex flex-col justify-between min-h-[280px]">
                        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-rose-500/20 rounded-full blur-3xl pointer-events-none group-hover:bg-rose-500/30 transition-all duration-700"></div>
                        <div class="relative z-10 space-y-3 max-w-md">
                            <div class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 px-3 py-1 text-[10px] font-black uppercase tracking-wider">
                                <span>🧪 CLINICAL SKIN SCIENCE</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-serif font-black tracking-tight text-white leading-tight">
                                Serums, Retinols & Hydration Heroes
                            </h3>
                            <p class="text-xs sm:text-sm text-rose-100/80 leading-relaxed">
                                Formulated to transform texture, target hyperpigmentation, and restore radiant skin barrier strength.
                            </p>
                        </div>

                        <div class="relative z-10 pt-6 mt-6 border-t border-white/10 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] uppercase font-bold text-slate-400">Use Code:</span>
                                <span class="px-2.5 py-1 rounded-lg bg-white/10 border border-white/20 text-xs font-mono font-black text-rose-300 tracking-wider">
                                    LUXEGLOW10
                                </span>
                            </div>
                            <Link
                                :href="route('products.index', { category: 'skincare' })"
                                class="inline-flex items-center gap-2 rounded-full bg-rose-600 hover:bg-rose-500 text-white px-5 py-2 text-xs font-bold uppercase tracking-wider shadow-lg shadow-rose-950/50 transition-all hover:scale-105"
                            >
                                <span>Shop Skincare</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Promo Card 2: Luxury Haute Parfumerie -->
                    <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-950 via-slate-900 to-rose-950 text-white p-8 sm:p-10 shadow-xl border border-amber-900/30 flex flex-col justify-between min-h-[280px]">
                        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-amber-500/20 rounded-full blur-3xl pointer-events-none group-hover:bg-amber-500/30 transition-all duration-700"></div>
                        <div class="relative z-10 space-y-3 max-w-md">
                            <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 px-3 py-1 text-[10px] font-black uppercase tracking-wider">
                                <span>🌸 HAUTE PARFUMERIE</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-serif font-black tracking-tight text-white leading-tight">
                                Signature Niche & French Perfumes
                            </h3>
                            <p class="text-xs sm:text-sm text-amber-100/80 leading-relaxed">
                                Intoxicating oriental ouds, floral bouquets, and long-lasting luxury sillage from top global perfumers.
                            </p>
                        </div>

                        <div class="relative z-10 pt-6 mt-6 border-t border-white/10 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] uppercase font-bold text-slate-400">Offer:</span>
                                <span class="text-xs font-bold text-amber-300">
                                    Complimentary 5ml Discovery Vial
                                </span>
                            </div>
                            <Link
                                :href="route('products.index', { category: 'fragrances-perfumes' })"
                                class="inline-flex items-center gap-2 rounded-full bg-amber-600 hover:bg-amber-500 text-white px-5 py-2 text-xs font-bold uppercase tracking-wider shadow-lg shadow-amber-950/50 transition-all hover:scale-105"
                            >
                                <span>Shop Fragrance</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. INTERACTIVE PRODUCT SHOWCASE (Trending, Bestsellers, New Arrivals) -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-pink-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-pink-700 border border-pink-200 mb-2">
                            <span>🔥 VERIFIED CATALOG</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-black tracking-tight text-slate-900">
                            Trending & Bestselling Essentials
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            100% original inventory with guaranteed batch authenticity and express dispatch.
                        </p>
                    </div>

                    <!-- Interactive Tab Switcher -->
                    <div class="inline-flex items-center gap-1 rounded-full bg-slate-100 p-1 border border-slate-200 text-xs font-bold shadow-2xs">
                        <button
                            @click="activeTab = 'trending'"
                            class="rounded-full px-4 py-2 transition-all cursor-pointer select-none"
                            :class="activeTab === 'trending' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            🔥 Trending Drops
                        </button>
                        <button
                            @click="activeTab = 'featured'"
                            class="rounded-full px-4 py-2 transition-all cursor-pointer select-none"
                            :class="activeTab === 'featured' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            ⭐ Bestsellers
                        </button>
                        <button
                            @click="activeTab = 'new'"
                            class="rounded-full px-4 py-2 transition-all cursor-pointer select-none"
                            :class="activeTab === 'new' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        >
                            ✨ Fresh Arrivals
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div
                        v-for="product in currentProducts"
                        :key="product.id"
                        class="group relative flex flex-col justify-between rounded-3xl bg-white p-4 shadow-sm border border-slate-100 hover:shadow-2xl hover:border-rose-300 hover:-translate-y-1 transition-all duration-300"
                    >
                        <div>
                            <!-- Product Image & Overlay Badges -->
                            <div class="relative aspect-square w-full overflow-hidden rounded-2xl bg-slate-50 mb-3.5">
                                <Link :href="route('products.show', product.slug)" class="block h-full w-full">
                                    <img
                                        :src="product.image_url"
                                        :alt="product.name"
                                        class="h-full w-full object-cover object-center group-hover:scale-108 transition-transform duration-500"
                                    />
                                </Link>

                                <!-- Top Badges -->
                                <div class="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10">
                                    <span v-if="product.badge" class="rounded-lg bg-rose-600 text-white text-[9px] font-black uppercase px-2 py-0.5 shadow-sm">
                                        {{ product.badge }}
                                    </span>
                                    <span v-if="product.discount_percentage > 0" class="rounded-lg bg-amber-500 text-white text-[9px] font-black px-2 py-0.5 shadow-sm">
                                        -{{ product.discount_percentage }}% OFF
                                    </span>
                                </div>

                                <!-- Brand Tag Floating -->
                                <div class="absolute top-2.5 right-2.5 z-10">
                                    <span class="rounded-lg bg-white/90 backdrop-blur-md text-slate-800 text-[9px] font-extrabold px-2 py-0.5 shadow-xs border border-slate-200">
                                        {{ product.brand || 'Luxe Care' }}
                                    </span>
                                </div>

                                <!-- Quick Add Overlay Action -->
                                <button
                                    @click="addToCart(product)"
                                    :disabled="addingProductId === product.id"
                                    class="absolute bottom-2.5 inset-x-2.5 py-2.5 rounded-xl bg-slate-900/95 hover:bg-rose-600 text-white text-xs font-bold tracking-wide shadow-lg opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-200 cursor-pointer flex items-center justify-center gap-1.5 z-20"
                                >
                                    <span v-if="addingProductId === product.id" class="animate-spin text-sm">⏳</span>
                                    <span v-else>🛍️</span>
                                    <span>{{ addingProductId === product.id ? 'Adding...' : 'Quick Add to Bag' }}</span>
                                </button>
                            </div>

                            <!-- Product Meta -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-rose-600 font-bold uppercase tracking-wider text-[10px]">
                                        {{ product.categoryRelation?.name || 'Cosmetics' }}
                                    </span>
                                    <div class="flex items-center gap-1 text-amber-500 font-bold">
                                        <span>★</span>
                                        <span>{{ product.rating || '4.9' }}</span>
                                        <span class="text-slate-400 font-normal">({{ product.reviews_count || 38 }})</span>
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
                        <div class="pt-3.5 mt-3.5 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-sm sm:text-base font-black text-slate-900">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                    <span v-if="product.original_price && product.original_price > product.price" class="text-[11px] text-slate-400 line-through">
                                        {{ formatPrice(product.original_price) }}
                                    </span>
                                </div>
                                <span class="text-[9px] text-emerald-600 font-semibold block">✓ In Stock & Ready to Ship</span>
                            </div>

                            <button
                                @click="addToCart(product)"
                                :disabled="addingProductId === product.id"
                                class="h-9 w-9 rounded-full bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 flex items-center justify-center text-sm transition-all shadow-2xs hover:scale-108 active:scale-95 cursor-pointer"
                                title="Add to Bag"
                            >
                                <span v-if="addingProductId === product.id" class="animate-spin text-xs">⏳</span>
                                <span v-else>＋</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Explore Catalog CTA -->
                <div class="text-center mt-12">
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center gap-2.5 rounded-full border-2 border-slate-900 hover:bg-slate-900 hover:text-white px-8 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-900 shadow-sm transition-all hover:scale-102"
                    >
                        <span>Explore Full Catalog ({{ stats.total_products || 25 }}+ Products)</span>
                        <span>&rarr;</span>
                    </Link>
                </div>
            </section>

            <!-- 6. WORLD-RENOWNED BRAND SPOTLIGHT -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-gradient-to-r from-slate-950 via-rose-950 to-slate-900 text-white p-8 sm:p-12 shadow-2xl relative overflow-hidden border border-rose-900/30">
                    <div class="relative z-10 max-w-xl space-y-3">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-0.5 text-[10px] font-bold tracking-widest uppercase text-rose-200 border border-white/15">
                            <span>💎 100% ORIGINAL SOURCED</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-serif font-black tracking-tight text-white">
                            World's Most Coveted Brands
                        </h2>
                        <p class="text-xs sm:text-sm text-rose-100/80 leading-relaxed">
                            From clinical skincare powerhouses to ultra-luxurious niche perfumes, discover genuine products with verified batch codes.
                        </p>
                    </div>

                    <!-- Brand Badges Grid -->
                    <div class="relative z-10 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mt-8">
                        <Link
                            v-for="brand in (brands.length ? brands : ['The Ordinary', 'CeraVe', 'Fenty Beauty', 'Olaplex', 'COSRX', 'Maybelline', 'L\'Oréal Paris', 'Kayali', 'Maison Francis Kurkdjian', 'Sol de Janeiro'])"
                            :key="brand"
                            :href="route('products.index', { brand: brand })"
                            class="p-4 rounded-2xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-center font-bold text-xs text-white transition-all hover:scale-105 hover:border-rose-400/50 shadow-sm"
                        >
                            {{ brand }}
                        </Link>
                    </div>
                </div>
            </section>

            <!-- 7. VERIFIED CUSTOMER REVIEWS WITH PRODUCTS -->
            <section v-if="reviews && reviews.length" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-xl mx-auto mb-10 space-y-2">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-800 border border-emerald-200">
                        <span>★ VERIFIED BUYER EXPERIENCES</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                        Loved by 25,000+ Verified Buyers
                    </h2>
                    <p class="text-xs text-slate-500">
                        Real transformations and honest feedback from beauty lovers across Pakistan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="rev in reviews"
                        :key="rev.id"
                        class="rounded-3xl bg-white p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:border-pink-200 transition-all flex flex-col justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center gap-1 text-amber-400 text-sm">
                                <span v-for="s in (rev.rating || 5)" :key="s">★</span>
                            </div>
                            <h4 v-if="rev.title" class="font-bold text-slate-900 text-xs sm:text-sm">
                                "{{ rev.title }}"
                            </h4>
                            <p class="text-xs text-slate-600 leading-relaxed italic">
                                "{{ rev.comment }}"
                            </p>
                        </div>

                        <!-- Review Footer with User & Purchased Product Reference -->
                        <div class="pt-4 mt-4 border-t border-slate-100 space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-rose-600 to-pink-500 text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                                        {{ rev.author_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 text-xs">{{ rev.author_name }}</p>
                                        <span class="text-[10px] text-emerald-600 font-semibold flex items-center gap-0.5">
                                            <span>✓</span>
                                            <span>Verified Buyer</span>
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400 font-medium">{{ rev.time_ago || 'Recently' }}</span>
                            </div>

                            <div v-if="rev.product_name" class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-600">
                                <span class="text-rose-500 font-bold">Purchased:</span>
                                <span class="truncate font-medium text-slate-800">{{ rev.product_name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 8. BEAUTY MAGAZINE & GLOW GUIDES -->
            <section v-if="latestBlogs && latestBlogs.length" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-purple-700 border border-purple-200 mb-2">
                            <span>📖 BEAUTY MAGAZINE</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                            Expert Guides & Glow Routines
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Dermatology insights, makeup masterclasses, and perfume layering techniques.
                        </p>
                    </div>
                    <Link :href="route('blogs.index')" class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 bg-rose-50 hover:bg-rose-100 px-4 py-2 rounded-full border border-rose-200/80 transition-all shrink-0">
                        <span>Read All Articles</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        v-for="blog in latestBlogs"
                        :key="blog.id"
                        :href="route('blogs.show', blog.slug)"
                        class="group overflow-hidden rounded-3xl bg-white shadow-sm border border-slate-100 hover:shadow-xl hover:border-rose-300 hover:-translate-y-1 transition-all duration-300 flex flex-col"
                    >
                        <div class="aspect-video w-full overflow-hidden bg-slate-100 relative">
                            <img
                                :src="blog.image || 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80'"
                                :alt="blog.title"
                                class="h-full w-full object-cover group-hover:scale-108 transition-transform duration-500"
                            />
                            <span class="absolute top-3 left-3 rounded-lg bg-slate-900/80 backdrop-blur-md text-white text-[9px] font-black uppercase px-2.5 py-1">
                                {{ blog.category || 'Skincare Edit' }}
                            </span>
                        </div>
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                            <div class="space-y-2">
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base group-hover:text-rose-600 transition leading-snug line-clamp-2">
                                    {{ blog.title }}
                                </h3>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                    {{ blog.summary || 'Expert beauty insights, clinical routine breakdowns, and authentic ingredient analyses.' }}
                                </p>
                            </div>
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-rose-600">
                                <span>Read Full Story</span>
                                <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- 9. VIP BEAUTY CLUB & NEWSLETTER BANNER -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-8">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-rose-950 via-pink-900 to-slate-950 text-white p-8 sm:p-12 shadow-2xl border border-rose-800/40">
                    <div class="relative z-10 max-w-2xl space-y-3">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3.5 py-1 text-[10px] font-black uppercase tracking-widest text-pink-200 border border-white/20">
                            <span>👑 THE LUXE BEAUTY CLUB</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-serif font-black tracking-tight text-white">
                            Unlock PKR 500 Off Your First Order
                        </h2>
                        <p class="text-xs sm:text-sm text-pink-100/90 leading-relaxed">
                            Join over 25,000 beauty connoisseurs. Receive secret flash sales, early access to new launches, and curated skincare advice.
                        </p>

                        <!-- Form -->
                        <form @submit.prevent="handleNewsletter" class="flex flex-col sm:flex-row gap-2.5 pt-4 max-w-lg">
                            <input
                                v-model="newsletterEmail"
                                type="email"
                                placeholder="Enter your email address..."
                                required
                                class="flex-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 px-5 py-3 text-xs sm:text-sm text-white placeholder-pink-200/60 focus:bg-white/20 focus:border-rose-400 focus:ring-0"
                            />
                            <button
                                type="submit"
                                class="rounded-full bg-gradient-to-r from-rose-600 via-pink-600 to-amber-500 hover:from-rose-500 hover:to-pink-500 text-white font-bold text-xs uppercase tracking-wider px-7 py-3 shadow-lg hover:scale-105 transition-all cursor-pointer shrink-0"
                            >
                                {{ newsletterSubscribed ? 'Subscribed!' : 'Claim PKR 500' }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

