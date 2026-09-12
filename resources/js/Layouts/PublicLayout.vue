<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import Navbar from '@/Components/Navbar.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const siteSettings = computed(() => page.props.site_settings || {});

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
</script>

<template>
    <div class="page-shell min-h-screen text-slate-800 flex flex-col justify-between relative bg-gradient-to-b from-[#FFF7F8] via-[#FFF1F3] to-[#FDF2F4] selection:bg-rose-500 selection:text-white">
        <!-- Floating Ambient Glow Accents -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden opacity-50 select-none">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-pink-400/20 blur-3xl"></div>
            <div class="absolute top-1/4 -right-24 w-96 h-96 rounded-full bg-amber-300/20 blur-3xl"></div>
            <div class="absolute bottom-10 -left-20 w-80 h-80 rounded-full bg-rose-500/15 blur-3xl"></div>
        </div>

        <Navbar />

        <main class="flex-grow pt-20 sm:pt-28 pb-28 sm:pb-24 relative z-10">
            <slot />
        </main>

        <!-- Floating Mobile Bottom Navigation Dock -->
        <div class="fixed bottom-2.5 inset-x-3 sm:inset-x-8 z-50 lg:hidden pointer-events-none">
            <nav class="pointer-events-auto mx-auto max-w-md rounded-full p-1.5 shadow-[0_10px_35px_rgba(225,29,72,0.18)] border border-white/90 bg-white/95 backdrop-blur-2xl flex items-center justify-around ring-1 ring-rose-500/10">
                <!-- 1. Home -->
                <Link
                    :href="route('home')"
                    class="group relative flex flex-col items-center justify-center py-1.5 px-3 rounded-full transition-all duration-300"
                    :class="route().current('home') ? 'text-rose-600 font-bold bg-rose-50' : 'text-slate-500 hover:text-slate-900'"
                >
                    <span class="text-base">🏠</span>
                    <span class="text-[10px] tracking-tight mt-0.5 leading-none font-medium">Home</span>
                </Link>

                <!-- 2. Store / Catalog -->
                <Link
                    :href="route('products.index')"
                    class="group relative flex flex-col items-center justify-center py-1.5 px-3 rounded-full transition-all duration-300"
                    :class="route().current('products.index') ? 'text-rose-600 font-bold bg-rose-50' : 'text-slate-500 hover:text-slate-900'"
                >
                    <span class="text-base">🛍️</span>
                    <span class="text-[10px] tracking-tight mt-0.5 leading-none font-medium">Shop</span>
                </Link>

                <!-- 3. Cart / Bag -->
                <Link
                    :href="route('products.index', { cart: 'open' })"
                    class="group relative flex flex-col items-center justify-center py-1.5 px-3 rounded-full transition-all duration-300"
                    :class="route().current('products.*') && page.url.includes('cart=open') ? 'text-rose-600 font-bold bg-rose-50' : 'text-slate-500 hover:text-slate-900'"
                >
                    <div class="relative flex items-center justify-center text-base">
                        <span>🛒</span>
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-1 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-white text-[8px] font-black ring-1 ring-white"
                        >
                            {{ cartCount > 99 ? '99+' : cartCount }}
                        </span>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5 leading-none font-medium">Bag</span>
                </Link>

                <!-- 4. Wishlist -->
                <Link
                    v-if="user"
                    :href="route('customer.wishlist.index')"
                    class="group relative flex flex-col items-center justify-center py-1.5 px-3 rounded-full transition-all duration-300"
                    :class="route().current('customer.wishlist.*') ? 'text-rose-600 font-bold bg-rose-50' : 'text-slate-500 hover:text-slate-900'"
                >
                    <span class="text-base">❤️</span>
                    <span class="text-[10px] tracking-tight mt-0.5 leading-none font-medium">Saved</span>
                </Link>

                <!-- 5. Account / Login -->
                <Link
                    :href="user ? route('dashboard') : route('login')"
                    class="group relative flex flex-col items-center justify-center py-1.5 px-3 rounded-full transition-all duration-300"
                    :class="route().current('dashboard') || route().current('customer.*') || route().current('admin.*') ? 'text-rose-600 font-bold bg-rose-50' : 'text-slate-500 hover:text-slate-900'"
                >
                    <div class="relative flex items-center justify-center text-base">
                        <span>👤</span>
                        <span v-if="user" class="absolute -top-0.5 -right-1 h-2 w-2 rounded-full bg-emerald-500 border border-white"></span>
                    </div>
                    <span class="text-[10px] tracking-tight mt-0.5 leading-none font-medium">
                        {{ user ? (user.name ? user.name.split(' ')[0] : 'Account') : 'Sign In' }}
                    </span>
                </Link>
            </nav>
        </div>

        <!-- Luxury Marketplace Footer -->
        <footer class="relative mt-12 border-t border-pink-200/80 bg-white/90 backdrop-blur-xl py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 pb-10 border-b border-pink-100">
                    <!-- Brand & Mission -->
                    <div class="space-y-3.5">
                        <Link :href="route('home')" class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-rose-600 via-pink-600 to-amber-500 text-white font-serif font-black text-base shadow-md shadow-pink-900/20">
                                {{ (siteSettings.site_name || 'L').charAt(0) }}
                            </div>
                            <div>
                                <span class="font-serif text-base font-bold tracking-tight text-slate-900">
                                    {{ siteSettings.site_name || 'Luxe Market' }}
                                </span>
                                <p class="text-[10px] text-rose-500 font-medium">
                                    {{ siteSettings.site_tagline || '100% Genuine Cosmetics Marketplace' }}
                                </p>
                            </div>
                        </Link>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            {{ siteSettings.site_description || 'Pakistan’s premier multi-brand luxury cosmetics and skincare marketplace delivering original, verified beauty essentials to your doorstep.' }}
                        </p>
                        
                        <!-- Social Channel Icons -->
                        <div class="flex items-center gap-2 pt-1">
                            <a
                                v-if="siteSettings.social_instagram"
                                :href="siteSettings.social_instagram"
                                target="_blank"
                                class="h-8 w-8 rounded-full bg-pink-50 hover:bg-rose-500 hover:text-white text-rose-600 border border-pink-200 flex items-center justify-center text-xs transition"
                                title="Instagram"
                            >
                                📸
                            </a>
                            <a
                                v-if="siteSettings.social_facebook"
                                :href="siteSettings.social_facebook"
                                target="_blank"
                                class="h-8 w-8 rounded-full bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-600 border border-blue-200 flex items-center justify-center text-xs transition"
                                title="Facebook"
                            >
                                🌐
                            </a>
                            <a
                                v-if="siteSettings.social_tiktok"
                                :href="siteSettings.social_tiktok"
                                target="_blank"
                                class="h-8 w-8 rounded-full bg-slate-100 hover:bg-black hover:text-white text-slate-800 border border-slate-200 flex items-center justify-center text-xs transition"
                                title="TikTok"
                            >
                                🎵
                            </a>
                            <a
                                v-if="siteSettings.support_whatsapp"
                                :href="`https://wa.me/${siteSettings.support_whatsapp.replace(/[^0-9]/g, '')}`"
                                target="_blank"
                                class="h-8 w-8 rounded-full bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-600 border border-emerald-200 flex items-center justify-center text-xs transition"
                                title="WhatsApp"
                            >
                                💬
                            </a>
                        </div>
                    </div>

                    <!-- Category Quick Links -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Popular Categories</h4>
                        <ul class="space-y-1.5 text-xs text-slate-600">
                            <li><Link :href="route('products.index', { category: 'skincare' })" class="hover:text-rose-600 transition">✨ Skincare & Serums</Link></li>
                            <li><Link :href="route('products.index', { category: 'makeup-cosmetics' })" class="hover:text-rose-600 transition">💄 Makeup & Lipsticks</Link></li>
                            <li><Link :href="route('products.index', { category: 'haircare-styling' })" class="hover:text-rose-600 transition">💇‍♀️ Haircare & Bond Repair</Link></li>
                            <li><Link :href="route('products.index', { category: 'fragrances-perfumes' })" class="hover:text-rose-600 transition">🌸 Luxury Perfumes & Mists</Link></li>
                            <li><Link :href="route('products.index', { category: 'organic-herbal' })" class="hover:text-rose-600 transition">🌿 Organic & Pure Oils</Link></li>
                        </ul>
                    </div>

                    <!-- Account & Customer Care -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Customer Care</h4>
                        <ul class="space-y-1.5 text-xs text-slate-600">
                            <li><Link :href="route('login')" class="hover:text-rose-600 transition">My Account / Order History</Link></li>
                            <li><Link :href="route('products.index', { cart: 'open' })" class="hover:text-rose-600 transition">Shopping Bag & Checkout</Link></li>
                            <li><Link :href="route('blogs.index')" class="hover:text-rose-600 transition">📖 Beauty Magazine & Tips</Link></li>
                            <li><Link :href="route('products.index', { trending: 1 })" class="hover:text-rose-600 transition font-semibold text-rose-600">🔥 Flash Deals & Discounts</Link></li>
                        </ul>
                    </div>

                    <!-- Contact & Office -->
                    <div class="space-y-2.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Helpdesk & Support</h4>
                        <div class="space-y-2 text-xs text-slate-700">
                            <a
                                :href="`mailto:${siteSettings.contact_email || 'support@luxemarket.pk'}`"
                                class="flex items-center gap-2 hover:text-rose-600 transition font-medium"
                            >
                                <span class="text-rose-500 text-sm">📧</span>
                                <span>{{ siteSettings.contact_email || 'support@luxemarket.pk' }}</span>
                            </a>
                            <a
                                :href="`tel:${(siteSettings.contact_phone || '+923001234567').replace(/[^0-9+]/g, '')}`"
                                class="flex items-center gap-2 hover:text-rose-600 transition font-medium"
                            >
                                <span class="text-rose-500 text-sm">📞</span>
                                <span>{{ siteSettings.contact_phone || '+92 (300) 123-4567' }}</span>
                            </a>
                            <a
                                v-if="siteSettings.support_whatsapp"
                                :href="`https://wa.me/${siteSettings.support_whatsapp.replace(/[^0-9]/g, '')}`"
                                target="_blank"
                                class="flex items-center gap-2 text-emerald-700 hover:text-emerald-800 transition font-bold"
                            >
                                <span class="text-emerald-500 text-sm">💬</span>
                                <span>WhatsApp: {{ siteSettings.support_whatsapp }}</span>
                            </a>
                            <div v-if="siteSettings.office_address" class="flex items-start gap-2 text-[11px] text-slate-500 pt-1">
                                <span class="text-rose-400 mt-0.5 text-xs">📍</span>
                                <span>{{ siteSettings.office_address }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Copyright Strip -->
                <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                    <p>&copy; {{ new Date().getFullYear() }} {{ siteSettings.site_name || 'Luxe Beauty Market' }}. All rights reserved.</p>
                    <div class="flex items-center gap-3 text-[11px] text-slate-500">
                        <span>Karachi</span> &bull;
                        <span>Lahore</span> &bull;
                        <span>Islamabad</span> &bull;
                        <span>Rawalpindi</span> &bull;
                        <span>Faisalabad</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
