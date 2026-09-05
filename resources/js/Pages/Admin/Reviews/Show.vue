<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    review: {
        type: Object,
        required: true,
    },
});

const lightboxImage = ref(null);

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff`);
};

const getImageUrl = (path) => {
    return storageUrl(path);
};

const approveReview = () => {
    Swal.fire({
        title: 'Approve & Publish Review?',
        text: 'This review will be published and made visible on the artist profile and marketplace.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, Approve',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.reviews.approve', props.review.id), {}, {
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
                }
            });
        }
    });
};

const rejectReview = () => {
    Swal.fire({
        title: 'Reject / Hide Review?',
        text: 'Provide a reason for rejection (e.g. policy violation, profanity):',
        input: 'textarea',
        inputPlaceholder: 'Reason for rejection...',
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
            router.post(route('admin.reviews.reject', props.review.id), {
                reason: result.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: 'Review rejected & unlisted',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

const deleteReview = () => {
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
            router.delete(route('admin.reviews.destroy', props.review.id));
        }
    });
};
</script>

<template>
    <Head :title="`Review #${review.id} Moderation Dossier | Admin Portal`" />

    <AdminLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Breadcrumbs & Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <Link
                        :href="route('admin.reviews.index')"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-glam-700 hover:underline mb-2"
                    >
                        <span>&larr;</span>
                        <span>Back to All Reviews</span>
                    </Link>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 flex items-center gap-2">
                        <span>Review Moderation Dossier #{{ review.id }}</span>
                        <span
                            :class="review.is_approved ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'"
                            class="px-3 py-0.5 rounded-full text-xs font-bold border"
                        >
                            {{ review.is_approved ? '✓ Approved' : '⏳ Pending Approval' }}
                        </span>
                    </h1>
                </div>

                <!-- Top Fast Actions -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="!review.is_approved"
                        type="button"
                        class="px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition"
                        @click="approveReview"
                    >
                        ✓ Approve Review
                    </button>
                    <button
                        v-if="review.is_approved"
                        type="button"
                        class="px-5 py-2.5 rounded-2xl bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-xs transition"
                        @click="rejectReview"
                    >
                        Reject / Unlist
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition"
                        @click="deleteReview"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <!-- Main Content Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Feedback Details & Photos -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Rating & Statement Card -->
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-5">
                        <!-- Score Bar -->
                        <div class="flex items-center justify-between border-b border-rose-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex text-amber-400 text-2xl">
                                    <span v-for="s in 5" :key="s">
                                        {{ s <= review.rating ? '★' : '☆' }}
                                    </span>
                                </div>
                                <span class="font-serif text-2xl font-extrabold text-slate-900">{{ review.rating }}.0</span>
                            </div>
                            <span class="text-xs text-slate-400">
                                Submitted on {{ formatDate(review.created_at) }}
                            </span>
                        </div>

                        <!-- Feedback Commentary -->
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Written Client Review</h3>
                            <div class="p-5 rounded-2xl bg-rose-50/40 border border-rose-100 text-slate-800 text-sm leading-relaxed whitespace-pre-line">
                                {{ review.review || 'The client gave a star rating without written feedback.' }}
                            </div>
                        </div>

                        <!-- Rejection Reason if any -->
                        <div v-if="review.rejection_reason" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                            <strong>Admin Rejection Note:</strong> {{ review.rejection_reason }}
                        </div>
                    </div>

                    <!-- Customer Photos Gallery -->
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Customer Makeover Verification Photos ({{ review.images?.length || 0 }})
                            </h3>
                            <span v-if="review.is_verified" class="text-emerald-700 text-xs font-semibold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                ✓ Verified Booking Review
                            </span>
                        </div>

                        <div v-if="review.images && review.images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <button
                                v-for="(img, idx) in review.images"
                                :key="idx"
                                type="button"
                                class="aspect-square rounded-2xl overflow-hidden border border-rose-200 hover:opacity-90 hover:scale-[1.02] transition shadow-xs"
                                @click="lightboxImage = getImageUrl(img.image_path)"
                            >
                                <img :src="getImageUrl(img.image_path)" :alt="'Makeover photo ' + (idx + 1)" class="w-full h-full object-cover" />
                            </button>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic py-4 text-center">
                            No photo attachments were uploaded by the client for this review.
                        </p>
                    </div>
                </div>

                <!-- Right 1 Col: Customer, Salon & Booking Dossier -->
                <div class="space-y-6">
                    <!-- Client Profile -->
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Client Profile</h3>
                        <div class="flex items-center gap-3">
                            <img
                                :src="getAvatar(review.customer?.avatar, review.customer?.name)"
                                :alt="review.customer?.name"
                                class="w-12 h-12 rounded-full object-cover ring-2 ring-rose-200"
                            />
                            <div>
                                <p class="font-bold text-slate-900 text-sm">{{ review.customer?.name || 'Verified Client' }}</p>
                                <p class="text-xs text-slate-500">{{ review.customer?.email || 'N/A' }}</p>
                                <p v-if="review.customer?.phone" class="text-xs text-slate-500">{{ review.customer?.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Salon Studio Profile -->
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Salon Studio</h3>
                            <Link
                                v-if="review.artist_profile?.id"
                                :href="route('admin.artists.show', review.artist_profile.id)"
                                class="text-xs font-bold text-glam-700 hover:underline"
                            >
                                Studio Admin &rarr;
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <img
                                :src="getAvatar(review.artist_profile?.profile_image, review.artist_profile?.business_name)"
                                :alt="review.artist_profile?.business_name"
                                class="w-12 h-12 rounded-full object-cover ring-2 ring-rose-200"
                            />
                            <div>
                                <p class="font-bold text-slate-900 text-sm">{{ review.artist_profile?.business_name || 'Salon' }}</p>
                                <p class="text-xs text-slate-500">📍 {{ review.artist_profile?.city?.name || review.artist_profile?.city || 'Pakistan' }}</p>
                                <p class="text-xs text-amber-600 font-semibold mt-0.5">
                                    ★ {{ Number(review.artist_profile?.rating_avg || 5.0).toFixed(1) }} Overall Rating
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Booking Card -->
                    <div v-if="review.booking" class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Appointment Record</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Booking Number</span>
                                <span class="font-mono font-bold text-slate-800">#{{ review.booking.booking_number }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Treatment</span>
                                <span class="font-semibold text-glam-700">{{ review.booking.service?.name || 'Service' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Appointment Date</span>
                                <span class="font-medium text-slate-700">{{ formatDate(review.booking.booking_date) }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-slate-500">Status</span>
                                <span class="font-bold uppercase text-emerald-700">{{ review.booking.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox Modal -->
        <div
            v-if="lightboxImage"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4"
            @click="lightboxImage = null"
        >
            <div class="relative max-w-4xl max-h-[90vh]">
                <button
                    type="button"
                    class="absolute -top-12 right-0 text-white hover:text-rose-400 text-2xl font-bold"
                    @click="lightboxImage = null"
                >
                    ✕
                </button>
                <img :src="lightboxImage" alt="Makeover verification photo" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl" />
            </div>
        </div>
    </AdminLayout>
</template>
