<script setup>
import PagesView from '@/Pages/Admin/Content/Pages.vue';

const props = defineProps({
    pages: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total_pages: 0,
            published_pages: 0,
            draft_pages: 0,
            seo_pages: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <PagesView v-bind="props" />
</template>
