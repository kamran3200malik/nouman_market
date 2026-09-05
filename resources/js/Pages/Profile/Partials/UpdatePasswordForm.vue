<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <header class="border-b border-pink-100 pb-4">
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span>🔐</span>
                <span>Security & Password</span>
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Ensure your account is protected with a strong, secure password.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <InputLabel for="current_password" value="Current Password" class="text-xs font-bold text-slate-700" />
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.current_password" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="password" value="New Password" class="text-xs font-bold text-slate-700" />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.password" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm New Password" class="text-xs font-bold text-slate-700" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-pink-100">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm shadow-md hover:scale-[1.02] active:scale-[0.98] transition disabled:opacity-50 cursor-pointer"
                >
                    <span v-if="form.processing" class="inline-block animate-spin">⌛</span>
                    <span v-else>🔒</span>
                    <span>Update Password</span>
                </button>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-y-1"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                        <span>✓</span>
                        <span>Password updated!</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
