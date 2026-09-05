<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';
import { storageUrl } from '@/Utils/storage';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_customers: 0,
            total_artists: 0,
            subscribed_artists: 0,
            total_users: 0,
            total_notifications_sent: 0,
        }),
    },
    recentNotifications: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
        }),
    },
});

const activeTab = ref('compose'); // 'compose' | 'history'

const form = useForm({
    title: '',
    message: '',
    target_type: 'all',
    category: 'announcement',
});

// Quick Templates
const templates = [
    {
        name: '🌸 Seasonal Glam Promo',
        title: '✨ Exclusive Weekend Beauty Offer - Up to 25% Off Treatments!',
        message: 'Book your favorite salon and bridal makeovers this weekend with exclusive savings across top verified studios.',
        target_type: 'customers',
        category: 'promotion',
    },
    {
        name: '👑 Salon Partner Update',
        title: '👑 New Platform Growth Features Now Live for Beauty Studios',
        message: 'Explore your new salon analytics, instant booking calendar management, and zero-commission subscription options.',
        target_type: 'artists',
        category: 'announcement',
    },
    {
        name: '⚡ Scheduled Maintenance',
        title: '⚡ Routine System Upgrades Tonight at 02:00 AM PKT',
        message: 'We will be conducting brief performance upgrades for 30 minutes. Appointments and bookings will resume smoothly afterwards.',
        target_type: 'all',
        category: 'maintenance',
    },
    {
        name: '💎 VIP Salon Perk',
        title: '💎 Exclusive Feature Spotlight for Pro Subscribed Salons',
        message: 'Your studio is featured on top homepage rankings with 0% booking commission take.',
        target_type: 'subscribers',
        category: 'announcement',
    },
];

const applyTemplate = (tpl) => {
    form.title = tpl.title;
    form.message = tpl.message;
    form.target_type = tpl.target_type;
    form.category = tpl.category;
};

const getTargetBadge = (target) => {
    switch (target) {
        case 'customers':
            return { label: '👰 Customers Only', class: 'bg-rose-100 text-rose-800 border-rose-200' };
        case 'artists':
            return { label: '👑 Salons & Artists', class: 'bg-purple-100 text-purple-800 border-purple-200' };
        case 'subscribers':
            return { label: '💎 Pro Subscribed Salons', class: 'bg-amber-100 text-amber-800 border-amber-200' };
        default:
            return { label: '🌐 All Users (Customers + Studios)', class: 'bg-blue-100 text-blue-800 border-blue-200' };
    }
};

const estimatedAudienceCount = computed(() => {
    switch (form.target_type) {
        case 'customers':
            return props.stats.total_customers;
        case 'artists':
            return props.stats.total_artists;
        case 'subscribers':
            return props.stats.subscribed_artists;
        default:
            return props.stats.total_users;
    }
});

const submitBroadcast = () => {
    Swal.fire({
        title: 'Dispatch Broadcast?',
        text: `This notification will be broadcast to ${estimatedAudienceCount.value} recipient(s) via In-App Bell and Email.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Send Now',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            form.post(route('admin.notifications.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: '🚀 Broadcast delivered successfully!',
                        showConfirmButton: false,
                        timer: 3500,
                    });
                },
                onError: (err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Broadcast Failed',
                        text: Object.values(err)[0] || 'Please check your inputs.',
                    });
                },
            });
        }
    });
};

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getAvatar = (img, name = 'User') => {
    return storageUrl(img, `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f43f5e&color=fff&bold=true`);
};
</script>

<template>
    <Head title="Broadcast Center & Notifications | Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- 1. HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            Broadcast Center & Push Notifications
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            COMMUNICATIONS
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Send targeted multi-channel announcements, seasonal deals, and system updates to customers and salon partners.
                    </p>
                </div>
            </div>

            <!-- 2. AUDIENCE REACH KPI RIBBON (4 LUXURY CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Customer Reach -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-rose-800">Customer Audience</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ Number(stats.total_customers).toLocaleString() }}
                        </h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">Registered marketplace clients</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-700 flex items-center justify-center text-xl shadow-xs">
                        👰
                    </div>
                </div>

                <!-- Active Salon Studios -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800">Verified Studios</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ Number(stats.total_artists).toLocaleString() }}
                        </h3>
                        <p class="text-[10px] text-purple-700 font-semibold mt-0.5">Approved salon partners</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shadow-xs">
                        👑
                    </div>
                </div>

                <!-- Subscribed Pro Studios -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Pro Subscribed</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ Number(stats.subscribed_artists).toLocaleString() }}
                        </h3>
                        <p class="text-[10px] text-amber-700 font-semibold mt-0.5">Monthly plan studios</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl shadow-xs">
                        💎
                    </div>
                </div>

                <!-- Total Delivered Dispatches -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Delivered Alerts</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ Number(stats.total_notifications_sent).toLocaleString() }}
                        </h3>
                        <p class="text-[10px] text-blue-700 font-semibold mt-0.5">Dispatches delivered</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl shadow-xs">
                        📢
                    </div>
                </div>
            </div>

            <!-- 3. VIEW TABS -->
            <div class="flex items-center gap-2 border-b border-rose-100 pb-2">
                <button
                    type="button"
                    @click="activeTab = 'compose'"
                    class="px-4 py-2 rounded-2xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeTab === 'compose'
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-600 hover:text-slate-900 border border-rose-100 hover:bg-rose-50/60'"
                >
                    📢 Broadcast Composer & Preview
                </button>
                <button
                    type="button"
                    @click="activeTab = 'history'"
                    class="px-4 py-2 rounded-2xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeTab === 'history'
                        ? 'bg-glam-600 text-white shadow-md shadow-glam-600/20'
                        : 'bg-white text-slate-600 hover:text-slate-900 border border-rose-100 hover:bg-rose-50/60'"
                >
                    📑 Dispatch Log & History ({{ recentNotifications.data ? recentNotifications.data.length : 0 }})
                </button>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: BROADCAST COMPOSER & LIVE PREVIEW -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'compose'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Composer Form (7 Cols) -->
                <div class="lg:col-span-7 p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-5">
                    <!-- Quick Templates Pills -->
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Quick Campaign Presets:</span>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            <button
                                v-for="(tpl, idx) in templates"
                                :key="idx"
                                type="button"
                                @click="applyTemplate(tpl)"
                                class="px-3 py-1 rounded-xl text-[11px] font-bold bg-rose-50 hover:bg-glam-600 hover:text-white text-glam-800 border border-rose-200 transition cursor-pointer"
                            >
                                {{ tpl.name }}
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submitBroadcast" class="space-y-4 pt-2">
                        <!-- Target Audience -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Target Audience Group <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    v-for="target in [
                                        { key: 'all', icon: '🌐', label: 'All Users' },
                                        { key: 'customers', icon: '👰', label: 'Customers' },
                                        { key: 'artists', icon: '👑', label: 'All Studios' },
                                        { key: 'subscribers', icon: '💎', label: 'Pro Salons' },
                                    ]"
                                    :key="target.key"
                                    type="button"
                                    @click="form.target_type = target.key"
                                    class="p-3 rounded-2xl border text-center transition flex flex-col items-center gap-1 cursor-pointer"
                                    :class="form.target_type === target.key
                                        ? 'border-glam-500 bg-rose-50/70 text-glam-950 font-bold shadow-xs'
                                        : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs'"
                                >
                                    <span class="text-base">{{ target.icon }}</span>
                                    <span class="text-[11px]">{{ target.label }}</span>
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-400 font-medium">
                                Estimated delivery audience: <strong class="text-slate-800">{{ estimatedAudienceCount }} recipients</strong>
                            </p>
                        </div>

                        <!-- Category / Type -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Campaign Category & Severity
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-glam-500 cursor-pointer"
                            >
                                <option value="announcement">📢 General Announcement</option>
                                <option value="promotion">🏷️ Promotional / Seasonal Discount Offer</option>
                                <option value="maintenance">⚡ System Maintenance / Upgrade Notice</option>
                                <option value="alert">⚠️ Urgent Policy / Service Alert</option>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Broadcast Headline <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="e.g. ✨ Grand Weekend Glam Sale - Up to 25% Off!"
                                class="w-full p-3 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                required
                            />
                        </div>

                        <!-- Message Content -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Notification Message Body <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="form.message"
                                rows="4"
                                placeholder="Enter detailed message text communicated to users..."
                                class="w-full p-3 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 leading-relaxed"
                                required
                            ></textarea>
                            <div class="flex items-center justify-between text-[10px] text-slate-400">
                                <span>Multi-channel broadcast will deliver via In-App Bell + Email.</span>
                                <span>{{ form.message.length }} / 2000 chars</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3 border-t border-rose-50 flex items-center justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing || !form.title || !form.message"
                                class="px-6 py-3 rounded-2xl bg-gradient-to-r from-glam-500 via-rose-500 to-pink-600 hover:from-glam-600 hover:to-pink-700 text-white text-xs font-bold shadow-lg shadow-pink-950/20 transition-all active:scale-98 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <span class="text-base">🚀</span>
                                <span>{{ form.processing ? 'Dispatching Broadcast...' : `Broadcast to ${estimatedAudienceCount} Users` }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Live Preview Mockup Container (5 Cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="p-6 rounded-3xl bg-slate-950 text-white shadow-xl space-y-4 border border-slate-800">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Live Device Simulation</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                In-App Notification Bell
                            </span>
                        </div>

                        <!-- In-App Notification Bell Card Mockup -->
                        <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2 shadow-inner">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center text-white text-sm shadow-md">
                                        🔔
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white leading-tight">Beauty Marketplace</p>
                                        <p class="text-[10px] text-slate-400">Just now • Official System Alert</p>
                                    </div>
                                </div>
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                            </div>

                            <div class="pt-1">
                                <h4 class="text-xs font-bold text-rose-200">
                                    {{ form.title || 'Your Announcement Headline' }}
                                </h4>
                                <p class="text-[11px] text-slate-300 mt-1 leading-relaxed line-clamp-3">
                                    {{ form.message || 'The full message text will render here inside the user notification bell popover and mobile notification tray.' }}
                                </p>
                            </div>

                            <div class="pt-2 flex items-center justify-between border-t border-slate-800/80 text-[10px] text-slate-400">
                                <span>Target: {{ getTargetBadge(form.target_type).label }}</span>
                                <span class="text-rose-400 font-bold">Tap to view &rarr;</span>
                            </div>
                        </div>

                        <!-- Email Preview Snippet -->
                        <div class="p-4 rounded-2xl bg-white text-slate-900 space-y-2 shadow-lg">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">✉️ Email Inbox Preview</span>
                                <span class="text-[10px] text-slate-400">To: client@domain.com</span>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900">
                                    {{ form.title || 'Broadcast Subject Line' }}
                                </h5>
                                <p class="text-[10px] text-slate-600 mt-1 line-clamp-2">
                                    {{ form.message || 'Official email broadcast formatted nicely with your marketplace header and direct CTA.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: SYSTEM DISPATCH LOG & NOTIFICATION HISTORY -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'history'" class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <div v-if="recentNotifications.data && recentNotifications.data.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4">Recipient</th>
                                <th class="py-3.5 px-4">Notification Title & Summary</th>
                                <th class="py-3.5 px-4">Type</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Dispatched At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr v-for="notif in recentNotifications.data" :key="notif.id" class="hover:bg-rose-50/30 transition-colors">
                                <!-- Recipient -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div v-if="notif.recipient" class="flex items-center gap-2.5">
                                        <img
                                            :src="getAvatar(notif.recipient.avatar, notif.recipient.name)"
                                            :alt="notif.recipient.name"
                                            class="w-7 h-7 rounded-full object-cover ring-1 ring-rose-200"
                                        />
                                        <div>
                                            <p class="font-bold text-slate-900 text-xs">{{ notif.recipient.name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ notif.recipient.email }}</p>
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">System Recipient</span>
                                </td>

                                <!-- Title & Body -->
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ notif.title }}</p>
                                    <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ notif.message }}</p>
                                </td>

                                <!-- Type -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ notif.type }}
                                    </span>
                                </td>

                                <!-- Read Status -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="notif.is_read ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                    >
                                        {{ notif.is_read ? '✓ Read' : '● Unread' }}
                                    </span>
                                </td>

                                <!-- Date -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap text-slate-400 font-medium text-[11px]">
                                    {{ formatTimeAgo(notif.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-16">
                    <AppEmptyState
                        icon="🔔"
                        title="No notifications dispatched yet"
                        description="Compose a broadcast in the Broadcast Center to reach clients and salon partners."
                    />
                </div>

                <!-- Pagination -->
                <div v-if="recentNotifications.links && recentNotifications.links.length > 3" class="p-4 border-t border-rose-100">
                    <AppPagination :links="recentNotifications.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
