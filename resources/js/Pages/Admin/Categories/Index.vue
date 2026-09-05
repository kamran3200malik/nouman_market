<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    parentCategories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            parent_count: 0,
            sub_count: 0,
            active: 0,
            inactive: 0,
            total_services: 0,
        }),
    },
});

// View mode: 'tree' (hierarchy cards) or 'table'
const viewMode = ref('tree');

// Filter & Search states
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const levelFilter = ref(props.filters.level || '');
const parentFilter = ref(props.filters.parent_id || '');
const sortBy = ref(props.filters.sort_by || 'sort_order');
const sortDir = ref(props.filters.sort_dir || 'asc');

// Modal state
const showModal = ref(false);
const editingCategory = ref(null);
const submitting = ref(false);
const imagePreview = ref(null);
const imageFile = ref(null);

const form = ref({
    name: '',
    slug: '',
    parent_id: '',
    description: '',
    image: '',
    sort_order: 0,
    is_active: true,
});

// Preset Beauty Emojis / Icons
const popularIcons = [
    { icon: '💄', label: 'Makeup' },
    { icon: '💇', label: 'Hair' },
    { icon: '💅', label: 'Nails' },
    { icon: '✨', label: 'Skincare' },
    { icon: '🌿', label: 'Mehndi' },
    { icon: '👁️', label: 'Lashes' },
    { icon: '🧖', label: 'Spa' },
    { icon: '✂️', label: 'Barber' },
    { icon: '🌸', label: 'Bridal' },
    { icon: '🧴', label: 'Cosmetics' },
    { icon: '💆', label: 'Massage' },
    { icon: '🪞', label: 'Aesthetics' },
];

const filterStatuses = computed(() => [
    { key: '', label: 'All Categories', count: props.stats?.total ?? 0 },
    { key: 'active', label: '✓ Active', count: props.stats?.active ?? 0 },
    { key: 'inactive', label: '✕ Inactive', count: props.stats?.inactive ?? 0 },
]);

const applyFilters = () => {
    router.get(
        route('admin.categories.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            level: levelFilter.value || undefined,
            parent_id: parentFilter.value || undefined,
            sort_by: sortBy.value !== 'sort_order' ? sortBy.value : undefined,
            sort_dir: sortDir.value !== 'asc' ? sortDir.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const setStatus = (statusKey) => {
    statusFilter.value = statusKey;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    levelFilter.value = '';
    parentFilter.value = '';
    sortBy.value = 'sort_order';
    sortDir.value = 'asc';
    router.get(route('admin.categories.index'));
};

const generateSlug = () => {
    if (!form.value.slug || editingCategory.value === null) {
        form.value.slug = form.value.name
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

const selectIcon = (icon) => {
    form.value.image = icon;
    imagePreview.value = null;
    imageFile.value = null;
};

const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        imageFile.value = file;
        form.value.image = '';
        const reader = new FileReader();
        reader.onload = (event) => {
            imagePreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    imageFile.value = null;
    imagePreview.value = null;
    form.value.image = '';
};

const openCreateModal = (parentId = null) => {
    editingCategory.value = null;
    imagePreview.value = null;
    imageFile.value = null;
    form.value = {
        name: '',
        slug: '',
        parent_id: parentId ? String(parentId) : '',
        description: '',
        image: '✨',
        sort_order: (props.categories?.data?.length || 0) + 1,
        is_active: true,
    };
    showModal.value = true;
};

const openEditModal = (category) => {
    editingCategory.value = category;
    imageFile.value = null;
    imagePreview.value = null;

    let initialImage = category.image || '';
    if (initialImage && (initialImage.startsWith('http://') || initialImage.startsWith('https://') || initialImage.includes('/') || initialImage.includes('.'))) {
        imagePreview.value = storageUrl(initialImage);
        initialImage = '';
    }

    form.value = {
        name: category.name,
        slug: category.slug,
        parent_id: category.parent_id ? String(category.parent_id) : '',
        description: category.description || '',
        image: initialImage,
        sort_order: category.sort_order ?? 0,
        is_active: !!category.is_active,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCategory.value = null;
    imageFile.value = null;
    imagePreview.value = null;
};

const submitForm = () => {
    if (!form.value.name.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Required Field',
            text: 'Category name is required.',
        });
        return;
    }

    submitting.value = true;

    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('slug', form.value.slug);
    formData.append('parent_id', form.value.parent_id || '');
    formData.append('description', form.value.description || '');
    formData.append('sort_order', form.value.sort_order || 0);
    formData.append('is_active', form.value.is_active ? '1' : '0');

    if (imageFile.value) {
        formData.append('image_file', imageFile.value);
    } else if (form.value.image) {
        formData.append('image', form.value.image);
    }

    if (editingCategory.value) {
        // Submit update via POST with spoofing or direct post
        router.post(route('admin.categories.update', editingCategory.value.id), formData, {
            onSuccess: () => {
                submitting.value = false;
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Category updated successfully!',
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onError: (errors) => {
                submitting.value = false;
                const msg = Object.values(errors).flat().join('\n') || 'Failed to update category.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error Saving Category',
                    text: msg,
                });
            },
        });
    } else {
        router.post(route('admin.categories.store'), formData, {
            onSuccess: () => {
                submitting.value = false;
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Category created successfully!',
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onError: (errors) => {
                submitting.value = false;
                const msg = Object.values(errors).flat().join('\n') || 'Failed to create category.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error Creating Category',
                    text: msg,
                });
            },
        });
    }
};

const toggleStatus = (category) => {
    const nextStatus = !category.is_active;
    router.post(
        route('admin.categories.update-status', category.id),
        { is_active: nextStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Category '${category.name}' ${nextStatus ? 'activated' : 'deactivated'}`,
                    showConfirmButton: false,
                    timer: 2500,
                });
            },
        }
    );
};

const confirmDelete = (category) => {
    const childCount = category.children_count || (category.children ? category.children.length : 0);
    const serviceCount = category.services_count || 0;

    let warningText = 'Are you sure you want to delete this category?';
    if (childCount > 0 && serviceCount > 0) {
        warningText = `This category has ${childCount} subcategories and ${serviceCount} linked services. Subcategories will be moved to top-level.`;
    } else if (childCount > 0) {
        warningText = `This category has ${childCount} subcategories. They will be moved to top-level.`;
    } else if (serviceCount > 0) {
        warningText = `This category currently has ${serviceCount} services associated with it.`;
    }

    Swal.fire({
        title: `Delete "${category.name}"?`,
        text: warningText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.categories.destroy', category.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Category deleted successfully',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
            });
        }
    });
};

const getCategoryDisplayIcon = (category) => {
    if (!category.image) return '✨';
    if (category.image.length <= 4) return category.image; // Emoji
    return null;
};

const getCategoryImageUrl = (category) => {
    if (!category.image) return null;
    if (category.image.length <= 4) return null; // Emoji
    return storageUrl(category.image);
};
</script>

<template>
    <AdminLayout>
        <Head title="Categories Management - Admin Portal" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            Taxonomy & Catalog Architecture
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                        Service Categories
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Manage main beauty domains, organize subcategories, configure service taxonomy, and control catalog visibility.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- View Mode Toggle -->
                    <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200/80">
                        <button
                            @click="viewMode = 'tree'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                            :class="viewMode === 'tree' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            title="Grouped Hierarchy Cards View"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span>Grouped Tree</span>
                        </button>
                        <button
                            @click="viewMode = 'table'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                            :class="viewMode === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            title="Flat Data Table View"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Data Table</span>
                        </button>
                    </div>

                    <button
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200/80 hover:bg-slate-50 shadow-xs transition-all cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>

                    <button
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 shadow-md shadow-rose-500/20 transition-all hover:scale-[1.02] cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Category
                    </button>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <div class="rounded-2xl bg-white p-4.5 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-all">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Categories</p>
                        <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-xs">🗂️</span>
                    </div>
                    <p class="text-2xl font-black text-slate-900 mt-1.5">{{ stats?.total ?? 0 }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ stats?.parent_count ?? 0 }} Main • {{ stats?.sub_count ?? 0 }} Sub</p>
                </div>

                <div class="rounded-2xl bg-white p-4.5 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-all">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Main Domains</p>
                        <span class="w-7 h-7 rounded-lg bg-pink-50 flex items-center justify-center text-pink-600 text-xs">🏛️</span>
                    </div>
                    <p class="text-2xl font-black text-pink-600 mt-1.5">{{ stats?.parent_count ?? 0 }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Top-level categories</p>
                </div>

                <div class="rounded-2xl bg-white p-4.5 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-all">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Subcategories</p>
                        <span class="w-7 h-7 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600 text-xs">📂</span>
                    </div>
                    <p class="text-2xl font-black text-purple-600 mt-1.5">{{ stats?.sub_count ?? 0 }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Specialized sub-genres</p>
                </div>

                <div class="rounded-2xl bg-white p-4.5 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-all">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Status</p>
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 text-xs">🟢</span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-1.5">{{ stats?.active ?? 0 }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ stats?.inactive ?? 0 }} Inactive</p>
                </div>

                <div class="col-span-2 sm:col-span-1 rounded-2xl bg-white p-4.5 shadow-sm border border-slate-200/80 hover:border-slate-300 transition-all">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Linked Services</p>
                        <span class="w-7 h-7 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 text-xs">💅</span>
                    </div>
                    <p class="text-2xl font-black text-rose-600 mt-1.5">{{ stats?.total_services ?? 0 }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Active salon listings</p>
                </div>
            </div>

            <!-- Status Tabs & Filter Controls -->
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <!-- Status Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
                    <button
                        v-for="item in filterStatuses"
                        :key="item.key"
                        @click="setStatus(item.key)"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 border cursor-pointer"
                        :class="[
                            statusFilter === item.key
                                ? 'bg-slate-900 text-white border-slate-900 shadow-sm shadow-slate-900/10'
                                : 'bg-white text-slate-600 border-slate-200/80 hover:bg-slate-50 hover:text-slate-900'
                        ]"
                    >
                        <span>{{ item.label }}</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold"
                            :class="[
                                statusFilter === item.key
                                    ? 'bg-white/20 text-white'
                                    : 'bg-slate-100 text-slate-600'
                            ]"
                        >
                            {{ item.count }}
                        </span>
                    </button>
                </div>

                <!-- Hierarchy Level Filter Pills -->
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex rounded-xl p-1 bg-white border border-slate-200/80 text-xs font-medium">
                        <button
                            @click="levelFilter = ''; applyFilters()"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer"
                            :class="levelFilter === '' ? 'bg-rose-50 text-rose-700 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            All Levels
                        </button>
                        <button
                            @click="levelFilter = 'parent'; applyFilters()"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer"
                            :class="levelFilter === 'parent' ? 'bg-rose-50 text-rose-700 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Main Only
                        </button>
                        <button
                            @click="levelFilter = 'sub'; applyFilters()"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer"
                            :class="levelFilter === 'sub' ? 'bg-rose-50 text-rose-700 font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Subcategories Only
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search & Dropdown Filter Bar -->
            <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200/80">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="relative md:col-span-6">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search categories by name, slug, description, or parent..."
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all outline-none"
                            @keyup.enter="applyFilters"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <button
                            v-if="search"
                            @click="search = ''; applyFilters()"
                            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Parent Category Filter -->
                    <div class="md:col-span-3">
                        <select
                            v-model="parentFilter"
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50/50 text-xs sm:text-sm text-slate-800 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all outline-none"
                        >
                            <option value="">All Parent Domains</option>
                            <option v-for="parent in parentCategories" :key="parent.id" :value="parent.id">
                                {{ parent.image ? parent.image + ' ' : '' }}{{ parent.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div class="md:col-span-3">
                        <select
                            v-model="sortBy"
                            @change="applyFilters"
                            class="w-full py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50/50 text-xs sm:text-sm text-slate-800 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 transition-all outline-none"
                        >
                            <option value="sort_order">Sort: Custom Order</option>
                            <option value="name">Sort: Name (A to Z)</option>
                            <option value="services_count">Sort: Most Services</option>
                            <option value="created_at">Sort: Recently Created</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: GROUPED HIERARCHY / TREE CARDS VIEW -->
            <div v-if="viewMode === 'tree'" class="space-y-4">
                <div v-if="parentCategories.length === 0" class="rounded-3xl bg-white p-12 text-center border border-slate-200/80 shadow-sm">
                    <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto text-2xl mb-4">
                        📂
                    </div>
                    <h3 class="text-base font-bold text-slate-900">No categories found</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
                        Get started by adding your first beauty domain category to organize services and artist catalogs.
                    </p>
                    <button
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-md shadow-rose-500/20 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create First Category
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 gap-4">
                    <div
                        v-for="parent in parentCategories"
                        :key="parent.id"
                        class="rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all overflow-hidden"
                    >
                        <!-- Main Parent Card Header -->
                        <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/40 border-b border-slate-100">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <!-- Category Icon/Avatar -->
                                <div class="w-12 h-12 rounded-2xl bg-white shadow-xs border border-slate-200 flex items-center justify-center text-2xl shrink-0 overflow-hidden">
                                    <img
                                        v-if="getCategoryImageUrl(parent)"
                                        :src="getCategoryImageUrl(parent)"
                                        :alt="parent.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else>{{ getCategoryDisplayIcon(parent) }}</span>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900">
                                            {{ parent.name }}
                                        </h2>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-100 text-slate-600 border border-slate-200">
                                            /{{ parent.slug }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="parent.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="parent.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                            {{ parent.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-1">
                                        {{ parent.description || 'No description provided for this domain.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Badges & Actions -->
                            <div class="flex items-center gap-3 flex-wrap self-end md:self-auto">
                                <div class="flex items-center gap-2 text-xs font-semibold">
                                    <span class="px-2.5 py-1 rounded-xl bg-purple-50 text-purple-700 border border-purple-200">
                                        {{ parent.children ? parent.children.length : 0 }} Subcategories
                                    </span>
                                    <span class="px-2.5 py-1 rounded-xl bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ parent.services_count ?? 0 }} Services
                                    </span>
                                    <span class="px-2 py-1 rounded-xl bg-slate-100 text-slate-500 text-[11px] font-mono" title="Sort Order">
                                        #{{ parent.sort_order ?? 0 }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        @click="openCreateModal(parent.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition-all cursor-pointer"
                                        title="Add Subcategory under this domain"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Sub
                                    </button>

                                    <button
                                        @click="openEditModal(parent)"
                                        class="p-1.5 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-white border border-transparent hover:border-slate-200 transition-all cursor-pointer"
                                        title="Edit Domain"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <button
                                        @click="toggleStatus(parent)"
                                        class="p-1.5 rounded-xl transition-all cursor-pointer"
                                        :class="parent.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100'"
                                        :title="parent.is_active ? 'Deactivate Category' : 'Activate Category'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    <button
                                        @click="confirmDelete(parent)"
                                        class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all cursor-pointer"
                                        title="Delete Category"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Subcategories Grid / List -->
                        <div class="p-4 sm:p-5">
                            <div v-if="!parent.children || parent.children.length === 0" class="text-center py-4 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                                <p class="text-xs text-slate-400 font-medium">No subcategories under {{ parent.name }} yet.</p>
                                <button
                                    @click="openCreateModal(parent.id)"
                                    class="text-xs font-bold text-rose-600 hover:text-rose-700 mt-1 inline-flex items-center gap-1 cursor-pointer"
                                >
                                    + Add first subcategory
                                </button>
                            </div>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div
                                    v-for="sub in parent.children"
                                    :key="sub.id"
                                    class="p-3.5 rounded-xl bg-slate-50/80 hover:bg-slate-100/80 border border-slate-200/70 transition-all flex items-center justify-between gap-3 group"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-xl bg-white shadow-xs border border-slate-200/80 flex items-center justify-center text-sm shrink-0 overflow-hidden">
                                            <img
                                                v-if="getCategoryImageUrl(sub)"
                                                :src="getCategoryImageUrl(sub)"
                                                :alt="sub.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else>{{ getCategoryDisplayIcon(sub) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-xs font-bold text-slate-900 truncate">
                                                    {{ sub.name }}
                                                </p>
                                                <span
                                                    v-if="!sub.is_active"
                                                    class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-200 text-slate-600"
                                                >
                                                    Off
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] text-slate-500 font-mono">/{{ sub.slug }}</span>
                                                <span class="text-[10px] font-bold text-rose-600">{{ sub.services_count ?? 0 }} services</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity shrink-0">
                                        <button
                                            @click="openEditModal(sub)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white transition-all cursor-pointer"
                                            title="Edit Subcategory"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="toggleStatus(sub)"
                                            class="p-1 rounded-lg transition-all cursor-pointer"
                                            :class="sub.is_active ? 'text-emerald-500 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-200'"
                                            :title="sub.is_active ? 'Deactivate' : 'Activate'"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="confirmDelete(sub)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all cursor-pointer"
                                            title="Delete Subcategory"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: FLAT DATA TABLE VIEW -->
            <div v-else class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4">Order</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Type / Parent</th>
                                <th class="py-3.5 px-4">Slug</th>
                                <th class="py-3.5 px-4 text-center">Services</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <tr
                                v-for="cat in categories.data"
                                :key="cat.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <!-- Order -->
                                <td class="py-3.5 px-4 font-mono text-slate-400 text-xs">
                                    #{{ cat.sort_order ?? 0 }}
                                </td>

                                <!-- Category Info -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200/80 flex items-center justify-center text-lg shrink-0 overflow-hidden">
                                            <img
                                                v-if="getCategoryImageUrl(cat)"
                                                :src="getCategoryImageUrl(cat)"
                                                :alt="cat.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else>{{ getCategoryDisplayIcon(cat) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                                                {{ cat.name }}
                                                <span v-if="!cat.parent_id" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-pink-100 text-pink-700">
                                                    Main Domain
                                                </span>
                                            </p>
                                            <p class="text-[11px] text-slate-400 truncate max-w-xs">
                                                {{ cat.description || 'No description' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Parent / Type -->
                                <td class="py-3.5 px-4">
                                    <div v-if="cat.parent" class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                        <span class="text-slate-400">↳ Sub of</span>
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-800 border border-slate-200">
                                            {{ cat.parent.name }}
                                        </span>
                                    </div>
                                    <div v-else class="text-xs font-bold text-purple-600 flex items-center gap-1">
                                        <span>🏛️ Top Domain</span>
                                        <span class="text-[10px] text-slate-400">({{ cat.children_count || 0 }} subs)</span>
                                    </div>
                                </td>

                                <!-- Slug -->
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                    /{{ cat.slug }}
                                </td>

                                <!-- Services Count -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ cat.services_count ?? 0 }}
                                    </span>
                                </td>

                                <!-- Status Toggle -->
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        @click="toggleStatus(cat)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold transition-all cursor-pointer"
                                        :class="cat.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        {{ cat.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="!cat.parent_id"
                                            @click="openCreateModal(cat.id)"
                                            class="p-1.5 rounded-xl text-purple-600 hover:bg-purple-50 transition-all cursor-pointer"
                                            title="Add Subcategory"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openEditModal(cat)"
                                            class="p-1.5 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-all cursor-pointer"
                                            title="Edit Category"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(cat)"
                                            class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all cursor-pointer"
                                            title="Delete Category"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!categories.data || categories.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No categories match the applied filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Table View -->
                <div v-if="categories.links && categories.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                    <p class="text-xs text-slate-500 font-medium">
                        Showing {{ categories.from ?? 0 }} to {{ categories.to ?? 0 }} of {{ categories.total ?? 0 }} categories
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in categories.links" :key="index">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url)"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                :class="link.active ? 'bg-rose-500 text-white shadow-xs' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-2.5 py-1.5 text-xs text-slate-300 select-none"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- CREATE / EDIT CATEGORY MODAL -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity overflow-y-auto"
            >
                <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 my-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                                {{ editingCategory ? '✏️' : '✨' }}
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-900">
                                    {{ editingCategory ? 'Edit Category' : 'Create New Category' }}
                                </h3>
                                <p class="text-xs text-slate-400">
                                    {{ editingCategory ? 'Update category taxonomy and details' : 'Add a new beauty domain or subcategory' }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="closeModal"
                            class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-all cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <div class="space-y-4 py-4">
                        <!-- Category Name & Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Category Name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    @input="generateSlug"
                                    placeholder="e.g. Bridal Makeup, Nail Art"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Slug / URL Key
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-mono">/</span>
                                    <input
                                        v-model="form.slug"
                                        type="text"
                                        placeholder="e.g. bridal-makeup"
                                        class="w-full pl-6 pr-3.5 py-2.5 rounded-xl border border-slate-200 font-mono text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Parent Category & Sort Order -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Parent Domain
                                </label>
                                <select
                                    v-model="form.parent_id"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all"
                                >
                                    <option value="">None (Top-Level Main Domain)</option>
                                    <option
                                        v-for="parent in parentCategories"
                                        :key="parent.id"
                                        :value="String(parent.id)"
                                        :disabled="editingCategory && Number(editingCategory.id) === Number(parent.id)"
                                    >
                                        {{ parent.image ? parent.image + ' ' : '' }}{{ parent.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Display Order (Rank)
                                </label>
                                <input
                                    v-model="form.sort_order"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all"
                                />
                            </div>
                        </div>

                        <!-- Icon / Emoji & Image Selector -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Icon & Visual Identity
                            </label>
                            
                            <!-- Quick Emoji Grid -->
                            <div class="flex items-center gap-1.5 flex-wrap mb-2.5">
                                <button
                                    v-for="item in popularIcons"
                                    :key="item.icon"
                                    type="button"
                                    @click="selectIcon(item.icon)"
                                    class="w-8 h-8 rounded-xl flex items-center justify-center text-sm transition-all border cursor-pointer"
                                    :class="form.image === item.icon ? 'bg-rose-50 border-rose-500 ring-2 ring-rose-500/20 scale-110' : 'bg-slate-50 border-slate-200/80 hover:bg-slate-100'"
                                    :title="item.label"
                                >
                                    {{ item.icon }}
                                </button>
                            </div>

                            <!-- Custom Image Upload or Emoji Input -->
                            <div class="flex items-center gap-3">
                                <div class="relative flex-1">
                                    <input
                                        v-model="form.image"
                                        type="text"
                                        placeholder="Type custom emoji or icon code (e.g. 💄)"
                                        class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 outline-none focus:border-rose-500"
                                    />
                                </div>

                                <span class="text-xs text-slate-400 font-bold uppercase">OR</span>

                                <label class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 cursor-pointer flex items-center gap-1.5 shrink-0">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Upload Photo
                                    <input type="file" accept="image/*" class="hidden" @change="handleImageUpload" />
                                </label>
                            </div>

                            <!-- Image Preview if chosen -->
                            <div v-if="imagePreview" class="mt-2 flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-200">
                                <img :src="imagePreview" alt="Preview" class="w-10 h-10 rounded-lg object-cover" />
                                <span class="text-xs text-slate-600 font-medium">Custom image selected</span>
                                <button @click="removeImage" class="text-xs text-rose-600 hover:text-rose-700 font-bold ml-auto cursor-pointer">
                                    Remove
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Short description explaining what beauty services fall under this category..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none transition-all"
                            />
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Active Status</p>
                                <p class="text-[11px] text-slate-500">When active, this category is visible in marketplace search and booking filters.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-all cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="submitting"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 shadow-md shadow-rose-500/20 transition-all cursor-pointer flex items-center gap-2"
                        >
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ editingCategory ? 'Update Category' : 'Create Category' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
