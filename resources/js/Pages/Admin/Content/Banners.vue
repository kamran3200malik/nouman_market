<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    banners: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({ total_banners: 0, active_banners: 0 }),
    },
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(
        route('admin.content.banners'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

// Modal State
const isModalOpen = ref(false);
const editingBanner = ref(null);
const imagePreview = ref(null);

const form = useForm({
    title: '',
    subtitle: '',
    tag: '',
    button_text: 'Shop Now',
    link_url: '',
    product_id: '',
    category_id: '',
    price: '',
    position: 'home_hero',
    sort_order: 0,
    is_active: true,
    image: '',
    image_file: null,
});

const openCreateModal = () => {
    editingBanner.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.button_text = 'Shop Collection';
    form.position = 'home_hero';
    imagePreview.value = null;
    isModalOpen.value = true;
};

const openEditModal = (banner) => {
    editingBanner.value = banner;
    form.clearErrors();
    form.title = banner.title || '';
    form.subtitle = banner.subtitle || '';
    form.tag = banner.tag || '';
    form.button_text = banner.button_text || 'Shop Now';
    form.link_url = banner.link_url || '';
    form.product_id = banner.product_id || '';
    form.category_id = banner.category_id || '';
    form.price = banner.price || '';
    form.position = banner.position || 'home_hero';
    form.sort_order = banner.sort_order || 0;
    form.is_active = Boolean(banner.is_active);
    form.image = banner.image || '';
    form.image_file = null;
    imagePreview.value = storageUrl(banner.image);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingBanner.value = null;
    form.reset();
    imagePreview.value = null;
};

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image_file = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const onProductSelect = () => {
    if (form.product_id) {
        const prod = props.products.find(p => p.id === parseInt(form.product_id));
        if (prod) {
            form.link_url = `/products/${prod.id}`;
            if (!form.price) form.price = prod.price;
        }
    }
};

const onCategorySelect = () => {
    if (form.category_id) {
        const cat = props.categories.find(c => c.id === parseInt(form.category_id));
        if (cat) {
            form.link_url = `/products?category=${cat.slug}`;
        }
    }
};

const submitForm = () => {
    if (editingBanner.value) {
        form.post(route('admin.content.update-banner-post', editingBanner.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Banner updated!', showConfirmButton: false, timer: 3000 });
            },
        });
    } else {
        form.post(route('admin.content.store-banner'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Banner created!', showConfirmButton: false, timer: 3000 });
            },
        });
    }
};

const toggleStatus = (banner) => {
    router.post(route('admin.content.toggle-banner-status', banner.id), {}, { preserveScroll: true });
};

const deleteBanner = (banner) => {
    Swal.fire({
        title: 'Delete Banner?',
        text: `Permanently delete "${banner.title}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Delete',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.content.destroy-banner', banner.id), { preserveScroll: true });
        }
    });
};
</script>

<template>
    <Head title="Hero Banners & Content | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                        Promotional Hero Banners
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Manage luxury billboard sliders, flash deal banners, and departmental promotions across the storefront.
                    </p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider shadow-md shadow-rose-500/20 transition-all hover:scale-102 cursor-pointer shrink-0"
                >
                    <span>＋</span>
                    <span>Create New Banner</span>
                </button>
            </div>

            <!-- Stats Ribbon -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-bold uppercase text-slate-400">Total Banners</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.total_banners }}</p>
                </div>
                <div class="p-5 rounded-3xl bg-white border border-emerald-100 shadow-xs">
                    <p class="text-[11px] font-bold uppercase text-emerald-600">Active Live</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.active_banners }}</p>
                </div>
            </div>

            <!-- Banners Grid -->
            <div v-if="banners.data && banners.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="banner in banners.data"
                    :key="banner.id"
                    class="group rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between"
                >
                    <div>
                        <!-- Banner Image Container -->
                        <div class="relative aspect-video w-full overflow-hidden bg-slate-900">
                            <img
                                :src="storageUrl(banner.image)"
                                :alt="banner.title"
                                class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>

                            <span v-if="banner.tag" class="absolute top-3 left-3 rounded-lg bg-rose-600 text-white text-[10px] font-black uppercase px-2.5 py-0.5 shadow-sm">
                                {{ banner.tag }}
                            </span>

                            <button
                                @click="toggleStatus(banner)"
                                class="absolute top-3 right-3 px-2.5 py-0.5 rounded-full text-[10px] font-bold border transition cursor-pointer"
                                :class="banner.is_active ? 'bg-emerald-500/90 text-white border-white/30' : 'bg-slate-900/90 text-slate-300 border-white/20'"
                            >
                                {{ banner.is_active ? 'Live' : 'Draft' }}
                            </button>

                            <div class="absolute bottom-3 inset-x-3 text-white">
                                <h3 class="font-bold text-sm sm:text-base leading-tight drop-shadow-sm">{{ banner.title }}</h3>
                                <p v-if="banner.subtitle" class="text-[11px] text-slate-200 line-clamp-1 mt-0.5">{{ banner.subtitle }}</p>
                            </div>
                        </div>

                        <!-- Meta Info -->
                        <div class="p-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Position:</span>
                                <span class="font-bold text-slate-700 uppercase">{{ banner.position || 'home_hero' }}</span>
                            </div>
                            <div v-if="banner.button_text" class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">CTA Button:</span>
                                <span class="font-bold text-rose-600">{{ banner.button_text }}</span>
                            </div>
                            <div v-if="banner.link_url" class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Target Link:</span>
                                <span class="font-mono text-slate-600 truncate max-w-[150px]">{{ banner.link_url }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-3 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400 font-bold">Order #{{ banner.sort_order }}</span>
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="openEditModal(banner)"
                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:text-rose-600 hover:border-rose-200 text-xs font-bold transition cursor-pointer"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteBanner(banner)"
                                class="p-1.5 rounded-xl text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                title="Delete"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8 space-y-4">
                <span class="text-4xl">🎨</span>
                <h3 class="font-bold text-slate-900 text-sm">No promotional banners yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Create hero billboard banners to highlight flash sales, new beauty product launches, or seasonal skincare discounts.</p>
                <button
                    @click="openCreateModal"
                    class="inline-block px-6 py-2.5 rounded-full bg-rose-600 text-white font-bold text-xs uppercase shadow-md hover:bg-rose-700 transition cursor-pointer"
                >
                    Create First Banner
                </button>
            </div>

            <!-- MODAL: CREATE / EDIT BANNER -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="w-full max-w-xl rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h3 class="text-base font-bold text-slate-900">
                            {{ editingBanner ? 'Edit Promotional Banner' : 'Create New Promotional Banner' }}
                        </h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Banner Headline / Title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="e.g. 100% Genuine French Perfumes"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Subheading / Description</label>
                                <textarea
                                    v-model="form.subtitle"
                                    rows="2"
                                    placeholder="e.g. Exclusive oriental and floral fragrances with verified batch codes."
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tag / Pill Badge</label>
                                <input
                                    v-model="form.tag"
                                    type="text"
                                    placeholder="e.g. NEW LAUNCH / 30% OFF"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">CTA Button Text</label>
                                <input
                                    v-model="form.button_text"
                                    type="text"
                                    placeholder="Shop Collection"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <!-- Target Destination -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                            <p class="text-xs font-bold text-slate-800">Banner Destination Link</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Link to Product</label>
                                    <select
                                        v-model="form.product_id"
                                        @change="onProductSelect"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900"
                                    >
                                        <option value="">-- Choose Product --</option>
                                        <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Link to Department / Category</label>
                                    <select
                                        v-model="form.category_id"
                                        @change="onCategorySelect"
                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900"
                                    >
                                        <option value="">-- Choose Category --</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Or Custom Target URL</label>
                                <input
                                    v-model="form.link_url"
                                    type="text"
                                    placeholder="/products?trending=1"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs text-slate-900"
                                />
                            </div>
                        </div>

                        <!-- Image Upload & Preview -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Banner Graphic Image</label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleFileSelect"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100"
                            />

                            <div v-if="imagePreview" class="mt-2.5 aspect-video w-full rounded-2xl overflow-hidden bg-slate-900 border border-slate-200">
                                <img :src="imagePreview" alt="Preview" class="h-full w-full object-cover" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Display Sort Order</label>
                                <input
                                    v-model="form.sort_order"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900"
                                />
                            </div>

                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <span class="text-xs font-bold text-slate-800">Publish Immediately (Active)</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-sm cursor-pointer"
                            >
                                {{ editingBanner ? 'Save Changes' : 'Create Banner' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
