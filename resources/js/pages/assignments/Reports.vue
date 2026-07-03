<template>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6 animate-[fadeIn_0.2s_ease-out] print:p-0 print:m-0 print:bg-white print:text-black">
        <!-- Header Section (hidden in print) -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
            <div class="flex items-center gap-3">
                <router-link to="/assignments" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 rounded-xl transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </router-link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                        Teacher Assignment Reports
                    </h1>
                    <p class="text-xs text-slate-500">Generate, customize columns, print and download tabular reports for teacher assignments.</p>
                </div>
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <button
                    @click="exportExcel"
                    :disabled="assignments.length === 0"
                    class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-xs rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                >
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </button>
                <button
                    @click="downloadPDF"
                    :disabled="assignments.length === 0"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/15 transition-all flex items-center justify-center gap-1.5 border-none cursor-pointer disabled:opacity-50"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- Workspace Report Filters (hidden in print) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 p-4 rounded-3xl shadow-sm space-y-4 print:hidden">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4 flex-1">
                    <!-- Academic Session Selection -->
                    <div class="w-full sm:w-48">
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Academic Session</label>
                        <select v-model="filters.academic_year_id" @change="handleYearChange" class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none text-slate-700 dark:text-slate-200 font-bold cursor-pointer">
                            <option value="">All Sessions...</option>
                            <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.title }}</option>
                        </select>
                    </div>

                    <!-- Teacher Selection -->
                    <div class="w-full sm:w-56">
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Teacher</label>
                        <select v-model="filters.teacher_id" @change="fetchReport" class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <option value="">All Teachers...</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end">
                    <button 
                        @click="showAdvancedFilters = !showAdvancedFilters"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-800 rounded-xl text-xs text-slate-650 dark:text-slate-300 font-bold flex items-center gap-1.5 transition-colors cursor-pointer border border-slate-250/20"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Advanced Filters
                    </button>
                    <button 
                        @click="resetFilters"
                        class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-500 border border-slate-200 dark:border-slate-800 rounded-xl transition-all cursor-pointer"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Collapsible Advanced Section -->
            <div v-show="showAdvancedFilters" class="pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Class Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Class</label>
                        <select v-model="filters.class_id" @change="handleClassChange" class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <option value="">All Classes...</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Section Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Section</label>
                        <select v-model="filters.section_id" @change="handleSectionChange" :disabled="!filters.class_id" class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-955 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none text-slate-700 dark:text-slate-200 font-semibold cursor-pointer disabled:opacity-50">
                            <option value="">All Sections...</option>
                            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Subject Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Subject</label>
                        <select v-model="filters.subject_id" @change="fetchReport" :disabled="!filters.section_id" class="w-full px-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-955 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none text-slate-700 dark:text-slate-200 font-semibold cursor-pointer disabled:opacity-50">
                            <option value="">All Subjects...</option>
                            <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Columns Checklist Section -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80">
                    <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Configure Report Columns</span>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <label v-for="(col, key) in columns" :key="key" class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-350 cursor-pointer font-bold select-none">
                            <input type="checkbox" v-model="columns[key]" class="rounded border-slate-300 text-indigo-650 dark:text-indigo-400 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer" />
                            {{ getColumnLabel(key) }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Results Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden print:border-none print:shadow-none space-y-4">
            <div class="p-4 bg-slate-50/50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center gap-4 print:hidden">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider shrink-0">Report Preview Grid</span>
                
                <!-- Quick table filter search -->
                <div v-if="assignments.length > 0" class="relative w-full sm:w-60">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="localSearch"
                        type="text" 
                        placeholder="Search assignments preview..." 
                        class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
                
                <span v-if="assignments.length > 0" class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-650 dark:text-indigo-400 rounded-lg text-[10px] font-bold border border-indigo-150 shrink-0">
                    {{ filteredAssignments.length }} of {{ assignments.length }} entries
                </span>
            </div>

            <div v-if="loading" class="p-12 text-center text-slate-500 space-y-3 print:hidden">
                <div class="w-8 h-8 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin mx-auto"></div>
                <p class="text-xs font-bold text-slate-400">Loading assignments directory...</p>
            </div>

            <div v-else-if="assignments.length === 0" class="p-12 text-center text-slate-500 space-y-3">
                <svg class="w-12 h-12 text-slate-350 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <p class="text-xs font-bold text-slate-400">No teacher assignments configured for this criteria.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table id="assignments-report-table" class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-100 dark:border-slate-850/80 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider print:bg-slate-100 print:text-black">
                            <th v-if="columns.teacher_name" class="p-4 pl-6">Teacher Name</th>
                            <th v-if="columns.teacher_code" class="p-4">Employee ID</th>
                            <th v-if="columns.teacher_email" class="p-4">Email</th>
                            <th v-if="columns.academic_session" class="p-4">Academic Session</th>
                            <th v-if="columns.class" class="p-4">Class</th>
                            <th v-if="columns.section" class="p-4">Section</th>
                            <th v-if="columns.subject" class="p-4">Subject</th>
                            <th v-if="columns.type" class="p-4 pr-6">Assignment Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850 text-slate-750 dark:text-slate-300 print:text-black print:divide-slate-200">
                        <tr v-for="item in filteredAssignments" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-950/40 transition-colors print:hover:bg-transparent">
                            <td v-if="columns.teacher_name" class="p-4 pl-6 font-bold text-slate-800 dark:text-slate-100">{{ item.teacher?.name }}</td>
                            <td v-if="columns.teacher_code" class="p-4 font-semibold font-mono text-slate-500">{{ item.teacher?.employee_id || 'N/A' }}</td>
                            <td v-if="columns.teacher_email" class="p-4 text-slate-500 font-mono">{{ item.teacher?.email }}</td>
                            <td v-if="columns.academic_session" class="p-4 font-semibold text-slate-600 dark:text-slate-400">{{ item.academic_year?.title }}</td>
                            <td v-if="columns.class" class="p-4 font-semibold text-slate-600 dark:text-slate-400">{{ item.class?.name }}</td>
                            <td v-if="columns.section" class="p-4 font-semibold text-slate-650 dark:text-slate-400">{{ item.section?.name }}</td>
                            <td v-if="columns.subject" class="p-4 font-bold text-slate-800 dark:text-white print:text-black">
                                {{ item.subject?.name || 'Class Teacher Allocation' }}
                            </td>
                            <td v-if="columns.type" class="p-4 pr-6">
                                <span class="font-bold text-xs" :class="item.is_class_teacher ? 'text-amber-600 dark:text-amber-400' : 'text-indigo-650 dark:text-indigo-400'">
                                    {{ item.is_class_teacher ? 'Class Teacher' : 'Subject Teacher' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { exportToExcel } from '../../utils/reportExporter';

export default {
    name: 'TeacherAssignmentReports',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const academicYears = ref([]);
        const classes = ref([]);
        const sections = ref([]);
        const subjects = ref([]);
        const teachers = ref([]);
        const assignments = ref([]);
        const loading = ref(false);
        
        const localSearch = ref('');
        const showAdvancedFilters = ref(false);

        // Filter objects
        const filters = ref({
            academic_year_id: '',
            teacher_id: '',
            class_id: '',
            section_id: '',
            subject_id: ''
        });

        // Configurable column selectors
        const columns = ref({
            teacher_name: true,
            teacher_code: true,
            teacher_email: false,
            academic_session: true,
            class: true,
            section: true,
            subject: true,
            type: true
        });

        const resetFilters = () => {
            filters.value.teacher_id = '';
            filters.value.class_id = '';
            filters.value.section_id = '';
            filters.value.subject_id = '';
            sections.value = [];
            subjects.value = [];
            fetchReport();
        };

        const getColumnLabel = (key) => {
            const labels = {
                teacher_name: 'Teacher Name',
                teacher_code: 'Employee ID',
                teacher_email: 'Email ID',
                academic_session: 'Academic Session',
                class: 'Class Name',
                section: 'Section Name',
                subject: 'Subject Name',
                type: 'Assignment Type'
            };
            return labels[key] || key;
        };

        const currentYearTitle = computed(() => {
            const yr = academicYears.value.find(ay => ay.id === Number(filters.value.academic_year_id));
            return yr ? yr.title : '';
        });

        const filteredAssignments = computed(() => {
            if (!localSearch.value) return assignments.value;
            const q = localSearch.value.toLowerCase();
            return assignments.value.filter(item => {
                const nameMatch = item.teacher?.name?.toLowerCase().includes(q);
                const codeMatch = item.teacher?.employee_id?.toLowerCase().includes(q);
                const emailMatch = item.teacher?.email?.toLowerCase().includes(q);
                const classMatch = item.class?.name?.toLowerCase().includes(q);
                const sectionMatch = item.section?.name?.toLowerCase().includes(q);
                const subjectMatch = (item.subject?.name || 'Class Teacher Allocation').toLowerCase().includes(q);
                return nameMatch || codeMatch || emailMatch || classMatch || sectionMatch || subjectMatch;
            });
        });

        const loadInitData = async () => {
            try {
                const ayRes = await window.axios.get('/api/academic-years');
                academicYears.value = ayRes.data.academic_years;

                const curr = academicYears.value.find(ay => ay.is_current);
                if (curr) {
                    filters.value.academic_year_id = curr.id;
                }

                const classRes = await window.axios.get('/api/classes');
                classes.value = classRes.data.classes;

                const teachRes = await window.axios.get('/api/users', { params: { role: 'Teacher' } });
                teachers.value = teachRes.data.users;

                fetchReport();
            } catch (err) {
                console.error(err);
                toastStore.error('Could not initialize reports selector.');
            }
        };

        const fetchReport = async () => {
            loading.value = true;
            try {
                const res = await window.axios.get('/api/teacher-assignments', { params: filters.value });
                assignments.value = res.data.assignments;
            } catch (err) {
                console.error(err);
                toastStore.error('Could not load report mapping data.');
            } finally {
                loading.value = false;
            }
        };

        const handleYearChange = () => {
            fetchReport();
        };

        const handleClassChange = async () => {
            filters.value.section_id = '';
            filters.value.subject_id = '';
            sections.value = [];
            subjects.value = [];
            if (filters.value.class_id) {
                try {
                    const secRes = await window.axios.get('/api/sections', { params: { class_id: filters.value.class_id } });
                    sections.value = secRes.data.sections;
                } catch (err) {
                    console.error(err);
                }
            }
            fetchReport();
        };

        const handleSectionChange = async () => {
            filters.value.subject_id = '';
            subjects.value = [];
            if (filters.value.class_id && filters.value.section_id) {
                try {
                    const subRes = await window.axios.get('/api/subjects', {
                        params: { 
                            class_id: filters.value.class_id, 
                            section_id: filters.value.section_id,
                            all: true
                        }
                    });
                    subjects.value = subRes.data.subjects;
                } catch (err) {
                    console.error(err);
                }
            }
            fetchReport();
        };

        const downloadPDF = () => {
            const columnsSelected = [];
            if (columns.value.teacher_name) columnsSelected.push('teacher_name');
            if (columns.value.teacher_code) columnsSelected.push('teacher_code');
            if (columns.value.teacher_email) columnsSelected.push('teacher_email');
            if (columns.value.academic_session) columnsSelected.push('academic_session');
            if (columns.value.class) columnsSelected.push('class');
            if (columns.value.section) columnsSelected.push('section');
            if (columns.value.subject) columnsSelected.push('subject');
            if (columns.value.type) columnsSelected.push('type');

            const queryParams = new URLSearchParams({
                ...filters.value,
                selected_columns: columnsSelected.join(',')
            }).toString();

            window.open(`/api/teacher-assignments/pdf?${queryParams}`, '_blank');
        };

        const exportExcel = () => {
            exportToExcel('#assignments-report-table', 'Teacher_Assignments_Report');
        };

        onMounted(() => {
            loadInitData();
        });

        return {
            authStore,
            toastStore,
            academicYears,
            classes,
            sections,
            subjects,
            teachers,
            assignments,
            filteredAssignments,
            loading,
            filters,
            columns,
            getColumnLabel,
            currentYearTitle,
            handleYearChange,
            handleClassChange,
            handleSectionChange,
            fetchReport,
            downloadPDF,
            exportExcel,
            localSearch,
            showAdvancedFilters,
            resetFilters
        };
    }
};
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
