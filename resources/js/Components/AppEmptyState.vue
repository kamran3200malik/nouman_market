<script setup>
defineProps({
    icon: {
        type: String,
        default: '📭'
    },
    title: {
        type: String,
        default: 'No data found'
    },
    description: {
        type: String,
        default: 'There is nothing to display here.'
    },
    actionText: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['action']);
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <div class="text-6xl mb-4">{{ icon }}</div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ title }}</h3>
        <p class="text-gray-500 mb-6 max-w-sm">{{ description }}</p>
        <button
            v-if="actionText"
            @click="emit('action')"
            class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors"
        >
            {{ actionText }}
        </button>
    </div>
</template>
