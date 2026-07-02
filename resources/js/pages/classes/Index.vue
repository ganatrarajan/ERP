<template>
    <div class="space-y-6">
        <!-- Settings Control Header -->
        <SettingsHeader />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-slate-800 dark:text-white tracking-tight">Classes</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure class rooms, grades, and link them to active academic sessions.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('class.create') && isCurrentYear"
                @click="openModal()"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow shadow-indigo-600/10 transition-all flex items-center gap-1.5 cursor-pointer border-none"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Class
            </button>
        </div>

        <!-- Filters Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Search input -->
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search class name..." 
                        class="w-full pl-9 pr-4 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    />
                </div>

                <!-- Session Select and Advanced filter toggle -->
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Session:</label>
                    <select 
                        v-model="selectedSessionId"
                        @change="fetchClasses"
                        class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none cursor-pointer"
                    >
                        <option value="">All Academic Years</option>
                        <option v-for="year in academicYears" :key="year.id" :value="year.id">
                            {{ year.title }} <span v-if="year.is_current">(Current)</span>
                        </option>
                    </select>

                    <button 
                        @click="showAdvancedFilters = !showAdvancedFilters"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-800 rounded-xl text-xs text-slate-600 dark:text-slate-350 font-bold flex items-center gap-1.5 transition-colors cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filters
                    </button>
                </div>
            </div>

            <!-- Collapsible Advanced Filters Drawer -->
            <div v-show="showAdvancedFilters" class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap gap-4 items-center">
                <!-- Status Filter -->
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status:</label>
                    <select 
                        v-model="filterStatus"
                        class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none cursor-pointer"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>

                <button 
                    @click="searchQuery = ''; filterStatus = '';"
                    class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-955 text-xs font-bold text-slate-500 dark:text-slate-450 border border-slate-200 dark:border-slate-800/80 rounded-xl transition-all cursor-pointer"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Table Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="sortedClasses.length === 0" class="p-12 text-center text-slate-500 bg-slate-50/20 dark:bg-slate-900/10">
                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <h4 class="font-bold text-slate-700 dark:text-slate-300">No Classes Found</h4>
                <p class="text-xs text-slate-400 mt-1">Refine your filters, search queries, or add a class to begin.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60 select-none">
                            <th class="p-4 pl-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-850" @click="sortBy('name')">
                                <span class="flex items-center gap-1">
                                    Class Name
                                    <span v-if="sortKey === 'name'">{{ sortDesc ? '↓' : '↑' }}</span>
                                </span>
                            </th>
                            <th class="p-4">Description</th>
                            <th class="p-4">Academic Session</th>
                            <th class="p-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-850" @click="sortBy('status')">
                                <span class="flex items-center gap-1">
                                    Status
                                    <span v-if="sortKey === 'status'">{{ sortDesc ? '↓' : '↑' }}</span>
                                </span>
                            </th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="cls in sortedClasses" :key="cls.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-850 dark:text-white">
                                {{ cls.name }}
                            </td>
                            <td class="p-4 text-xs text-slate-500 dark:text-slate-405">
                                {{ cls.description || 'No description provided.' }}
                            </td>
                            <td class="p-4 font-medium text-xs">
                                {{ cls.academic_year?.title || 'N/A' }}
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                    cls.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ cls.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit -->
                                    <button 
                                        v-if="authStore.hasPermission('class.edit') && isCurrentYear"
                                        @click="openModal(cls)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
                                        title="Edit Class"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('class.delete') && isCurrentYear"
                                        @click="handleDelete(cls)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-455 border border-rose-500/20 rounded-lg transition-colors cursor-pointer"
                                        title="Delete Class"
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

        <!-- Class Form Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity animate-[fadeIn_0.2s_ease-out]">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Class' : 'Create Class' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors border-none bg-transparent cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="p-6 space-y-4">
                    <!-- Academic Year -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Academic Year</label>
                        <select 
                            v-model="form.academic_year_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all cursor-pointer"
                        >
                            <option value="" disabled>Select Academic Year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current)</span>
                            </option>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Class Name</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            required 
                            placeholder="e.g. Class 1"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="2"
                            placeholder="Brief details about the class"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all resize-none"
                        ></textarea>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Status</label>
                        <select 
                            v-model="form.status" 
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all cursor-pointer"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Error Messages -->
                    <div v-if="errors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                        {{ errors }}
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all border-none bg-transparent cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1 cursor-pointer border-none"
                        >
                            <span v-if="saving">Saving...</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';
import SettingsHeader from '../../components/SettingsHeader.vue';

export default {
    name: 'ClassesIndex',
    components: { SettingsHeader },
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        
        const classes = ref([]);
        const academicYears = ref([]);
        const selectedSessionId = ref('');
        const loading = ref(true);
        const modalOpen = ref(false);
        const editingId = ref(null);
        const saving = ref(false);
        const errors = ref('');

        // Search and Filters State
        const searchQuery = ref('');
        const filterStatus = ref('');
        const showAdvancedFilters = ref(false);

        // Sorting state
        const sortKey = ref('name');
        const sortDesc = ref(false);
        
        const isCurrentYear = computed(() => {
            if (!selectedSessionId.value) return true;
            const selected = academicYears.value.find(y => y.id === selectedSessionId.value);
            return selected ? !!selected.is_current : false;
        });

        const form = ref({
            academic_year_id: '',
            name: '',
            description: '',
            status: 'active'
        });

        const fetchAcademicYears = async () => {
            try {
                const response = await window.axios.get('/api/academic-years');
                academicYears.value = response.data.academic_years;
                
                const current = academicYears.value.find(y => y.is_current);
                if (current) {
                    selectedSessionId.value = current.id;
                    form.value.academic_year_id = current.id;
                }
            } catch (error) {
                console.error(error);
            }
        };

        const fetchClasses = async () => {
            loading.value = true;
            try {
                const params = {};
                if (selectedSessionId.value) {
                    params.academic_year_id = selectedSessionId.value;
                }
                const response = await window.axios.get('/api/classes', { params });
                classes.value = response.data.classes;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load classes.');
            } finally {
                loading.value = false;
            }
        };

        const filteredClasses = computed(() => {
            let list = classes.value || [];
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                list = list.filter(cls => cls.name.toLowerCase().includes(query) || (cls.description && cls.description.toLowerCase().includes(query)));
            }
            if (filterStatus.value) {
                list = list.filter(cls => cls.status === filterStatus.value);
            }
            return list;
        });

        const sortedClasses = computed(() => {
            const list = [...filteredClasses.value];
            list.sort((a, b) => {
                let valA = a[sortKey.value] || '';
                let valB = b[sortKey.value] || '';
                if (typeof valA === 'string') {
                    valA = valA.toLowerCase();
                    valB = valB.toLowerCase();
                }
                if (valA < valB) return sortDesc.value ? 1 : -1;
                if (valA > valB) return sortDesc.value ? -1 : 1;
                return 0;
            });
            return list;
        });

        const sortBy = (key) => {
            if (sortKey.value === key) {
                sortDesc.value = !sortDesc.value;
            } else {
                sortKey.value = key;
                sortDesc.value = false;
            }
        };

        const openModal = (cls = null) => {
            errors.value = '';
            if (cls) {
                editingId.value = cls.id;
                form.value = {
                    academic_year_id: cls.academic_year_id,
                    name: cls.name,
                    description: cls.description || '',
                    status: cls.status
                };
            } else {
                editingId.value = null;
                const defaultYear = selectedSessionId.value || (academicYears.value.find(y => y.is_current)?.id || '');
                form.value = {
                    academic_year_id: defaultYear,
                    name: '',
                    description: '',
                    status: 'active'
                };
            }
            modalOpen.value = true;
        };

        const closeModal = () => {
            modalOpen.value = false;
        };

        const saveForm = async () => {
            saving.value = true;
            errors.value = '';
            try {
                if (editingId.value) {
                    await window.axios.put(`/api/classes/${editingId.value}`, form.value);
                    toastStore.success('Class updated successfully.');
                } else {
                    await window.axios.post('/api/classes', form.value);
                    toastStore.success('Class created successfully.');
                }
                closeModal();
                fetchClasses();
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Validation error.';
            } finally {
                saving.value = false;
            }
        };

        const handleDelete = async (cls) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Class',
                message: `Are you sure you want to delete class "${cls.name}"? All sections and student academic records in this class will be permanently removed.`,
                type: 'danger',
                confirmText: 'Delete Class',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/classes/${cls.id}`);
                    toastStore.success('Class deleted successfully.');
                    fetchClasses();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete class.');
                }
            }
        };

        onMounted(async () => {
            await fetchAcademicYears();
            await fetchClasses();

            window.addEventListener('open-settings-create-modal', () => {
                if (window.location.pathname.includes('/classes')) {
                    openModal();
                }
            });
        });

        return {
            authStore,
            classes,
            academicYears,
            selectedSessionId,
            loading,
            modalOpen,
            editingId,
            saving,
            errors,
            form,
            openModal,
            closeModal,
            saveForm,
            handleDelete,
            fetchClasses,
            isCurrentYear,
            // Filters & search
            searchQuery,
            filterStatus,
            showAdvancedFilters,
            sortedClasses,
            sortKey,
            sortDesc,
            sortBy
        };
    }
}
</script>
