<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    conversation: {
        type: Object,
        required: true
    },
    messages: {
        type: Array,
        default: () => []
    }
});

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};
</script>

<template>
    <Head title="Inspect Chat Thread - Admin" />

    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.messages.index')"
                        class="p-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition text-xs font-bold"
                    >
                        &larr; Back to Inquiries
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">
                            {{ conversation.customer?.name }} &harr; {{ conversation.artist_profile?.business_name || 'Store Merchant' }}
                        </h1>
                        <p class="text-xs text-slate-500">Platform Moderation & Audit Thread</p>
                    </div>
                </div>
            </div>

            <!-- Chat Pane -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-[65vh]">
                <div class="p-4 bg-slate-50 border-b border-slate-200 text-xs flex items-center justify-between">
                    <span>Thread ID: #{{ conversation.id }}</span>
                    <span v-if="conversation.booking" class="font-bold text-glam-800">
                        Order #{{ conversation.booking.booking_number }}
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/30">
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex flex-col"
                        :class="msg.sender_id === conversation.customer_id ? 'items-start' : 'items-end'"
                    >
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 mb-1">
                            <span>{{ msg.sender?.name || (msg.sender_id === conversation.customer_id ? 'Buyer' : 'Seller') }}</span>
                            <span>•</span>
                            <span>{{ formatTime(msg.created_at) }}</span>
                        </div>

                        <div
                            class="max-w-md rounded-2xl p-4 text-xs shadow-xs leading-relaxed"
                            :class="msg.sender_id === conversation.customer_id 
                                ? 'bg-white text-slate-800 border border-slate-200' 
                                : 'bg-slate-900 text-white'"
                        >
                            {{ msg.message }}
                        </div>
                    </div>

                    <div v-if="messages.length === 0" class="text-center py-12 text-slate-400 text-xs">
                        No messages recorded in this conversation.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
