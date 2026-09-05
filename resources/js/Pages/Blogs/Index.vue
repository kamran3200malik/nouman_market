<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    blogs: {
        type: Object,
        required: true,
    },
    featured: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');

const applyFilters = () => {
    router.get(
        route('blogs.index'),
        {
            search: search.value || undefined,
            category: categoryFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const filterByCategory = (cat) => {
    categoryFilter.value = categoryFilter.value === cat ? '' : cat;
    applyFilters();
};
</script>

<template>
    <PublicLayout>
        <Head title="Beauty Editorial, Guides & Salon Trends - BeautyBook" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <!-- Hero Header Section -->
            <div class="text-center max-w-3xl mx-auto space-y-4 pt-6">
                <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-widest bg-rose-100 text-rose-700 border border-rose-200">
                    The Beauty Magazine
                </span>
                <h1 class="text-3xl sm:text-5xl font-serif font-black text-slate-900 tracking-tight">
                    Stories, Trends & Expert Beauty Guides
                </h1>
                <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                    Explore curated tips from leading makeup artists, salon directors, skincare experts, and transformation artists.
                </p>

                <!-- Search Bar -->
                <div class="max-w-lg mx-auto mt-6 flex items-center bg-white rounded-2xl p-1.5 shadow-lg border border-pink-100 focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-200 transition-all">
                    <span class="pl-3.5 text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search beauty topics, skincare, hairstyles..."
                        class="w-full px-3 py-2 bg-transparent text-slate-800 text-sm border-none focus:outline-none focus:ring-0 placeholder-slate-400"
                    />
                    <button
                        @click="applyFilters"
                        class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer shrink-0"
                    >
                        Search
                    </button>
                </div>
            </div>

            <!-- Categories Pills Filter -->
            <div v-if="categories.length > 0" class="flex items-center justify-center gap-2 flex-wrap pt-2">
                <button
                    @click="filterByCategory('')"
                    class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                    :class="!categoryFilter ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'bg-white text-slate-600 hover:bg-rose-50 border border-pink-100'"
                >
                    All Topics
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat"
                    @click="filterByCategory(cat)"
                    class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer"
                    :class="categoryFilter === cat ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'bg-white text-slate-600 hover:bg-rose-50 border border-pink-100'"
                >
                    {{ cat }}
                </button>
            </div>

            <!-- Featured Spotlights Carousel/Grid -->
            <div v-if="featured.length > 0 && !search && !categoryFilter" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-serif font-bold text-slate-900 flex items-center gap-2">
                        <span class="text-rose-600">✦</span> Featured Spotlights
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        v-for="item in featured"
                        :key="item.id"
                        :href="route('blogs.show', item.slug)"
                        class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-pink-100 transition-all duration-300 flex flex-col justify-between"
                    >
                        <div class="relative h-56 w-full overflow-hidden bg-rose-50">
                            <img
                                v-if="item.image_url"
                                :src="storageUrl(item.image_url)"
                                :alt="item.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div class="absolute top-3 left-3 flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-600 text-white shadow-md">
                                    {{ item.category || 'Featured' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="text-[11px] text-slate-400 font-medium mb-1.5 flex items-center gap-2">
                                    <span>{{ item.author_name || 'BeautyBook Team' }}</span>
                                    <span>•</span>
                                    <span>{{ item.read_time }} min read</span>
                                </div>
                                <h3 class="text-lg font-serif font-bold text-slate-900 group-hover:text-rose-600 transition-colors line-clamp-2">
                                    {{ item.title }}
                                </h3>
                                <p class="text-xs text-slate-600 mt-2 line-clamp-2 leading-relaxed">
                                    {{ item.excerpt }}
                                </p>
                            </div>

                            <div class="mt-4 pt-4 border-t border-pink-50 flex items-center justify-between text-xs text-rose-600 font-bold group-hover:translate-x-1 transition-transform">
                                <span>Read Full Article</span>
                                <span>→</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Main Articles Feed -->
            <div class="space-y-6">
                <h2 class="text-xl font-serif font-bold text-slate-900">
                    {{ categoryFilter ? `Articles in "${categoryFilter}"` : 'Latest Articles & Guides' }}
                </h2>

                <div v-if="blogs.data && blogs.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link
                        v-for="blog in blogs.data"
                        :key="blog.id"
                        :href="route('blogs.show', blog.slug)"
                        class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-pink-100 transition-all duration-300 flex flex-col justify-between"
                    >
                        <div class="relative h-48 w-full overflow-hidden bg-rose-50">
                            <img
                                v-if="blog.image_url"
                                :src="storageUrl(blog.image_url)"
                                :alt="blog.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div class="absolute top-3 left-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/90 backdrop-blur-md text-rose-700 shadow-sm border border-pink-100">
                                    {{ blog.category || 'Beauty' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="text-[11px] text-slate-400 font-medium mb-1.5 flex items-center gap-2">
                                    <span>{{ blog.author_name || 'BeautyBook' }}</span>
                                    <span>•</span>
                                    <span>{{ blog.read_time }} min read</span>
                                </div>
                                <h3 class="text-base font-serif font-bold text-slate-900 group-hover:text-rose-600 transition-colors line-clamp-2">
                                    {{ blog.title }}
                                </h3>
                                <p class="text-xs text-slate-600 mt-2 line-clamp-2 leading-relaxed">
                                    {{ blog.excerpt }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-pink-50 flex items-center justify-between text-xs text-slate-500">
                                <span>{{ blog.formatted_date }}</span>
                                <span class="font-bold text-rose-600 group-hover:translate-x-1 transition-transform">Read →</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="bg-white rounded-3xl p-12 text-center border border-pink-100 shadow-sm">
                    <p class="text-slate-500 text-sm">No articles found matching your criteria.</p>
                </div>

                <!-- Pagination -->
                <div v-if="blogs && blogs.total > 0" class="pt-6 border-t border-pink-100">
                    <AppPagination
                        :links="blogs.links"
                        :from="blogs.from"
                        :to="blogs.to"
                        :total="blogs.total"
                        :show-info="true"
                    />
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
