<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Exam Schedules</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Schedule dates, timings, and maximum marks for subject papers across classes and sections.</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4">
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
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">Select Section</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>
            </div>

            <div class="flex items-end">
                <button 
                    @click="loadSubjectsForSchedule"
                    :disabled="!filters.exam_id || !filters.class_id || !filters.section_id || loading"
                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18"></path></svg>
                    Load & Setup Schedule
                </button>
            </div>
        </div>

        <!-- Schedule Setup Table -->
        <div v-if="loaded" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 dark:bg-slate-900/60">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Configure Papers Schedule</h3>
                    <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/40 mt-1 inline-block">
                        {{ selectedExamName }} | Class: {{ selectedClassName }}
                    </span>
                </div>
                
                <div class="flex items-center gap-4 w-full sm:w-auto">
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

                    <button 
                        @click="downloadAdmitCards"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-600/10 transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Download Admit Cards
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6" style="width: 25%;">Subject</th>
                            <th class="p-4" style="width: 20%;">Exam Date <span class="text-rose-500">*</span></th>
                            <th class="p-4" style="width: 15%;">Start Time <span class="text-rose-500">*</span></th>
                            <th class="p-4" style="width: 15%;">End Time <span class="text-rose-500">*</span></th>
                            <th class="p-4 text-center" style="width: 10%;">Max Marks <span class="text-rose-500">*</span></th>
                            <th class="p-4 pr-6" style="width: 15%;">Report Card Visibility</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="(sched, index) in scheduleList" :key="sched.subject_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6">
                                <div><strong>{{ sched.subject_name }}</strong></div>
                                <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ sched.subject_code || 'No Code' }}</div>
                            </td>
                            <td class="p-4">
                                <input 
                                    v-model="sched.exam_date" 
                                    v-datepicker
                                    type="text"
                                    placeholder="YYYY-MM-DD"
                                    required
                                    class="w-full px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                    :class="{ 'border-rose-450 dark:border-rose-800 focus:ring-rose-500': checkHoliday(sched.exam_date) }"
                                  />
                                <div v-if="checkHoliday(sched.exam_date)" class="text-[10px] text-rose-500 dark:text-rose-400 font-semibold mt-1 flex items-center gap-1">
                                    <span>⚠️ Holiday: {{ checkHoliday(sched.exam_date) }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <input 
                                    v-model="sched.start_time" 
                                    type="time"
                                    required
                                    class="w-full px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                />
                            </td>
                            <td class="p-4">
                                <input 
                                    v-model="sched.end_time" 
                                    type="time"
                                    required
                                    class="w-full px-3 py-1.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                />
                            </td>
                            <td class="p-4 text-center">
                                <span v-if="sched.evaluation_type === 'grades'" class="text-slate-400 font-medium">-</span>
                                <input 
                                    v-else
                                    v-model.number="sched.max_marks" 
                                    type="number"
                                    min="1"
                                    required
                                    class="w-20 px-3 py-1.5 text-sm rounded-xl text-center border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                />
                            </td>
                            <td class="p-4 pr-6">
                                <select 
                                    v-model="sched.report_card_visibility" 
                                    class="w-full px-2 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                >
                                    <option value="">Use Subject Default</option>
                                    <option value="included_in_result">Included in Result</option>
                                    <option value="display_only">Display Only</option>
                                    <option value="hidden">Hidden</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Save -->
            <div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end bg-slate-50/30 dark:bg-slate-900/40">
                <button 
                    @click="saveSchedule"
                    :disabled="saving || scheduleList.length === 0"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                >
                    {{ saving ? 'Saving Schedule...' : 'Save Exam Schedule' }}
                </button>
            </div>
        </div>

        <div v-else-if="loading" class="p-12 text-center text-slate-500">
            <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600 mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading schedule configurations...
        </div>

        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-500 rounded-2xl shadow-sm">
            <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Please select the Exam, Class, and Section and click "Load & Setup Schedule" to configure exam details.
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
const scheduleList = ref([]);
const weekendSettings = ref([]);
const holidayList = ref([]);

const loading = ref(false);
const saving = ref(false);
const loaded = ref(false);
const includeGraded = ref('no');

const selectedExamName = ref('');
const selectedClassName = ref('');

const filters = reactive({
    exam_id: '',
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
        window.toastr?.error('Failed to load exams master.');
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
    filters.class_id = '';
    filters.section_id = '';
    sections.value = [];
    scheduleList.value = [];
    loaded.value = false;
    await fetchClasses();
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

const loadSubjectsForSchedule = async () => {
    loading.value = true;
    loaded.value = false;
    try {
        // Find names for heading
        const exObj = exams.value.find(e => e.id === filters.exam_id);
        const clsObj = classes.value.find(c => c.id === filters.class_id);
        const secObj = sections.value.find(s => s.id === filters.section_id);
        
        selectedExamName.value = exObj ? exObj.name : '';
        selectedClassName.value = (clsObj ? clsObj.name : '') + ' - ' + (secObj ? secObj.name : '');
        filters.academic_year_id = exObj ? exObj.academic_year_id : '';

        // Fetch weekend settings and holidays in parallel for real-time warning checks
        try {
            const [wsRes, hRes] = await Promise.all([
                window.axios.get('/api/weekend-settings'),
                window.axios.get('/api/holidays', { params: { academic_year_id: filters.academic_year_id } })
            ]);
            weekendSettings.value = wsRes.data.settings || [];
            holidayList.value = hRes.data.holidays || [];
        } catch (err) {
            console.error('Failed to load weekend settings or holidays for warnings.', err);
        }

        // Fetch all subjects for this class/section
        const subResponse = await window.axios.get('/api/subjects', {
            params: {
                all: true,
                class_id: filters.class_id,
                section_id: filters.section_id,
                academic_year_id: filters.academic_year_id
            }
        });
        const subjects = subResponse.data.subjects || [];

        // Fetch existing schedules
        const schedResponse = await window.axios.get('/api/exam-schedules', {
            params: {
                exam_id: filters.exam_id,
                class_id: filters.class_id,
                section_id: filters.section_id,
                academic_year_id: filters.academic_year_id
            }
        });
        const schedules = schedResponse.data.schedules || [];
        const schedBySubject = {};
        schedules.forEach(s => {
            schedBySubject[s.subject_id] = s;
        });

        // Combine
        scheduleList.value = subjects.map(sub => {
            const existing = schedBySubject[sub.id];
            return {
                subject_id: sub.id,
                subject_name: sub.name,
                subject_code: sub.code,
                evaluation_type: sub.evaluation_type || 'marks',
                exam_date: existing ? existing.exam_date : '',
                start_time: existing ? existing.start_time : '09:00',
                end_time: existing ? existing.end_time : '12:00',
                max_marks: existing ? existing.max_marks : (sub.maximum_marks || 100),
                report_card_visibility: existing ? (existing.report_card_visibility || '') : '',
            };
        });

        loaded.value = true;
    } catch (e) {
        window.toastr?.error('Failed to load schedule detail.');
    } finally {
        loading.value = false;
    }
};

const saveSchedule = async () => {
    // Filter out subjects that do not have an exam date scheduled
    const activeSchedules = scheduleList.value.filter(s => s.exam_date && s.exam_date !== '');

    if (activeSchedules.length === 0) {
        window.toastr?.error('Please configure an exam date for at least one subject.');
        return;
    }

    // Validate that if they filled exam_date, they also filled start_time, end_time, and max_marks
    const invalid = activeSchedules.some(s => !s.start_time || !s.end_time || !s.max_marks);
    if (invalid) {
        window.toastr?.error('Please enter start time, end time, and max marks for all scheduled exams.');
        return;
    }

    // Validate dates are within Exam start/end range
    const exObj = exams.value.find(e => e.id === filters.exam_id);
    if (exObj) {
        const start = new Date(exObj.start_date);
        start.setHours(0, 0, 0, 0);
        const end = new Date(exObj.end_date);
        end.setHours(23, 59, 59, 999);

        for (const s of activeSchedules) {
            const examDate = new Date(s.exam_date);
            examDate.setHours(12, 0, 0, 0);
            if (examDate < start || examDate > end) {
                window.toastr?.error(`The exam date for "${s.subject_name}" (${s.exam_date}) must be between ${exObj.start_date} and ${exObj.end_date}.`);
                return;
            }
        }
    }

    saving.value = true;
    try {
        const payload = {
            academic_year_id: filters.academic_year_id,
            exam_id: filters.exam_id,
            class_id: filters.class_id,
            section_id: filters.section_id,
            schedules: activeSchedules.map(s => ({
                subject_id: s.subject_id,
                exam_date: s.exam_date,
                start_time: s.start_time,
                end_time: s.end_time,
                max_marks: s.max_marks,
                report_card_visibility: s.report_card_visibility || null
            }))
        };

        const response = await window.axios.post('/api/exam-schedules', payload);
        window.toastr?.success(response.data.message || 'Exam schedule saved successfully.');
    } catch (e) {
        if (e.response?.status === 422) {
            const errors = Object.values(e.response.data.errors).flat().join('\n');
            window.toastr?.error(errors);
        } else {
            window.toastr?.error('Failed to save exam schedule.');
        }
    } finally {
        saving.value = false;
    }
};

const downloadAdmitCards = () => {
    const queryParams = new URLSearchParams({
        academic_year_id: filters.academic_year_id,
        exam_id: filters.exam_id,
        class_id: filters.class_id,
        section_id: filters.section_id,
        include_graded: includeGraded.value
    }).toString();

    window.open(`/api/admit-cards/pdf?${queryParams}`, '_blank');
};

const checkHoliday = (dateStr) => {
    if (!dateStr) return null;
    
    // Parse date safely
    const parts = dateStr.split('-');
    if (parts.length !== 3) return null;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    const dateObj = new Date(year, month, day);
    
    if (isNaN(dateObj.getTime())) return null;
    
    const dayOfWeek = dateObj.getDay(); // 0 is Sunday, 6 is Saturday
    if (dayOfWeek === 0) {
        return 'Sunday';
    }

    // 2. Saturday check
    if (dayOfWeek === 6) {
        // Check school-wide Saturday settings
        const schoolWide = weekendSettings.value.some(s => s.target_type === 'all' && s.day_name === 'Saturday');
        if (schoolWide) {
            return 'Saturday Holiday';
        }
        
        // Check class/section Saturday settings
        const classSection = weekendSettings.value.some(s => 
            s.target_type === 'class_section' && 
            s.day_name === 'Saturday' && 
            s.class_id === filters.class_id &&
            (!s.section_id || s.section_id === filters.section_id)
        );
        if (classSection) {
            return 'Saturday Holiday';
        }
    }

    // 3. Holidays check
    const holiday = holidayList.value.find(h => h.holiday_date === dateStr && h.status === 'active');
    if (holiday) {
        if (holiday.target_type === 'all' || holiday.target_type === 'students') {
            return holiday.title;
        }
        if (holiday.target_type === 'class' && holiday.class_id === filters.class_id) {
            return holiday.title;
        }
        if (holiday.target_type === 'section' && holiday.class_id === filters.class_id && holiday.section_id === filters.section_id) {
            return holiday.title;
        }
    }

    return null;
};

onMounted(() => {
    fetchAcademicYears();
    fetchExams();
    fetchClasses();
});
</script>
