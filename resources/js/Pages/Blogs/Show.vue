<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { storageUrl } from '@/Utils/storage';
import { renderMarkdown } from '@/Utils/markdown';

const props = defineProps({
    blog: {
        type: Object,
        required: true,
    },
    relatedBlogs: {
        type: Array,
        default: () => [],
    },
});

const getGalleryByArea = (galleryList, areaName) => {
    if (!Array.isArray(galleryList)) return [];
    return galleryList.filter(item => item.area === areaName);
};
</script>

<template>
    <PublicLayout>
        <Head>
            <title>{{ blog.meta_title || blog.title }} - BeautyBook</title>
            <meta name="description" :content="blog.meta_description || blog.excerpt" />
            <meta name="keywords" :content="blog.meta_keywords || ''" />
        </Head>

        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pt-4 pb-16">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <Link :href="route('home')" class="hover:text-rose-600">Home</Link>
                <span>/</span>
                <Link :href="route('blogs.index')" class="hover:text-rose-600">Magazine</Link>
                <span>/</span>
                <span class="text-rose-600 font-semibold truncate">{{ blog.title }}</span>
            </div>

            <!-- Article Header -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">
                        {{ blog.category || 'Beauty' }}
                    </span>
                    <span class="text-xs text-slate-400">• {{ blog.read_time }} min read</span>
                    <span class="text-xs text-slate-400">• {{ blog.formatted_date }}</span>
                </div>

                <h1 class="text-3xl sm:text-5xl font-serif font-black text-slate-900 leading-tight">
                    {{ blog.title }}
                </h1>

                <!-- Author & Stats -->
                <div class="flex items-center justify-between py-4 border-y border-pink-100">
                    <div class="flex items-center gap-3">
                        <div class="h-11 w-11 rounded-full bg-gradient-to-tr from-rose-600 via-pink-600 to-amber-500 text-white font-bold flex items-center justify-center shadow-md">
                            {{ (blog.author_name || 'B').charAt(0) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900">{{ blog.author_name || 'BeautyBook Editorial' }}</div>
                            <div class="text-xs text-slate-500">Verified Beauty Contributor</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>{{ Number(blog.views_count || 0).toLocaleString() }} views</span>
                    </div>
                </div>
            </div>

            <!-- Primary Cover Image -->
            <div v-if="blog.image_url" class="rounded-3xl overflow-hidden shadow-xl border border-pink-100 bg-rose-50">
                <img :src="storageUrl(blog.image_url)" :alt="blog.title" class="w-full max-h-[460px] object-cover" />
            </div>

            <!-- Top Area Highlight Photos if attached -->
            <div v-if="getGalleryByArea(blog.gallery_images_data, 'top').length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="(img, idx) in getGalleryByArea(blog.gallery_images_data, 'top')" :key="idx" class="rounded-2xl overflow-hidden shadow-md border border-pink-100">
                    <img :src="storageUrl(img.url)" class="w-full h-56 object-cover" />
                    <p v-if="img.caption" class="p-2 text-xs text-slate-500 bg-white italic text-center">{{ img.caption }}</p>
                </div>
            </div>

            <!-- Excerpt Callout -->
            <div v-if="blog.excerpt" class="p-6 rounded-2xl bg-gradient-to-r from-rose-50 to-pink-50 border-l-4 border-rose-500 text-slate-700 italic text-base leading-relaxed">
                "{{ blog.excerpt }}"
            </div>

            <!-- Main Content Body -->
            <div class="prose prose-rose prose-lg max-w-none text-slate-700 leading-relaxed" v-html="renderMarkdown(blog.content)">
            </div>

            <!-- Middle Highlight Photos if attached -->
            <div v-if="getGalleryByArea(blog.gallery_images_data, 'middle').length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="(img, idx) in getGalleryByArea(blog.gallery_images_data, 'middle')" :key="idx" class="rounded-2xl overflow-hidden shadow-md border border-pink-100 bg-white">
                    <img :src="storageUrl(img.url)" class="w-full h-60 object-cover" />
                    <p v-if="img.caption" class="p-2 text-xs text-slate-600 bg-rose-50/50 italic text-center font-medium">{{ img.caption }}</p>
                </div>
            </div>

            <!-- Product / Kit Essentials Area if attached -->
            <div v-if="getGalleryByArea(blog.gallery_images_data, 'product_spotlight').length > 0" class="p-6 rounded-3xl bg-gradient-to-br from-rose-50/70 to-pink-50/70 border border-pink-100 space-y-4">
                <h3 class="text-xl font-serif font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-rose-600">✦</span> Recommended Kit & Essentials
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="(img, idx) in getGalleryByArea(blog.gallery_images_data, 'product_spotlight')" :key="idx" class="rounded-2xl overflow-hidden border border-pink-200 bg-white shadow-sm">
                        <img :src="storageUrl(img.url)" class="w-full h-52 object-cover" />
                        <div class="p-3 text-center bg-white">
                            <span class="text-xs font-bold text-slate-800">{{ img.caption || 'Featured Product' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Before & After Transformation Area if attached -->
            <div v-if="getGalleryByArea(blog.gallery_images_data, 'before_after').length > 0" class="p-6 rounded-3xl bg-white shadow-lg border border-pink-100 space-y-4">
                <h3 class="text-xl font-serif font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-rose-600">✦</span> Transformation Results
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="(img, idx) in getGalleryByArea(blog.gallery_images_data, 'before_after')" :key="idx" class="rounded-2xl overflow-hidden border border-pink-100 bg-pink-50">
                        <img :src="storageUrl(img.url)" class="w-full h-64 object-cover" />
                        <div class="p-2.5 text-center bg-white">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">{{ img.caption || (idx === 0 ? 'Before Session' : 'After Transformation') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery Area if attached -->
            <div v-if="getGalleryByArea(blog.gallery_images_data, 'gallery').length > 0" class="space-y-4 pt-6 border-t border-pink-100">
                <h3 class="text-xl font-serif font-bold text-slate-900">Photo Gallery</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div v-for="(img, idx) in getGalleryByArea(blog.gallery_images_data, 'gallery')" :key="idx" class="rounded-2xl overflow-hidden shadow-sm border border-pink-100 bg-white">
                        <img :src="storageUrl(img.url)" class="w-full h-44 object-cover hover:scale-105 transition-transform" />
                        <p v-if="img.caption" class="p-2 text-xs text-slate-500 truncate">{{ img.caption }}</p>
                    </div>
                </div>
            </div>

            <!-- Bottom / Conclusion Feature if attached -->
            <div v-if="getGalleryByArea(blog.gallery_images_data, 'bottom').length > 0" class="space-y-3 pt-4">
                <div v-for="(img, idx) in getGalleryByArea(blog.gallery_images_data, 'bottom')" :key="idx" class="rounded-3xl overflow-hidden shadow-md border border-pink-100">
                    <img :src="storageUrl(img.url)" class="w-full max-h-80 object-cover" />
                    <p v-if="img.caption" class="p-2 text-xs text-slate-600 bg-white italic text-center">{{ img.caption }}</p>
                </div>
            </div>

            <!-- Tags -->
            <div v-if="blog.tags && blog.tags.length > 0" class="pt-6 border-t border-pink-100 flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tags:</span>
                <span
                    v-for="tag in blog.tags"
                    :key="tag"
                    class="px-3 py-1 rounded-full text-xs font-medium bg-white text-slate-600 border border-pink-100"
                >
                    #{{ tag }}
                </span>
            </div>

            <!-- Related Articles -->
            <div v-if="relatedBlogs.length > 0" class="pt-12 border-t border-pink-200 space-y-6">
                <h3 class="text-2xl font-serif font-bold text-slate-900">You Might Also Love</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <Link
                        v-for="item in relatedBlogs"
                        :key="item.id"
                        :href="route('blogs.show', item.slug)"
                        class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg border border-pink-100 transition-all flex flex-col justify-between"
                    >
                        <div class="h-36 w-full bg-rose-50 overflow-hidden">
                            <img v-if="item.image_url" :src="storageUrl(item.image_url)" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                        </div>
                        <div class="p-4">
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-rose-600 line-clamp-2">{{ item.title }}</h4>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ item.excerpt }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </article>
    </PublicLayout>
</template>
