<script setup>
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ArtistCard from '@/Components/ArtistCard.vue';

const props = defineProps({
    category: Object,
    artists: Object,
});
</script>

<template>
    <Head :title="category.name" />

    <PublicLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ category.name }}</h1>
                <p class="text-gray-600 mb-6">{{ category.description }}</p>

                <!-- Artists Grid -->
                <div v-if="artists.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <ArtistCard
                        v-for="artist in artists.data"
                        :key="artist.id"
                        :artist="artist"
                    />
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No artists found</h3>
                    <p class="mt-1 text-sm text-gray-500">There are no artists in this category yet.</p>
                    <div class="mt-6">
                        <Link :href="route('artists.index')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700">
                            Browse All Artists
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="artists.links.length > 3" class="mt-6">
                    <div class="flex justify-center">
                        <template v-for="(link, index) in artists.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-2 mx-1 rounded-md text-sm"
                                :class="link.active ? 'bg-pink-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-2 mx-1 rounded-md text-sm text-gray-400"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
