<script setup>
import { Link } from '@inertiajs/vue3';
import { storageUrl } from '@/Utils/storage';

defineProps({
    blogs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <section v-if="blogs && blogs.length > 0" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-widest bg-rose-100 text-rose-700 border border-rose-200">
                    The Beauty Magazine
                </span>
                <h2 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight mt-2">
                    Latest Editorial, Guides & Trends
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Expert tips, bridal advice, and transformation routines from verified salons and master stylists.
                </p>
            </div>

            <Link
                :href="route('blogs.index')"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 transition self-start sm:self-auto group"
            >
                <span>View All Articles</span>
                <span class="group-hover:translate-x-1 transition-transform">→</span>
            </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Link
                v-for="blog in blogs"
                :key="blog.id"
                :href="route('blogs.show', blog.slug)"
                class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-pink-100 transition-all duration-300 flex flex-col justify-between"
            >
                <div class="relative h-52 w-full overflow-hidden bg-rose-50">
                    <img
                        v-if="blog.image_url"
                        :src="storageUrl(blog.image_url)"
                        :alt="blog.title"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                    <div class="absolute top-3 left-3">
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/95 backdrop-blur-md text-rose-700 shadow-sm border border-pink-100">
                            {{ blog.category || 'Beauty' }}
                        </span>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium mb-1.5 flex items-center gap-2">
                            <span class="text-rose-600 font-semibold">By {{ blog.author_name || 'BeautyBook' }}</span>
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

                    <div class="mt-5 pt-3 border-t border-pink-50 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ blog.formatted_date }}</span>
                        <span class="font-bold text-rose-600 group-hover:translate-x-1 transition-transform">Read Story →</span>
                    </div>
                </div>
            </Link>
        </div>
    </section>
</template>
