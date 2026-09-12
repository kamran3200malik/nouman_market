<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const formatPrice = (price) => {
    if (price === null || price === undefined || isNaN(price)) return 'PKR 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(price || 0);
};

// Search State
const searchTerm = ref('');
const selectedCategory = ref('all');

// 1. Portion 1: Admin Hero Banner Carousel
const defaultHeroSlides = [
    {
        id: 'default-1',
        title: 'Luxury Skincare & Dermal Radiance Elixirs',
        subtitle: 'Discover 100% authentic international serums, hydrating creams, and clinical glow formulas.',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1600&q=80',
        price: 4500,
        tag: '🌸 Featured Haute Beauté',
        link: null,
    },
    {
        id: 'default-2',
        title: 'Signature Hair Care, Argan Oils & Keratin Therapies',
        subtitle: 'Transform your hair with pure Moroccan elixirs, restorative hair masks, and clinical scalp care.',
        image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1600&q=80',
        price: 3800,
        tag: '💎 Top Rated Hair Care',
        link: null,
    },
    {
        id: 'default-3',
        title: 'Haute Couture Makeup, Palettes & Velvet Lip Clays',
        subtitle: 'High-pigment international beauty essentials delivered with 100% genuine product guarantee.',
        image: 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=1600&q=80',
        price: 5200,
        tag: '✨ Exclusive Glam',
        link: null,
    },
];

const heroSlides = computed(() => {
    if (props.banners && props.banners.length > 0) {
        const mainBanners = props.banners.filter(b => (b.position === 'hero' || !b.position) && b.is_active !== false);
        if (mainBanners.length > 0) {
            return mainBanners.map(b => ({
                id: b.id,
                title: b.title,
                subtitle: b.description || b.subtitle,
                image: storageUrl(b.image || b.image_url, defaultHeroSlides[0].image),
                price: b.price ? Number(b.price) : null,
                tag: b.tag || (b.title ? `🌸 ${b.title}` : null),
                link: b.target_url || b.link || null,
                button_text: b.button_text || 'Shop Now',
            }));
        }
    }
    return defaultHeroSlides;
});

const currentSlide = ref(0);
const isCarouselPaused = ref(false);
let carouselTimer = null;

const touchStartX = ref(0);
const touchEndX = ref(0);

const handleTouchStart = (e) => {
    isCarouselPaused.value = true;
    if (e.changedTouches && e.changedTouches.length > 0) {
        touchStartX.value = e.changedTouches[0].screenX;
    }
};

const handleTouchEnd = (e) => {
    isCarouselPaused.value = false;
    if (e.changedTouches && e.changedTouches.length > 0) {
        touchEndX.value = e.changedTouches[0].screenX;
        const diff = touchEndX.value - touchStartX.value;
        if (Math.abs(diff) > 40) {
            if (diff < 0) {
                nextSlide();
            } else {
                prevSlide();
            }
        }
    }
};

const activeHeroSlide = computed(() => {
    const idx = currentSlide.value % (heroSlides.value.length || 1);
    return heroSlides.value[idx] || heroSlides.value[0] || defaultHeroSlides[0];
});

const nextSlide = () => {
    if (heroSlides.value.length > 1) {
        currentSlide.value = (currentSlide.value + 1) % heroSlides.value.length;
    }
};

const prevSlide = () => {
    if (heroSlides.value.length > 1) {
        currentSlide.value = (currentSlide.value - 1 + heroSlides.value.length) % heroSlides.value.length;
    }
};

const goToSlide = (index) => {
    currentSlide.value = index;
};

const resolveLink = (link) => {
    if (!link) return route('products.index');
    if (link.startsWith('http://') || link.startsWith('https://') || link.startsWith('/')) return link;
    return route('products.index');
};

// 2. Portion 2: Right Spotlight Products
const fallbackProducts = [
    {
        id: 1,
        name: 'Organic Moroccan Argan Hair Serum',
        slug: 'organic-argan-hair-oil',
        brand: 'Luxe Organics',
        description: 'Deep nourishing elixir for frizzy hair, heat protection & high gloss shine.',
        price: 3450,
        original_price: 4200,
        image: 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=600&q=80',
        rating: 4.9,
        reviewsCount: 124,
        is_trending: true,
        target_url: '/products',
    },
    {
        id: 2,
        name: 'Gold Radiance Vitamin C Brightening Serum',
        slug: 'gold-radiance-serum',
        brand: 'Glow Botanics',
        description: 'Antioxidant booster for instant radiant glow and dark spot correction.',
        price: 2890,
        original_price: 3500,
        image: 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=600&q=80',
        rating: 4.8,
        reviewsCount: 98,
        is_trending: true,
        target_url: '/products',
    },
];

const displayProducts = computed(() => {
    if (props.products && props.products.length > 0) {
        return props.products.slice(0, 6).map(p => ({
            id: p.id,
            name: p.name,
            slug: p.slug,
            brand: p.brand || 'Luxe Beauty',
            description: p.description || p.short_description,
            price: Number(p.price),
            original_price: p.original_price ? Number(p.original_price) : null,
            image: storageUrl(p.image || p.image_url, fallbackProducts[0].image),
            rating: p.rating || 4.9,
            reviewsCount: p.reviews_count || 85,
            is_trending: Boolean(p.is_trending ?? true),
            target_url: route('products.show', p.slug || p.id),
        }));
    }
    return fallbackProducts;
});

const approvedSlidingProducts = computed(() => {
    let list = [];
    if (props.products && props.products.length > 0) {
        list = props.products.filter(p => p.is_active !== false).map(p => ({
            id: p.id,
            name: p.name,
            slug: p.slug,
            brand: p.brand || 'Luxe Beauty',
            price: Number(p.price),
            original_price: p.original_price ? Number(p.original_price) : null,
            image: storageUrl(p.image || p.image_url, fallbackProducts[0].image),
            rating: Number(p.rating || 4.9),
            reviews_count: p.reviews_count || 45,
            badge: p.badge || (p.is_trending ? '🔥 Trending' : '✨ 100% Genuine'),
            discount_percentage: p.original_price && p.original_price > p.price
                ? Math.round(((p.original_price - p.price) / p.original_price) * 100)
                : 0,
            target_url: route('products.show', p.slug || p.id),
        }));
    }

    if (!list || list.length === 0) {
        list = fallbackProducts.map(p => ({
            ...p,
            reviews_count: p.reviewsCount || 50,
            badge: '✨ Genuine',
            discount_percentage: p.original_price && p.original_price > p.price
                ? Math.round(((p.original_price - p.price) / p.original_price) * 100)
                : 0,
            target_url: route('products.index'),
        }));
    }

    return list;
});

const shouldAnimateMarquee = computed(() => approvedSlidingProducts.value.length >= 4);

const displaySlidingProducts = computed(() => {
    const list = approvedSlidingProducts.value;
    if (list.length >= 4) {
        return [...list, ...list];
    }
    return list;
});

const addedToBag = ref({});
const toastMsg = ref('');
let toastTimer = null;

const addProductToBag = (product, e) => {
    if (e) e.stopPropagation();
    try {
        const savedCart = localStorage.getItem('beautybook_cart');
        const cart = savedCart ? JSON.parse(savedCart) : [];
        const existingIdx = cart.findIndex(item => item.id === product.id);
        if (existingIdx > -1) {
            cart[existingIdx].quantity += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                original_price: product.original_price,
                image: product.image,
                brand: product.brand,
                quantity: 1,
                in_stock: true,
            });
        }
        localStorage.setItem('beautybook_cart', JSON.stringify(cart));
        window.dispatchEvent(new Event('cart-updated'));

        addedToBag.value[product.id] = true;
        toastMsg.value = `🛍️ Added "${product.name}" to your bag!`;
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            addedToBag.value[product.id] = false;
            toastMsg.value = '';
        }, 3000);
    } catch (err) {
        console.error(err);
    }
};

const currentProductIndex = ref(0);
const secondaryProductIndex = ref(1);
let productTimer = null;

const activeProduct = computed(() => {
    const list = displayProducts.value;
    return list[currentProductIndex.value % list.length] || list[0] || fallbackProducts[0];
});

const activeSecondaryProduct = computed(() => {
    const list = displayProducts.value;
    const idx = list.length > 1 ? (secondaryProductIndex.value % list.length) : 0;
    return list[idx] || list[0] || fallbackProducts[1];
});

const rightTopBanner = computed(() => {
    if (props.banners && props.banners.length > 0) {
        const b = props.banners.find(item => item.position === 'right-top' && item.is_active !== false);
        if (b) {
            return {
                id: b.id,
                title: b.title,
                description: b.description || b.subtitle,
                image: storageUrl(b.image || b.image_url, defaultHeroSlides[0].image),
                link: b.target_url || b.link || null,
                button_text: b.button_text || 'Shop',
                price: b.price ? Number(b.price) : null,
            };
        }
    }
    return null;
});

const rightBottomBanner = computed(() => {
    if (props.banners && props.banners.length > 0) {
        const b = props.banners.find(item => item.position === 'right-bottom' && item.is_active !== false);
        if (b) {
            return {
                id: b.id,
                title: b.title,
                description: b.description || b.subtitle,
                image: storageUrl(b.image || b.image_url, defaultHeroSlides[1].image),
                link: b.target_url || b.link || null,
                button_text: b.button_text || 'Shop',
                price: b.price ? Number(b.price) : null,
            };
        }
    }
    return null;
});

function executeSearch() {
    const queryParams = {};
    if (searchTerm.value.trim()) queryParams.search = searchTerm.value.trim();
    if (selectedCategory.value && selectedCategory.value !== 'all') queryParams.category = selectedCategory.value;
    router.get(route('products.index'), queryParams);
}

function quickSearch(tag) {
    if (tag.query) searchTerm.value = tag.query;
    executeSearch();
}

const trendingTags = [
    { label: '✨ Vitamin C Serum', query: 'Serum' },
    { label: '💄 Matte Lipstick', query: 'Lipstick' },
    { label: '🧴 Hydra Moisturizer', query: 'Moisturizer' },
    { label: '🌿 Argan Hair Oil', query: 'Argan' },
    { label: '🌸 Luxury Perfume', query: 'Perfume' },
];

onMounted(() => {
    carouselTimer = setInterval(() => {
        if (!isCarouselPaused.value) {
            nextSlide();
        }
    }, 5500);
    productTimer = setInterval(() => {
        currentProductIndex.value = (currentProductIndex.value + 1) % (displayProducts.value.length || 1);
        secondaryProductIndex.value = (secondaryProductIndex.value + 1) % (displayProducts.value.length || 1);
    }, 6500);
});

onBeforeUnmount(() => {
    if (carouselTimer) clearInterval(carouselTimer);
    if (productTimer) clearInterval(productTimer);
});
</script>

<template>
    <!-- 1. ULTRA-LUXURY EDITORIAL & RESPONSIVE HERO SECTION -->
    <section class="relative mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 pt-1 sm:pt-3">
        <!-- Atmospheric Ambient Glow Blobs Behind Hero -->
        <div class="pointer-events-none absolute -top-12 left-1/4 h-80 w-80 rounded-full bg-rose-500/15 blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/3 right-12 h-72 w-72 rounded-full bg-purple-500/15 blur-3xl"></div>

        <div class="relative grid gap-3.5 sm:gap-4 lg:grid-cols-12 lg:items-stretch">
            <!-- LEFT COLUMN: MAIN EDITORIAL BILLBOARD -->
            <div class="lg:col-span-7 xl:col-span-8 flex flex-col">
                <!-- PORTION 1: ULTRA-LUXURY EDITORIAL BILLBOARD -->
                <div
                    class="relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl border border-pink-500/20 bg-[#0c0612] h-full min-h-[20rem] sm:min-h-[24rem] lg:min-h-[33.5rem] flex flex-col justify-between group/hero select-none"
                    @mouseenter="isCarouselPaused = true"
                    @mouseleave="isCarouselPaused = false"
                    @touchstart="handleTouchStart"
                    @touchend="handleTouchEnd"
                >
                    <!-- Background Carousel with Crystal Clear Image & Directional Shading -->
                    <div class="absolute inset-0 overflow-hidden pointer-events-none rounded-2xl sm:rounded-3xl">
                        <transition-group
                            tag="div"
                            class="relative h-full w-full"
                            enter-active-class="transition-all duration-1000 ease-out"
                            enter-from-class="opacity-0 scale-[1.03]"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition-all duration-1000 ease-in absolute inset-0"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-[0.98]"
                        >
                            <div
                                v-for="(slide, index) in heroSlides"
                                :key="slide.id"
                                v-show="currentSlide === index"
                                class="absolute inset-0 h-full w-full pointer-events-auto"
                            >
                                <Link
                                    :href="resolveLink(slide.link)"
                                    class="block h-full w-full relative"
                                >
                                    <img
                                        :src="slide.image"
                                        :alt="slide.title || 'Marketplace Featured'"
                                        class="h-full w-full object-cover object-center sm:object-right transform transition-transform duration-10000 ease-out scale-100 group-hover/hero:scale-105"
                                        loading="eager"
                                    />
                                    <!-- Soft Top & Bottom Edge Vignette for Text Contrast -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-black/40 pointer-events-none"></div>
                                </Link>
                            </div>
                        </transition-group>
                    </div>

                    <!-- Editorial Layer (Top Bar + Bottom Title & Action Bar) -->
                    <div class="relative z-10 p-4 sm:p-6 lg:p-7 flex flex-col justify-between h-full space-y-4 pointer-events-none">
                        <!-- Top Row: Dynamic Tag & Slide Indicators -->
                        <div class="flex items-center justify-between gap-2 flex-wrap pointer-events-auto">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div v-if="activeHeroSlide.tag" class="inline-flex items-center gap-1.5 rounded-full bg-black/60 backdrop-blur-xl px-3.5 py-1 text-[11px] sm:text-xs font-bold uppercase tracking-wider text-pink-200 border border-pink-500/30 shadow-md">
                                    <span class="text-rose-400">✨</span>
                                    <span>{{ activeHeroSlide.tag }}</span>
                                </div>
                            </div>

                            <!-- Slide Dots Counter -->
                            <div class="flex items-center gap-2 bg-black/50 backdrop-blur-xl px-3 py-1 rounded-full border border-white/20 shadow-sm">
                                <span class="text-[11px] font-mono font-bold text-pink-200">
                                    0{{ (currentSlide % (heroSlides.length || 1)) + 1 }} / 0{{ heroSlides.length || 1 }}
                                </span>
                                <div class="flex items-center gap-1 pl-1 border-l border-white/20">
                                    <button
                                        v-for="(slide, idx) in heroSlides"
                                        :key="slide.id"
                                        @click.stop="goToSlide(idx)"
                                        class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
                                        :class="currentSlide === idx ? 'w-5 bg-gradient-to-r from-rose-400 to-pink-500 shadow-sm shadow-rose-500/50' : 'w-1.5 bg-white/40 hover:bg-white/80'"
                                        :aria-label="`Slide ${idx + 1}`"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Action & Title Strip -->
                        <div class="flex items-end sm:items-center justify-between gap-4 pt-4 pb-2 px-1 border-t border-white/15 pointer-events-auto backdrop-blur-xs">
                            <div class="space-y-1 max-w-xl text-left">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h2 v-if="activeHeroSlide.title" class="font-serif text-base sm:text-xl lg:text-2xl font-bold text-white leading-tight drop-shadow-md">
                                        {{ activeHeroSlide.title }}
                                    </h2>
                                    <span v-if="activeHeroSlide.price" class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-gradient-to-r from-rose-500 to-pink-600 text-white font-serif text-xs sm:text-sm font-extrabold shadow-sm border border-white/20">
                                        {{ formatPrice(activeHeroSlide.price) }}
                                    </span>
                                </div>
                                <p v-if="activeHeroSlide.subtitle" class="text-xs sm:text-sm text-pink-100/90 line-clamp-2 leading-relaxed font-normal drop-shadow-xs">
                                    {{ activeHeroSlide.subtitle }}
                                </p>
                            </div>
                            <Link
                                :href="resolveLink(activeHeroSlide.link)"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-lg shadow-pink-900/30 hover:shadow-rose-600/40 transition-all duration-200 hover:scale-105 cursor-pointer shrink-0 pointer-events-auto"
                            >
                                <span>{{ activeHeroSlide.button_text || 'Shop Now' }}</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Previous/Next Glass Arrows -->
                    <button
                        type="button"
                        @click.stop="prevSlide"
                        class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-20 flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/80 text-white backdrop-blur-xl border border-white/20 transition duration-200 cursor-pointer shadow-lg opacity-80 sm:opacity-0 group-hover/hero:opacity-100 hover:scale-105"
                        aria-label="Previous Slide"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click.stop="nextSlide"
                        class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-20 flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-full bg-black/40 hover:bg-black/80 text-white backdrop-blur-xl border border-white/20 transition duration-200 cursor-pointer shadow-lg opacity-80 sm:opacity-0 group-hover/hero:opacity-100 hover:scale-105"
                        aria-label="Next Slide"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- RIGHT COLUMN: DUAL DYNAMIC LUXURY SPOTLIGHT CARDS -->
            <div class="lg:col-span-5 xl:col-span-4 grid sm:grid-cols-2 lg:grid-cols-1 gap-3.5 sm:gap-4">
                <!-- PORTION 2: TOP SPOTLIGHT (RIGHT-TOP BANNER OR DYNAMIC PRODUCT) -->
                <div class="relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-xl border border-pink-500/20 bg-[#0c0612] min-h-[14rem] sm:min-h-[15.5rem] lg:min-h-[16rem] xl:min-h-[16.2rem] flex flex-col justify-between group select-none">
                    <Link
                        :href="resolveLink(rightTopBanner?.link || activeProduct?.target_url)"
                        class="absolute inset-0 overflow-hidden cursor-pointer block"
                    >
                        <img
                            :src="rightTopBanner?.image || activeProduct.image"
                            :alt="rightTopBanner?.title || activeProduct.name"
                            class="h-full w-full object-cover object-center transform transition-transform duration-7000 ease-out group-hover:scale-108"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-black/50 pointer-events-none"></div>
                    </Link>

                    <!-- Top Row: Tag & Price -->
                    <div class="relative z-10 p-3.5 sm:p-4 flex items-center justify-between gap-2 pointer-events-auto">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 rounded-full bg-black/60 backdrop-blur-xl px-2.5 py-0.5 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-pink-200 border border-pink-500/30 shadow-md">
                                <span class="text-rose-400">✨</span>
                                <span>{{ rightTopBanner ? 'Special Deal' : (activeProduct.is_trending ? '🔥 Trending' : 'Top Pick') }}</span>
                            </span>
                            <span v-if="!rightTopBanner && activeProduct.rating" class="inline-flex items-center gap-0.5 rounded-full bg-black/60 backdrop-blur-xl px-2 py-0.5 text-[10px] font-bold text-amber-300 border border-white/20 shadow-xs">
                                <span>★</span>
                                <span>{{ activeProduct.rating }}</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span v-if="rightTopBanner?.price || activeProduct?.price" class="inline-flex items-center px-2 py-0.5 rounded-lg bg-gradient-to-r from-rose-500 to-pink-600 text-white font-serif text-xs sm:text-sm font-extrabold shadow-sm border border-white/20">
                                {{ formatPrice(rightTopBanner?.price || activeProduct?.price) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Action Strip -->
                    <div class="relative z-10 p-3.5 sm:p-4 pt-2 border-t border-white/15 backdrop-blur-xs flex items-end justify-between gap-3 pointer-events-auto">
                        <div class="space-y-0.5 max-w-[72%] text-left">
                            <h4 class="font-serif text-sm sm:text-base font-bold text-white leading-tight drop-shadow-md line-clamp-1">
                                {{ rightTopBanner?.title || activeProduct.name }}
                            </h4>
                            <p class="text-[10px] sm:text-[11px] text-pink-100/90 line-clamp-1 leading-normal font-normal drop-shadow-xs">
                                {{ rightTopBanner?.description || activeProduct.description }}
                            </p>
                        </div>
                        <Link
                            :href="resolveLink(rightTopBanner?.link || activeProduct?.target_url)"
                            class="inline-flex items-center gap-1 rounded-xl bg-white hover:bg-rose-50 text-slate-900 hover:text-rose-700 px-3 py-1.5 text-xs font-bold shadow-md hover:scale-102 transition-all shrink-0 cursor-pointer"
                        >
                            <span>{{ rightTopBanner?.button_text || 'Shop' }}</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>
                </div>

                <!-- PORTION 3: BOTTOM SPOTLIGHT (RIGHT-BOTTOM BANNER OR SECOND DYNAMIC PRODUCT) -->
                <div class="relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-xl border border-pink-500/20 bg-[#0c0612] min-h-[14rem] sm:min-h-[15.5rem] lg:min-h-[16rem] xl:min-h-[16.2rem] flex flex-col justify-between group select-none">
                    <Link
                        :href="resolveLink(rightBottomBanner?.link || activeSecondaryProduct?.target_url)"
                        class="absolute inset-0 overflow-hidden cursor-pointer block"
                    >
                        <img
                            :src="rightBottomBanner?.image || activeSecondaryProduct.image"
                            :alt="rightBottomBanner?.title || activeSecondaryProduct.name"
                            class="h-full w-full object-cover object-center transform transition-transform duration-7000 ease-out group-hover:scale-108"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-black/50 pointer-events-none"></div>
                    </Link>

                    <!-- Top Row: Tag & Price -->
                    <div class="relative z-10 p-3.5 sm:p-4 flex items-center justify-between gap-2 pointer-events-auto">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 rounded-full bg-black/60 backdrop-blur-xl px-2.5 py-0.5 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-pink-200 border border-pink-500/30 shadow-md">
                                <span class="text-rose-400">💎</span>
                                <span>{{ rightBottomBanner ? 'Best Seller' : 'Exclusive Offer' }}</span>
                            </span>
                            <span v-if="!rightBottomBanner && activeSecondaryProduct.brand" class="inline-flex items-center gap-1 rounded-full bg-black/60 backdrop-blur-xl px-2 py-0.5 text-[10px] font-bold text-pink-200 border border-white/20 shadow-xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="truncate max-w-[100px]">{{ activeSecondaryProduct.brand }}</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span v-if="rightBottomBanner?.price || activeSecondaryProduct?.price" class="inline-flex items-center px-2 py-0.5 rounded-lg bg-gradient-to-r from-rose-500 to-pink-600 text-white font-serif text-xs sm:text-sm font-extrabold shadow-sm border border-white/20">
                                {{ formatPrice(rightBottomBanner?.price || activeSecondaryProduct?.price) }}
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Action Strip -->
                    <div class="relative z-10 p-3.5 sm:p-4 pt-2 border-t border-white/15 backdrop-blur-xs flex items-end justify-between gap-3 pointer-events-auto">
                        <div class="space-y-0.5 max-w-[72%] text-left">
                            <h4 class="font-serif text-sm sm:text-base font-bold text-white leading-tight drop-shadow-md line-clamp-1">
                                {{ rightBottomBanner?.title || activeSecondaryProduct.name }}
                            </h4>
                            <p class="text-[10px] sm:text-[11px] text-pink-100/90 line-clamp-1 leading-normal font-normal drop-shadow-xs">
                                {{ rightBottomBanner?.description || activeSecondaryProduct.description }}
                            </p>
                        </div>
                        <Link
                            :href="resolveLink(rightBottomBanner?.link || activeSecondaryProduct?.target_url)"
                            class="inline-flex items-center gap-1 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white px-3.5 py-1.5 text-xs font-bold shadow-md hover:scale-102 transition-all shrink-0 cursor-pointer"
                        >
                            <span>{{ rightBottomBanner?.button_text || 'Shop' }}</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-2.5 sm:mt-4 rounded-xl sm:rounded-3xl p-1.5 sm:p-4 bg-white/90 backdrop-blur-xl border border-rose-100/80 shadow-xs overflow-hidden relative group/strip">
            <div class="flex items-center justify-between gap-2 mb-1.5 sm:mb-2 px-1">
                <div class="flex items-center gap-1 sm:gap-2">
                    <span class="flex h-4 w-4 sm:h-6 sm:w-6 items-center justify-center rounded-md sm:rounded-lg bg-rose-500/10 text-rose-600 text-[9px] sm:text-xs font-black">
                        🛍️
                    </span>
                    <h3 class="text-[10px] sm:text-sm font-extrabold text-slate-900 tracking-tight flex items-center gap-1 sm:gap-1.5">
                        <span>Boutique-Best Products</span>
                        <span class="inline-flex items-center gap-0.5 sm:gap-1 text-[7px] sm:text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1 sm:px-2 py-0.5 rounded-full border border-emerald-200/60">
                            <span class="h-1 w-1 sm:h-1.5 sm:w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            100% Genuine
                        </span>
                    </h3>
                </div>

                <Link
                    :href="route('products.index')"
                    class="inline-flex items-center gap-0.5 sm:gap-1 text-[9px] sm:text-xs font-bold text-rose-600 hover:text-rose-700 group-hover/strip:translate-x-0.5 transition-all shrink-0 cursor-pointer"
                >
                    <span>View All</span>
                    <span>&rarr;</span>
                </Link>
            </div>

            <!-- Left & Right Gradient Fade Masks -->
            <div class="pointer-events-none absolute left-0 top-6 sm:top-8 bottom-0 w-3 sm:w-12 bg-gradient-to-r from-white via-white/80 to-transparent z-10"></div>
            <div class="pointer-events-none absolute right-0 top-6 sm:top-8 bottom-0 w-3 sm:w-12 bg-gradient-to-l from-white via-white/80 to-transparent z-10"></div>

            <!-- Sliding Track -->
            <div class="overflow-hidden py-0.5">
                <div 
                    :class="shouldAnimateMarquee ? 'animate-marquee-left flex items-center gap-1.5 sm:gap-3 w-max' : 'flex flex-nowrap items-center gap-1.5 sm:gap-3 overflow-x-auto scrollbar-none'"
                >
                    <div
                        v-for="(product, idx) in displaySlidingProducts"
                        :key="`${product.id}-${idx}`"
                        class="w-44 sm:w-68 shrink-0 p-1.5 sm:p-2.5 rounded-xl sm:rounded-2xl bg-white border border-rose-100/80 hover:border-rose-300 shadow-2xs hover:shadow-md transition-all duration-300 flex items-center gap-1.5 sm:gap-2.5 group/card cursor-pointer relative overflow-hidden"
                        @click="router.visit(product.target_url)"
                    >
                        <div class="relative h-10 w-10 sm:h-14 sm:w-14 rounded-lg sm:rounded-xl overflow-hidden bg-slate-50 shrink-0 border border-slate-100">
                            <img
                                :src="product.image"
                                :alt="product.name"
                                class="h-full w-full object-cover group-hover/card:scale-110 transition-transform duration-500"
                                loading="lazy"
                            />
                            <span
                                v-if="product.discount_percentage > 0"
                                class="absolute top-0.5 left-0.5 px-0.5 sm:px-1 py-0.2 sm:py-0.5 rounded bg-rose-600 text-[7px] sm:text-[8px] font-black text-white shadow-2xs leading-none"
                            >
                                -{{ product.discount_percentage }}%
                            </span>
                        </div>

                        <div class="min-w-0 flex-1 flex flex-col justify-between space-y-0.5">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[7px] sm:text-[9px] font-bold text-slate-400 uppercase tracking-wider truncate">
                                    {{ product.brand }}
                                </span>
                                <span class="text-[7px] sm:text-[8px] font-black text-emerald-600">
                                    ✓
                                </span>
                            </div>

                            <h4 class="text-[9px] sm:text-xs font-bold text-slate-900 truncate group-hover/card:text-rose-600 transition-colors leading-tight">
                                {{ product.name }}
                            </h4>

                            <div class="flex items-center justify-between gap-1 pt-0.5">
                                <div class="min-w-0">
                                    <span class="text-[9px] sm:text-xs font-black text-rose-700">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                    <span
                                        v-if="product.original_price && product.original_price > product.price"
                                        class="block text-[7px] sm:text-[8px] text-slate-400 line-through leading-none"
                                    >
                                        {{ formatPrice(product.original_price) }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    @click.stop="addProductToBag(product, $event)"
                                    class="inline-flex items-center justify-center gap-0.5 text-[8px] sm:text-[9px] font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-md sm:rounded-lg transition-all shrink-0 cursor-pointer active:scale-95 shadow-2xs"
                                    :class="addedToBag[product.id]
                                        ? 'bg-emerald-600 text-white shadow-emerald-600/20'
                                        : 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-600/20'"
                                >
                                    <span>{{ addedToBag[product.id] ? '✓ Added' : '+ Bag' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Bag Toast Notification -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 translate-y-3"
            enter-to-class="transform opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="transform opacity-100 translate-y-0"
            leave-to-class="transform opacity-0 translate-y-3"
        >
            <div
                v-if="toastMsg"
                class="fixed bottom-6 right-4 sm:right-6 z-50 flex items-center gap-2.5 rounded-2xl bg-slate-950/95 text-white px-4 py-3 shadow-2xl border border-rose-500/40 backdrop-blur-xl text-xs font-bold"
            >
                <span class="text-emerald-400">✓</span>
                <span>{{ toastMsg }}</span>
                <Link :href="route('products.index')" class="underline text-rose-300 hover:text-white font-extrabold ml-1">
                    View Bag &rarr;
                </Link>
            </div>
        </transition>
    </section>
</template>

<style scoped>
@keyframes marqueeSlideLeft {
    0% {
        transform: translate3d(0, 0, 0);
    }
    100% {
        transform: translate3d(-50%, 0, 0);
    }
}

.animate-marquee-left {
    display: flex;
    width: max-content;
    animation: marqueeSlideLeft 50s linear infinite;
    will-change: transform;
    backface-visibility: hidden;
}

.animate-marquee-left:hover {
    animation-play-state: paused;
}

@media (max-width: 640px) {
    .animate-marquee-left {
        animation-duration: 40s;
    }
}
</style>
