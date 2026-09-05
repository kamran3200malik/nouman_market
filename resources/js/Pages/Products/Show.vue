<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
    shipping_settings: {
        type: Object,
        default: () => ({
            standard_fee: 250,
            free_threshold: 5000,
            estimated_days: '2 - 4 Business Days',
        }),
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const selectedImage = ref(props.product.image_url);
const quantity = ref(1);
const activeTab = ref('description');
const notification = ref('');

const reviewForm = useForm({
    rating: 5,
    title: '',
    comment: '',
    author_name: user.value?.name || '',
    author_email: user.value?.email || '',
});

const submitReview = () => {
    reviewForm.post(route('products.review.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset('title', 'comment');
            notification.value = 'Thank you! Your review has been submitted.';
            setTimeout(() => notification.value = '', 4000);
        },
    });
};

const addToCart = (product, qty = 1) => {
    try {
        let cart = JSON.parse(localStorage.getItem('beautybook_cart') || '[]');
        const index = cart.findIndex(item => item.id === product.id);
        if (index > -1) {
            cart[index].quantity = (cart[index].quantity || 1) + qty;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: Number(product.price),
                image: product.image_url,
                brand: product.brand,
                quantity: qty,
            });
        }
        localStorage.setItem('beautybook_cart', JSON.stringify(cart));
        window.dispatchEvent(new Event('cart-updated'));

        notification.value = `Added ${qty} × "${product.name}" to your shopping bag!`;
        setTimeout(() => notification.value = '', 3500);
    } catch (e) {
        console.error(e);
    }
};

const toggleWishlist = () => {
    useForm({}).post(route('wishlist.toggle', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            notification.value = 'Wishlist updated!';
            setTimeout(() => notification.value = '', 3000);
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="`${product.name} - Luxe Beauty Market`" />

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
                class="fixed bottom-20 right-4 sm:bottom-8 sm:right-8 z-50 flex items-center gap-3 rounded-2xl bg-slate-900 text-white px-5 py-3.5 shadow-2xl border border-rose-500/30 backdrop-blur-xl max-w-md text-xs font-semibold"
            >
                <span class="text-base text-emerald-400">✓</span>
                <span class="flex-1">{{ notification }}</span>
                <Link :href="route('products.index', { cart: 'open' })" class="text-rose-400 hover:text-rose-300 underline font-bold uppercase text-[10px]">
                    View Bag
                </Link>
            </div>
        </transition>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-12">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                <Link :href="route('home')" class="hover:text-rose-600 transition">Home</Link>
                <span>/</span>
                <Link :href="route('products.index')" class="hover:text-rose-600 transition">Products</Link>
                <span>/</span>
                <span v-if="product.category" class="hover:text-rose-600 transition cursor-pointer">
                    {{ product.category }}
                </span>
                <span>/</span>
                <span class="text-slate-900 font-bold truncate max-w-xs">{{ product.name }}</span>
            </nav>

            <!-- Product Primary Section: Images + Details -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-pink-100">
                <!-- Gallery Column (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="relative aspect-square w-full rounded-2xl overflow-hidden bg-slate-50 border border-pink-100">
                        <img
                            :src="selectedImage || product.image_url"
                            :alt="product.name"
                            class="h-full w-full object-cover object-center"
                        />
                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            <span v-if="product.badge" class="rounded-lg bg-rose-600 text-white text-[10px] font-black uppercase px-2.5 py-1 shadow-sm">
                                {{ product.badge }}
                            </span>
                            <span v-if="product.discount_percentage > 0" class="rounded-lg bg-amber-500 text-white text-[10px] font-black px-2.5 py-1 shadow-sm">
                                -{{ product.discount_percentage }}% OFF
                            </span>
                        </div>
                    </div>

                    <!-- Thumbnails Strip -->
                    <div v-if="product.images && product.images.length > 1" class="flex items-center gap-3 overflow-x-auto pb-1">
                        <button
                            v-for="img in product.images"
                            :key="img.id"
                            @click="selectedImage = img.url"
                            class="h-16 w-16 rounded-xl overflow-hidden border-2 transition shrink-0 cursor-pointer"
                            :class="selectedImage === img.url ? 'border-rose-600 scale-105' : 'border-transparent opacity-70 hover:opacity-100'"
                        >
                            <img :src="img.url" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- Product Buying Info (7 cols) -->
                <div class="lg:col-span-7 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <!-- Brand & Rating Header -->
                        <div class="flex items-center justify-between">
                            <span class="rounded-full bg-rose-50 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-rose-700 border border-rose-200">
                                {{ product.brand || 'Luxe Care' }}
                            </span>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-amber-500">
                                <span class="text-sm">★</span>
                                <span>{{ product.rating || '5.0' }}</span>
                                <span class="text-slate-400 font-normal">({{ product.reviews_count || product.reviews?.length || 0 }} customer reviews)</span>
                            </div>
                        </div>

                        <!-- Product Title -->
                        <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900 tracking-tight leading-tight">
                            {{ product.name }}
                        </h1>

                        <!-- SKU & Stock Status -->
                        <div class="flex items-center gap-4 text-xs">
                            <span v-if="product.sku" class="text-slate-400 font-mono">SKU: {{ product.sku }}</span>
                            <span
                                class="inline-flex items-center gap-1 font-bold"
                                :class="product.in_stock ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                <span class="h-2 w-2 rounded-full" :class="product.in_stock ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                <span>{{ product.in_stock ? `In Stock (${product.stock_quantity} available)` : 'Currently Out of Stock' }}</span>
                            </span>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-200/80 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-slate-500 block">Retail Price (Inclusive of all taxes)</span>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl sm:text-3xl font-black text-slate-900">
                                        PKR {{ Number(product.price).toLocaleString() }}
                                    </span>
                                    <span v-if="product.original_price && product.original_price > product.price" class="text-sm text-slate-400 line-through">
                                        PKR {{ Number(product.original_price).toLocaleString() }}
                                    </span>
                                </div>
                            </div>
                            <span v-if="product.discount_percentage > 0" class="px-3 py-1 bg-rose-600 text-white rounded-xl text-xs font-black">
                                SAVE {{ product.discount_percentage }}%
                            </span>
                        </div>

                        <!-- Short Description -->
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ product.short_description || product.description }}
                        </p>

                        <!-- Key Features Bullets -->
                        <div v-if="product.short_features && product.short_features.length" class="space-y-1.5 pt-1">
                            <div
                                v-for="(feat, fIdx) in product.short_features"
                                :key="fIdx"
                                class="flex items-center gap-2 text-xs text-slate-700 font-medium"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                <span>{{ feat }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Selector & Actions -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Quantity Modifier -->
                            <div class="flex items-center rounded-2xl border border-slate-200 bg-slate-50 p-1">
                                <button
                                    @click="quantity = Math.max(1, quantity - 1)"
                                    class="h-9 w-9 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-600 flex items-center justify-center font-bold text-base shadow-xs transition"
                                >
                                    −
                                </button>
                                <span class="w-12 text-center font-bold text-sm text-slate-900">
                                    {{ quantity }}
                                </span>
                                <button
                                    @click="quantity = Math.min(product.stock_quantity || 99, quantity + 1)"
                                    class="h-9 w-9 rounded-xl bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-600 flex items-center justify-center font-bold text-base shadow-xs transition"
                                >
                                    ＋
                                </button>
                            </div>

                            <!-- Add to Bag Button -->
                            <button
                                @click="addToCart(product, quantity)"
                                :disabled="!product.in_stock"
                                class="flex-1 min-w-[200px] py-3.5 px-6 rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 text-white font-bold text-xs sm:text-sm uppercase tracking-wider shadow-lg shadow-pink-900/20 transition hover:scale-102 flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
                            >
                                <span>🛍️</span>
                                <span>{{ product.in_stock ? 'Add to Shopping Bag' : 'Out of Stock' }}</span>
                            </button>

                            <!-- Wishlist Toggle -->
                            <button
                                @click="toggleWishlist"
                                class="h-12 w-12 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-rose-50 text-slate-700 hover:text-rose-600 flex items-center justify-center text-lg transition shadow-xs cursor-pointer"
                                title="Add to Wishlist"
                            >
                                ❤️
                            </button>
                        </div>

                        <!-- Delivery Promises Strip -->
                        <div class="grid grid-cols-2 gap-3 pt-2 text-[11px] text-slate-500 font-medium">
                            <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span>🚚</span>
                                <span>Est. Delivery: <strong>{{ shipping_settings.estimated_days }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span>💵</span>
                                <span><strong>Cash on Delivery</strong> across Pakistan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Tabs: Detailed Specs, How to Use, Ingredients, Reviews -->
            <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-pink-100 space-y-8">
                <!-- Tab Buttons -->
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3 overflow-x-auto text-xs font-bold">
                    <button
                        @click="activeTab = 'description'"
                        class="px-5 py-2 rounded-xl transition cursor-pointer"
                        :class="activeTab === 'description' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Detailed Description
                    </button>
                    <button
                        v-if="product.usage_instructions"
                        @click="activeTab = 'usage'"
                        class="px-5 py-2 rounded-xl transition cursor-pointer"
                        :class="activeTab === 'usage' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'text-slate-600 hover:text-slate-900'"
                    >
                        How to Use
                    </button>
                    <button
                        v-if="product.ingredients"
                        @click="activeTab = 'ingredients'"
                        class="px-5 py-2 rounded-xl transition cursor-pointer"
                        :class="activeTab === 'ingredients' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Ingredients
                    </button>
                    <button
                        @click="activeTab = 'reviews'"
                        class="px-5 py-2 rounded-xl transition cursor-pointer"
                        :class="activeTab === 'reviews' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'text-slate-600 hover:text-slate-900'"
                    >
                        Customer Reviews ({{ product.reviews?.length || 0 }})
                    </button>
                </div>

                <!-- Tab 1: Description -->
                <div v-if="activeTab === 'description'" class="space-y-4 text-xs sm:text-sm text-slate-700 leading-relaxed max-w-3xl">
                    <p class="whitespace-pre-line">{{ product.description }}</p>
                </div>

                <!-- Tab 2: Usage Instructions -->
                <div v-if="activeTab === 'usage'" class="space-y-3 text-xs sm:text-sm text-slate-700 leading-relaxed max-w-3xl">
                    <h4 class="font-bold text-slate-900">Application Protocol:</h4>
                    <p class="whitespace-pre-line bg-rose-50/50 p-4 rounded-2xl border border-rose-100">{{ product.usage_instructions }}</p>
                </div>

                <!-- Tab 3: Ingredients -->
                <div v-if="activeTab === 'ingredients'" class="space-y-3 text-xs sm:text-sm text-slate-700 leading-relaxed max-w-3xl">
                    <h4 class="font-bold text-slate-900">Full Formula Breakdown:</h4>
                    <p class="font-mono text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200 text-slate-600 leading-normal">{{ product.ingredients }}</p>
                </div>

                <!-- Tab 4: Customer Reviews -->
                <div v-if="activeTab === 'reviews'" class="space-y-8">
                    <!-- Write Review Form -->
                    <div class="p-6 rounded-3xl bg-rose-50/40 border border-rose-200/80 max-w-2xl space-y-4">
                        <h4 class="font-bold text-slate-900 text-sm">Write a Customer Review</h4>
                        <form @submit.prevent="submitReview" class="space-y-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Rating</label>
                                <select
                                    v-model="reviewForm.rating"
                                    class="w-full rounded-xl text-xs bg-white border border-slate-200 py-2 px-3"
                                >
                                    <option :value="5">⭐⭐⭐⭐⭐ (5 - Exceptional / Must Buy)</option>
                                    <option :value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                                    <option :value="3">⭐⭐⭐ (3 - Average)</option>
                                    <option :value="2">⭐⭐ (2 - Below Expectation)</option>
                                    <option :value="1">⭐ (1 - Disappointed)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Review Headline</label>
                                <input
                                    v-model="reviewForm.title"
                                    type="text"
                                    placeholder="e.g. 100% genuine and fast delivery!"
                                    class="w-full rounded-xl text-xs bg-white border border-slate-200 py-2 px-3"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Your Detailed Experience *</label>
                                <textarea
                                    v-model="reviewForm.comment"
                                    rows="3"
                                    placeholder="Share your thoughts on texture, fragrance, results, packaging..."
                                    class="w-full rounded-xl text-xs bg-white border border-slate-200 py-2 px-3"
                                    required
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="reviewForm.processing"
                                class="px-6 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs uppercase tracking-wider hover:bg-rose-600 transition disabled:opacity-50"
                            >
                                Submit Review
                            </button>
                        </form>
                    </div>

                    <!-- Existing Reviews List -->
                    <div v-if="product.reviews && product.reviews.length" class="space-y-4 max-w-3xl">
                        <div
                            v-for="rev in product.reviews"
                            :key="rev.id"
                            class="p-5 rounded-2xl border border-slate-100 bg-slate-50/50 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-full bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-xs">
                                        {{ (rev.author_name || rev.user?.name || 'U').charAt(0) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-xs text-slate-900">{{ rev.author_name || rev.user?.name || 'Verified Buyer' }}</span>
                                        <span class="text-[10px] text-emerald-600 font-bold ml-2">✓ Verified Purchase</span>
                                    </div>
                                </div>
                                <div class="text-amber-400 text-xs">
                                    <span v-for="s in (rev.rating || 5)" :key="s">★</span>
                                </div>
                            </div>
                            <h5 v-if="rev.title" class="font-bold text-xs text-slate-900">{{ rev.title }}</h5>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ rev.comment }}</p>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400">
                        No reviews yet for this product. Be the first to leave a verified review!
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div v-if="relatedProducts && relatedProducts.length" class="space-y-6">
                <h3 class="text-xl font-serif font-black text-slate-900">You Might Also Love</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <div
                        v-for="rel in relatedProducts"
                        :key="rel.id"
                        class="rounded-3xl bg-white p-4 shadow-sm border border-pink-100 flex flex-col justify-between"
                    >
                        <div>
                            <Link :href="route('products.show', rel.slug)" class="block aspect-square rounded-2xl overflow-hidden bg-slate-50 mb-3">
                                <img :src="rel.image_url" :alt="rel.name" class="h-full w-full object-cover hover:scale-105 transition duration-300" />
                            </Link>
                            <span class="text-[10px] font-bold text-rose-600 uppercase">{{ rel.brand }}</span>
                            <Link :href="route('products.show', rel.slug)">
                                <h4 class="text-xs font-bold text-slate-900 line-clamp-2 hover:text-rose-600 transition">{{ rel.name }}</h4>
                            </Link>
                        </div>
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                            <span class="font-black text-xs text-slate-900">PKR {{ Number(rel.price).toLocaleString() }}</span>
                            <button @click="addToCart(rel, 1)" class="text-xs text-rose-600 font-bold hover:underline">
                                ＋ Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
