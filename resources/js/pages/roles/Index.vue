<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">System Roles & Permissions</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">View access privileges and map authorization boundaries.</p>
            </div>
            <router-link 
                v-if="authStore.isSuperAdmin"
                to="/roles/create"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Role
            </router-link>
        </div>

        <!-- Roles Grid -->
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-pulse">
            <div v-for="i in 3" :key="i" class="h-48 bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-800/80 rounded-2xl"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
                v-for="role in roles" 
                :key="role.id" 
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between"
            >
                <div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-800 dark:text-white text-base tracking-tight">{{ role.name }}</h3>
                            <span v-if="isSystemRole(role)" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-100 dark:bg-slate-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/10 font-bold tracking-wider uppercase">System</span>
                        </div>
                        
                        <div class="flex items-center gap-1.5">
                            <!-- Edit -->
                            <router-link 
                                v-if="authStore.hasPermission('role.edit') && role.name !== 'Super Admin'"
                                :to="`/roles/${role.id}/edit`"
                                class="p-1 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                                title="Edit Role Permissions"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </router-link>

                            <!-- Delete -->
                            <button 
                                v-if="authStore.isSuperAdmin && !isSystemRole(role)"
                                @click="handleDelete(role)"
                                class="p-1 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-450 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                                title="Delete Role"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Permissions Badges -->
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold text-slate-550 dark:text-slate-500 uppercase tracking-wider">Assigned System Privileges:</p>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span 
                                v-if="role.name === 'Super Admin'"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20"
                            >
                                * Full System Access (All Permissions)
                            </span>
                            <template v-else-if="role.permissions && role.permissions.length > 0">
                                <span 
                                    v-for="perm in role.permissions" 
                                    :key="perm.id"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800/80"
                                >
                                    {{ perm.name }}
                                </span>
                            </template>
                            <span v-else class="text-xs text-slate-500 italic">No specific permissions assigned.</span>
                        </div>
                    </div>
                </div>
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
    name: 'RolesIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const roles = ref([]);
        const loading = ref(true);

        const fetchRoles = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/roles');
                roles.value = response.data.roles;
            } catch (error) {
                console.error('Failed to load roles', error);
            } finally {
                loading.value = false;
            }
        };

        const isSystemRole = (role) => {
            return ['Super Admin', 'School Admin', 'Teacher'].includes(role.name);
        };

        const handleDelete = async (role) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Role',
                message: `Are you sure you want to delete the role "${role.name}"? This will affect all users assigned to this role.`,
                type: 'danger',
                confirmText: 'Delete Role',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    await window.axios.delete(`/api/roles/${role.id}`);
                    toastStore.success('Role deleted successfully.');
                    fetchRoles();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete role.');
                }
            }
        };

        onMounted(() => {
            fetchRoles();
        });

        return {
            authStore,
            roles,
            loading,
            isSystemRole,
            handleDelete
        };
    }
}
</script>
