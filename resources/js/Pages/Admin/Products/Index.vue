<script setup>
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    products: {
        type: Array,
        default: () => []
    }
});

// Search & Filter
const searchQuery = ref('');
const selectedCategory = ref('all');

// Modal State for Create / Edit
const showProductModal = ref(false);
const modalMode = ref('create'); // 'create' | 'edit'
const isSaving = ref(false);
const formErrors = ref({});
const imagePreview = ref(null);
const fileInputRef = ref(null);

// Delete confirmation modal state
const showDeleteModal = ref(false);
const productToDelete = ref(null);
const isDeleting = ref(false);

const defaultForm = () => ({
    id: null,
    name: '',
    slug: '',
    category: 'Hair Care',
    brand: '',
    price: '',
    original_price: '',
    stock_quantity: 50,
    in_stock: true,
    rating: 4.9,
    badge: '',
    description: '',
    usage_instructions: '',
    ingredients: '',
    is_trending: false,
    is_active: true,
    sort_order: 0,
    image: null,
    image_url: null,
});

const form = ref(defaultForm());

const predefinedCategories = [
    'Hair Care',
    'Skin Care',
    'Bridal & Henna',
    'Nails & Tools',
    'Tools & Accessories',
    'Makeup Essentials',
    'Fragrance & Body'
];

const badgePresets = [
    'Best Seller',
    'Trending',
    'Organic 100%',
    'Salon Grade',
    'Bridal Special',
    'Studio Pro',
    'Top Rated',
    'Staff Pick'
];

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(price || 0);
};

const getProductImage = (product) => {
    return storageUrl(product?.image, 'https://images.unsplash.com/photo-1608248597359-00f7e44a953e?auto=format&fit=crop&w=300&q=80');
};

const uniqueCategories = computed(() => {
    const cats = new Set(predefinedCategories);
    props.products.forEach(p => {
        if (p.category) cats.add(p.category);
    });
    return Array.from(cats);
});

const filteredProducts = computed(() => {
    return props.products.filter(p => {
        const matchesSearch = !searchQuery.value ||
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (p.brand && p.brand.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (p.category && p.category.toLowerCase().includes(searchQuery.value.toLowerCase()));
        
        const matchesCategory = selectedCategory.value === 'all' || p.category === selectedCategory.value;
        return matchesSearch && matchesCategory;
    });
});

const totalProductsCount = computed(() => props.products.length);
const activeProductsCount = computed(() => props.products.filter(p => p.is_active).length);
const trendingProductsCount = computed(() => props.products.filter(p => p.is_trending).length);
const totalStockCount = computed(() => props.products.reduce((acc, p) => acc + (p.stock_quantity || 0), 0));

// Slug Auto-generation
const generateSlug = () => {
    if (modalMode.value === 'create' || !form.value.slug) {
        form.value.slug = form.value.name
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

// Open Create Modal
const openCreateModal = () => {
    modalMode.value = 'create';
    form.value = defaultForm();
    formErrors.value = {};
    imagePreview.value = null;
    showProductModal.value = true;
};

// Open Edit Modal
const openEditModal = (product) => {
    modalMode.value = 'edit';
    formErrors.value = {};
    imagePreview.value = getProductImage(product);
    form.value = {
        id: product.id,
        name: product.name || '',
        slug: product.slug || '',
        category: product.category || 'Hair Care',
        brand: product.brand || '',
        price: product.price || '',
        original_price: product.original_price || '',
        stock_quantity: product.stock_quantity ?? 50,
        in_stock: Boolean(product.in_stock),
        rating: product.rating || 4.9,
        badge: product.badge || '',
        description: product.description || '',
        usage_instructions: product.usage_instructions || '',
        ingredients: product.ingredients || '',
        is_trending: Boolean(product.is_trending),
        is_active: Boolean(product.is_active),
        sort_order: product.sort_order || 0,
        image: null,
        image_url: product.image || null,
    };
    showProductModal.value = true;
};

// Handle Image File Selection
const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.value.image = file;
        const reader = new FileReader();
        reader.onload = (event) => {
            imagePreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeSelectedImage = () => {
    form.value.image = null;
    imagePreview.value = form.value.image_url ? getProductImage({ image: form.value.image_url }) : null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

// Submit Create or Edit Form
const submitProductForm = () => {
    isSaving.value = true;
    formErrors.value = {};

    const formData = new FormData();
    formData.append('name', form.value.name);
    if (form.value.slug) formData.append('slug', form.value.slug);
    if (form.value.category) formData.append('category', form.value.category);
    if (form.value.brand) formData.append('brand', form.value.brand);
    if (form.value.description) formData.append('description', form.value.description);
    if (form.value.price !== '') formData.append('price', form.value.price);
    if (form.value.original_price !== '') formData.append('original_price', form.value.original_price);
    if (form.value.rating !== '') formData.append('rating', form.value.rating);
    if (form.value.stock_quantity !== '') formData.append('stock_quantity', form.value.stock_quantity);
    formData.append('in_stock', form.value.in_stock ? '1' : '0');
    if (form.value.badge) formData.append('badge', form.value.badge);
    if (form.value.usage_instructions) formData.append('usage_instructions', form.value.usage_instructions);
    if (form.value.ingredients) formData.append('ingredients', form.value.ingredients);
    formData.append('is_trending', form.value.is_trending ? '1' : '0');
    formData.append('is_active', form.value.is_active ? '1' : '0');
    formData.append('sort_order', form.value.sort_order || 0);

    if (form.value.image instanceof File) {
        formData.append('image', form.value.image);
    }

    if (modalMode.value === 'create') {
        router.post(route('admin.products.store'), formData, {
            onSuccess: () => {
                showProductModal.value = false;
                isSaving.value = false;
            },
            onError: (errs) => {
                formErrors.value = errs;
                isSaving.value = false;
            }
        });
    } else {
        // Laravel file upload update with method spoofing
        formData.append('_method', 'PUT');
        router.post(route('admin.products.update', form.value.id), formData, {
            onSuccess: () => {
                showProductModal.value = false;
                isSaving.value = false;
            },
            onError: (errs) => {
                formErrors.value = errs;
                isSaving.value = false;
            }
        });
    }
};

// Delete Product Modal handlers
const confirmDeleteProduct = (product) => {
    productToDelete.value = product;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!productToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('admin.products.destroy', productToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            productToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

// Fast 1-click Admin Toggles
const toggleHome = (product) => {
    router.post(route('admin.products.toggle-home', product.id), {}, {
        preserveScroll: true,
    });
};

const toggleApproval = (product) => {
    router.post(route('admin.products.toggle-approval', product.id), {}, {
        preserveScroll: true,
    });
};

const toggleStatus = (product) => {
    router.post(route('admin.products.toggle-status', product.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Products Catalog & Inventory CRM" />

        <div class="space-y-6">
            <!-- Header section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Salon Marketplace</span>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">Products Catalog</h1>
                    <p class="text-xs sm:text-sm text-slate-500">Manage salon beauty inventory, pricing, and fast in-place product edits.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        :href="route('products.index')"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 border border-slate-200 shadow-2xs hover:bg-slate-50 hover:text-rose-600 transition"
                    >
                        <span>👁️</span>
                        <span>View Live Store</span>
                    </a>
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-rose-600/20 transition cursor-pointer"
                    >
                        <span>+</span>
                        <span>Add Product</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Products</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-700 text-sm">🛍️</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-slate-900 mt-2">{{ totalProductsCount }}</p>
                    <span class="text-[10px] text-slate-400">Listed in catalog</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Online</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 text-sm">✓</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-emerald-600 mt-2">{{ activeProductsCount }}</p>
                    <span class="text-[10px] text-emerald-600 font-medium">Visible to buyers</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Trending / Best Sellers</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 text-sm">🔥</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-rose-600 mt-2">{{ trendingProductsCount }}</p>
                    <span class="text-[10px] text-rose-600 font-medium">Spotlight marquee</span>
                </div>

                <div class="rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Units in Stock</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-champagne-50 text-amber-700 text-sm">📦</span>
                    </div>
                    <p class="text-2xl font-serif font-bold text-slate-900 mt-2">{{ totalStockCount }}</p>
                    <span class="text-[10px] text-slate-400">Total physical inventory</span>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 rounded-2xl bg-white p-4 border border-slate-200/80 shadow-2xs">
                <div class="relative w-full sm:w-80">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Filter products by title or brand..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white focus:ring-0"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select
                        v-model="selectedCategory"
                        class="w-full sm:w-auto rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-rose-500 focus:bg-white focus:ring-0"
                    >
                        <option value="all">All Categories</option>
                        <option v-for="cat in uniqueCategories" :key="cat" :value="cat">
                            {{ cat }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Products Table -->
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">Product</th>
                                <th class="px-4 py-3.5">Category & Brand</th>
                                <th class="px-4 py-3.5">Price & Savings</th>
                                <th class="px-4 py-3.5 text-center">Stock</th>
                                <th class="px-4 py-3.5 text-center">Home Page Showcase</th>
                                <th class="px-4 py-3.5 text-center">Admin Approval</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="product in filteredProducts"
                                :key="product.id"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <!-- Product details & Image -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="getProductImage(product)"
                                            :alt="product.name"
                                            class="h-12 w-12 rounded-xl object-cover border border-slate-100 shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <h4 class="font-serif font-bold text-slate-900 text-sm truncate max-w-xs">{{ product.name }}</h4>
                                            <p class="text-[11px] text-slate-400 truncate max-w-xs">{{ product.slug }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category & Brand -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-block rounded-md bg-rose-50 text-rose-700 font-bold px-2 py-0.5 text-[10px] border border-rose-200/60">
                                        {{ product.category || 'Beauty' }}
                                    </span>
                                    <p class="text-[11px] font-medium text-slate-500 mt-1">{{ product.brand || 'Salon Pro' }}</p>
                                </td>

                                <!-- Price -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="font-serif font-bold text-rose-600 text-sm">
                                        {{ formatPrice(product.price) }}
                                    </span>
                                    <p v-if="product.original_price && product.original_price > product.price" class="text-[10px] text-slate-400 line-through">
                                        {{ formatPrice(product.original_price) }}
                                    </p>
                                </td>

                                <!-- Stock -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span
                                        :class="product.stock_quantity > 10 ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-800 font-bold'"
                                        class="rounded-full px-2.5 py-1 text-[11px]"
                                    >
                                        {{ product.stock_quantity || 0 }} units
                                    </span>
                                </td>

                                <!-- Home Page Showcase Toggle Button -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="toggleHome(product)"
                                        :title="product.is_trending ? 'Click to remove from Home screen' : 'Click to authorize on Home screen'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-extrabold transition-all duration-200 cursor-pointer shadow-2xs active:scale-95 border"
                                        :class="product.is_trending 
                                            ? 'bg-emerald-500 hover:bg-emerald-600 text-white border-emerald-600 shadow-sm shadow-emerald-500/25' 
                                            : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200 hover:text-slate-700'"
                                    >
                                        <span class="h-2 w-2 rounded-full" :class="product.is_trending ? 'bg-white animate-pulse' : 'bg-slate-400'"></span>
                                        <span>{{ product.is_trending ? 'Show on Home 🏠' : 'Hidden from Home' }}</span>
                                    </button>
                                </td>

                                <!-- Admin Approval Toggle -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="toggleApproval(product)"
                                        :title="'Click to toggle approval status'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition-all cursor-pointer active:scale-95"
                                        :class="product.approval_status === 'approved' || !product.approval_status
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 shadow-2xs'
                                            : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                    >
                                        <span>{{ product.approval_status === 'approved' || !product.approval_status ? '✓ Approved' : '⏳ Pending' }}</span>
                                    </button>
                                </td>

                                <!-- Status Toggle -->
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="toggleStatus(product)"
                                        class="rounded-full px-2.5 py-1 text-[10px] font-bold border transition cursor-pointer"
                                        :class="product.is_active 
                                            ? 'bg-emerald-100 text-emerald-800 border-emerald-200 hover:bg-emerald-200' 
                                            : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'"
                                    >
                                        {{ product.is_active ? 'Active' : 'Draft' }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditModal(product)"
                                            type="button"
                                            class="rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="confirmDeleteProduct(product)"
                                            type="button"
                                            class="rounded-lg bg-rose-50 hover:bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-700 transition cursor-pointer"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="filteredProducts.length === 0" class="text-center py-12 space-y-2">
                        <span class="text-3xl block">🛍️</span>
                        <p class="text-sm font-semibold text-slate-700">No products matching your search</p>
                        <p class="text-xs text-slate-400">Try adjusting your filters or click "+ Add Product" to create one.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT PRODUCT MODAL -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showProductModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-2xl rounded-3xl bg-white shadow-2xl overflow-hidden border border-slate-100 flex flex-col max-h-[92vh]"
                >
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">
                                {{ modalMode === 'create' ? 'New Inventory Item' : 'Inventory Editor' }}
                            </span>
                            <h3 class="font-serif text-lg font-bold text-slate-900">
                                {{ modalMode === 'create' ? 'Add Salon Product' : `Edit "${form.name}"` }}
                            </h3>
                        </div>
                        <button
                            @click="showProductModal = false"
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 transition cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitProductForm" class="p-6 overflow-y-auto space-y-4 text-xs text-left">
                        
                        <!-- Product Name & Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Product Title *</label>
                                <input
                                    v-model="form.name"
                                    @input="generateSlug"
                                    required
                                    type="text"
                                    placeholder="e.g., Organic Argan Oil Serum"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                                <span v-if="formErrors.name" class="text-rose-600 text-[10px] font-semibold mt-0.5 block">{{ formErrors.name }}</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">URL Slug</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="e.g., organic-argan-oil-serum"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                                <span v-if="formErrors.slug" class="text-rose-600 text-[10px] font-semibold mt-0.5 block">{{ formErrors.slug }}</span>
                            </div>
                        </div>

                        <!-- Category, Brand & Badge -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Category *</label>
                                <select
                                    v-model="form.category"
                                    required
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                >
                                    <option v-for="cat in uniqueCategories" :key="cat" :value="cat">
                                        {{ cat }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Brand / Salon</label>
                                <input
                                    v-model="form.brand"
                                    type="text"
                                    placeholder="e.g., Moroccanoil Pro"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Promo Badge</label>
                                <input
                                    v-model="form.badge"
                                    type="text"
                                    placeholder="e.g., Best Seller"
                                    list="badge-list"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                                <datalist id="badge-list">
                                    <option v-for="b in badgePresets" :key="b" :value="b" />
                                </datalist>
                            </div>
                        </div>

                        <!-- Pricing & Stock -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Selling Price (PKR) *</label>
                                <input
                                    v-model.number="form.price"
                                    required
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="3500"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white font-bold"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Original Price (PKR)</label>
                                <input
                                    v-model.number="form.original_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="4200 (for discount %)"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Stock Quantity</label>
                                <input
                                    v-model.number="form.stock_quantity"
                                    type="number"
                                    min="0"
                                    placeholder="50"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Rating (1-5)</label>
                                <input
                                    v-model.number="form.rating"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="5"
                                    placeholder="4.9"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                />
                            </div>
                        </div>

                        <!-- Product Image Upload -->
                        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-200/80 space-y-3">
                            <label class="block font-bold text-slate-700">Product Image</label>
                            
                            <div class="flex items-center gap-4">
                                <div v-if="imagePreview" class="relative h-20 w-20 rounded-xl overflow-hidden bg-slate-200 border border-slate-300 shrink-0">
                                    <img :src="imagePreview" alt="Preview" class="h-full w-full object-cover" />
                                    <button
                                        @click="removeSelectedImage"
                                        type="button"
                                        class="absolute top-1 right-1 h-5 w-5 rounded-full bg-slate-900/80 text-white flex items-center justify-center text-[10px] hover:bg-rose-600"
                                        title="Reset"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div class="flex-1">
                                    <input
                                        ref="fileInputRef"
                                        @change="handleImageChange"
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Accepts JPEG, PNG, WEBP up to 2MB. Leave blank to keep existing image.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description & Instructions -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Product Description</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Details about this product, ingredients, effects..."
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Application Tip / Instructions</label>
                                <textarea
                                    v-model="form.usage_instructions"
                                    rows="2"
                                    placeholder="How to apply (e.g. apply 1-2 pumps onto towel dried hair)..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Ingredients List</label>
                                <textarea
                                    v-model="form.ingredients"
                                    rows="2"
                                    placeholder="Active key ingredients (e.g., Pure Argan Oil, HA 5%, Rose Extract)..."
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-800 focus:border-rose-500 focus:bg-white"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Toggles & Status -->
                        <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-slate-100">
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span>Active Online (Visible in Store)</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700 bg-emerald-50 text-emerald-800 px-3 py-1 rounded-xl border border-emerald-200/80">
                                <input
                                    v-model="form.is_trending"
                                    type="checkbox"
                                    class="rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span>🏠 Authorize & Showcase on Home Page</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                                <input
                                    v-model="form.in_stock"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <span>In Stock</span>
                            </label>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3 shrink-0">
                            <button
                                @click="showProductModal = false"
                                type="button"
                                class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button
                                :disabled="isSaving"
                                type="submit"
                                class="rounded-xl bg-rose-600 hover:bg-rose-700 px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-rose-600/20 transition cursor-pointer disabled:opacity-50"
                            >
                                {{ isSaving ? 'Saving Product...' : (modalMode === 'create' ? 'Create Product' : 'Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>

        <!-- DELETE CONFIRMATION MODAL -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDeleteModal && productToDelete"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 text-center space-y-4"
                >
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-600 text-2xl mx-auto">
                        🗑️
                    </div>
                    <h3 class="font-serif text-lg font-bold text-slate-900">Delete Product?</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Are you sure you want to remove <span class="font-bold text-slate-800">"{{ productToDelete.name }}"</span> from the marketplace store? This action cannot be undone.
                    </p>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <button
                            @click="showDeleteModal = false; productToDelete = null;"
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            :disabled="isDeleting"
                            @click="executeDelete"
                            type="button"
                            class="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-md transition cursor-pointer disabled:opacity-50"
                        >
                            {{ isDeleting ? 'Deleting...' : 'Confirm Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AdminLayout>
</template>