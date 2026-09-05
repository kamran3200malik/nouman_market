<script setup>
const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    }
});

const sizes = {
    sm: 'w-4 h-4 border-2',
    md: 'w-8 h-8 border-3',
    lg: 'w-12 h-12 border-4'
};
</script>

<template>
    <div class="flex items-center justify-center">
        <div
            :class="[
                'rounded-full border-pink-200 border-t-pink-600 animate-spin',
                sizes[size]
            ]"
        />
    </div>
</template>
