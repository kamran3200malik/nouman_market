<script setup>
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { storageUrl } from '@/Utils/storage';
import { detectCurrentAddress } from '@/Utils/geolocation';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
    cities: {
        type: Array,
        default: () => [],
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const avatarPreview = ref(null);
const fileInput = ref(null);
const isDetectingLocation = ref(false);

const form = useForm({
    _method: 'PATCH',
    name: user.value.name || '',
    username: user.value.username || '',
    email: user.value.email || '',
    phone: user.value.phone || '',
    address: user.value.address || '',
    city: user.value.city || '',
    area: user.value.area || '',
    avatar: null,
});

const autoDetectProfileLocation = async () => {
    isDetectingLocation.value = true;
    try {
        const loc = await detectCurrentAddress();
        if (loc.fullAddress) {
            form.address = loc.fullAddress;
        }
        if (loc.city) {
            form.city = loc.city;
        }
        if (loc.area) {
            form.area = loc.area;
        }
    } catch (err) {
        alert(err.message || 'Could not auto-detect location.');
    } finally {
        isDetectingLocation.value = false;
    }
};


const currentAvatarUrl = computed(() => {
    if (avatarPreview.value) {
        return avatarPreview.value;
    }
    return storageUrl(user.value.avatar, 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=240');
});

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (event) => {
            avatarPreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const triggerFileInput = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const availableAreas = computed(() => {
    if (!form.city || !props.cities.length) return [];
    const selectedCity = props.cities.find(c => c.name === form.city || String(c.id) === String(form.city));
    return selectedCity ? (selectedCity.areas || []) : [];
});

const submit = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            avatarPreview.value = null;
        },
    });
};
</script>

<template>
    <section class="space-y-6">
        <header class="border-b border-pink-100 pb-4">
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span>👤</span>
                <span>Profile & Identity</span>
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Manage your public beauty profile, profile photo, contact details, and location.
            </p>
        </header>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. AVATAR UPLOAD SECTION -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 p-5 rounded-3xl bg-gradient-to-r from-pink-50/70 via-rose-50/40 to-slate-50 border border-pink-100">
                <div class="relative group cursor-pointer" @click="triggerFileInput">
                    <div class="h-24 w-24 rounded-full overflow-hidden border-3 border-glam-400 shadow-md bg-white">
                        <img
                            :src="currentAvatarUrl"
                            :alt="user.name"
                            class="h-full w-full object-cover group-hover:scale-105 transition duration-300"
                        />
                    </div>
                    <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white text-[11px] font-bold transition-opacity">
                        <span>📷</span>
                        <span>Change</span>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleAvatarChange"
                    />
                </div>

                <div class="flex-1 text-center sm:text-left space-y-2">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Profile Picture</h4>
                        <p class="text-xs text-slate-500">Upload a crisp avatar (JPG, PNG, or WEBP up to 5MB).</p>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                        <button
                            type="button"
                            @click="triggerFileInput"
                            class="px-4 py-1.5 rounded-xl bg-white hover:bg-pink-50 text-glam-800 text-xs font-bold border border-pink-200 shadow-xs transition"
                        >
                            Upload New Photo
                        </button>
                        <span v-if="avatarPreview" class="text-xs text-emerald-600 font-bold self-center">✓ New photo selected</span>
                    </div>
                    <InputError :message="form.errors.avatar" />
                </div>
            </div>

            <!-- 2. PERSONAL DETAILS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <!-- Full Name -->
                <div>
                    <InputLabel for="name" value="Full Name *" class="text-xs font-bold text-slate-700" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        v-model="form.name"
                        required
                        autocomplete="name"
                        placeholder="e.g. Ayesha Khan"
                    />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>

                <!-- Username -->
                <div>
                    <InputLabel for="username" value="Username" class="text-xs font-bold text-slate-700" />
                    <TextInput
                        id="username"
                        type="text"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        v-model="form.username"
                        autocomplete="username"
                        placeholder="e.g. ayesha_glam"
                    />
                    <InputError class="mt-1" :message="form.errors.username" />
                </div>

                <!-- Email Address -->
                <div>
                    <InputLabel for="email" value="Email Address *" class="text-xs font-bold text-slate-700" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        v-model="form.email"
                        required
                        autocomplete="email"
                        placeholder="ayesha@example.com"
                    />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <!-- Phone Number -->
                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="phone" value="Phone Number (WhatsApp)" class="text-xs font-bold text-slate-700" />
                        <span class="text-[10px] text-slate-400 font-mono">{{ form.phone ? form.phone.length : 0 }}/11 digits</span>
                    </div>
                    <TextInput
                        id="phone"
                        type="tel"
                        maxlength="11"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs tracking-wide"
                        v-model="form.phone"
                        @input="e => form.phone = e.target.value.replace(/\D/g, '').slice(0, 11)"
                        autocomplete="tel"
                        placeholder="03001234567"
                    />
                    <InputError class="mt-1" :message="form.errors.phone" />
                </div>

            </div>

            <!-- 3. LOCATION & ADDRESS DETAILS -->
            <div class="p-5 rounded-3xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <span>📍</span>
                        <span>City & Delivery / Service Address</span>
                    </h4>
                    <button
                        type="button"
                        @click="autoDetectProfileLocation"
                        :disabled="isDetectingLocation"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white text-[11px] font-bold shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                        <span v-if="isDetectingLocation" class="inline-block h-3 w-3 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <span v-else>📍</span>
                        <span>{{ isDetectingLocation ? 'Detecting...' : 'Auto-Detect Address' }}</span>
                    </button>
                </div>


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- City -->
                    <div>
                        <InputLabel for="city" value="City" class="text-xs font-bold text-slate-700" />
                        <select
                            v-if="cities && cities.length > 0"
                            id="city"
                            v-model="form.city"
                            class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs bg-white"
                        >
                            <option value="">Select City</option>
                            <option v-for="city in cities" :key="city.id" :value="city.name">
                                {{ city.name }}
                            </option>
                            <option v-if="form.city && !cities.some(c => c.name === form.city)" :value="form.city">
                                {{ form.city }}
                            </option>
                        </select>
                        <TextInput
                            v-else
                            id="city"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                            v-model="form.city"
                            placeholder="e.g. Lahore, Karachi, Islamabad"
                        />
                        <InputError class="mt-1" :message="form.errors.city" />
                    </div>

                    <!-- Area -->
                    <div>
                        <InputLabel for="area" value="Area / Neighborhood" class="text-xs font-bold text-slate-700" />
                        <select
                            v-if="availableAreas && availableAreas.length > 0"
                            id="area"
                            v-model="form.area"
                            class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs bg-white"
                        >
                            <option value="">Select Area</option>
                            <option v-for="area in availableAreas" :key="area.id" :value="area.name">
                                {{ area.name }}
                            </option>
                        </select>
                        <TextInput
                            v-else
                            id="area"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                            v-model="form.area"
                            placeholder="e.g. Gulberg, DHA Phase 5, Bahria Town"
                        />
                        <InputError class="mt-1" :message="form.errors.area" />
                    </div>
                </div>

                <!-- Full Address -->
                <div>
                    <InputLabel for="address" value="Street Address (for Home Services)" class="text-xs font-bold text-slate-700" />
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="2"
                        class="mt-1 block w-full rounded-2xl border-pink-200 focus:border-glam-500 focus:ring-glam-500 text-sm shadow-xs"
                        placeholder="House / Apartment #, Street #, Landmark..."
                    ></textarea>
                    <InputError class="mt-1" :message="form.errors.address" />
                </div>
            </div>

            <!-- Email Verification Notice if Applicable -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-800 space-y-2">
                <p>
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-bold underline hover:text-amber-900 cursor-pointer"
                    >
                        Click here to re-send verification email.
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="text-emerald-700 font-bold">
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <!-- Submit Button & Feedback -->
            <div class="flex items-center justify-between pt-4 border-t border-pink-100">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-glam-600 via-rose-600 to-pink-700 hover:from-glam-700 hover:to-pink-800 text-white font-bold text-xs sm:text-sm shadow-md shadow-pink-950/20 hover:scale-[1.02] active:scale-[0.98] transition disabled:opacity-50 cursor-pointer"
                >
                    <span v-if="form.processing" class="inline-block animate-spin">⌛</span>
                    <span v-else>💾</span>
                    <span>Save Profile Changes</span>
                </button>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-y-1"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <p v-if="form.recentlySuccessful" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                        <span>✓</span>
                        <span>Saved successfully!</span>
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
