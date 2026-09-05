<script setup>
const model = defineModel();

defineProps({
    label: String,
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    error: String
});
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        <select
            v-model="model"
            :class="[
                'w-full rounded-lg border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500',
                error ? 'border-red-300' : ''
            ]"
        >
            <option value="">{{ placeholder }}</option>
            <option 
                v-for="option in options" 
                :key="option.value" 
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
