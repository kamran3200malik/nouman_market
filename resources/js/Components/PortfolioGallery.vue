<script setup>
import { ref } from 'vue';

const props = defineProps({
    images: {
        type: Array,
        default: () => []
    },
    columns: {
        type: Number,
        default: 3
    }
});

const selectedImage = ref(null);

const openLightbox = (image) => {
    selectedImage.value = image;
};

const closeLightbox = () => {
    selectedImage.value = null;
};

const gridCols = {
    2: 'grid-cols-2',
    3: 'grid-cols-3',
    4: 'grid-cols-4'
};
</script>

<template>
    <div v-if="images.length > 0">
        <div :class="['grid gap-4', gridCols[columns] || 'grid-cols-3']">
            <div
                v-for="(image, index) in images"
                :key="index"
                @click="openLightbox(image)"
                class="relative aspect-square overflow-hidden rounded-xl cursor-pointer group"
            >
                <img
                    :src="image"
                    :alt="`Portfolio image ${index + 1}`"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <div
            v-if="selectedImage"
            @click="closeLightbox"
            class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4"
        >
            <img
                :src="selectedImage"
                alt="Enlarged portfolio image"
                class="max-w-full max-h-full object-contain rounded-lg"
                @click.stop
            />
            <button
                @click="closeLightbox"
                class="absolute top-4 right-4 text-white hover:text-gray-300"
            >
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    
    <div v-else class="text-center py-8 text-gray-500">
        <p>No portfolio images yet</p>
    </div>
</template>
