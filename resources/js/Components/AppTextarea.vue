<script setup>
const model = defineModel();

defineProps({
    label: String,
    placeholder: String,
    rows: {
        type: Number,
        default: 3
    },
    error: String
});
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        <textarea
            v-model="model"
            :rows="rows"
            :placeholder="placeholder"
            :class="[
                'w-full rounded-lg border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500',
                error ? 'border-red-300' : ''
            ]"
        />
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
