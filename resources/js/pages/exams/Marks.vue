<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Marks Entry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Record exam paper scores or grade evaluation values for class students.</p>
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
                    @change="handleSectionChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Section</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Subject <span class="text-rose-500">*</span></label>
                <select 
                    v-model="filters.subject_id"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Subject</option>
                    <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                </select>
            </div>

            <div class="flex items-end">
                <button 
                    @click="loadStudentsList"
                    :disabled="!filters.exam_id || !filters.class_id || !filters.section_id || !filters.subject_id || loading"
                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Load Students
                </button>
            </div>
        </div>

        <!-- Marks Setup Table -->
        <div v-if="loaded" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/60">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Record Grades & Scores</h3>
                    <p class="text-xs text-slate-400">Evaluation Type: <span class="capitalize font-bold">{{ subjectType }}</span> | Max Marks: <span class="font-bold">{{ maxMarks }}</span></p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/40">
                    {{ selectedExamName }} | Class: {{ selectedClassName }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6" style="width: 10%;">Roll No</th>
                            <th class="p-4" style="width: 15%;">Adm. No</th>
                            <th class="p-4" style="width: 25%;">Student Name</th>
                            <th class="p-4 text-center" style="width: 15%;">Attendance</th>
                            <th class="p-4 text-center" style="width: 20%;">
                                Score / Grade Obtained
                            </th>
                            <th class="p-4 pr-6" style="width: 15%;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="(stud, index) in studentsList" :key="stud.student_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold font-mono text-slate-500">
                                {{ stud.roll_no || '-' }}
                            </td>
                            <td class="p-4 font-mono text-slate-500">
                                {{ stud.admission_no }}
                            </td>
                            <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                {{ stud.name }}
                            </td>

                            <!-- Attendance toggle: Absent / Present -->
                            <td class="p-4 text-center">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="stud.is_absent"
                                        @change="if (stud.is_absent) { stud.marks_obtained = null; stud.grade_id = ''; }"
                                        class="rounded border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-rose-500 dark:bg-slate-950 disabled:opacity-50" 
                                    />
                                    <span :class="[stud.is_absent ? 'text-rose-600 font-bold' : 'text-slate-500']">Absent</span>
                                </label>
                            </td>

                            <!-- Input Mode: Marks -->
                            <td v-if="subjectType === 'marks'" class="p-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <template v-if="!stud.is_absent">
                                        <input 
                                            v-model.number="stud.marks_obtained" 
                                            type="number"
                                            min="0"
                                            :max="maxMarks"
                                            class="w-24 px-3 py-1.5 text-sm rounded-xl text-center border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                            placeholder="Marks"
                                        />
                                        <span class="text-xs text-slate-400">/ {{ maxMarks }}</span>
                                        
                                        <!-- Dynamic Auto Grade Symbol -->
                                        <span 
                                            v-if="stud.marks_obtained !== null && stud.marks_obtained !== ''" 
                                            class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40"
                                            title="Calculated Grade"
                                        >
                                            {{ calculateAutoGrade(stud.marks_obtained) }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 border border-rose-500/20 uppercase">Absent</span>
                                    </template>
                                </div>
                            </td>

                            <!-- Input Mode: Grades -->
                            <td v-else class="p-4 text-center">
                                <template v-if="!stud.is_absent">
                                    <select 
                                        v-model="stud.grade_id"
                                        class="w-full max-w-xs px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                    >
                                        <option value="">Select Grade</option>
                                        <option v-for="gr in gradesList" :key="gr.id" :value="gr.id">
                                            {{ gr.grade }}{{ gr.description ? ` (${gr.description})` : '' }}
                                        </option>
                                    </select>
                                </template>
                                <template v-else>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 border border-rose-500/20 uppercase">Absent</span>
                                </template>
                            </td>

                            <td class="p-4 pr-6">
                                <input 
                                    v-model="stud.remarks" 
                                    type="text"
                                    class="w-full px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                    placeholder="Remarks (e.g. Absent, sick, etc.)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Save -->
            <div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end bg-slate-50/30 dark:bg-slate-900/40">
                <button 
                    @click="saveMarks"
                    :disabled="saving || studentsList.length === 0"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                >
                    {{ saving ? 'Saving Marks...' : 'Save & Publish Marks' }}
                </button>
            </div>
        </div>

        <div v-else-if="loading" class="p-12 text-center text-slate-500">
            <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600 mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading students list for marks record...
        </div>

        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500 rounded-2xl shadow-sm">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Please select the Exam, Class, Section, and Subject then click "Load Students" to input mark sheets.
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

const authStore = useAuthStore();
const confirmStore = useConfirmStore();
const toastStore = useToastStore();

const exams = ref([]);
const classes = ref([]);
const sections = ref([]);
const subjects = ref([]);
const studentsList = ref([]);
const gradesList = ref([]);

const loading = ref(false);
const saving = ref(false);
const loaded = ref(false);

const selectedExamName = ref('');
const selectedClassName = ref('');
const subjectType = ref('marks');
const maxMarks = ref(100);
const examScheduleId = ref(null);

const filters = reactive({
    exam_id: '',
    class_id: '',
    section_id: '',
    subject_id: '',
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
        toastStore.error('Failed to load exams list.');
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
        toastStore.error('Failed to load classes.');
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

const handleSectionChange = () => {
    fetchSubjects();
};

const resetSelection = (clearClass = false) => {
    loaded.value = false;
    studentsList.value = [];
    if (clearClass) {
        filters.class_id = '';
        filters.section_id = '';
        sections.value = [];
    }
    filters.subject_id = '';
    subjects.value = [];
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
        toastStore.error('Failed to load class sections.');
    }
};

const fetchSubjects = async () => {
    if (!filters.class_id || !filters.section_id) {
        subjects.value = [];
        filters.subject_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/subjects', {
            params: {
                all: true,
                class_id: filters.class_id,
                section_id: filters.section_id,
                academic_year_id: filters.academic_year_id,
                exam_id: filters.exam_id
            }
        });
        subjects.value = response.data.subjects || [];
        filters.subject_id = '';
    } catch (e) {
        toastStore.error('Failed to load subjects.');
    }
};

const loadStudentsList = async () => {
    loading.value = true;
    loaded.value = false;
    try {
        const exObj = exams.value.find(e => e.id === filters.exam_id);
        const clsObj = classes.value.find(c => c.id === filters.class_id);
        const secObj = sections.value.find(s => s.id === filters.section_id);
        const subObj = subjects.value.find(sb => sb.id === filters.subject_id);

        selectedExamName.value = exObj ? exObj.name : '';
        selectedClassName.value = (clsObj ? clsObj.name : '') + ' - ' + (secObj ? secObj.name : '');

        const params = {
            academic_year_id: filters.academic_year_id,
            exam_id: filters.exam_id,
            class_id: filters.class_id,
            section_id: filters.section_id,
            subject_id: filters.subject_id
        };

        const response = await window.axios.get('/api/exam-marks/students', { params });
        studentsList.value = (response.data.students || []).map(s => ({
            ...s,
            grade_id: s.grade_id || '',
            is_absent: !!s.is_absent
        }));
        gradesList.value = response.data.grades || [];
        
        const subjectData = response.data.subject;
        const scheduleData = response.data.schedule;

        subjectType.value = subjectData ? subjectData.evaluation_type : 'marks';
        maxMarks.value = scheduleData ? scheduleData.max_marks : 100;
        examScheduleId.value = scheduleData ? scheduleData.id : null;

        loaded.value = true;
    } catch (e) {
        if (e.response?.status === 422) {
            toastStore.info(e.response.data.message || 'Validation error occurred.');
        } else {
            toastStore.error('Failed to load students record for marks entry.');
        }
    } finally {
        loading.value = false;
    }
};

const calculateAutoGrade = (marksObtained) => {
    if (marksObtained === null || marksObtained === '') return '-';
    const pct = (marksObtained / maxMarks.value) * 100;
    
    // Match in gradesList
    const matched = gradesList.value
        .filter(g => g.min_percentage <= pct)
        .sort((a, b) => b.min_percentage - a.min_percentage)[0];

    return matched ? matched.grade : '-';
};
const saveMarks = async () => {
    // Validate marks limit (only for present students)
    if (subjectType.value === 'marks') {
        const outOfRange = studentsList.value.some(s => !s.is_absent && (s.marks_obtained > maxMarks.value || s.marks_obtained < 0));
        if (outOfRange) {
            confirmStore.show({
                title: 'Invalid Marks Entry',
                message: `Marks obtained cannot exceed the maximum marks (${maxMarks.value}) or be negative. Please check all entries.`,
                type: 'danger',
                confirmText: 'OK',
                cancelText: ''
            });
            return;
        }
    }

    saving.value = true;
    try {
        const payload = {
            academic_year_id: filters.academic_year_id,
            exam_id: filters.exam_id,
            exam_schedule_id: examScheduleId.value,
            subject_id: filters.subject_id,
            subject_evaluation_type: subjectType.value, // Pass to backend for auto grading
            marks: studentsList.value.map(s => ({
                student_id: s.student_id,
                marks_obtained: s.is_absent ? null : ((s.marks_obtained === '' || s.marks_obtained === null || s.marks_obtained === undefined) ? null : Number(s.marks_obtained)),
                is_absent: !!s.is_absent,
                grade_id: s.is_absent ? null : (s.grade_id || null),
                remarks: s.remarks || null
            }))
        };

        // Determine correct schedule ID
        // The backend resolves exam_schedule_id accurately by validating foreign keys.
        // Let's pass the correct schedule_id fetched from API.
        const response = await window.axios.post('/api/exam-marks/save', payload);
        toastStore.success(response.data.message || 'Marks saved successfully.');
    } catch (e) {
        if (e.response?.status === 422) {
            const errors = Object.values(e.response.data.errors).flat().join('\n');
            toastStore.error(errors);
        } else {
            toastStore.error('Failed to save examination marks.');
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchAcademicYears();
    fetchExams();
    fetchClasses();
});
</script>
