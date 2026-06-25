<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Academic Years</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage school sessions, active semesters, and define the current academic year.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('academic_year.create')"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Academic Year
            </button>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="academicYears.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                No academic years defined yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Title</th>
                            <th class="p-4">Start Date</th>
                            <th class="p-4">End Date</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Current Year</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="year in academicYears" :key="year.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ year.title }}
                            </td>
                            <td class="p-4">
                                {{ formatDate(year.start_date) }}
                            </td>
                            <td class="p-4">
                                {{ formatDate(year.end_date) }}
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                    year.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ year.status }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span v-if="year.is_current" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400 animate-ping"></span>
                                    Active Current
                                </span>
                                <span v-else class="text-xs text-slate-400 dark:text-slate-500">
                                    No
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit -->
                                    <button 
                                        v-if="authStore.hasPermission('academic_year.edit')"
                                        @click="openModal(year)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Academic Year"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('academic_year.delete')"
                                        @click="handleDelete(year)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Academic Year"
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

        <!-- Academic Year Form Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Academic Year' : 'Create Academic Year' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="flex flex-col max-h-[80vh]">
                    <!-- Scrollable Body Content -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Title -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Title</label>
                            <input 
                                v-model="form.title" 
                                type="text" 
                                required 
                                placeholder="e.g. 2025-2026"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                            />
                        </div>

                        <!-- Start Date -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Start Date</label>
                            <input 
                                v-model="form.start_date" 
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                            />
                        </div>

                        <!-- End Date -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">End Date</label>
                            <input 
                                v-model="form.end_date" 
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                            />
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

                        <!-- Is Current -->
                        <div class="flex items-center gap-2 pt-2">
                            <input 
                                v-model="form.is_current" 
                                type="checkbox" 
                                id="is_current"
                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                            />
                            <label for="is_current" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                                Mark as current active academic year
                            </label>
                        </div>

                        <!-- Clone Configuration -->
                        <div v-if="!editingId" class="space-y-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <input 
                                    v-model="form.should_clone" 
                                    type="checkbox" 
                                    id="should_clone"
                                    class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                                />
                                <label for="should_clone" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Clone setup from a previous academic year
                                </label>
                            </div>

                            <div v-if="form.should_clone" class="pl-6 space-y-4">
                                <!-- Clone Source Year -->
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Source Academic Year</label>
                                    <select 
                                        v-model="form.clone_source_id" 
                                        required
                                        class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                                    >
                                        <option :value="null" disabled>Select Source Year</option>
                                        <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                            {{ year.title }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Clone Elements -->
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide block">Data to Clone</label>
                                    <div class="flex flex-col gap-2">
                                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                value="classes_sections" 
                                                v-model="form.clone_elements"
                                                :disabled="form.clone_elements.includes('subjects') || form.clone_elements.includes('fee_structures')"
                                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950 disabled:opacity-50"
                                            />
                                            Classes & Sections
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                value="subjects" 
                                                v-model="form.clone_elements"
                                                @change="handleCloneElementChange"
                                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                                            />
                                            Subjects
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                value="fee_structures" 
                                                v-model="form.clone_elements"
                                                @change="handleCloneElementChange"
                                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                                            />
                                            Fee Structures
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <div v-if="errors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                            {{ errors }}
                        </div>
                    </div>

                    <!-- Fixed Form Actions -->
                    <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
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
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'AcademicYearsIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const academicYears = ref([]);
        const loading = ref(true);
        const modalOpen = ref(false);
        const editingId = ref(null);
        const saving = ref(false);
        const errors = ref('');

        const form = ref({
            title: '',
            start_date: '',
            end_date: '',
            status: 'active',
            is_current: false,
            should_clone: false,
            clone_source_id: null,
            clone_elements: ['classes_sections', 'subjects', 'fee_structures']
        });

        const fetchAcademicYears = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/academic-years');
                academicYears.value = response.data.academic_years;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load academic years.');
            } finally {
                loading.value = false;
            }
        };

        const openModal = (year = null) => {
            errors.value = '';
            if (year) {
                editingId.value = year.id;
                form.value = {
                    title: year.title,
                    start_date: year.start_date ? year.start_date.substring(0, 10) : '',
                    end_date: year.end_date ? year.end_date.substring(0, 10) : '',
                    status: year.status,
                    is_current: !!year.is_current,
                    should_clone: false,
                    clone_source_id: null,
                    clone_elements: ['classes_sections', 'subjects', 'fee_structures']
                };
            } else {
                editingId.value = null;
                const activeYear = academicYears.value.find(y => y.is_current);
                form.value = {
                    title: '',
                    start_date: '',
                    end_date: '',
                    status: 'active',
                    is_current: false,
                    should_clone: false,
                    clone_source_id: activeYear ? activeYear.id : null,
                    clone_elements: ['classes_sections', 'subjects', 'fee_structures']
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
                const payload = {
                    title: form.value.title,
                    start_date: form.value.start_date,
                    end_date: form.value.end_date,
                    status: form.value.status,
                    is_current: form.value.is_current,
                };
                if (!editingId.value && form.value.should_clone) {
                    payload.clone_source_id = form.value.clone_source_id;
                    payload.clone_elements = form.value.clone_elements;
                }

                if (editingId.value) {
                    await window.axios.put(`/api/academic-years/${editingId.value}`, payload);
                    toastStore.success('Academic year updated successfully.');
                } else {
                    await window.axios.post('/api/academic-years', payload);
                    toastStore.success('Academic year created successfully.');
                }
                closeModal();
                fetchAcademicYears();
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Validation error.';
            } finally {
                saving.value = false;
            }
        };

        const handleCloneElementChange = () => {
            if (form.value.clone_elements.includes('subjects') || form.value.clone_elements.includes('fee_structures')) {
                if (!form.value.clone_elements.includes('classes_sections')) {
                    form.value.clone_elements.push('classes_sections');
                }
            }
        };

        const handleDelete = async (year) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Academic Year',
                message: `Are you sure you want to delete "${year.title}"? All classes and sections associated will be lost.`,
                type: 'danger',
                confirmText: 'Delete Session',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/academic-years/${year.id}`);
                    toastStore.success('Academic year deleted successfully.');
                    fetchAcademicYears();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete session.');
                }
            }
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        };

        onMounted(() => {
            fetchAcademicYears();
        });

        return {
            authStore,
            academicYears,
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
            formatDate,
            handleCloneElementChange
        };
    }
}
</script>
