<script setup>
import { Link } from '@inertiajs/vue3';
import AppBadge from './AppBadge.vue';

const props = defineProps({
    booking: {
        type: Object,
        required: true
    },
    showActions: {
        type: Boolean,
        default: true
    }
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return String(date).split('T')[0] || 'N/A';
        return d.toLocaleDateString('en-PK', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return String(date).split('T')[0] || 'N/A';
    }
};

const formatTime = (time) => {
    if (!time) return 'N/A';
    try {
        const str = String(time);
        const parts = str.split(':');
        if (parts.length >= 2) {
            const h = parseInt(parts[0], 10);
            const m = parts[1];
            const ampm = h >= 12 ? 'PM' : 'AM';
            const h12 = h % 12 || 12;
            return `${h12}:${m} ${ampm}`;
        }
        return str;
    } catch (e) {
        return String(time);
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0
    }).format(price || 0);
};

const statusColors = {
    pending: 'warning',
    confirmed: 'success',
    rejected: 'danger',
    cancelled: 'danger',
    rescheduled: 'info',
    completed: 'success',
    'no_show': 'danger'
};
</script>

<template>
    <div class="w-full bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-pink-100/90 hover:border-pink-300 hover:shadow-md transition-all space-y-4">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-pink-50 pb-4">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-pink-100 to-rose-50 border border-pink-200 flex items-center justify-center text-xl shrink-0">
                    💄
                </div>
                <div>
                    <h4 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                        {{ booking.service?.name || 'Beauty Treatment' }}
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                        <span>🏪</span>
                        <span class="font-medium text-slate-700">
                            {{ booking.artist_profile?.business_name || booking.artistProfile?.business_name || 'Verified Beauty Studio' }}
                        </span>
                        <span v-if="booking.artist_profile?.city?.name || booking.artistProfile?.city?.name" class="text-slate-400">
                            &bull; {{ booking.artist_profile?.city?.name || booking.artistProfile?.city?.name }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <AppBadge :variant="statusColors[booking.status] || 'default'" size="sm">
                    {{ (booking.status || 'pending').replace('_', ' ').toUpperCase() }}
                </AppBadge>
            </div>
        </div>

        <!-- Appointment Details Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-pink-50/40 rounded-2xl p-4 border border-pink-100/70 text-xs">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Booking Ref</span>
                <span class="font-bold text-slate-800 font-mono text-[11px] sm:text-xs">{{ booking.booking_number || ('BK-' + booking.id) }}</span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">🗓️ Date</span>
                <span class="font-bold text-slate-800">{{ formatDate(booking.booking_date || booking.date) }}</span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">⏰ Time</span>
                <span class="font-bold text-slate-800">{{ formatTime(booking.booking_time || booking.time) }}</span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">💵 Total</span>
                <span class="font-extrabold text-glam-800 sm:text-sm">{{ formatPrice(booking.total_amount || booking.total_price) }}</span>
            </div>
        </div>

        <!-- Footer / Venue & CTA Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            <div class="text-xs text-slate-500 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg">
                    <span>{{ booking.service_type === 'home' ? '🏡' : '🏪' }}</span>
                    <span>{{ booking.service_type === 'home' ? 'Home Visit Service' : 'In-Studio Salon Service' }}</span>
                </span>
                <span v-if="booking.duration_minutes" class="text-slate-400">
                    ⏱️ {{ booking.duration_minutes }} mins
                </span>
            </div>

            <div v-if="showActions" class="flex items-center gap-2">
                <Link
                    :href="route('customer.bookings.show', booking.id)"
                    class="w-full sm:w-auto px-4 py-2 bg-gradient-to-r from-glam-600 to-rose-600 hover:from-glam-700 hover:to-rose-700 text-white rounded-xl transition text-xs font-bold shadow-sm shadow-pink-900/10 text-center cursor-pointer"
                >
                    View Details &rarr;
                </Link>
            </div>
        </div>
    </div>
</template>
