<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    backups: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_backups: 0,
            total_size_formatted: '0 B',
            last_backup: null,
            database_tables_count: 45,
            database_name: 'laravel',
        }),
    },
});

const creating = ref(false);
const restoringFile = ref(null);
const showRestoreModal = ref(false);
const restoreSubmitting = ref(false);

const createInstantBackup = () => {
    creating.value = true;
    router.post(
        route('admin.settings.backups.create'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                creating.value = false;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '💾 Database snapshot created successfully!',
                    showConfirmButton: false,
                    timer: 3500,
                });
            },
            onError: (err) => {
                creating.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Backup Failed',
                    text: Object.values(err)[0] || 'Unable to generate snapshot.',
                });
            },
        }
    );
};

const openRestoreModal = (backup) => {
    restoringFile.value = backup;
    showRestoreModal.value = true;
};

const closeRestoreModal = () => {
    restoringFile.value = null;
    showRestoreModal.value = false;
};

const confirmRestore = () => {
    if (!restoringFile.value) return;
    restoreSubmitting.value = true;

    router.post(
        route('admin.settings.backups.restore', restoringFile.value.filename),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                restoreSubmitting.value = false;
                closeRestoreModal();
                Swal.fire({
                    title: 'Database Restored!',
                    text: `Database tables have been restored successfully from snapshot [${restoringFile.value.filename}].`,
                    icon: 'success',
                    confirmButtonColor: '#e11d48',
                });
            },
            onError: (err) => {
                restoreSubmitting.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Restore Failed',
                    text: Object.values(err)[0] || 'Database restore error occurred.',
                });
            },
        }
    );
};

const deleteBackup = (backup) => {
    Swal.fire({
        title: 'Delete Backup Snapshot?',
        text: `Are you sure you want to permanently remove "${backup.filename}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(route('admin.settings.backups.destroy', backup.filename), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Backup removed from storage.',
                        showConfirmButton: false,
                        timer: 2500,
                    });
                },
            });
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Never';
    return new Date(dateStr).toLocaleDateString('en-PK', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};
</script>

<template>
    <Head title="Database Backups & Disaster Recovery | Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- 1. EXECUTIVE HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 tracking-tight">
                            Database Backups & Disaster Recovery
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            DATA SAFEGUARD
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Generate on-demand database snapshots, download offline archives, and restore marketplace records safely.
                    </p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <Link
                        :href="route('admin.settings.index')"
                        class="px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-50 border border-rose-200 text-slate-700 text-xs font-bold shadow-xs transition"
                    >
                        ⚙️ Platform Settings
                    </Link>

                    <button
                        type="button"
                        @click="createInstantBackup"
                        :disabled="creating"
                        class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 hover:from-emerald-700 hover:to-cyan-800 text-white text-xs font-bold shadow-lg shadow-emerald-950/20 transition-all active:scale-98 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span :class="{ 'animate-spin': creating }">💾</span>
                        <span>{{ creating ? 'Creating Snapshot...' : 'Create Instant Snapshot' }}</span>
                    </button>
                </div>
            </div>

            <!-- 2. DISASTER RECOVERY KPI RIBBON (4 LUXURY CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Backups -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Snapshots</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ stats.total_backups }}
                        </h3>
                        <p class="text-[10px] text-emerald-700 font-semibold mt-0.5">Stored in local vault</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
                        💾
                    </div>
                </div>

                <!-- Archive Footprint -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800">Archive Footprint</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-purple-950 mt-1">
                            {{ stats.total_size_formatted }}
                        </h3>
                        <p class="text-[10px] text-purple-700 font-semibold mt-0.5">Total disk space used</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shadow-xs">
                        📦
                    </div>
                </div>

                <!-- Last Backup Generated -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Last Backup</p>
                        <h3 class="text-sm sm:text-base font-serif font-bold text-slate-900 mt-1 line-clamp-1">
                            {{ stats.last_backup ? formatDate(stats.last_backup) : 'No backups yet' }}
                        </h3>
                        <p class="text-[10px] text-blue-700 font-semibold mt-0.5">Disaster safeguard status</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl shadow-xs">
                        ⏱️
                    </div>
                </div>

                <!-- Database Scope -->
                <div class="p-5 rounded-3xl bg-white border border-rose-100 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Database Tables</p>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold text-slate-900 mt-1">
                            {{ stats.database_tables_count }} Tables
                        </h3>
                        <p class="text-[10px] text-amber-700 font-semibold mt-0.5">Database: {{ stats.database_name }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl shadow-xs">
                        🗄️
                    </div>
                </div>
            </div>

            <!-- 3. BACKUP SNAPSHOTS LEDGER TABLE -->
            <div class="relative rounded-3xl bg-white border border-rose-100 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-rose-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-rose-50/30">
                    <div>
                        <h2 class="text-base font-serif font-bold text-slate-900">
                            🗃️ Available Backup Archives
                        </h2>
                        <p class="text-xs text-slate-400">Chronological list of all full-table database snapshots</p>
                    </div>

                    <span class="text-xs font-bold text-slate-600 bg-white px-3 py-1.5 rounded-xl border border-rose-100 shadow-xs self-start sm:self-auto">
                        Storage Path: <code class="font-mono text-[11px] text-rose-700">storage/app/backups/</code>
                    </span>
                </div>

                <div v-if="backups && backups.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-rose-50/50 border-b border-rose-100 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4">Backup Archive File</th>
                                <th class="py-3.5 px-4">Format</th>
                                <th class="py-3.5 px-4">File Size</th>
                                <th class="py-3.5 px-4 text-center">Tables Included</th>
                                <th class="py-3.5 px-4">Generated Timestamp</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50/80">
                            <tr v-for="b in backups" :key="b.filename" class="hover:bg-rose-50/30 transition-colors">
                                <!-- Filename -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-base shrink-0">
                                            📄
                                        </span>
                                        <div>
                                            <p class="font-mono font-bold text-slate-900 text-xs sm:text-sm">{{ b.filename }}</p>
                                            <p class="text-[10px] text-slate-400">{{ b.time_ago }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Format -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="b.extension === 'SQL' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-purple-100 text-purple-800 border border-purple-200'"
                                    >
                                        {{ b.extension === 'SQL' ? '🗄️ SQL DUMP' : '📑 JSON ARCHIVE' }}
                                    </span>
                                </td>

                                <!-- File Size -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono font-bold text-slate-800 text-xs">
                                    {{ b.size_formatted }}
                                </td>

                                <!-- Tables Count -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold text-slate-700">
                                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px]">
                                        ✓ {{ b.tables_count }} Tables
                                    </span>
                                </td>

                                <!-- Timestamp -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600">
                                    {{ formatDate(b.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Download -->
                                        <a
                                            :href="route('admin.settings.backups.download', b.filename)"
                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition flex items-center gap-1 cursor-pointer"
                                            title="Download to PC"
                                        >
                                            <span>📥</span>
                                            <span>Download</span>
                                        </a>

                                        <!-- Restore -->
                                        <button
                                            type="button"
                                            @click="openRestoreModal(b)"
                                            class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold text-xs transition flex items-center gap-1 cursor-pointer"
                                            title="Restore database from this snapshot"
                                        >
                                            <span>🔄</span>
                                            <span>Restore</span>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="deleteBackup(b)"
                                            class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 font-bold text-xs transition cursor-pointer"
                                            title="Delete backup"
                                        >
                                            <span>🗑️</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-16">
                    <AppEmptyState
                        icon="💾"
                        title="No database snapshots found"
                        description="Click 'Create Instant Snapshot' to safeguard all salon, user, booking, and payment records."
                    />
                </div>
            </div>

            <!-- 4. AUTOMATED BACKUP & DISASTER RECOVERY INSTRUCTIONS -->
            <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-xs space-y-4">
                <h3 class="text-base font-serif font-bold text-slate-900">
                    🛡️ Automated Disaster Recovery & Off-Site Storage
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span>⏱️</span>
                            <span>Automated Cron Scheduling</span>
                        </span>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            To automate daily snapshots at midnight, ensure your server cron runs <code class="font-mono bg-white px-1 py-0.5 rounded text-rose-600">php artisan schedule:run</code> every minute.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span>📦</span>
                            <span>CLI Terminal Backup</span>
                        </span>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Run snapshots directly on the CLI anytime using <code class="font-mono bg-white px-1 py-0.5 rounded text-purple-700">php artisan app:backup-database</code>.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span>☁️</span>
                            <span>Off-Site Download</span>
                        </span>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Always download regular copies of your snapshots to secure off-site cloud storage or local encrypted cold storage.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. SAFE RESTORE CONFIRMATION MODAL -->
        <div
            v-if="showRestoreModal && restoringFile"
            class="fixed inset-0 z-50 overflow-y-auto bg-onyx-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
            @click.self="closeRestoreModal"
        >
            <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-100 animate-in fade-in zoom-in duration-200">
                <div class="p-6 bg-amber-50/70 border-b border-amber-100 flex items-start gap-3">
                    <span class="text-3xl shrink-0">⚠️</span>
                    <div>
                        <h3 class="font-serif text-lg font-bold text-amber-950">Confirm Database Restoration</h3>
                        <p class="text-xs text-amber-800 mt-0.5">
                            You are about to restore the marketplace database from snapshot:
                        </p>
                        <p class="font-mono text-xs font-bold text-slate-900 mt-1 bg-white px-2 py-1 rounded-lg border border-amber-200 inline-block">
                            {{ restoringFile.filename }}
                        </p>
                    </div>
                </div>

                <div class="p-6 space-y-3 text-xs text-slate-600">
                    <p class="leading-relaxed">
                        <strong class="text-slate-900">Important Warning:</strong> Restoring this snapshot will replace current database tables with data from <span class="font-bold">{{ formatDate(restoringFile.created_at) }}</span>. Any changes made after this timestamp will be reverted.
                    </p>
                    <p class="text-slate-500">
                        Foreign key checks will be safely toggled during the batch transaction.
                    </p>
                </div>

                <div class="p-5 border-t border-rose-100 flex items-center justify-end gap-2 bg-slate-50">
                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 transition cursor-pointer"
                        @click="closeRestoreModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="restoreSubmitting"
                        @click="confirmRestore"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span>🔄</span>
                        <span>{{ restoreSubmitting ? 'Restoring Database...' : 'Proceed with Restoration' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
