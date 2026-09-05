<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { playNotificationBeep } from '@/Utils/notificationAudio';

const page = usePage();
const isOpen = ref(false);
const bellContainer = ref(null);

const user = computed(() => page.props.auth?.user || null);
const unreadCount = computed(() => user.value?.unread_notifications_count || 0);
const notifications = computed(() => user.value?.notifications || []);

// Track previous unread count to trigger beep on new notification
const previousUnreadCount = ref(unreadCount.value);
const hasInteracted = ref(false);

watch(unreadCount, (newCount, oldCount) => {
    if (newCount > (oldCount ?? previousUnreadCount.value)) {
        // Play notification chime/beep sound
        playNotificationBeep();
    }
    previousUnreadCount.value = newCount;
});

// Periodic background check every 25 seconds for new notifications & messages
let pollInterval = null;
onMounted(() => {
    previousUnreadCount.value = unreadCount.value;

    if (user.value) {
        pollInterval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                router.reload({ only: ['auth'] });
            }
        }, 25000);
    }

    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (bellContainer.value && !bellContainer.value.contains(e.target)) {
        isOpen.value = false;
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (!hasInteracted.value) {
        hasInteracted.value = true;
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

const testChimeSound = () => {
    playNotificationBeep();
};

const getNotificationIcon = (type) => {
    switch (type) {
        case 'booking':
            return '🗓️';
        case 'message':
            return '💬';
        case 'review':
            return '⭐';
        case 'system':
            return '👑';
        case 'success':
            return '✨';
        case 'warning':
            return '⚠️';
        default:
            return '🔔';
    }
};

const handleNotificationClick = (notif) => {
    // Mark as read
    if (!notif.read_at) {
        router.post(route('notifications.read', notif.id), {}, { preserveScroll: true, preserveState: true });
    }
    closeDropdown();
    if (notif.action_url) {
        router.visit(notif.action_url);
    }
};

const markAllRead = () => {
    router.post(route('notifications.mark-all-read'), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <div v-if="user" ref="bellContainer" class="relative">
        <!-- Bell Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            class="relative p-2.5 rounded-2xl bg-white/90 hover:bg-white border border-pink-100/90 text-slate-700 shadow-xs hover:shadow-sm transition cursor-pointer flex items-center justify-center group"
            title="Notifications & Alerts"
        >
            <span class="text-base transition-transform group-hover:scale-110">🔔</span>

            <!-- Unread Badge Ping -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-[10px] font-black text-white shadow-sm ring-2 ring-white animate-pulse"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Modal / Popover -->
        <div
            v-if="isOpen"
            class="fixed sm:absolute right-4 sm:right-0 top-16 sm:top-12 z-50 w-[90vw] sm:w-96 rounded-3xl bg-white shadow-2xl border border-pink-100 overflow-hidden flex flex-col max-h-[85vh] animate-in fade-in slide-in-from-top-2 duration-200"
        >
            <!-- Dropdown Header -->
            <div class="p-4 bg-gradient-to-r from-[#1b0917] via-[#2d0d26] to-[#150612] text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm">🔔</span>
                    <h4 class="text-xs sm:text-sm font-bold text-white">Notifications</h4>
                    <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                        {{ unreadCount }} new
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- Audio Test Chime Button -->
                    <button
                        type="button"
                        @click.stop="testChimeSound"
                        class="p-1 rounded-lg text-pink-300 hover:text-white hover:bg-white/10 transition cursor-pointer text-xs"
                        title="Test notification chime sound"
                    >
                        🔊
                    </button>

                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        @click="markAllRead"
                        class="text-[10px] text-pink-300 hover:text-white underline font-semibold transition cursor-pointer"
                    >
                        Mark all read
                    </button>
                </div>
            </div>

            <!-- Notification Items List -->
            <div class="overflow-y-auto divide-y divide-pink-50 max-h-96">
                <div
                    v-for="notif in notifications"
                    :key="notif.id"
                    @click="handleNotificationClick(notif)"
                    class="p-3.5 sm:p-4 transition cursor-pointer flex items-start gap-3 hover:bg-pink-50/50"
                    :class="!notif.read_at ? 'bg-pink-50/30' : 'bg-white'"
                >
                    <!-- Icon -->
                    <div class="h-9 w-9 rounded-2xl bg-pink-100 text-glam-800 flex items-center justify-center text-sm shrink-0 shadow-xs border border-pink-200">
                        {{ getNotificationIcon(notif.type) }}
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <h5 class="text-xs font-bold text-slate-900 truncate">{{ notif.title }}</h5>
                            <span class="text-[10px] text-slate-400 shrink-0">{{ notif.created_at }}</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-0.5 line-clamp-2 leading-relaxed">
                            {{ notif.message }}
                        </p>
                    </div>

                    <!-- Unread Indicator Dot -->
                    <span
                        v-if="!notif.read_at"
                        class="h-2 w-2 rounded-full bg-rose-500 shrink-0 mt-1.5 ring-2 ring-rose-200"
                    ></span>
                </div>

                <!-- Empty State -->
                <div v-if="notifications.length === 0" class="p-8 text-center space-y-2">
                    <span class="text-3xl block">✨</span>
                    <p class="text-xs font-bold text-slate-800">All Caught Up!</p>
                    <p class="text-[11px] text-slate-400">You don't have any new notifications right now.</p>
                </div>
            </div>
        </div>
    </div>
</template>
