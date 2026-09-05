<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    reviews: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    pendingReviewBookings: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total_reviews: 0, avg_rating: 5.0, five_star_count: 0, pending_count: 0 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', rating: '' })
    }
});

const searchQuery = ref(props.filters.search || '');
const selectedRating = ref(props.filters.rating || '');

// Photo Lightbox state
const activeLightboxImage = ref(null);

// Review Modal state (Create / Edit)
const isModalOpen = ref(false);
const modalMode = ref('create'); // 'create' | 'edit'
const selectedBooking = ref(null);
const selectedReview = ref(null);
const hoverRating = ref(0);

const reviewForm = useForm({
    booking_id: '',
    rating: 5,
    review: '',
    images: [],
});

let searchTimeout = null;
const handleFilterChange = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('customer.reviews.index'), {
            search: searchQuery.value || undefined,
            rating: selectedRating.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
};

watch(searchQuery, handleFilterChange);
watch(selectedRating, handleFilterChange);

const openCreateModal = (booking) => {
    modalMode.value = 'create';
    selectedBooking.value = booking;
    selectedReview.value = null;
    reviewForm.reset();
    reviewForm.clearErrors();
    reviewForm.booking_id = booking.id;
    reviewForm.rating = 5;
    isModalOpen.value = true;
};

const openEditModal = (review) => {
    modalMode.value = 'edit';
    selectedReview.value = review;
    selectedBooking.value = review.booking;
    reviewForm.reset();
    reviewForm.clearErrors();
    reviewForm.rating = review.rating;
    reviewForm.review = review.review;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedBooking.value = null;
    selectedReview.value = null;
    reviewForm.reset();
};

const handleImageUpload = (e) => {
    reviewForm.images = Array.from(e.target.files);
};

const submitReview = () => {
    if (modalMode.value === 'create') {
        reviewForm.post(route('customer.reviews.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Feedback Submitted!',
                    text: 'Thank you for rating your makeover appointment.',
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        });
    } else {
        reviewForm.put(route('customer.reviews.update', selectedReview.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Review Updated!',
                    text: 'Your review changes have been saved.',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        });
    }
};

const deleteReview = (reviewId) => {
    Swal.fire({
        title: 'Delete this Review?',
        text: 'Are you sure you want to remove your rating and feedback for this appointment?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('customer.reviews.destroy', reviewId), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Review removed successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            });
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="My Salon Reviews & Ratings" />

    <CustomerLayout>
        <div class="space-y-6 sm:space-y-8">
            <!-- 1. LUXURY HEADER BANNER -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#240c1d] via-[#35102a] to-[#1e0817] p-6 sm:p-8 text-white shadow-xl border border-pink-900/50">
                <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-rose-500/20 blur-3xl pointer-events-none"></div>
                <div class="absolute left-1/3 -bottom-16 h-64 w-64 rounded-full bg-pink-400/15 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-pink-500/20 px-3.5 py-1 text-xs font-bold text-pink-300 border border-pink-500/30 backdrop-blur-sm">
                            <span>⭐</span>
                            <span>Verified Client Feedback & Ratings</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-2">
                            <span>My Salon Reviews & Experiences</span>
                            <span class="text-xl">✨</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-pink-200/80 max-w-2xl">
                            Your published ratings, photos, and reviews sharing your bridal and makeover experiences with verified beauticians across Pakistan.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link
                            :href="route('customer.bookings.index')"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white text-sm font-bold shadow-lg shadow-pink-950/40 hover:scale-[1.02] transition-all cursor-pointer"
                        >
                            <span>🗓️</span>
                            <span>View All Bookings</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. PENDING REVIEW INVITATION ALERT -->
            <div
                v-if="pendingReviewBookings.length > 0"
                class="rounded-3xl bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-pink-500/15 p-6 border border-amber-300/60 shadow-xs relative overflow-hidden"
            >
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl shrink-0 border border-amber-200 shadow-xs">
                            🌟
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                You have {{ pendingReviewBookings.length }} completed appointment{{ pendingReviewBookings.length > 1 ? 's' : '' }} waiting for your review!
                            </h3>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Share how your makeup, hair style, or beauty session went to help the salon community.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            v-for="booking in pendingReviewBookings.slice(0, 2)"
                            :key="booking.id"
                            @click="openCreateModal(booking)"
                            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-rose-600 hover:from-amber-700 hover:to-rose-700 text-white text-xs font-bold shadow-md shadow-amber-950/20 transition cursor-pointer flex items-center gap-1.5"
                        >
                            <span>⭐ Review {{ booking.artist?.business_name }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. REVIEW STATS BAR -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6">
                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-pink-700">Reviews Written</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.total_reviews }}</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-pink-50 text-glam-700 flex items-center justify-center text-2xl border border-pink-100">
                        ✍️
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Avg Rating Given</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1 flex items-center gap-1">
                            <span>{{ Number(stats.avg_rating || 5.0).toFixed(1) }}</span>
                            <span class="text-amber-500 text-xl">★</span>
                        </h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-2xl border border-amber-100">
                        ⭐
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">5-Star Praises</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1">{{ stats.five_star_count }}</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl border border-rose-100">
                        💖
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-5 border border-pink-100/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Verified Badge</p>
                        <h3 class="text-3xl font-serif font-bold text-slate-900 mt-1">100%</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl border border-emerald-100">
                        🛡️
                    </div>
                </div>
            </div>

            <!-- 4. SEARCH & RATING FILTER STRIP -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white/80 backdrop-blur-md p-4 rounded-3xl border border-pink-100 shadow-xs">
                <div class="relative w-full sm:w-80">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search your reviews or salon names..."
                        class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-xl border border-pink-200 bg-pink-50/40 focus:bg-white focus:border-glam-500 focus:ring-2 focus:ring-pink-200 transition"
                    />
                    <span class="absolute left-3.5 top-3 text-slate-400 text-xs">🔍</span>
                </div>

                <!-- Star Rating Filter Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                    <button
                        type="button"
                        @click="selectedRating = ''"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0"
                        :class="!selectedRating ? 'bg-slate-900 text-white shadow-xs' : 'bg-pink-50 text-slate-600 hover:bg-pink-100'"
                    >
                        All Stars
                    </button>
                    <button
                        v-for="star in [5, 4, 3, 2, 1]"
                        :key="star"
                        type="button"
                        @click="selectedRating = String(star)"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer shrink-0 flex items-center gap-1"
                        :class="selectedRating === String(star) ? 'bg-amber-500 text-white shadow-xs' : 'bg-pink-50 text-slate-600 hover:bg-pink-100'"
                    >
                        <span>{{ star }}</span>
                        <span class="text-amber-300">★</span>
                    </button>
                </div>
            </div>

            <!-- 5. REVIEWS LIST -->
            <div v-if="reviews.data && reviews.data.length > 0" class="space-y-4">
                <div
                    v-for="item in reviews.data"
                    :key="item.id"
                    class="p-6 rounded-3xl bg-white border border-pink-100 shadow-xs hover:shadow-md transition-all space-y-4"
                >
                    <!-- Review Card Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-pink-50 pb-4">
                        <div class="flex items-center gap-3.5">
                            <img
                                :src="storageUrl(item.artist_profile?.cover_image || item.artist_profile?.user?.avatar, 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=150')"
                                :alt="item.artist_profile?.business_name"
                                class="h-12 w-12 rounded-2xl object-cover border-2 border-pink-200 shadow-xs"
                            />
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                        {{ item.artist_profile?.business_name || 'Beauty Salon' }}
                                    </h3>
                                    <span v-if="item.artist_profile?.city" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-50 text-glam-800 border border-pink-200">
                                        📍 {{ item.artist_profile.city.name }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-400 pt-0.5">
                                    <span v-if="item.booking?.service?.name" class="text-pink-700 font-semibold">
                                        💄 {{ item.booking.service.name }}
                                    </span>
                                    <span>&bull;</span>
                                    <span>{{ formatDate(item.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Badge & Verified Pill -->
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1 px-3 py-1 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-xs font-black">
                                <span>{{ '★'.repeat(item.rating) }}</span>
                                <span class="text-slate-300 font-normal">({{ item.rating }}.0)</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ✓ Verified Booking
                            </span>
                        </div>
                    </div>

                    <!-- Review Text -->
                    <div class="relative pl-3 border-l-3 border-pink-300 text-xs sm:text-sm text-slate-700 leading-relaxed italic">
                        "{{ item.review }}"
                    </div>

                    <!-- Review Photos Gallery -->
                    <div v-if="item.images && item.images.length > 0" class="pt-2">
                        <div class="flex flex-wrap gap-2.5">
                            <img
                                v-for="img in item.images"
                                :key="img.id"
                                :src="storageUrl(img.image_path)"
                                alt="Makeover photo"
                                @click="activeLightboxImage = storageUrl(img.image_path)"
                                class="h-20 w-20 rounded-2xl object-cover border-2 border-pink-200 shadow-xs cursor-pointer hover:scale-105 transition-transform"
                            />
                        </div>
                    </div>

                    <!-- Review Actions Footer -->
                    <div class="pt-3 border-t border-pink-50 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400 font-medium">
                            Booking Ref #{{ item.booking?.booking_number || item.booking_id }}
                        </span>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openEditModal(item)"
                                class="px-3 py-1.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-glam-800 font-bold transition cursor-pointer flex items-center gap-1"
                            >
                                <span>✏️</span>
                                <span>Edit</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteReview(item.id)"
                                class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold transition cursor-pointer flex items-center gap-1 border border-rose-200/60"
                            >
                                <span>🗑️</span>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="reviews.links && reviews.links.length > 3" class="pt-8 flex justify-center">
                    <div class="flex items-center gap-1.5 bg-white p-2 rounded-2xl border border-pink-100 shadow-xs">
                        <template v-for="(link, idx) in reviews.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition"
                                :class="link.active 
                                    ? 'bg-gradient-to-r from-glam-600 to-rose-600 text-white shadow-xs' 
                                    : 'text-slate-600 hover:bg-pink-50'"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- 6. EMPTY STATE -->
            <div v-else class="rounded-3xl bg-white border border-pink-100/90 shadow-sm p-12 text-center max-w-2xl mx-auto space-y-5">
                <div class="h-20 w-20 mx-auto rounded-3xl bg-gradient-to-tr from-pink-100 via-rose-100 to-pink-50 border border-pink-200/80 flex items-center justify-center text-3xl shadow-inner">
                    <span>⭐</span>
                </div>
                <div class="space-y-1.5">
                    <h3 class="text-lg font-bold text-slate-900">No Reviews Written Yet</h3>
                    <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
                        {{ searchQuery || selectedRating 
                            ? 'No reviews match your selected filter criteria.' 
                            : 'After you complete your appointments with salons and makeup artists, your reviews and makeover photo memories will appear here.' }}
                    </p>
                </div>
                <div class="pt-2">
                    <Link
                        :href="route('customer.bookings.index')"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white text-xs sm:text-sm font-bold shadow-md shadow-pink-900/20 hover:scale-[1.02] transition cursor-pointer"
                    >
                        <span>🗓️</span>
                        <span>View Completed Appointments</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- 7. WRITE / EDIT REVIEW MODAL                                         -->
        <!-- ==================================================================== -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="max-w-xl w-full bg-white rounded-3xl overflow-hidden shadow-2xl border border-pink-200 flex flex-col">
                        <!-- Modal Header -->
                        <div class="p-5 sm:p-6 bg-gradient-to-r from-[#1b0917] via-[#2d0d26] to-[#150612] text-white flex items-center justify-between border-b border-pink-900/50">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-2xl bg-pink-500/20 border border-pink-500/30 flex items-center justify-center text-lg text-pink-300">
                                    ⭐
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">
                                        {{ modalMode === 'create' ? 'Rate Your Beauty Appointment' : 'Edit Your Review' }}
                                    </h3>
                                    <p class="text-xs text-pink-300/80">
                                        {{ selectedBooking?.artist?.business_name || 'Salon Feedback' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeModal"
                                class="h-8 w-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm border border-white/10 transition cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Modal Form -->
                        <form @submit.prevent="submitReview" class="p-5 sm:p-6 space-y-4">
                            <!-- Star Rating Selector -->
                            <div class="text-center p-4 rounded-2xl bg-pink-50/50 border border-pink-100 space-y-2">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide block">How was your overall experience?</span>
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        @click="reviewForm.rating = star"
                                        @mouseenter="hoverRating = star"
                                        @mouseleave="hoverRating = 0"
                                        class="text-3xl transition-transform hover:scale-125 cursor-pointer p-1"
                                        :class="(hoverRating || reviewForm.rating) >= star ? 'text-amber-400 drop-shadow-xs' : 'text-slate-300'"
                                    >
                                        ★
                                    </button>
                                </div>
                                <p class="text-xs font-bold text-glam-800">
                                    {{ ['Terrible', 'Bad', 'Average', 'Very Good', 'Exceptional & Flawless!'][reviewForm.rating - 1] }}
                                </p>
                            </div>

                            <!-- Review Textarea -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    Your Experience & Praise *
                                </label>
                                <textarea
                                    v-model="reviewForm.review"
                                    rows="4"
                                    required
                                    placeholder="Describe the makeup finish, hairstyling quality, punctuality, salon ambiance, and staff hospitality..."
                                    class="w-full rounded-2xl border border-pink-200 bg-pink-50/30 p-3.5 text-xs sm:text-sm focus:bg-white focus:border-glam-500 focus:ring-2 focus:ring-pink-200 transition"
                                ></textarea>
                                <p v-if="reviewForm.errors.review" class="mt-1 text-xs text-rose-600">{{ reviewForm.errors.review }}</p>
                            </div>

                            <!-- Photo Upload (Create mode) -->
                            <div v-if="modalMode === 'create'">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                                    Attach Makeover Photos (Optional)
                                </label>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    @change="handleImageUpload"
                                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pink-100 file:text-glam-800 hover:file:bg-pink-200 cursor-pointer"
                                />
                            </div>

                            <!-- Actions -->
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition cursor-pointer"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="reviewForm.processing || !reviewForm.review.trim()"
                                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-glam-600 to-rose-700 hover:from-glam-700 hover:to-rose-800 text-white text-xs sm:text-sm font-bold shadow-md shadow-pink-900/20 transition disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
                                >
                                    <span>{{ reviewForm.processing ? 'Submitting...' : (modalMode === 'create' ? 'Post Review' : 'Save Changes') }}</span>
                                    <span>✓</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ==================================================================== -->
        <!-- 8. FULL-HD LIGHTBOX MODAL                                            -->
        <!-- ==================================================================== -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="activeLightboxImage"
                    @click="activeLightboxImage = null"
                    class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 cursor-zoom-out"
                >
                    <img
                        :src="activeLightboxImage"
                        class="max-w-4xl max-h-[85vh] w-auto h-auto rounded-3xl object-contain shadow-2xl border border-white/20"
                    />
                </div>
            </Transition>
        </Teleport>
    </CustomerLayout>
</template>
