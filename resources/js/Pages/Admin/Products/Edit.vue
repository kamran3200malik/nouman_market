<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppCard from '@/Components/AppCard.vue';
import FileUpload from '@/Components/FileUpload.vue';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const form = ref({
    name: props.product.name,
    slug: props.product.slug,
    description: props.product.description || '',
    image: null,
    price: props.product.price || '',
    is_trending: props.product.is_trending,
    is_active: props.product.is_active,
    sort_order: props.product.sort_order || 0
});

function generateSlug() {
    form.value.slug = form.value.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

function submit() {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('slug', form.value.slug);
    formData.append('description', form.value.description);
    formData.append('price', form.value.price);
    formData.append('is_trending', form.value.is_trending ? '1' : '0');
    formData.append('is_active', form.value.is_active ? '1' : '0');
    formData.append('sort_order', form.value.sort_order);
    
    if (form.value.image) {
        formData.append('image', form.value.image);
    }

    router.post(route('admin.products.update', props.product.id), formData, {
        method: 'put',
        onSuccess: () => {
            router.visit(route('admin.products.index'));
        }
    });
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Edit Product</h1>
                <Link
                    :href="route('admin.products.index')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                    Back to Products
                </Link>
            </div>
        </template>

        <div class="max-w-2xl">
            <AppCard>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            @input="generateSlug"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            placeholder="e.g., Bridal Makeup Kit"
                            required
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            placeholder="e.g., bridal-makeup-kit"
                            required
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            placeholder="Describe this product..."
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Image</label>
                        <div v-if="product.image" class="mb-2">
                            <img 
                                :src="storageUrl(product.image)" 
                                :alt="product.name"
                                class="h-32 w-32 rounded-lg object-cover"
                            />
                        </div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Image</label>
                        <FileUpload
                            v-model="form.image"
                            accept="image/*"
                            :max-size="2048"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price (PKR)</label>
                        <input
                            v-model.number="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            placeholder="e.g., 5000"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            placeholder="e.g., 0"
                        />
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.is_trending"
                                type="checkbox"
                                id="is_trending"
                                class="rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                            />
                            <label for="is_trending" class="text-sm text-gray-700">Show in trending marquee</label>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                id="is_active"
                                class="rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                            />
                            <label for="is_active" class="text-sm text-gray-700">Active</label>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-4 border-t">
                        <Link
                            :href="route('admin.products.index')"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-center"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700"
                        >
                            Update Product
                        </button>
                    </div>
                </form>
            </AppCard>
        </div>
    </AdminLayout>
</template>