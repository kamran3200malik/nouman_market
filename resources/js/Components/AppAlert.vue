<script setup>
const props = defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    dismissible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['dismiss']);

const types = {
    success: 'bg-green-50 border-green-200 text-green-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800'
};

const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
};
</script>

<template>
    <div :class="[
        'border rounded-lg p-4 flex items-start gap-3',
        types[type]
    ]">
        <span class="text-lg font-bold">{{ icons[type] }}</span>
        <div class="flex-1">
            <slot />
        </div>
        <button 
            v-if="dismissible"
            @click="emit('dismiss')"
            class="text-current opacity-60 hover:opacity-100"
        >
            ✕
        </button>
    </div>
</template>
