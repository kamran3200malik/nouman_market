<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    conversations: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({ total_threads: 0, total_messages: 0 })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const searchQuery = ref(props.filters.search || '');

let timeout = null;
watch(searchQuery, (val) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.messages.index'), { search: val }, {
            preserveState: true,
            replace: true
        });
    }, 400);
});

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};
</script>

<template>
    <Head title="Platform Communications & Chat Oversight" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                        <span>💬</span>
                        <span>Communications & Inquiries Oversight</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Monitor live conversations, client questions, and dispute records across all salons on the marketplace.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-pink-50 text-glam-800 border border-pink-200 shadow-xs">
                        {{ stats.total_threads }} Total Conversations ({{ stats.total_messages }} Messages)
                    </span>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="relative max-w-md">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search conversations by client, salon or message..."
                        class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-glam-500/20"
                    />
                    <span class="absolute left-3.5 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>
            </div>

            <!-- Conversations Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                            <tr>
                                <th class="p-4">Customer</th>
                                <th class="p-4">Salon / Artist</th>
                                <th class="p-4">Linked Service / Booking</th>
                                <th class="p-4">Latest Message</th>
                                <th class="p-4">Total Messages</th>
                                <th class="p-4">Last Activity</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr
                                v-for="conv in conversations.data"
                                :key="conv.id"
                                class="hover:bg-pink-50/20 transition"
                            >
                                <td class="p-4 font-bold text-slate-900">
                                    {{ conv.customer?.name || 'Customer' }}
                                </td>
                                <td class="p-4 font-bold text-glam-800">
                                    {{ conv.artist_profile?.business_name || conv.artist_profile?.user?.name || 'Salon' }}
                                </td>
                                <td class="p-4">
                                    <span v-if="conv.booking" class="px-2 py-0.5 rounded-lg bg-pink-50 text-pink-700 font-bold border border-pink-100">
                                        {{ conv.booking?.service?.name || `#${conv.booking?.booking_number}` }}
                                    </span>
                                    <span v-else class="text-slate-400">Direct Chat</span>
                                </td>
                                <td class="p-4 max-w-xs truncate text-slate-600">
                                    {{ conv.latest_message?.message || 'No messages' }}
                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    {{ conv.messages_count || 0 }} msgs
                                </td>
                                <td class="p-4 text-slate-400">
                                    {{ formatTime(conv.last_message_at || conv.updated_at) }}
                                </td>
                                <td class="p-4 text-right">
                                    <Link
                                        :href="route('admin.messages.show', conv.id)"
                                        class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-glam-700 text-white font-bold transition text-[11px]"
                                    >
                                        Inspect Thread &rarr;
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!conversations.data || conversations.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-slate-400">
                                    No conversations found on the platform.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="conversations.links && conversations.links.length > 3" class="p-4 border-t border-slate-100 flex justify-center">
                    <div class="flex items-center gap-1">
                        <template v-for="(link, idx) in conversations.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                                :class="link.active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
