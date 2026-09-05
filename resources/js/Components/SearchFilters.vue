<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    cities: {
        type: Array,
        default: () => []
    },
    initialFilters: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['filter']);

const filters = ref({
    category: props.initialFilters.category || '',
    city: props.initialFilters.city || '',
    area: props.initialFilters.area || '',
    professional_type: props.initialFilters.professional_type || '',
    min_price: props.initialFilters.min_price || '',
    max_price: props.initialFilters.max_price || '',
    min_rating: props.initialFilters.min_rating || props.initialFilters.rating || '',
    min_experience: props.initialFilters.min_experience || '',
    verified_only: props.initialFilters.verified_only === 'true' || props.initialFilters.verified_only === true,
    home_service: props.initialFilters.home_service === 'true' || props.initialFilters.home_service === true,
    featured: props.initialFilters.featured === 'true' || props.initialFilters.featured === true,
});

const professionalTypes = [
    { value: '', label: 'All Specializations', icon: '✨' },
    { value: 'makeup_artist', label: 'Makeup Artist', icon: '💄' },
    { value: 'bridal_makeup_artist', label: 'Bridal Specialist', icon: '👰' },
    { value: 'hair_stylist', label: 'Hair Stylist & Colorist', icon: '💇' },
    { value: 'nail_artist', label: 'Nail Technician', icon: '💅' },
    { value: 'facial_specialist', label: 'Facial & Skincare', icon: '💆' },
    { value: 'mehndi_artist', label: 'Mehndi & Henna', icon: '🌿' },
    { value: 'lash_artist', label: 'Lash & Brow Pro', icon: '👁️' },
    { value: 'beauty_salon', label: 'Luxury Beauty Salon', icon: '👑' },
    { value: 'spa', label: 'Aesthetic Spa & Lounge', icon: '🧖' }
];

const budgetPresets = [
    { label: 'Any', min: '', max: '' },
    { label: '< 3K', min: '', max: '3000' },
    { label: '3K - 8K', min: '3000', max: '8000' },
    { label: '8K - 20K', min: '8000', max: '20000' },
    { label: '20K+', min: '20000', max: '' },
];

const ratingOptions = [
    { value: '', label: 'Any Rating' },
    { value: '4.8', label: '4.8+ ★ Top Star' },
    { value: '4.5', label: '4.5+ ★ Highly Rated' },
    { value: '4.0', label: '4.0+ ★ Recommended' }
];

const experienceOptions = [
    { value: '', label: 'Any Experience' },
    { value: '2', label: '2+ Years' },
    { value: '5', label: '5+ Years' },
    { value: '10', label: '10+ Years Master' }
];

const applyBudgetPreset = (preset) => {
    filters.value.min_price = preset.min;
    filters.value.max_price = preset.max;
};

const isPresetActive = (preset) => {
    return (filters.value.min_price || '') === preset.min && (filters.value.max_price || '') === preset.max;
};

watch(filters, (newFilters) => {
    emit('filter', newFilters);
}, { deep: true });

function resetFilters() {
    filters.value = {
        category: '',
        city: '',
        area: '',
        professional_type: '',
        min_price: '',
        max_price: '',
        min_rating: '',
        min_experience: '',
        verified_only: false,
        home_service: false,
        featured: false,
    };
}
</script>

<template>
    <div class="rounded-3xl bg-white p-5 lg:p-6 shadow-sm border border-rose-100/90 space-y-6 font-sans">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-rose-100/70 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="h-8 w-8 rounded-xl bg-gradient-to-tr from-glam-600 to-pink-500 text-white flex items-center justify-center text-sm shadow-xs">
                    <span>🎛️</span>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Refine Directory</h3>
                    <p class="text-[11px] text-slate-500">Filter verified artists</p>
                </div>
            </div>
            <button
                type="button"
                @click="resetFilters"
                class="text-xs font-bold text-rose-600 hover:text-rose-800 transition cursor-pointer px-2.5 py-1 rounded-lg hover:bg-rose-50"
            >
                Reset All
            </button>
        </div>

        <div class="space-y-5">
            <!-- 1. Treatment Category -->
            <div>
                <label class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    <span>Treatment Category</span>
                    <span v-if="filters.category" class="text-[10px] text-glam-700 font-bold lowercase">active</span>
                </label>
                <div class="relative">
                    <select
                        v-model="filters.category"
                        class="w-full rounded-2xl border-rose-200 bg-rose-50/30 pl-3.5 pr-8 py-2.5 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500 appearance-none cursor-pointer"
                    >
                        <option value="">✨ All Service Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs">▼</span>
                </div>
            </div>

            <!-- 2. City / Region -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">City / Region</label>
                <div class="relative">
                    <select
                        v-model="filters.city"
                        class="w-full rounded-2xl border-rose-200 bg-rose-50/30 pl-3.5 pr-8 py-2.5 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500 appearance-none cursor-pointer"
                    >
                        <option value="">📍 All Pakistan Cities</option>
                        <option v-for="city in cities" :key="city.id" :value="city.name">
                            {{ city.name }}
                        </option>
                    </select>
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs">▼</span>
                </div>
            </div>

            <!-- 3. Neighborhood / Area -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Area / Neighborhood</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                    <input
                        v-model="filters.area"
                        type="text"
                        placeholder="e.g. DHA, Gulberg, Clifton, F-7"
                        class="w-full rounded-2xl border-rose-200 bg-rose-50/30 pl-8 pr-3.5 py-2.5 text-xs font-semibold text-slate-800 placeholder-slate-400 shadow-xs focus:border-glam-500 focus:ring-glam-500"
                    />
                </div>
            </div>

            <!-- 4. Specialization Type -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Specialization</label>
                <div class="relative">
                    <select
                        v-model="filters.professional_type"
                        class="w-full rounded-2xl border-rose-200 bg-rose-50/30 pl-3.5 pr-8 py-2.5 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500 appearance-none cursor-pointer"
                    >
                        <option v-for="type in professionalTypes" :key="type.value" :value="type.value">
                            {{ type.icon }} {{ type.label }}
                        </option>
                    </select>
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs">▼</span>
                </div>
            </div>

            <!-- 5. Budget & Pricing (Quick Presets + Range) -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Budget Range (PKR)</label>
                
                <!-- Quick Preset Pills -->
                <div class="grid grid-cols-5 gap-1 pt-0.5">
                    <button
                        v-for="preset in budgetPresets"
                        :key="preset.label"
                        type="button"
                        @click="applyBudgetPreset(preset)"
                        class="py-1 px-1 rounded-xl text-[10px] font-bold transition text-center border cursor-pointer truncate"
                        :class="isPresetActive(preset)
                            ? 'bg-glam-600 text-white border-glam-600 shadow-xs'
                            : 'bg-rose-50/50 text-slate-600 border-rose-100 hover:bg-rose-100/60'"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <div>
                        <span class="text-[10px] text-slate-400 font-semibold mb-0.5 block">Min Price</span>
                        <input
                            v-model="filters.min_price"
                            type="number"
                            placeholder="PKR 0"
                            class="w-full rounded-2xl border-rose-200 bg-rose-50/30 px-3 py-2 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500"
                        />
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-semibold mb-0.5 block">Max Price</span>
                        <input
                            v-model="filters.max_price"
                            type="number"
                            placeholder="PKR ∞"
                            class="w-full rounded-2xl border-rose-200 bg-rose-50/30 px-3 py-2 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500"
                        />
                    </div>
                </div>
            </div>

            <!-- 6. Client Rating & Experience -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Min Rating</label>
                    <div class="relative">
                        <select
                            v-model="filters.min_rating"
                            class="w-full rounded-2xl border-rose-200 bg-rose-50/30 pl-3.5 pr-8 py-2.5 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500 appearance-none cursor-pointer"
                        >
                            <option v-for="option in ratingOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs">▼</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Experience</label>
                    <div class="relative">
                        <select
                            v-model="filters.min_experience"
                            class="w-full rounded-2xl border-rose-200 bg-rose-50/30 pl-3.5 pr-8 py-2.5 text-xs font-semibold text-slate-800 shadow-xs focus:border-glam-500 focus:ring-glam-500 appearance-none cursor-pointer"
                        >
                            <option v-for="exp in experienceOptions" :key="exp.value" :value="exp.value">
                                {{ exp.label }}
                            </option>
                        </select>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs">▼</span>
                    </div>
                </div>
            </div>

            <!-- 7. Premium Perks & Feature Toggles -->
            <div class="space-y-2.5 border-t border-rose-100/80 pt-4">
                <label class="flex items-center justify-between p-2.5 rounded-2xl bg-rose-50/40 hover:bg-rose-50 border border-rose-100/60 cursor-pointer transition select-none">
                    <div class="flex items-center gap-2.5">
                        <span class="h-6 w-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">✓</span>
                        <span class="text-xs font-semibold text-slate-800">Verified Studios Only</span>
                    </div>
                    <input
                        v-model="filters.verified_only"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-rose-300 text-glam-600 focus:ring-glam-500"
                    />
                </label>

                <label class="flex items-center justify-between p-2.5 rounded-2xl bg-rose-50/40 hover:bg-rose-50 border border-rose-100/60 cursor-pointer transition select-none">
                    <div class="flex items-center gap-2.5">
                        <span class="h-6 w-6 rounded-lg bg-pink-100 text-glam-600 flex items-center justify-center text-xs font-bold">🏡</span>
                        <span class="text-xs font-semibold text-slate-800">Home Service Available</span>
                    </div>
                    <input
                        v-model="filters.home_service"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-rose-300 text-glam-600 focus:ring-glam-500"
                    />
                </label>

                <label class="flex items-center justify-between p-2.5 rounded-2xl bg-rose-50/40 hover:bg-rose-50 border border-rose-100/60 cursor-pointer transition select-none">
                    <div class="flex items-center gap-2.5">
                        <span class="h-6 w-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">👑</span>
                        <span class="text-xs font-semibold text-slate-800">Featured Luxe Salons</span>
                    </div>
                    <input
                        v-model="filters.featured"
                        type="checkbox"
                        class="h-4 w-4 rounded-md border-rose-300 text-glam-600 focus:ring-glam-500"
                    />
                </label>
            </div>
        </div>
    </div>
</template>
