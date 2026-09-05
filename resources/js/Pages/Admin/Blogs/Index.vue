<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';
import { renderMarkdown } from '@/Utils/markdown';

const props = defineProps({
    blogs: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_blogs: 0,
            published_blogs: 0,
            draft_blogs: 0,
            featured_blogs: 0,
            total_views: 0,
        }),
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

// View mode: 'grid' or 'table'
const viewMode = ref('grid');

// Search & Filters
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const statusFilter = ref(props.filters.status || '');
const sortFilter = ref(props.filters.sort || 'newest');

const defaultCategories = [
    'Skincare & Glow',
    'Hair Styling & Trends',
    'Bridal & Glamour',
    'Makeup Tutorials',
    'Nail Art & Care',
    'Wellness & Spa',
    'Product Reviews',
    'Celebrity Looks',
    'Industry News',
];

const availableCategories = computed(() => {
    const combined = new Set([...defaultCategories, ...props.categories]);
    return Array.from(combined).filter(Boolean).sort();
});

const applyFilters = () => {
    router.get(
        route('admin.blogs.index'),
        {
            search: search.value || undefined,
            category: categoryFilter.value || undefined,
            status: statusFilter.value || undefined,
            sort: sortFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    search.value = '';
    categoryFilter.value = '';
    statusFilter.value = '';
    sortFilter.value = 'newest';
    applyFilters();
};

// Modal State
const showModal = ref(false);
const showPreviewModal = ref(false);
const previewBlog = ref(null);
const editingBlog = ref(null);
const submitting = ref(false);
const activeModalTab = ref('content'); // 'content' | 'images' | 'seo' | 'preview'
const slugLocked = ref(true);

// Image Areas definition
const areaOptions = [
    { value: 'top', label: 'Top Section / Intro Hero', icon: 'M5 10l7-7m0 0l7 7m-7-7v18' },
    { value: 'middle', label: 'Mid-Article Highlight', icon: 'M4 6h16M4 12h16m-7 6h7' },
    { value: 'before_after', label: 'Before & After Showcase', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { value: 'product_spotlight', label: 'Product / Kit Essentials', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
    { value: 'gallery', label: 'Photo Gallery Grid', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { value: 'bottom', label: 'Bottom / Conclusion Feature', icon: 'M19 14l-7 7m0 0l-7-7m7 7V3' },
];

// Form state
const form = ref({
    title: '',
    slug: '',
    category: '',
    author_name: '',
    read_time: 3,
    excerpt: '',
    content: '',
    image: null,
    imagePreview: '',
    remove_image: false,
    existing_gallery: [],
    new_gallery_files: [],
    tags: '',
    is_published: true,
    is_featured: false,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
});

// Auto slug generator
const generateSlug = () => {
    if (slugLocked.value && form.value.title) {
        form.value.slug = form.value.title
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

// Word counter and read time estimation
watch(() => form.value.content, (newVal) => {
    if (newVal) {
        const text = newVal.replace(/<[^>]*>?/gm, '');
        const words = text.trim().split(/\s+/).filter(Boolean).length;
        form.value.read_time = Math.max(1, Math.ceil(words / 200));
    }
});

// Cover image selection
const onCoverImageChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        form.value.image = file;
        form.value.remove_image = false;
        form.value.imagePreview = URL.createObjectURL(file);
    }
};

const removeCoverImage = () => {
    form.value.image = null;
    form.value.imagePreview = '';
    form.value.remove_image = true;
};

// Multi-Image Area Manager
const onAddGalleryImages = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach((file) => {
        form.value.new_gallery_files.push({
            file: file,
            preview: URL.createObjectURL(file),
            area: 'gallery',
            caption: '',
        });
    });
    e.target.value = '';
};

const removeExistingGalleryImage = (index) => {
    form.value.existing_gallery.splice(index, 1);
};

const removeNewGalleryImage = (index) => {
    form.value.new_gallery_files.splice(index, 1);
};

// Format Toolbar helper
const insertFormatting = (syntaxStart, syntaxEnd = '') => {
    const textarea = document.getElementById('blog-content-editor');
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const currentText = form.value.content || '';
    const selectedText = currentText.substring(start, end) || 'Sample text';
    const replacement = `${syntaxStart}${selectedText}${syntaxEnd}`;

    form.value.content = currentText.substring(0, start) + replacement + currentText.substring(end);

    setTimeout(() => {
        textarea.focus();
        textarea.setSelectionRange(start + syntaxStart.length, start + syntaxStart.length + selectedText.length);
    }, 50);
};

const insertImageTag = (url, caption = '') => {
    const textarea = document.getElementById('blog-content-editor');
    if (!textarea) return;
    const tag = `\n\n![${caption || 'Beauty Image'}](${url})\n\n`;
    const start = textarea.selectionStart;
    const currentText = form.value.content || '';
    form.value.content = currentText.substring(0, start) + tag + currentText.substring(start);
};

// Open Create Modal
const openCreateModal = () => {
    editingBlog.value = null;
    slugLocked.value = true;
    activeModalTab.value = 'content';
    form.value = {
        title: '',
        slug: '',
        category: availableCategories.value[0] || 'Skincare & Glow',
        author_name: 'BeautyBook Team',
        read_time: 3,
        excerpt: '',
        content: `## The Secret to Radiant, Flawless Beauty\n\nEvery beauty transformation starts with understanding the right technique and using high-performance products suited for your skin and hair type.\n\n### 1. The Prep Routine\nBegin with gentle exfoliation and deep hydration. A well-hydrated base ensures seamless product blending and long-lasting glow.\n\n### 2. Pro Technique & Styling\nFocus on sculpting and accentuating natural features rather than heavy masking. Blend upwards and outwards for an effortless lift.\n\n> "True beauty is when your confidence shines through subtle elegance."\n\n### 3. Long-Lasting Aftercare\nFinish with an antioxidant mist to lock in hydration and maintain your radiant finish all day!`,
        image: null,
        imagePreview: '',
        remove_image: false,
        existing_gallery: [],
        new_gallery_files: [],
        tags: 'Beauty, Skincare, Glow, Tips',
        is_published: true,
        is_featured: false,
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
    };
    showModal.value = true;
};

// Open Edit Modal
const openEditModal = (blog) => {
    editingBlog.value = blog;
    slugLocked.value = false;
    activeModalTab.value = 'content';

    const rawTags = Array.isArray(blog.tags) ? blog.tags.join(', ') : (blog.tags || '');

    form.value = {
        title: blog.title || '',
        slug: blog.slug || '',
        category: blog.category || 'Skincare & Glow',
        author_name: blog.author_name || 'BeautyBook Team',
        read_time: blog.read_time || 3,
        excerpt: blog.excerpt || '',
        content: blog.content || '',
        image: null,
        imagePreview: blog.image_url || '',
        remove_image: false,
        existing_gallery: Array.isArray(blog.gallery_images_data) ? [...blog.gallery_images_data] : [],
        new_gallery_files: [],
        tags: rawTags,
        is_published: Boolean(blog.is_published),
        is_featured: Boolean(blog.is_featured),
        meta_title: blog.meta_title || '',
        meta_description: blog.meta_description || '',
        meta_keywords: blog.meta_keywords || '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingBlog.value = null;
};

// Open Quick Preview Modal
const openPreview = (blog) => {
    previewBlog.value = blog;
    showPreviewModal.value = true;
};

const closePreviewModal = () => {
    showPreviewModal.value = false;
    previewBlog.value = null;
};

// Submit form
const submitForm = () => {
    if (!form.value.title.trim()) {
        Swal.fire('Error', 'Please enter a title for the blog article.', 'error');
        return;
    }

    if (!form.value.content.trim()) {
        Swal.fire('Error', 'Please write some content for the article.', 'error');
        return;
    }

    submitting.value = true;

    const formData = new FormData();
    formData.append('title', form.value.title);
    formData.append('slug', form.value.slug || '');
    formData.append('category', form.value.category || '');
    formData.append('author_name', form.value.author_name || '');
    formData.append('read_time', form.value.read_time || 3);
    formData.append('excerpt', form.value.excerpt || '');
    formData.append('content', form.value.content || '');
    formData.append('tags', form.value.tags || '');
    formData.append('is_published', form.value.is_published ? '1' : '0');
    formData.append('is_featured', form.value.is_featured ? '1' : '0');
    formData.append('meta_title', form.value.meta_title || '');
    formData.append('meta_description', form.value.meta_description || '');
    formData.append('meta_keywords', form.value.meta_keywords || '');

    // Cover image
    if (form.value.image) {
        formData.append('image', form.value.image);
    }
    if (form.value.remove_image) {
        formData.append('remove_image', '1');
    }

    // Existing gallery images retained
    form.value.existing_gallery.forEach((item, index) => {
        formData.append(`existing_gallery[${index}][path]`, item.path || item.url || '');
        formData.append(`existing_gallery[${index}][url]`, item.url || '');
        formData.append(`existing_gallery[${index}][area]`, item.area || 'gallery');
        formData.append(`existing_gallery[${index}][caption]`, item.caption || '');
    });

    // New gallery images uploaded
    form.value.new_gallery_files.forEach((item, index) => {
        formData.append(`gallery_files[${index}]`, item.file);
        formData.append(`gallery_areas[${index}]`, item.area || 'gallery');
        formData.append(`gallery_captions[${index}]`, item.caption || '');
    });

    if (editingBlog.value) {
        formData.append('_method', 'PUT');
        router.post(route('admin.blogs.update', editingBlog.value.id), formData, {
            onSuccess: () => {
                submitting.value = false;
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Article Updated!',
                    text: 'Your blog post was updated successfully.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
            onError: (errors) => {
                submitting.value = false;
                const errorMsg = Object.values(errors).flat().join('\n');
                Swal.fire('Validation Error', errorMsg || 'Please check the form fields.', 'error');
            },
        });
    } else {
        router.post(route('admin.blogs.store'), formData, {
            onSuccess: () => {
                submitting.value = false;
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Article Published!',
                    text: 'Your new blog article is now saved.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
            onError: (errors) => {
                submitting.value = false;
                const errorMsg = Object.values(errors).flat().join('\n');
                Swal.fire('Validation Error', errorMsg || 'Please check the form fields.', 'error');
            },
        });
    }
};

// Toggle status
const toggleStatus = (blog) => {
    router.post(
        route('admin.blogs.toggle-status', blog.id),
        {},
        {
            preserveScroll: true,
        }
    );
};

// Toggle featured
const toggleFeatured = (blog) => {
    router.post(
        route('admin.blogs.toggle-featured', blog.id),
        {},
        {
            preserveScroll: true,
        }
    );
};

// Delete blog
const deleteBlog = (blog) => {
    Swal.fire({
        title: 'Delete this article?',
        text: `Are you sure you want to permanently delete "${blog.title}"? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.blogs.destroy', blog.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Blog post deleted successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const getGalleryByArea = (galleryList, areaName) => {
    if (!Array.isArray(galleryList)) return [];
    return galleryList.filter(item => item.area === areaName);
};
</script>

<template>
    <AdminLayout>
        <Head title="Blogs & Articles Management - Admin Console" />

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- 1. Luxury Pink Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gradient-to-r from-rose-50/90 via-pink-50 to-rose-100/70 p-6 rounded-3xl border border-rose-200/80 shadow-sm backdrop-blur-sm">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-rose-600 via-pink-600 to-rose-500 flex items-center justify-center text-white shadow-md shadow-rose-900/15">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            Editorial & Blogs
                        </h1>
                        <span class="px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200">
                            {{ stats.total_blogs }} Articles
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl leading-relaxed">
                        Curate beauty stories, guides, hair styling trends, transformations, and multi-area photo showcases for your audience.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <!-- Grid / Table View Switcher -->
                    <div class="flex items-center bg-white p-1 rounded-2xl border border-rose-200/80 shadow-xs">
                        <button
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="p-2 rounded-xl transition-all cursor-pointer"
                            title="Grid Cards View"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button
                            @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="p-2 rounded-xl transition-all cursor-pointer"
                            title="Table View"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Create Article Button -->
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 text-white text-xs sm:text-sm font-bold shadow-lg shadow-rose-950/20 transition-all hover:scale-[1.02] cursor-pointer"
                    >
                        <span class="text-base font-bold">+</span>
                        <span>Write Article</span>
                    </button>
                </div>
            </div>

            <!-- 2. KPI Stats Ribbon (5 Soft Pink Luxury Cards) -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Total Blogs -->
                <div class="bg-white p-5 rounded-3xl border border-rose-100 shadow-xs flex flex-col justify-between group hover:border-rose-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Articles</span>
                        <div class="h-8 w-8 rounded-xl bg-pink-100 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">{{ stats.total_blogs }}</span>
                        <span class="text-xs text-slate-500 font-medium">Stories</span>
                    </div>
                </div>

                <!-- Published -->
                <div class="bg-white p-5 rounded-3xl border border-rose-100 shadow-xs flex flex-col justify-between group hover:border-rose-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Published</span>
                        <div class="h-8 w-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-serif font-bold text-emerald-600">{{ stats.published_blogs }}</span>
                        <span class="text-xs text-emerald-700/80 font-medium">Live Online</span>
                    </div>
                </div>

                <!-- Drafts -->
                <div class="bg-white p-5 rounded-3xl border border-rose-100 shadow-xs flex flex-col justify-between group hover:border-rose-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Drafts</span>
                        <div class="h-8 w-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-serif font-bold text-amber-600">{{ stats.draft_blogs }}</span>
                        <span class="text-xs text-amber-700/80 font-medium">In Progress</span>
                    </div>
                </div>

                <!-- Featured Spotlights -->
                <div class="bg-white p-5 rounded-3xl border border-rose-100 shadow-xs flex flex-col justify-between group hover:border-rose-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Spotlights</span>
                        <div class="h-8 w-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4 fill-current text-rose-600" viewBox="0 0 24 24">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-serif font-bold text-rose-600">{{ stats.featured_blogs }}</span>
                        <span class="text-xs text-rose-700/80 font-medium">Featured</span>
                    </div>
                </div>

                <!-- Total Views -->
                <div class="col-span-2 sm:col-span-1 bg-white p-5 rounded-3xl border border-rose-100 shadow-xs flex flex-col justify-between group hover:border-rose-300 transition-all">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Readers</span>
                        <div class="h-8 w-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-serif font-bold text-purple-600">{{ Number(stats.total_views).toLocaleString() }}</span>
                        <span class="text-xs text-purple-700/80 font-medium">Views</span>
                    </div>
                </div>
            </div>

            <!-- 3. Filter & Search Toolbar -->
            <div class="bg-white p-4 sm:p-5 rounded-3xl border border-rose-100 shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <div class="flex flex-1 flex-col sm:flex-row gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-rose-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by title, excerpt, author, content..."
                            class="w-full pl-10 pr-4 py-2.5 bg-rose-50/40 text-slate-800 placeholder-slate-400 rounded-2xl border border-rose-200/80 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200 text-xs sm:text-sm transition-all"
                        />
                    </div>

                    <!-- Category Filter -->
                    <select
                        v-model="categoryFilter"
                        @change="applyFilters"
                        class="bg-rose-50/40 text-slate-800 rounded-2xl border border-rose-200/80 text-xs sm:text-sm py-2.5 px-3.5 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="bg-rose-50/40 text-slate-800 rounded-2xl border border-rose-200/80 text-xs sm:text-sm py-2.5 px-3.5 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200"
                    >
                        <option value="">All Statuses</option>
                        <option value="published">Published Only</option>
                        <option value="draft">Drafts Only</option>
                        <option value="featured">Featured Spotlight Only</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <!-- Sort Filter -->
                    <select
                        v-model="sortFilter"
                        @change="applyFilters"
                        class="bg-rose-50/40 text-slate-800 rounded-2xl border border-rose-200/80 text-xs sm:text-sm py-2.5 px-3.5 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200"
                    >
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="views">Most Read</option>
                        <option value="title">Alphabetical (A-Z)</option>
                    </select>

                    <!-- Reset Filters -->
                    <button
                        @click="resetFilters"
                        class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs sm:text-sm font-bold rounded-2xl border border-rose-200 transition-colors cursor-pointer"
                        title="Reset all filters"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- 4. Blog Articles List -->
            <div v-if="blogs.data && blogs.data.length > 0">
                <!-- GRID VIEW MODE -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="blog in blogs.data"
                        :key="blog.id"
                        class="bg-white rounded-3xl border border-rose-100 overflow-hidden shadow-xs hover:border-rose-300 hover:shadow-xl transition-all duration-300 flex flex-col justify-between group"
                    >
                        <!-- Cover Image & Badges -->
                        <div class="relative h-52 w-full bg-rose-50 overflow-hidden shrink-0">
                            <img
                                v-if="blog.image_url"
                                :src="storageUrl(blog.image_url)"
                                :alt="blog.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-rose-50 to-pink-50 text-rose-300">
                                <svg class="w-12 h-12 stroke-[1.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-[11px] font-semibold mt-1">No Cover Photo</span>
                            </div>

                            <!-- Top Badges Overlay -->
                            <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 pointer-events-none">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/95 backdrop-blur-md text-rose-700 border border-rose-200 shadow-sm">
                                    {{ blog.category || 'Beauty' }}
                                </span>

                                <div class="flex items-center gap-1.5">
                                    <!-- Featured Star Badge -->
                                    <button
                                        @click.stop="toggleFeatured(blog)"
                                        class="p-1.5 rounded-full bg-white/95 backdrop-blur-md border border-rose-200 shadow-sm pointer-events-auto transition-transform hover:scale-110 cursor-pointer"
                                        :title="blog.is_featured ? 'Featured spotlight (Click to unfeature)' : 'Mark as Featured'"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5"
                                            :class="blog.is_featured ? 'fill-amber-400 text-amber-500' : 'fill-none text-slate-400 hover:text-amber-400'"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </button>

                                    <!-- Status Pill -->
                                    <button
                                        @click.stop="toggleStatus(blog)"
                                        class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider backdrop-blur-md pointer-events-auto border shadow-sm transition-colors cursor-pointer"
                                        :class="blog.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                    >
                                        {{ blog.is_published ? 'Published' : 'Draft' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Multi-image gallery badge -->
                            <div v-if="blog.gallery_images_data && blog.gallery_images_data.length > 0" class="absolute bottom-3 left-3 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md text-slate-700 text-[10px] font-bold border border-rose-200 shadow-sm">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>+{{ blog.gallery_images_data.length }} Photos</span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400 font-medium mb-1.5">
                                    <span class="text-rose-600 font-semibold">By {{ blog.author_name || 'Beauty Editorial' }}</span>
                                    <span>•</span>
                                    <span>{{ blog.read_time }} min read</span>
                                    <span>•</span>
                                    <span>{{ blog.formatted_date }}</span>
                                </div>

                                <h2 class="text-lg font-serif font-bold text-slate-900 group-hover:text-rose-600 transition-colors line-clamp-2">
                                    {{ blog.title }}
                                </h2>

                                <p class="text-xs text-slate-600 mt-2 line-clamp-2 leading-relaxed">
                                    {{ blog.excerpt || blog.content.replace(/<[^>]*>?/gm, '').substring(0, 120) + '...' }}
                                </p>
                            </div>

                            <!-- Footer info & Actions -->
                            <div class="mt-5 pt-4 border-t border-rose-50 flex items-center justify-between">
                                <div class="flex items-center gap-1.5 text-xs text-slate-500" title="Views">
                                    <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span class="font-bold text-slate-700">{{ Number(blog.views_count || 0).toLocaleString() }}</span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <!-- Preview Button -->
                                    <button
                                        @click="openPreview(blog)"
                                        class="p-2 text-slate-500 hover:text-slate-900 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                        title="Preview Article"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Edit Button -->
                                    <button
                                        @click="openEditModal(blog)"
                                        class="p-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                        title="Edit Article"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <button
                                        @click="deleteBlog(blog)"
                                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                        title="Delete Article"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE VIEW MODE -->
                <div v-else class="bg-white rounded-3xl border border-rose-100 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-rose-100 text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-rose-50/60">
                                    <th class="py-4 px-5">Article</th>
                                    <th class="py-4 px-4">Category</th>
                                    <th class="py-4 px-4">Author</th>
                                    <th class="py-4 px-4">Status</th>
                                    <th class="py-4 px-4">Featured</th>
                                    <th class="py-4 px-4">Views</th>
                                    <th class="py-4 px-4">Date</th>
                                    <th class="py-4 px-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50 text-xs">
                                <tr
                                    v-for="blog in blogs.data"
                                    :key="blog.id"
                                    class="hover:bg-rose-50/40 transition-colors group"
                                >
                                    <!-- Article Details -->
                                    <td class="py-4 px-5">
                                        <div class="flex items-center gap-3.5">
                                            <div class="h-12 w-16 rounded-xl bg-rose-50 overflow-hidden shrink-0 border border-rose-100">
                                                <img
                                                    v-if="blog.image_url"
                                                    :src="storageUrl(blog.image_url)"
                                                    class="w-full h-full object-cover"
                                                />
                                                <div v-else class="w-full h-full flex items-center justify-center text-rose-300">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 group-hover:text-rose-600 transition-colors truncate max-w-xs text-sm">
                                                    {{ blog.title }}
                                                </div>
                                                <div class="text-[11px] text-slate-400 truncate max-w-xs font-mono">
                                                    /blogs/{{ blog.slug }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Category -->
                                    <td class="py-4 px-4">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            {{ blog.category || 'General' }}
                                        </span>
                                    </td>

                                    <!-- Author -->
                                    <td class="py-4 px-4 text-slate-700 font-semibold">
                                        {{ blog.author_name || 'Beauty Editorial' }}
                                    </td>

                                    <!-- Status Switch -->
                                    <td class="py-4 px-4">
                                        <button
                                            @click="toggleStatus(blog)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border cursor-pointer transition-colors"
                                            :class="blog.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="blog.is_published ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                            {{ blog.is_published ? 'Published' : 'Draft' }}
                                        </button>
                                    </td>

                                    <!-- Featured -->
                                    <td class="py-4 px-4">
                                        <button
                                            @click="toggleFeatured(blog)"
                                            class="p-1 rounded-lg transition-colors cursor-pointer"
                                            :class="blog.is_featured ? 'text-amber-500' : 'text-slate-300 hover:text-amber-400'"
                                        >
                                            <svg class="w-4 h-4" :class="{ 'fill-amber-400': blog.is_featured }" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        </button>
                                    </td>

                                    <!-- Views -->
                                    <td class="py-4 px-4 text-slate-800 font-bold">
                                        {{ Number(blog.views_count || 0).toLocaleString() }}
                                    </td>

                                    <!-- Date -->
                                    <td class="py-4 px-4 text-slate-500 text-[11px]">
                                        {{ blog.formatted_date }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-4 px-5 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                @click="openPreview(blog)"
                                                class="p-2 text-slate-500 hover:text-slate-900 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                                title="Preview Article"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="openEditModal(blog)"
                                                class="p-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                                title="Edit Article"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteBlog(blog)"
                                                class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
                                                title="Delete Article"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <AppPagination :links="blogs.links" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-3xl border border-rose-100 p-12 text-center shadow-xs">
                <div class="max-w-md mx-auto flex flex-col items-center">
                    <div class="h-16 w-16 rounded-3xl bg-rose-100 text-rose-600 flex items-center justify-center mb-4 border border-rose-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-serif font-bold text-slate-900">No Blog Articles Found</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        {{ search || categoryFilter || statusFilter ? 'No articles match your search criteria. Try resetting filters.' : 'Your beauty magazine is waiting for its first editorial story!' }}
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 text-white text-xs sm:text-sm font-bold shadow-lg shadow-rose-950/20 hover:scale-105 transition-all cursor-pointer"
                    >
                        <span class="text-base">+</span>
                        <span>Create Your First Blog Article</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- CREATE / EDIT BLOG MODAL (Luxury Pink Palette) -->
        <!-- ========================================================================= -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto bg-slate-900/60 backdrop-blur-sm"
        >
            <div class="bg-white w-full max-w-4xl rounded-3xl border border-rose-200 shadow-2xl overflow-hidden flex flex-col max-h-[92vh] my-auto animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-4.5 border-b border-rose-100 flex items-center justify-between bg-gradient-to-r from-rose-50/90 via-pink-50/80 to-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white shadow-md flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-serif font-bold text-slate-900">
                                {{ editingBlog ? 'Edit Beauty Article' : 'Draft New Beauty Article' }}
                            </h2>
                            <p class="text-xs text-slate-500">
                                Curate story content, multi-area photography, search engine tags, and live reader preview.
                            </p>
                        </div>
                    </div>

                    <button
                        @click="closeModal"
                        class="p-2 text-slate-400 hover:text-slate-800 hover:bg-rose-50 rounded-2xl transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Tabs -->
                <div class="px-6 border-b border-rose-100 bg-rose-50/40 flex items-center gap-2 overflow-x-auto shrink-0">
                    <button
                        @click="activeModalTab = 'content'"
                        class="py-3.5 px-4 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer shrink-0"
                        :class="activeModalTab === 'content' ? 'border-rose-600 text-rose-700 bg-white/80 rounded-t-2xl shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Article & Content</span>
                    </button>

                    <button
                        @click="activeModalTab = 'images'"
                        class="py-3.5 px-4 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer shrink-0"
                        :class="activeModalTab === 'images' ? 'border-rose-600 text-rose-700 bg-white/80 rounded-t-2xl shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Multi-Area Photos</span>
                        <span v-if="form.existing_gallery.length + form.new_gallery_files.length > 0" class="px-2 py-0.5 rounded-full text-[10px] bg-rose-600 text-white font-bold">
                            {{ form.existing_gallery.length + form.new_gallery_files.length }}
                        </span>
                    </button>

                    <button
                        @click="activeModalTab = 'seo'"
                        class="py-3.5 px-4 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer shrink-0"
                        :class="activeModalTab === 'seo' ? 'border-rose-600 text-rose-700 bg-white/80 rounded-t-2xl shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>SEO & Discovery</span>
                    </button>

                    <button
                        @click="activeModalTab = 'preview'"
                        class="py-3.5 px-4 text-xs font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer shrink-0"
                        :class="activeModalTab === 'preview' ? 'border-rose-600 text-rose-700 bg-white/80 rounded-t-2xl shadow-xs' : 'border-transparent text-slate-500 hover:text-slate-900'"
                    >
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Live Reader Preview</span>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto flex-1 space-y-6 bg-white">
                    <!-- TAB 1: ARTICLE & CONTENT -->
                    <div v-show="activeModalTab === 'content'" class="space-y-5">
                        <!-- Title & Slug -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Article Title <span class="text-rose-600">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    @input="generateSlug"
                                    type="text"
                                    placeholder="e.g. 10 Secret Hydration Habits for Glass Skin"
                                    class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200 text-sm font-medium transition-all"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        URL Slug
                                    </label>
                                    <button
                                        type="button"
                                        @click="slugLocked = !slugLocked"
                                        class="text-[11px] text-rose-600 hover:text-rose-700 font-bold cursor-pointer"
                                    >
                                        {{ slugLocked ? 'Unlock Custom URL' : 'Lock Auto-Slug' }}
                                    </button>
                                </div>
                                <div class="flex items-center bg-rose-50/40 rounded-2xl border border-rose-200 overflow-hidden focus-within:border-rose-500 focus-within:bg-white">
                                    <span class="pl-3.5 text-xs text-rose-500 font-mono select-none">/blogs/</span>
                                    <input
                                        v-model="form.slug"
                                        :readonly="slugLocked"
                                        type="text"
                                        placeholder="article-slug"
                                        class="w-full px-2 py-2.5 bg-transparent text-slate-800 text-sm font-mono border-none focus:outline-none focus:ring-0"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Category, Author & Read Time -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Category
                                </label>
                                <input
                                    v-model="form.category"
                                    list="category-suggestions"
                                    type="text"
                                    placeholder="e.g. Skincare & Glow"
                                    class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200 text-sm"
                                />
                                <datalist id="category-suggestions">
                                    <option v-for="cat in availableCategories" :key="cat" :value="cat" />
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Author Name
                                </label>
                                <input
                                    v-model="form.author_name"
                                    type="text"
                                    placeholder="e.g. Dr. Sarah Jenkins"
                                    class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200 text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Est. Read Time (Minutes)
                                </label>
                                <input
                                    v-model.number="form.read_time"
                                    type="number"
                                    min="1"
                                    max="180"
                                    class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200 text-sm"
                                />
                            </div>
                        </div>

                        <!-- Excerpt / Summary -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Short Excerpt / Hook
                            </label>
                            <textarea
                                v-model="form.excerpt"
                                rows="2"
                                placeholder="A captivating 1-2 sentence hook for cards, social shares, and previews..."
                                class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 focus:bg-white focus:ring-2 focus:ring-rose-200 text-sm leading-relaxed"
                            ></textarea>
                        </div>

                        <!-- Primary Cover Image Banner -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Primary Cover Banner (Hero)
                            </label>
                            <div class="flex flex-col sm:flex-row gap-4 items-start">
                                <div
                                    v-if="form.imagePreview"
                                    class="relative h-32 w-52 rounded-2xl bg-rose-50 overflow-hidden border border-rose-200 shrink-0 group shadow-xs"
                                >
                                    <img :src="storageUrl(form.imagePreview)" class="w-full h-full object-cover" />
                                    <button
                                        type="button"
                                        @click="removeCoverImage"
                                        class="absolute inset-0 bg-slate-950/70 text-rose-400 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-1 text-xs font-bold transition-opacity cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Remove</span>
                                    </button>
                                </div>

                                <div class="flex-1 w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-32 px-4 border-2 border-dashed border-rose-200 hover:border-rose-400 rounded-2xl bg-rose-50/40 hover:bg-rose-50/80 transition-all cursor-pointer">
                                        <svg class="w-8 h-8 text-rose-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-xs font-bold text-slate-700">Click to upload cover photo</span>
                                        <span class="text-[10px] text-slate-500 mt-0.5">JPG, PNG, WEBP up to 8MB</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            @change="onCoverImageChange"
                                            class="hidden"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Rich Content Editor with Formatting Toolbar -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Article Content <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[11px] text-slate-500">
                                    Markdown & HTML Supported
                                </span>
                            </div>

                            <!-- Editor Toolbar -->
                            <div class="flex flex-wrap items-center gap-1.5 p-2.5 bg-rose-50/80 rounded-t-2xl border border-rose-200 border-b-0 text-slate-700 text-xs">
                                <button
                                    type="button"
                                    @click="insertFormatting('## ')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-bold border border-rose-200 transition-colors cursor-pointer"
                                    title="Heading 2"
                                >
                                    H2
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('### ')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-bold border border-rose-200 transition-colors cursor-pointer"
                                    title="Heading 3"
                                >
                                    H3
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('**', '**')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-bold border border-rose-200 transition-colors cursor-pointer"
                                    title="Bold"
                                >
                                    B
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('*', '*')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg italic font-serif border border-rose-200 transition-colors cursor-pointer"
                                    title="Italic"
                                >
                                    I
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('> ')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-semibold border border-rose-200 transition-colors cursor-pointer"
                                    title="Quote"
                                >
                                    Quote
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('- ')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-semibold border border-rose-200 transition-colors cursor-pointer"
                                    title="Bullet List"
                                >
                                    • List
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('1. ')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-semibold border border-rose-200 transition-colors cursor-pointer"
                                    title="Numbered List"
                                >
                                    1. List
                                </button>
                                <button
                                    type="button"
                                    @click="insertFormatting('\n---\n')"
                                    class="px-2.5 py-1 bg-white hover:bg-rose-100 rounded-lg font-semibold border border-rose-200 transition-colors cursor-pointer"
                                    title="Divider Line"
                                >
                                    Divider
                                </button>

                                <div class="ml-auto text-[11px] text-slate-500 font-semibold">
                                    {{ form.content ? form.content.split(/\s+/).filter(Boolean).length : 0 }} Words
                                </div>
                            </div>

                            <textarea
                                id="blog-content-editor"
                                v-model="form.content"
                                rows="12"
                                placeholder="Write your beauty story here..."
                                class="w-full px-4 py-3 bg-white text-slate-900 rounded-b-2xl border border-rose-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 font-mono text-xs sm:text-sm leading-relaxed"
                            ></textarea>
                        </div>

                        <!-- Publishing and Featured Switches -->
                        <div class="p-5 bg-gradient-to-r from-rose-50/60 to-pink-50/60 rounded-2xl border border-rose-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">Publish Article</span>
                                    <span class="text-[11px] text-slate-500 block">Make this article live and readable online</span>
                                </div>
                                <input
                                    v-model="form.is_published"
                                    type="checkbox"
                                    class="h-5 w-5 rounded-lg bg-white border-rose-300 text-rose-600 focus:ring-rose-500"
                                />
                            </label>

                            <label class="flex items-center justify-between cursor-pointer">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">Featured Spotlight</span>
                                    <span class="text-[11px] text-slate-500 block">Promote prominently in featured carousels</span>
                                </div>
                                <input
                                    v-model="form.is_featured"
                                    type="checkbox"
                                    class="h-5 w-5 rounded-lg bg-white border-rose-300 text-amber-500 focus:ring-amber-400"
                                />
                            </label>
                        </div>
                    </div>

                    <!-- TAB 2: MULTI-AREA IMAGES MANAGER -->
                    <div v-show="activeModalTab === 'images'" class="space-y-6">
                        <div class="bg-gradient-to-r from-rose-50 to-pink-50 p-5 rounded-2xl border border-rose-100">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-sm font-serif font-bold text-slate-900">Multi-Area Photos & Galleries</h3>
                                    <p class="text-xs text-slate-600 mt-0.5">
                                        Attach images to designated areas (Top, Mid-Article, Before & After, Product Essentials, Gallery Grid, or Conclusion).
                                    </p>
                                </div>

                                <label class="px-4 py-2 rounded-2xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white text-xs font-bold shadow-md flex items-center gap-1.5 transition-all cursor-pointer shrink-0">
                                    <span class="text-base font-bold">+</span>
                                    <span>Add Photos</span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        @change="onAddGalleryImages"
                                        class="hidden"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Display Area Legend -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                            <div v-for="area in areaOptions" :key="area.value" class="p-2.5 rounded-xl bg-rose-50/50 border border-rose-100 flex items-center gap-2 text-slate-700">
                                <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="area.icon" />
                                </svg>
                                <span class="truncate font-semibold">{{ area.label }}</span>
                            </div>
                        </div>

                        <!-- Combined Photos List -->
                        <div v-if="form.existing_gallery.length + form.new_gallery_files.length > 0" class="space-y-3">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                Assigned Images ({{ form.existing_gallery.length + form.new_gallery_files.length }})
                            </div>

                            <!-- Existing Retained Images -->
                            <div
                                v-for="(item, index) in form.existing_gallery"
                                :key="'existing-' + index"
                                class="p-4 rounded-2xl bg-white border border-rose-100 shadow-xs flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="h-16 w-20 rounded-xl bg-rose-50 overflow-hidden shrink-0 border border-rose-200">
                                        <img :src="storageUrl(item.url)" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input
                                            v-model="item.caption"
                                            type="text"
                                            placeholder="Add image caption / alt text..."
                                            class="w-full px-3 py-1.5 bg-rose-50/40 text-xs text-slate-900 rounded-xl border border-rose-200 focus:border-rose-500 mb-1.5"
                                        />
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] text-slate-500 font-bold">Target Area:</span>
                                            <select
                                                v-model="item.area"
                                                class="bg-rose-50/40 text-slate-800 text-xs py-1 px-2.5 rounded-xl border border-rose-200 focus:border-rose-500"
                                            >
                                                <option v-for="opt in areaOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                    <button
                                        type="button"
                                        @click="insertImageTag(item.url, item.caption)"
                                        class="px-3 py-1.5 rounded-xl bg-rose-100 hover:bg-rose-200 text-rose-700 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer"
                                        title="Insert image markdown code into content"
                                    >
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span>Insert in Content</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="removeExistingGalleryImage(index)"
                                        class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                        title="Remove photo"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Newly Added Images -->
                            <div
                                v-for="(item, index) in form.new_gallery_files"
                                :key="'new-' + index"
                                class="p-4 rounded-2xl bg-white border-2 border-rose-300 shadow-xs flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="h-16 w-20 rounded-xl bg-rose-50 overflow-hidden shrink-0 border border-rose-200 relative">
                                        <img :src="item.preview" class="w-full h-full object-cover" />
                                        <span class="absolute top-1 left-1 px-1.5 py-0.2 rounded-md bg-rose-600 text-white text-[9px] font-bold">NEW</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input
                                            v-model="item.caption"
                                            type="text"
                                            placeholder="Add image caption / alt text..."
                                            class="w-full px-3 py-1.5 bg-rose-50/40 text-xs text-slate-900 rounded-xl border border-rose-200 focus:border-rose-500 mb-1.5"
                                        />
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] text-slate-500 font-bold">Target Area:</span>
                                            <select
                                                v-model="item.area"
                                                class="bg-rose-50/40 text-slate-800 text-xs py-1 px-2.5 rounded-xl border border-rose-200 focus:border-rose-500"
                                            >
                                                <option v-for="opt in areaOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                    <button
                                        type="button"
                                        @click="removeNewGalleryImage(index)"
                                        class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                        title="Remove photo"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-8 text-center bg-rose-50/40 rounded-2xl border border-rose-100">
                            <svg class="w-10 h-10 mx-auto text-rose-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs text-slate-600 block font-bold">No multi-area photos uploaded yet</span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Click "+ Add Photos" above to attach step-by-step guides, before/after photos, or photo galleries.</span>
                        </div>
                    </div>

                    <!-- TAB 3: SEO & DISCOVERY -->
                    <div v-show="activeModalTab === 'seo'" class="space-y-5">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    SEO Meta Title
                                </label>
                                <span class="text-[11px]" :class="(form.meta_title || form.title).length > 60 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                    {{ (form.meta_title || form.title).length }}/60 chars
                                </span>
                            </div>
                            <input
                                v-model="form.meta_title"
                                type="text"
                                :placeholder="form.title || 'SEO Title displayed in search results...'"
                                class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 text-sm"
                            />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    SEO Meta Description
                                </label>
                                <span class="text-[11px]" :class="(form.meta_description || form.excerpt).length > 160 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                    {{ (form.meta_description || form.excerpt).length }}/160 chars
                                </span>
                            </div>
                            <textarea
                                v-model="form.meta_description"
                                rows="3"
                                :placeholder="form.excerpt || 'Brief description summary for search engines and social cards...'"
                                class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 text-sm leading-relaxed"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Tags & Keywords (Comma separated)
                            </label>
                            <input
                                v-model="form.tags"
                                type="text"
                                placeholder="Beauty, Skincare, Hydration, Makeup Tips, Bridal"
                                class="w-full px-4 py-2.5 bg-rose-50/40 text-slate-900 rounded-2xl border border-rose-200 focus:border-rose-500 text-sm"
                            />
                        </div>

                        <!-- Google SERP Snippet Preview -->
                        <div class="p-5 bg-gradient-to-r from-rose-50/80 to-pink-50/80 rounded-2xl border border-rose-100 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-rose-700 tracking-wider block mb-2">Google Search Snippet Preview</span>
                            <div class="text-xs text-emerald-600 font-sans truncate font-medium">
                                https://beautybook.com/blogs/{{ form.slug || 'article-slug' }}
                            </div>
                            <div class="text-sm font-bold text-blue-600 hover:underline cursor-pointer line-clamp-1">
                                {{ form.meta_title || form.title || 'Your Article Title - BeautyBook' }}
                            </div>
                            <div class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                {{ form.meta_description || form.excerpt || 'Read this comprehensive beauty guide on BeautyBook to discover expert tips, stylist trends, and transformative beauty routines.' }}
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: LIVE READER PREVIEW -->
                    <div v-show="activeModalTab === 'preview'" class="space-y-6">
                        <div class="bg-gradient-to-b from-white to-rose-50/40 p-6 sm:p-8 rounded-3xl border border-rose-200 shadow-sm max-w-2xl mx-auto space-y-6">
                            <!-- Category & Date -->
                            <div class="flex items-center gap-2">
                                <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">
                                    {{ form.category || 'Beauty Guide' }}
                                </span>
                                <span class="text-xs text-slate-500">• {{ form.read_time }} min read</span>
                            </div>

                            <!-- Title -->
                            <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900 leading-tight">
                                {{ form.title || 'Untitled Beauty Article' }}
                            </h1>

                            <!-- Author Card -->
                            <div class="flex items-center gap-3 pt-2 border-t border-rose-100">
                                <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-500 flex items-center justify-center text-white font-bold text-xs shadow-md">
                                    {{ (form.author_name || 'B').charAt(0) }}
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">{{ form.author_name || 'BeautyBook Editorial' }}</div>
                                    <div class="text-[10px] text-slate-500">Published in {{ form.category }}</div>
                                </div>
                            </div>

                            <!-- Hero Cover Image -->
                            <div v-if="form.imagePreview" class="rounded-2xl overflow-hidden border border-rose-200 shadow-md">
                                <img :src="storageUrl(form.imagePreview)" class="w-full max-h-72 object-cover" />
                            </div>

                            <!-- Top Section Gallery Photos -->
                            <div v-if="getGalleryByArea([...form.existing_gallery, ...form.new_gallery_files], 'top').length > 0" class="space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div v-for="(img, i) in getGalleryByArea([...form.existing_gallery, ...form.new_gallery_files], 'top')" :key="i" class="rounded-2xl overflow-hidden border border-rose-200 bg-white">
                                        <img :src="img.url || img.preview" class="w-full h-40 object-cover" />
                                        <p v-if="img.caption" class="p-2 text-[11px] text-slate-500 bg-white italic text-center">{{ img.caption }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Excerpt Quote Box -->
                            <div v-if="form.excerpt" class="p-5 rounded-2xl bg-gradient-to-r from-rose-50 to-pink-50 border-l-4 border-rose-500 text-xs sm:text-sm text-slate-700 italic leading-relaxed">
                                "{{ form.excerpt }}"
                            </div>

                            <!-- Content Body Preview -->
                            <div class="prose prose-rose max-w-none text-slate-700 text-xs sm:text-sm leading-relaxed" v-html="renderMarkdown(form.content)">
                            </div>

                            <!-- Before & After Transformation Showcase -->
                            <div v-if="getGalleryByArea([...form.existing_gallery, ...form.new_gallery_files], 'before_after').length > 0" class="p-5 rounded-2xl bg-white border border-rose-200 shadow-sm space-y-3">
                                <span class="text-xs font-bold text-rose-700 uppercase tracking-wider block">✦ Before & After Transformation</span>
                                <div class="grid grid-cols-2 gap-3">
                                    <div v-for="(img, i) in getGalleryByArea([...form.existing_gallery, ...form.new_gallery_files], 'before_after')" :key="i" class="rounded-xl overflow-hidden border border-rose-100 bg-rose-50">
                                        <img :src="img.url || img.preview" class="w-full h-36 object-cover" />
                                        <p class="p-1.5 text-[10px] text-slate-600 bg-white text-center font-semibold">{{ img.caption || (i === 0 ? 'Before' : 'After Result') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Photo Gallery Grid -->
                            <div v-if="getGalleryByArea([...form.existing_gallery, ...form.new_gallery_files], 'gallery').length > 0" class="space-y-2 pt-4 border-t border-rose-100">
                                <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block">Photo Gallery</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <div v-for="(img, i) in getGalleryByArea([...form.existing_gallery, ...form.new_gallery_files], 'gallery')" :key="i" class="rounded-xl overflow-hidden border border-rose-200">
                                        <img :src="img.url || img.preview" class="w-full h-28 object-cover hover:scale-105 transition-transform" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4.5 border-t border-rose-100 bg-gradient-to-r from-rose-50/50 via-white to-pink-50/50 flex items-center justify-between shrink-0">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-800 text-xs sm:text-sm font-bold rounded-2xl transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 text-white text-xs sm:text-sm font-bold rounded-2xl shadow-lg shadow-rose-950/20 flex items-center gap-2 transition-all hover:scale-105 cursor-pointer disabled:opacity-50"
                        >
                            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ editingBlog ? 'Update Article' : 'Publish Article' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- QUICK ARTICLE READER PREVIEW MODAL -->
        <!-- ========================================================================= -->
        <div
            v-if="showPreviewModal && previewBlog"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto bg-slate-900/60 backdrop-blur-sm"
        >
            <div class="bg-white w-full max-w-3xl rounded-3xl border border-rose-200 shadow-2xl overflow-hidden flex flex-col max-h-[90vh] my-auto animate-in fade-in zoom-in duration-200">
                <!-- Preview Header -->
                <div class="px-6 py-4.5 border-b border-rose-100 flex items-center justify-between bg-gradient-to-r from-rose-50 via-pink-50 to-white">
                    <span class="text-xs font-bold text-rose-700 uppercase tracking-widest">
                        Article Reader Preview
                    </span>
                    <button
                        @click="closePreviewModal"
                        class="p-2 text-slate-400 hover:text-slate-800 hover:bg-rose-50 rounded-2xl transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Preview Content -->
                <div class="p-6 sm:p-8 overflow-y-auto space-y-6 bg-white">
                    <div class="flex items-center gap-2">
                        <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">
                            {{ previewBlog.category || 'Beauty' }}
                        </span>
                        <span class="text-xs text-slate-500">• {{ previewBlog.read_time }} min read</span>
                        <span class="text-xs text-slate-500">• {{ previewBlog.formatted_date }}</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900 leading-tight">
                        {{ previewBlog.title }}
                    </h1>

                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-500 flex items-center justify-center text-white font-bold text-xs shadow-md">
                            {{ (previewBlog.author_name || 'B').charAt(0) }}
                        </div>
                        <span class="text-xs font-bold text-slate-800">{{ previewBlog.author_name || 'BeautyBook Editorial' }}</span>
                    </div>

                    <div v-if="previewBlog.image_url" class="rounded-2xl overflow-hidden border border-rose-200 shadow-md">
                        <img :src="storageUrl(previewBlog.image_url)" class="w-full max-h-80 object-cover" />
                    </div>

                    <p v-if="previewBlog.excerpt" class="p-5 rounded-2xl bg-gradient-to-r from-rose-50 to-pink-50 border-l-4 border-rose-500 text-sm text-slate-700 italic leading-relaxed">
                        {{ previewBlog.excerpt }}
                    </p>

                    <div class="prose prose-rose max-w-none text-slate-700 text-sm leading-relaxed" v-html="renderMarkdown(previewBlog.content)">
                    </div>

                    <!-- Gallery Photos if any -->
                    <div v-if="previewBlog.gallery_images_data && previewBlog.gallery_images_data.length > 0" class="pt-5 border-t border-rose-100 space-y-3">
                        <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block">Attached Photos ({{ previewBlog.gallery_images_data.length }})</span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div v-for="(img, idx) in previewBlog.gallery_images_data" :key="idx" class="rounded-2xl overflow-hidden border border-rose-200 bg-rose-50">
                                <img :src="storageUrl(img.url)" class="w-full h-32 object-cover" />
                                <div class="p-2 text-[10px] text-slate-600 flex items-center justify-between bg-white">
                                    <span class="capitalize text-rose-700 font-bold">{{ img.area || 'Gallery' }}</span>
                                    <span v-if="img.caption" class="truncate ml-1">{{ img.caption }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Footer -->
                <div class="px-6 py-4 border-t border-rose-100 bg-rose-50/50 flex items-center justify-end">
                    <button
                        @click="closePreviewModal"
                        class="px-5 py-2 bg-rose-100 hover:bg-rose-200 text-rose-800 text-xs font-bold rounded-2xl transition-colors cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
