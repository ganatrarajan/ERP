<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">School Payment Settings</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manage online fee payment module activation across all schools.</p>
            </div>
        </div>

        <!-- Schools Payment Config List -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-72">
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search schools..." 
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="p-12 flex flex-col items-center justify-center space-y-3">
                <div class="w-8 h-8 rounded-full border-2 border-indigo-600 border-t-transparent animate-spin"></div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Loading school configurations...</p>
            </div>

            <!-- Content Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">
                            <th class="px-6 py-4">School Name</th>
                            <th class="px-6 py-4">School Code</th>
                            <th class="px-6 py-4">Module Enabled</th>
                            <th class="px-6 py-4">Gateway Configured</th>
                            <th class="px-6 py-4">Mode</th>
                            <th class="px-6 py-4">Gateway Status</th>
                            <th class="px-6 py-4">Last Updated</th>
                            <th class="px-6 py-4 text-center">Toggle Access</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-850">
                        <tr v-for="school in filteredSchools" :key="school.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">{{ school.name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-350 border border-slate-200 dark:border-slate-700 font-bold">
                                    {{ school.school_code }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border',
                                    school.module_enabled 
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' 
                                        : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                                ]">
                                    {{ school.module_enabled ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ school.gateway_name }}</td>
                            <td class="px-6 py-4">
                                <span v-if="school.mode" :class="[
                                    'inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider',
                                    school.mode === 'live' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                                ]">
                                    {{ school.mode }}
                                </span>
                                <span v-else class="text-slate-400 dark:text-slate-600">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="school.gateway_name !== 'Not Configured'" :class="[
                                    'inline-flex items-center gap-1 text-[11px] font-bold',
                                    school.active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-450'
                                ]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', school.active ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                                    {{ school.active ? 'Active' : 'Inactive' }}
                                </span>
                                <span v-else class="text-slate-400 dark:text-slate-600">—</span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ school.last_updated || 'Never' }}</td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="togglePaymentModule(school)"
                                    :disabled="togglingId === school.id"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all border shadow-sm active:scale-95 disabled:opacity-50',
                                        school.module_enabled 
                                            ? 'bg-rose-600 hover:bg-rose-700 text-white border-rose-600 hover:border-rose-700' 
                                            : 'bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-600 hover:border-indigo-700'
                                    ]"
                                >
                                    {{ togglingId === school.id ? 'Updating...' : (school.module_enabled ? 'Disable Online Fee' : 'Enable Online Fee') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredSchools.length === 0">
                            <td colspan="8" class="text-center py-8 text-slate-500 dark:text-slate-400">No schools matching your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'SchoolPaymentSettings',
    setup() {
        const toastStore = useToastStore();
        const schools = ref([]);
        const loading = ref(true);
        const search = ref('');
        const togglingId = ref(null);

        const fetchSchools = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/super-admin/school-payment-settings');
                schools.value = response.data.schools;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load school payment settings.');
            } finally {
                loading.value = false;
            }
        };

        const togglePaymentModule = async (school) => {
            togglingId.value = school.id;
            const targetState = !school.module_enabled;
            try {
                const response = await window.axios.post(`/api/super-admin/school-payment-settings/${school.id}/toggle`, {
                    enabled: targetState
                });
                school.module_enabled = targetState;
                toastStore.success(response.data.message);
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to update payment module status.');
            } finally {
                togglingId.value = null;
            }
        };

        const filteredSchools = computed(() => {
            if (!search.value) return schools.value;
            const query = search.value.toLowerCase();
            return schools.value.filter(school => 
                school.name.toLowerCase().includes(query) || 
                school.school_code.toLowerCase().includes(query)
            );
        });

        onMounted(() => {
            fetchSchools();
        });

        return {
            schools,
            loading,
            search,
            togglingId,
            filteredSchools,
            togglePaymentModule
        };
    }
}
</script>
