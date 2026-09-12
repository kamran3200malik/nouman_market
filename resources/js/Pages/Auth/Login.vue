<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const quickFill = (role) => {
    if (role === 'admin') {
        form.login = 'admin@example.com';
        form.password = 'password';
    } else if (role === 'customer') {
        form.login = 'customer@example.com';
        form.password = 'password';
    }
};
</script>

<template>
    <GuestLayout title="Welcome back" subtitle="Sign in to track orders, manage your beauty wishlist, and shop authentic products.">
        <Head title="Sign In - Luxe Beauty Market" />

        <div v-if="status" class="mb-6 rounded-2xl bg-green-50 border border-green-200 px-4 py-3 text-xs font-semibold text-green-700">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="login" value="Email, Phone, or Username" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />

                <TextInput
                    id="login"
                    type="text"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                    v-model="form.login"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Enter email or phone number"
                />

                <InputError class="mt-1.5 text-xs" :message="form.errors.login" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <InputLabel for="password" value="Password" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-semibold text-rose-600 transition hover:text-rose-700"
                    >
                        Forgot password?
                    </Link>
                </div>

                <div class="relative mt-2">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-11 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600 cursor-pointer"
                    >
                        {{ showPassword ? '👁️' : '🙈' }}
                    </button>
                </div>

                <InputError class="mt-1.5 text-xs" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded-md border-slate-300 text-rose-600 focus:ring-rose-500" />
                    <span class="ml-2 text-xs font-medium text-slate-600">Remember me</span>
                </label>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-pink-950/20 transition hover:scale-101 cursor-pointer"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Signing in...' : 'Sign In to My Account' }}
                </PrimaryButton>
            </div>

            <!-- Quick Fill Demo Accounts -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block text-center">
                    ⚡ 1-Click Demo Login (Password: password)
                </span>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="quickFill('customer')"
                        class="px-2.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold border border-emerald-200 transition cursor-pointer text-center"
                        title="customer@example.com / password"
                    >
                        🛍️ Customer Buyer
                    </button>
                    <button
                        type="button"
                        @click="quickFill('admin')"
                        class="px-2.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-800 text-[11px] font-bold border border-rose-200 transition cursor-pointer text-center"
                        title="admin@example.com / password"
                    >
                        👑 Store Admin
                    </button>
                </div>
            </div>

            <div class="text-center pt-2">
                <p class="text-xs text-slate-600">
                    Don't have an account?
                    <Link
                        :href="route('register')"
                        class="ml-1 font-bold text-rose-600 transition hover:text-rose-700 hover:underline"
                    >
                        Create an account &rarr;
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
