<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';
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
            sellers: 0,
            customers: 0,
            active: 0,
            inactive: 0,
        }),
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
    role: 'customer', // 'customer' | 'seller' | 'admin'
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    city: '',
    address: '',
    shop_name: '',
});

const openCreateModal = (defaultRole = 'customer') => {
    createForm.reset();
    createForm.clearErrors();
    createForm.role = defaultRole;
    createForm.is_active = true;
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
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
                title: 'User created successfully!',
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
    role: 'customer',
    is_active: true,
    city: '',
    address: '',
    shop_name: '',
});

const openEditModal = (user) => {
    editingUser.value = user;
    editForm.clearErrors();
    editForm.name = user.name || '';
    editForm.email = user.email || '';
    editForm.phone = user.phone || '';
    editForm.role = user.role || 'customer';
    editForm.is_active = Boolean(user.is_active);
    editForm.city = user.city || '';
    editForm.address = user.address || '';
    editForm.shop_name = user.shop_name || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingUser.value = null;
    editForm.reset();
    editForm.clearErrors();
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
// RESET PASSWORD MODAL
// ----------------------------------------------------
const showPasswordModal = ref(false);
const passwordUser = ref(null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const openPasswordModal = (user) => {
    passwordUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    passwordUser.value = null;
    passwordForm.reset();
    passwordForm.clearErrors();
};

const submitPasswordReset = () => {
    if (!passwordUser.value) return;
    passwordForm.post(route('admin.users.reset-password', passwordUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Password for ${passwordUser.value?.name} has been updated!`,
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

// ----------------------------------------------------
// TOGGLE STATUS & DELETE
// ----------------------------------------------------
const toggleUserStatus = (user) => {
    const actionText = user.is_active ? 'deactivate' : 'activate';
    Swal.fire({
        title: `Are you sure?`,
        text: `Do you want to ${actionText} ${user.name}'s account?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: user.is_active ? '#e11d48' : '#059669',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${actionText}!`,
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.users.toggle-status', user.id), {}, {
                preserveScroll: true,
            });
        }
    });
};

const deleteUser = (user) => {
    Swal.fire({
        title: 'Delete User Account?',
        text: `Permanently delete ${user.name}? This will remove all their marketplace records.`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.users.destroy', user.id), {
                preserveScroll: true,
            });
        }
    });
};

const getRoleBadge = (role) => {
    switch (role) {
        case 'admin':
            return { label: 'Admin Staff', color: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'seller':
            return { label: 'Verified Seller', color: 'bg-purple-50 text-purple-700 border-purple-200' };
        default:
            return { label: 'Buyer Customer', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    }
};
</script>

<template>
    <Head title="Marketplace Users | Admin Portal" />

    <AdminLayout>
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-black text-slate-900">
                        Marketplace Users & Staff
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Manage registered buyers, store sellers, and administrative staff accounts.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        @click="openCreateModal('customer')"
                        class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 px-4 py-2.5 text-xs font-bold transition-all cursor-pointer"
                    >
                        <span>＋</span>
                        <span>Add Customer</span>
                    </button>

                    <button
                        @click="openCreateModal('admin')"
                        class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider shadow-md shadow-rose-500/20 transition-all hover:scale-102 cursor-pointer"
                    >
                        <span>🛡️</span>
                        <span>Add Admin User</span>
                    </button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <button
                    @click="setRole('')"
                    type="button"
                    class="p-4 rounded-3xl bg-white border text-left transition-all cursor-pointer shadow-xs hover:shadow-md"
                    :class="roleFilter === '' ? 'border-rose-400 ring-2 ring-rose-100' : 'border-slate-200/80'"
                >
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Users</span>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.total }}</p>
                </button>

                <button
                    @click="setRole('customer')"
                    type="button"
                    class="p-4 rounded-3xl bg-white border text-left transition-all cursor-pointer shadow-xs hover:shadow-md"
                    :class="roleFilter === 'customer' ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-200/80'"
                >
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Customers (Buyers)</span>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.customers }}</p>
                </button>

                <button
                    @click="setRole('admin')"
                    type="button"
                    class="p-4 rounded-3xl bg-white border text-left transition-all cursor-pointer shadow-xs hover:shadow-md"
                    :class="roleFilter === 'admin' ? 'border-rose-400 ring-2 ring-rose-100' : 'border-slate-200/80'"
                >
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Admin Staff</span>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.admins }}</p>
                </button>

                <button
                    @click="setRole('seller')"
                    type="button"
                    class="p-4 rounded-3xl bg-white border text-left transition-all cursor-pointer shadow-xs hover:shadow-md"
                    :class="roleFilter === 'seller' ? 'border-purple-400 ring-2 ring-purple-100' : 'border-slate-200/80'"
                >
                    <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600">Store Sellers</span>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ stats.sellers }}</p>
                </button>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-3xl bg-white p-4 shadow-sm border border-slate-200/80 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="relative flex-1 w-full max-w-md">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by name, email, phone, city, or shop..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 text-slate-900"
                    />
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto overflow-x-auto">
                    <select
                        v-model="statusFilter"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 py-2 pl-3 pr-8 text-slate-700 focus:border-rose-500 focus:ring-0 cursor-pointer"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button
                        v-if="search || roleFilter || statusFilter"
                        @click="resetFilters"
                        class="px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-3xl bg-white shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-black uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-4 sm:px-6">User / Buyer</th>
                                <th class="py-3.5 px-4">Contact Info</th>
                                <th class="py-3.5 px-4">Role</th>
                                <th class="py-3.5 px-4">City / Address</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-500 text-white font-black flex items-center justify-center text-sm shadow-2xs shrink-0">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-xs">{{ user.name }}</p>
                                            <span v-if="user.shop_name" class="text-[10px] text-purple-600 font-bold block">
                                                🏪 {{ user.shop_name }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">ID: #{{ user.id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    <p class="font-medium text-slate-800">{{ user.email }}</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ user.phone || 'No phone' }}</p>
                                </td>

                                <td class="py-4 px-4">
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border"
                                        :class="getRoleBadge(user.role || 'customer').color"
                                    >
                                        {{ getRoleBadge(user.role || 'customer').label }}
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <p class="font-semibold text-slate-800">{{ user.city || 'Pakistan' }}</p>
                                    <p class="text-[11px] text-slate-400 truncate max-w-[180px]">{{ user.address || '-' }}</p>
                                </td>

                                <td class="py-4 px-4">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border transition cursor-pointer"
                                        :class="user.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        <span>{{ user.is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </td>

                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(user)"
                                            class="p-1.5 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                                            title="Edit user"
                                        >
                                            ✏️
                                        </button>

                                        <button
                                            @click="openPasswordModal(user)"
                                            class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 transition cursor-pointer"
                                            title="Reset password"
                                        >
                                            🔑
                                        </button>

                                        <button
                                            @click="deleteUser(user)"
                                            class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                            title="Delete user"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    No users found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.links && users.links.length > 3" class="p-4 border-t border-slate-100">
                    <AppPagination :links="users.links" />
                </div>
            </div>

            <!-- MODAL: CREATE USER -->
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h3 class="text-base font-bold text-slate-900">Create New Marketplace Account</h3>
                        <button @click="closeCreateModal" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Account Role *</label>
                            <select
                                v-model="createForm.role"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                            >
                                <option value="customer">Buyer Customer</option>
                                <option value="seller">Store Seller</option>
                                <option value="admin">Admin Staff</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Full Name *</label>
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email Address *</label>
                                <input
                                    v-model="createForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                                <input
                                    v-model="createForm.phone"
                                    type="text"
                                    placeholder="+92 300 1234567"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">City</label>
                                <input
                                    v-model="createForm.city"
                                    type="text"
                                    placeholder="Lahore / Karachi"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div v-if="createForm.role === 'seller'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Shop / Brand Name</label>
                            <input
                                v-model="createForm.shop_name"
                                type="text"
                                placeholder="Luxe Glow Store"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Shipping / Physical Address</label>
                            <input
                                v-model="createForm.address"
                                type="text"
                                placeholder="House #, Street, Area"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Password *</label>
                                <input
                                    v-model="createForm.password"
                                    type="password"
                                    required
                                    minlength="8"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Confirm Password *</label>
                                <input
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    required
                                    minlength="8"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow-sm cursor-pointer"
                            >
                                Create Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: EDIT USER -->
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-slate-100">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h3 class="text-base font-bold text-slate-900">Edit User Details</h3>
                        <button @click="closeEditModal" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Phone</label>
                                <input
                                    v-model="editForm.phone"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Role</label>
                                <select
                                    v-model="editForm.role"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                >
                                    <option value="customer">Buyer Customer</option>
                                    <option value="seller">Store Seller</option>
                                    <option value="admin">Admin Staff</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">City</label>
                                <input
                                    v-model="editForm.city"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Shop Name (If Seller)</label>
                                <input
                                    v-model="editForm.shop_name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Address</label>
                            <input
                                v-model="editForm.address"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                            />
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 bg-slate-900 hover:bg-rose-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider cursor-pointer"
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: RESET PASSWORD -->
            <div
                v-if="showPasswordModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="w-full max-w-md rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-slate-100">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Reset User Password</h3>
                            <p class="text-xs text-slate-500">{{ passwordUser?.name }} ({{ passwordUser?.email }})</p>
                        </div>
                        <button @click="closePasswordModal" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
                    </div>

                    <form @submit.prevent="submitPasswordReset" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">New Password (min 8 chars) *</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                minlength="8"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Confirm New Password *</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                minlength="8"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-900 focus:border-rose-500 focus:ring-0"
                            />
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="closePasswordModal"
                                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold uppercase tracking-wider cursor-pointer"
                            >
                                Set Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
