<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import axios from 'axios';
import { detectCurrentAddress } from '@/Utils/geolocation';

const page = usePage();


const authUser = computed(() => page.props.auth?.user);

const props = defineProps({
    products: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    },
    priceStats: {
        type: Object,
        default: () => ({ min: 500, max: 25000 })
    },
    trendingProducts: {
        type: Array,
        default: () => []
    },
    totalProductsCount: {
        type: Number,
        default: 0
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    shipping_settings: {
        type: Object,
        default: () => ({
            standard_fee: 250,
            free_threshold: 3000,
            free_enabled: true,
            carrier_name: 'Standard Express Beauty Courier',
            estimated_days: '2 - 4 Business Days'
        })
    }
});

// Layout & View states
const viewMode = ref('grid'); // 'grid' | 'list'
const showMobileFilters = ref(false);
const isCartOpen = ref(false);
const quickViewProduct = ref(null);
const quickViewQty = ref(1);
const showCheckoutModal = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
let toastTimer = null;

// Filter Reactive States
const searchInput = ref(props.filters.search || '');
const activeCategory = ref(props.filters.category || '');
const activeBrand = ref(props.filters.brand || '');
const sortBy = ref(props.filters.sort_by || 'recommended');
const minPrice = ref(props.filters.min_price || '');
const maxPrice = ref(props.filters.max_price || '');
const minRating = ref(props.filters.min_rating || '');
const trendingOnly = ref(props.filters.trending || false);
const inStockOnly = ref(props.filters.in_stock || false);
const isFiltering = ref(false);

// Local Wishlist & Cart states
const wishlist = ref({});
const cart = ref([]);

onMounted(() => {
    try {
        const savedWishlist = localStorage.getItem('beautybook_wishlist');
        if (savedWishlist) wishlist.value = JSON.parse(savedWishlist);
        
        const savedCart = localStorage.getItem('beautybook_cart');
        if (savedCart) cart.value = JSON.parse(savedCart);

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('cart') === 'open') {
            isCartOpen.value = true;
        }
    } catch (e) {
        console.error('Storage error', e);
    }
});

const saveWishlist = () => {
    localStorage.setItem('beautybook_wishlist', JSON.stringify(wishlist.value));
};

const saveCart = () => {
    localStorage.setItem('beautybook_cart', JSON.stringify(cart.value));
};

const triggerToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastMessage.value = '';
    }, 3500);
};

const toggleWishlist = (product) => {
    if (wishlist.value[product.id]) {
        delete wishlist.value[product.id];
        triggerToast(`Removed "${product.name}" from your wishlist`, 'info');
    } else {
        wishlist.value[product.id] = {
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image_url || product.image,
            brand: product.brand
        };
        triggerToast(`Added "${product.name}" to your wishlist! ❤️`, 'success');
    }
    saveWishlist();
};

const addToCart = (product, quantity = 1) => {
    const existingIndex = cart.value.findIndex(item => item.id === product.id);
    if (existingIndex > -1) {
        cart.value[existingIndex].quantity += quantity;
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            slug: product.slug,
            price: parseFloat(product.price) || 0,
            original_price: parseFloat(product.original_price) || parseFloat(product.price) || 0,
            image: product.image_url || product.image,
            brand: product.brand || 'Salon Pro',
            category: product.category,
            quantity: quantity,
            in_stock: product.in_stock !== false
        });
    }
    saveCart();
    triggerToast(`Added ${quantity}x "${product.name}" to your bag! 🛍️`, 'success');
};

const updateCartQty = (productId, delta) => {
    const item = cart.value.find(i => i.id === productId);
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();
        }
    }
};

const removeFromCart = (productId) => {
    cart.value = cart.value.filter(i => i.id !== productId);
    saveCart();
    triggerToast('Item removed from your bag', 'info');
};

const clearCart = () => {
    cart.value = [];
    saveCart();
};

// Cart Calculations
const cartTotalCount = computed(() => {
    return cart.value.reduce((acc, item) => acc + item.quantity, 0);
});

const cartSubtotal = computed(() => {
    return cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0);
});

// Dynamic Admin Shipping Configuration
const shippingSettings = computed(() => ({
    standard_fee: Number(props.shipping_settings?.standard_fee ?? 250),
    free_threshold: Number(props.shipping_settings?.free_threshold ?? 3000),
    free_enabled: Boolean(props.shipping_settings?.free_enabled ?? true),
    carrier_name: props.shipping_settings?.carrier_name || 'Standard Express Beauty Courier',
    estimated_days: props.shipping_settings?.estimated_days || '2 - 4 Business Days',
}));

const freeShippingThreshold = computed(() => shippingSettings.value.free_threshold);
const isFreeShippingEnabled = computed(() => shippingSettings.value.free_enabled);

const shippingCost = computed(() => {
    if (cartSubtotal.value === 0) return 0;
    if (isFreeShippingEnabled.value && cartSubtotal.value >= freeShippingThreshold.value) {
        return 0;
    }
    return shippingSettings.value.standard_fee;
});

const cartTotal = computed(() => {
    return cartSubtotal.value + shippingCost.value;
});

const freeShippingProgress = computed(() => {
    if (!isFreeShippingEnabled.value || freeShippingThreshold.value <= 0) return 100;
    if (cartSubtotal.value >= freeShippingThreshold.value) return 100;
    return Math.min(100, Math.round((cartSubtotal.value / freeShippingThreshold.value) * 100));
});

const amountToFreeShipping = computed(() => {
    if (!isFreeShippingEnabled.value) return 0;
    return Math.max(0, freeShippingThreshold.value - cartSubtotal.value);
});

// Price Formatting
const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(price || 0);
};

// Sort Options
const sortOptions = [
    { value: 'recommended', label: '✨ Curated & Recommended' },
    { value: 'trending', label: '🔥 Best Sellers & Trending' },
    { value: 'price_asc', label: '🏷️ Price: Low to High' },
    { value: 'price_desc', label: '💎 Price: High to Low' },
    { value: 'rating_desc', label: '⭐ Highest Customer Rating' },
    { value: 'discount_desc', label: '💥 Biggest Discounts' },
    { value: 'newest', label: '🆕 New Arrivals' }
];

// Price Presets
const pricePresets = [
    { label: 'All Prices', min: '', max: '' },
    { label: '< PKR 2.5k', min: '', max: '2500' },
    { label: '2.5k - 5k', min: '2500', max: '5000' },
    { label: '5k - 10k', min: '5000', max: '10000' },
    { label: 'PKR 10k+', min: '10000', max: '' }
];

// Active Filter Tags
const activeFilterTags = computed(() => {
    const tags = [];
    if (searchInput.value) {
        tags.push({ key: 'search', label: `Search: "${searchInput.value}"` });
    }
    if (activeCategory.value && activeCategory.value !== 'all') {
        tags.push({ key: 'category', label: `Category: ${activeCategory.value}` });
    }
    if (activeBrand.value && activeBrand.value !== 'all') {
        tags.push({ key: 'brand', label: `Brand: ${activeBrand.value}` });
    }
    if (minPrice.value || maxPrice.value) {
        tags.push({ key: 'price', label: `Price: PKR ${minPrice.value || '0'} - ${maxPrice.value || '∞'}` });
    }
    if (minRating.value) {
        tags.push({ key: 'min_rating', label: `Rating: ${minRating.value}+ Stars` });
    }
    if (trendingOnly.value) {
        tags.push({ key: 'trending', label: '🔥 Best Sellers Only' });
    }
    if (inStockOnly.value) {
        tags.push({ key: 'in_stock', label: '📦 In Stock Only' });
    }
    return tags;
});

// Perform Search / Filter request via Inertia
const applyFilters = (customParams = {}) => {
    isFiltering.value = true;
    const params = {
        search: searchInput.value || undefined,
        category: activeCategory.value && activeCategory.value !== 'all' ? activeCategory.value : undefined,
        brand: activeBrand.value && activeBrand.value !== 'all' ? activeBrand.value : undefined,
        sort_by: sortBy.value !== 'recommended' ? sortBy.value : undefined,
        min_price: minPrice.value || undefined,
        max_price: maxPrice.value || undefined,
        min_rating: minRating.value || undefined,
        trending: trendingOnly.value ? 1 : undefined,
        in_stock: inStockOnly.value ? 1 : undefined,
        ...customParams
    };

    router.get(route('products.index'), params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isFiltering.value = false;
        }
    });
};

const selectCategory = (catName) => {
    activeCategory.value = catName === activeCategory.value ? '' : catName;
    applyFilters();
};

const applyPricePreset = (preset) => {
    minPrice.value = preset.min;
    maxPrice.value = preset.max;
    applyFilters();
};

const removeFilterTag = (key) => {
    if (key === 'search') searchInput.value = '';
    if (key === 'category') activeCategory.value = '';
    if (key === 'brand') activeBrand.value = '';
    if (key === 'price') {
        minPrice.value = '';
        maxPrice.value = '';
    }
    if (key === 'min_rating') minRating.value = '';
    if (key === 'trending') trendingOnly.value = false;
    if (key === 'in_stock') inStockOnly.value = false;
    applyFilters();
};

const clearAllFilters = () => {
    searchInput.value = '';
    activeCategory.value = '';
    activeBrand.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    minRating.value = '';
    trendingOnly.value = false;
    inStockOnly.value = false;
    sortBy.value = 'recommended';
    applyFilters();
};

// Open Quick View Modal
const openQuickView = (product) => {
    quickViewProduct.value = product;
    quickViewQty.value = 1;
};

const closeQuickView = () => {
    quickViewProduct.value = null;
};

// Checkout state form
const checkoutForm = ref({
    fullName: '',
    phone: '',
    city: 'Karachi',
    address: '',
    notes: '',
    paymentMethod: 'cod'
});
const isSubmittingOrder = ref(false);
const orderCompleted = ref(false);
const createdOrder = ref(null);

// Auth in Checkout Modal State
const authTab = ref('login'); // 'login' | 'register'
const loginForm = ref({
    email: '',
    password: '',
    remember: true,
});
const registerForm = ref({
    name: '',
    email: '',
    phone: '',
    username: '',
    password: '',
    password_confirmation: '',
});
const isAuthSubmitting = ref(false);
const authErrors = ref({});
const showPassword = ref(false);

const populateCheckoutFromUser = (user) => {
    if (!user) return;
    if (!checkoutForm.value.fullName) checkoutForm.value.fullName = user.name || '';
    if (!checkoutForm.value.phone) checkoutForm.value.phone = user.phone || '';
    if (!checkoutForm.value.address) checkoutForm.value.address = user.address || '';
    if (user.city) {
        checkoutForm.value.city = typeof user.city === 'object' ? (user.city.name || 'Karachi') : user.city;
    }
};

watch(authUser, (newUser) => {
    if (newUser) {
        populateCheckoutFromUser(newUser);
    }
});

const openCheckout = () => {
    isCartOpen.value = false;
    showCheckoutModal.value = true;
    orderCompleted.value = false;
    authErrors.value = {};

    if (authUser.value) {
        populateCheckoutFromUser(authUser.value);
    }
};

const handleInlineLogin = async () => {
    isAuthSubmitting.value = true;
    authErrors.value = {};
    saveCart();

    try {
        const response = await axios.post(route('auth.inline-login'), loginForm.value);
        if (response.data?.success) {
            const user = response.data.user;
            page.props.auth.user = user;
            populateCheckoutFromUser(user);
            isAuthSubmitting.value = false;
            authErrors.value = {};
            triggerToast('Welcome back! You can now complete your order.', 'success');
        }
    } catch (error) {
        isAuthSubmitting.value = false;
        if (error.response?.data?.errors) {
            authErrors.value = error.response.data.errors;
            const firstErr = Object.values(error.response.data.errors)[0];
            triggerToast(Array.isArray(firstErr) ? firstErr[0] : firstErr, 'error');
        } else {
            triggerToast('Login failed. Please check your credentials.', 'error');
        }
    }
};

const handleInlineRegister = async () => {
    isAuthSubmitting.value = true;
    authErrors.value = {};
    saveCart();

    // Auto-generate username from email prefix + random digits if empty
    if (!registerForm.value.username && registerForm.value.email) {
        registerForm.value.username = registerForm.value.email.split('@')[0].replace(/[^a-zA-Z0-9]/g, '') + Math.floor(Math.random() * 1000);
    }

    try {
        const response = await axios.post(route('auth.inline-register'), registerForm.value);
        if (response.data?.success) {
            const user = response.data.user;
            page.props.auth.user = user;
            populateCheckoutFromUser(user);
            isAuthSubmitting.value = false;
            authErrors.value = {};
            triggerToast('Account created successfully! Proceed with your order.', 'success');
        }
    } catch (error) {
        isAuthSubmitting.value = false;
        if (error.response?.data?.errors) {
            authErrors.value = error.response.data.errors;
            const firstErr = Object.values(error.response.data.errors)[0];
            triggerToast(Array.isArray(firstErr) ? firstErr[0] : firstErr, 'error');
        } else {
            triggerToast('Registration error. Please check your input fields.', 'error');
        }
    }
};


const isDetectingProductAddress = ref(false);

const autoDetectProductAddress = async () => {
    isDetectingProductAddress.value = true;
    try {
        const loc = await detectCurrentAddress();
        if (loc.fullAddress) {
            checkoutForm.value.address = loc.fullAddress;
        }
        if (loc.city) {
            const citiesList = ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Sialkot', 'Gujranwala', 'Hyderabad'];
            const matchedCity = citiesList.find(c => c.toLowerCase() === loc.city.toLowerCase());
            if (matchedCity) {
                checkoutForm.value.city = matchedCity;
            }
        }
        triggerToast('Delivery address auto-detected! 📍', 'success');
    } catch (err) {
        triggerToast(err.message || 'Could not auto-detect address.', 'error');
    } finally {
        isDetectingProductAddress.value = false;
    }
};

const redirectToLoginForCheckout = () => {
    saveCart();
    window.location.href = route('login');
};

const handleOrderSubmit = async () => {
    if (!checkoutForm.value.fullName || !checkoutForm.value.phone || !checkoutForm.value.address) {
        alert('Please fill in your Full Name, Phone Number, and Delivery Address.');
        return;
    }

    if (cart.value.length === 0) {
        alert('Your shopping bag is empty. Please add products before placing an order.');
        return;
    }

    isSubmittingOrder.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(route('products.order.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                fullName: checkoutForm.value.fullName,
                phone: checkoutForm.value.phone,
                email: checkoutForm.value.email || authUser.value?.email || null,
                city: checkoutForm.value.city,
                address: checkoutForm.value.address,
                notes: checkoutForm.value.notes,
                paymentMethod: checkoutForm.value.paymentMethod,
                items: cart.value.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    brand: item.brand,
                    category: item.category,
                    image: item.image,
                })),
            })
        });

        let data = {};
        try {
            data = await response.json();
        } catch (parseErr) {
            console.error('Response parsing error:', parseErr);
        }

        if (response.ok && data.success) {
            createdOrder.value = data.order;
            orderCompleted.value = true;
            clearCart();
            triggerToast(`Order #${data.order.order_number} confirmed! 🎉`, 'success');
        } else if (data.errors) {
            const firstError = Object.values(data.errors)[0];
            alert(Array.isArray(firstError) ? firstError[0] : firstError);
        } else {
            alert(data.message || 'Error creating your order. Please verify your details.');
        }
    } catch (e) {
        console.error('Order submission error:', e);
        alert('Unable to connect to order server. Please check your connection and try again.');
    } finally {
        isSubmittingOrder.value = false;
    }
};

// WhatsApp Direct 1-Click Order Link Generator
const generateWhatsAppOrderLink = (product = null, quantity = 1) => {
    const phoneNumber = '923001234567'; // Salon Marketplace official hotline
    let text = '';

    if (product) {
        text = `Salam! I would like to order this salon product from BeautyBook Luxe:%0A%0A` +
            `*Product:* ${encodeURIComponent(product.name)}%0A` +
            `*Brand:* ${encodeURIComponent(product.brand || 'Salon Pro')}%0A` +
            `*Quantity:* ${quantity}%0A` +
            `*Price:* PKR ${(product.price * quantity).toLocaleString()}%0A%0A` +
            `Please confirm availability and delivery details. Thank you!`;
    } else {
        if (cart.value.length === 0) return '#';
        text = `Salam! I would like to place an order for the following items from BeautyBook Luxe:%0A%0A`;
        cart.value.forEach((item, idx) => {
            text += `${idx + 1}. *${encodeURIComponent(item.name)}* x ${item.quantity} = PKR ${(item.price * item.quantity).toLocaleString()}%0A`;
        });
        text += `%0A*Subtotal:* PKR ${cartSubtotal.value.toLocaleString()}%0A` +
            `*Shipping:* ${shippingCost.value === 0 ? 'FREE' : 'PKR ' + shippingCost.value}%0A` +
            `*Total Amount:* PKR ${cartTotal.value.toLocaleString()}%0A%0A` +
            `Please confirm my order and send payment/delivery instructions.`;
    }

    return `https://wa.me/${phoneNumber}?text=${text}`;
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Salon-Grade Beauty, Haircare & Bridal Essentials | BeautyBook Luxe Store</title>
            <meta name="description" content="Shop 100% original salon-grade hair serums, skin glow elixirs, organic bridal henna, keratin treatments, and beauty tools in Pakistan." />
        </Head>

        <!-- Floating Cart Toggle Button -->
        <button
            @click="isCartOpen = true"
            type="button"
            class="fixed bottom-6 right-6 z-40 flex items-center gap-3 rounded-full bg-slate-900 px-5 py-3.5 text-white shadow-2xl shadow-rose-950/30 ring-2 ring-rose-500/50 hover:bg-rose-600 transition-all duration-300 hover:scale-105 group cursor-pointer"
            aria-label="View Shopping Bag"
        >
            <div class="relative">
                <svg class="h-6 w-6 text-white group-hover:animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span
                    v-if="cartTotalCount > 0"
                    class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white ring-2 ring-slate-900 animate-pulse"
                >
                    {{ cartTotalCount }}
                </span>
            </div>
            <div class="text-left hidden sm:block">
                <span class="block text-[10px] uppercase font-bold text-slate-300 group-hover:text-rose-100">Bag Subtotal</span>
                <span class="font-serif text-sm font-bold text-white">{{ formatPrice(cartSubtotal) }}</span>
            </div>
        </button>

        <!-- Toast Notifications -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastMessage"
                class="fixed top-20 right-5 z-50 flex items-center gap-3 rounded-2xl bg-slate-950/90 text-white px-5 py-3.5 shadow-2xl backdrop-blur-xl border border-white/10 text-sm font-medium"
            >
                <span class="text-lg">✨</span>
                <span>{{ toastMessage }}</span>
                <button @click="toastMessage = ''" class="ml-2 text-slate-400 hover:text-white text-xs">✕</button>
            </div>
        </transition>

        <div class="min-h-screen bg-gradient-to-b from-rose-50/40 via-white to-slate-50/50 pb-20 pt-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Hero Header Banner -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-950 via-slate-900 to-rose-950 p-6 sm:p-10 text-white shadow-xl shadow-rose-950/10">
                    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-rose-600/20 blur-3xl"></div>
                    <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="max-w-2xl text-center md:text-left space-y-3">
                            <div class="inline-flex items-center gap-2 rounded-full bg-rose-500/20 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-rose-300 border border-rose-500/30">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-400 animate-ping"></span>
                                100% Certified Salon-Grade Marketplace
                            </div>
                            <h1 class="font-serif text-3xl sm:text-5xl font-normal tracking-tight text-white">
                                Professional Beauty, Hair & Bridal Vault
                            </h1>
                            <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                                Curated authentic formulas straight from elite salons: pure argan oils, dermal hyaluronic serums, triple-filtered Rajasthani organic mehndi, and pro tools.
                            </p>
                        </div>

                        <!-- Highlights Pill Badge -->
                        <div class="shrink-0 flex flex-col sm:flex-row md:flex-col gap-3 w-full md:w-auto">
                            <div class="flex items-center gap-3 rounded-2xl bg-white/10 backdrop-blur-md p-3.5 border border-white/10">
                                <span class="text-2xl">🚚</span>
                                <div>
                                    <h4 class="text-xs font-bold text-white">Complimentary Delivery</h4>
                                    <p class="text-[11px] text-slate-300">On all orders over PKR 3,000</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl bg-white/10 backdrop-blur-md p-3.5 border border-white/10">
                                <span class="text-2xl">🛡️</span>
                                <div>
                                    <h4 class="text-xs font-bold text-white">Authenticity Escrow</h4>
                                    <p class="text-[11px] text-slate-300">100% Original Brand Guarantee</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Redesigned Spotlight & Trending Salon Picks Strip -->
                    <div v-if="trendingProducts && trendingProducts.length > 0" class="mt-6 pt-5 border-t border-white/10 relative">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-rose-300">
                                <span class="animate-pulse">🔥</span>
                                <span>Trending Salon Favorites</span>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">Tap to quick view</span>
                        </div>

                        <!-- Track container with soft edge gradient masks -->
                        <div class="relative overflow-hidden">
                            <div class="pointer-events-none absolute left-0 inset-y-0 w-6 bg-gradient-to-r from-slate-950 to-transparent z-10"></div>
                            <div class="pointer-events-none absolute right-0 inset-y-0 w-6 bg-gradient-to-l from-slate-950 to-transparent z-10"></div>

                            <div class="flex items-center gap-2.5 overflow-x-auto pb-1.5 scrollbar-none">
                                <button
                                    v-for="tProduct in trendingProducts"
                                    :key="tProduct.id"
                                    @click="openQuickView(tProduct)"
                                    type="button"
                                    class="shrink-0 flex items-center gap-2.5 rounded-2xl bg-white/10 hover:bg-white/20 active:scale-98 p-1.5 pr-3.5 backdrop-blur-xl border border-white/15 text-xs text-white transition-all duration-200 cursor-pointer shadow-2xs group"
                                >
                                    <img
                                        :src="tProduct.image_url || tProduct.image"
                                        :alt="tProduct.name"
                                        class="h-8 w-8 rounded-xl object-cover border border-white/20 shrink-0 group-hover:scale-105 transition-transform"
                                    />
                                    <div class="text-left min-w-0">
                                        <p class="font-bold text-slate-100 max-w-[130px] sm:max-w-[160px] truncate group-hover:text-rose-300 transition-colors leading-tight">
                                            {{ tProduct.name }}
                                        </p>
                                        <span class="text-[11px] font-extrabold text-rose-300">
                                            {{ formatPrice(tProduct.price) }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Filter Bar and Search Controls -->
                <div class="rounded-3xl bg-white p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                        
                        <!-- Search input -->
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchInput"
                                @keyup.enter="applyFilters()"
                                type="text"
                                placeholder="Search by name, active ingredients (e.g. Argan, Keratin), or brand..."
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 pl-10 pr-24 py-2.5 text-sm text-slate-800 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-500/20 transition duration-200"
                            />
                            <div class="absolute inset-y-0 right-1.5 flex items-center gap-1">
                                <button
                                    v-if="searchInput"
                                    @click="searchInput = ''; applyFilters();"
                                    type="button"
                                    class="p-1.5 text-slate-400 hover:text-slate-600 text-xs rounded-full"
                                    title="Clear search"
                                >
                                    ✕
                                </button>
                                <button
                                    @click="applyFilters()"
                                    type="button"
                                    class="rounded-xl bg-slate-900 hover:bg-rose-600 px-3 py-1.5 text-xs font-bold text-white transition duration-200 cursor-pointer"
                                >
                                    Search
                                </button>
                            </div>
                        </div>

                        <!-- Filter actions & Sorting -->
                        <div class="flex flex-wrap items-center gap-3">
                            
                            <!-- Category selector -->
                            <div class="w-full sm:w-auto">
                                <select
                                    v-model="activeCategory"
                                    @change="applyFilters()"
                                    class="w-full sm:w-auto rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-700 focus:border-rose-500 focus:bg-white focus:ring-0 cursor-pointer"
                                >
                                    <option value="">All Categories ({{ totalProductsCount }})</option>
                                    <option v-for="cat in categories" :key="cat.name" :value="cat.name">
                                        {{ cat.icon }} {{ cat.name }} ({{ cat.count }})
                                    </option>
                                </select>
                            </div>

                            <!-- Brand selector -->
                            <div v-if="brands && brands.length > 0" class="w-full sm:w-auto">
                                <select
                                    v-model="activeBrand"
                                    @change="applyFilters()"
                                    class="w-full sm:w-auto rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-medium text-slate-700 focus:border-rose-500 focus:bg-white focus:ring-0 cursor-pointer"
                                >
                                    <option value="">All Salon Brands</option>
                                    <option v-for="brand in brands" :key="brand" :value="brand">
                                        {{ brand }}
                                    </option>
                                </select>
                            </div>

                            <!-- Sort dropdown -->
                            <div class="w-full sm:w-auto">
                                <select
                                    v-model="sortBy"
                                    @change="applyFilters()"
                                    class="w-full sm:w-auto rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:border-rose-500 focus:bg-white focus:ring-0 cursor-pointer"
                                >
                                    <option v-for="s in sortOptions" :key="s.value" :value="s.value">
                                        {{ s.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- View Mode Switcher -->
                            <div class="hidden sm:flex items-center rounded-2xl bg-slate-100 p-1 border border-slate-200/60">
                                <button
                                    @click="viewMode = 'grid'"
                                    type="button"
                                    :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                    class="rounded-xl p-1.5 transition duration-150"
                                    title="Grid View"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </button>
                                <button
                                    @click="viewMode = 'list'"
                                    type="button"
                                    :class="viewMode === 'list' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                                    class="rounded-xl p-1.5 transition duration-150"
                                    title="List View"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Secondary Quick Filters & Price Presets -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                        
                        <!-- Quick Toggles -->
                        <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                            <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-slate-700 hover:text-rose-600">
                                <input
                                    v-model="trendingOnly"
                                    @change="applyFilters()"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span>🔥 Best Sellers Only</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer select-none font-semibold text-slate-700 hover:text-rose-600">
                                <input
                                    v-model="inStockOnly"
                                    @change="applyFilters()"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span>📦 In Stock Only</span>
                            </label>

                            <button
                                @click="minRating = minRating === '4.8' ? '' : '4.8'; applyFilters();"
                                type="button"
                                :class="minRating === '4.8' ? 'bg-amber-500 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-amber-50'"
                                class="rounded-xl px-3 py-1 text-xs transition duration-150 cursor-pointer"
                            >
                                ⭐ 4.8+ Rated
                            </button>
                        </div>

                        <!-- Price Range Presets -->
                        <div class="flex items-center gap-1.5 overflow-x-auto">
                            <span class="text-slate-400 font-medium mr-1">Budget:</span>
                            <button
                                v-for="preset in pricePresets"
                                :key="preset.label"
                                @click="applyPricePreset(preset)"
                                type="button"
                                :class="minPrice === preset.min && maxPrice === preset.max ? 'bg-rose-100 text-rose-800 font-bold border-rose-300' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200'"
                                class="rounded-lg px-2.5 py-1 text-[11px] border transition duration-150 cursor-pointer"
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Active Filter Badges Bar -->
                    <div v-if="activeFilterTags.length > 0" class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Filters:</span>
                        <div
                            v-for="tag in activeFilterTags"
                            :key="tag.key"
                            class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 border border-rose-200"
                        >
                            <span>{{ tag.label }}</span>
                            <button @click="removeFilterTag(tag.key)" class="hover:text-rose-950 text-rose-400 font-black">✕</button>
                        </div>
                        <button
                            @click="clearAllFilters"
                            type="button"
                            class="text-xs font-bold text-slate-500 hover:text-rose-600 underline ml-2 cursor-pointer"
                        >
                            Clear All
                        </button>
                    </div>
                </div>

                <!-- Loading State Indicator -->
                <div v-if="isFiltering" class="flex items-center justify-center py-10">
                    <div class="flex items-center gap-3 rounded-2xl bg-white px-6 py-4 shadow-lg border border-slate-100">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-rose-600 border-t-transparent"></div>
                        <span class="text-sm font-semibold text-slate-700">Refining salon products...</span>
                    </div>
                </div>

                <!-- Products Grid / List View -->
                <div v-else-if="products.data && products.data.length > 0">
                    
                    <!-- GRID VIEW -->
                    <div v-if="viewMode === 'grid'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="product in products.data"
                            :key="product.id"
                            class="group relative flex flex-col justify-between rounded-3xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-xl hover:shadow-rose-950/5 hover:border-rose-200 transition-all duration-300"
                        >
                            <!-- Product Image Area -->
                            <div class="relative aspect-square w-full rounded-2xl overflow-hidden bg-slate-100">
                                <img
                                    :src="product.image_url || product.image"
                                    :alt="product.name"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-108"
                                    loading="lazy"
                                />

                                <!-- Top Badges -->
                                <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
                                    <span v-if="product.badge" class="rounded-full bg-slate-950/80 backdrop-blur-md px-3 py-1 text-[10px] font-black uppercase tracking-wider text-white shadow-sm border border-white/20">
                                        {{ product.badge }}
                                    </span>
                                    <span v-if="product.discount_percentage > 0" class="rounded-full bg-rose-600 px-2.5 py-0.5 text-[10px] font-black text-white shadow-sm">
                                        -{{ product.discount_percentage }}% OFF
                                    </span>
                                </div>

                                <!-- Wishlist & Quick View Floating Buttons -->
                                <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-90 sm:opacity-0 sm:group-hover:opacity-100 transition-all duration-300">
                                    <button
                                        @click="toggleWishlist(product)"
                                        type="button"
                                        :class="wishlist[product.id] ? 'bg-rose-600 text-white' : 'bg-white/90 text-slate-700 hover:text-rose-600'"
                                        class="flex h-9 w-9 items-center justify-center rounded-full shadow-md backdrop-blur-md transition duration-200 cursor-pointer"
                                        title="Save to Wishlist"
                                    >
                                        <svg class="h-4 w-4" :fill="wishlist[product.id] ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openQuickView(product)"
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 hover:text-rose-600 shadow-md backdrop-blur-md transition duration-200 cursor-pointer"
                                        title="Quick View Details"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- In-Stock indicator overlay if low stock -->
                                <div v-if="product.stock_quantity && product.stock_quantity <= 20" class="absolute bottom-2 left-2">
                                    <span class="rounded-md bg-amber-500/90 backdrop-blur-md px-2 py-0.5 text-[9px] font-bold uppercase text-white shadow-xs">
                                        ⚡ Only {{ product.stock_quantity }} Left
                                    </span>
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="mt-4 space-y-2 flex-1">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-400">
                                    <span class="uppercase tracking-wider text-rose-600 font-bold truncate max-w-[60%]">
                                        {{ product.brand || 'Salon Pro' }}
                                    </span>
                                    <span class="flex items-center gap-1 text-amber-500 shrink-0">
                                        ★ {{ parseFloat(product.rating || 4.9).toFixed(1) }}
                                        <span class="text-slate-400 font-normal">({{ product.reviews_count || 12 }})</span>
                                    </span>
                                </div>

                                <div v-if="product.seller_name" class="flex items-center gap-1 text-[10px] text-slate-500 font-medium">
                                    <span class="text-emerald-600">🏪</span>
                                    <span class="truncate">Sold by <strong class="text-slate-700 font-semibold">{{ product.seller_name }}</strong></span>
                                </div>

                                <h3
                                    @click="openQuickView(product)"
                                    class="font-serif text-base sm:text-lg font-bold text-slate-900 line-clamp-1 group-hover:text-rose-600 transition-colors cursor-pointer"
                                >
                                    {{ product.name }}
                                </h3>

                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                    {{ product.description || 'Salon-exclusive formula with active botanical nourishment.' }}
                                </p>

                                <!-- Features chips if available -->
                                <div v-if="product.short_features && product.short_features.length" class="flex flex-wrap gap-1.5 pt-1">
                                    <span
                                        v-for="(feat, fIdx) in product.short_features.slice(0, 2)"
                                        :key="fIdx"
                                        class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                                    >
                                        ✓ {{ feat }}
                                    </span>
                                </div>
                            </div>

                            <!-- Price & Add To Bag Actions -->
                            <div class="mt-4 border-t border-slate-100 pt-3 flex items-center justify-between gap-2">
                                <div>
                                    <div class="flex items-baseline gap-1.5">
                                        <p class="font-serif text-lg font-bold text-rose-600">
                                            {{ formatPrice(product.price) }}
                                        </p>
                                        <p v-if="product.original_price && product.original_price > product.price" class="text-xs text-slate-400 line-through">
                                            {{ formatPrice(product.original_price) }}
                                        </p>
                                    </div>
                                    <span class="text-[10px] text-emerald-600 font-semibold block">
                                        ✓ Authentic In Stock
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    @click="addToCart(product, 1)"
                                    class="flex items-center gap-1.5 rounded-2xl bg-slate-900 hover:bg-rose-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:shadow-lg hover:shadow-rose-600/20 transition duration-200 cursor-pointer"
                                >
                                    <span>Add to Bag</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LIST VIEW -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="product in products.data"
                            :key="product.id"
                            class="group relative flex flex-col sm:flex-row items-center justify-between rounded-3xl bg-white p-4 sm:p-6 border border-slate-200/80 shadow-xs hover:shadow-xl hover:shadow-rose-950/5 hover:border-rose-200 transition-all duration-300 gap-5"
                        >
                            <!-- Thumbnail -->
                            <div class="relative h-44 w-44 sm:h-36 sm:w-36 shrink-0 rounded-2xl overflow-hidden bg-slate-100">
                                <img
                                    :src="product.image_url || product.image"
                                    :alt="product.name"
                                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <span v-if="product.badge" class="absolute top-2 left-2 rounded-full bg-slate-950/80 px-2.5 py-0.5 text-[9px] font-bold text-white">
                                    {{ product.badge }}
                                </span>
                            </div>

                            <!-- Middle Details -->
                            <div class="flex-1 space-y-2 text-center sm:text-left">
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs">
                                    <span class="font-bold uppercase tracking-wider text-rose-600">{{ product.brand || 'Salon Pro' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500 font-medium">{{ product.category }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="flex items-center gap-1 text-amber-500 font-bold">
                                        ★ {{ parseFloat(product.rating || 4.9).toFixed(1) }} ({{ product.reviews_count || 12 }})
                                    </span>
                                </div>

                                <h3
                                    @click="openQuickView(product)"
                                    class="font-serif text-lg font-bold text-slate-900 hover:text-rose-600 transition-colors cursor-pointer"
                                >
                                    {{ product.name }}
                                </h3>

                                <p class="text-xs text-slate-500 line-clamp-2 max-w-2xl">
                                    {{ product.description }}
                                </p>

                                <div v-if="product.short_features && product.short_features.length" class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                                    <span
                                        v-for="(f, i) in product.short_features"
                                        :key="i"
                                        class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                                    >
                                        ✓ {{ f }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right Actions & Pricing -->
                            <div class="shrink-0 flex flex-col sm:items-end justify-between gap-3 text-center sm:text-right border-t sm:border-t-0 sm:border-l border-slate-100 pt-3 sm:pt-0 sm:pl-6 w-full sm:w-auto">
                                <div>
                                    <div class="flex items-baseline justify-center sm:justify-end gap-2">
                                        <span class="font-serif text-xl font-bold text-rose-600">
                                            {{ formatPrice(product.price) }}
                                        </span>
                                        <span v-if="product.original_price && product.original_price > product.price" class="text-xs text-slate-400 line-through">
                                            {{ formatPrice(product.original_price) }}
                                        </span>
                                    </div>
                                    <span v-if="product.discount_percentage > 0" class="inline-block rounded-full bg-rose-100 text-rose-700 font-bold text-[10px] px-2 py-0.5 mt-1">
                                        Save {{ product.discount_percentage }}%
                                    </span>
                                </div>

                                <div class="flex items-center justify-center sm:justify-end gap-2">
                                    <button
                                        @click="toggleWishlist(product)"
                                        type="button"
                                        :class="wishlist[product.id] ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-600 hover:text-rose-600'"
                                        class="p-2.5 rounded-xl transition duration-150"
                                        title="Wishlist"
                                    >
                                        <svg class="h-4 w-4" :fill="wishlist[product.id] ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openQuickView(product)"
                                        type="button"
                                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                    >
                                        Quick View
                                    </button>
                                    <button
                                        @click="addToCart(product, 1)"
                                        type="button"
                                        class="rounded-xl bg-slate-900 hover:bg-rose-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-xs transition duration-200 cursor-pointer"
                                    >
                                        Add to Bag
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-12 flex justify-center">
                        <AppPagination :links="products.links" />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-3xl bg-white p-12 text-center border border-slate-200/80 shadow-xs space-y-4 max-w-xl mx-auto">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-rose-600 text-2xl mx-auto">
                        🔍
                    </div>
                    <h3 class="font-serif text-xl font-bold text-slate-900">
                        No Matching Beauty Products Found
                    </h3>
                    <p class="text-sm text-slate-500 leading-relaxed">
                        We couldn't find any salon items matching your current search parameters. Try broadening your keywords or clearing active filters.
                    </p>
                    <div class="pt-2">
                        <button
                            @click="clearAllFilters"
                            type="button"
                            class="rounded-2xl bg-slate-900 hover:bg-rose-600 px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md transition duration-200 cursor-pointer"
                        >
                            Reset All Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK VIEW MODAL (Teleported to Body) -->
        <Teleport to="body">
            <transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="quickViewProduct" class="fixed inset-0 z-[99990] overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6">
                    <div
                        @click.stop
                        class="relative w-full max-w-3xl rounded-3xl bg-white p-6 sm:p-8 shadow-2xl overflow-hidden border border-slate-100 my-auto"
                    >
                        <!-- Close button -->
                        <button
                            @click="closeQuickView"
                            type="button"
                            class="absolute top-5 right-5 flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold transition cursor-pointer z-10"
                        >
                            ✕
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                            <!-- Product Large Image Preview -->
                            <div class="space-y-4">
                                <div class="relative aspect-square w-full rounded-2xl overflow-hidden bg-slate-100 border border-slate-100">
                                    <img
                                        :src="quickViewProduct.image_url || quickViewProduct.image"
                                        :alt="quickViewProduct.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-if="quickViewProduct.badge" class="absolute top-3 left-3 rounded-full bg-slate-950/80 px-3 py-1 text-[10px] font-black uppercase text-white">
                                        {{ quickViewProduct.badge }}
                                    </span>
                                </div>

                                <!-- Trust Guarantee Badges in Modal -->
                                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                    <div class="rounded-xl bg-slate-50 p-2.5 border border-slate-100">
                                        <span class="block font-bold text-slate-800">🛡️ 100% Original</span>
                                        <span class="text-[10px] text-slate-400">Direct from Brand</span>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-2.5 border border-slate-100">
                                        <span class="block font-bold text-slate-800">⚡ Fast Dispatch</span>
                                        <span class="text-[10px] text-slate-400">Within 24-48 Hours</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Specification & Buy Form -->
                            <div class="space-y-4 text-left">
                                <div>
                                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-rose-600">{{ quickViewProduct.brand }}</span>
                                    <h3 class="font-serif text-2xl font-bold text-slate-900 mt-0.5">{{ quickViewProduct.name }}</h3>
                                    <p class="text-xs text-slate-500">{{ quickViewProduct.category?.name }}</p>
                                </div>

                                <!-- Rating & Sold -->
                                <div class="flex items-center gap-3 text-xs">
                                    <div class="flex items-center text-amber-400 font-bold">
                                        <span>★</span>
                                        <span class="ml-1 text-slate-800 font-semibold">{{ quickViewProduct.rating || 4.9 }}</span>
                                    </div>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500">{{ quickViewProduct.reviews_count || 18 }} reviews</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 text-[10px]">
                                        {{ quickViewProduct.in_stock ? 'In Stock' : 'Out of Stock' }}
                                    </span>
                                </div>

                                <!-- Price -->
                                <div class="flex items-baseline gap-3 p-3 rounded-2xl bg-rose-50/50 border border-rose-100">
                                    <span class="font-serif text-2xl font-black text-rose-600">{{ formatPrice(quickViewProduct.price) }}</span>
                                    <span v-if="quickViewProduct.original_price" class="text-xs text-slate-400 line-through">
                                        {{ formatPrice(quickViewProduct.original_price) }}
                                    </span>
                                </div>

                                <!-- Description -->
                                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    {{ quickViewProduct.description }}
                                </p>

                                <!-- Key Features -->
                                <div v-if="quickViewProduct.short_features && quickViewProduct.short_features.length" class="space-y-1.5">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Highlights & Benefits:</h4>
                                    <ul class="grid grid-cols-1 gap-1 text-xs text-slate-600">
                                        <li v-for="(feat, idx) in quickViewProduct.short_features" :key="idx" class="flex items-center gap-2">
                                            <span class="text-emerald-500 font-bold">✓</span>
                                            <span>{{ feat }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- How to use / Instructions -->
                                <div v-if="quickViewProduct.usage_instructions" class="rounded-2xl bg-amber-50/60 p-3 text-xs text-amber-900 border border-amber-200/60">
                                    <span class="font-bold block mb-0.5">💡 Salon Application Tip:</span>
                                    {{ quickViewProduct.usage_instructions }}
                                </div>

                                <!-- Quantity & Action Buttons -->
                                <div class="space-y-3 pt-2">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50 p-1">
                                            <button
                                                @click="quickViewQty = Math.max(1, quickViewQty - 1)"
                                                type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-sm shadow-xs transition cursor-pointer"
                                            >
                                                -
                                            </button>
                                            <span class="w-10 text-center text-xs font-bold text-slate-900">{{ quickViewQty }}</span>
                                            <button
                                                @click="quickViewQty = quickViewQty + 1"
                                                type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-sm shadow-xs transition cursor-pointer"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <button
                                            @click="addToCart(quickViewProduct, quickViewQty); closeQuickView();"
                                            type="button"
                                            class="flex-1 rounded-2xl bg-slate-900 hover:bg-rose-600 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md transition duration-200 cursor-pointer flex items-center justify-center gap-2"
                                        >
                                            <span>🛍️</span>
                                            <span>Add to Bag • {{ formatPrice(quickViewProduct.price * quickViewQty) }}</span>
                                        </button>
                                    </div>

                                    <a
                                        :href="`https://wa.me/923001234567?text=Salam!%20I%20am%20interested%20in%20ordering%20'${quickViewProduct.name}'%20(Price:%20PKR%20${quickViewProduct.price})%20on%20BeautyBook.`"
                                        target="_blank"
                                        class="w-full flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 py-2.5 text-xs font-bold text-white shadow-sm transition"
                                    >
                                        <span>💬</span>
                                        <span>Order via WhatsApp</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- SLIDE-OVER SHOPPING BAG / CART DRAWER (Teleported to Body) -->
        <Teleport to="body">
            <transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="isCartOpen" class="fixed inset-0 z-[99990] overflow-hidden bg-slate-950/70 backdrop-blur-xs">
                    <div class="absolute inset-0" @click="isCartOpen = false"></div>
                    <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                        <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col justify-between">
                            
                            <!-- Header -->
                            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 text-lg">
                                        🛍️
                                    </div>
                                    <div>
                                        <h3 class="font-serif text-lg font-bold text-slate-900">Your Shopping Bag</h3>
                                        <p class="text-xs text-slate-400">{{ cartTotalCount }} item(s) selected</p>
                                    </div>
                                </div>
                                <button
                                    @click="isCartOpen = false"
                                    type="button"
                                    class="rounded-full p-2 text-slate-400 hover:text-slate-600 text-sm font-bold cursor-pointer"
                                >
                                    ✕
                                </button>
                            </div>

                            <!-- Free Shipping Progress Bar (If enabled in Admin) -->
                            <div v-if="isFreeShippingEnabled" class="px-6 py-3 bg-rose-50/50 border-b border-rose-100 text-xs">
                                <div class="flex items-center justify-between font-semibold text-rose-900 mb-1.5">
                                    <span v-if="amountToFreeShipping > 0">
                                        Add <span class="font-bold text-rose-600">{{ formatPrice(amountToFreeShipping) }}</span> for FREE shipping!
                                    </span>
                                    <span v-else class="text-emerald-700 font-bold flex items-center gap-1">
                                        🎉 You unlocked FREE Nationwide Delivery!
                                    </span>
                                    <span>{{ freeShippingProgress }}%</span>
                                </div>
                                <div class="w-full bg-rose-200/60 rounded-full h-2 overflow-hidden">
                                    <div
                                        class="bg-gradient-to-r from-rose-500 to-emerald-500 h-2 rounded-full transition-all duration-500"
                                        :style="{ width: `${freeShippingProgress}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Bag Items List -->
                            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                                <div v-if="cart.length === 0" class="text-center py-16 space-y-3">
                                    <span class="text-4xl block">🛍️</span>
                                    <h4 class="font-serif text-base font-bold text-slate-800">Your bag is currently empty</h4>
                                    <p class="text-xs text-slate-400">Explore our salon catalog and add premium beauty essentials.</p>
                                    <button
                                        @click="isCartOpen = false"
                                        type="button"
                                        class="mt-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-bold uppercase text-white hover:bg-rose-600 transition cursor-pointer"
                                    >
                                        Start Shopping
                                    </button>
                                </div>

                                <div
                                    v-for="item in cart"
                                    :key="item.id"
                                    class="flex items-center gap-4 rounded-2xl bg-slate-50/80 p-3.5 border border-slate-100"
                                >
                                    <img :src="item.image" :alt="item.name" class="h-16 w-16 rounded-xl object-cover shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">{{ item.brand }}</span>
                                        <h4 class="font-serif text-sm font-bold text-slate-900 truncate">{{ item.name }}</h4>
                                        <p class="font-bold text-xs text-rose-600 mt-0.5">{{ formatPrice(item.price) }}</p>
                                        
                                        <!-- Quantity controller -->
                                        <div class="flex items-center gap-2 mt-2">
                                            <button
                                                @click="updateCartQty(item.id, -1)"
                                                type="button"
                                                class="h-6 w-6 rounded-md bg-white border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer"
                                            >
                                                -
                                            </button>
                                            <span class="text-xs font-bold text-slate-800 w-5 text-center">{{ item.quantity }}</span>
                                            <button
                                                @click="updateCartQty(item.id, 1)"
                                                type="button"
                                                class="h-6 w-6 rounded-md bg-white border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>

                                    <button
                                        @click="removeFromCart(item.id)"
                                        type="button"
                                        class="text-slate-400 hover:text-red-500 p-1 text-xs cursor-pointer"
                                        title="Remove item"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </div>

                            <!-- Footer Summary & Checkout Actions -->
                            <div v-if="cart.length > 0" class="p-6 border-t border-slate-100 bg-slate-50 space-y-4">
                                <div class="space-y-1.5 text-xs text-slate-600">
                                    <div class="flex justify-between">
                                        <span>Subtotal</span>
                                        <span class="font-bold text-slate-900">{{ formatPrice(cartSubtotal) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex flex-col">
                                            <span>Estimated Shipping</span>
                                            <span class="text-[10px] text-slate-400 font-medium">{{ shippingSettings.carrier_name }} &bull; {{ shippingSettings.estimated_days }}</span>
                                        </div>
                                        <span class="font-bold" :class="shippingCost === 0 ? 'text-emerald-600' : 'text-slate-900'">
                                            {{ shippingCost === 0 ? 'FREE' : formatPrice(shippingCost) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-sm font-bold text-slate-900 pt-2 border-t border-slate-200">
                                        <span>Total Amount</span>
                                        <span class="text-rose-600 font-serif text-base">{{ formatPrice(cartTotal) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <button
                                        v-if="authUser"
                                        @click="openCheckout"
                                        type="button"
                                        class="w-full rounded-2xl bg-slate-900 hover:bg-rose-600 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition duration-200 cursor-pointer"
                                    >
                                        Proceed to Fast Checkout
                                    </button>
                                    <button
                                        v-else
                                        @click="openCheckout"
                                        type="button"
                                        class="w-full rounded-2xl bg-gradient-to-r from-rose-600 via-rose-700 to-pink-700 hover:from-rose-700 hover:to-pink-800 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition duration-200 cursor-pointer flex items-center justify-center gap-2"
                                    >
                                        <span>🔐</span>
                                        <span>Log in to Checkout ({{ formatPrice(cartTotal) }})</span>
                                    </button>
                                    <a
                                        :href="generateWhatsAppOrderLink()"
                                        target="_blank"
                                        class="w-full flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 py-3 text-xs font-bold text-white shadow-sm transition duration-200"
                                    >
                                        <span>💬</span>
                                        <span>Instant WhatsApp Order (COD)</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- FAST CHECKOUT MODAL (Teleported directly to <body> with z-[99999]) -->
        <Teleport to="body">
            <transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="showCheckoutModal"
                    class="fixed inset-0 z-[99999] overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-6"
                >
                    <div
                        @click.stop
                        class="relative w-full max-w-lg rounded-3xl bg-white shadow-2xl shadow-slate-950/60 border border-pink-100 overflow-hidden my-auto max-h-[92vh] flex flex-col"
                    >
                        <!-- Top Luxury Gradient Accent Bar -->
                        <div class="h-1.5 w-full bg-gradient-to-r from-rose-500 via-pink-500 to-amber-400 shrink-0"></div>

                        <!-- Pinned Top Header -->
                        <div class="px-5 pt-4 pb-3 sm:px-6 sm:pt-4 sm:pb-3.5 border-b border-pink-100/80 flex items-center justify-between shrink-0 bg-white">
                            <div class="flex items-center gap-2.5">
                                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-rose-500 to-pink-600 text-white flex items-center justify-center text-lg shadow-sm shrink-0">
                                    🛍️
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200/60">
                                            Fast VIP Checkout
                                        </span>
                                        <span v-if="authUser" class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <span>✓</span> Verified Account
                                        </span>
                                    </div>
                                    <h3 class="font-serif text-base sm:text-lg font-bold text-slate-900 mt-0.5 leading-tight">
                                        Delivery & Payment Details
                                    </h3>
                                </div>
                            </div>

                            <!-- Floating Close Button -->
                            <button
                                @click="showCheckoutModal = false"
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 text-xs font-bold transition cursor-pointer shrink-0 shadow-2xs"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- 1. ORDER CONFIRMATION SUCCESS VIEW -->
                        <div v-if="orderCompleted" class="p-6 sm:p-7 text-center space-y-4 overflow-y-auto">
                            <div class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white text-3xl shadow-lg shadow-emerald-500/25 animate-bounce">
                                ✓
                            </div>

                            <div class="space-y-1">
                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <span>🎉</span> Order Verified & Placed
                                </span>
                                <h3 class="font-serif text-xl sm:text-2xl font-bold text-slate-900">
                                    Order #{{ createdOrder?.order_number || 'Confirmed' }}
                                </h3>
                                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                                    Thank you, <strong class="text-slate-800">{{ createdOrder?.customer_name || checkoutForm.fullName }}</strong>! Your salon product parcel has been dispatched to our logistics carrier for fast delivery in <span class="font-semibold text-rose-600">{{ createdOrder?.city || checkoutForm.city }}</span>.
                                </p>
                            </div>

                            <!-- Live Order Breakdown Summary Card -->
                            <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-200/80 text-xs text-slate-700 space-y-2 text-left shadow-2xs">
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-slate-500">Order Reference:</span>
                                    <span class="font-mono font-bold text-slate-900">#{{ createdOrder?.order_number }}</span>
                                </div>
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-slate-500">Payment Method:</span>
                                    <span class="font-bold uppercase text-slate-800">{{ checkoutForm.paymentMethod === 'cod' ? '💵 Cash on Delivery' : '🏦 Bank Transfer' }}</span>
                                </div>
                                <div class="flex justify-between items-center font-semibold">
                                    <span class="text-slate-500">Logistics Carrier:</span>
                                    <span class="font-semibold text-slate-800">{{ shippingSettings.carrier_name }} ({{ shippingSettings.estimated_days }})</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-sm font-bold text-slate-900">
                                    <span>Grand Total Amount:</span>
                                    <span class="font-serif text-base font-bold text-rose-600">{{ createdOrder?.formatted_total || formatPrice(cartTotal) }}</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                                <a
                                    :href="`https://wa.me/923001234567?text=Salam!%20I%20have%20placed%20order%20%23${createdOrder?.order_number}%20on%20BeautyBook%20Luxe.%20Please%20confirm%20dispatch%20timeline.`"
                                    target="_blank"
                                    class="flex-1 flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition cursor-pointer"
                                >
                                    <span>💬</span>
                                    <span>Track on WhatsApp</span>
                                </a>
                                <button
                                    @click="showCheckoutModal = false; orderCompleted = false;"
                                    type="button"
                                    class="flex-1 rounded-2xl bg-slate-900 hover:bg-rose-600 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-sm transition cursor-pointer"
                                >
                                    Continue Shopping
                                </button>
                            </div>
                        </div>

                        <!-- 2. NON-LOGGED IN GUEST SIGN IN / VIP LOGIN SCREEN -->
                        <div v-else-if="!authUser" class="flex-1 flex flex-col justify-between overflow-hidden">
                            <!-- Scrollable Auth Container -->
                            <div class="flex-1 overflow-y-auto px-5 py-4 sm:px-6 space-y-4">
                                <!-- Header Banner -->
                                <div class="text-center space-y-1">
                                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-rose-100 to-pink-100 text-rose-600 text-xl shadow-inner mb-0.5">
                                        🔐
                                    </div>
                                    <h3 class="font-serif text-lg sm:text-xl font-bold text-slate-900">
                                        Sign In to Complete Order
                                    </h3>
                                    <p class="text-xs text-slate-500 max-w-xs mx-auto leading-relaxed">
                                        To protect your purchases and enable live delivery tracking, please sign in to your VIP account.
                                    </p>
                                </div>

                                <!-- Quick Cart Summary Bar -->
                                <div class="rounded-2xl bg-slate-50 p-3 border border-slate-200/80 text-xs text-slate-700 flex items-center justify-between shadow-2xs">
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-500">Shopping Bag:</span>
                                        <span class="font-bold text-slate-900">{{ cart.length }} Product(s) ({{ cartTotalCount }} units)</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-slate-500">Total:</span>
                                        <span class="font-serif font-bold text-rose-600 text-sm">{{ formatPrice(cartTotal) }}</span>
                                    </div>
                                </div>

                                <!-- Auth Tabs (Sign In vs Register) -->
                                <div class="grid grid-cols-2 p-1 bg-slate-100 rounded-xl text-xs font-bold">
                                    <button
                                        type="button"
                                        @click="authTab = 'login'; authErrors = {};"
                                        class="py-2 rounded-lg transition-all cursor-pointer text-center"
                                        :class="authTab === 'login' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                    >
                                        Sign In
                                    </button>
                                    <button
                                        type="button"
                                        @click="authTab = 'register'; authErrors = {};"
                                        class="py-2 rounded-lg transition-all cursor-pointer text-center"
                                        :class="authTab === 'register' ? 'bg-white text-rose-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                    >
                                        Create VIP Account
                                    </button>
                                </div>

                                <!-- Sign In Form -->
                                <form v-if="authTab === 'login'" @submit.prevent="handleInlineLogin" class="space-y-3 pt-1 text-left">
                                    <div v-if="authErrors.email || authErrors.password" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-start gap-2">
                                        <span class="shrink-0 mt-0.5">⚠️</span>
                                        <span>{{ authErrors.email || authErrors.password || 'Invalid login credentials. Please try again.' }}</span>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Email Address</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs">✉️</span>
                                            <input
                                                v-model="loginForm.email"
                                                type="email"
                                                required
                                                placeholder="you@example.com"
                                                class="w-full pl-9 pr-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600">Password</label>
                                            <a :href="route('password.request')" target="_blank" class="text-[11px] text-rose-600 hover:underline">Forgot password?</a>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs">🔒</span>
                                            <input
                                                v-model="loginForm.password"
                                                :type="showPassword ? 'text' : 'password'"
                                                required
                                                placeholder="••••••••"
                                                class="w-full pl-9 pr-9 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white"
                                            />
                                            <button
                                                type="button"
                                                @click="showPassword = !showPassword"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 text-xs cursor-pointer"
                                            >
                                                {{ showPassword ? '👁️' : '🙈' }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-0.5">
                                        <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                            <input v-model="loginForm.remember" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                            <span>Remember me on this device</span>
                                        </label>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="isAuthSubmitting"
                                        class="w-full flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-rose-900/20 transition cursor-pointer disabled:opacity-50"
                                    >
                                        <span v-if="isAuthSubmitting" class="inline-block h-3.5 w-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                        <span>{{ isAuthSubmitting ? 'Authenticating...' : 'Sign In & Continue Order' }}</span>
                                        <span v-if="!isAuthSubmitting">→</span>
                                    </button>
                                </form>

                                <!-- Quick Register Form -->
                                <form v-else @submit.prevent="handleInlineRegister" class="space-y-3 pt-1 text-left">
                                    <div v-if="Object.keys(authErrors).length > 0" class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 space-y-1">
                                        <div v-for="(err, field) in authErrors" :key="field" class="flex items-center gap-1">
                                            <span class="shrink-0">⚠️</span>
                                            <span>{{ Array.isArray(err) ? err[0] : err }}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Full Name</label>
                                        <input
                                            v-model="registerForm.name"
                                            type="text"
                                            required
                                            placeholder="e.g. Ayesha Khan"
                                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white"
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Email Address</label>
                                            <input
                                                v-model="registerForm.email"
                                                type="email"
                                                required
                                                placeholder="ayesha@example.com"
                                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white"
                                            />
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600">Mobile Phone</label>
                                                <span class="text-[10px] text-slate-400 font-mono">{{ registerForm.phone ? registerForm.phone.length : 0 }}/11 digits</span>
                                            </div>
                                            <input
                                                v-model="registerForm.phone"
                                                @input="e => registerForm.phone = e.target.value.replace(/\D/g, '').slice(0, 11)"
                                                type="tel"
                                                maxlength="11"
                                                required
                                                placeholder="03001234567"
                                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white tracking-wide"
                                            />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Create Password</label>
                                            <input
                                                v-model="registerForm.password"
                                                type="password"
                                                required
                                                placeholder="Min 8 chars"
                                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Confirm Password</label>
                                            <input
                                                v-model="registerForm.password_confirmation"
                                                type="password"
                                                required
                                                placeholder="Repeat password"
                                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-white"
                                            />
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="isAuthSubmitting"
                                        class="w-full flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-rose-900/20 transition cursor-pointer disabled:opacity-50"
                                    >
                                        <span v-if="isAuthSubmitting" class="inline-block h-3.5 w-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                        <span>{{ isAuthSubmitting ? 'Creating VIP Account...' : 'Create Account & Checkout' }}</span>
                                        <span v-if="!isAuthSubmitting">→</span>
                                    </button>
                                </form>

                                <div class="flex items-center justify-center gap-3 text-[10px] text-slate-400 font-semibold pt-1">
                                    <span>🛡️ 100% Buyer Protection</span>
                                    <span>•</span>
                                    <span>🚚 Cash on Delivery Available</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. AUTHENTICATED EXPRESS CHECKOUT FORM -->
                        <div v-else class="flex-1 flex flex-col justify-between overflow-hidden">
                            <!-- Scrollable Form Content -->
                            <div class="flex-1 overflow-y-auto px-5 py-3.5 sm:px-6 sm:py-4 space-y-3.5">
                                <!-- Mini Order Summary Bar -->
                                <div class="p-3 rounded-2xl bg-pink-50/50 border border-pink-100/90 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="flex -space-x-1.5 overflow-hidden shrink-0">
                                            <img
                                                v-for="item in cart.slice(0, 3)"
                                                :key="item.id"
                                                :src="item.image"
                                                :alt="item.name"
                                                class="inline-block h-7 w-7 rounded-full ring-2 ring-white object-cover shadow-2xs"
                                            />
                                            <div v-if="cart.length > 3" class="h-7 w-7 rounded-full bg-pink-200 ring-2 ring-white flex items-center justify-center text-[9px] font-bold text-pink-800">
                                                +{{ cart.length - 3 }}
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">{{ cartTotalCount }} item(s) in bag</p>
                                            <p class="text-[10px] text-slate-500">
                                                Shipping: <strong :class="shippingCost === 0 ? 'text-emerald-600' : 'text-slate-700'">{{ shippingCost === 0 ? 'FREE' : formatPrice(shippingCost) }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-[9px] text-slate-400 block uppercase font-semibold">Total Payable</span>
                                        <span class="font-serif text-sm sm:text-base font-bold text-rose-600">{{ formatPrice(cartTotal) }}</span>
                                    </div>
                                </div>

                                <!-- Form Inputs -->
                                <form id="checkoutFormSubmit" @submit.prevent="handleOrderSubmit" class="space-y-3">
                                    <!-- Full Name -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Full Name *</label>
                                        <input
                                            v-model="checkoutForm.fullName"
                                            required
                                            type="text"
                                            placeholder="e.g., Ayesha Khan"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-rose-500 focus:bg-white focus:outline-hidden transition"
                                        />
                                    </div>

                                    <!-- Phone & City -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-bold text-slate-700">WhatsApp / Phone Number *</label>
                                                <span class="text-[10px] text-slate-400 font-mono">{{ checkoutForm.phone ? checkoutForm.phone.length : 0 }}/11 digits</span>
                                            </div>
                                            <input
                                                v-model="checkoutForm.phone"
                                                @input="e => checkoutForm.phone = e.target.value.replace(/\D/g, '').slice(0, 11)"
                                                required
                                                type="tel"
                                                maxlength="11"
                                                placeholder="0300 1234567"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-rose-500 focus:bg-white focus:outline-hidden transition tracking-wide"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Destination City *</label>
                                            <select
                                                v-model="checkoutForm.city"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:bg-white focus:outline-hidden transition cursor-pointer"
                                            >
                                                <option value="Karachi">Karachi</option>
                                                <option value="Lahore">Lahore</option>
                                                <option value="Islamabad">Islamabad</option>
                                                <option value="Rawalpindi">Rawalpindi</option>
                                                <option value="Faisalabad">Faisalabad</option>
                                                <option value="Multan">Multan</option>
                                                <option value="Peshawar">Peshawar</option>
                                                <option value="Quetta">Quetta</option>
                                                <option value="Sialkot">Sialkot</option>
                                                <option value="Gujranwala">Gujranwala</option>
                                                <option value="Hyderabad">Hyderabad</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Delivery Address -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-[11px] font-bold text-slate-700">Street Address & Landmark *</label>
                                            <button
                                                type="button"
                                                @click="autoDetectProductAddress"
                                                :disabled="isDetectingProductAddress"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2 py-0.5 rounded-lg border border-rose-200 transition cursor-pointer disabled:opacity-50"
                                            >
                                                <span v-if="isDetectingProductAddress" class="inline-block h-2.5 w-2.5 rounded-full border-2 border-rose-600 border-t-transparent animate-spin"></span>
                                                <span v-else>📍</span>
                                                <span>{{ isDetectingProductAddress ? 'Detecting...' : 'Auto-Detect Address' }}</span>
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="checkoutForm.address"
                                            required
                                            rows="2"
                                            placeholder="House / Apartment #, Street, Block, Area..."
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-rose-500 focus:bg-white focus:outline-hidden transition"
                                        ></textarea>
                                    </div>


                                    <!-- Payment Method Selector -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Select Payment Method</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <label
                                                :class="checkoutForm.paymentMethod === 'cod' ? 'border-rose-500 bg-rose-50/70 ring-1 ring-rose-400' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
                                                class="flex items-center gap-2.5 p-2.5 rounded-xl border transition cursor-pointer"
                                            >
                                                <input type="radio" v-model="checkoutForm.paymentMethod" value="cod" class="text-rose-600 focus:ring-rose-500" />
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-slate-900">💵 Cash on Delivery</p>
                                                    <p class="text-[9px] text-slate-500">Pay cash upon arrival</p>
                                                </div>
                                            </label>
                                            <label
                                                :class="checkoutForm.paymentMethod === 'bank' ? 'border-rose-500 bg-rose-50/70 ring-1 ring-rose-400' : 'border-slate-200 bg-slate-50 hover:bg-slate-100/80'"
                                                class="flex items-center gap-2.5 p-2.5 rounded-xl border transition cursor-pointer"
                                            >
                                                <input type="radio" v-model="checkoutForm.paymentMethod" value="bank" class="text-rose-600 focus:ring-rose-500" />
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-slate-900">🏦 Direct Bank Transfer</p>
                                                    <p class="text-[9px] text-slate-500">Online bank details</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Pinned Bottom Action Footer -->
                            <div class="px-5 py-3 sm:px-6 sm:py-3.5 border-t border-pink-100/80 bg-slate-50/90 shrink-0 space-y-1.5">
                                <button
                                    form="checkoutFormSubmit"
                                    :disabled="isSubmittingOrder"
                                    type="submit"
                                    class="w-full rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-pink-950/20 hover:scale-[1.01] active:scale-[0.99] transition duration-200 cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    <span>🔒</span>
                                    <span>{{ isSubmittingOrder ? 'Placing Order...' : `Confirm Order & Dispatch • ${formatPrice(cartTotal)}` }}</span>
                                </button>
                                <div class="flex items-center justify-center gap-3 text-[10px] text-slate-400 font-semibold pt-0.5">
                                    <span>🛡️ Genuine Salon Guarantee</span>
                                    <span>•</span>
                                    <span>🚚 {{ shippingSettings.carrier_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </PublicLayout>
</template>
