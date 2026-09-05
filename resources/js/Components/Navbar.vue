<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';

const showingNavigationDropdown = ref(false);
const searchQuery = ref('');
const page = usePage();
const cartCount = ref(0);

const updateCartCount = () => {
    try {
        const saved = localStorage.getItem('beautybook_cart');
        if (saved) {
            const cart = JSON.parse(saved);
            cartCount.value = Array.isArray(cart) ? cart.reduce((acc, item) => acc + (Number(item.quantity) || 1), 0) : 0;
        } else {
            cartCount.value = 0;
        }
    } catch (e) {
        cartCount.value = 0;
    }
};

onMounted(() => {
    updateCartCount();
    window.addEventListener('cart-updated', updateCartCount);
    window.addEventListener('storage', updateCartCount);
});

onUnmounted(() => {
    window.removeEventListener('cart-updated', updateCartCount);
    window.removeEventListener('storage', updateCartCount);
});

const user = computed(() => page.props.auth?.user || null);
const siteSettings = computed(() => page.props.site_settings || {});

const userRoleBadge = computed(() => {
    if (!user.value) return null;
    if (user.value.role === 'admin' || user.value.roles?.some(r => r.name === 'admin')) {
        return { label: 'Admin Portal', routeName: 'admin.dashboard', color: 'bg-rose-500/15 text-rose-700 border-rose-200' };
    }
    return { label: 'My Account', routeName: 'customer.dashboard', color: 'bg-emerald-500/15 text-emerald-700 border-emerald-200' };
});

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('products.index'), { search: searchQuery.value.trim() });
    }
};
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <!-- Top Announcement Bar -->
        <div class="bg-gradient-to-r from-rose-900 via-pink-900 to-rose-950 text-white text-[11px] py-1.5 px-4 text-center font-medium tracking-wide flex items-center justify-between sm:justify-center gap-3">
            <span class="truncate">🎉 Free Delivery on all luxury orders over PKR 5,000 | 100% Genuine & Authentic Products</span>
            <div class="hidden sm:flex items-center gap-4 text-[10px] text-pink-200 font-normal">
                <span>⚡ 24-48H Express Delivery</span>
                <span>•</span>
                <span>🔒 Cash on Delivery Available</span>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-3 py-2.5 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between rounded-2xl sm:rounded-full px-4 sm:px-6 py-2 shadow-xl shadow-slate-950/5 transition-all border border-white/80 bg-white/95 backdrop-blur-2xl">
                <!-- Brand Logo -->
                <Link :href="route('home')" class="group flex items-center gap-2.5 shrink-0">
                    <div class="relative flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-rose-600 via-pink-600 to-amber-500 shadow-md shadow-pink-900/25 ring-1 ring-white/40 transition-transform duration-300 group-hover:scale-105">
                        <span class="font-serif font-black text-white text-lg">
                            {{ (siteSettings.site_name || 'L').charAt(0) }}
                        </span>
                        <div class="absolute -top-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 border-2 border-white animate-pulse"></div>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-serif text-base sm:text-lg font-bold tracking-tight text-slate-900 group-hover:text-rose-600 transition-colors">
                                {{ siteSettings.site_name || 'Luxe Market' }}
                            </span>
                        </div>
                        <p class="text-[10px] font-medium tracking-wide text-rose-500 hidden sm:block">
                            {{ siteSettings.site_tagline || '100% Genuine Cosmetics & Care' }}
                        </p>
                    </div>
                </Link>

                <!-- Search Input Bar (Desktop) -->
                <div class="hidden md:flex flex-1 max-w-md mx-6">
                    <form @submit.prevent="handleSearch" class="relative w-full">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products, brands (The Ordinary, CeraVe, Fenty...)"
                            class="w-full pl-9 pr-20 py-2 rounded-full text-xs bg-slate-100/90 border border-slate-200 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 text-slate-900 placeholder:text-slate-400 transition"
                        />
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                        <button
                            type="submit"
                            class="absolute right-1.5 top-1 bottom-1 px-3 bg-rose-600 hover:bg-rose-700 text-white rounded-full text-[10px] font-bold uppercase tracking-wider transition"
                        >
                            Search
                        </button>
                    </form>
                </div>

                <!-- Center Navigation Links -->
                <div class="hidden items-center gap-1 rounded-full bg-slate-100/80 p-1 backdrop-blur-md xl:flex border border-slate-200/60 text-xs">
                    <Link
                        :href="route('home')"
                        class="rounded-full px-3.5 py-1.5 font-bold uppercase tracking-wider transition-all duration-200 text-[11px]"
                        :class="route().current('home') ? 'bg-white text-rose-600 shadow-xs border border-pink-100' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                    >
                        Home
                    </Link>
                    <Link
                        :href="route('products.index')"
                        class="rounded-full px-3.5 py-1.5 font-bold uppercase tracking-wider transition-all duration-200 text-[11px]"
                        :class="route().current('products.index') && !route().params?.category ? 'bg-white text-rose-600 shadow-xs border border-pink-100' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                    >
                        Shop All
                    </Link>
                    <Link
                        :href="route('products.index', { category: 'skincare' })"
                        class="rounded-full px-3 py-1.5 font-bold uppercase tracking-wider transition-all duration-200 text-[11px]"
                        :class="route().params?.category === 'skincare' ? 'bg-white text-rose-600 shadow-xs border border-pink-100' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                    >
                        Skincare
                    </Link>
                    <Link
                        :href="route('products.index', { category: 'makeup-cosmetics' })"
                        class="rounded-full px-3 py-1.5 font-bold uppercase tracking-wider transition-all duration-200 text-[11px]"
                        :class="route().params?.category === 'makeup-cosmetics' ? 'bg-white text-rose-600 shadow-xs border border-pink-100' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                    >
                        Makeup
                    </Link>
                    <Link
                        :href="route('products.index', { category: 'haircare-styling' })"
                        class="rounded-full px-3 py-1.5 font-bold uppercase tracking-wider transition-all duration-200 text-[11px]"
                        :class="route().params?.category === 'haircare-styling' ? 'bg-white text-rose-600 shadow-xs border border-pink-100' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                    >
                        Haircare
                    </Link>
                    <Link
                        :href="route('blogs.index')"
                        class="rounded-full px-3 py-1.5 font-bold uppercase tracking-wider transition-all duration-200 text-[11px]"
                        :class="route().current('blogs.*') ? 'bg-white text-rose-600 shadow-xs border border-pink-100' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
                    >
                        Blogs
                    </Link>
                </div>

                <!-- Right Action Icons: Wishlist, Cart Bag, Account -->
                <div class="hidden items-center gap-2.5 sm:flex">
                    <!-- Wishlist Link -->
                    <Link
                        v-if="user"
                        :href="route('customer.wishlist.index')"
                        class="relative inline-flex items-center justify-center h-9 w-9 rounded-full bg-slate-100/90 hover:bg-rose-50 text-slate-700 hover:text-rose-600 border border-slate-200/80 shadow-xs transition-all hover:scale-105"
                        title="My Wishlist"
                    >
                        <span class="text-sm">❤️</span>
                    </Link>

                    <!-- Shopping Cart Button -->
                    <Link
                        :href="route('products.index', { cart: 'open' })"
                        class="relative inline-flex items-center justify-center h-9 w-9 rounded-full bg-slate-100/90 hover:bg-rose-50 text-slate-700 hover:text-rose-600 border border-slate-200/80 shadow-xs transition-all hover:scale-105 cursor-pointer"
                        title="Shopping Bag"
                    >
                        <span class="text-sm">🛍️</span>
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-1 -right-1 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-rose-600 text-white text-[9px] font-black shadow-xs ring-2 ring-white"
                        >
                            {{ cartCount > 99 ? '99+' : cartCount }}
                        </span>
                    </Link>

                    <template v-if="user">
                        <NotificationBell />

                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-white/95 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-800 shadow-xs transition hover:border-rose-300 hover:text-rose-600"
                        >
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>{{ userRoleBadge?.label || 'Account' }}</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-full px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 transition hover:text-rose-600"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-pink-900/20 transition hover:scale-102"
                        >
                            <span>Register</span>
                        </Link>
                    </template>
                </div>

                <!-- Mobile Header Right Actions -->
                <div class="flex items-center gap-2 xl:hidden">
                    <Link
                        :href="route('products.index', { cart: 'open' })"
                        class="relative inline-flex items-center justify-center h-8 w-8 rounded-xl bg-rose-50 text-rose-700 border border-rose-200/80 shadow-xs"
                    >
                        <span class="text-xs">🛍️</span>
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-white text-[8px] font-black ring-1 ring-white"
                        >
                            {{ cartCount > 99 ? '99+' : cartCount }}
                        </span>
                    </Link>

                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="inline-flex items-center justify-center rounded-xl p-2 text-slate-800 bg-slate-100/90 hover:bg-rose-50 hover:text-rose-600 transition border border-slate-200/60"
                        aria-label="Toggle navigation menu"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </nav>

            <!-- Mobile Navigation Drawer -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform -translate-y-4 opacity-0 scale-95"
                enter-to-class="transform translate-y-0 opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100 scale-100"
                leave-to-class="transform -translate-y-4 opacity-0 scale-95"
            >
                <div v-show="showingNavigationDropdown" class="mt-2 xl:hidden max-h-[80vh] overflow-y-auto rounded-3xl shadow-2xl border border-white/90 bg-white/98 backdrop-blur-2xl p-4 space-y-4 ring-1 ring-rose-500/15">
                    <!-- Mobile Search Bar -->
                    <form @submit.prevent="handleSearch" class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products..."
                            class="w-full pl-9 pr-16 py-2 rounded-xl text-xs bg-slate-100 border border-slate-200 focus:bg-white focus:border-rose-500 text-slate-900"
                        />
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                        <button
                            type="submit"
                            @click="showingNavigationDropdown = false"
                            class="absolute right-1 top-1 bottom-1 px-3 bg-rose-600 text-white rounded-lg text-[10px] font-bold"
                        >
                            Go
                        </button>
                    </form>

                    <!-- User Account / Sign In in Drawer -->
                    <div v-if="user" class="p-3 rounded-2xl bg-rose-50/50 border border-rose-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                                {{ (user.name || 'U').charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ user.name }}</p>
                                <span class="text-[10px] text-slate-500">{{ user.email }}</span>
                            </div>
                        </div>
                        <Link
                            :href="route('dashboard')"
                            @click="showingNavigationDropdown = false"
                            class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-[10px] font-bold uppercase tracking-wider"
                        >
                            Dashboard
                        </Link>
                    </div>
                    <div v-else class="grid grid-cols-2 gap-2">
                        <Link
                            :href="route('login')"
                            @click="showingNavigationDropdown = false"
                            class="text-center rounded-xl bg-slate-100 py-2 text-xs font-bold text-slate-800"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('register')"
                            @click="showingNavigationDropdown = false"
                            class="text-center rounded-xl bg-rose-600 py-2 text-xs font-bold text-white shadow-sm"
                        >
                            Register
                        </Link>
                    </div>

                    <!-- Category Navigation Links -->
                    <div class="space-y-1 text-xs font-semibold">
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 px-3">Departments</p>
                        <Link :href="route('home')" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>🏠 Home Storefront</span>
                            <span>&rarr;</span>
                        </Link>
                        <Link :href="route('products.index')" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>🛍️ Shop All Products</span>
                            <span class="text-[10px] bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded-md font-bold">ALL</span>
                        </Link>
                        <Link :href="route('products.index', { category: 'skincare' })" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>✨ Skincare & Serums</span>
                            <span>&rarr;</span>
                        </Link>
                        <Link :href="route('products.index', { category: 'makeup-cosmetics' })" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>💄 Makeup & Cosmetics</span>
                            <span>&rarr;</span>
                        </Link>
                        <Link :href="route('products.index', { category: 'haircare-styling' })" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>💇‍♀️ Haircare & Treatments</span>
                            <span>&rarr;</span>
                        </Link>
                        <Link :href="route('products.index', { category: 'fragrances-perfumes' })" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>🌸 Perfumes & Fragrances</span>
                            <span>&rarr;</span>
                        </Link>
                        <Link :href="route('products.index', { category: 'organic-herbal' })" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>🌿 Organic & Herbal</span>
                            <span>&rarr;</span>
                        </Link>
                        <Link :href="route('blogs.index')" @click="showingNavigationDropdown = false" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-rose-50 text-slate-700">
                            <span>📖 Beauty Guides & Blogs</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>
                </div>
            </transition>
        </div>
    </header>
</template>
