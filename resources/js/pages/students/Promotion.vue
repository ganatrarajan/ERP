<template>
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Student Promotion Wizard</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Promote students in batches to the next academic session, class, and section.</p>
            </div>
            <router-link 
                to="/students" 
                class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors"
            >
                Back to Directory
            </router-link>
        </div>

        <!-- Non-Current Target Session Banner -->
        <div v-if="target.academic_year_id && !isTargetCurrentYear" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Non-Current Target Session Alert:</span> You are selecting an academic session as the target that is not currently marked as the active/current system session. Please verify that this is your intended destination.
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Panel: Session & Placement Selectors -->
            <div class="space-y-6">
                <!-- Source Setup Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-rose-500 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Source (Promote From)
                    </h3>

                    <!-- Academic Year -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Academic Year</label>
                        <select 
                            v-model="source.academic_year_id" 
                            @change="onSourceYearChange"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-rose-500 text-slate-800 dark:text-slate-200"
                        >
                            <option value="" disabled>Select Year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current)</span>
                            </option>
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Class</label>
                        <select 
                            v-model="source.class_id" 
                            @change="onSourceClassChange"
                            :disabled="!source.academic_year_id"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-rose-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                        >
                            <option value="" disabled>Select Class</option>
                            <option v-for="cls in sourceClasses" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Section</label>
                        <select 
                            v-model="source.section_id" 
                            :disabled="!source.class_id"
                            @change="loadSourceStudents"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-rose-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                        >
                            <option value="" disabled>Select Section</option>
                            <option v-for="sec in sourceSections" :key="sec.id" :value="sec.id">
                                {{ sec.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Target Setup Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-emerald-500 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Target (Promote To)
                    </h3>

                    <!-- Academic Year -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Academic Year</label>
                        <select 
                            v-model="target.academic_year_id" 
                            @change="onTargetYearChange"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-800 dark:text-slate-200"
                        >
                            <option value="" disabled>Select Year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current)</span>
                            </option>
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Class</label>
                        <select 
                            v-model="target.class_id" 
                            @change="onTargetClassChange"
                            :disabled="!target.academic_year_id"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                        >
                            <option value="" disabled>Select Class</option>
                            <option v-for="cls in targetClasses" :key="cls.id" :value="cls.id">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400">Section</label>
                        <select 
                            v-model="target.section_id" 
                            :disabled="!target.class_id"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                        >
                            <option value="" disabled>Select Section</option>
                            <option v-for="sec in targetSections" :key="sec.id" :value="sec.id">
                                {{ sec.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Student Selection Table & Promote Button -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
                    
                    <!-- Title/Action Bar -->
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center flex-wrap gap-2">
                        <div>
                            <h3 class="font-extrabold text-slate-800 dark:text-white text-base">Select Students to Promote</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-450">Check mark students who passed validation rules and are ready for advancement.</p>
                        </div>
                        <div class="flex items-center gap-1.5" v-if="students.length > 0">
                            <button 
                                @click="selectAll" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold transition-all"
                            >
                                Select All
                            </button>
                            <button 
                                @click="selectNone" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold transition-all"
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Students Table Container -->
                    <div class="flex-1 overflow-y-auto min-h-[300px]">
                        <div v-if="loadingStudents" class="p-12 text-center animate-pulse space-y-3">
                            <div class="h-6 bg-slate-200 dark:bg-slate-800 w-1/2 mx-auto rounded-md"></div>
                            <div class="h-4 bg-slate-200 dark:bg-slate-800 w-1/3 mx-auto rounded-md"></div>
                        </div>

                        <div v-else-if="!source.section_id" class="p-12 text-center text-slate-400">
                            <svg class="w-14 h-14 mx-auto mb-3 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                            Please configure the Source placement to load the class directory.
                        </div>

                        <div v-else-if="students.length === 0" class="p-12 text-center text-slate-500">
                            No active students found in this section.
                        </div>

                        <table v-else class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/20 dark:bg-slate-900/30">
                                    <th class="p-4 pl-6 w-12">Select</th>
                                    <th class="p-4">Admission No</th>
                                    <th class="p-4">Student Name</th>
                                    <th class="p-4">Roll No</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                                <tr v-for="std in students" :key="std.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="p-4 pl-6">
                                        <input 
                                            type="checkbox" 
                                            :value="std.id" 
                                            v-model="selectedStudentIds"
                                            class="w-4.5 h-4.5 text-indigo-600 rounded border-slate-350 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-950"
                                        />
                                    </td>
                                    <td class="p-4 font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ std.admission_no }}
                                    </td>
                                    <td class="p-4 font-bold text-slate-800 dark:text-white">
                                        {{ std.first_name }} {{ std.last_name }}
                                    </td>
                                    <td class="p-4">
                                        {{ std.roll_no || 'N/A' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Action Bar -->
                    <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center flex-wrap gap-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-450">
                            Selected: {{ selectedStudentIds.length }} / {{ students.length }} students
                        </span>
                        
                        <div class="flex items-center gap-2">
                            <span v-if="serverError" class="text-xs text-rose-500 bg-rose-500/10 px-3 py-1.5 rounded-lg border border-rose-500/20">
                                {{ serverError }}
                            </span>
                            <span v-if="target.academic_year_id && !isTargetCurrentYear" class="text-xs text-amber-600 bg-amber-500/10 px-3 py-1.5 rounded-lg border border-amber-500/20">
                                Target year is not current.
                            </span>

                            <button 
                                @click="executePromotion"
                                :disabled="submitting || selectedStudentIds.length === 0 || !target.section_id"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:scale-100 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-indigo-600/10 active:scale-95 transition-all flex items-center gap-1.5"
                            >
                                <span v-if="submitting">Processing Batch...</span>
                                <span v-else>Execute Promotion</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'StudentPromotion',
    setup() {
        const router = useRouter();
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();

        // Metadata lists
        const academicYears = ref([]);
        const allClasses = ref([]);
        const allSections = ref([]);

        // Source filter selection
        const source = ref({
            academic_year_id: '',
            class_id: '',
            section_id: ''
        });

        // Target placement selection
        const target = ref({
            academic_year_id: '',
            class_id: '',
            section_id: ''
        });

        // Dependent dropdowns
        const sourceClasses = ref([]);
        const sourceSections = ref([]);
        const targetClasses = ref([]);
        const targetSections = ref([]);

        // Source class list loaded students
        const students = ref([]);
        const selectedStudentIds = ref([]);
        const loadingStudents = ref(false);

        const submitting = ref(false);
        const serverError = ref('');

        const isTargetCurrentYear = computed(() => {
            if (!target.value.academic_year_id) return true;
            const selected = academicYears.value.find(y => y.id === parseInt(target.value.academic_year_id));
            return selected ? !!selected.is_current : false;
        });

        const fetchMetadata = async () => {
            try {
                const yResponse = await window.axios.get('/api/academic-years', { params: { status: 'active' } });
                academicYears.value = yResponse.data.academic_years;

                const cResponse = await window.axios.get('/api/classes', { params: { status: 'active', ignore_academic_year: true } });
                allClasses.value = cResponse.data.classes;

                const sResponse = await window.axios.get('/api/sections', { params: { status: 'active', ignore_academic_year: true } });
                allSections.value = sResponse.data.sections;

                // Default setup for current academic year
                const current = academicYears.value.find(y => y.is_current);
                if (current) {
                    source.value.academic_year_id = current.id;
                    onSourceYearChange();
                }
            } catch (error) {
                console.error(error);
            }
        };

        // Source filters changes
        const onSourceYearChange = () => {
            sourceClasses.value = allClasses.value.filter(
                c => c.academic_year_id === parseInt(source.value.academic_year_id)
            );
            source.value.class_id = '';
            source.value.section_id = '';
            sourceSections.value = [];
            students.value = [];
            selectedStudentIds.value = [];
        };

        const onSourceClassChange = () => {
            sourceSections.value = allSections.value.filter(
                s => s.class_id === parseInt(source.value.class_id)
            );
            source.value.section_id = '';
            students.value = [];
            selectedStudentIds.value = [];
        };

        // Target filters changes
        const onTargetYearChange = () => {
            targetClasses.value = allClasses.value.filter(
                c => c.academic_year_id === parseInt(target.value.academic_year_id)
            );
            target.value.class_id = '';
            target.value.section_id = '';
            targetSections.value = [];
        };

        const onTargetClassChange = () => {
            targetSections.value = allSections.value.filter(
                s => s.class_id === parseInt(target.value.class_id)
            );
            target.value.section_id = '';
        };

        // Load students in source section
        const loadSourceStudents = async () => {
            if (!source.value.section_id) return;
            loadingStudents.value = true;
            students.value = [];
            selectedStudentIds.value = [];
            try {
                const response = await window.axios.get('/api/students', {
                    params: {
                        academic_year_id: source.value.academic_year_id,
                        class_id: source.value.class_id,
                        section_id: source.value.section_id,
                        status: 'active',
                        per_page: 200 // Fetch all in this section
                    }
                });
                students.value = response.data.data;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load students in source class placement.');
            } finally {
                loadingStudents.value = false;
            }
        };

        const selectAll = () => {
            selectedStudentIds.value = students.value.map(s => s.id);
        };

        const selectNone = () => {
            selectedStudentIds.value = [];
        };

        const executePromotion = async () => {
            serverError.value = '';
            
            // Basic guard validation
            if (parseInt(source.value.academic_year_id) === parseInt(target.value.academic_year_id)) {
                const proceed = await confirmStore.show({
                    title: 'Promotion in Same Session',
                    message: 'You have selected the same source and target academic session. Are you sure you want to transfer these students inside the same session?',
                    type: 'warning',
                    confirmText: 'Yes, Proceed',
                    cancelText: 'Cancel'
                });
                if (!proceed) return;
            }

            const confirmed = await confirmStore.show({
                title: 'Confirm Student Promotion Batch',
                message: `Are you sure you want to promote ${selectedStudentIds.value.length} selected students to the new target placement? This will create new academic placement snapshots for the next session.`,
                type: 'info',
                confirmText: 'Promote Students',
                cancelText: 'Cancel'
            });

            if (!confirmed) return;

            submitting.value = true;
            try {
                await window.axios.post('/api/promotions', {
                    student_ids: selectedStudentIds.value,
                    from_academic_year_id: source.value.academic_year_id,
                    from_class_id: source.value.class_id,
                    from_section_id: source.value.section_id,
                    to_academic_year_id: target.value.academic_year_id,
                    to_class_id: target.value.class_id,
                    to_section_id: target.value.section_id
                });
                toastStore.success('Batch promotion completed successfully.');
                router.push('/students');
            } catch (error) {
                console.error(error);
                serverError.value = error.response?.data?.message || 'Promotion request failed.';
            } finally {
                submitting.value = false;
            }
        };

        onMounted(() => {
            fetchMetadata();
        });

        return {
            academicYears,
            source,
            target,
            sourceClasses,
            sourceSections,
            targetClasses,
            targetSections,
            students,
            selectedStudentIds,
            loadingStudents,
            submitting,
            serverError,
            authStore,
            onSourceYearChange,
            onSourceClassChange,
            onTargetYearChange,
            onTargetClassChange,
            loadSourceStudents,
            selectAll,
            selectNone,
            executePromotion,
            isTargetCurrentYear
        };
    }
}
</script>
