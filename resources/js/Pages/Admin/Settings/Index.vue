<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const activeSection = ref('general'); // 'general' | 'monetization' | 'booking' | 'notifications' | 'social' | 'system'

const form = useForm({
    settings: {
        // General & Branding
        site_name: props.settings.site_name || '',
        site_tagline: props.settings.site_tagline || '',
        site_description: props.settings.site_description || '',
        contact_email: props.settings.contact_email || '',
        contact_phone: props.settings.contact_phone || '',
        support_whatsapp: props.settings.support_whatsapp || '',
        office_address: props.settings.office_address || '',
        currency_code: props.settings.currency_code || 'PKR',
        currency_symbol: props.settings.currency_symbol || 'PKR',
        timezone: props.settings.timezone || 'Asia/Karachi',

        // Monetization
        commission_rate: props.settings.commission_rate || '10',
        subscription_monthly_fee: props.settings.subscription_monthly_fee || '3000',
        subscription_grace_days: props.settings.subscription_grace_days || '3',
        min_payout_threshold: props.settings.min_payout_threshold || '5000',
        tax_percentage: props.settings.tax_percentage || '0',

        // Shipping & Logistics (Marketplace)
        shipping_fee_standard: props.settings.shipping_fee_standard || '250',
        shipping_free_threshold: props.settings.shipping_free_threshold || '3000',
        shipping_free_enabled: props.settings.shipping_free_enabled === '1' || props.settings.shipping_free_enabled === true,
        shipping_carrier_name: props.settings.shipping_carrier_name || 'Standard Express Beauty Courier',
        shipping_estimated_days: props.settings.shipping_estimated_days || '2 - 4 Business Days',

        // Booking Policies
        max_advance_booking_days: props.settings.max_advance_booking_days || '30',
        min_booking_notice_hours: props.settings.min_booking_notice_hours || '2',
        cancellation_cutoff_hours: props.settings.cancellation_cutoff_hours || '6',
        auto_confirm_bookings: props.settings.auto_confirm_bookings === '1' || props.settings.auto_confirm_bookings === true,
        allow_customer_reviews: props.settings.allow_customer_reviews === '1' || props.settings.allow_customer_reviews === true,

        // Notifications
        email_booking_notifications: props.settings.email_booking_notifications === '1' || props.settings.email_booking_notifications === true,
        sms_whatsapp_notifications: props.settings.sms_whatsapp_notifications === '1' || props.settings.sms_whatsapp_notifications === true,
        admin_new_salon_alerts: props.settings.admin_new_salon_alerts === '1' || props.settings.admin_new_salon_alerts === true,

        // Social
        social_instagram: props.settings.social_instagram || '',
        social_facebook: props.settings.social_facebook || '',
        social_tiktok: props.settings.social_tiktok || '',
        social_youtube: props.settings.social_youtube || '',

        // SEO & Search Indexing
        seo_meta_title: props.settings.seo_meta_title || 'BeautyBook Luxe - Premier Salon Marketplace & Beauty CRM',
        seo_meta_description: props.settings.seo_meta_description || 'Discover and book verified luxury salons, certified makeup artists, bridal packages, and professional beauty essentials across Pakistan.',
        seo_meta_keywords: props.settings.seo_meta_keywords || 'salon booking pakistan, bridal makeup lahore, beauty parlor karachi, makeup artists islamabad, beauty products online, salon appointments',
        seo_google_analytics: props.settings.seo_google_analytics || '',
        seo_google_verification: props.settings.seo_google_verification || '',
        seo_index_enabled: props.settings.seo_index_enabled === '1' || props.settings.seo_index_enabled === true || props.settings.seo_index_enabled === undefined,

        // System
        maintenance_mode: props.settings.maintenance_mode === '1' || props.settings.maintenance_mode === true,
        maintenance_message: props.settings.maintenance_message || '',
    },
});

const submit = () => {
    form.put(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '⚙️ Platform configuration saved successfully!',
                showConfirmButton: false,
                timer: 3000,
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: Object.values(err)[0] || 'Please check your inputs.',
            });
        },
    });
};
</script>

<template>
    <Head title="Platform Settings & System Controls | Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- 1. EXECUTIVE HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            Platform Settings & System Controls
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            CONFIG STUDIO
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Configure brand identity, default salon commissions, subscription plans, booking lead times, and global platform policies.
                    </p>
                </div>

                <!-- Save Trigger -->
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-glam-500 via-rose-500 to-pink-600 hover:from-glam-600 hover:to-pink-700 text-white text-xs font-bold shadow-lg shadow-pink-950/20 transition-all active:scale-98 flex items-center gap-2 self-start sm:self-auto cursor-pointer disabled:opacity-50"
                >
                    <span>💾</span>
                    <span>{{ form.processing ? 'Saving Changes...' : 'Save Configuration' }}</span>
                </button>
            </div>

            <!-- 2. MAIN TWO-COLUMN LAYOUT (NAVIGATION MENU + SETTINGS PANEL) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Navigation Sidebar (3 Cols) -->
                <div class="lg:col-span-3 space-y-2">
                    <div class="p-3 bg-white rounded-3xl border border-rose-100 shadow-xs space-y-1">
                        <button
                            v-for="section in [
                                { key: 'general', icon: '🌸', label: 'Brand & General', desc: 'Identity, contacts & currency' },
                                { key: 'shipping', icon: '🚚', label: 'Shipping & Logistics', desc: 'Delivery rates & free shipping' },
                                { key: 'monetization', icon: '💰', label: 'Monetization & Fees', desc: 'Commissions & subscriptions' },
                                { key: 'booking', icon: '🗓️', label: 'Booking & Policies', desc: 'Lead times & cancellations' },
                                { key: 'notifications', icon: '🔔', label: 'Alerts & Gateways', desc: 'Email, WhatsApp & digests' },
                                { key: 'social', icon: '🌐', label: 'Social Channels', desc: 'Instagram, TikTok & links' },
                                { key: 'seo', icon: '🔍', label: 'SEO & Search Engine', desc: 'Sitemap, meta tags & SERP' },
                                { key: 'system', icon: '🛡️', label: 'System & Maintenance', desc: 'Maintenance mode & notices' },
                            ]"
                            :key="section.key"
                            type="button"
                            @click="activeSection = section.key"
                            class="w-full p-3 rounded-2xl text-left transition-all flex items-start gap-3 cursor-pointer"
                            :class="activeSection === section.key
                                ? 'bg-rose-50/80 border border-rose-200 text-slate-900 shadow-xs'
                                : 'hover:bg-slate-50 text-slate-600 border border-transparent'"
                        >
                            <span class="text-xl shrink-0 mt-0.5">{{ section.icon }}</span>
                            <div>
                                <p class="text-xs font-bold" :class="activeSection === section.key ? 'text-glam-800' : 'text-slate-800'">
                                    {{ section.label }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ section.desc }}</p>
                            </div>
                        </button>
                    </div>

                    <!-- Live Brand Snapshot Card -->
                    <div class="p-5 rounded-3xl bg-slate-950 text-white shadow-lg border border-slate-800 space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-400">Live Brand Telemetry</span>
                        <h4 class="font-serif font-bold text-sm text-white line-clamp-1">
                            {{ form.settings.site_name || 'Glamora Beauty' }}
                        </h4>
                        <p class="text-[11px] text-slate-300 line-clamp-2">
                            {{ form.settings.site_tagline || 'Luxury Salon Appointments' }}
                        </p>
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                            <span>Default Commission:</span>
                            <span class="text-rose-400 font-bold">{{ form.settings.commission_rate }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Settings Content Panel (9 Cols) -->
                <div class="lg:col-span-9 space-y-6">
                    <form @submit.prevent="submit" class="p-6 sm:p-8 bg-white rounded-3xl border border-rose-100 shadow-xs space-y-6">
                        <!-- ========================================================= -->
                        <!-- SECTION 1: BRANDING & GENERAL -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'general'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🌸 Brand Identity & General Platform</h3>
                                <p class="text-xs text-slate-400">Marketplace name, support contact channels, and currency settings.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Marketplace Site Name <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.settings.site_name"
                                        type="text"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Slogan / Tagline</label>
                                    <input
                                        v-model="form.settings.site_tagline"
                                        type="text"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Meta Site Description</label>
                                <textarea
                                    v-model="form.settings.site_description"
                                    rows="2"
                                    class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Official Support Email</label>
                                    <input
                                        v-model="form.settings.contact_email"
                                        type="email"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Support Helpline Phone</label>
                                    <input
                                        v-model="form.settings.contact_phone"
                                        type="text"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Support WhatsApp Number</label>
                                    <input
                                        v-model="form.settings.support_whatsapp"
                                        type="text"
                                        placeholder="+923001234567"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Currency Code</label>
                                    <input
                                        v-model="form.settings.currency_code"
                                        type="text"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Currency Symbol</label>
                                    <input
                                        v-model="form.settings.currency_symbol"
                                        type="text"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Platform Timezone</label>
                                    <input
                                        v-model="form.settings.timezone"
                                        type="text"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-slate-700">Headquarter Physical Address</label>
                                <input
                                    v-model="form.settings.office_address"
                                    type="text"
                                    class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                />
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION: SHIPPING & LOGISTICS (MARKETPLACE) -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'shipping'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🚚 Cosmetics & Merchandise Shipping Logistics</h3>
                                <p class="text-xs text-slate-400">Configure standard shipping charges, free shipping thresholds, courier partners, and transit delivery estimates for beauty products.</p>
                            </div>

                            <!-- Highlights & Pricing Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold text-slate-900">Standard Shipping Fee (PKR)</label>
                                        <span class="text-xs font-bold text-indigo-700">📦 Standard Flat Rate</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500">Default delivery fee added to beauty store cart checkout when below free threshold.</p>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.shipping_fee_standard"
                                            type="number"
                                            min="0"
                                            step="10"
                                            class="w-full p-2.5 pr-14 rounded-xl border border-indigo-200 bg-white text-xs sm:text-sm text-slate-900 font-bold focus:ring-2 focus:ring-indigo-500"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-slate-400">PKR</span>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-100 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold text-slate-900">Free Shipping Minimum Cart Order (PKR)</label>
                                        <span class="text-xs font-bold text-emerald-700">✨ Free Delivery</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500">Cart subtotal amount required to unlock 100% Free Nationwide Delivery.</p>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.shipping_free_threshold"
                                            type="number"
                                            min="0"
                                            step="100"
                                            :disabled="!form.settings.shipping_free_enabled"
                                            class="w-full p-2.5 pr-14 rounded-xl border border-emerald-200 bg-white text-xs sm:text-sm text-slate-900 font-bold focus:ring-2 focus:ring-emerald-500 disabled:opacity-50"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-slate-400">PKR</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Enable / Disable Free Shipping Toggle -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-900">Enable "Free Shipping" Incentive</p>
                                    <p class="text-[10px] text-slate-500">Display dynamic free shipping progress bars and waive delivery charges when orders reach threshold.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.settings.shipping_free_enabled"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Official Logistics / Courier Partner</label>
                                    <input
                                        v-model="form.settings.shipping_carrier_name"
                                        type="text"
                                        placeholder="e.g., Standard Express Beauty Courier / TCS / Call Courier"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Displayed on customer invoices and shipment tracking timeline.</p>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Estimated Delivery Window</label>
                                    <input
                                        v-model="form.settings.shipping_estimated_days"
                                        type="text"
                                        placeholder="e.g., 2 - 4 Business Days"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Estimated delivery duration shown to clients in cart drawer.</p>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION 2: MONETIZATION & COMMISSIONS -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'monetization'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">💰 Monetization, Subscriptions & Payouts</h3>
                                <p class="text-xs text-slate-400">Manage default salon commission takes, flat monthly plan pricing, and payout thresholds.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold text-slate-900">Default Commission Rate (%)</label>
                                        <span class="text-xs font-bold text-glam-700">% Fee Model</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500">Platform fee deducted per completed treatment booking on commission plan.</p>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.commission_rate"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="w-full p-2.5 pr-8 rounded-xl border border-rose-200 bg-white text-xs sm:text-sm text-slate-900 font-bold"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-slate-400">%</span>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-purple-50/50 border border-purple-100 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold text-slate-900">Monthly Subscription Fee (PKR)</label>
                                        <span class="text-xs font-bold text-purple-700">💎 Pro Plan</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500">Flat monthly subscription fee for 0% commission unlimited bookings.</p>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.subscription_monthly_fee"
                                            type="number"
                                            min="0"
                                            step="500"
                                            class="w-full p-2.5 pr-14 rounded-xl border border-purple-200 bg-white text-xs sm:text-sm text-slate-900 font-bold"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-slate-400">PKR</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Subscription Grace Days</label>
                                    <input
                                        v-model="form.settings.subscription_grace_days"
                                        type="number"
                                        min="0"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Days before auto-suspension upon expiry.</p>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Minimum Payout Withdrawal (PKR)</label>
                                    <input
                                        v-model="form.settings.min_payout_threshold"
                                        type="number"
                                        min="1000"
                                        step="500"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">Minimum balance for salon payout request.</p>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Applicable Sales Tax / GST (%)</label>
                                    <input
                                        v-model="form.settings.tax_percentage"
                                        type="number"
                                        min="0"
                                        max="30"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">General sales tax percentage.</p>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION 3: BOOKING & POLICIES -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'booking'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🗓️ Booking Funnel & Appointment Policies</h3>
                                <p class="text-xs text-slate-400">Rules governing appointment scheduling windows, notice hours, and cancellations.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Max Advance Booking Window</label>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.max_advance_booking_days"
                                            type="number"
                                            min="1"
                                            max="365"
                                            class="w-full p-2.5 pr-12 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 font-bold"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">Days</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">How far in advance clients can reserve.</p>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Minimum Notice Required</label>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.min_booking_notice_hours"
                                            type="number"
                                            min="0"
                                            class="w-full p-2.5 pr-14 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 font-bold"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">Hours</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">Lead time before appointment start.</p>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Free Cancellation Cutoff</label>
                                    <div class="relative mt-1">
                                        <input
                                            v-model="form.settings.cancellation_cutoff_hours"
                                            type="number"
                                            min="0"
                                            class="w-full p-2.5 pr-14 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 font-bold"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs text-slate-400">Hours</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1">Hours before start for penalty-free cancel.</p>
                                </div>
                            </div>

                            <div class="space-y-3 pt-2">
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">Instant Auto-Confirmation</p>
                                        <p class="text-[10px] text-slate-400">Automatically confirm appointment bookings if salon calendar slot is open</p>
                                    </div>
                                    <input
                                        v-model="form.settings.auto_confirm_bookings"
                                        type="checkbox"
                                        class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                                    />
                                </div>

                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">Enable Client Verified Reviews</p>
                                        <p class="text-[10px] text-slate-400">Allow customers who completed appointments to leave 1-5 star ratings & comments</p>
                                    </div>
                                    <input
                                        v-model="form.settings.allow_customer_reviews"
                                        type="checkbox"
                                        class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION 4: NOTIFICATIONS & GATEWAYS -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'notifications'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🔔 Communications & Gateway Triggers</h3>
                                <p class="text-xs text-slate-400">Automated transaction emails, WhatsApp booking updates, and admin digests.</p>
                            </div>

                            <div class="space-y-3">
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">Email Transaction Alerts</p>
                                        <p class="text-[10px] text-slate-400">Send branded HTML receipt emails upon booking creation, rescheduling, and completion</p>
                                    </div>
                                    <input
                                        v-model="form.settings.email_booking_notifications"
                                        type="checkbox"
                                        class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                                    />
                                </div>

                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">WhatsApp / SMS Reminders</p>
                                        <p class="text-[10px] text-slate-400">Dispatch instant WhatsApp reminder notifications to clients 2 hours before scheduled slot</p>
                                    </div>
                                    <input
                                        v-model="form.settings.sms_whatsapp_notifications"
                                        type="checkbox"
                                        class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                                    />
                                </div>

                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">Admin New Salon Onboarding Alerts</p>
                                        <p class="text-[10px] text-slate-400">Notify administrator immediately when a new salon studio submits verification documents</p>
                                    </div>
                                    <input
                                        v-model="form.settings.admin_new_salon_alerts"
                                        type="checkbox"
                                        class="w-5 h-5 text-rose-600 rounded-lg focus:ring-rose-500 cursor-pointer"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION 5: SOCIAL CHANNELS -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'social'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🌐 Social Media & Community Channels</h3>
                                <p class="text-xs text-slate-400">Official social links displayed on customer storefront footer and email templates.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Instagram Profile URL</label>
                                    <input
                                        v-model="form.settings.social_instagram"
                                        type="url"
                                        placeholder="https://instagram.com/your_handle"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Facebook Page URL</label>
                                    <input
                                        v-model="form.settings.social_facebook"
                                        type="url"
                                        placeholder="https://facebook.com/your_page"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">TikTok Profile URL</label>
                                    <input
                                        v-model="form.settings.social_tiktok"
                                        type="url"
                                        placeholder="https://tiktok.com/@your_handle"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">YouTube Channel URL</label>
                                    <input
                                        v-model="form.settings.social_youtube"
                                        type="url"
                                        placeholder="https://youtube.com/@your_channel"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION 7: SEO & SEARCH INDEXING -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'seo'" class="space-y-6">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🔍 SEO & Search Engine Optimization</h3>
                                <p class="text-xs text-slate-400">Manage Google SERP snippet previews, XML sitemaps, robots.txt, and webmaster verification.</p>
                            </div>

                            <!-- Live Google SERP Preview Card -->
                            <div class="p-5 rounded-3xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                                        <span>🌐</span> Live Google Search Snippet Preview
                                    </span>
                                    <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        SEO Healthy
                                    </span>
                                </div>
                                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1">
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="font-medium text-slate-700">beautybook.pk</span>
                                        <span>›</span>
                                        <span>salons</span>
                                    </div>
                                    <h4 class="text-base font-medium text-[#1a0dab] hover:underline cursor-pointer line-clamp-1">
                                        {{ form.settings.seo_meta_title || form.settings.site_name || 'BeautyBook Luxe' }}
                                    </h4>
                                    <p class="text-xs text-[#4d5156] line-clamp-2 leading-relaxed">
                                        {{ form.settings.seo_meta_description || 'Discover and book verified luxury salons, certified makeup artists, bridal packages, and professional beauty essentials across Pakistan.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Meta Title & Meta Description Inputs -->
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between items-center">
                                        <label class="text-[11px] font-bold text-slate-700">Default Meta Title <span class="text-rose-500">*</span></label>
                                        <span class="text-[10px]" :class="(form.settings.seo_meta_title?.length || 0) > 60 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                            {{ form.settings.seo_meta_title?.length || 0 }} / 60 chars
                                        </span>
                                    </div>
                                    <input
                                        v-model="form.settings.seo_meta_title"
                                        type="text"
                                        placeholder="e.g. BeautyBook Luxe - Premier Salon Marketplace & Beauty CRM"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>

                                <div>
                                    <div class="flex justify-between items-center">
                                        <label class="text-[11px] font-bold text-slate-700">Default Meta Description <span class="text-rose-500">*</span></label>
                                        <span class="text-[10px]" :class="(form.settings.seo_meta_description?.length || 0) > 160 ? 'text-amber-600 font-bold' : 'text-slate-400'">
                                            {{ form.settings.seo_meta_description?.length || 0 }} / 160 chars
                                        </span>
                                    </div>
                                    <textarea
                                        v-model="form.settings.seo_meta_description"
                                        rows="3"
                                        placeholder="Compelling description summarizing the platform for Google search results..."
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Target SEO Keywords (Comma Separated)</label>
                                    <input
                                        v-model="form.settings.seo_meta_keywords"
                                        type="text"
                                        placeholder="salon booking, bridal makeup, beauty parlor karachi, makeup artists..."
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500"
                                    />
                                </div>
                            </div>

                            <!-- Webmaster & Analytics Verification -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Google Search Console Verification Code</label>
                                    <input
                                        v-model="form.settings.seo_google_verification"
                                        type="text"
                                        placeholder="e.g. google-site-verification=abc123xyz"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 font-mono text-[11px]"
                                    />
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-700">Google Analytics 4 (GA4 Measurement ID)</label>
                                    <input
                                        v-model="form.settings.seo_google_analytics"
                                        type="text"
                                        placeholder="e.g. G-XXXXXXXXXX"
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-glam-500 font-mono text-[11px]"
                                    />
                                </div>
                            </div>

                            <!-- Live Crawler Resources Strip -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <a
                                    href="/sitemap.xml"
                                    target="_blank"
                                    class="p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center justify-between transition group"
                                >
                                    <div class="space-y-0.5">
                                        <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                            <span>🗺️</span> XML Sitemap
                                        </p>
                                        <p class="text-[10px] text-slate-500">Live dynamic URL index for search bots</p>
                                    </div>
                                    <span class="text-xs text-rose-600 font-bold group-hover:translate-x-1 transition-transform">/sitemap.xml ↗</span>
                                </a>
                                <a
                                    href="/robots.txt"
                                    target="_blank"
                                    class="p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center justify-between transition group"
                                >
                                    <div class="space-y-0.5">
                                        <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                            <span>🤖</span> Crawler Directives
                                        </p>
                                        <p class="text-[10px] text-slate-500">Search engine indexing rules</p>
                                    </div>
                                    <span class="text-xs text-rose-600 font-bold group-hover:translate-x-1 transition-transform">/robots.txt ↗</span>
                                </a>
                            </div>

                            <!-- Indexing Toggle -->
                            <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-emerald-950">Allow Search Engine Indexing (Google, Bing)</h4>
                                    <p class="text-[10px] text-emerald-700">When enabled, robots meta tag is set to index, follow.</p>
                                </div>
                                <input
                                    v-model="form.settings.seo_index_enabled"
                                    type="checkbox"
                                    class="w-5 h-5 text-emerald-600 rounded-lg focus:ring-emerald-500 cursor-pointer"
                                />
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- SECTION 8: SYSTEM & MAINTENANCE -->
                        <!-- ========================================================= -->
                        <div v-show="activeSection === 'system'" class="space-y-5">
                            <div>
                                <h3 class="text-base font-serif font-bold text-slate-900">🛡️ System Health & Emergency Controls</h3>
                                <p class="text-xs text-slate-400">Put the platform into maintenance mode for upgrades or show global announcements.</p>
                            </div>

                            <div class="p-5 rounded-3xl bg-amber-50/60 border border-amber-200 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-amber-950">Marketplace Maintenance Mode</h4>
                                        <p class="text-[10px] text-amber-800">Temporarily pause storefront bookings while performing system upgrades</p>
                                    </div>
                                    <input
                                        v-model="form.settings.maintenance_mode"
                                        type="checkbox"
                                        class="w-5 h-5 text-amber-600 rounded-lg focus:ring-amber-500 cursor-pointer"
                                    />
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold text-amber-950">Maintenance Notice Message</label>
                                    <textarea
                                        v-model="form.settings.maintenance_message"
                                        rows="3"
                                        placeholder="Message displayed to visitors during maintenance..."
                                        class="w-full mt-1 p-2.5 rounded-2xl border border-amber-200 bg-white text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-amber-500"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button in Panel -->
                        <div class="pt-4 border-t border-rose-50 flex items-center justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-3 rounded-2xl bg-gradient-to-r from-glam-500 via-rose-500 to-pink-600 hover:from-glam-600 hover:to-pink-700 text-white text-xs font-bold shadow-lg shadow-pink-950/20 transition-all active:scale-98 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <span>💾</span>
                                <span>{{ form.processing ? 'Saving Platform Changes...' : 'Save Configuration' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
