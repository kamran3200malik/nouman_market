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

const activeSection = ref('general'); // 'general' | 'shipping' | 'notifications' | 'social_seo' | 'system'

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

        // Shipping & Logistics (Marketplace)
        shipping_fee_standard: props.settings.shipping_fee_standard || '250',
        shipping_free_threshold: props.settings.shipping_free_threshold || '5000',
        shipping_free_enabled: props.settings.shipping_free_enabled === '1' || props.settings.shipping_free_enabled === true,
        shipping_carrier_name: props.settings.shipping_carrier_name || 'TCS / Leopard Express',
        shipping_estimated_days: props.settings.shipping_estimated_days || '2 - 4 Business Days',
        cash_on_delivery_enabled: props.settings.cash_on_delivery_enabled === '1' || props.settings.cash_on_delivery_enabled === true,
        online_payment_enabled: props.settings.online_payment_enabled === '1' || props.settings.online_payment_enabled === true,
        tax_percentage: props.settings.tax_percentage || '0',
        allow_customer_reviews: props.settings.allow_customer_reviews === '1' || props.settings.allow_customer_reviews === true,

        // Notifications
        email_order_notifications: props.settings.email_order_notifications === '1' || props.settings.email_order_notifications === true,
        sms_whatsapp_notifications: props.settings.sms_whatsapp_notifications === '1' || props.settings.sms_whatsapp_notifications === true,
        admin_new_order_alerts: props.settings.admin_new_order_alerts === '1' || props.settings.admin_new_order_alerts === true,

        // Social
        social_instagram: props.settings.social_instagram || '',
        social_facebook: props.settings.social_facebook || '',
        social_tiktok: props.settings.social_tiktok || '',
        social_youtube: props.settings.social_youtube || '',

        // SEO & Search Indexing
        seo_meta_title: props.settings.seo_meta_title || 'Luxe Beauty Market - Authentic Cosmetics & Skincare',
        seo_meta_description: props.settings.seo_meta_description || 'Shop 100% original skincare, cosmetics, makeup, haircare and luxury fragrances with fast delivery across Pakistan.',
        seo_meta_keywords: props.settings.seo_meta_keywords || 'cosmetics pakistan, skincare lahore, makeup online karachi, original perfumes, beauty shop pakistan',
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
                title: '⚙️ Store configuration saved successfully!',
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
    <Head title="Store Settings | Marketplace Admin" />

    <AdminLayout>
        <div class="space-y-6 max-w-6xl mx-auto pb-12">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                        Marketplace Store Settings
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Configure brand identity, shipping tariffs, payment channels, SEO keywords, and notifications.
                    </p>
                </div>

                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-rose-500/25 transition-all hover:scale-102 active:scale-95 disabled:opacity-50 cursor-pointer shrink-0"
                >
                    <span v-if="form.processing" class="animate-spin">⏳</span>
                    <span v-else>💾</span>
                    <span>{{ form.processing ? 'Saving...' : 'Save Settings' }}</span>
                </button>
            </div>

            <!-- Settings Layout: Vertical Tabs + Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
                <!-- Navigation Tabs -->
                <div class="rounded-3xl bg-white p-3 shadow-sm border border-slate-200/80 space-y-1">
                    <button
                        type="button"
                        @click="activeSection = 'general'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition-all text-left cursor-pointer"
                        :class="activeSection === 'general' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>🏪</span>
                        <span>General & Store Identity</span>
                    </button>

                    <button
                        type="button"
                        @click="activeSection = 'shipping'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition-all text-left cursor-pointer"
                        :class="activeSection === 'shipping' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>🚚</span>
                        <span>Shipping & Payments</span>
                    </button>

                    <button
                        type="button"
                        @click="activeSection = 'notifications'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition-all text-left cursor-pointer"
                        :class="activeSection === 'notifications' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>🔔</span>
                        <span>Order Notifications</span>
                    </button>

                    <button
                        type="button"
                        @click="activeSection = 'social_seo'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition-all text-left cursor-pointer"
                        :class="activeSection === 'social_seo' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>🌐</span>
                        <span>Social Media & SEO</span>
                    </button>

                    <button
                        type="button"
                        @click="activeSection = 'system'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs font-bold transition-all text-left cursor-pointer"
                        :class="activeSection === 'system' ? 'bg-rose-500 text-white shadow-md shadow-rose-500/20' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>🛠️</span>
                        <span>System & Maintenance</span>
                    </button>
                </div>

                <!-- Settings Content Panel -->
                <div class="lg:col-span-3 rounded-3xl bg-white p-6 sm:p-8 shadow-sm border border-slate-200/80">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- SECTION 1: GENERAL & STORE IDENTITY -->
                        <div v-show="activeSection === 'general'" class="space-y-5">
                            <div class="border-b border-slate-100 pb-4">
                                <h3 class="text-base font-bold text-slate-900">Store Profile & Branding</h3>
                                <p class="text-xs text-slate-500">Configure marketplace naming, contact emails, and currency representation.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Store Name *</label>
                                    <input
                                        v-model="form.settings.site_name"
                                        type="text"
                                        required
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Store Tagline</label>
                                    <input
                                        v-model="form.settings.site_tagline"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Store Description</label>
                                <textarea
                                    v-model="form.settings.site_description"
                                    rows="3"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Support Email</label>
                                    <input
                                        v-model="form.settings.contact_email"
                                        type="email"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Support Phone</label>
                                    <input
                                        v-model="form.settings.contact_phone"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp Hotline</label>
                                    <input
                                        v-model="form.settings.support_whatsapp"
                                        type="text"
                                        placeholder="+923001234567"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Warehouse / Office Address</label>
                                <input
                                    v-model="form.settings.office_address"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Currency Code</label>
                                    <input
                                        v-model="form.settings.currency_code"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Currency Symbol</label>
                                    <input
                                        v-model="form.settings.currency_symbol"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Timezone</label>
                                    <input
                                        v-model="form.settings.timezone"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: SHIPPING & PAYMENTS -->
                        <div v-show="activeSection === 'shipping'" class="space-y-5">
                            <div class="border-b border-slate-100 pb-4">
                                <h3 class="text-base font-bold text-slate-900">Shipping Tariffs & Payment Channels</h3>
                                <p class="text-xs text-slate-500">Configure courier shipping rules, free delivery eligibility, COD, and sales tax.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Standard Delivery Fee (PKR)</label>
                                    <input
                                        v-model="form.settings.shipping_fee_standard"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Free Shipping Minimum Threshold (PKR)</label>
                                    <input
                                        v-model="form.settings.shipping_free_threshold"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Courier Partner Name</label>
                                    <input
                                        v-model="form.settings.shipping_carrier_name"
                                        type="text"
                                        placeholder="TCS / Leopard / Call Courier"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Estimated Delivery Timeline</label>
                                    <input
                                        v-model="form.settings.shipping_estimated_days"
                                        type="text"
                                        placeholder="2 - 4 Business Days"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                            </div>

                            <div class="space-y-3 pt-2">
                                <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                                    <input
                                        v-model="form.settings.shipping_free_enabled"
                                        type="checkbox"
                                        class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block">Enable Free Nationwide Shipping Banner</span>
                                        <span class="text-[11px] text-slate-500">Automatically apply PKR 0 shipping when cart exceeds the threshold.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                                    <input
                                        v-model="form.settings.cash_on_delivery_enabled"
                                        type="checkbox"
                                        class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block">Enable Cash on Delivery (COD)</span>
                                        <span class="text-[11px] text-slate-500">Allow customers to pay cash upon parcel delivery at their doorstep.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                                    <input
                                        v-model="form.settings.allow_customer_reviews"
                                        type="checkbox"
                                        class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block">Allow Customer Reviews & Ratings</span>
                                        <span class="text-[11px] text-slate-500">Allow verified buyers to leave reviews on purchased products.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- SECTION 3: ORDER NOTIFICATIONS -->
                        <div v-show="activeSection === 'notifications'" class="space-y-5">
                            <div class="border-b border-slate-100 pb-4">
                                <h3 class="text-base font-bold text-slate-900">Order Alerts & Notifications</h3>
                                <p class="text-xs text-slate-500">Control automated customer email receipts, dispatch alerts, and admin notifications.</p>
                            </div>

                            <div class="space-y-3">
                                <label class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                                    <input
                                        v-model="form.settings.email_order_notifications"
                                        type="checkbox"
                                        class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block">Customer Order Confirmation Emails</span>
                                        <span class="text-[11px] text-slate-500">Send instant HTML receipt upon order placement.</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                                    <input
                                        v-model="form.settings.admin_new_order_alerts"
                                        type="checkbox"
                                        class="rounded text-rose-600 focus:ring-rose-500 h-4 w-4"
                                    />
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 block">Admin Instant Order Alerts</span>
                                        <span class="text-[11px] text-slate-500">Notify store administrators immediately when a new order is received.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- SECTION 4: SOCIAL MEDIA & SEO -->
                        <div v-show="activeSection === 'social_seo'" class="space-y-5">
                            <div class="border-b border-slate-100 pb-4">
                                <h3 class="text-base font-bold text-slate-900">Social Channels & SEO Directives</h3>
                                <p class="text-xs text-slate-500">Optimize search engine visibility and maintain official brand links.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Instagram URL</label>
                                    <input
                                        v-model="form.settings.social_instagram"
                                        type="url"
                                        placeholder="https://instagram.com/..."
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Facebook URL</label>
                                    <input
                                        v-model="form.settings.social_facebook"
                                        type="url"
                                        placeholder="https://facebook.com/..."
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">TikTok URL</label>
                                    <input
                                        v-model="form.settings.social_tiktok"
                                        type="url"
                                        placeholder="https://tiktok.com/@..."
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">YouTube URL</label>
                                    <input
                                        v-model="form.settings.social_youtube"
                                        type="url"
                                        placeholder="https://youtube.com/@..."
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Global SEO Meta Title</label>
                                <input
                                    v-model="form.settings.seo_meta_title"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Global SEO Meta Description</label>
                                <textarea
                                    v-model="form.settings.seo_meta_description"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">SEO Search Keywords (comma-separated)</label>
                                <input
                                    v-model="form.settings.seo_meta_keywords"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                />
                            </div>
                        </div>

                        <!-- SECTION 5: SYSTEM & MAINTENANCE -->
                        <div v-show="activeSection === 'system'" class="space-y-5">
                            <div class="border-b border-slate-100 pb-4">
                                <h3 class="text-base font-bold text-slate-900">System Mode & Maintenance</h3>
                                <p class="text-xs text-slate-500">Put the public storefront into temporary maintenance mode during stock audits.</p>
                            </div>

                            <label class="flex items-center gap-3 p-4 rounded-2xl bg-rose-50/50 border border-rose-200 cursor-pointer">
                                <input
                                    v-model="form.settings.maintenance_mode"
                                    type="checkbox"
                                    class="rounded text-rose-600 focus:ring-rose-500 h-5 w-5"
                                />
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">Enable Maintenance Mode</span>
                                    <span class="text-[11px] text-slate-500">Display a polite maintenance screen to public visitors while allowing administrators full access.</span>
                                </div>
                            </label>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Maintenance Notice Message</label>
                                <textarea
                                    v-model="form.settings.maintenance_message"
                                    rows="3"
                                    placeholder="We are upgrading our inventory servers. We will be back online shortly."
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-900 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 hover:bg-rose-600 px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-md transition-all hover:scale-102 cursor-pointer disabled:opacity-50"
                            >
                                <span v-if="form.processing" class="animate-spin">⏳</span>
                                <span v-else>💾</span>
                                <span>Save All Settings</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
