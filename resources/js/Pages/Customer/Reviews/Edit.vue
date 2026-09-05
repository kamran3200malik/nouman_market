<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    review: {
        type: Object,
        required: true
    }
});

const hoverRating = ref(0);

const form = useForm({
    rating: props.review.rating,
    review: props.review.review,
});

const submit = () => {
    form.put(route('customer.reviews.update', props.review.id));
};
</script>

<template>
    <Head title="Edit Review - VIP Salon Experience" />

    <CustomerLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('customer.reviews.index')"
                    class="h-10 w-10 rounded-2xl bg-white border border-pink-200 text-glam-800 flex items-center justify-center font-bold text-sm shadow-xs hover:bg-pink-50 transition"
                >
                    &larr;
                </Link>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Edit Your Review</h1>
                    <p class="text-xs text-slate-500">{{ review.artist_profile?.business_name }}</p>
                </div>
            </div>

            <!-- Review Card -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-pink-100 shadow-sm space-y-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Rating Star Picker -->
                    <div class="text-center p-6 rounded-2xl bg-pink-50/40 border border-pink-100 space-y-2">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wide block">Your Rating *</span>
                        <div class="flex items-center justify-center gap-3">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="form.rating = star"
                                @mouseenter="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                                class="text-4xl transition-transform hover:scale-125 cursor-pointer p-1"
                                :class="(hoverRating || form.rating) >= star ? 'text-amber-400 drop-shadow-xs' : 'text-slate-300'"
                            >
                                ★
                            </button>
                        </div>
                        <p class="text-xs font-bold text-glam-800">
                            {{ ['Terrible', 'Bad', 'Average', 'Very Good', 'Exceptional & Flawless!'][form.rating - 1] }}
                        </p>
                    </div>

                    <!-- Review Text -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">
                            Your Updated Feedback *
                        </label>
                        <textarea
                            v-model="form.review"
                            rows="5"
                            required
                            class="w-full rounded-2xl border border-pink-200 bg-pink-50/30 p-4 text-xs sm:text-sm focus:bg-white focus:border-glam-500 focus:ring-2 focus:ring-pink-200 transition"
                        ></textarea>
                        <p v-if="form.errors.review" class="mt-1 text-xs text-rose-600">{{ form.errors.review }}</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-pink-50 flex items-center justify-end gap-3">
                        <Link
                            :href="route('customer.reviews.index')"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.review.trim()"
                            class="px-8 py-3 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white text-xs sm:text-sm font-bold shadow-lg shadow-pink-950/20 transition disabled:opacity-50 cursor-pointer"
                        >
                            {{ form.processing ? 'Saving...' : 'Update Review ✓' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerLayout>
</template>
