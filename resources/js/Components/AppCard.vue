<script setup>
defineProps({
    padding: {
        type: String,
        default: 'p-6'
    },
    shadow: {
        type: String,
        default: 'shadow-md'
    },
    rounded: {
        type: String,
        default: 'rounded-xl'
    },
    hoverable: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div
        :class="[
            'w-full market-card',
            padding,
            shadow,
            rounded,
            hoverable ? 'market-card-hover' : ''
        ]"
    >
        <slot />
    </div>
</template>
