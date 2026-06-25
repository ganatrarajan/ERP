<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Classes</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">View and manage classes across different academic sessions.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('class.create') && isCurrentYear"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Class
            </button>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loading" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Creating, editing, or deleting classes is disabled.
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl flex flex-wrap items-center gap-4 shadow-sm">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Academic Session:</label>
                <select 
                    v-model="selectedSessionId" 
                    @change="fetchClasses"
                    class="px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Academic Years</option>
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }} <span v-if="year.is_current">(Current)</span>
                    </option>
                </select>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="classes.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                No classes defined yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Class Name</th>
                            <th class="p-4">Academic Year</th>
                            <th class="p-4">Description</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="cls in classes" :key="cls.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ cls.name }}
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                                    {{ cls.academic_year?.title || 'N/A' }}
                                </span>
                            </td>
                            <td class="p-4 max-w-xs truncate text-slate-500">
                                {{ cls.description || 'No description' }}
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
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Class"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('class.delete') && isCurrentYear"
                                        @click="handleDelete(cls)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
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
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Class' : 'Create Class' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="p-6 space-y-4">
                    <!-- Academic Year -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Academic Year</label>
                        <select 
                            v-model="form.academic_year_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        >
                            <option value="" disabled>Select Academic Year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current)</span>
                            </option>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Class Name</label>
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
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="2"
                            placeholder="Brief details about the class"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all resize-none"
                        ></textarea>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                        <select 
                            v-model="form.status" 
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
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
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1"
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

export default {
    name: 'ClassesIndex',
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
        
        const isCurrentYear = computed(() => {
            if (!selectedSessionId.value) return true; // Don't block if "All Academic Years" is selected (though they will be blocked by specific items/row checks)
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
                
                // Set default to current session if available
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
                // Default to selectedSessionId if defined, otherwise current active session
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
            isCurrentYear
        };
    }
}
</script>
