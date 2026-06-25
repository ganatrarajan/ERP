<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">School Directories</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">View and manage all registered school tenants in the system.</p>
            </div>
            <router-link 
                v-if="authStore.hasPermission('school.create')"
                to="/schools/create"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Register School
            </router-link>
        </div>

        <!-- Schools Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="schools.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                No schools found in the database.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">School Info</th>
                            <th class="p-4">Contact</th>
                            <th class="p-4">Total Users</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="school in schools" :key="school.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-550 dark:text-slate-400 font-bold overflow-hidden">
                                        <img v-if="school.logo" :src="school.logo" class="object-cover w-full h-full" />
                                        <span v-else>{{ school.name.substring(0, 2).toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white">{{ school.name }}</h4>
                                        <p class="text-xs text-slate-500 max-w-[200px] truncate" :title="school.address">{{ school.address || 'No address specified' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="space-y-0.5">
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ school.email }}</p>
                                    <p class="text-xs text-slate-505 dark:text-slate-500">{{ school.phone || 'N/A' }}</p>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700/50">
                                    {{ school.users_count || 0 }} Users
                                </span>
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-bold capitalize',
                                    school.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                ]">
                                    {{ school.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Login as School -->
                                    <button 
                                        @click="handleImpersonate(school)"
                                        class="px-2.5 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/20 text-xs font-bold rounded-lg transition-all flex items-center gap-1 active:scale-95"
                                        title="Impersonate School Admin"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Login as School
                                    </button>
 
                                    <!-- Modules -->
                                    <button 
                                        v-if="authStore.isSuperAdmin"
                                        @click="openModuleModal(school)"
                                        class="px-2.5 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 text-xs font-bold rounded-lg transition-all flex items-center gap-1 active:scale-95"
                                        title="Manage Modules"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path></svg>
                                        Modules
                                    </button>
 
                                    <!-- Edit -->
                                    <router-link 
                                        v-if="authStore.hasPermission('school.edit')"
                                        :to="`/schools/${school.id}/edit`"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-250 dark:border-slate-700/60 transition-colors"
                                        title="Edit School"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </router-link>
 
                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('school.delete')"
                                        @click="handleDelete(school)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete School"
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
 
        <!-- Modules Management Modal -->
        <div v-if="moduleModalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl animate-fade-in">
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white">Manage Modules</h3>
                        <p class="text-xs text-slate-500 mt-1">Configure active features for <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ selectedSchool?.name }}</span></p>
                    </div>
                    <button @click="closeModuleModal" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Modal Body (Module checkboxes) -->
                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                    <div v-if="loadingModules" class="space-y-3 animate-pulse">
                        <div v-for="i in 4" :key="i" class="h-14 bg-slate-100 dark:bg-slate-800/50 rounded-xl"></div>
                    </div>
                    <div v-else class="grid grid-cols-1 gap-3">
                        <div 
                            v-for="module in availableModules" 
                            :key="module.id"
                            @click="toggleModule(module.id)"
                            class="flex items-start gap-3.5 p-4 rounded-xl border cursor-pointer select-none transition-all duration-205"
                            :class="[
                                selectedModuleIds.includes(module.id)
                                    ? 'bg-indigo-50/45 dark:bg-indigo-950/15 border-indigo-500/30 dark:border-indigo-500/35 text-indigo-950 dark:text-white'
                                    : 'bg-slate-50/50 dark:bg-slate-950/20 border-slate-200 dark:border-slate-800/70 hover:border-slate-350 dark:hover:border-slate-700 text-slate-750 dark:text-slate-300'
                            ]"
                        >
                            <!-- Checkbox Input -->
                            <div class="flex items-center h-5 mt-0.5">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedModuleIds.includes(module.id)"
                                    @click.stop="toggleModule(module.id)"
                                    class="h-4.5 w-4.5 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-indigo-600 focus:ring-indigo-500/20 focus:ring-offset-slate-900"
                                />
                            </div>
                            
                            <!-- Icon / Detail -->
                            <div class="flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-lg">{{ getModuleEmoji(module.slug) }}</span>
                                    <span class="text-sm font-bold">{{ module.name }}</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-normal">{{ module.description || 'Access to school module features.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-6 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/20 flex items-center justify-end gap-3">
                    <button 
                        @click="closeModuleModal"
                        class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-white bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 rounded-xl transition-all"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveSchoolModules"
                        :disabled="savingModules"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/15 transition-all disabled:opacity-50"
                    >
                        <span v-if="savingModules">Saving changes...</span>
                        <span v-else>Save configuration</span>
                    </button>
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
import { useRouter } from 'vue-router';

export default {
    name: 'SchoolsIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const router = useRouter();

        const schools = ref([]);
        const loading = ref(true);

        const moduleModalOpen = ref(false);
        const selectedSchool = ref(null);
        const availableModules = ref([]);
        const selectedModuleIds = ref([]);
        const loadingModules = ref(false);
        const savingModules = ref(false);

        const getModuleEmoji = (slug) => {
            const emojis = {
                students: '🎓',
                teachers: '👨‍🏫',
                attendance: '📅',
                homework: '📝',
                fees: '💵',
                exams: '✍️',
                library: '📚',
                transport: '🚌',
                hostel: '🏢',
                reports: '📊',
                settings: '⚙️'
            };
            return emojis[slug] || '📁';
        };

        const fetchSchools = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/schools');
                schools.value = response.data.schools;
            } catch (error) {
                console.error(error);
            } finally {
                loading.value = false;
            }
        };

        const openModuleModal = async (school) => {
            selectedSchool.value = school;
            moduleModalOpen.value = true;
            loadingModules.value = true;
            selectedModuleIds.value = [];
            try {
                const response = await window.axios.get(`/api/schools/${school.id}/modules`);
                availableModules.value = response.data.modules;
                selectedModuleIds.value = response.data.modules
                    .filter(m => m.is_active)
                    .map(m => m.id);
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load school modules.');
                closeModuleModal();
            } finally {
                loadingModules.value = false;
            }
        };

        const closeModuleModal = () => {
            moduleModalOpen.value = false;
            selectedSchool.value = null;
            availableModules.value = [];
            selectedModuleIds.value = [];
        };

        const toggleModule = (id) => {
            const index = selectedModuleIds.value.indexOf(id);
            if (index > -1) {
                selectedModuleIds.value.splice(index, 1);
            } else {
                selectedModuleIds.value.push(id);
            }
        };

        const saveSchoolModules = async () => {
            savingModules.value = true;
            try {
                await window.axios.put(`/api/schools/${selectedSchool.value.id}/modules`, {
                    module_ids: selectedModuleIds.value
                });
                toastStore.success('School modules configuration updated successfully.');
                closeModuleModal();
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to save modules configuration.');
            } finally {
                savingModules.value = false;
            }
        };

        const handleDelete = async (school) => {
            const confirmed = await confirmStore.show({
                title: 'Delete School',
                message: `Are you sure you want to delete "${school.name}"? This will permanently delete all associated users and records!`,
                type: 'danger',
                confirmText: 'Delete School',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    await window.axios.delete(`/api/schools/${school.id}`);
                    toastStore.success('School deleted successfully.');
                    fetchSchools();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete school.');
                }
            }
        };

        const handleImpersonate = async (school) => {
            const confirmed = await confirmStore.show({
                title: 'Impersonate School Admin',
                message: `Do you want to log in and manage "${school.name}" as a School Admin?`,
                type: 'info',
                confirmText: 'Login as School',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    const response = await window.axios.post(`/api/impersonate/login/${school.id}`);
                    authStore.user = response.data.user;
                    authStore.impersonating = response.data.impersonating;
                    authStore.activeModules = response.data.active_modules || [];
                    toastStore.success(response.data.message);
                    router.push({ name: 'dashboard' });
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to login as School Admin.');
                }
            }
        };

        onMounted(() => {
            fetchSchools();
        });

        return {
            authStore,
            schools,
            loading,
            moduleModalOpen,
            selectedSchool,
            availableModules,
            selectedModuleIds,
            loadingModules,
            savingModules,
            getModuleEmoji,
            openModuleModal,
            closeModuleModal,
            toggleModule,
            saveSchoolModules,
            handleDelete,
            handleImpersonate
        };
    }
}
</script>
