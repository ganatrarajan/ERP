<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Optional Subjects Assignment</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Map student-wise optional subjects. Students can choose a maximum of 4 optional subjects.</p>
            </div>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loading" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Configuring optional subjects is disabled.
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Session / Academic Year</label>
                <select 
                    v-model="filters.academic_year_id" 
                    @change="handleAcademicYearChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }} <span v-if="year.is_current">(Current)</span>
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                <select 
                    v-model="filters.class_id" 
                    @change="fetchSections"
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
                    @change="loadGridData"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Section</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>
            </div>

            <div class="flex items-end">
                <button 
                    @click="loadGridData"
                    :disabled="!filters.class_id || !filters.section_id || loading"
                    class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18.24"></path></svg>
                    Reload
                </button>
            </div>
        </div>

        <!-- Assignment Grid -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <!-- Loading Indicator -->
            <div v-if="loading" class="p-12 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <!-- No Selection State -->
            <div v-else-if="!filters.class_id || !filters.section_id" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-750 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                Please select Class and Section to load the assignment grid.
            </div>

            <!-- Empty Students State -->
            <div v-else-if="students.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-750 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                No active students found in the selected section.
            </div>

            <!-- Empty Optional Subjects State -->
            <div v-else-if="optionalSubjects.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-750 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                No subjects marked as optional for the selected class. Mark subjects as optional in the Subject List first.
            </div>

            <!-- Grid Content -->
            <div v-else class="flex flex-col">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" style="min-width: 250px;">Student Information</th>
                                <th v-for="subj in optionalSubjects" :key="subj.id" class="p-4 text-center">
                                    <span class="block font-semibold text-slate-800 dark:text-white">{{ subj.name }}</span>
                                    <span class="font-mono text-[10px] text-slate-400 font-normal">{{ subj.code || 'No Code' }}</span>
                                </th>
                                <th class="p-4 pr-6 text-center" style="width: 120px;">Total selected</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-350">
                            <tr v-for="student in students" :key="student.student_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6">
                                    <div class="font-semibold text-slate-800 dark:text-white">
                                        {{ student.name }}
                                    </div>
                                    <div class="text-xs text-slate-400 flex gap-3 mt-0.5">
                                        <span>Roll: <strong class="text-slate-600 dark:text-slate-300">{{ student.roll_no || 'N/A' }}</strong></span>
                                        <span>Admission No: <strong class="text-slate-600 dark:text-slate-300">{{ student.admission_no }}</strong></span>
                                    </div>
                                </td>

                                <td v-for="subj in optionalSubjects" :key="subj.id" class="p-4 text-center">
                                    <input 
                                        type="checkbox"
                                        :checked="isSelected(student.student_id, subj.id)"
                                        :disabled="isDisabled(student.student_id, subj.id) || !isCurrentYear"
                                        @change="toggleSubject(student.student_id, subj.id)"
                                        class="w-4 h-4 rounded border-slate-300 dark:border-slate-800 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-950 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                    />
                                </td>

                                <td class="p-4 pr-6 text-center">
                                    <span 
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold"
                                        :class="[
                                            getSelectedCount(student.student_id) === 4 ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700/60'
                                        ]"
                                    >
                                        {{ getSelectedCount(student.student_id) }} / 4
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Action Footer -->
                <div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/40">
                    <span class="text-xs text-slate-500 dark:text-slate-450">
                        Assignments will be saved class-wide. Verify counts (max 4 optional subjects) before saving.
                    </span>
                    <button 
                        @click="saveAssignments"
                        :disabled="saving || !isCurrentYear"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all disabled:opacity-50"
                    >
                        {{ saving ? 'Saving Assignments...' : 'Save Assignments' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';

const academicYears = ref([]);
const classes = ref([]);
const sections = ref([]);
const students = ref([]);
const optionalSubjects = ref([]);
const studentAssignments = ref({}); // key: student_id, value: array of subject_ids

const loading = ref(false);
const saving = ref(false);

const isCurrentYear = computed(() => {
    if (!filters.academic_year_id) return true;
    const selected = academicYears.value.find(y => y.id === filters.academic_year_id);
    return selected ? !!selected.is_current : false;
});

const filters = reactive({
    academic_year_id: '',
    class_id: '',
    section_id: '',
});

const fetchAcademicYears = async () => {
    try {
        const response = await window.axios.get('/api/academic-years');
        academicYears.value = response.data.academic_years;
        const currentYear = academicYears.value.find(y => y.is_current);
        if (currentYear) {
            filters.academic_year_id = currentYear.id;
        }
    } catch (e) {
        window.toastr?.error('Failed to load academic sessions.');
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

const handleAcademicYearChange = async () => {
    filters.class_id = '';
    filters.section_id = '';
    sections.value = [];
    students.value = [];
    optionalSubjects.value = [];
    studentAssignments.value = {};
    await fetchClasses();
};

const fetchSections = async () => {
    filters.section_id = '';
    sections.value = [];
    students.value = [];
    optionalSubjects.value = [];
    studentAssignments.value = {};
    if (!filters.class_id) return;
    
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: filters.class_id, all: true } });
        sections.value = response.data.sections || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const loadGridData = async () => {
    if (!filters.class_id || !filters.section_id) return;
    loading.value = true;
    try {
        const response = await window.axios.get('/api/optional-subjects', {
            params: {
                academic_year_id: filters.academic_year_id,
                class_id: filters.class_id,
                section_id: filters.section_id,
            }
        });
        
        students.value = response.data.students || [];
        optionalSubjects.value = response.data.optional_subjects || [];
        
        // Populate current assignments
        const loadedAssignments = response.data.assignments || {};
        const assignmentsMap = {};
        
        // Ensure student assignments are initialized for all loaded students
        students.value.forEach(student => {
            const studentId = student.student_id;
            assignmentsMap[studentId] = loadedAssignments[studentId] ? loadedAssignments[studentId].map(id => Number(id)) : [];
        });
        
        studentAssignments.value = assignmentsMap;
    } catch (e) {
        window.toastr?.error('Failed to load students and optional subjects.');
    } finally {
        loading.value = false;
    }
};

const getSelectedCount = (studentId) => {
    return (studentAssignments.value[studentId] || []).length;
};

const isSelected = (studentId, subjectId) => {
    return (studentAssignments.value[studentId] || []).includes(Number(subjectId));
};

const isDisabled = (studentId, subjectId) => {
    const sId = Number(subjectId);
    return !isSelected(studentId, sId) && getSelectedCount(studentId) >= 4;
};

const toggleSubject = (studentId, subjectId) => {
    const sId = Number(subjectId);
    if (!studentAssignments.value[studentId]) {
        studentAssignments.value[studentId] = [];
    }
    
    const index = studentAssignments.value[studentId].indexOf(sId);
    if (index > -1) {
        studentAssignments.value[studentId].splice(index, 1);
    } else {
        if (studentAssignments.value[studentId].length >= 4) {
            window.toastr?.warning('A student can select a maximum of 4 optional subjects.');
            return;
        }
        studentAssignments.value[studentId].push(sId);
    }
};

const saveAssignments = async () => {
    saving.value = true;
    try {
        await window.axios.post('/api/optional-subjects', {
            academic_year_id: filters.academic_year_id,
            assignments: studentAssignments.value,
        });
        window.toastr?.success('Optional subject assignments saved successfully.');
        loadGridData();
    } catch (e) {
        if (e.response?.status === 422) {
            const errors = Object.values(e.response.data.errors).flat().join('\n');
            window.toastr?.error(errors);
        } else {
            window.toastr?.error('An error occurred while saving.');
        }
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await fetchAcademicYears();
    await fetchClasses();
});
</script>

<style scoped>
</style>
