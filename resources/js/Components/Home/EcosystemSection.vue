<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- 5. DUAL ECOSYSTEM CRM & CLIENT CONCIERGE -->
    <section class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:gap-6 md:grid-cols-2">
            <!-- Card 1: For Clients (Luxury Pink / Rose Gold Concierge) -->
            <div class="group relative rounded-3xl p-5 sm:p-7 md:p-9 bg-gradient-to-br from-white via-rose-50/60 to-pink-100/40 backdrop-blur-xl border border-rose-200/80 shadow-lg hover:shadow-2xl hover:shadow-rose-500/10 hover:border-rose-300 transition-all duration-300 flex flex-col justify-between space-y-5 sm:space-y-6 overflow-hidden">
                <!-- Ambient Glow Orb -->
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-rose-400/15 blur-3xl group-hover:scale-110 transition-transform duration-700"></div>
                <div class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 rounded-full bg-pink-400/15 blur-3xl"></div>

                <div class="relative z-10 space-y-3 sm:space-y-4">
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 rounded-full bg-rose-500/10 px-3 py-1 sm:px-3.5 sm:py-1.5 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-rose-700 border border-rose-200/70 shadow-xs backdrop-blur-md">
                        <span>🌸</span>
                        <span>VIP Client Concierge</span>
                    </div>

                    <h3 class="font-serif text-xl sm:text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Book Verified Salons with <span class="bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-transparent">Zero Wait-Time</span>
                    </h3>

                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Browse real artist portfolios, transparent pricing, verified client reviews, and enjoy instant slot confirmation with 100% Escrow protection.
                    </p>

                    <!-- Feature Pills Grid -->
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 pt-1 sm:pt-2 text-[11px] sm:text-xs font-semibold text-slate-700">
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/80 border border-rose-100 px-2.5 sm:px-3 py-1.5 sm:py-2 shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-[9px] sm:text-[10px] font-black shrink-0">✓</span>
                            <span class="truncate">100% Escrow Safety</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/80 border border-rose-100 px-2.5 sm:px-3 py-1.5 sm:py-2 shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-rose-100 text-rose-600 text-[9px] sm:text-[10px] font-black shrink-0">⚡</span>
                            <span class="truncate">Real-Time Sync</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/80 border border-rose-100 px-2.5 sm:px-3 py-1.5 sm:py-2 shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-purple-100 text-purple-600 text-[9px] sm:text-[10px] font-black shrink-0">💎</span>
                            <span class="truncate">Verified Portfolios</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/80 border border-rose-100 px-2.5 sm:px-3 py-1.5 sm:py-2 shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-pink-100 text-pink-600 text-[9px] sm:text-[10px] font-black shrink-0">🏡</span>
                            <span class="truncate">Salon & Home Visit</span>
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <div class="relative z-10 pt-1 sm:pt-2">
                    <Link
                        :href="route('artists.index')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-rose-600 via-pink-600 to-rose-700 hover:from-rose-500 hover:to-pink-600 px-5 sm:px-6 py-3 sm:py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-rose-600/25 hover:shadow-xl hover:shadow-rose-600/35 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 cursor-pointer"
                    >
                        <span>Browse Verified Salons</span>
                        <span class="group-hover:translate-x-1 transition-transform duration-200">&rarr;</span>
                    </Link>
                </div>
            </div>

            <!-- Card 2: For Salon Owners & Beauticians (Sleek Royal Indigo/Amethyst SaaS CRM) -->
            <div class="group relative rounded-3xl p-5 sm:p-7 md:p-9 bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white border border-indigo-500/30 shadow-xl hover:shadow-2xl hover:shadow-indigo-500/15 hover:border-indigo-400/50 transition-all duration-300 flex flex-col justify-between space-y-5 sm:space-y-6 overflow-hidden">
                <!-- Ambient Glow Orb -->
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-indigo-500/20 blur-3xl group-hover:scale-110 transition-transform duration-700"></div>
                <div class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 rounded-full bg-purple-500/20 blur-3xl"></div>

                <div class="relative z-10 space-y-3 sm:space-y-4">
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 rounded-full bg-indigo-500/20 px-3 py-1 sm:px-3.5 sm:py-1.5 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-indigo-300 border border-indigo-400/30 shadow-xs backdrop-blur-md">
                        <span>⚡</span>
                        <span>Master Studio CRM Portal</span>
                    </div>

                    <h3 class="font-serif text-xl sm:text-2xl md:text-3xl font-extrabold text-white tracking-tight leading-tight">
                        Supercharge Your Studio with <span class="bg-gradient-to-r from-indigo-300 via-pink-300 to-amber-200 bg-clip-text text-transparent">BeautyBook CRM</span>
                    </h3>

                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-light">
                        Get a complete SaaS growth suite: automated bookings, direct client chat, digital appointment calendar, instant escrow payouts, and verified badges.
                    </p>

                    <!-- Feature Pills Grid -->
                    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 pt-1 sm:pt-2 text-[11px] sm:text-xs font-semibold text-slate-200">
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/10 border border-white/10 px-2.5 sm:px-3 py-1.5 sm:py-2 backdrop-blur-md shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300 text-[9px] sm:text-[10px] font-black shrink-0">✓</span>
                            <span class="truncate">Digital Calendar Sync</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/10 border border-white/10 px-2.5 sm:px-3 py-1.5 sm:py-2 backdrop-blur-md shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-amber-500/20 text-amber-300 text-[9px] sm:text-[10px] font-black shrink-0">💰</span>
                            <span class="truncate">Direct Payout Vault</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/10 border border-white/10 px-2.5 sm:px-3 py-1.5 sm:py-2 backdrop-blur-md shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-cyan-500/20 text-cyan-300 text-[9px] sm:text-[10px] font-black shrink-0">💬</span>
                            <span class="truncate">Direct Client Chat</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2 rounded-xl bg-white/10 border border-white/10 px-2.5 sm:px-3 py-1.5 sm:py-2 backdrop-blur-md shadow-2xs">
                            <span class="flex h-4.5 w-4.5 sm:h-5 sm:w-5 items-center justify-center rounded-full bg-rose-500/20 text-rose-300 text-[9px] sm:text-[10px] font-black shrink-0">🎉</span>
                            <span class="truncate">Zero Setup Fee</span>
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <div class="relative z-10 pt-1 sm:pt-2">
                    <Link
                        :href="route('artist.register')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-rose-600 hover:from-indigo-500 hover:to-purple-500 px-5 sm:px-6 py-3 sm:py-3.5 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-indigo-950/50 hover:shadow-xl hover:shadow-indigo-900/60 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 cursor-pointer"
                    >
                        <span>Join as Artist / Studio CRM</span>
                        <span class="group-hover:translate-x-1 transition-transform duration-200">&rarr;</span>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

