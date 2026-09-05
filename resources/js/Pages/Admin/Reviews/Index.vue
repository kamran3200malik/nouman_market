<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    reviews: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            pending: 0,
            approved: 0,
            average_rating: 5.0,
            with_photos: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchInput = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const ratingFilter = ref(props.filters.rating || '');
const hasPhotosFilter = ref(props.filters.has_photos || '');
const loading = ref(false);

// Modals
const selectedReview = ref(null);
const lightboxImage = ref(null);
const actionLoading = ref(false);

const filterStatuses = computed(() => [
    { key: '', label: 'All Reviews', count: props.stats?.total ?? 0 },
    { key: 'pending', label: '⏳ Pending Approval', count: props.stats?.pending ?? 0, highlight: (props.stats?.pending ?? 0) > 0 },
    { key: 'approved', label: '✓ Approved', count: props.stats?.approved ?? 0 },
]);

const ratingsOptions = [
    { value: '', label: '★ All Ratings' },
    { value: '5', label: '5 Stars (★★★★★)' },
    { value: '4', label: '4 Stars (★★★★☆)' },
    { value: '3', label: '3 Stars (★★★☆☆)' },
    { value: '2', label: '2 Stars (★★☆☆☆)' },
    { value: '1', label: '1 Star (★☆☆☆☆)' },
];

const applyFilters = () => {
    loading.value = true;
    router.get(
        route('admin.reviews.index'),
        {
            search: searchInput.value || undefined,
            status: statusFilter.value || undefined,
            rating: ratingFilter.value || undefined,
            has_photos: hasPhotosFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                loading.value = false;
            },
        }
    );
};

const setStatus = (statusKey) => {
    statusFilter.value = statusKey;
    applyFilters();
};

const clearFilters = () => {
    searchInput.value = '';
    statusFilter.value = '';
    ratingFilter.value = '';
    hasPhotosFilter.value = '';
    applyFilters();
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff`);
};

const getImageUrl = (path) => {
    return storageUrl(path);
};

// Moderation Actions
const approveReview = (review) => {
    Swal.fire({
        title: 'Approve Review?',
        text: `Publish review by "${review.customer?.name || 'Client'}" for "${review.artist_profile?.business_name || 'Salon'}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Approve Review',
    }).then((result) => {
        if (result.isConfirmed) {
            actionLoading.value = true;
            router.post(route('admin.reviews.approve', review.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    actionLoading.value = false;
                    if (selectedReview.value?.id === review.id) {
                        selectedReview.value.is_approved = true;
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Review approved and published!',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
                onError: () => {
                    actionLoading.value = false;
                }
            });
        }
    });
};

const rejectReview = (review) => {
    Swal.fire({
        title: 'Reject / Hide Review?',
        text: 'Enter the reason for rejection (e.g. offensive language, spam, policy violation):',
        input: 'textarea',
        inputPlaceholder: 'Reason for rejection...',
        inputAttributes: {
            'aria-label': 'Reason for rejection'
        },
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Reject Review',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Please provide a rejection reason.';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            actionLoading.value = true;
            router.post(route('admin.reviews.reject', review.id), {
                reason: result.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    actionLoading.value = false;
                    if (selectedReview.value?.id === review.id) {
                        selectedReview.value.is_approved = false;
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: 'Review rejected & unlisted',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
                onError: () => {
                    actionLoading.value = false;
                }
            });
        }
    });
};

const deleteReview = (review) => {
    Swal.fire({
        title: 'Delete Review Permanently?',
        text: 'This will remove the review and all attached photos. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Delete',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.reviews.destroy', review.id), {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedReview.value?.id === review.id) {
                        selectedReview.value = null;
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Review deleted',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

const openDetailModal = (review) => {
    selectedReview.value = review;
};

const closeDetailModal = () => {
    selectedReview.value = null;
};

const openLightbox = (imagePath) => {
    lightboxImage.value = getImageUrl(imagePath);
};

const closeLightbox = () => {
    lightboxImage.value = null;
};
</script>

<template>
    <Head title="Client Reviews & Moderation | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- PAGE TITLE & DESCRIPTION -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900">
                        Client Reviews & Moderation
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Moderate client feedbacks, ratings, and customer makeover verification photos across all beauty salons.
                    </p>
                </div>
            </div>

            <!-- 1. STATS SUMMARY RIBBON (4 CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Reviews</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.total }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-glam-700 flex items-center justify-center text-xl shadow-xs">
                        💬
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white border border-amber-200/80 shadow-xs flex items-center justify-between" :class="stats.pending > 0 ? 'ring-2 ring-amber-400/40 bg-amber-50/20' : ''">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Moderation</p>
                            <span v-if="stats.pending > 0" class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-amber-900 mt-1">{{ stats.pending }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl shadow-xs">
                        ⏳
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white border border-emerald-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Approved Reviews</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-emerald-950 mt-1">{{ stats.approved }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        ✓
                    </div>
                </div>

                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Average Platform Rating</p>
                        <div class="flex items-baseline gap-1 mt-1">
                            <h3 class="text-2xl sm:text-3xl font-serif font-bold text-glam-700">{{ stats.average_rating }}</h3>
                            <span class="text-amber-400 text-base">★</span>
                            <span class="text-xs text-slate-400">/ 5.0</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                        ⭐
                    </div>
                </div>
            </div>

            <!-- 2. STATUS TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="status in filterStatuses"
                    :key="status.key"
                    type="button"
                    class="px-4 py-2 rounded-2xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-2"
                    :class="statusFilter === status.key
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-700 border border-rose-100 hover:bg-rose-50/60'"
                    @click="setStatus(status.key)"
                >
                    <span>{{ status.label }}</span>
                    <span
                        class="px-1.5 py-0.5 rounded-full text-[10px]"
                        :class="statusFilter === status.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                    >
                        {{ status.count }}
                    </span>
                </button>
            </div>

            <!-- 3. FILTER TOOLBAR -->
            <div class="p-4 sm:p-5 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <!-- Search Input -->
                    <div class="sm:col-span-6 relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchInput"
                            type="text"
                            placeholder="Search by client name, email, salon name, review comment, or booking #..."
                            class="w-full pl-10 pr-8 py-2.5 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 transition-all"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            v-if="searchInput"
                            type="button"
                            class="absolute inset-y-0 right-2.5 flex items-center text-slate-400 hover:text-slate-600"
                            @click="searchInput = ''; applyFilters();"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Rating Dropdown -->
                    <div class="sm:col-span-3">
                        <select
                            v-model="ratingFilter"
                            class="w-full py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                            @change="applyFilters"
                        >
                            <option v-for="r in ratingsOptions" :key="r.value" :value="r.value">
                                {{ r.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Photos Dropdown -->
                    <div class="sm:col-span-3">
                        <select
                            v-model="hasPhotosFilter"
                            class="w-full py-2.5 px-3 rounded-2xl bg-slate-50 border border-rose-100 text-xs sm:text-sm text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-glam-500 cursor-pointer"
                            @change="applyFilters"
                        >
                            <option value="">📸 Photo Attachments</option>
                            <option value="yes">With Makeover Photos</option>
                            <option value="no">Without Photos</option>
                        </select>
                    </div>
                </div>

                <!-- Active tags & reset -->
                <div v-if="searchInput || ratingFilter || hasPhotosFilter" class="flex items-center justify-between pt-2 border-t border-rose-50 text-xs">
                    <span class="text-slate-500">Filters applied</span>
                    <button
                        type="button"
                        class="text-glam-700 font-semibold hover:underline"
                        @click="clearFilters"
                    >
                        Reset all filters
                    </button>
                </div>
            </div>

            <!-- 4. REVIEWS MODERATION TABLE -->
            <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <!-- Loading overlay -->
                <div v-if="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <div class="w-8 h-8 border-3 border-glam-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div v-if="reviews.data && reviews.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4 sm:px-6">Customer</th>
                                <th class="py-3.5 px-4">Salon / Artist</th>
                                <th class="py-3.5 px-4">Rating & Feedback</th>
                                <th class="py-3.5 px-4">Booking Ref</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Moderation Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr
                                v-for="review in reviews.data"
                                :key="review.id"
                                class="hover:bg-rose-50/30 transition-colors"
                            >
                                <!-- Customer Column -->
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="getAvatar(review.customer?.avatar, review.customer?.name)"
                                            :alt="review.customer?.name"
                                            class="w-9 h-9 rounded-full object-cover ring-1 ring-rose-200"
                                        />
                                        <div>
                                            <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ review.customer?.name || 'Verified Client' }}</p>
                                            <p class="text-[11px] text-slate-400">{{ review.customer?.email || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Salon / Artist Column -->
                                <td class="py-4 px-4">
                                    <div class="space-y-0.5">
                                        <p class="font-semibold text-slate-800">{{ review.artist_profile?.business_name || review.artist_profile?.user?.name || 'Salon Studio' }}</p>
                                        <p class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span>📍</span>
                                            <span>{{ review.artist_profile?.city?.name || review.artist_profile?.city || 'Pakistan' }}</span>
                                        </p>
                                    </div>
                                </td>

                                <!-- Rating & Feedback Text -->
                                <td class="py-4 px-4 max-w-sm">
                                    <div class="space-y-1.5">
                                        <!-- Stars row -->
                                        <div class="flex items-center gap-1">
                                            <span class="flex text-amber-400 text-sm">
                                                <span v-for="s in 5" :key="s">
                                                    {{ s <= review.rating ? '★' : '☆' }}
                                                </span>
                                            </span>
                                            <span class="font-bold text-slate-700 ml-1">{{ review.rating }}.0</span>
                                            <span class="text-[10px] text-slate-400">• {{ formatDate(review.created_at) }}</span>
                                        </div>

                                        <!-- Review Comment -->
                                        <p class="text-slate-600 line-clamp-2 leading-relaxed">
                                            {{ review.review || 'No written commentary provided.' }}
                                        </p>

                                        <!-- Photos Strip (if any) -->
                                        <div v-if="review.images && review.images.length > 0" class="flex items-center gap-1.5 pt-1">
                                            <button
                                                v-for="(img, idx) in review.images.slice(0, 3)"
                                                :key="idx"
                                                type="button"
                                                class="w-8 h-8 rounded-lg overflow-hidden border border-rose-200 hover:opacity-80 transition shrink-0"
                                                @click="openLightbox(img.image_path)"
                                                title="Click to view photo"
                                            >
                                                <img :src="getImageUrl(img.image_path)" :alt="'Makeover photo ' + (idx + 1)" class="w-full h-full object-cover" />
                                            </button>
                                            <span v-if="review.images.length > 3" class="text-[10px] text-slate-400 font-bold">
                                                +{{ review.images.length - 3 }} more
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Booking Ref & Service -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div v-if="review.booking" class="space-y-0.5">
                                        <span class="font-semibold text-glam-700 block">
                                            {{ review.booking.service?.name || 'Treatment' }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 block font-mono">
                                            #{{ review.booking.booking_number }}
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Direct review</span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 text-center whitespace-nowrap">
                                    <span
                                        v-if="review.is_approved"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        ✓ Approved
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse"
                                    >
                                        ⏳ Pending
                                    </span>
                                </td>

                                <!-- Moderation Actions -->
                                <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Quick Approve -->
                                        <button
                                            v-if="!review.is_approved"
                                            type="button"
                                            class="p-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white transition-colors"
                                            title="Approve & Publish Review"
                                            @click="approveReview(review)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>

                                        <!-- Quick Reject -->
                                        <button
                                            v-if="review.is_approved"
                                            type="button"
                                            class="p-2 rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white transition-colors"
                                            title="Reject / Unlist Review"
                                            @click="rejectReview(review)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>

                                        <!-- Inspect Modal -->
                                        <button
                                            type="button"
                                            class="p-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                                            title="View Details"
                                            @click="openDetailModal(review)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors"
                                            title="Delete Review Permanently"
                                            @click="deleteReview(review)"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-else class="py-12">
                    <AppEmptyState
                        icon="💬"
                        title="No client reviews found"
                        description="There are currently no reviews matching your status or search query."
                        action-text="Reset Filters"
                        @action="clearFilters"
                    />
                </div>

                <!-- Pagination -->
                <div v-if="reviews.links && reviews.links.length > 3" class="p-4 border-t border-rose-100">
                    <AppPagination :links="reviews.links" />
                </div>
            </div>
        </div>

        <!-- 5. DETAIL INSPECTION MODAL -->
        <div
            v-if="selectedReview"
            class="fixed inset-0 z-50 overflow-y-auto bg-onyx-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeDetailModal"
        >
            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100 animate-in fade-in zoom-in duration-200">
                <!-- Header -->
                <div class="p-6 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-glam-700">Review Moderation Dossier</span>
                        <h3 class="font-serif text-xl font-bold text-slate-900 mt-0.5">
                            Client Feedback #{{ selectedReview.id }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="w-8 h-8 rounded-full bg-white text-slate-500 hover:text-slate-900 flex items-center justify-center shadow-xs"
                        @click="closeDetailModal"
                    >
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Rating & Status Banner -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl text-amber-400">
                                <span v-for="s in 5" :key="s">
                                    {{ s <= selectedReview.rating ? '★' : '☆' }}
                                </span>
                            </span>
                            <span class="font-bold text-slate-800 text-lg">{{ selectedReview.rating }}.0</span>
                        </div>
                        <span
                            :class="selectedReview.is_approved ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'"
                            class="px-3 py-1 rounded-full text-xs font-bold border"
                        >
                            {{ selectedReview.is_approved ? '✓ Approved & Public' : '⏳ Pending Moderation' }}
                        </span>
                    </div>

                    <!-- Customer & Salon Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Customer -->
                        <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Client Profile</span>
                            <div class="flex items-center gap-3 mt-2">
                                <img
                                    :src="getAvatar(selectedReview.customer?.avatar, selectedReview.customer?.name)"
                                    :alt="selectedReview.customer?.name"
                                    class="w-10 h-10 rounded-full object-cover ring-2 ring-rose-200"
                                />
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ selectedReview.customer?.name || 'Client' }}</p>
                                    <p class="text-xs text-slate-500">{{ selectedReview.customer?.email || 'N/A' }}</p>
                                    <p v-if="selectedReview.customer?.phone" class="text-xs text-slate-500">{{ selectedReview.customer?.phone }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Salon / Artist -->
                        <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Salon Studio</span>
                            <div class="flex items-center gap-3 mt-2">
                                <img
                                    :src="getAvatar(selectedReview.artist_profile?.profile_image, selectedReview.artist_profile?.business_name)"
                                    :alt="selectedReview.artist_profile?.business_name"
                                    class="w-10 h-10 rounded-full object-cover ring-2 ring-rose-200"
                                />
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ selectedReview.artist_profile?.business_name || 'Salon Studio' }}</p>
                                    <p class="text-xs text-slate-500">📍 {{ selectedReview.artist_profile?.city?.name || selectedReview.artist_profile?.city || 'Pakistan' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Booking Details (if present) -->
                    <div v-if="selectedReview.booking" class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Associated Appointment</span>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="font-bold text-slate-800">
                                💄 {{ selectedReview.booking.service?.name || 'Beauty Treatment' }}
                            </span>
                            <span class="font-mono text-glam-700 bg-rose-50 px-2 py-0.5 rounded-md font-semibold">
                                Booking #{{ selectedReview.booking.booking_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Full Review Comment -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Client Statement & Feedback</label>
                        <div class="p-4 rounded-2xl bg-white border border-rose-100 text-slate-800 text-sm leading-relaxed whitespace-pre-line shadow-xs">
                            {{ selectedReview.review || 'No written feedback was submitted with this rating.' }}
                        </div>
                    </div>

                    <!-- Makeover Attached Photos -->
                    <div v-if="selectedReview.images && selectedReview.images.length > 0" class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer Makeover Attachments ({{ selectedReview.images.length }})</label>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            <button
                                v-for="(img, idx) in selectedReview.images"
                                :key="idx"
                                type="button"
                                class="aspect-square rounded-2xl overflow-hidden border border-rose-200 hover:scale-102 transition shadow-xs group"
                                @click="openLightbox(img.image_path)"
                            >
                                <img :src="getImageUrl(img.image_path)" :alt="'Makeover photo ' + (idx + 1)" class="w-full h-full object-cover group-hover:opacity-90" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Moderation Actions -->
                <div class="p-5 border-t border-rose-100 bg-slate-50 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-100 transition"
                        @click="deleteReview(selectedReview)"
                    >
                        Delete Review
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="selectedReview.is_approved"
                            type="button"
                            class="px-5 py-2 rounded-xl text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 transition"
                            @click="rejectReview(selectedReview)"
                        >
                            Reject & Unlist
                        </button>
                        <button
                            v-if="!selectedReview.is_approved"
                            type="button"
                            class="px-6 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md transition"
                            @click="approveReview(selectedReview)"
                        >
                            ✓ Approve Review
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 transition"
                            @click="closeDetailModal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. LIGHTBOX PHOTO MODAL -->
        <div
            v-if="lightboxImage"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4"
            @click="closeLightbox"
        >
            <div class="relative max-w-4xl max-h-[90vh]">
                <button
                    type="button"
                    class="absolute -top-12 right-0 text-white hover:text-rose-400 text-2xl font-bold"
                    @click="closeLightbox"
                >
                    ✕
                </button>
                <img :src="lightboxImage" alt="Makeover verification photo" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl" />
            </div>
        </div>
    </AdminLayout>
</template>
