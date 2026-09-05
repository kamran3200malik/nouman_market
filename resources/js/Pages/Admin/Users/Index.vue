<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            admins: 0,
            artists: 0,
            customers: 0,
            active: 0,
            inactive: 0,
        }),
    },
    cities: {
        type: Array,
        default: () => [],
    },
});

// Filters
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(
        route('admin.users.index'),
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const setRole = (roleKey) => {
    roleFilter.value = roleKey;
    applyFilters();
};

const setStatus = (statusKey) => {
    statusFilter.value = statusKey;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    roleFilter.value = '';
    statusFilter.value = '';
    router.get(route('admin.users.index'));
};

// ----------------------------------------------------
// CREATE USER MODAL & FORM
// ----------------------------------------------------
const showCreateModal = ref(false);
const createForm = useForm({
    role: 'customer', // 'customer' | 'artist' | 'admin'
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    city_id: '',
    area_id: '',
    address: '',
    // Artist specific
    business_name: '',
    professional_type: 'beauty_salon',
    years_of_experience: 2,
    approval_status: 'approved',
    billing_model: 'commission',
    commission_rate: 10,
    home_service_available: false,
});

const selectedCityAreas = computed(() => {
    if (!createForm.city_id) return [];
    const city = props.cities.find(c => c.id === parseInt(createForm.city_id));
    return city?.areas || [];
});

const openCreateModal = (presetRole = 'customer') => {
    createForm.reset();
    createForm.clearErrors();
    createForm.role = presetRole;
    createForm.is_active = true;
    createForm.approval_status = 'approved';
    createForm.billing_model = 'commission';
    createForm.commission_rate = 10;
    createForm.years_of_experience = 2;
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `${createForm.role.toUpperCase()} account created successfully!`,
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

// ----------------------------------------------------
// EDIT USER MODAL & FORM
// ----------------------------------------------------
const showEditModal = ref(false);
const editingUser = ref(null);
const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    is_active: true,
    city_id: '',
    area_id: '',
    address: '',
    // Artist specific
    business_name: '',
    professional_type: '',
    approval_status: 'approved',
    billing_model: 'commission',
    commission_rate: 10,
});

const editCityAreas = computed(() => {
    if (!editForm.city_id) return [];
    const city = props.cities.find(c => c.id === parseInt(editForm.city_id));
    return city?.areas || [];
});

const openEditModal = (user) => {
    editingUser.value = user;
    editForm.clearErrors();
    editForm.name = user.name || '';
    editForm.email = user.email || '';
    editForm.phone = user.phone || '';
    editForm.is_active = !!user.is_active;
    editForm.address = user.address || '';

    // Find city ID matching user city name if any
    const matchedCity = props.cities.find(c => c.name?.toLowerCase() === user.city?.toLowerCase());
    editForm.city_id = matchedCity?.id || (user.artist_profile?.city_id || '');

    if (user.artist_profile) {
        editForm.business_name = user.artist_profile.business_name || '';
        editForm.professional_type = user.artist_profile.professional_type || 'beauty_salon';
        editForm.approval_status = user.artist_profile.approval_status || 'approved';
        editForm.billing_model = user.artist_profile.billing_model || 'commission';
        editForm.commission_rate = user.artist_profile.commission_rate || 10;
        editForm.area_id = user.artist_profile.area_id || '';
    } else {
        editForm.business_name = '';
        editForm.professional_type = '';
        editForm.approval_status = 'approved';
        editForm.billing_model = 'commission';
        editForm.commission_rate = 10;
        editForm.area_id = '';
    }

    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingUser.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingUser.value) return;
    editForm.put(route('admin.users.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'User updated successfully!',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

// ----------------------------------------------------
// RESET PASSWORD MODAL & FORM
// ----------------------------------------------------
const showPasswordModal = ref(false);
const targetUser = ref(null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});
const sendingResetEmail = ref(false);

const openPasswordModal = (user) => {
    targetUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    targetUser.value = null;
    passwordForm.reset();
};

const submitResetPassword = () => {
    if (!targetUser.value) return;
    passwordForm.post(route('admin.users.reset-password', targetUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Password updated for ${targetUser.value.name}!`,
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

const sendResetEmail = () => {
    if (!targetUser.value) return;
    sendingResetEmail.value = true;
    router.post(
        route('admin.users.send-reset-link', targetUser.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                sendingResetEmail.value = false;
                closePasswordModal();
            },
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Reset email sent to ${targetUser.value.email}!`,
                    showConfirmButton: false,
                    timer: 3000,
                });
            },
            onError: (errors) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Send Email',
                    text: errors?.error || 'Could not send reset email. Please check mail settings in .env.',
                });
            }
        }
    );
};

// ----------------------------------------------------
// TOGGLE STATUS & DELETE
// ----------------------------------------------------
const toggleUserStatus = (user) => {
    const nextState = !user.is_active;
    Swal.fire({
        title: `${nextState ? 'Activate' : 'Deactivate'} User?`,
        text: `Are you sure you want to ${nextState ? 'activate' : 'deactivate'} ${user.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: nextState ? '#10b981' : '#f59e0b',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Yes, ${nextState ? 'Activate' : 'Deactivate'}`
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.users.toggle-status', user.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `User status changed successfully!`,
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

const deleteUser = (user) => {
    Swal.fire({
        title: `Delete ${user.name}?`,
        text: "This action is permanent and will remove associated data.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete Account'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.users.destroy', user.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `User deleted successfully.`,
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

const getUserRole = (user) => {
    if (user.roles?.some(r => r.name === 'admin')) return 'admin';
    if (user.roles?.some(r => r.name === 'artist') || user.artist_profile) return 'artist';
    return 'customer';
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="System Users & Staff - Admin" />

    <AdminLayout>
        <div class="space-y-6 pb-12">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">System Users & Accounts</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Manage all platform users, create new Admin/Artist/Customer accounts, and handle password resets.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="openCreateModal('customer')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-pink-200 transition-all active:scale-[0.98]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Add New User</span>
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div
                    @click="setRole('')"
                    :class="[
                        'cursor-pointer p-4 rounded-2xl border transition-all duration-200',
                        roleFilter === '' ? 'bg-white border-rose-400 shadow-md ring-2 ring-rose-100' : 'bg-white border-slate-200/80 hover:border-slate-300'
                    ]"
                >
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Users</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.total }}</p>
                </div>

                <div
                    @click="setRole('admin')"
                    :class="[
                        'cursor-pointer p-4 rounded-2xl border transition-all duration-200',
                        roleFilter === 'admin' ? 'bg-indigo-50/70 border-indigo-400 shadow-md ring-2 ring-indigo-100' : 'bg-white border-slate-200/80 hover:border-indigo-200'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Admins</p>
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    </div>
                    <p class="text-2xl font-black text-indigo-900 mt-1">{{ stats.admins }}</p>
                </div>

                <div
                    @click="setRole('artist')"
                    :class="[
                        'cursor-pointer p-4 rounded-2xl border transition-all duration-200',
                        roleFilter === 'artist' ? 'bg-rose-50/70 border-rose-400 shadow-md ring-2 ring-rose-100' : 'bg-white border-slate-200/80 hover:border-rose-200'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Artists / Salons</p>
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </div>
                    <p class="text-2xl font-black text-rose-900 mt-1">{{ stats.artists }}</p>
                </div>

                <div
                    @click="setRole('customer')"
                    :class="[
                        'cursor-pointer p-4 rounded-2xl border transition-all duration-200',
                        roleFilter === 'customer' ? 'bg-emerald-50/70 border-emerald-400 shadow-md ring-2 ring-emerald-100' : 'bg-white border-slate-200/80 hover:border-emerald-200'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Customers</p>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <p class="text-2xl font-black text-emerald-900 mt-1">{{ stats.customers }}</p>
                </div>

                <div
                    @click="setStatus('active')"
                    :class="[
                        'cursor-pointer p-4 rounded-2xl border transition-all duration-200',
                        statusFilter === 'active' ? 'bg-teal-50/70 border-teal-400 shadow-md ring-2 ring-teal-100' : 'bg-white border-slate-200/80 hover:border-teal-200'
                    ]"
                >
                    <p class="text-xs font-semibold text-teal-600 uppercase tracking-wider">Active</p>
                    <p class="text-2xl font-black text-teal-900 mt-1">{{ stats.active }}</p>
                </div>

                <div
                    @click="setStatus('inactive')"
                    :class="[
                        'cursor-pointer p-4 rounded-2xl border transition-all duration-200',
                        statusFilter === 'inactive' ? 'bg-amber-50/70 border-amber-400 shadow-md ring-2 ring-amber-100' : 'bg-white border-slate-200/80 hover:border-amber-200'
                    ]"
                >
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Inactive</p>
                    <p class="text-2xl font-black text-amber-900 mt-1">{{ stats.inactive }}</p>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
                <!-- Search Input -->
                <div class="relative w-full md:w-80">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search name, email, phone..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Role Tabs -->
                <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl w-full md:w-auto overflow-x-auto">
                    <button
                        @click="setRole('')"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', roleFilter === '' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                        All Roles
                    </button>
                    <button
                        @click="setRole('admin')"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', roleFilter === 'admin' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                        🛡️ Admins
                    </button>
                    <button
                        @click="setRole('artist')"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', roleFilter === 'artist' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                        💇 Artists / Salons
                    </button>
                    <button
                        @click="setRole('customer')"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', roleFilter === 'customer' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
                    >
                        👤 Customers
                    </button>
                </div>

                <!-- Clear / Reset -->
                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 text-xs font-semibold bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-all"
                    >
                        Filter
                    </button>
                    <button
                        v-if="search || roleFilter || statusFilter"
                        @click="resetFilters"
                        class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-all"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">User</th>
                                <th class="py-3.5 px-4">Role</th>
                                <th class="py-3.5 px-4">Contact</th>
                                <th class="py-3.5 px-4">Location</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Created</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                                <!-- User Info -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-rose-400 to-pink-500 flex items-center justify-center text-white font-bold text-sm shadow-sm overflow-hidden flex-shrink-0">
                                            <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover" />
                                            <span v-else>{{ user.name?.charAt(0)?.toUpperCase() || 'U' }}</span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                                <span>{{ user.name }}</span>
                                                <span v-if="user.id === $page.props.auth?.user?.id" class="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-md font-semibold">You</span>
                                            </div>
                                            <div class="text-xs text-slate-400">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col items-start gap-1">
                                        <span
                                            v-if="getUserRole(user) === 'admin'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                        >
                                            🛡️ Super Admin
                                        </span>
                                        <span
                                            v-else-if="getUserRole(user) === 'artist'"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200"
                                        >
                                            💇 Artist / Salon
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            👤 Customer
                                        </span>

                                        <span v-if="user.artist_profile" class="text-[11px] font-medium text-slate-500">
                                            {{ user.artist_profile.business_name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td class="py-3.5 px-4">
                                    <div class="text-xs font-medium text-slate-900">{{ user.phone || 'No phone' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ user.email_verified_at ? '✓ Email Verified' : 'Unverified' }}</div>
                                </td>

                                <!-- Location -->
                                <td class="py-3.5 px-4">
                                    <div class="text-xs text-slate-700">
                                        <span v-if="user.city || user.artist_profile?.city_id">{{ user.city || 'Assigned' }}</span>
                                        <span v-else class="text-slate-400">—</span>
                                    </div>
                                    <div v-if="user.area" class="text-[11px] text-slate-400">{{ user.area }}</div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        :title="user.is_active ? 'Click to deactivate' : 'Click to activate'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-all"
                                        :class="user.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ user.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                <!-- Created At -->
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ formatDate(user.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Reset Password Button -->
                                        <button
                                            @click="openPasswordModal(user)"
                                            title="Reset Password"
                                            class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </button>

                                        <!-- Edit User Button -->
                                        <button
                                            @click="openEditModal(user)"
                                            title="Edit User"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Direct Link if Artist -->
                                        <Link
                                            v-if="user.artist_profile"
                                            :href="route('admin.artists.show', user.artist_profile.id)"
                                            title="View Salon Profile"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>

                                        <!-- Delete Button -->
                                        <button
                                            v-if="user.id !== $page.props.auth?.user?.id"
                                            @click="deleteUser(user)"
                                            title="Delete User"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="font-medium text-slate-600">No users found</p>
                                        <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or click "+ Add New User".</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Showing {{ users.from || 0 }} to {{ users.to || 0 }} of {{ users.total }} records
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in users.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg transition-all',
                                link.active ? 'bg-rose-500 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- CREATE USER MODAL -->
        <!-- ======================================================= -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Create New System Account</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Choose an account role and complete profile details</p>
                    </div>
                    <button @click="closeCreateModal" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 space-y-5">
                    <!-- Role Selection Tabs -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Account Role</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                type="button"
                                @click="createForm.role = 'customer'"
                                :class="[
                                    'p-3.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5',
                                    createForm.role === 'customer' ? 'border-emerald-500 bg-emerald-50/80 text-emerald-900 ring-2 ring-emerald-100 font-bold' : 'border-slate-200 hover:border-slate-300 text-slate-700'
                                ]"
                            >
                                <span class="text-2xl">👤</span>
                                <span class="text-xs font-semibold">Customer</span>
                            </button>

                            <button
                                type="button"
                                @click="createForm.role = 'artist'"
                                :class="[
                                    'p-3.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5',
                                    createForm.role === 'artist' ? 'border-rose-500 bg-rose-50/80 text-rose-900 ring-2 ring-rose-100 font-bold' : 'border-slate-200 hover:border-slate-300 text-slate-700'
                                ]"
                            >
                                <span class="text-2xl">💇</span>
                                <span class="text-xs font-semibold">Artist / Salon</span>
                            </button>

                            <button
                                type="button"
                                @click="createForm.role = 'admin'"
                                :class="[
                                    'p-3.5 rounded-2xl border text-center transition-all flex flex-col items-center gap-1.5',
                                    createForm.role === 'admin' ? 'border-indigo-500 bg-indigo-50/80 text-indigo-900 ring-2 ring-indigo-100 font-bold' : 'border-slate-200 hover:border-slate-300 text-slate-700'
                                ]"
                            >
                                <span class="text-2xl">🛡️</span>
                                <span class="text-xs font-semibold">Admin Staff</span>
                            </button>
                        </div>
                    </div>

                    <!-- Common Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="e.g. Sarah Jenkins"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                            <p v-if="createForm.errors.name" class="text-xs text-rose-600 mt-1">{{ createForm.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address *</label>
                            <input
                                v-model="createForm.email"
                                type="email"
                                required
                                placeholder="user@example.com"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                            <p v-if="createForm.errors.email" class="text-xs text-rose-600 mt-1">{{ createForm.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="createForm.phone"
                                type="text"
                                placeholder="03001234567"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                            <p v-if="createForm.errors.phone" class="text-xs text-rose-600 mt-1">{{ createForm.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Password * (min 8 chars)</label>
                            <input
                                v-model="createForm.password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                            <p v-if="createForm.errors.password" class="text-xs text-rose-600 mt-1">{{ createForm.errors.password }}</p>
                        </div>
                    </div>

                    <!-- ARTIST SPECIFIC FIELDS -->
                    <div v-if="createForm.role === 'artist'" class="p-4 bg-rose-50/50 rounded-2xl border border-rose-100 space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-rose-700 uppercase tracking-wider">
                            <span>💇 Salon & Artist Profile Details</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Business / Salon Name *</label>
                                <input
                                    v-model="createForm.business_name"
                                    type="text"
                                    required
                                    placeholder="e.g. Glow & Grace Studio"
                                    class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                />
                                <p v-if="createForm.errors.business_name" class="text-xs text-rose-600 mt-1">{{ createForm.errors.business_name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Category / Specialty *</label>
                                <select
                                    v-model="createForm.professional_type"
                                    class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                >
                                    <option value="beauty_salon">Beauty Salon</option>
                                    <option value="makeup_artist">Makeup Artist</option>
                                    <option value="bridal_makeup_artist">Bridal Makeup Artist</option>
                                    <option value="hair_stylist">Hair Stylist</option>
                                    <option value="nail_artist">Nail Artist</option>
                                    <option value="facial_specialist">Facial Specialist</option>
                                    <option value="mehndi_artist">Mehndi / Henna Artist</option>
                                    <option value="spa">Spa & Wellness</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Approval Status</label>
                                <select
                                    v-model="createForm.approval_status"
                                    class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                >
                                    <option value="approved">Approved (Live immediately)</option>
                                    <option value="pending">Pending Review</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Commission Rate (%)</label>
                                <input
                                    v-model.number="createForm.commission_rate"
                                    type="number"
                                    min="0"
                                    max="100"
                                    placeholder="10"
                                    class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Location Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">City</label>
                            <select
                                v-model="createForm.city_id"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            >
                                <option value="">Select City (Optional)</option>
                                <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <div v-if="selectedCityAreas.length > 0">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Area / Neighborhood</label>
                            <select
                                v-model="createForm.area_id"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            >
                                <option value="">Select Area (Optional)</option>
                                <option v-for="a in selectedCityAreas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <div :class="selectedCityAreas.length > 0 ? 'col-span-2' : ''">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Street Address</label>
                            <input
                                v-model="createForm.address"
                                type="text"
                                placeholder="Street, Plaza, or Sector"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                        </div>
                    </div>

                    <!-- Status Toggle -->
                    <div class="flex items-center gap-3 pt-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="createForm.is_active" class="sr-only peer" />
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            <span class="ml-3 text-sm font-semibold text-slate-700">Account Active</span>
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm transition-all disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Creating...' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- EDIT USER MODAL -->
        <!-- ======================================================= -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Edit User Details</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Editing: {{ editingUser?.name }}</p>
                    </div>
                    <button @click="closeEditModal" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address *</label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                            <input
                                v-model="editForm.phone"
                                type="text"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">City</label>
                            <select
                                v-model="editForm.city_id"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            >
                                <option value="">Select City</option>
                                <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Artist specific edit fields -->
                    <div v-if="editingUser?.artist_profile" class="p-4 bg-rose-50/50 rounded-2xl border border-rose-100 space-y-3">
                        <p class="text-xs font-bold text-rose-700 uppercase tracking-wider">Salon Settings</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Business Name</label>
                                <input
                                    v-model="editForm.business_name"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Approval Status</label>
                                <select
                                    v-model="editForm.approval_status"
                                    class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                >
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm transition-all disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- RESET / CHANGE PASSWORD MODAL -->
        <!-- ======================================================= -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-amber-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg font-bold">
                            🔑
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Reset Password</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ targetUser?.name }} ({{ targetUser?.email }})</p>
                        </div>
                    </div>
                    <button @click="closePasswordModal" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Method 1: Set new password directly -->
                    <form @submit.prevent="submitResetPassword" class="space-y-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Option 1: Set New Password Directly</p>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">New Password (min 8 chars) *</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                placeholder="Enter new password"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                            />
                            <p v-if="passwordForm.errors.password" class="text-xs text-rose-600 mt-1">{{ passwordForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password *</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                placeholder="Re-type new password"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 text-sm font-bold rounded-xl shadow-sm transition-all disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password Now' }}
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="relative flex items-center justify-center">
                        <div class="border-t border-slate-200 w-full"></div>
                        <span class="bg-white px-3 text-xs font-semibold text-slate-400 uppercase">Or</span>
                    </div>

                    <!-- Method 2: Send reset link via email -->
                    <div class="space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Option 2: Email Password Reset Link</p>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Sends an automated email to <strong class="text-slate-800">{{ targetUser?.email }}</strong> with a secure link to reset their own password.
                        </p>
                        <button
                            type="button"
                            @click="sendResetEmail"
                            :disabled="sendingResetEmail"
                            class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl border border-slate-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>{{ sendingResetEmail ? 'Sending Email...' : 'Send Reset Link Email' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
