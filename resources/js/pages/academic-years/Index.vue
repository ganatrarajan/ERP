<template>
    <div class="space-y-6">
        <!-- Settings Control Header -->
        <SettingsHeader />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-slate-800 dark:text-white tracking-tight">Academic Sessions</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Define active school years, semesters, and configuration parameters.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('academic_year.create')"
                @click="openModal()"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow shadow-indigo-600/10 transition-all flex items-center gap-1.5 cursor-pointer border-none"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Academic Year
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
                        placeholder="Search sessions (e.g. 2025)..." 
                        class="w-full pl-9 pr-4 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    />
                </div>

                <!-- Advanced Collapse Toggle -->
                <button 
                    @click="showAdvancedFilters = !showAdvancedFilters"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-800 rounded-xl text-xs text-slate-600 dark:text-slate-350 font-bold flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filters
                </button>
            </div>

            <!-- Collapsible filters drawer -->
            <div v-show="showAdvancedFilters" class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap gap-4 items-center">
                <!-- Status Filter -->
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status:</label>
                    <select 
                        v-model="filterStatus"
                        class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none cursor-pointer"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>

                <button 
                    @click="searchQuery = ''; filterStatus = '';"
                    class="px-3 py-1.5 bg-slate-550 hover:bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-500 dark:text-slate-450 border border-slate-200 dark:border-slate-800/80 rounded-xl transition-all cursor-pointer"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="sortedAcademicYears.length === 0" class="p-12 text-center text-slate-500 bg-slate-50/20 dark:bg-slate-900/10">
                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <h4 class="font-bold text-slate-700 dark:text-slate-300">No Academic Sessions Found</h4>
                <p class="text-xs text-slate-400 mt-1">Refine your search or create a new academic session to start.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60 select-none">
                            <th class="p-4 pl-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-850" @click="sortBy('title')">
                                <span class="flex items-center gap-1">
                                    Title
                                    <span v-if="sortKey === 'title'">{{ sortDesc ? '↓' : '↑' }}</span>
                                </span>
                            </th>
                            <th class="p-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-850" @click="sortBy('start_date')">
                                <span class="flex items-center gap-1">
                                    Start Date
                                    <span v-if="sortKey === 'start_date'">{{ sortDesc ? '↓' : '↑' }}</span>
                                </span>
                            </th>
                            <th class="p-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-850" @click="sortBy('end_date')">
                                <span class="flex items-center gap-1">
                                    End Date
                                    <span v-if="sortKey === 'end_date'">{{ sortDesc ? '↓' : '↑' }}</span>
                                </span>
                            </th>
                            <th class="p-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-850" @click="sortBy('status')">
                                <span class="flex items-center gap-1">
                                    Status
                                    <span v-if="sortKey === 'status'">{{ sortDesc ? '↓' : '↑' }}</span>
                                </span>
                            </th>
                            <th class="p-4">Current Year</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="year in sortedAcademicYears" :key="year.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ year.title }}
                            </td>
                            <td class="p-4 font-mono text-xs">
                                {{ formatDate(year.start_date) }}
                            </td>
                            <td class="p-4 font-mono text-xs">
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
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
                                        title="Edit Academic Year"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('academic_year.delete')"
                                        @click="handleDelete(year)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors cursor-pointer"
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
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity animate-[fadeIn_0.2s_ease-out]">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Academic Year' : 'Create Academic Year' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors border-none bg-transparent cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="flex flex-col max-h-[80vh]">
                    <!-- Scrollable Body Content -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Title -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Title</label>
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
                            <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Start Date</label>
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
                            <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">End Date</label>
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
                            <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Status</label>
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
                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950 cursor-pointer"
                            />
                            <label for="is_current" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">
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
                                    class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950 cursor-pointer"
                                />
                                <label for="should_clone" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                    Clone setup from a previous academic year
                                </label>
                            </div>

                            <div v-if="form.should_clone" class="pl-6 space-y-4">
                                <!-- Clone Source Year -->
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wide">Source Academic Year</label>
                                    <select 
                                        v-model="form.clone_source_id" 
                                        required
                                        class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                                    >
                                        <option :value="null" disabled>Select Source Year</option>
                                        <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.title }}</option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-550 uppercase tracking-wide block">Elements to Clone</label>
                                    <div class="flex flex-col gap-2">
                                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                value="classes_sections" 
                                                v-model="form.clone_elements"
                                                @change="handleCloneElementChange"
                                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                                            />
                                            Classes & Sections Mapping
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
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all border-none bg-transparent cursor-pointer"
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
    name: 'AcademicYearsIndex',
    components: { SettingsHeader },
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

        // Search & Filters state
        const searchQuery = ref('');
        const filterStatus = ref('');
        const showAdvancedFilters = ref(false);

        // Sorting state
        const sortKey = ref('title');
        const sortDesc = ref(false);

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

        const filteredAcademicYears = computed(() => {
            let list = academicYears.value || [];
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                list = list.filter(year => year.title.toLowerCase().includes(query));
            }
            if (filterStatus.value) {
                list = list.filter(year => year.status === filterStatus.value);
            }
            return list;
        });

        const sortedAcademicYears = computed(() => {
            const list = [...filteredAcademicYears.value];
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
            errors.value = '';
            
            // Validation: Dates
            if (form.value.start_date && form.value.end_date) {
                if (new Date(form.value.start_date) >= new Date(form.value.end_date)) {
                    errors.value = 'Start date must be strictly before end date.';
                    return;
                }
            }

            saving.value = true;
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
            window.addEventListener('open-settings-create-modal', () => {
                if (window.location.pathname.includes('/academic-years')) {
                    openModal();
                }
            });
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
            handleCloneElementChange,
            // Search & sorting
            searchQuery,
            filterStatus,
            showAdvancedFilters,
            sortedAcademicYears,
            sortKey,
            sortDesc,
            sortBy
        };
    }
}
</script>
