<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    pages: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_pages: 0,
            published_pages: 0,
            draft_pages: 0,
            seo_pages: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const viewMode = ref('grid'); // 'grid' | 'table'
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Modal state
const showModal = ref(false);
const editingPage = ref(null);
const submitting = ref(false);
const activeModalTab = ref('content'); // 'content' | 'seo' | 'preview'

// Form state
const form = ref({
    title: '',
    slug: '',
    content: '',
    meta_title: '',
    meta_description: '',
    is_published: true,
});

const applyFilters = () => {
    router.get(
        route('admin.content.pages'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const generateSlug = () => {
    if (!editingPage.value) {
        form.value.slug = form.value.title
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
};

const openCreateModal = () => {
    editingPage.value = null;
    activeModalTab.value = 'content';
    form.value = {
        title: '',
        slug: '',
        content: '',
        meta_title: '',
        meta_description: '',
        is_published: true,
    };
    showModal.value = true;
};

const openEditModal = (page) => {
    editingPage.value = page;
    activeModalTab.value = 'content';
    form.value = {
        title: page.title,
        slug: page.slug,
        content: page.content,
        meta_title: page.meta_title || '',
        meta_description: page.meta_description || '',
        is_published: Boolean(page.is_published),
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPage.value = null;
};

const insertTag = (prefix, suffix = '') => {
    form.value.content = form.value.content + `\n${prefix}Your Text Here${suffix}\n`;
};

const copyUrl = (slug) => {
    const fullUrl = `${window.location.origin}/${slug}`;
    navigator.clipboard.writeText(fullUrl).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '🔗 Page link copied to clipboard!',
            showConfirmButton: false,
            timer: 2000,
        });
    });
};

const togglePageStatus = (page) => {
    router.post(
        route('admin.content.toggle-page-status', page.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Page is now ${!page.is_published ? 'Published' : 'Draft'}`,
                    showConfirmButton: false,
                    timer: 2000,
                });
            },
        }
    );
};

const savePage = () => {
    submitting.value = true;
    const url = editingPage.value
        ? route('admin.content.update-page', editingPage.value.id)
        : route('admin.content.store-page');
    const method = editingPage.value ? 'put' : 'post';

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            submitting.value = false;
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Page ${editingPage.value ? 'updated' : 'created'} successfully!`,
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onError: (err) => {
            submitting.value = false;
            Swal.fire({
                icon: 'error',
                title: 'Operation Failed',
                text: Object.values(err)[0] || 'Please check form inputs.',
            });
        },
    });
};

const deletePage = (page) => {
    Swal.fire({
        title: 'Delete CMS Page?',
        text: `Are you sure you want to permanently delete "${page.title}" (/${page.slug})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(route('admin.content.destroy-page', page.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Page deleted successfully.',
                        showConfirmButton: false,
                        timer: 2500,
                    });
                },
            });
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="CMS Editorial Pages & Policy Documents | Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- 1. HEADER & ACTIONS -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            CMS Pages & Policy Documents
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            EDITORIAL CMS
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Curate storefront policy pages, about us, terms of service, privacy guidelines, FAQs, and custom SEO landing URLs.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-glam-500 via-rose-500 to-pink-600 hover:from-glam-600 hover:to-pink-700 text-white text-xs font-bold shadow-lg shadow-pink-950/20 transition-all active:scale-98 flex items-center gap-2 self-start sm:self-auto cursor-pointer"
                >
                    <span class="text-base">+</span>
                    <span>Create Editorial Page</span>
                </button>
            </div>

            <!-- 2. DOCUMENT KPI RIBBON (4 LUXURY CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Pages -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Custom Pages</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.total_pages }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Documents created</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl shadow-xs">
                        📑
                    </div>
                </div>

                <!-- Published Pages -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Live Published</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-emerald-950 mt-1">{{ stats.published_pages }}</h3>
                        <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">Publicly accessible</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        🟢
                    </div>
                </div>

                <!-- Draft Documents -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Drafts / In-Progress</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.draft_pages }}</h3>
                        <p class="text-[10px] text-amber-700 font-semibold mt-0.5">Unpublished drafts</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl shadow-xs">
                        📝
                    </div>
                </div>

                <!-- SEO Optimized Pages -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800">SEO Indexed Pages</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-purple-950 mt-1">{{ stats.seo_pages }}</h3>
                        <p class="text-[10px] text-purple-700 font-semibold mt-0.5">With custom meta tags</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shadow-xs">
                        🔍
                    </div>
                </div>
            </div>

            <!-- 3. FILTER CONTROLS & VIEW SWITCHER -->
            <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                    <div class="relative flex-1 min-w-[200px]">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search page by title, slug or keywords..."
                            class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                            @keyup.enter="applyFilters"
                        />
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                    </div>

                    <select
                        v-model="statusFilter"
                        class="py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                        @change="applyFilters"
                    >
                        <option value="">All Statuses</option>
                        <option value="published">🟢 Published Only</option>
                        <option value="draft">⚪ Drafts Only</option>
                    </select>
                </div>

                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl">
                    <button
                        type="button"
                        @click="viewMode = 'grid'"
                        class="p-2 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        title="Visual Cards View"
                    >
                        <span>🖼️ Cards</span>
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'table'"
                        class="p-2 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        title="Ledger Table View"
                    >
                        <span>📑 Table</span>
                    </button>
                </div>
            </div>

            <!-- 4. VIEW MODE 1: VISUAL SHOWCASE CARDS -->
            <div v-if="viewMode === 'grid'">
                <div v-if="pages.data && pages.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="page in pages.data"
                        :key="page.id"
                        class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-col justify-between hover:shadow-lg hover:border-rose-200 transition-all duration-300 group space-y-4"
                    >
                        <!-- Top Header -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="page.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                >
                                    <span>{{ page.is_published ? '● Live Published' : '○ Draft' }}</span>
                                </span>

                                <span v-if="page.meta_title" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                    🔍 SEO Ready
                                </span>
                            </div>

                            <h3 class="font-serif font-bold text-slate-900 text-lg group-hover:text-glam-600 transition-colors line-clamp-1">
                                {{ page.title }}
                            </h3>

                            <!-- Slug Link Pill -->
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200/70 text-xs">
                                <span class="font-mono text-[11px] text-slate-600 truncate max-w-[200px]">
                                    /{{ page.slug }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyUrl(page.slug)"
                                    class="text-[10px] font-bold text-glam-700 hover:text-glam-900 cursor-pointer"
                                    title="Copy link"
                                >
                                    📋 Copy
                                </button>
                            </div>

                            <!-- Content Excerpt -->
                            <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed">
                                {{ page.content.replace(/<[^>]*>?/gm, '').substring(0, 160) }}...
                            </p>
                        </div>

                        <!-- Footer & Actions -->
                        <div class="pt-4 border-t border-rose-50 space-y-3">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>Updated: {{ formatDate(page.updated_at) }}</span>
                                <span>{{ page.content.length }} chars</span>
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <button
                                    type="button"
                                    @click="togglePageStatus(page)"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer"
                                    :class="page.is_published ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'"
                                >
                                    {{ page.is_published ? 'Unpublish' : 'Publish' }}
                                </button>

                                <div class="flex items-center gap-1.5">
                                    <Link
                                        :href="`/${page.slug}`"
                                        target="_blank"
                                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                                    >
                                        👁️ View
                                    </Link>
                                    <button
                                        type="button"
                                        @click="openEditModal(page)"
                                        class="px-3 py-1.5 rounded-xl bg-glam-50 hover:bg-glam-600 hover:text-white text-glam-700 font-bold text-xs transition cursor-pointer"
                                    >
                                        ✏️ Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="deletePage(page)"
                                        class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold text-xs transition cursor-pointer"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-16">
                    <AppEmptyState
                        icon="📄"
                        title="No editorial pages found"
                        description="Click 'Create Editorial Page' to compose your first custom storefront document."
                    />
                </div>
            </div>

            <!-- 5. VIEW MODE 2: FULL LEDGER TABLE -->
            <div v-else-if="viewMode === 'table'" class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <div v-if="pages.data && pages.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4">Document Title</th>
                                <th class="py-3.5 px-4">URL Slug</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center">SEO Ready</th>
                                <th class="py-3.5 px-4">Last Updated</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr v-for="page in pages.data" :key="page.id" class="hover:bg-rose-50/30 transition-colors">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ page.title }}</p>
                                    <p class="text-[10px] text-slate-400 line-clamp-1">{{ page.meta_title || 'No meta title set' }}</p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-slate-600 text-xs">
                                    /{{ page.slug }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="togglePageStatus(page)"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition"
                                        :class="page.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ page.is_published ? '✓ Published' : 'Draft' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span v-if="page.meta_title" class="text-purple-700 font-bold text-xs">✓ Optimized</span>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500">
                                    {{ formatDate(page.updated_at) }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="`/${page.slug}`"
                                            target="_blank"
                                            class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition"
                                        >
                                            View
                                        </Link>
                                        <button
                                            type="button"
                                            @click="openEditModal(page)"
                                            class="px-2.5 py-1 rounded-xl bg-glam-50 hover:bg-glam-600 hover:text-white text-glam-700 font-bold text-[11px] transition cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deletePage(page)"
                                            class="px-2.5 py-1 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold text-[11px] transition cursor-pointer"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-16">
                    <AppEmptyState
                        icon="📄"
                        title="No pages found"
                        description="Publish custom policy and editorial documents for the storefront."
                    />
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="pages.links && pages.links.length > 3" class="p-4 bg-white rounded-3xl border border-rose-100 shadow-xs">
                <AppPagination :links="pages.links" />
            </div>
        </div>

        <!-- 6. COMPREHENSIVE COMPOSER & SEO MODAL -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-onyx-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeModal"
        >
            <div class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100 animate-in fade-in zoom-in duration-200 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="p-6 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-glam-700">Editorial Studio</span>
                        <h3 class="font-serif text-lg font-bold text-slate-900 mt-0.5">
                            {{ editingPage ? `Edit Page: ${editingPage.title}` : 'Compose New Editorial Page' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-full bg-white text-slate-500 hover:text-slate-900 flex items-center justify-center shadow-xs cursor-pointer"
                        @click="closeModal"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Sub-Tabs -->
                <div class="px-6 pt-3 flex items-center gap-2 border-b border-rose-100 bg-white">
                    <button
                        type="button"
                        @click="activeModalTab = 'content'"
                        class="px-4 py-2 text-xs font-bold transition cursor-pointer border-b-2"
                        :class="activeModalTab === 'content' ? 'border-glam-600 text-glam-800' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        📄 Page Content & Layout
                    </button>
                    <button
                        type="button"
                        @click="activeModalTab = 'seo'"
                        class="px-4 py-2 text-xs font-bold transition cursor-pointer border-b-2"
                        :class="activeModalTab === 'seo' ? 'border-glam-600 text-glam-800' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        🔍 SEO & SERP Optimization
                    </button>
                    <button
                        type="button"
                        @click="activeModalTab = 'preview'"
                        class="px-4 py-2 text-xs font-bold transition cursor-pointer border-b-2"
                        :class="activeModalTab === 'preview' ? 'border-glam-600 text-glam-800' : 'border-transparent text-slate-500 hover:text-slate-800'"
                    >
                        👁️ Live Responsive Preview
                    </button>
                </div>

                <!-- Modal Form Body -->
                <form @submit.prevent="savePage" class="p-6 space-y-5 overflow-y-auto flex-1">
                    <!-- TAB 1: CONTENT -->
                    <div v-show="activeModalTab === 'content'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Document Title <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="e.g. Terms of Service, About Our Marketplace"
                                    class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    @input="generateSlug"
                                    required
                                />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">URL Slug / Permalink <span class="text-rose-500">*</span></label>
                                <div class="mt-1 flex items-center rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden">
                                    <span class="pl-3 pr-1 text-xs text-slate-400 font-mono">/</span>
                                    <input
                                        v-model="form.slug"
                                        type="text"
                                        placeholder="terms-of-service"
                                        class="w-full p-2.5 bg-transparent text-xs sm:text-sm text-slate-900 font-mono focus:outline-none"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Markdown / Formatting Toolbar -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-bold text-slate-700">Page Content (HTML / Markdown Allowed) <span class="text-rose-500">*</span></label>
                                <div class="flex items-center gap-1 text-[10px]">
                                    <button type="button" @click="insertTag('## ')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">H2</button>
                                    <button type="button" @click="insertTag('### ')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">H3</button>
                                    <button type="button" @click="insertTag('**', '**')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">Bold</button>
                                    <button type="button" @click="insertTag('- ')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">List</button>
                                    <button type="button" @click="insertTag('> ')" class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">Quote</button>
                                </div>
                            </div>
                            <textarea
                                v-model="form.content"
                                rows="12"
                                placeholder="Write the complete document contents here..."
                                class="w-full p-3.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 font-sans focus:ring-2 focus:ring-glam-500 leading-relaxed font-mono"
                                required
                            ></textarea>
                            <div class="flex items-center justify-between text-[10px] text-slate-400">
                                <span>Formatting tags are supported seamlessly.</span>
                                <span>{{ form.content.length }} characters</span>
                            </div>
                        </div>

                        <!-- Publish Switch -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-900">Publish Immediately</p>
                                <p class="text-[10px] text-slate-400">Make this page live and accessible at /{{ form.slug || 'slug' }}</p>
                            </div>
                            <input
                                v-model="form.is_published"
                                type="checkbox"
                                class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                            />
                        </div>
                    </div>

                    <!-- TAB 2: SEO CONFIGURATION & GOOGLE SERP SIMULATION -->
                    <div v-show="activeModalTab === 'seo'" class="space-y-5">
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-bold text-slate-700">SEO Meta Title</label>
                                    <span class="text-[10px]" :class="form.meta_title.length > 60 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                        {{ form.meta_title.length }} / 60 chars (Optimal)
                                    </span>
                                </div>
                                <input
                                    v-model="form.meta_title"
                                    type="text"
                                    placeholder="e.g. Terms of Service | Premium Beauty Salon Marketplace"
                                    class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-bold text-slate-700">SEO Meta Description</label>
                                    <span class="text-[10px]" :class="form.meta_description.length > 160 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                        {{ form.meta_description.length }} / 160 chars (Optimal)
                                    </span>
                                </div>
                                <textarea
                                    v-model="form.meta_description"
                                    rows="3"
                                    placeholder="Enter concise snippet summarizing this page for search engine results..."
                                    class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Live Google Search Result Mockup -->
                        <div class="p-5 rounded-2xl bg-white border border-slate-200 space-y-2 shadow-xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Search Result Snippet Simulation</span>
                            <div class="space-y-1 pt-1">
                                <p class="text-xs text-emerald-800 flex items-center gap-1 font-sans">
                                    <span>https://beauty.pk</span>
                                    <span>&rsaquo;</span>
                                    <span>{{ form.slug || 'page-slug' }}</span>
                                </p>
                                <h4 class="text-base text-blue-800 hover:underline font-medium cursor-pointer">
                                    {{ form.meta_title || form.title || 'Page Title | Beauty Salon Marketplace' }}
                                </h4>
                                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                    {{ form.meta_description || 'Read our detailed document and marketplace policies across top beauty salons in Pakistan.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: LIVE PREVIEW -->
                    <div v-show="activeModalTab === 'preview'" class="space-y-4">
                        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                            <div class="border-b border-slate-200 pb-4">
                                <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">
                                    {{ form.title || 'Document Title Preview' }}
                                </h1>
                                <p class="text-xs text-slate-400 mt-1">
                                    Published on {{ new Date().toLocaleDateString('en-PK', { month: 'long', day: 'numeric', year: 'numeric' }) }}
                                </p>
                            </div>
                            <div class="prose prose-sm max-w-none text-slate-700 whitespace-pre-line leading-relaxed">
                                {{ form.content || 'Your page content preview will render here as you type in the editor tab.' }}
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-rose-100 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition cursor-pointer"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting || !form.title || !form.slug"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-glam-600 hover:bg-glam-700 shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <span v-if="submitting">Saving...</span>
                            <span v-else>{{ editingPage ? 'Update Document' : 'Publish Editorial Page' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
