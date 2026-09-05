<script setup>
import Navbar from '@/Components/Navbar.vue';

defineProps({
    title: {
        type: String,
        default: 'Welcome back'
    },
    subtitle: {
        type: String,
        default: 'Sign in to manage bookings, favorites, and your profile.'
    },
    heroTitle: {
        type: String,
        default: 'Welcome back to BeautyBook'
    },
    heroSubtitle: {
        type: String,
        default: 'Connect with verified beauty professionals, book appointments, and discover your perfect look.'
    }
});
</script>

<template>
    <div class="page-shell min-h-screen text-slate-800">
        <Navbar />

        <main class="flex min-h-screen">
            <!-- Left Side - Hero Image -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
                <img
                    src="https://placehold.co/1200x800/rose/white?text=BeautyBook"
                    alt="Beauty salon"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-br from-rose-900/90 via-rose-800/70 to-slate-900/80"></div>
                
                <div class="relative z-10 flex flex-col justify-center p-8 lg:p-12">
                    <div class="mb-6 lg:mb-8">
                        <div class="flex h-12 w-12 lg:h-16 lg:w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                            <span class="text-2xl lg:text-3xl font-bold text-white">B</span>
                        </div>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight mb-3 lg:mb-4">
                        {{ heroTitle }}
                    </h1>
                    <p class="text-sm sm:text-base lg:text-lg text-white/80 mb-6 lg:mb-8 max-w-md">
                        {{ heroSubtitle }}
                    </p>
                    <div class="space-y-3 lg:space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-4 w-4 lg:h-5 lg:w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-xs sm:text-sm lg:text-base text-white/90">Verified professionals only</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-4 w-4 lg:h-5 lg:w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-xs sm:text-sm lg:text-base text-white/90">Home and salon services</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-4 w-4 lg:h-5 lg:w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-xs sm:text-sm lg:text-base text-white/90">Easy booking and payments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="flex w-full lg:w-1/2 items-center justify-center p-4 sm:p-6 lg:p-12 pt-20 sm:pt-24 lg:pt-28">
                <div class="w-full max-w-md">
                    <div class="mb-6 sm:mb-8">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.2em] sm:tracking-[0.25em] text-rose-700">{{ title }}</p>
                        <p class="mt-3 text-xs sm:text-sm text-slate-500 break-words">{{ subtitle }}</p>
                    </div>

                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
