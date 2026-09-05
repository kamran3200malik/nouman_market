<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
    class: {
        type: String,
        default: '',
    },
});

const classes = computed(() => {
    const baseClasses = props.class;
    if (props.active) {
        return `${baseClasses} bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-lg shadow-pink-500/20`.trim();
    }
    return baseClasses;
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
