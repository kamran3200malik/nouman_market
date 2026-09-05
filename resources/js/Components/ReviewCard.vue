<script setup>
import RatingStars from './RatingStars.vue';

const props = defineProps({
    review: {
        type: Object,
        required: true
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <div class="w-full bg-white rounded-xl p-3 shadow-sm border border-gray-100 sm:p-4">
        <div class="flex items-start gap-3 sm:gap-4">
            <img
                :src="review.customer?.profile_image || '/images/default-avatar.png'"
                :alt="review.customer?.name"
                class="w-10 h-10 rounded-full object-cover sm:w-12 sm:h-12"
            />

            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 sm:text-base">{{ review.customer?.name }}</h4>
                        <p class="text-[10px] text-gray-500 sm:text-xs">{{ formatDate(review.created_at) }}</p>
                    </div>
                    <RatingStars :rating="review.rating" size="sm" />
                </div>

                <p class="text-xs text-gray-700 mb-3 sm:text-sm">{{ review.review }}</p>

                <div v-if="review.images && review.images.length > 0" class="flex gap-2">
                    <img
                        v-for="(image, index) in review.images.slice(0, 3)"
                        :key="index"
                        :src="image"
                        class="w-12 h-12 rounded-lg object-cover cursor-pointer hover:opacity-80 sm:w-16 sm:h-16"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
