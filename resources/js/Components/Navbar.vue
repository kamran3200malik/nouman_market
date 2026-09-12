<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';

const page = usePage();
const showingNavigationDropdown = ref(false);
const showingUserDropdown = ref(false);
const showingCategoriesDropdown = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('all');
const cartCount = ref(0);
const cartTotal = ref(0);
const isScrolled = ref(false);

const updateCartData = () => {
    try {
        const saved = localStorage.getItem('beautybook_cart');
        if (saved) {
            const cart = JSON.parse(saved);
            if (Array.isArray(cart)) {
                cartCount.value = cart.reduce((acc, item) => acc + (Number(item.quantity) || 1), 0);
                cartTotal.value = cart.reduce((acc, item) => acc + ((Number(item.price) || 0) * (Number(item.quantity) || 1)), 0);
                return;
            }
        }
        cartCount.value = 0;
        cartTotal.value = 0;
    } catch (e) {
        cartCount.value = 0;
        cartTotal.value = 0;
    }
};

const formatPrice = (price) => {
    if (!price) return 'PKR 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const handleScroll = () => {
    if (typeof window !== 'undefined') {
        isScrolled.value = window.scrollY > 15;
    }
};

const closeAllDropdowns = (e) => {
    if (!e?.target?.closest('.user-dropdown-container')) {
        showingUserDropdown.value = false;
    }
    if (!e?.target?.closest('.categories-dropdown-container')) {
        showingCategoriesDropdown.value = false;
    }
};

onMounted(() => {
    updateCartData();
    window.addEventListener('cart-updated', updateCartData);
    window.addEventListener('storage', updateCartData);
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('click', closeAllDropdowns);
});

onUnmounted(() => {
    window.removeEventListener('cart-updated', updateCartData);
    window.removeEventListener('storage', updateCartData);
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('click', closeAllDropdowns);
});

const user = computed(() => page.props.auth?.user || null);
const siteSettings = computed(() => page.props.site_settings || {});

const userRoleBadge = computed(() => {
    if (!user.value) return null;
    if (user.value.role === 'admin' || user.value.roles?.some(r => r.name === 'admin')) {
        return { label: 'Admin Portal', routeName: 'admin.dashboard', color: 'bg-rose-500/15 text-rose-700 border-rose-200' };
    }
    return { label: 'Customer', routeName: 'customer.dashboard', color: 'bg-emerald-500/15 text-emerald-700 border-emerald-200' };
});

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        showingNavigationDropdown.value = false;
        const params = { search: searchQuery.value.trim() };
        if (selectedCategory.value !== 'all') {
            params.category = selectedCategory.value;
        }
        router.get(route('products.index'), params);
    }
};

const handleLogout = () => {
    showingUserDropdown.value = false;
    router.post(route('logout'));
};

const navCategories = [
    { name: 'Skincare', slug: 'skincare', icon: '✨', desc: 'Hydrating serums, toners & creams' },
    { name: 'Makeup', slug: 'makeup-cosmetics', icon: '💄', desc: 'Foundations, lipsticks & palettes' },
    { name: 'Hair Care', slug: 'haircare-styling', icon: '💇‍♀️', desc: 'Argan oils, masks & styling' },
    { name: 'Fragrances', slug: 'fragrances-perfumes', icon: '🌸', desc: 'Luxury French & Oriental perfumes' },
    { name: 'Organic', slug: 'organic-herbal', icon: '🌿', desc: 'Pure herbal & botanical formulas' },
    { name: 'Tools & Nails', slug: 'nails-tools', icon: '💅', desc: 'Brushes, sponges & nail care' },
];
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 bg-white shadow-sm transition-all duration-300">
        <!-- MAIN HEADER BAR -->
        <div
            class="border-b border-slate-100 bg-white/95 backdrop-blur-xl transition-all duration-300"
            :class="isScrolled ? 'py-2.5' : 'py-3.5'"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4 sm:gap-8">
                <!-- Left: Brand Logo -->
                <Link :href="route('home')" class="group flex items-center gap-3 shrink-0">
                    <div class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-rose-600 via-pink-600 to-amber-500 text-white font-serif font-black text-xl shadow-md shadow-pink-900/20 group-hover:scale-105 transition-transform duration-300">
                        {{ (siteSettings.site_name || 'L').charAt(0) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-serif text-lg sm:text-xl font-bold tracking-tight text-slate-900 group-hover:text-rose-600 transition-colors">
                                {{ siteSettings.site_name || 'Luxe Market' }}
                            </span>
                        </div>
                        <p class="text-[10px] font-semibold tracking-wider uppercase text-rose-500 hidden sm:block">
                            {{ siteSettings.site_tagline || '100% Genuine Cosmetics & Care' }}
                        </p>
                    </div>
                </Link>

                <!-- Center: Luxury Integrated Search Bar -->
                <div class="hidden md:flex flex-1 max-w-2xl">
                    <form @submit.prevent="handleSearch" class="flex w-full items-center rounded-full bg-slate-50 border border-slate-200/90 focus-within:border-rose-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-rose-500/20 transition-all p-1 shadow-2xs">
                        <!-- Category Dropdown filter -->
                        <div class="relative hidden xl:block border-r border-slate-200 px-3 py-1 text-xs">
                            <select
                                v-model="selectedCategory"
                                class="border-0 bg-transparent py-0 pl-1 pr-6 text-xs font-semibold text-slate-700 focus:ring-0 cursor-pointer"
                            >
                                <option value="all">All Departments</option>
                                <option v-for="cat in navCategories" :key="cat.slug" :value="cat.slug">{{ cat.name }}</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="relative flex-1 flex items-center px-3">
                            <svg class="h-4 w-4 text-slate-400 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by brand, serum, foundation, lipstick, fragrance..."
                                class="w-full border-0 p-0 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:ring-0 bg-transparent"
                            />
                        </div>

                        <!-- Search CTA Button -->
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 px-5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-sm transition-all duration-200 hover:scale-102 active:scale-95 shrink-0 cursor-pointer"
                        >
                            <span>Search</span>
                        </button>
                    </form>
                </div>

                <!-- Right: Action Utilities (Wishlist, Shopping Bag, Account) -->
                <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                    <!-- Wishlist Icon -->
                    <Link
                        :href="user ? route('customer.wishlist.index') : route('login')"
                        class="relative hidden sm:inline-flex items-center justify-center h-10 w-10 rounded-full text-slate-700 hover:text-rose-600 hover:bg-rose-50 transition-all cursor-pointer border border-transparent hover:border-pink-100"
                        title="Wishlist"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </Link>

                    <!-- Shopping Cart / Bag with Live Counter & Total -->
                    <Link
                        :href="route('products.index', { cart: 'open' })"
                        class="flex items-center gap-2.5 p-1.5 sm:px-3.5 sm:py-2 rounded-full bg-slate-50 hover:bg-rose-50 text-slate-800 hover:text-rose-600 border border-slate-200/80 hover:border-pink-200 transition-all cursor-pointer shadow-2xs group"
                        title="Shopping Bag"
                    >
                        <div class="relative flex items-center justify-center">
                            <svg class="h-5 w-5 text-slate-700 group-hover:text-rose-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span
                                v-if="cartCount > 0"
                                class="absolute -top-1.5 -right-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-rose-600 text-white text-[9px] font-black shadow-xs ring-2 ring-white"
                            >
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </div>
                        <div class="hidden xl:block text-left leading-none pr-1">
                            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">My Bag</span>
                            <span class="block text-xs font-black text-rose-700 mt-0.5">{{ formatPrice(cartTotal) }}</span>
                        </div>
                    </Link>

                    <!-- Authenticated Account Menu -->
                    <template v-if="user">
                        <NotificationBell />

                        <div class="relative user-dropdown-container">
                            <button
                                @click="showingUserDropdown = !showingUserDropdown"
                                type="button"
                                class="flex items-center gap-2 rounded-full border border-slate-200/90 bg-white pl-1.5 pr-3 py-1 shadow-2xs hover:border-pink-300 hover:shadow-sm transition cursor-pointer select-none"
                            >
                                <div class="h-7 w-7 rounded-full bg-gradient-to-tr from-rose-600 to-pink-500 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                                    {{ (user.name || 'U').charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-xs font-bold text-slate-800 max-w-[90px] truncate hidden sm:inline-block">
                                    {{ user.name?.split(' ')[0] }}
                                </span>
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- User Dropdown Menu -->
                            <transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="transform opacity-0 -translate-y-2 scale-95"
                                enter-to-class="transform opacity-100 translate-y-0 scale-100"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="transform opacity-100 translate-y-0 scale-100"
                                leave-to-class="transform opacity-0 -translate-y-2 scale-95"
                            >
                                <div
                                    v-show="showingUserDropdown"
                                    class="absolute right-0 mt-2 w-60 rounded-3xl bg-white p-2 shadow-2xl border border-pink-100 ring-1 ring-rose-500/10 z-50 divide-y divide-gray-100 text-left"
                                >
                                    <div class="p-3">
                                        <p class="text-xs font-bold text-slate-900 truncate">{{ user.name }}</p>
                                        <p class="text-[11px] text-slate-500 truncate">{{ user.email }}</p>
                                        <div class="mt-2">
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                                :class="userRoleBadge?.color"
                                            >
                                                {{ userRoleBadge?.label }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="py-1.5 text-xs font-medium space-y-0.5">
                                        <Link
                                            :href="route('dashboard')"
                                            @click="showingUserDropdown = false"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-rose-50 hover:text-rose-600 transition"
                                        >
                                            <span>📊</span>
                                            <span>Dashboard</span>
                                        </Link>

                                        <Link
                                            :href="route('customer.orders.index')"
                                            @click="showingUserDropdown = false"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-rose-50 hover:text-rose-600 transition"
                                        >
                                            <span>📦</span>
                                            <span>My Orders</span>
                                        </Link>

                                        <Link
                                            :href="route('customer.wishlist.index')"
                                            @click="showingUserDropdown = false"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-rose-50 hover:text-rose-600 transition"
                                        >
                                            <span>❤️</span>
                                            <span>Wishlist</span>
                                        </Link>

                                        <Link
                                            :href="route('profile.edit')"
                                            @click="showingUserDropdown = false"
                                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-rose-50 hover:text-rose-600 transition"
                                        >
                                            <span>⚙️</span>
                                            <span>Profile Settings</span>
                                        </Link>
                                    </div>

                                    <div class="pt-1">
                                        <button
                                            type="button"
                                            @click="handleLogout"
                                            class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition text-left cursor-pointer"
                                        >
                                            <span>🚪</span>
                                            <span>Sign Out</span>
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </template>

                    <!-- Guest Authentication CTA -->
                    <template v-else>
                        <div class="hidden sm:flex items-center gap-2">
                            <Link
                                :href="route('login')"
                                class="px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 hover:text-rose-600 transition"
                            >
                                Sign In
                            </Link>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center rounded-full bg-slate-900 hover:bg-rose-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-xs transition hover:scale-102"
                            >
                                Register
                            </Link>
                        </div>
                    </template>

                    <!-- Mobile Drawer Toggle Button -->
                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="inline-flex md:hidden items-center justify-center rounded-xl p-2 text-slate-800 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 transition"
                        aria-label="Toggle menu"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. SECONDARY CATEGORY NAVIGATION RIBBON (DESKTOP) -->
        <div class="hidden md:block bg-white/95 border-b border-slate-100 shadow-2xs">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-6 py-2 text-xs font-semibold">
                <!-- Left Links -->
                <div class="flex items-center gap-5 overflow-x-auto scrollbar-none py-0.5">
                    <Link
                        :href="route('home')"
                        class="hover:text-rose-600 transition-colors pb-0.5 flex items-center gap-1"
                        :class="route().current('home') ? 'text-rose-600 font-extrabold border-b-2 border-rose-600' : 'text-slate-700'"
                    >
                        <span>🏠 Home</span>
                    </Link>

                    <Link
                        :href="route('products.index')"
                        class="hover:text-rose-600 transition-colors pb-0.5 flex items-center gap-1"
                        :class="route().current('products.index') && !route().params?.category && !route().params?.trending ? 'text-rose-600 font-extrabold border-b-2 border-rose-600' : 'text-slate-700'"
                    >
                        <span>🛍️ All Products</span>
                    </Link>

                    <Link
                        v-for="cat in navCategories"
                        :key="cat.slug"
                        :href="route('products.index', { category: cat.slug })"
                        class="hover:text-rose-600 transition-colors pb-0.5 flex items-center gap-1 shrink-0"
                        :class="route().params?.category === cat.slug ? 'text-rose-600 font-extrabold border-b-2 border-rose-600' : 'text-slate-700'"
                    >
                        <span>{{ cat.icon }}</span>
                        <span>{{ cat.name }}</span>
                    </Link>

                    <Link
                        :href="route('blogs.index')"
                        class="hover:text-rose-600 transition-colors pb-0.5 flex items-center gap-1 shrink-0"
                        :class="route().current('blogs.*') ? 'text-rose-600 font-extrabold border-b-2 border-rose-600' : 'text-slate-700'"
                    >
                        <span>📖 Beauty Blog</span>
                    </Link>
                </div>

                <!-- Right Highlight: Flash Deals -->
                <Link
                    :href="route('products.index', { trending: '1' })"
                    class="hidden lg:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-[11px] border border-rose-200/80 transition-all hover:scale-102 shrink-0"
                >
                    <span class="h-2 w-2 rounded-full bg-rose-600 animate-ping"></span>
                    <span>🔥 Flash Deals & Discounts</span>
                </Link>
            </div>
        </div>

        <!-- 4. MOBILE NAVIGATION DRAWER -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform -translate-y-4 opacity-0 scale-95"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform -translate-y-4 opacity-0 scale-95"
        >
            <div
                v-show="showingNavigationDropdown"
                class="md:hidden max-h-[85vh] overflow-y-auto bg-white border-b border-slate-200 p-4 space-y-4 shadow-2xl"
            >
                <!-- Mobile Search Bar -->
                <form @submit.prevent="handleSearch" class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search products, serums, makeup..."
                        class="w-full pl-9 pr-16 py-2.5 rounded-xl text-xs bg-slate-100 border border-slate-200 focus:bg-white focus:border-rose-500 text-slate-900"
                    />
                    <span class="absolute left-3 top-3 text-slate-400 text-xs">🔍</span>
                    <button
                        type="submit"
                        @click="showingNavigationDropdown = false"
                        class="absolute right-1.5 top-1.5 bottom-1.5 px-3 bg-rose-600 text-white rounded-lg text-[10px] font-bold"
                    >
                        Go
                    </button>
                </form>

                <!-- Mobile User Profile / Auth -->
                <div v-if="user" class="p-3.5 rounded-2xl bg-rose-50/70 border border-rose-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                                {{ (user.name || 'U').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ user.name }}</p>
                                <span class="text-[10px] text-slate-500 truncate block">{{ user.email }}</span>
                            </div>
                        </div>
                        <Link
                            :href="route('dashboard')"
                            @click="showingNavigationDropdown = false"
                            class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-[10px] font-bold uppercase tracking-wider shrink-0"
                        >
                            Dashboard
                        </Link>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-rose-200/60 text-xs font-semibold">
                        <Link :href="route('customer.orders.index')" @click="showingNavigationDropdown = false" class="p-2 rounded-xl bg-white text-slate-700 text-center border border-rose-100 shadow-2xs">
                            📦 My Orders
                        </Link>
                        <Link :href="route('customer.wishlist.index')" @click="showingNavigationDropdown = false" class="p-2 rounded-xl bg-white text-slate-700 text-center border border-rose-100 shadow-2xs">
                            ❤️ Wishlist
                        </Link>
                    </div>
                </div>
                <div v-else class="grid grid-cols-2 gap-2">
                    <Link
                        :href="route('login')"
                        @click="showingNavigationDropdown = false"
                        class="text-center rounded-xl bg-slate-100 py-2.5 text-xs font-bold text-slate-800"
                    >
                        Sign In
                    </Link>
                    <Link
                        :href="route('register')"
                        @click="showingNavigationDropdown = false"
                        class="text-center rounded-xl bg-rose-600 py-2.5 text-xs font-bold text-white shadow-sm"
                    >
                        Register
                    </Link>
                </div>

                <!-- Departments Navigation -->
                <div class="space-y-1 text-xs font-semibold">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 px-2 pb-1">Shop by Department</p>
                    <Link :href="route('home')" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                        <span>🏠 Home Storefront</span>
                        <span>&rarr;</span>
                    </Link>
                    <Link :href="route('products.index')" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                        <span>🛍️ All Products Catalog</span>
                        <span class="text-[10px] bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded-md font-bold">ALL</span>
                    </Link>
                    <Link
                        v-for="cat in navCategories"
                        :key="cat.slug"
                        :href="route('products.index', { category: cat.slug })"
                        @click="showingNavigationDropdown = false"
                        class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700"
                    >
                        <span class="flex items-center gap-2">
                            <span>{{ cat.icon }}</span>
                            <span>{{ cat.name }}</span>
                        </span>
                        <span>&rarr;</span>
                    </Link>
                    <Link :href="route('products.index', { trending: '1' })" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-rose-600 font-bold">
                        <span>🔥 Flash Deals & Trending</span>
                        <span>&rarr;</span>
                    </Link>
                    <Link :href="route('blogs.index')" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                        <span>📖 Beauty Blogs & Advice</span>
                        <span>&rarr;</span>
                    </Link>
                </div>

                <!-- Sign Out -->
                <div v-if="user" class="pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        @click="handleLogout"
                        class="w-full py-2.5 text-center text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition"
                    >
                        🚪 Sign Out
                    </button>
                </div>
            </div>
        </transition>
    </header>
</template>
