<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    favorites: {
        type: Object,
        required: true,
    },
});

const removeFromWishlist = (product) => {
    useForm({}).delete(route('customer.wishlist.destroy', product.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="My Wishlist - Luxe Beauty Market" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-serif font-black text-slate-900">My Saved Wishlist</h1>
                    <p class="text-xs text-slate-500">Products you've saved for later.</p>
                </div>
                <Link
                    :href="route('products.index')"
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold uppercase hover:bg-rose-600 transition"
                >
                    Continue Shopping
                </Link>
            </div>

            <div v-if="favorites.data && favorites.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="fav in favorites.data"
                    :key="fav.id"
                    class="p-4 rounded-3xl bg-white border border-pink-100 shadow-xs flex flex-col justify-between"
                >
                    <div v-if="fav.product">
                        <Link :href="route('products.show', fav.product.slug)" class="aspect-square rounded-2xl overflow-hidden bg-slate-50 mb-3 block">
                            <img :src="fav.product.image_url" :alt="fav.product.name" class="h-full w-full object-cover hover:scale-105 transition" />
                        </Link>
                        <span class="text-[10px] font-bold text-rose-600 uppercase">{{ fav.product.brand }}</span>
                        <Link :href="route('products.show', fav.product.slug)">
                            <h3 class="text-xs font-bold text-slate-900 line-clamp-2 hover:text-rose-600">{{ fav.product.name }}</h3>
                        </Link>
                        <p class="font-black text-xs text-slate-900 mt-2">PKR {{ Number(fav.product.price).toLocaleString() }}</p>
                    </div>

                    <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                        <Link
                            v-if="fav.product"
                            :href="route('products.show', fav.product.slug)"
                            class="text-xs font-bold text-rose-600 hover:underline"
                        >
                            View Product &rarr;
                        </Link>
                        <button
                            @click="removeFromWishlist(fav.product)"
                            class="text-xs text-slate-400 hover:text-rose-600 font-bold"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-16 bg-white rounded-3xl border border-pink-100 p-8 space-y-4">
                <span class="text-4xl">❤️</span>
                <h3 class="font-bold text-slate-900 text-sm">Your wishlist is empty</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Explore our collection of authentic beauty products and click the heart icon to save your favorites.</p>
                <Link
                    :href="route('products.index')"
                    class="inline-block px-6 py-2.5 rounded-full bg-rose-600 text-white font-bold text-xs uppercase shadow-md hover:bg-rose-700 transition"
                >
                    Explore Products
                </Link>
            </div>
        </div>
    </CustomerLayout>
</template>
