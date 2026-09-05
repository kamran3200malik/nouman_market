<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { storageUrl } from '@/Utils/storage';

const page = usePage();
const mobileMenuOpen = ref(false);
const sidebarCollapsed = ref(false);

onMounted(() => {
    try {
        const stored = localStorage.getItem('customer_sidebar_collapsed');
        if (stored !== null) {
            sidebarCollapsed.value = stored === 'true';
        }
    } catch (e) {}
});

const toggleSidebar = () => {
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        mobileMenuOpen.value = !mobileMenuOpen.value;
    } else {
        sidebarCollapsed.value = !sidebarCollapsed.value;
        try {
            localStorage.setItem('customer_sidebar_collapsed', sidebarCollapsed.value ? 'true' : 'false');
        } catch (e) {}
    }
};

const user = computed(() => page.props.auth?.user || {});
const userName = computed(() => user.value?.name || 'Customer');
const userEmail = computed(() => user.value?.email || '');

const navSections = [
    {
        title: 'My Shopping Hub',
        items: [
            { name: 'Account Overview', routeName: 'customer.dashboard', icon: 'dashboard', badge: null },
            { name: 'My Orders & Deliveries', routeName: 'customer.orders.index', icon: 'orders', badge: null },
            { name: 'Saved Wishlist', routeName: 'customer.wishlist.index', icon: 'favorites', badge: null },
            { name: 'My Reviews', routeName: 'customer.reviews.index', icon: 'reviews', badge: null },
        ],
    },
    {
        title: 'Marketplace Store',
        items: [
            { name: 'Browse All Products', routeName: 'products.index', icon: 'products', badge: 'Shop' },
            { name: 'Beauty Magazine', routeName: 'blogs.index', icon: 'blogs', badge: null },
        ],
    },
    {
        title: 'Account Settings',
        items: [
            { name: 'Shipping Address & Profile', routeName: 'customer.profile', icon: 'profile', badge: null },
        ],
    },
];

const isRouteActive = (name) => {
    try {
        return route().current(name) || route().current(`${name}.*`);
    } catch (e) {
        return false;
    }
};

const getRouteUrl = (name) => {
    try {
        return route(name);
    } catch (e) {
        return '#';
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-900/5 flex flex-col font-sans text-slate-800 antialiased selection:bg-rose-500 selection:text-white">
        <div class="flex flex-1 min-h-screen">
            <!-- Mobile Drawer Backdrop -->
            <transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden"
                    @click="mobileMenuOpen = false"
                ></div>
            </transition>

            <!-- Customer Portal Sidebar -->
            <aside
                :class="[
                    'fixed inset-y-0 left-0 z-50 h-screen bg-[#140812] border-r border-pink-950/60 flex flex-col justify-between transition-all duration-300 ease-in-out shadow-2xl lg:shadow-none',
                    mobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full',
                    'lg:translate-x-0',
                    sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64'
                ]"
            >
                <div class="flex flex-col h-full overflow-hidden relative z-10">
                    <!-- Brand Header -->
                    <div
                        class="p-4 border-b border-pink-900/30 flex items-center shrink-0 transition-all duration-300"
                        :class="sidebarCollapsed ? 'justify-center' : 'justify-between'"
                    >
                        <Link
                            :href="route('customer.dashboard')"
                            class="flex items-center gap-3 group min-w-0"
                            @click="mobileMenuOpen = false"
                        >
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-pink-600 via-rose-600 to-amber-400 shadow-md shadow-pink-900/40 ring-1 ring-white/20 shrink-0">
                                <span class="font-serif font-black text-white text-lg">🛍️</span>
                            </div>

                            <div v-show="!sidebarCollapsed || mobileMenuOpen" class="flex flex-col min-w-0">
                                <span class="font-bold text-white text-sm tracking-tight group-hover:text-pink-200 transition-colors truncate">
                                    Luxe Market
                                </span>
                                <span class="text-[10px] font-medium text-pink-300/70 truncate">
                                    {{ userName }}
                                </span>
                            </div>
                        </Link>

                        <!-- Desktop Collapse Button -->
                        <button
                            @click="toggleSidebar"
                            class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-pink-300/70 hover:text-white hover:bg-pink-950/60 border border-pink-900/50 transition-all cursor-pointer shadow-xs"
                            :title="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
                        >
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <!-- Mobile Close Button -->
                        <button
                            @click="mobileMenuOpen = false"
                            class="lg:hidden p-1.5 rounded-xl text-pink-300 hover:text-white hover:bg-pink-950 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Navigation Items List -->
                    <div class="flex-1 overflow-y-auto px-3 py-3 space-y-4 scrollbar-thin scrollbar-thumb-pink-950">
                        <div v-for="(section, idx) in navSections" :key="idx" class="space-y-0.5">
                            <div v-show="!sidebarCollapsed || mobileMenuOpen" class="px-3 pt-2 pb-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-pink-400/80">
                                    {{ section.title }}
                                </span>
                            </div>

                            <div v-if="sidebarCollapsed && !mobileMenuOpen" class="w-full h-px bg-pink-900/30 my-2"></div>

                            <Link
                                v-for="item in section.items"
                                :key="item.routeName"
                                :href="getRouteUrl(item.routeName)"
                                @click="mobileMenuOpen = false"
                                class="flex items-center rounded-xl text-xs transition-all duration-200 group relative select-none"
                                :class="[
                                    isRouteActive(item.routeName)
                                        ? 'bg-gradient-to-r from-pink-600/30 to-rose-600/20 text-white font-bold border border-pink-500/40 shadow-xs'
                                        : 'text-pink-200/70 hover:text-white hover:bg-pink-950/40 font-medium border border-transparent',
                                    sidebarCollapsed && !mobileMenuOpen
                                        ? 'justify-center p-2.5'
                                        : 'justify-between px-3 py-2.5'
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg transition-all shrink-0 text-sm"
                                        :class="isRouteActive(item.routeName)
                                            ? 'bg-rose-600 text-white shadow-xs'
                                            : 'text-pink-300 group-hover:text-pink-100 group-hover:bg-pink-900/40'"
                                    >
                                        <svg v-if="item.icon === 'dashboard'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                                        <svg v-else-if="item.icon === 'orders'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        <svg v-else-if="item.icon === 'favorites'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                        <svg v-else-if="item.icon === 'products'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                        <svg v-else-if="item.icon === 'reviews'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                        <svg v-else-if="item.icon === 'blogs'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                                        <svg v-else-if="item.icon === 'profile'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </span>

                                    <span v-show="!sidebarCollapsed || mobileMenuOpen" class="truncate">
                                        {{ item.name }}
                                    </span>
                                </div>

                                <span
                                    v-if="item.badge && (!sidebarCollapsed || mobileMenuOpen)"
                                    class="px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 text-[9px] font-bold border border-rose-500/30"
                                >
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </div>
                    </div>

                    <!-- Bottom User Area -->
                    <div class="p-3 border-t border-pink-950/60 bg-[#0a0309] shrink-0">
                        <div class="flex items-center justify-between rounded-xl bg-pink-950/40 border border-pink-900/40 p-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <img
                                    :src="storageUrl(user?.avatar, 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=140')"
                                    :alt="userName"
                                    class="h-8 w-8 rounded-lg object-cover border border-rose-400/50"
                                />
                                <div v-show="!sidebarCollapsed || mobileMenuOpen" class="min-w-0">
                                    <p class="text-xs font-bold text-white truncate">{{ userName }}</p>
                                    <p class="text-[10px] text-pink-300/80 truncate">Verified Buyer</p>
                                </div>
                            </div>
                            <Link
                                v-show="!sidebarCollapsed || mobileMenuOpen"
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="p-1.5 rounded-lg text-pink-300/70 hover:text-rose-300 transition"
                                title="Sign out"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Container -->
            <div
                :class="[
                    'flex-1 flex flex-col min-w-0 min-h-screen bg-slate-50/70 transition-all duration-300',
                    sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-64'
                ]"
            >
                <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-xl border-b border-pink-100 shadow-xs">
                    <div class="px-4 sm:px-8 py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <button
                                @click="toggleSidebar"
                                class="p-2 rounded-xl text-slate-600 hover:bg-pink-50 border border-slate-200"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14" />
                                </svg>
                            </button>
                            <Link :href="route('products.index')" class="text-xs font-bold text-rose-600 hover:underline">
                                🛍️ Shop Collection
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <NotificationBell />
                            <Link :href="route('home')" class="text-xs font-semibold text-slate-700 hover:text-rose-600">
                                🏪 Storefront
                            </Link>
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
