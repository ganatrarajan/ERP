<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Student Fee Assignment</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Assign fee structure templates and optional fee mappings to students manually or in bulk by class.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('fee_structure.create') && isCurrentYear"
                @click="openBulkModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Bulk Assign By Class
            </button>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loading" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Assigning fees manually or in bulk is disabled.
            </div>
        </div>

        <!-- Filters Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Academic Session</label>
                <select 
                    v-model="filters.academic_year_id" 
                    @change="handleAcademicYearChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Class</label>
                <select 
                    v-model="filters.class_id" 
                    @change="handleClassChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option value="">Select Class</option>
                    <option v-for="c in classes" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Section</label>
                <select 
                    v-model="filters.section_id" 
                    @change="fetchStudents"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option value="">All Sections</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                        {{ sec.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Search Student</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="handleSearch"
                        type="text" 
                        placeholder="Name or adm no..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                    />
                </div>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="students.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                No student academic records found for the selected filters.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Admission No</th>
                            <th class="p-4">Student Name</th>
                            <th class="p-4">Class / Section</th>
                            <th class="p-4">Assigned Structure</th>
                            <th class="p-4">Optional Fees</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="stu in students" :key="stu.student_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-mono text-xs">
                                {{ stu.admission_no }}
                            </td>
                            <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                {{ stu.name }}
                            </td>
                            <td class="p-4">
                                {{ stu.class }} {{ stu.section ? '('+stu.section+')' : '' }}
                            </td>
                            <td class="p-4 font-medium">
                                <span v-if="stu.assignment" class="text-slate-800 dark:text-slate-200">
                                    {{ stu.assignment.fee_structure_name }}
                                </span>
                                <span v-else class="text-rose-500 font-semibold text-xs bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 rounded">
                                    Not Assigned
                                </span>
                            </td>
                            <td class="p-4">
                                <div v-if="stu.optional_fees.length > 0" class="flex flex-wrap gap-1">
                                    <span v-for="opt in stu.optional_fees" :key="opt.fee_type_id" class="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/15 px-1.5 py-0.5 rounded text-[10px]">
                                        {{ opt.name }}
                                    </span>
                                </div>
                                <span v-else class="text-xs text-slate-400">
                                    None
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <button 
                                    v-if="authStore.hasPermission('fee_structure.create') && isCurrentYear"
                                    @click="openAssignModal(stu)"
                                    class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 rounded-lg transition-all active:scale-95"
                                >
                                    {{ stu.assignment ? 'Edit Assignment' : 'Assign Fee' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Individual Assignment Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">Assign Dues: {{ selectedStudentName }}</h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Fee Structure Template</label>
                        <select 
                            v-model="form.fee_structure_id" 
                            @change="handleStructureChange"
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="" disabled>Select Structure</option>
                            <option v-for="s in activeStructures" :key="s.id" :value="s.id">
                                {{ s.name }} (₹{{ s.total_amount }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Assignment Date</label>
                        <input 
                            v-model="form.assigned_date" 
                            v-datepicker
                            type="text" 
                            required
                            placeholder="YYYY-MM-DD"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Optional Fees Checkboxes -->
                    <div v-if="optionalFeesOptions.length > 0" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide block">Select Optional Fees to Assign</label>
                        <div class="space-y-1.5 max-h-40 overflow-y-auto">
                            <div v-for="opt in optionalFeesOptions" :key="opt.fee_type_id" class="flex items-center gap-2.5">
                                <input 
                                    v-model="form.optional_fee_type_ids" 
                                    type="checkbox" 
                                    :value="opt.fee_type_id"
                                    :id="'opt_fee_' + opt.fee_type_id"
                                    class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                                />
                                <label :for="'opt_fee_' + opt.fee_type_id" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                                    {{ opt.name }} (+₹{{ opt.amount }})
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Remarks</label>
                        <textarea 
                            v-model="form.remarks" 
                            rows="2"
                            placeholder="Optional notes..."
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                        ></textarea>
                    </div>

                    <div v-if="errors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                        {{ errors }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
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
                            <span v-if="saving">Assigning...</span>
                            <span v-else>Assign Structure</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Class Assignment Modal -->
        <div v-if="bulkModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">Bulk Assign by Class</h3>
                    <button @click="closeBulkModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveBulkForm" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Target Class</label>
                        <select 
                            v-model="bulkForm.class_id" 
                            @change="fetchBulkStructures"
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                        >
                            <option value="" disabled>Select Class</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Fee Structure Template</label>
                        <select 
                            v-model="bulkForm.fee_structure_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                        >
                            <option value="" disabled>Select Structure</option>
                            <option v-for="s in bulkStructures" :key="s.id" :value="s.id">
                                {{ s.name }} (₹{{ s.total_amount }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Assignment Date</label>
                        <input 
                            v-model="bulkForm.assigned_date" 
                            v-datepicker
                            type="text" 
                            required
                            placeholder="YYYY-MM-DD"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Remarks</label>
                        <textarea 
                            v-model="bulkForm.remarks" 
                            rows="2"
                            placeholder="Optional notes..."
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input 
                            v-model="bulkForm.overwrite_existing" 
                            type="checkbox" 
                            id="overwrite_existing"
                            class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                        />
                        <label for="overwrite_existing" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Overwrite existing assignments for this session
                        </label>
                    </div>

                    <div v-if="bulkErrors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                        {{ bulkErrors }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeBulkModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingBulk"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1"
                        >
                            <span v-if="savingBulk">Bulk Assigning...</span>
                            <span v-else>Bulk Assign</span>
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
import { useToastStore } from '../../stores/toast';

export default {
    name: 'AssignmentsIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const students = ref([]);
        const academicYears = ref([]);
        const classes = ref([]);
        const sections = ref([]);
        const activeStructures = ref([]);
        const bulkStructures = ref([]);
        const optionalFeesOptions = ref([]);

        const loading = ref(true);
        const modalOpen = ref(false);
        const bulkModalOpen = ref(false);
        const selectedStudentId = ref(null);
        const selectedStudentName = ref('');
        const saving = ref(false);
        const savingBulk = ref(false);
        const errors = ref('');
        const bulkErrors = ref('');

        const filters = ref({
            academic_year_id: '',
            class_id: '',
            section_id: '',
            search: ''
        });

        const form = ref({
            academic_year_id: '',
            student_id: '',
            fee_structure_id: '',
            assigned_date: '',
            remarks: '',
            optional_fee_type_ids: []
        });

        const bulkForm = ref({
            academic_year_id: '',
            class_id: '',
            fee_structure_id: '',
            assigned_date: '',
            remarks: '',
            overwrite_existing: false
        });

        let searchTimeout = null;

        const isCurrentYear = computed(() => {
            const selected = academicYears.value.find(y => y.id === filters.value.academic_year_id);
            return selected ? !!selected.is_current : false;
        });

        const fetchClasses = async () => {
            try {
                const params = {};
                if (filters.value.academic_year_id) {
                    params.academic_year_id = filters.value.academic_year_id;
                }
                const cRes = await window.axios.get('/api/classes', { params });
                classes.value = cRes.data.classes || cRes.data;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load classes.');
            }
        };

        const fetchFiltersData = async () => {
            try {
                // Fetch Academic Years
                const yRes = await window.axios.get('/api/academic-years');
                academicYears.value = yRes.data.academic_years;
                const activeYear = academicYears.value.find(y => y.is_current);
                if (activeYear) {
                    filters.value.academic_year_id = activeYear.id;
                } else if (academicYears.value.length > 0) {
                    filters.value.academic_year_id = academicYears.value[0].id;
                }

                // Fetch Classes
                await fetchClasses();
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load filters data.');
            }
        };

        const handleAcademicYearChange = async () => {
            filters.value.class_id = '';
            filters.value.section_id = '';
            sections.value = [];
            students.value = [];
            await fetchClasses();
            fetchStudents();
        };

        const handleClassChange = async () => {
            filters.value.section_id = '';
            sections.value = [];
            if (filters.value.class_id) {
                try {
                    const response = await window.axios.get('/api/sections', {
                        params: { class_id: filters.value.class_id }
                    });
                    sections.value = response.data.sections;
                } catch (error) {
                    console.error(error);
                }
            }
            fetchStudents();
        };

        const fetchStudents = async () => {
            if (!filters.value.academic_year_id) return;
            loading.value = true;
            try {
                const response = await window.axios.get('/api/fee-assignments', {
                    params: filters.value
                });
                students.value = response.data.students;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load student list.');
            } finally {
                loading.value = false;
            }
        };

        const handleSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchStudents();
            }, 300);
        };

        const openAssignModal = async (stu) => {
            errors.value = '';
            selectedStudentId.value = stu.student_id;
            selectedStudentName.value = stu.name;

            // Load active structures for this class and academic year
            try {
                const response = await window.axios.get('/api/fee-structures', {
                    params: {
                        academic_year_id: stu.academic_year_id,
                        class_id: stu.class_id,
                        status: 'active'
                    }
                });
                activeStructures.value = response.data.fee_structures;
            } catch (error) {
                console.error(error);
            }

            if (stu.assignment) {
                form.value = {
                    academic_year_id: stu.academic_year_id,
                    student_id: stu.student_id,
                    fee_structure_id: stu.assignment.fee_structure_id,
                    assigned_date: stu.assignment.assigned_date,
                    remarks: stu.assignment.remarks || '',
                    optional_fee_type_ids: stu.optional_fees.map(o => o.fee_type_id)
                };
                // Populate optional fees options based on current assignment
                handleStructureChange();
            } else {
                form.value = {
                    academic_year_id: stu.academic_year_id,
                    student_id: stu.student_id,
                    fee_structure_id: '',
                    assigned_date: new Date().toISOString().substring(0, 10),
                    remarks: '',
                    optional_fee_type_ids: []
                };
                optionalFeesOptions.value = [];
            }

            modalOpen.value = true;
        };

        const handleStructureChange = () => {
            optionalFeesOptions.value = [];
            const selected = activeStructures.value.find(s => s.id === form.value.fee_structure_id);
            if (selected) {
                // Filter out optional items in structure
                optionalFeesOptions.value = selected.items
                    .filter(i => i.fee_type && i.fee_type.is_optional)
                    .map(i => ({
                        fee_type_id: i.fee_type_id,
                        name: i.fee_type.name,
                        amount: i.amount
                    }));
            }
        };

        const closeModal = () => {
            modalOpen.value = false;
        };

        const saveForm = async () => {
            saving.value = true;
            errors.value = '';
            try {
                await window.axios.post('/api/fee-assignments', form.value);
                toastStore.success('Fee assignment updated successfully.');
                closeModal();
                fetchStudents();
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Failed to assign fees.';
            } finally {
                saving.value = false;
            }
        };

        const openBulkModal = () => {
            bulkErrors.value = '';
            bulkForm.value = {
                academic_year_id: filters.value.academic_year_id,
                class_id: filters.value.class_id || '',
                fee_structure_id: '',
                assigned_date: new Date().toISOString().substring(0, 10),
                remarks: '',
                overwrite_existing: false
            };
            bulkStructures.value = [];
            if (bulkForm.value.class_id) {
                fetchBulkStructures();
            }
            bulkModalOpen.value = true;
        };

        const fetchBulkStructures = async () => {
            bulkForm.value.fee_structure_id = '';
            if (!bulkForm.value.class_id) return;
            try {
                const response = await window.axios.get('/api/fee-structures', {
                    params: {
                        academic_year_id: bulkForm.value.academic_year_id,
                        class_id: bulkForm.value.class_id,
                        status: 'active'
                    }
                });
                bulkStructures.value = response.data.fee_structures;
            } catch (error) {
                console.error(error);
            }
        };

        const closeBulkModal = () => {
            bulkModalOpen.value = false;
        };

        const saveBulkForm = async () => {
            savingBulk.value = true;
            bulkErrors.value = '';
            try {
                const response = await window.axios.post('/api/fee-assignments/bulk', bulkForm.value);
                toastStore.success(response.data.message);
                closeBulkModal();
                fetchStudents();
            } catch (error) {
                console.error(error);
                bulkErrors.value = error.response?.data?.message || 'Failed to complete bulk assignment.';
            } finally {
                savingBulk.value = false;
            }
        };

        onMounted(async () => {
            await fetchFiltersData();
            await fetchStudents();
        });

        return {
            authStore,
            students,
            academicYears,
            classes,
            sections,
            activeStructures,
            bulkStructures,
            optionalFeesOptions,
            loading,
            modalOpen,
            bulkModalOpen,
            selectedStudentName,
            saving,
            savingBulk,
            errors,
            bulkErrors,
            filters,
            form,
            bulkForm,
            isCurrentYear,
            handleAcademicYearChange,
            handleClassChange,
            fetchStudents,
            handleSearch,
            openAssignModal,
            handleStructureChange,
            closeModal,
            saveForm,
            openBulkModal,
            fetchBulkStructures,
            closeBulkModal,
            saveBulkForm
        };
    }
}
</script>
