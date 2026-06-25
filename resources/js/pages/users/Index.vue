<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">User Directories</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">View and manage accounts, teachers, and system administrators.</p>
            </div>
            <router-link 
                v-if="authStore.hasPermission('user.create')"
                to="/users/create"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Account
            </router-link>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="users.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                No accounts found in this directory.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-555 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Account Name</th>
                            <th class="p-4">Mobile</th>
                            <th class="p-4">Associated School</th>
                            <th class="p-4">Designation Role</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Last Active</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-705 dark:text-slate-300">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold uppercase text-xs">
                                        {{ user.name.substring(0, 2) }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                                            {{ user.name }}
                                            <span v-if="user.id === authStore.user?.id" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700/60 font-semibold uppercase">You</span>
                                        </h4>
                                        <p class="text-xs text-slate-500">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-300">
                                {{ user.mobile || 'N/A' }}
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-300">
                                <span v-if="user.school" class="font-semibold text-slate-700 dark:text-slate-200">{{ user.school.name }}</span>
                                <span v-else class="text-indigo-600 dark:text-indigo-400 text-xs font-bold tracking-wide uppercase">SaaS Administration</span>
                            </td>
                            <td class="p-4">
                                <span 
                                    v-for="role in user.roles" 
                                    :key="role.id"
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold mr-1 uppercase',
                                        role.name === 'Super Admin' ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-150 dark:border-indigo-500/20' : '',
                                        role.name === 'School Admin' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-150 dark:border-amber-500/20' : '',
                                        role.name === 'Teacher' ? 'bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-150 dark:border-sky-500/20' : '',
                                    ]"
                                >
                                    {{ role.name }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-bold capitalize',
                                    user.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                ]">
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="p-4 text-xs text-slate-500">
                                {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit -->
                                    <router-link 
                                        v-if="authStore.hasPermission('user.edit')"
                                        :to="`/users/${user.id}/edit`"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-250 dark:border-slate-700/60 transition-colors"
                                        title="Edit Account"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </router-link>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('user.delete') && user.id !== authStore.user?.id"
                                        @click="handleDelete(user)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Account"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'UsersIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const users = ref([]);
        const loading = ref(true);

        const fetchUsers = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/users');
                users.value = response.data.users;
            } catch (error) {
                console.error(error);
            } finally {
                loading.value = false;
            }
        };

        const handleDelete = async (user) => {
            const confirmed = await confirmStore.show({
                title: 'Delete User Account',
                message: `Are you sure you want to delete the user account: "${user.name}"? This action cannot be undone.`,
                type: 'danger',
                confirmText: 'Delete Account',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    await window.axios.delete(`/api/users/${user.id}`);
                    toastStore.success('User account deleted successfully.');
                    fetchUsers();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete user.');
                }
            }
        };

        onMounted(() => {
            fetchUsers();
        });

        return {
            authStore,
            users,
            loading,
            handleDelete
        };
    }
}
</script>
