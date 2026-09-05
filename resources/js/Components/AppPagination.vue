<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => []
    },
    meta: {
        type: Object,
        default: null
    },
    from: {
        type: Number,
        default: null
    },
    to: {
        type: Number,
        default: null
    },
    total: {
        type: Number,
        default: null
    },
    showInfo: {
        type: Boolean,
        default: true
    }
});

const paginationLinks = computed(() => {
    if (props.links && props.links.length > 0) {
        return props.links;
    }
    if (props.meta && props.meta.links) {
        return props.meta.links;
    }
    return [];
});

const infoFrom = computed(() => {
    if (props.from !== null) return props.from;
    if (props.meta?.from) return props.meta.from;
    return null;
});

const infoTo = computed(() => {
    if (props.to !== null) return props.to;
    if (props.meta?.to) return props.meta.to;
    return null;
});

const infoTotal = computed(() => {
    if (props.total !== null) return props.total;
    if (props.meta?.total !== undefined) return props.meta.total;
    return null;
});

function formatLabel(label) {
    if (!label) return '';
    return label
        .replace('&laquo; Previous', '‹ Prev')
        .replace('Next &raquo;', 'Next ›')
        .replace('&laquo;', '«')
        .replace('&raquo;', '»');
}

function isActive(link) {
    return link.active;
}

function isDisabled(link) {
    return link.url === null;
}
</script>

<template>
    <div v-if="paginationLinks.length > 0 || (infoTotal !== null && infoTotal > 0)" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4 font-sans">
        <!-- Results Count Summary -->
        <div v-if="showInfo && infoTotal !== null" class="text-xs text-slate-500 font-medium">
            <span v-if="infoFrom && infoTo">
                Showing <strong class="text-slate-900 font-bold">{{ infoFrom }}</strong> to <strong class="text-slate-900 font-bold">{{ infoTo }}</strong> of <strong class="text-slate-900 font-bold">{{ infoTotal }}</strong> articles
            </span>
            <span v-else>
                Total <strong class="text-slate-900 font-bold">{{ infoTotal }}</strong> articles
            </span>
        </div>

        <!-- Page Navigation Buttons -->
        <nav v-if="paginationLinks.length > 1" class="flex items-center gap-1.5 sm:gap-2 flex-wrap justify-center ml-auto">
            <template v-for="(link, index) in paginationLinks" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'min-w-[38px] h-[38px] px-3.5 rounded-2xl text-xs font-bold transition-all duration-200 flex items-center justify-center cursor-pointer select-none',
                        isActive(link)
                            ? 'bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 text-white shadow-md shadow-rose-950/20 scale-105'
                            : 'bg-white text-slate-700 hover:bg-rose-50 hover:text-rose-700 border border-rose-100 hover:border-rose-300 shadow-2xs'
                    ]"
                >
                    <span v-html="formatLabel(link.label)"></span>
                </Link>
                <span
                    v-else-if="link.active || paginationLinks.length > 3"
                    class="min-w-[38px] h-[38px] px-3.5 rounded-2xl text-xs font-medium text-slate-300 bg-slate-50/70 border border-slate-100 flex items-center justify-center cursor-not-allowed select-none"
                >
                    <span v-html="formatLabel(link.label)"></span>
                </span>
            </template>
        </nav>
    </div>
</template>
