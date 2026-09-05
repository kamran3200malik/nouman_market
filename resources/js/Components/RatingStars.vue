<script setup>
const props = defineProps({
    rating: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 5
    },
    readonly: {
        type: Boolean,
        default: true
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    showScore: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:rating']);

const sizes = {
    sm: 'w-3.5 h-3.5',
    md: 'w-4 h-4',
    lg: 'w-5 h-5'
};

function setRating(value) {
    if (!props.readonly) {
        emit('update:rating', value);
    }
}
</script>

<template>
    <div class="inline-flex items-center gap-1">
        <div class="flex items-center gap-0.5">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="setRating(star)"
                :disabled="readonly"
                :class="[
                    'transition-transform duration-150',
                    readonly ? 'cursor-default' : 'cursor-pointer hover:scale-125'
                ]"
            >
                <svg
                    :class="[
                        sizes[size],
                        star <= Math.round(rating)
                            ? 'text-champagne-500 fill-champagne-400 drop-shadow-xs'
                            : 'text-stone-300 fill-stone-200'
                    ]"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </button>
        </div>
        <span v-if="rating > 0 && showScore" class="ml-1.5 font-serif text-xs font-semibold tracking-wide text-slate-800">
            {{ Number(rating).toFixed(1) }}
        </span>
    </div>
</template>
