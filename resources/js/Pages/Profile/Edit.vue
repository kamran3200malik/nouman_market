<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    cities: {
        type: Array,
        default: () => [],
    }
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="My Profile Settings" />

    <CustomerLayout>
        <div class="max-w-4xl mx-auto space-y-8 pb-12">
            <!-- Header Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                        <span>⚙️</span>
                        <span>Account & Profile Settings</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Update your photo, personal details, contact information, and security preferences.
                    </p>
                </div>
            </div>

            <!-- Profile Info Card -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-pink-100/90 shadow-sm hover:shadow-md transition">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :cities="cities"
                />
            </div>

            <!-- Update Password Card -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-pink-100/90 shadow-sm hover:shadow-md transition">
                <UpdatePasswordForm />
            </div>
        </div>
    </CustomerLayout>
</template>
