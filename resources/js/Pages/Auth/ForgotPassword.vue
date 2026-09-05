<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout title="Reset Password" subtitle="Enter your email and we'll send you a password reset link.">
        <Head title="Forgot Password" />

        <div
            v-if="status"
            class="mb-4 rounded-full bg-green-50 px-4 py-2 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email" class="text-sm font-semibold text-slate-700" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-full border-0 bg-white/95 px-5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:ring-4 focus:ring-rose-200"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your email"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full rounded-full bg-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-700"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Send Reset Link
                </PrimaryButton>
            </div>

            <div class="text-center">
                <Link
                    :href="route('login')"
                    class="text-sm font-semibold text-rose-600 transition hover:text-rose-700"
                >
                    Back to login
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
