<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';

const props = defineProps({
    reviews: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
});

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="My Product Reviews - Luxe Beauty Market" />

        <div class="space-y-6 max-w-5xl mx-auto pb-12">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-serif font-black text-slate-900">My Product Reviews</h1>
                    <p class="text-xs text-slate-500">Your ratings and reviews submitted for authentic beauty items.</p>
                </div>
                <Link
                    :href="route('customer.orders.index')"
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold uppercase hover:bg-rose-600 transition"
                >
                    View My Orders
                </Link>
            </div>

            <!-- Reviews List -->
            <div v-if="reviews.data && reviews.data.length" class="space-y-4">
                <div
                    v-for="rev in reviews.data"
                    :key="rev.id"
                    class="p-5 rounded-3xl bg-white border border-pink-100 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
                >
                    <div class="flex items-start gap-4 min-w-0">
                        <div v-if="rev.product" class="h-16 w-16 rounded-2xl overflow-hidden bg-slate-50 border border-slate-100 shrink-0">
                            <img :src="rev.product.image_url" :alt="rev.product.name" class="h-full w-full object-cover" />
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-rose-600 uppercase">{{ rev.product?.brand || 'Luxe' }}</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[9px] font-bold"
                                    :class="rev.is_approved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ rev.is_approved ? '✓ Published' : '⏳ Moderation Pending' }}
                                </span>
                            </div>
                            <Link v-if="rev.product" :href="route('products.show', rev.product.slug)">
                                <h3 class="font-bold text-slate-900 text-xs sm:text-sm hover:text-rose-600 transition truncate">
                                    {{ rev.product.name }}
                                </h3>
                            </Link>

                            <div class="flex items-center gap-1 text-amber-400 text-xs">
                                <span v-for="s in (rev.rating || 5)" :key="s">★</span>
                            </div>

                            <p v-if="rev.title" class="text-xs font-bold text-slate-800">"{{ rev.title }}"</p>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ rev.comment }}</p>
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="text-[11px] text-slate-400 block">{{ formatDate(rev.created_at) }}</span>
                        <Link
                            v-if="rev.product"
                            :href="route('products.show', rev.product.slug)"
                            class="inline-block mt-2 text-xs font-bold text-rose-600 hover:underline"
                        >
                            View Product &rarr;
                        </Link>
                    </div>
                </div>

                <div v-if="reviews.links && reviews.links.length > 3" class="pt-4">
                    <AppPagination :links="reviews.links" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-3xl border border-pink-100 p-8 space-y-4">
                <span class="text-4xl">💬</span>
                <h3 class="font-bold text-slate-900 text-sm">No reviews submitted yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Once you purchase cosmetics and receive delivery, you can leave helpful reviews for other buyers.</p>
                <Link
                    :href="route('products.index')"
                    class="inline-block px-6 py-2.5 rounded-full bg-rose-600 text-white font-bold text-xs uppercase shadow-md hover:bg-rose-700 transition"
                >
                    Explore Catalog
                </Link>
            </div>
        </div>
    </CustomerLayout>
</template>
