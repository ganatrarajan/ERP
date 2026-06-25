<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Result Engine & Reports</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Generate consolidated exam result sheets, calculate student ranks, and print report card PDFs.</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Exam <span class="text-rose-500">*</span></label>
                <select 
                    v-model="filters.exam_id"
                    @change="handleExamChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Exam</option>
                    <option v-for="ex in exams" :key="ex.id" :value="ex.id">{{ ex.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Second Exam (Optional)</label>
                <select 
                    v-model="filters.exam_id_2"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">None (Single Exam)</option>
                    <option v-for="ex in exams.filter(e => e.id !== filters.exam_id)" :key="ex.id" :value="ex.id">{{ ex.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                <select 
                    v-model="filters.class_id"
                    @change="handleClassChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Class</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section <span class="text-rose-500">*</span></label>
                <select 
                    v-model="filters.section_id"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Section</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>
            </div>

            <div class="flex items-end">
                <button 
                    @click="generateResults"
                    :disabled="!filters.exam_id || !filters.class_id || !filters.section_id || loading"
                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 00-2 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Compile Results
                </button>
            </div>
        </div>

        <!-- Result Overview Sheet -->
        <div v-if="loaded" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 dark:bg-slate-900/60">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Compiled Consolidated Sheet</h3>
                    <p class="text-xs text-slate-400">Total Students Rank List and Class Percentage Metrics</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <!-- Include Graded Option -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Include Graded?</label>
                        <select 
                            v-model="includeGraded"
                            class="px-2 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 cursor-pointer"
                        >
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>

                    <!-- Template select option -->
                    <div>
                        <select 
                            v-model="selectedTemplate"
                            class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        >
                            <option value="basic">Standard Layout</option>
                            <option value="detailed">Detailed Layout</option>
                            <option value="cbse">CBSE Format</option>
                        </select>
                    </div>

                    <button 
                        @click="downloadClassReportCards"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-semibold text-xs rounded-xl shadow transition-all flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Class PDFs
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60 select-none">
                            <th class="p-4 pl-6 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-colors" style="width: 10%;" @click="sortResults('roll_no')">
                                <span class="flex items-center gap-1.5">
                                    Roll No
                                    <svg v-if="sortKey === 'roll_no'" class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="sortDesc" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </span>
                            </th>
                            <th class="p-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-colors" style="width: 10%;" @click="sortResults('admission_no')">
                                <span class="flex items-center gap-1.5">
                                    Adm. No
                                    <svg v-if="sortKey === 'admission_no'" class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="sortDesc" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </span>
                            </th>
                            <th class="p-4 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-colors" style="width: 20%;" @click="sortResults('student')">
                                <span class="flex items-center gap-1.5">
                                    Student
                                    <svg v-if="sortKey === 'student'" class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="sortDesc" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </span>
                            </th>
                            <template v-if="filters.exam_id_2">
                                <th class="p-4 text-center" style="width: 12%;">Exam 1 %</th>
                                <th class="p-4 text-center" style="width: 12%;">Exam 2 %</th>
                                <th class="p-4 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-colors" style="width: 12%;" @click="sortResults('percentage')">
                                    <span class="flex items-center justify-center gap-1.5">
                                        Combined %
                                        <svg v-if="sortKey === 'percentage'" class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="sortDesc" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    </span>
                                </th>
                            </template>
                            <template v-else>
                                <th class="p-4 text-center" style="width: 15%;">Marks Obtained</th>
                                <th class="p-4 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-colors" style="width: 10%;" @click="sortResults('percentage')">
                                    <span class="flex items-center justify-center gap-1.5">
                                        Percentage
                                        <svg v-if="sortKey === 'percentage'" class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="sortDesc" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    </span>
                                </th>
                            </template>
                            <th class="p-4 text-center" style="width: 10%;">Overall Grade</th>
                            <th class="p-4 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40 transition-colors" style="width: 10%;" @click="sortResults('rank')">
                                <span class="flex items-center justify-center gap-1.5">
                                    Rank
                                    <svg v-if="sortKey === 'rank'" class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="sortDesc" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                </span>
                            </th>
                            <th class="p-4 text-center" style="width: 10%;">Result</th>
                            <th class="p-4 pr-6 text-right" style="width: 10%;">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="res in sortedResults" :key="res.student_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-mono text-slate-500 font-semibold">
                                {{ res.roll_no || '-' }}
                            </td>
                            <td class="p-4 font-mono text-slate-500">
                                {{ res.admission_no }}
                            </td>
                            <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                {{ res.student?.first_name }} {{ res.student?.last_name }}
                            </td>
                            <template v-if="filters.exam_id_2">
                                <td class="p-4 text-center font-mono font-medium">
                                    <span v-if="res.exam1_percentage !== null">{{ res.exam1_percentage }}%</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="p-4 text-center font-mono font-medium">
                                    <span v-if="res.exam2_percentage !== null">{{ res.exam2_percentage }}%</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="p-4 text-center font-bold font-mono">
                                    <span v-if="res.percentage !== null">{{ res.percentage }}%</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                            </template>
                            <template v-else>
                                <td class="p-4 text-center font-medium">
                                    <span v-if="res.total_obtained_marks !== null">
                                        {{ res.total_obtained_marks }} <span class="text-xs text-slate-400">/ {{ res.total_max_marks }}</span>
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="p-4 text-center font-bold font-mono">
                                    <span v-if="res.percentage !== null">{{ res.percentage }}%</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                            </template>
                            <td class="p-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ res.grade }}
                                </span>
                            </td>
                            <td class="p-4 text-center font-extrabold text-indigo-600 dark:text-indigo-400">
                                {{ res.rank }}
                            </td>
                            <td class="p-4 text-center">
                                <span 
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border',
                                        res.result === 'Pass' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : res.result === 'Fail' ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20'
                                    ]"
                                >
                                    {{ res.result }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <button 
                                    @click="downloadStudentReportCard(res.student_id)"
                                    class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                    title="Download Report Card PDF"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="loading" class="p-12 text-center text-slate-500">
            <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600 mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Compiling and rendering student report scores...
        </div>

        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500 rounded-2xl shadow-sm">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 00-2 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            Please select the Exam, Class, and Section then click "Compile Results" to generate grade sheets.
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();

const exams = ref([]);
const classes = ref([]);
const sections = ref([]);
const results = ref([]);

const loading = ref(false);
const loaded = ref(false);
const selectedTemplate = ref('basic');
const includeGraded = ref('no');

const sortKey = ref('rank');
const sortDesc = ref(false);

const sortResults = (key) => {
    if (sortKey.value === key) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortKey.value = key;
        sortDesc.value = false;
    }
};

const sortedResults = computed(() => {
    return [...results.value].sort((a, b) => {
        let valA, valB;
        if (sortKey.value === 'student') {
            valA = `${a.student?.first_name || ''} ${a.student?.last_name || ''}`.toLowerCase();
            valB = `${b.student?.first_name || ''} ${b.student?.last_name || ''}`.toLowerCase();
        } else if (sortKey.value === 'roll_no') {
            valA = a.roll_no ? Number(a.roll_no) : 999999;
            valB = b.roll_no ? Number(b.roll_no) : 999999;
        } else if (sortKey.value === 'admission_no') {
            valA = a.admission_no || '';
            valB = b.admission_no || '';
        } else if (sortKey.value === 'rank') {
            const rankA = a.rank === '-' ? 999999 : Number(a.rank);
            const rankB = b.rank === '-' ? 999999 : Number(b.rank);
            valA = rankA;
            valB = rankB;
        } else if (sortKey.value === 'percentage') {
            valA = a.percentage !== null ? Number(a.percentage) : -1;
            valB = b.percentage !== null ? Number(b.percentage) : -1;
        } else {
            valA = a[sortKey.value];
            valB = b[sortKey.value];
        }

        if (valA < valB) return sortDesc.value ? 1 : -1;
        if (valA > valB) return sortDesc.value ? -1 : 1;
        return 0;
    });
});

const filters = reactive({
    exam_id: '',
    exam_id_2: '',
    class_id: '',
    section_id: '',
    academic_year_id: ''
});

const academicYears = ref([]);

const fetchAcademicYears = async () => {
    try {
        const response = await window.axios.get('/api/academic-years');
        academicYears.value = response.data.academic_years || [];
    } catch (e) {
        console.error(e);
    }
};

const fetchExams = async () => {
    try {
        const response = await window.axios.get('/api/exams', { params: { all: true } });
        exams.value = response.data.exams || [];
    } catch (e) {
        window.toastr?.error('Failed to load exams list.');
    }
};

const fetchClasses = async () => {
    try {
        const params = { all: true };
        if (filters.academic_year_id) {
            params.academic_year_id = filters.academic_year_id;
        }
        const response = await window.axios.get('/api/classes', { params });
        classes.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const handleExamChange = async () => {
    const exObj = exams.value.find(e => e.id === filters.exam_id);
    filters.academic_year_id = exObj ? exObj.academic_year_id : '';
    resetSelection(true);
    await fetchClasses();
};

const handleClassChange = () => {
    fetchSections();
    resetSelection(false);
};

const resetSelection = (clearClass = false) => {
    loaded.value = false;
    results.value = [];
    if (clearClass) {
        filters.exam_id_2 = '';
        filters.class_id = '';
        filters.section_id = '';
        sections.value = [];
    }
};

const fetchSections = async () => {
    if (!filters.class_id) {
        sections.value = [];
        filters.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: filters.class_id, all: true } });
        sections.value = response.data.sections || response.data;
        filters.section_id = '';
    } catch (e) {
        window.toastr?.error('Failed to load class sections.');
    }
};

const generateResults = async () => {
    loading.value = true;
    loaded.value = false;
    try {
        const params = {
            academic_year_id: filters.academic_year_id,
            exam_id: filters.exam_id,
            exam_id_2: filters.exam_id_2 || undefined,
            class_id: filters.class_id,
            section_id: filters.section_id,
            include_graded: includeGraded.value
        };

        const response = await window.axios.get('/api/report-cards/students-status', { params });
        results.value = response.data.results || [];
        loaded.value = true;
    } catch (e) {
        window.toastr?.error('Failed to generate/load class results.');
    } finally {
        loading.value = false;
    }
};

const downloadStudentReportCard = (studentId) => {
    const queryParams = new URLSearchParams({
        academic_year_id: filters.academic_year_id,
        exam_id: filters.exam_id,
        exam_id_2: filters.exam_id_2 || '',
        class_id: filters.class_id,
        section_id: filters.section_id,
        student_id: studentId,
        template: selectedTemplate.value,
        include_graded: includeGraded.value
    }).toString();

    window.open(`/api/report-cards/pdf?${queryParams}`, '_blank');
};

const downloadClassReportCards = () => {
    const queryParams = new URLSearchParams({
        academic_year_id: filters.academic_year_id,
        exam_id: filters.exam_id,
        exam_id_2: filters.exam_id_2 || '',
        class_id: filters.class_id,
        section_id: filters.section_id,
        template: selectedTemplate.value,
        include_graded: includeGraded.value
    }).toString();

    window.open(`/api/report-cards/pdf?${queryParams}`, '_blank');
};

onMounted(() => {
    fetchAcademicYears();
    fetchExams();
    fetchClasses();
});
</script>
