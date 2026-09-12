<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    username: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout 
        title="Create your account" 
        subtitle="Join Luxe Market to order genuine cosmetics, track deliveries, and save your beauty wishlist."
        heroTitle="Join Luxe Market Today"
        heroSubtitle="Discover 100% genuine skincare, makeup, and luxury fragrances delivered across Pakistan."
    >
        <Head title="Create Account - Luxe Beauty Market" />

        <form @submit.prevent="submit" class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <InputLabel for="name" value="Full Name" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />

                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1.5 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="e.g. Ayesha Khan"
                    />

                    <InputError class="mt-1.5 text-xs" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="username" value="Username" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />

                    <TextInput
                        id="username"
                        type="text"
                        class="mt-1.5 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                        v-model="form.username"
                        required
                        autocomplete="username"
                        placeholder="e.g. ayesha_k"
                    />

                    <InputError class="mt-1.5 text-xs" :message="form.errors.username" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="phone" value="Mobile Phone" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />
                        <span class="text-[10px] text-slate-400 font-mono">{{ form.phone ? form.phone.length : 0 }}/11 digits</span>
                    </div>

                    <TextInput
                        id="phone"
                        type="tel"
                        class="mt-1.5 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs tracking-wide"
                        v-model="form.phone"
                        @input="e => form.phone = e.target.value.replace(/\D/g, '').slice(0, 11)"
                        required
                        maxlength="11"
                        autocomplete="tel"
                        placeholder="03001234567"
                    />

                    <InputError class="mt-1.5 text-xs" :message="form.errors.phone" />
                </div>

                <div>
                    <InputLabel for="email" value="Email Address" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1.5 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                        v-model="form.email"
                        required
                        autocomplete="email"
                        placeholder="ayesha@example.com"
                    />

                    <InputError class="mt-1.5 text-xs" :message="form.errors.email" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <InputLabel for="password" value="Password" class="text-xs font-bold text-slate-700 uppercase tracking-wider" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1.5 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                    />

                    <InputError class="mt-1.5 text-xs" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Confirm Password"
                        class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1.5 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-xs"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repeat your password"
                    />

                    <InputError
                        class="mt-1.5 text-xs"
                        :message="form.errors.password_confirmation"
                    />
                </div>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-pink-950/20 transition hover:scale-101 cursor-pointer"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating Account...' : 'Create My Account' }}
                </PrimaryButton>
            </div>

            <div class="text-center pt-1">
                <p class="text-xs text-slate-600">
                    Already have an account?
                    <Link
                        :href="route('login')"
                        class="ml-1 font-bold text-rose-600 transition hover:text-rose-700 hover:underline"
                    >
                        Sign in here &rarr;
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
