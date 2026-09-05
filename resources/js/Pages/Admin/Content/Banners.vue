<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    banners: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_banners: 0,
            active_banners: 0,
            hero_banners: 0,
            promo_banners: 0,
            sponsor_revenue: 0,
        }),
    },
    artists: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    courses: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const viewMode = ref('grid'); // 'grid' | 'table'
const search = ref(props.filters.search || '');
const positionFilter = ref(props.filters.position || '');
const statusFilter = ref(props.filters.status || '');

// Modal state
const showModal = ref(false);
const editingBanner = ref(null);
const submitting = ref(false);
const imagePreview = ref(null);

// Form state
const form = ref({
    title: '',
    description: '',
    image: null,
    use_target_image: null,
    position: 'hero',
    link_type: 'service', // 'service' | 'artist' | 'product' | 'course' | 'custom'
    artist_profile_id: null,
    service_id: null,
    product_id: null,
    course_id: null,
    button_text: 'Book Appointment',
    link: '',
    start_date: '',
    end_date: '',
    is_active: true,
    sort_order: 0,
    price: null,
});

// Image Lightbox
const previewLightbox = ref(null);
const previewTitle = ref('');

const selectedArtist = computed(() => {
    if (!form.value.artist_profile_id) return null;
    return props.artists.find(a => Number(a.id) === Number(form.value.artist_profile_id)) || null;
});

const availableServicesForArtist = computed(() => {
    return selectedArtist.value?.services || [];
});

const onLinkTypeChange = (type) => {
    form.value.link_type = type;
    if (type === 'service') {
        if (form.value.service_id) onServiceChange();
    } else if (type === 'artist') {
        if (form.value.artist_profile_id) onArtistChange();
    } else if (type === 'product') {
        if (form.value.product_id) onProductChange();
    } else if (type === 'course') {
        if (form.value.course_id) onCourseChange();
    }
};

const onArtistChange = () => {
    if (form.value.link_type === 'artist' && selectedArtist.value) {
        const a = selectedArtist.value;
        form.value.title = a.business_name || a.user?.name || '';
        form.value.description = a.bio || `Explore luxury beauty treatments and appointments by ${a.business_name}.`;
        const aImg = a.cover_image || a.profile_image;
        if (aImg) {
            imagePreview.value = storageUrl(aImg);
            form.value.use_target_image = aImg;
        }
        form.value.button_text = 'View Artist';
    } else if (form.value.link_type === 'service') {
        form.value.service_id = null;
    }
};

const onServiceChange = () => {
    if (form.value.service_id) {
        const s = availableServicesForArtist.value.find(item => Number(item.id) === Number(form.value.service_id));
        if (s) {
            form.value.title = s.name;
            form.value.description = s.description || (selectedArtist.value ? `Specialist signature service by ${selectedArtist.value.business_name}` : 'Exclusive beauty & wellness treatment.');
            if (s.price) {
                form.value.price = Number(s.price);
            }
            if (s.image) {
                imagePreview.value = storageUrl(s.image);
                form.value.use_target_image = s.image;
            } else if (selectedArtist.value?.cover_image || selectedArtist.value?.profile_image) {
                const aImg = selectedArtist.value.cover_image || selectedArtist.value.profile_image;
                imagePreview.value = storageUrl(aImg);
                form.value.use_target_image = aImg;
            }
            form.value.button_text = 'Book Appointment';
        }
    }
};

const onProductChange = () => {
    if (form.value.product_id) {
        const p = props.products.find(item => Number(item.id) === Number(form.value.product_id));
        if (p) {
            form.value.title = p.name;
            form.value.description = p.description || (p.brand ? `Signature ${p.brand} beauty collection.` : 'Top-rated skincare & cosmetics boutique.');
            if (p.price) {
                form.value.price = Number(p.price);
            }
            if (p.image) {
                imagePreview.value = storageUrl(p.image);
                form.value.use_target_image = p.image;
            }
            form.value.button_text = 'Shop Now';
        }
    }
};

const onCourseChange = () => {
    if (form.value.course_id) {
        const c = props.courses.find(item => Number(item.id) === Number(form.value.course_id));
        if (c) {
            form.value.title = c.title;
            form.value.description = c.summary || (c.artist_profile ? `Professional hands-on salon academy masterclass by ${c.artist_profile.business_name}.` : 'Professional beautician training workshop.');
            if (c.discount_price || c.price) {
                form.value.price = Number(c.discount_price || c.price);
            }
            if (c.cover_image) {
                imagePreview.value = storageUrl(c.cover_image);
                form.value.use_target_image = c.cover_image;
            }
            form.value.button_text = 'Enroll in Course';
        }
    }
};

const formatPrice = (val) => {
    if (val === null || val === undefined || isNaN(val) || val === '') return 'PKR 0';
    return 'PKR ' + Number(val).toLocaleString('en-PK', { maximumFractionDigits: 0 });
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'No limit';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getBannerImg = (img) => {
    return storageUrl(img, 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&q=80&w=800');
};

const applyFilters = () => {
    router.get(
        route('admin.content.banners'),
        {
            search: search.value || undefined,
            position: positionFilter.value || undefined,
            status: statusFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const openLightbox = (url, title = 'Banner Image') => {
    previewLightbox.value = url;
    previewTitle.value = title;
};

const closeLightbox = () => {
    previewLightbox.value = null;
    previewTitle.value = '';
};

const openCreateModal = () => {
    editingBanner.value = null;
    imagePreview.value = null;
    form.value = {
        title: '',
        description: '',
        image: null,
        use_target_image: null,
        position: 'hero',
        link_type: 'service',
        artist_profile_id: props.artists && props.artists.length > 0 ? props.artists[0].id : null,
        service_id: null,
        product_id: null,
        course_id: null,
        button_text: 'Book Appointment',
        link: '',
        start_date: '',
        end_date: '',
        is_active: true,
        sort_order: 0,
        price: null,
    };
    showModal.value = true;
};

const openEditModal = (banner) => {
    editingBanner.value = banner;
    imagePreview.value = getBannerImg(banner.image);
    form.value = {
        title: banner.title,
        description: banner.description || '',
        image: null,
        use_target_image: banner.image || null,
        position: banner.position || 'hero',
        link_type: banner.link_type || (banner.course_id ? 'course' : (banner.service_id ? 'service' : (banner.artist_profile_id ? 'artist' : (banner.product_id ? 'product' : 'custom')))),
        artist_profile_id: banner.artist_profile_id ? Number(banner.artist_profile_id) : (banner.service?.artist_profile_id ? Number(banner.service.artist_profile_id) : (banner.course?.artist_profile_id ? Number(banner.course.artist_profile_id) : null)),
        service_id: banner.service_id ? Number(banner.service_id) : null,
        product_id: banner.product_id ? Number(banner.product_id) : null,
        course_id: banner.course_id ? Number(banner.course_id) : null,
        button_text: banner.button_text || '',
        link: banner.link || '',
        start_date: banner.start_date ? banner.start_date.split('T')[0] : '',
        end_date: banner.end_date ? banner.end_date.split('T')[0] : '',
        is_active: Boolean(banner.is_active),
        sort_order: banner.sort_order || 0,
        price: banner.price !== null ? Number(banner.price) : null,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingBanner.value = null;
    imagePreview.value = null;
};

const onFileChange = (e) => {
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

const toggleBannerStatus = (banner) => {
    router.post(
        route('admin.content.toggle-banner-status', banner.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Banner is now ${!banner.is_active ? 'Active' : 'Inactive'}`,
                    showConfirmButton: false,
                    timer: 2500,
                });
            },
        }
    );
};

const submitForm = () => {
    submitting.value = true;
    const formData = new FormData();
    formData.append('title', form.value.title);
    formData.append('description', form.value.description || '');
    formData.append('position', form.value.position);
    formData.append('is_active', form.value.is_active ? '1' : '0');
    formData.append('sort_order', form.value.sort_order || '0');
    formData.append('link_type', form.value.link_type || 'custom');
    if (form.value.artist_profile_id) formData.append('artist_profile_id', form.value.artist_profile_id);
    if (form.value.service_id) formData.append('service_id', form.value.service_id);
    if (form.value.product_id) formData.append('product_id', form.value.product_id);
    if (form.value.course_id) formData.append('course_id', form.value.course_id);
    if (form.value.button_text) formData.append('button_text', form.value.button_text);
    if (form.value.link) formData.append('link', form.value.link);
    if (form.value.use_target_image) formData.append('use_target_image', form.value.use_target_image);
    if (form.value.price) formData.append('price', form.value.price);
    if (form.value.start_date) formData.append('start_date', form.value.start_date);
    if (form.value.end_date) formData.append('end_date', form.value.end_date);
    if (form.value.image) formData.append('image', form.value.image);

    if (editingBanner.value) {
        // Use post with _method or dedicated update route for multipart
        router.post(route('admin.content.update-banner-post', editingBanner.value.id), formData, {
            preserveScroll: true,
            onSuccess: () => {
                submitting.value = false;
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Banner campaign updated successfully!',
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onError: (err) => {
                submitting.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Update failed',
                    text: Object.values(err)[0] || 'Please check form inputs.',
                });
            },
        });
    } else {
        router.post(route('admin.content.store-banner'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                submitting.value = false;
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'New banner published successfully!',
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onError: (err) => {
                submitting.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Creation failed',
                    text: Object.values(err)[0] || 'Please check form inputs.',
                });
            },
        });
    }
};

const deleteBanner = (banner) => {
    Swal.fire({
        title: 'Delete Promotional Banner?',
        text: `Are you sure you want to permanently delete "${banner.title}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(route('admin.content.destroy-banner', banner.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Banner deleted successfully.',
                        showConfirmButton: false,
                        timer: 2500,
                    });
                },
            });
        }
    });
};

const getPositionBadge = (pos) => {
    switch (pos) {
        case 'hero':
            return { label: '💎 Hero Carousel', class: 'bg-purple-100 text-purple-800 border-purple-200' };
        case 'right-top':
            return { label: '⚡ Promo Top', class: 'bg-blue-100 text-blue-800 border-blue-200' };
        case 'right-bottom':
            return { label: '🎀 Promo Bottom', class: 'bg-rose-100 text-rose-800 border-rose-200' };
        default:
            return { label: pos, class: 'bg-slate-100 text-slate-800 border-slate-200' };
    }
};

const getTargetSummary = (banner) => {
    if (banner.link_type === 'course' || banner.course_id) {
        const cName = banner.course?.title || `Course #${banner.course_id}`;
        const aName = banner.course?.artist_profile?.business_name || banner.artist_profile?.business_name || '';
        return {
            type: 'course',
            icon: '🎓',
            label: `Academy Course: ${aName ? aName + ' — ' : ''}${cName}`,
            class: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        };
    }
    if (banner.link_type === 'service' || banner.service_id) {
        const sName = banner.service?.name || 'Artist Service';
        const aName = banner.artist_profile?.business_name || banner.artist_profile?.user?.name || (banner.service?.artist_profile_id ? `Artist #${banner.service.artist_profile_id}` : '');
        return {
            type: 'service',
            icon: '🌸',
            label: `Direct Booking: ${aName ? aName + ' — ' : ''}${sName}`,
            class: 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }
    if (banner.link_type === 'artist' || banner.artist_profile_id) {
        const aName = banner.artist_profile?.business_name || banner.artist_profile?.user?.name || `Artist #${banner.artist_profile_id}`;
        return {
            type: 'artist',
            icon: '💎',
            label: `Artist Profile: ${aName}`,
            class: 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
    if (banner.link_type === 'product' || banner.product_id) {
        const pName = banner.product?.name || `Product #${banner.product_id}`;
        return {
            type: 'product',
            icon: '🛍️',
            label: `Store Product: ${pName}`,
            class: 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }
    return {
        type: 'custom',
        icon: '🔗',
        label: banner.link ? `Custom: ${banner.link}` : 'Default Directory (/artists)',
        class: 'bg-slate-50 text-slate-700 border-slate-200',
    };
};
</script>

<template>
    <Head title="Marketing Banners & Campaigns | Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- 1. HEADER & ACTIONS -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            Marketing Banners & Campaigns
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            PROMOTIONS
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Curate hero billboard sliders, sidebar promotional slots, and seasonal sponsor campaigns on the customer storefront.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-glam-500 via-rose-500 to-pink-600 hover:from-glam-600 hover:to-pink-700 text-white text-xs font-bold shadow-lg shadow-pink-950/20 transition-all active:scale-98 flex items-center gap-2 self-start sm:self-auto cursor-pointer"
                >
                    <span class="text-base">+</span>
                    <span>Create Campaign Banner</span>
                </button>
            </div>

            <!-- 2. CAMPAIGN KPI CARDS (4 RIBBON CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Active Live Banners -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Live Active Banners</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ stats.active_banners }} <span class="text-sm font-normal text-slate-400">/ {{ stats.total_banners }}</span>
                        </h3>
                        <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">Currently displaying on homepage</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        🟢
                    </div>
                </div>

                <!-- Hero Carousel -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800">Hero Main Slider</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-purple-950 mt-1">{{ stats.hero_banners }} Slots</h3>
                        <p class="text-[10px] text-purple-700 font-semibold mt-0.5">Flagship billboard carousel</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shadow-xs">
                        💎
                    </div>
                </div>

                <!-- Sidebar Promos -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Sidebar Promo Slots</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.promo_banners }} Slots</h3>
                        <p class="text-[10px] text-blue-700 font-semibold mt-0.5">Right-top & bottom deals</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl shadow-xs">
                        ⚡
                    </div>
                </div>

                <!-- Sponsor Revenue -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Campaign Ad Value</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ formatPrice(stats.sponsor_revenue) }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Sponsor revenue generated</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl shadow-xs">
                        🏷️
                    </div>
                </div>
            </div>

            <!-- 3. FILTER CONTROLS & VIEW MODE SWITCHER -->
            <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[200px]">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search campaign by title or description..."
                            class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500"
                            @keyup.enter="applyFilters"
                        />
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                    </div>

                    <!-- Position Filter -->
                    <select
                        v-model="positionFilter"
                        class="py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                        @change="applyFilters"
                    >
                        <option value="">All Placements</option>
                        <option value="hero">💎 Hero Carousel</option>
                        <option value="right-top">⚡ Promo Right-Top</option>
                        <option value="right-bottom">🎀 Promo Right-Bottom</option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                        @change="applyFilters"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">🟢 Active Only</option>
                        <option value="inactive">⚪ Inactive Only</option>
                        <option value="expired">✕ Expired Campaigns</option>
                    </select>
                </div>

                <!-- View Mode Toggle Buttons -->
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl">
                    <button
                        type="button"
                        @click="viewMode = 'grid'"
                        class="p-2 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'grid' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        title="Visual Showcase Grid"
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

            <!-- 4. VIEW MODE 1: VISUAL SHOWCASE GRID -->
            <div v-if="viewMode === 'grid'">
                <div v-if="banners.data && banners.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="banner in banners.data"
                        :key="banner.id"
                        class="rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden flex flex-col justify-between hover:shadow-lg hover:border-rose-200 transition-all duration-300 group"
                    >
                        <!-- Banner Image Container -->
                        <div class="relative h-48 w-full bg-slate-900 overflow-hidden">
                            <img
                                :src="getBannerImg(banner.image)"
                                :alt="banner.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 cursor-pointer"
                                @click="openLightbox(getBannerImg(banner.image), banner.title)"
                            />
                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20 pointer-events-none"></div>

                            <!-- Top Badges (Position & Order) -->
                            <div class="absolute top-3 inset-x-3 flex items-center justify-between">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold shadow-xs backdrop-blur-xs"
                                    :class="getPositionBadge(banner.position).class"
                                >
                                    {{ getPositionBadge(banner.position).label }}
                                </span>

                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-black/60 text-white backdrop-blur-xs">
                                    Sort: #{{ banner.sort_order }}
                                </span>
                            </div>

                            <!-- Price Tag (If Sponsor Ad) -->
                            <div v-if="banner.price" class="absolute bottom-3 left-3">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-serif font-black bg-rose-600 text-white shadow-md">
                                    {{ formatPrice(banner.price) }}
                                </span>
                            </div>

                            <!-- Live Active Indicator -->
                            <div class="absolute bottom-3 right-3">
                                <button
                                    type="button"
                                    @click.stop="toggleBannerStatus(banner)"
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition shadow-md flex items-center gap-1"
                                    :class="banner.is_active
                                        ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                                        : 'bg-slate-700/90 text-slate-200 hover:bg-slate-800'"
                                >
                                    <span>{{ banner.is_active ? '● Live Active' : '○ Paused' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Card Content Body -->
                        <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-serif font-bold text-slate-900 text-base line-clamp-1 group-hover:text-glam-600 transition-colors">
                                    {{ banner.title }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                                    {{ banner.description || 'Homepage promotional campaign banner highlighting beauty treatments.' }}
                                </p>
                            </div>

                            <!-- Click Target Destination Badge -->
                            <div class="pt-2">
                                <div
                                    class="p-2 rounded-xl border text-[11px] font-medium flex items-center gap-1.5 line-clamp-1"
                                    :class="getTargetSummary(banner).class"
                                >
                                    <span>{{ getTargetSummary(banner).icon }}</span>
                                    <span class="truncate font-semibold">{{ getTargetSummary(banner).label }}</span>
                                </div>
                            </div>

                            <!-- Date Validity Info -->
                            <div class="pt-3 border-t border-rose-50 flex items-center justify-between text-[11px] text-slate-400">
                                <span class="flex items-center gap-1">
                                    <span>🗓️</span>
                                    <span>{{ formatDate(banner.start_date) }} - {{ formatDate(banner.end_date) }}</span>
                                </span>
                            </div>

                            <!-- Card Action Buttons -->
                            <div class="pt-2 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="openEditModal(banner)"
                                    class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-glam-600 hover:text-white text-slate-700 font-bold text-xs transition cursor-pointer shadow-xs"
                                >
                                    ✏️ Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteBanner(banner)"
                                    class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold text-xs transition cursor-pointer"
                                >
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-16">
                    <AppEmptyState
                        icon="🖼️"
                        title="No banners found"
                        description="Click 'Create Campaign Banner' to publish your first homepage promotional billboard."
                    />
                </div>
            </div>

            <!-- 5. VIEW MODE 2: FULL LEDGER TABLE -->
            <div v-else-if="viewMode === 'table'" class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <div v-if="banners.data && banners.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4">Banner Preview</th>
                                <th class="py-3.5 px-4">Campaign Title</th>
                                <th class="py-3.5 px-4">Placement</th>
                                <th class="py-3.5 px-4">Target On Click</th>
                                <th class="py-3.5 px-4">Campaign Dates</th>
                                <th class="py-3.5 px-4">Ad Price</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center">Sort Order</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr v-for="banner in banners.data" :key="banner.id" class="hover:bg-rose-50/30 transition-colors">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <img
                                        :src="getBannerImg(banner.image)"
                                        :alt="banner.title"
                                        class="h-12 w-24 object-cover rounded-xl shadow-xs ring-1 ring-rose-200 cursor-pointer"
                                        @click="openLightbox(getBannerImg(banner.image), banner.title)"
                                    />
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ banner.title }}</p>
                                    <p class="text-[10px] text-slate-400 line-clamp-1">{{ banner.description || 'No description' }}</p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="getPositionBadge(banner.position).class"
                                    >
                                        {{ getPositionBadge(banner.position).label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-semibold border"
                                        :class="getTargetSummary(banner).class"
                                    >
                                        <span>{{ getTargetSummary(banner).icon }}</span>
                                        <span class="truncate max-w-[200px]">{{ getTargetSummary(banner).label }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600">
                                    <p>{{ formatDate(banner.start_date) }}</p>
                                    <p class="text-[10px] text-slate-400">to {{ formatDate(banner.end_date) }}</p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap font-serif font-bold text-slate-900">
                                    {{ banner.price ? formatPrice(banner.price) : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="toggleBannerStatus(banner)"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition"
                                        :class="banner.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ banner.is_active ? '✓ Live' : 'Paused' }}
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-700 whitespace-nowrap">
                                    #{{ banner.sort_order }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(banner)"
                                            class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-glam-600 hover:text-white text-slate-700 font-bold text-[11px] transition cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteBanner(banner)"
                                            class="px-3 py-1 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold text-[11px] transition cursor-pointer"
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
                        icon="🖼️"
                        title="No banners found"
                        description="Publish promotional campaign banners to feature on the marketplace."
                    />
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="banners.links && banners.links.length > 3" class="p-4 bg-white rounded-3xl border border-rose-100 shadow-xs">
                <AppPagination :links="banners.links" />
            </div>
        </div>

        <!-- 6. CREATE / EDIT BANNER MODAL (WITH LIVE PREVIEW) -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-onyx-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeModal"
        >
            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100 animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="p-6 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-glam-700">Campaign Management</span>
                        <h3 class="font-serif text-lg font-bold text-slate-900 mt-0.5">
                            {{ editingBanner ? 'Edit Promotional Banner' : 'Create New Campaign Banner' }}
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

                <!-- Form Body -->
                <form @submit.prevent="submitForm" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Live Mockup Preview Window -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Live Homepage Mockup Preview</label>
                        <div class="relative h-44 sm:h-52 w-full rounded-2xl bg-slate-950 overflow-hidden shadow-inner border border-slate-800">
                            <img
                                :src="imagePreview || 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&q=80&w=800'"
                                class="w-full h-full object-cover opacity-80"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent flex flex-col justify-end p-4 sm:p-5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold self-start mb-1 bg-white/20 text-white backdrop-blur-xs">
                                    {{ getPositionBadge(form.position).label }}
                                </span>
                                <h4 class="text-white font-serif font-bold text-base sm:text-lg drop-shadow-md line-clamp-1">
                                    {{ form.title || 'Your Promotional Campaign Headline' }}
                                </h4>
                                <p class="text-white/80 text-xs line-clamp-1 mt-0.5">
                                    {{ form.description || 'Special bridal makeover and luxury salon discounts across top cities.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[11px] font-bold text-slate-700">
                                Banner Artwork Image <span v-if="!form.use_target_image && !editingBanner" class="text-rose-500">*</span>
                            </label>
                            <span v-if="form.use_target_image && !form.image" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                <span>✓</span>
                                <span>Image auto-taken from {{ form.link_type === 'course' ? 'academy course' : (form.link_type === 'product' ? 'product' : (form.link_type === 'artist' ? 'artist' : 'service')) }}</span>
                            </span>
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            @change="onFileChange"
                            class="w-full p-2.5 rounded-2xl border border-slate-200 text-xs bg-slate-50 text-slate-800 focus:bg-white focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-glam-600 file:text-white cursor-pointer"
                            :required="!editingBanner && !form.use_target_image"
                        />
                        <p class="text-[10px] text-slate-400">
                            {{ form.use_target_image ? 'Auto-loaded from selected item. Upload a new image file only if you want to replace it.' : 'Recommended: 1200x600px for Hero Billboard, 600x400px for Sidebar promo.' }}
                        </p>
                    </div>

                    <!-- Title & Description -->
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">Campaign Title <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="e.g. Grand Bridal Glam Fest 2026"
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                required
                            />
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">Short Subtitle / Description</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Promotional callout displayed on the banner..."
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Placement & Order -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">Storefront Placement</label>
                            <select
                                v-model="form.position"
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 cursor-pointer"
                            >
                                <option value="hero">💎 Hero Main Carousel Slider</option>
                                <option value="right-top">⚡ Sidebar Promo Right-Top</option>
                                <option value="right-bottom">🎀 Sidebar Promo Right-Bottom</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">Display Sort Order</label>
                            <input
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                            />
                        </div>
                    </div>

                    <!-- Click Action & Target Destination (Service / Artist / Product / Course / Custom) -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-rose-50/70 via-pink-50/40 to-purple-50/50 border border-rose-200/80 space-y-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>🎯 Action & Destination When Clicked</span>
                                </label>
                                <span class="text-[10px] font-semibold text-rose-700 bg-rose-100/80 px-2 py-0.5 rounded-full">
                                    Instant Customer Action
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Select what happens when a customer clicks this banner on the storefront.
                            </p>
                        </div>

                        <!-- Link Type Switcher -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                            <button
                                type="button"
                                @click="onLinkTypeChange('service')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1 transition cursor-pointer"
                                :class="form.link_type === 'service'
                                    ? 'bg-white border-rose-500 text-rose-700 shadow-sm ring-2 ring-rose-500/20'
                                    : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'"
                            >
                                <span class="text-base">🌸</span>
                                <span>Service</span>
                                <span class="text-[9px] font-normal text-slate-400">Direct Booking</span>
                            </button>

                            <button
                                type="button"
                                @click="onLinkTypeChange('artist')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1 transition cursor-pointer"
                                :class="form.link_type === 'artist'
                                    ? 'bg-white border-purple-500 text-purple-700 shadow-sm ring-2 ring-purple-500/20'
                                    : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'"
                            >
                                <span class="text-base">💎</span>
                                <span>Artist Profile</span>
                                <span class="text-[9px] font-normal text-slate-400">Salon Studio</span>
                            </button>

                            <button
                                type="button"
                                @click="onLinkTypeChange('product')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1 transition cursor-pointer"
                                :class="form.link_type === 'product'
                                    ? 'bg-white border-amber-500 text-amber-700 shadow-sm ring-2 ring-amber-500/20'
                                    : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'"
                            >
                                <span class="text-base">🛍️</span>
                                <span>Product</span>
                                <span class="text-[9px] font-normal text-slate-400">Beauty Store</span>
                            </button>

                            <button
                                type="button"
                                @click="onLinkTypeChange('course')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1 transition cursor-pointer"
                                :class="form.link_type === 'course'
                                    ? 'bg-white border-indigo-500 text-indigo-700 shadow-sm ring-2 ring-indigo-500/20'
                                    : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'"
                            >
                                <span class="text-base">🎓</span>
                                <span>Academy Course</span>
                                <span class="text-[9px] font-normal text-slate-400">Beautician Workshop</span>
                            </button>

                            <button
                                type="button"
                                @click="onLinkTypeChange('custom')"
                                class="p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center justify-center gap-1 transition cursor-pointer"
                                :class="form.link_type === 'custom'
                                    ? 'bg-white border-slate-600 text-slate-800 shadow-sm ring-2 ring-slate-400/20'
                                    : 'bg-white/60 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'"
                            >
                                <span class="text-base">🔗</span>
                                <span>Custom URL</span>
                                <span class="text-[9px] font-normal text-slate-400">Custom Link</span>
                            </button>
                        </div>

                        <!-- Conditional Section 1: Artist Service for Direct Booking -->
                        <div v-if="form.link_type === 'service'" class="space-y-3 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">1. Select Artist <span class="text-rose-500">*</span></label>
                                    <select
                                        v-model="form.artist_profile_id"
                                        @change="onArtistChange"
                                        class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 bg-white focus:ring-2 focus:ring-rose-500 cursor-pointer"
                                    >
                                        <option :value="null">-- Choose an Artist --</option>
                                        <option v-for="a in artists" :key="a.id" :value="a.id">
                                            {{ a.business_name }} ({{ a.user?.name || 'Artist' }})
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">2. Select Service for Booking <span class="text-rose-500">*</span></label>
                                    <select
                                        v-model="form.service_id"
                                        @change="onServiceChange"
                                        :disabled="!form.artist_profile_id"
                                        class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 bg-white focus:ring-2 focus:ring-rose-500 cursor-pointer disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                        <option :value="null">
                                            {{ form.artist_profile_id ? (availableServicesForArtist.length ? '-- Choose Service --' : 'No active services found for this artist') : '-- Select artist first --' }}
                                        </option>
                                        <option v-for="s in availableServicesForArtist" :key="s.id" :value="s.id">
                                            {{ s.name }} — PKR {{ Number(s.price).toLocaleString() }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-2.5 rounded-xl bg-white/80 border border-rose-100 flex items-center gap-2 text-[11px] text-rose-800">
                                <span>✨</span>
                                <span><strong>Direct Booking Action:</strong> Clicking this banner will take the customer directly to checkout/booking for this artist and service!</span>
                            </div>
                        </div>

                        <!-- Conditional Section 2: Artist Profile -->
                        <div v-else-if="form.link_type === 'artist'" class="space-y-3 pt-1">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Select Artist Profile <span class="text-rose-500">*</span></label>
                                <select
                                    v-model="form.artist_profile_id"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 bg-white focus:ring-2 focus:ring-purple-500 cursor-pointer"
                                >
                                    <option :value="null">-- Choose an Artist --</option>
                                    <option v-for="a in artists" :key="a.id" :value="a.id">
                                        {{ a.business_name }} ({{ a.user?.name || 'Artist' }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Conditional Section 3: Product -->
                        <div v-else-if="form.link_type === 'product'" class="space-y-3 pt-1">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Select Featured Product <span class="text-rose-500">*</span></label>
                                <select
                                    v-model="form.product_id"
                                    @change="onProductChange"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 bg-white focus:ring-2 focus:ring-amber-500 cursor-pointer"
                                >
                                    <option :value="null">-- Choose a Product --</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} — PKR {{ Number(p.price).toLocaleString() }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Conditional Section 4: Academy Course -->
                        <div v-else-if="form.link_type === 'course'" class="space-y-3 pt-1">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Select Beautician Academy Course <span class="text-rose-500">*</span></label>
                                <select
                                    v-model="form.course_id"
                                    @change="onCourseChange"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 bg-white focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                >
                                    <option :value="null">-- Choose a Course Workshop --</option>
                                    <option v-for="c in courses" :key="c.id" :value="c.id">
                                        {{ c.title }} ({{ c.artist_profile?.business_name || 'Studio' }}) — PKR {{ Number(c.discount_price || c.price).toLocaleString() }}
                                    </option>
                                </select>
                            </div>

                            <div class="p-2.5 rounded-xl bg-white/80 border border-indigo-100 flex items-center gap-2 text-[11px] text-indigo-800">
                                <span>🎓</span>
                                <span><strong>Academy Course Action:</strong> Clicking this banner opens the course details page and instant enrollment form!</span>
                            </div>
                        </div>

                        <!-- Conditional Section 5: Custom URL -->
                        <div v-else class="space-y-3 pt-1">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Custom Destination URL or Route</label>
                                <input
                                    v-model="form.link"
                                    type="text"
                                    placeholder="e.g. /courses, /services, or https://..."
                                    class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 bg-white focus:ring-2 focus:ring-slate-500"
                                />
                            </div>
                        </div>

                        <!-- CTA Button Label Customization -->
                        <div class="pt-2 border-t border-rose-100/80 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2 items-end">
                            <div>
                                <label class="text-[11px] font-bold text-slate-700">CTA Button Text on Banner</label>
                                <input
                                    v-model="form.button_text"
                                    type="text"
                                    placeholder="e.g. Book Appointment, Explore, Shop Now"
                                    class="w-full mt-1 p-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 bg-white focus:ring-2 focus:ring-rose-500"
                                />
                            </div>
                            <div class="flex items-center gap-1 pb-0.5 flex-wrap">
                                <button
                                    type="button"
                                    @click="form.button_text = 'Book Appointment'"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-rose-100 text-rose-700 border border-rose-200 transition cursor-pointer"
                                >
                                    Book Appointment
                                </button>
                                <button
                                    type="button"
                                    @click="form.button_text = 'Book Service'"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-rose-100 text-rose-700 border border-rose-200 transition cursor-pointer"
                                >
                                    Book Service
                                </button>
                                <button
                                    type="button"
                                    @click="form.button_text = 'Shop Now'"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-amber-100 text-amber-700 border border-amber-200 transition cursor-pointer"
                                >
                                    Shop Now
                                </button>
                                <button
                                    type="button"
                                    @click="form.button_text = 'Explore'"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 transition cursor-pointer"
                                >
                                    Explore
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Date Schedule & Ad Pricing -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">Start Date</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs text-slate-900 focus:ring-2 focus:ring-glam-500"
                            />
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">End Date (Auto-expire)</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs text-slate-900 focus:ring-2 focus:ring-glam-500"
                            />
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-700">Sponsor Ad Fee (PKR)</label>
                            <input
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="500"
                                placeholder="e.g. 15000"
                                class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs text-slate-900 focus:ring-2 focus:ring-glam-500"
                            />
                        </div>
                    </div>

                    <!-- Active Toggle Switch -->
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-900">Publish Immediately</p>
                            <p class="text-[10px] text-slate-400">Make this banner visible on the homepage right away</p>
                        </div>
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                        />
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
                            :disabled="submitting"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-glam-600 hover:bg-glam-700 shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <span v-if="submitting">Processing...</span>
                            <span v-else>{{ editingBanner ? 'Update Campaign' : 'Publish Banner' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 7. IMAGE LIGHTBOX MODAL -->
        <div
            v-if="previewLightbox"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 cursor-pointer"
            @click="closeLightbox"
        >
            <div class="relative max-w-4xl w-full bg-slate-900 rounded-3xl overflow-hidden shadow-2xl p-2 border border-slate-800" @click.stop>
                <div class="flex items-center justify-between p-3 text-white">
                    <span class="font-serif font-bold text-sm truncate">{{ previewTitle }}</span>
                    <button @click="closeLightbox" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center cursor-pointer">
                        ✕
                    </button>
                </div>
                <img :src="previewLightbox" class="w-full max-h-[80vh] object-contain rounded-2xl" />
            </div>
        </div>
    </AdminLayout>
</template>
