<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';

const page = usePage();

// Mobile sidebar open state (< 1024px)
const sidebarMobileOpen = ref(false);

// Desktop sidebar collapsed state (>= 1024px)
const sidebarCollapsed = ref(false);

// Sidebar menu search query
const searchQuery = ref('');

// Track open state for collapsible parent menus
const openMenus = ref({});

// Track flyout open state for collapsed sidebar mode on desktop
const activeFlyoutMenu = ref(null);

onMounted(() => {
    try {
        const stored = localStorage.getItem('admin_sidebar_collapsed');
        if (stored !== null) {
            sidebarCollapsed.value = stored === 'true';
        }
    } catch (e) {
        // Fallback if localStorage is inaccessible
    }
    initializeActiveMenus();
});

const user = computed(() => page.props.auth?.user || {});
const userName = computed(() => user.value?.name || 'Administrator');
const userEmail = computed(() => user.value?.email || 'admin@example.com');
const userRole = computed(() => {
    if (user.value?.roles?.some(r => r.name === 'admin')) return 'Super Admin';
    return 'Admin';
});

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const toggleSidebar = () => {
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        sidebarMobileOpen.value = !sidebarMobileOpen.value;
    } else {
        sidebarCollapsed.value = !sidebarCollapsed.value;
        activeFlyoutMenu.value = null;
        try {
            localStorage.setItem('admin_sidebar_collapsed', sidebarCollapsed.value ? 'true' : 'false');
        } catch (e) {}
    }
};

const closeMobileSidebar = () => {
    sidebarMobileOpen.value = false;
};

// Check if a specific route is active
const isRouteActive = (name) => {
    if (!name) return false;
    try {
        return route().current(name) || route().current(`${name}.*`);
    } catch (e) {
        return false;
    }
};

// Check if any child route in a submenu is active
const isParentActive = (menu) => {
    if (menu.routeName && isRouteActive(menu.routeName)) return true;
    if (menu.children && Array.isArray(menu.children)) {
        return menu.children.some(child => isRouteActive(child.routeName));
    }
    return false;
};

const getRouteUrl = (name) => {
    if (!name) return '#';
    try {
        return route(name);
    } catch (e) {
        return '#';
    }
};

// Navigation definition for Product Marketplace Admin
const navigation = [
    {
        section: 'Overview',
        items: [
            {
                id: 'dashboard',
                label: 'Dashboard',
                routeName: 'admin.dashboard',
                icon: 'dashboard',
                badge: 'Live',
                badgeVariant: 'emerald',
            },
        ],
    },
    {
        section: 'E-Commerce Store',
        items: [
            {
                id: 'products_menu',
                label: 'Products Catalog',
                icon: 'products',
                children: [
                    { label: 'All Products', routeName: 'admin.products.index' },
                    { label: 'Add New Product', routeName: 'admin.products.create' },
                    { label: 'Categories', routeName: 'admin.categories.index' },
                ],
            },
            {
                id: 'orders_menu',
                label: 'Orders & Sales',
                icon: 'orders',
                routeName: 'admin.orders.index',
                children: [
                    { label: 'All Customer Orders', routeName: 'admin.orders.index' },
                ],
            },
            {
                id: 'customers_menu',
                label: 'Customers & Users',
                icon: 'crm',
                children: [
                    { label: 'Customers', routeName: 'admin.customers.index' },
                    { label: 'Admin Staff Users', routeName: 'admin.users.index' },
                    { label: 'Product Reviews', routeName: 'admin.reviews.index' },
                ],
            },
        ],
    },
    {
        section: 'Marketing & Content',
        items: [
            {
                id: 'content',
                label: 'Promotions & Media',
                icon: 'content',
                children: [
                    { label: 'Hero Banners & Sliders', routeName: 'admin.content.banners' },
                    { label: 'Beauty Blog Articles', routeName: 'admin.blogs.index' },
                ],
            },
            {
                id: 'settings',
                label: 'Store Settings',
                icon: 'settings',
                routeName: 'admin.settings.index',
            },
        ],
    },
];

// Automatically open the parent submenu that has an active child
const initializeActiveMenus = () => {
    navigation.forEach(group => {
        group.items.forEach(item => {
            if (item.children) {
                if (isParentActive(item)) {
                    openMenus.value[item.id] = true;
                }
            }
        });
    });
};

// Toggle an accordion menu
const toggleMenu = (menuId) => {
    openMenus.value[menuId] = !openMenus.value[menuId];
};

// Filtered navigation based on search input
const filteredNavigation = computed(() => {
    if (!searchQuery.value.trim()) return navigation;
    const q = searchQuery.value.toLowerCase();

    return navigation.map(group => {
        const filteredItems = group.items.map(item => {
            // If item has children, check parent or children match
            if (item.children) {
                const matchingChildren = item.children.filter(child =>
                    child.label.toLowerCase().includes(q)
                );
                if (matchingChildren.length > 0 || item.label.toLowerCase().includes(q)) {
                    return {
                        ...item,
                        children: matchingChildren.length > 0 ? matchingChildren : item.children,
                    };
                }
                return null;
            } else {
                return item.label.toLowerCase().includes(q) ? item : null;
            }
        }).filter(Boolean);

        return {
            ...group,
            items: filteredItems,
        };
    }).filter(group => group.items.length > 0);
});

// Watch search to auto expand matching menus
watch(searchQuery, (newVal) => {
    if (newVal.trim()) {
        navigation.forEach(group => {
            group.items.forEach(item => {
                if (item.children) openMenus.value[item.id] = true;
            });
        });
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-900/5 flex flex-col font-sans text-slate-800 antialiased selection:bg-rose-500 selection:text-white">
        <div class="flex flex-1 min-h-screen">
            <!-- Mobile Sidebar Backdrop -->
            <transition
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="sidebarMobileOpen"
                    class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden"
                    @click="closeMobileSidebar"
                ></div>
            </transition>

            <!-- Ultra-Modern Multi-Level Sidebar -->
            <aside
                :class="[
                    'fixed inset-y-0 left-0 z-50 h-screen bg-[#090d16] border-r border-slate-800/80 flex flex-col justify-between transition-all duration-300 ease-in-out shadow-2xl lg:shadow-none select-none',
                    sidebarMobileOpen ? 'translate-x-0 w-72' : '-translate-x-full',
                    'lg:translate-x-0',
                    sidebarCollapsed ? 'lg:w-[76px]' : 'lg:w-72'
                ]"
            >
                <!-- Ambient Ambient Glow Accents -->
                <div class="pointer-events-none absolute -top-24 -left-24 h-56 w-56 rounded-full bg-rose-600/10 blur-3xl"></div>
                <div class="pointer-events-none absolute top-1/2 -right-24 h-48 w-48 rounded-full bg-pink-600/5 blur-3xl"></div>

                <!-- Main Container -->
                <div class="flex flex-col h-full overflow-hidden relative z-10">
                    <!-- Brand Header -->
                    <div
                        class="p-4 border-b border-slate-800/70 bg-[#090d16]/95 backdrop-blur-md flex items-center shrink-0 transition-all duration-300"
                        :class="sidebarCollapsed ? 'justify-center' : 'justify-between'"
                    >
                        <Link
                            :href="route('admin.dashboard')"
                            class="flex items-center gap-3 group min-w-0"
                            @click="closeMobileSidebar"
                            :title="sidebarCollapsed ? 'BeautyBook CRM Hub' : ''"
                        >
                            <!-- Faceted Brand Logo -->
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-rose-600 via-pink-500 to-rose-700 shadow-lg shadow-rose-500/25 ring-1 ring-white/20 transition-all duration-300 group-hover:scale-105 group-hover:shadow-rose-500/40 shrink-0">
                                <span class="font-serif font-black text-white text-lg tracking-wider">B</span>
                                <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 border-2 border-[#090d16]"></span>
                                </span>
                            </div>

                            <!-- Brand Name -->
                            <div
                                v-show="!sidebarCollapsed || sidebarMobileOpen"
                                class="flex flex-col min-w-0 transition-opacity duration-300"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span class="font-black text-white text-[15px] tracking-tight group-hover:text-rose-300 transition-colors truncate">
                                        BeautyBook
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded-md bg-gradient-to-r from-rose-500/20 to-pink-500/20 text-rose-300 text-[9px] font-extrabold uppercase tracking-wider border border-rose-500/30">
                                        PRO
                                    </span>
                                </div>
                                <span class="text-[11px] font-medium text-slate-400 flex items-center gap-1 truncate">
                                    <span>Control Center</span>
                                    <span class="inline-block w-1 h-1 rounded-full bg-slate-600"></span>
                                    <span class="text-rose-400 font-semibold">Admin</span>
                                </span>
                            </div>
                        </Link>

                        <!-- Desktop Collapse Toggle -->
                        <button
                            @click="toggleSidebar"
                            class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 border border-slate-800 hover:border-slate-700 transition-all cursor-pointer shadow-xs active:scale-95"
                            :title="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
                        >
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <!-- Mobile Close Button -->
                        <button
                            @click="closeMobileSidebar"
                            class="lg:hidden p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- In-Sidebar Quick Filter Search Bar -->
                    <div
                        v-show="!sidebarCollapsed || sidebarMobileOpen"
                        class="px-3.5 pt-3 pb-1 shrink-0"
                    >
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search menu & submenus..."
                                class="w-full pl-8 pr-7 py-1.5 text-[11px] rounded-lg bg-slate-900/90 border border-slate-800/90 text-slate-200 placeholder-slate-500 focus:bg-slate-900 focus:ring-1 focus:ring-rose-500/40 focus:border-rose-500/50 transition-all placeholder:text-[11px]"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute inset-y-0 right-0 flex items-center pr-2 text-slate-500 hover:text-slate-300"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Items List (Scrollable) -->
                    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-4 scrollbar-thin scrollbar-thumb-slate-800 scrollbar-track-transparent">
                        <div
                            v-if="filteredNavigation.length === 0"
                            class="px-3 py-8 text-center"
                        >
                            <p class="text-xs text-slate-500">No matching menu items</p>
                            <button
                                @click="searchQuery = ''"
                                class="mt-2 text-[11px] text-rose-400 hover:underline"
                            >
                                Clear search
                            </button>
                        </div>

                        <div
                            v-for="(group, gIdx) in filteredNavigation"
                            :key="gIdx"
                            class="space-y-1"
                        >
                            <!-- Section Title -->
                            <div
                                v-show="!sidebarCollapsed || sidebarMobileOpen"
                                class="px-3 pt-2.5 pb-1 flex items-center justify-between"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500/90">
                                    {{ group.section }}
                                </span>
                            </div>

                            <div v-if="sidebarCollapsed && !sidebarMobileOpen" class="w-full h-px bg-slate-800/60 my-2"></div>

                            <!-- Menu Items (Direct Link or Accordion Parent with Submenus) -->
                            <div
                                v-for="item in group.items"
                                :key="item.id || item.label"
                                class="space-y-1 relative"
                            >
                                <!-- Option 1: Direct Single Item Link (e.g. Dashboard) -->
                                <Link
                                    v-if="!item.children"
                                    :href="getRouteUrl(item.routeName)"
                                    @click="closeMobileSidebar"
                                    class="flex items-center rounded-xl text-xs transition-all duration-200 group relative select-none"
                                    :class="[
                                        isRouteActive(item.routeName)
                                            ? 'bg-gradient-to-r from-rose-500/20 via-rose-500/10 to-transparent text-white font-semibold border border-rose-500/30 shadow-xs shadow-rose-500/10'
                                            : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 font-medium border border-transparent',
                                        sidebarCollapsed && !sidebarMobileOpen
                                            ? 'justify-center p-2.5'
                                            : 'justify-between px-3 py-2.5'
                                    ]"
                                >
                                    <!-- Active Bar -->
                                    <span
                                        v-if="isRouteActive(item.routeName)"
                                        class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-1 rounded-r-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]"
                                    ></span>

                                    <div class="flex items-center gap-3 min-w-0">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-lg transition-all shrink-0"
                                            :class="isRouteActive(item.routeName)
                                                ? 'bg-gradient-to-tr from-rose-600 to-pink-600 text-white shadow-xs shadow-rose-600/30'
                                                : 'text-slate-400 group-hover:text-rose-400 group-hover:bg-slate-800/80 bg-slate-900/60 border border-slate-800/80'"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                        </span>
                                        <span v-show="!sidebarCollapsed || sidebarMobileOpen" class="truncate font-medium">
                                            {{ item.label }}
                                        </span>
                                    </div>

                                    <span
                                        v-if="item.badge && (!sidebarCollapsed || sidebarMobileOpen)"
                                        class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/40"
                                    >
                                        {{ item.badge }}
                                    </span>

                                    <!-- Collapsed Tooltip -->
                                    <div
                                        v-if="sidebarCollapsed && !sidebarMobileOpen"
                                        class="fixed left-20 px-3 py-1.5 rounded-xl bg-slate-900/95 text-white text-xs font-semibold whitespace-nowrap shadow-2xl border border-slate-700/80 pointer-events-none opacity-0 group-hover:opacity-100 transition-all duration-200 z-50 ml-2 backdrop-blur-md flex items-center gap-2"
                                    >
                                        <span>{{ item.label }}</span>
                                        <span v-if="item.badge" class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-500/30 text-emerald-300">
                                            {{ item.badge }}
                                        </span>
                                    </div>
                                </Link>

                                <!-- Option 2: Parent Menu with Submenus (Collapsible Accordion) -->
                                <div v-else class="relative group">
                                    <!-- Parent Button (Expanded View) -->
                                    <button
                                        v-if="!sidebarCollapsed || sidebarMobileOpen"
                                        type="button"
                                        @click="toggleMenu(item.id)"
                                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-200 group select-none cursor-pointer"
                                        :class="[
                                            isParentActive(item)
                                                ? 'text-rose-300 font-semibold bg-rose-500/10 border border-rose-500/20'
                                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50 font-medium border border-transparent'
                                        ]"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span
                                                class="flex h-7 w-7 items-center justify-center rounded-lg transition-all shrink-0"
                                                :class="isParentActive(item)
                                                    ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40 shadow-xs'
                                                    : 'text-slate-400 group-hover:text-rose-400 group-hover:bg-slate-800/80 bg-slate-900/60 border border-slate-800/80'"
                                            >
                                                <!-- CRM -->
                                                <svg v-if="item.icon === 'crm'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <!-- Products / Store -->
                                                <svg v-else-if="item.icon === 'products'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                                <!-- Finance -->
                                                <svg v-else-if="item.icon === 'finance'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <!-- Chat / Communication -->
                                                <svg v-else-if="item.icon === 'chat'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                                <!-- Content -->
                                                <svg v-else-if="item.icon === 'content'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                                </svg>
                                                <!-- Settings -->
                                                <svg v-else-if="item.icon === 'settings'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </span>

                                            <span class="truncate font-medium">
                                                {{ item.label }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-1.5">
                                            <span
                                                v-if="item.badge"
                                                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/40"
                                            >
                                                {{ item.badge }}
                                            </span>

                                            <!-- Chevron Indicator -->
                                            <svg
                                                class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200"
                                                :class="{ 'rotate-90 text-rose-400': openMenus[item.id] }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </button>

                                    <!-- Collapsed Trigger Button (Desktop Collapsed Mode) -->
                                    <div
                                        v-else
                                        class="flex items-center justify-center p-2.5 rounded-xl cursor-pointer transition-all duration-200"
                                        :class="[
                                            isParentActive(item)
                                                ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
                                                : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50'
                                        ]"
                                    >
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg">
                                            <svg v-if="item.icon === 'crm'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                            <svg v-else-if="item.icon === 'products'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                            <svg v-else-if="item.icon === 'finance'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <svg v-else-if="item.icon === 'chat'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                            <svg v-else-if="item.icon === 'content'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                                            <svg v-else-if="item.icon === 'settings'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </span>

                                        <!-- Collapsed Hover Submenu Flyout -->
                                        <div
                                            class="fixed left-20 py-2 w-56 rounded-2xl bg-slate-900/95 text-white shadow-2xl border border-slate-700/80 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-200 z-50 ml-2 backdrop-blur-xl"
                                        >
                                            <div class="px-3 py-1.5 border-b border-slate-800/80 mb-1">
                                                <p class="text-[11px] font-bold text-rose-300 uppercase tracking-wider">{{ item.label }}</p>
                                            </div>
                                            <div class="max-h-64 overflow-y-auto px-1.5 space-y-0.5">
                                                <Link
                                                    v-for="child in item.children"
                                                    :key="child.routeName"
                                                    :href="getRouteUrl(child.routeName)"
                                                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors"
                                                    :class="isRouteActive(child.routeName) ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'"
                                                >
                                                    <span>{{ child.label }}</span>
                                                    <span v-if="child.badge" class="px-1.5 py-0.2 rounded text-[8px] font-bold uppercase bg-rose-500/30 text-rose-200">
                                                        {{ child.badge }}
                                                    </span>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submenu Accordion Drawer (Expanded View) -->
                                    <transition
                                        enter-active-class="transition-all duration-200 ease-out"
                                        enter-from-class="opacity-0 max-h-0 overflow-hidden"
                                        enter-to-class="opacity-100 max-h-96"
                                        leave-active-class="transition-all duration-150 ease-in"
                                        leave-from-class="opacity-100 max-h-96"
                                        leave-to-class="opacity-0 max-h-0 overflow-hidden"
                                    >
                                        <div
                                            v-show="openMenus[item.id] && (!sidebarCollapsed || sidebarMobileOpen)"
                                            class="pl-6 pr-1 pt-1 pb-1 space-y-1 relative"
                                        >
                                            <!-- Vertical Connecting Guideline -->
                                            <div class="absolute left-6 top-1 bottom-2 w-px bg-slate-800/80"></div>

                                            <!-- Submenu Links -->
                                            <Link
                                                v-for="child in item.children"
                                                :key="child.routeName"
                                                :href="getRouteUrl(child.routeName)"
                                                @click="closeMobileSidebar"
                                                class="flex items-center justify-between pl-4 pr-2.5 py-1.5 rounded-lg text-xs transition-all group/sub relative"
                                                :class="[
                                                    isRouteActive(child.routeName)
                                                        ? 'bg-rose-500/15 text-rose-200 font-semibold border-l-2 border-l-rose-500 shadow-2xs'
                                                        : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/40 font-medium'
                                                ]"
                                            >
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <!-- Submenu Bullet Dot -->
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full transition-all shrink-0"
                                                        :class="isRouteActive(child.routeName)
                                                            ? 'bg-rose-400 scale-125 shadow-[0_0_6px_rgba(251,113,133,0.9)]'
                                                            : 'bg-slate-600 group-hover/sub:bg-slate-400'"
                                                    ></span>
                                                    <span class="truncate">{{ child.label }}</span>
                                                </div>

                                                <span
                                                    v-if="child.badge"
                                                    class="px-1.5 py-0.2 rounded text-[8px] font-bold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30"
                                                >
                                                    {{ child.badge }}
                                                </span>
                                            </Link>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <!-- Bottom User & System Widget -->
                    <div class="p-3 border-t border-slate-800/80 bg-[#070a12]/95 backdrop-blur-md shrink-0">
                        <div
                            class="flex items-center rounded-xl bg-slate-900/70 border border-slate-800/90 p-2.5 transition-all"
                            :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center' : 'justify-between'"
                        >
                            <!-- User Details -->
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="relative shrink-0">
                                    <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-rose-500 via-pink-600 to-rose-700 flex items-center justify-center font-bold text-white text-xs shadow-md ring-1 ring-white/20">
                                        {{ userName.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-[#090d16]"></span>
                                </div>

                                <div
                                    v-show="!sidebarCollapsed || sidebarMobileOpen"
                                    class="min-w-0 flex flex-col"
                                >
                                    <p class="text-xs font-bold text-slate-200 truncate leading-tight">{{ userName }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                        <p class="text-[10px] font-medium text-slate-400 truncate">{{ userRole }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- User Actions -->
                            <div
                                v-show="!sidebarCollapsed || sidebarMobileOpen"
                                class="flex items-center gap-1 shrink-0"
                            >
                                <Link
                                    :href="route('profile.edit')"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all cursor-pointer"
                                    title="Account Settings"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </Link>

                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all cursor-pointer"
                                    title="Sign out"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Right Content Wrapper -->
            <div
                :class="[
                    'flex-1 flex flex-col min-w-0 min-h-screen bg-slate-50/70 transition-all duration-300',
                    sidebarCollapsed ? 'lg:pl-[76px]' : 'lg:pl-72'
                ]"
            >
                <!-- Top SaaS CRM Header -->
                <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-xl border-b border-slate-200/80 shadow-xs">
                    <div class="px-4 sm:px-8 py-3 flex items-center justify-between gap-4">
                        <!-- Left Header: Toggle & Search Command Bar -->
                        <div class="flex items-center gap-3 sm:gap-4 flex-1 max-w-lg min-w-0">
                            <!-- Universal Side Menu Toggle Button -->
                            <button
                                @click="toggleSidebar"
                                class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/20 transition-all cursor-pointer shadow-xs active:scale-95 flex items-center justify-center shrink-0"
                                :title="sidebarCollapsed ? 'Expand Side Menu' : 'Collapse Side Menu'"
                                aria-label="Toggle side menu"
                            >
                                <svg class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14" />
                                </svg>
                            </button>

                            <!-- Modern Search Input Bar -->
                            <div class="relative w-full hidden sm:block">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    placeholder="Search products, orders, customers..."
                                    class="w-full pl-9 pr-12 py-1.5 text-xs rounded-xl bg-slate-100/70 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                                />
                                <span class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none">
                                    <kbd class="px-1.5 py-0.5 text-[9px] font-semibold text-slate-400 bg-white border border-slate-200 rounded-md shadow-2xs">Ctrl K</kbd>
                                </span>
                            </div>
                        </div>

                        <!-- Right Header Actions -->
                        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                            <!-- Quick Action Dropdown -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 shadow-sm shadow-rose-500/20 transition-all cursor-pointer">
                                        <span>+ Quick Action</span>
                                        <svg class="w-3 h-3 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('admin.products.index')">
                                        Manage Products & Inventory
                                    </DropdownLink>
                                    <DropdownLink :href="route('admin.products.orders')">
                                        View Product Orders
                                    </DropdownLink>
                                    <DropdownLink :href="route('admin.notifications.create')">
                                        Send Broadcast Alert
                                    </DropdownLink>
                                    <DropdownLink :href="route('admin.content.banners')">
                                        Manage Promotional Banners
                                    </DropdownLink>
                                    <DropdownLink :href="route('admin.settings.backups')">
                                        Generate System Backup
                                    </DropdownLink>
                                </template>
                            </Dropdown>

                            <NotificationBell />

                            <!-- Public Storefront Preview -->
                            <Link
                                :href="route('home')"
                                target="_blank"
                                class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all"
                            >
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span>Live Portal</span>
                            </Link>

                            <!-- User Profile Dropdown -->
                            <Dropdown align="right" width="56">
                                <template #trigger>
                                    <button class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer">
                                        <div class="h-8 w-8 rounded-lg bg-gradient-to-tr from-rose-600 to-pink-600 flex items-center justify-center font-bold text-white text-xs shadow-xs">
                                            {{ userName.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="hidden lg:flex flex-col text-left">
                                            <span class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[120px]">{{ userName }}</span>
                                            <span class="text-[10px] text-slate-400 leading-tight">{{ userRole }}</span>
                                        </div>
                                        <svg class="hidden lg:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="px-4 py-2.5 border-b border-slate-100">
                                        <p class="text-xs font-bold text-slate-800">{{ userName }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ userEmail }}</p>
                                    </div>
                                    <DropdownLink :href="route('profile.edit')">
                                        Account Settings
                                    </DropdownLink>
                                    <DropdownLink :href="route('admin.settings.index')">
                                        System Configuration
                                    </DropdownLink>
                                    <div class="border-t border-slate-100"></div>
                                    <DropdownLink :href="route('logout')" method="post" as="button" class="text-rose-600 font-semibold hover:bg-rose-50">
                                        Sign out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    <!-- Global Flash Alerts -->
                    <div v-if="flashSuccess" class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="p-1 rounded-lg bg-emerald-100 text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span>{{ flashSuccess }}</span>
                        </div>
                    </div>

                    <div v-if="flashError" class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="p-1 rounded-lg bg-rose-100 text-rose-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                            <span>{{ flashError }}</span>
                        </div>
                    </div>

                    <slot name="header" />
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
