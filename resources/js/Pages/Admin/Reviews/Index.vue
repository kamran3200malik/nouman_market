<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import Swal from 'sweetalert2';

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
const selectedReview = ref(null);

const filterStatuses = computed(() => [
    { key: '', label: 'All Reviews', count: props.stats?.total ?? 0 },
    { key: 'pending', label: '⏳ Pending Approval', count: props.stats?.pending ?? 0 },
    { key: 'approved', label: '✓ Approved', count: props.stats?.approved ?? 0 },
]);

const applyFilters = () => {
    router.get(
        route('admin.reviews.index'),
        {
            search: searchInput.value || undefined,
            status: statusFilter.value || undefined,
            rating: ratingFilter.value || undefined,
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

const clearFilters = () => {
    searchInput.value = '';
    statusFilter.value = '';
    ratingFilter.value = '';
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

const approveReview = (review) => {
    router.post(route('admin.reviews.approve', review.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Review approved and published!',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

const rejectReview = (review) => {
    router.post(route('admin.reviews.reject', review.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Review unapproved and hidden.',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

const deleteReview = (review) => {
    Swal.fire({
        title: 'Delete Review?',
        text: 'Permanently remove this review? This will recalculate the product rating.',
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
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Review deleted',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Product Reviews & Ratings | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- PAGE TITLE & DESCRIPTION -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                        Product Reviews Moderation
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Moderate authentic customer ratings, verified purchase reviews, and testimonials on cosmetic products.
                    </p>
                </div>
            </div>

            <!-- STATS SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Reviews</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.total }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-xs">
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

                <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Average Rating</p>
                        <div class="flex items-baseline gap-1 mt-1">
                            <h3 class="text-2xl sm:text-3xl font-serif font-bold text-rose-600">{{ stats.average_rating }}</h3>
                            <span class="text-amber-400 text-base">★</span>
                            <span class="text-xs text-slate-400">/ 5.0</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                        ⭐
                    </div>
                </div>
            </div>

            <!-- STATUS TABS -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="status in filterStatuses"
                    :key="status.key"
                    type="button"
                    class="px-4 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
                    :class="statusFilter === status.key
                        ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
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

            <!-- FILTER TOOLBAR -->
            <div class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative flex-1 w-full max-w-md">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Search by buyer name, product, or review text..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-slate-900"
                        @keyup.enter="applyFilters"
                    />
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <select
                        v-model="ratingFilter"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 text-slate-700 focus:border-rose-500 focus:ring-0 cursor-pointer"
                    >
                        <option value="">All Ratings</option>
                        <option value="5">★★★★★ 5 Stars</option>
                        <option value="4">★★★★☆ 4 Stars</option>
                        <option value="3">★★★☆☆ 3 Stars</option>
                        <option value="2">★★☆☆☆ 2 Stars</option>
                        <option value="1">★☆☆☆☆ 1 Star</option>
                    </select>

                    <button
                        v-if="searchInput || statusFilter || ratingFilter"
                        @click="clearFilters"
                        class="px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- REVIEWS TABLE -->
            <div class="rounded-3xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-black uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4 sm:px-6">Product</th>
                                <th class="py-3.5 px-4">Buyer / Customer</th>
                                <th class="py-3.5 px-4">Rating & Review</th>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="rev in reviews.data" :key="rev.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-2xl overflow-hidden bg-slate-50 border border-slate-200 shrink-0">
                                            <img
                                                v-if="rev.product?.image_url"
                                                :src="rev.product.image_url"
                                                :alt="rev.product.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="h-full w-full flex items-center justify-center text-lg">
                                                🛍️
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-[10px] font-bold text-rose-600 uppercase">{{ rev.product?.brand || 'Luxe' }}</span>
                                            <p class="font-bold text-slate-900 text-xs truncate max-w-[200px]">{{ rev.product?.name || 'Product' }}</p>
                                            <span class="text-[10px] text-slate-400">PKR {{ Number(rev.product?.price || 0).toLocaleString() }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    <p class="font-bold text-slate-900">{{ rev.author_name || rev.user?.name || 'Verified Buyer' }}</p>
                                    <p class="text-[11px] text-slate-400">{{ rev.user?.email || '-' }}</p>
                                    <span v-if="rev.is_verified_purchase" class="inline-flex items-center gap-0.5 text-[9px] font-bold text-emerald-600 mt-0.5">
                                        <span>✓</span>
                                        <span>Verified Purchase</span>
                                    </span>
                                </td>

                                <td class="py-4 px-4 max-w-sm">
                                    <div class="flex items-center gap-1 text-amber-400 text-xs font-bold mb-1">
                                        <span v-for="s in (rev.rating || 5)" :key="s">★</span>
                                    </div>
                                    <p v-if="rev.title" class="font-bold text-slate-800 text-xs mb-0.5">{{ rev.title }}</p>
                                    <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">{{ rev.comment }}</p>
                                </td>

                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap">
                                    {{ formatDate(rev.created_at) }}
                                </td>

                                <td class="py-4 px-4">
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border"
                                        :class="rev.is_approved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                    >
                                        {{ rev.is_approved ? '✓ Approved' : '⏳ Pending' }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="!rev.is_approved"
                                            @click="approveReview(rev)"
                                            class="px-2.5 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold uppercase transition cursor-pointer"
                                            title="Approve Review"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            v-if="rev.is_approved"
                                            @click="rejectReview(rev)"
                                            class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold uppercase transition cursor-pointer"
                                            title="Hide Review"
                                        >
                                            Hide
                                        </button>

                                        <button
                                            @click="deleteReview(rev)"
                                            class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                            title="Delete permanently"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="reviews.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    No customer product reviews found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="reviews.links && reviews.links.length > 3" class="p-4 border-t border-slate-100">
                    <AppPagination :links="reviews.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
