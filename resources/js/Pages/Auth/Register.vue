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
        subtitle="Join BeautyBook to book beauty professionals and manage your appointments."
        heroTitle="Join BeautyBook Today"
        heroSubtitle="Create your account and start discovering the best beauty professionals in your area."
    >
        <Head title="Register" />

        <form @submit.prevent="submit" class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <InputLabel for="name" value="Name" class="text-xs sm:text-sm font-semibold text-slate-700" />

                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1.5 sm:mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Enter your name"
                    />

                    <InputError class="mt-1.5 sm:mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="username" value="Username" class="text-xs sm:text-sm font-semibold text-slate-700" />

                    <TextInput
                        id="username"
                        type="text"
                        class="mt-1.5 sm:mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                        v-model="form.username"
                        required
                        autocomplete="username"
                        placeholder="Choose a username"
                    />

                    <InputError class="mt-1.5 sm:mt-2" :message="form.errors.username" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="phone" value="Mobile Number" class="text-xs sm:text-sm font-semibold text-slate-700" />
                        <span class="text-[10px] text-slate-400 font-mono">{{ form.phone ? form.phone.length : 0 }}/11 digits</span>
                    </div>

                    <TextInput
                        id="phone"
                        type="tel"
                        class="mt-1.5 sm:mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 tracking-wide"
                        v-model="form.phone"
                        @input="e => form.phone = e.target.value.replace(/\D/g, '').slice(0, 11)"
                        required
                        maxlength="11"
                        autocomplete="tel"
                        placeholder="03001234567"
                    />

                    <InputError class="mt-1.5 sm:mt-2" :message="form.errors.phone" />

                </div>

                <div>
                    <InputLabel for="email" value="Email" class="text-xs sm:text-sm font-semibold text-slate-700" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1.5 sm:mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                        v-model="form.email"
                        required
                        autocomplete="email"
                        placeholder="Enter your email"
                    />

                    <InputError class="mt-1.5 sm:mt-2" :message="form.errors.email" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <InputLabel for="password" value="Password" class="text-xs sm:text-sm font-semibold text-slate-700" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1.5 sm:mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a password"
                    />

                    <InputError class="mt-1.5 sm:mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Confirm Password"
                        class="text-xs sm:text-sm font-semibold text-slate-700"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1.5 sm:mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                    />

                    <InputError
                        class="mt-1.5 sm:mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full rounded-xl bg-rose-600 px-4 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-700"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Sign up
                </PrimaryButton>
            </div>

            <div class="text-center">
                <p class="text-sm text-slate-600">
                    Already have an account?
                    <Link
                        :href="route('login')"
                        class="ml-1 font-semibold text-rose-600 transition hover:text-rose-700"
                    >
                        Log in
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
