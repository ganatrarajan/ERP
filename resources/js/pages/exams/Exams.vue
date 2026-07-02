<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Exams Master</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Schedule academic exams, assign periods, and manage publishing status for student report cards.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('exam.create')"
                @click="openModal()"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow shadow-indigo-600/10 transition-all flex items-center gap-1.5 cursor-pointer border-none"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Exam
            </button>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Search input -->
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="handleSearch"
                        type="text" 
                        placeholder="Search exam name..." 
                        class="w-full pl-9 pr-4 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    />
                </div>

                <!-- Advanced Collapse Toggle -->
                <button 
                    @click="showAdvancedFilters = !showAdvancedFilters"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-800 rounded-xl text-xs text-slate-650 dark:text-slate-300 font-bold flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filters
                </button>
            </div>

            <!-- Collapsible drawer -->
            <div v-show="showAdvancedFilters" class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap gap-4 items-center">
                <!-- Exam Type Filter -->
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Exam Type:</label>
                    <select 
                        v-model="filters.exam_type_id" 
                        @change="fetchExams"
                        class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none cursor-pointer"
                    >
                        <option value="">All Types</option>
                        <option v-for="type in examTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status:</label>
                    <select 
                        v-model="filters.status" 
                        @change="fetchExams"
                        class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none cursor-pointer"
                    >
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>

                <button 
                    @click="resetFilters"
                    class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-955 text-xs font-bold text-slate-500 dark:text-slate-455 border border-slate-200 dark:border-slate-800/80 rounded-xl transition-all cursor-pointer"
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

            <div v-else-if="exams.length === 0" class="p-12 text-center text-slate-550 bg-slate-50/20 dark:bg-slate-900/10">
                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <h4 class="font-bold text-slate-700 dark:text-slate-300">No Exams Scheduled</h4>
                <p class="text-xs text-slate-400 mt-1">Refine your query filters or schedule a new exam to begin.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-550 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60 select-none">
                            <th class="p-4 pl-6">Exam Name</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Academic Year</th>
                            <th class="p-4 text-center">Start Date</th>
                            <th class="p-4 text-center">End Date</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-750 dark:text-slate-305">
                        <tr v-for="exam in exams" :key="exam.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-bold text-slate-800 dark:text-white">
                                {{ exam.name }}
                            </td>
                            <td class="p-4 text-xs font-medium text-slate-600 dark:text-slate-400">
                                {{ exam.exam_type?.name || 'N/A' }}
                            </td>
                            <td class="p-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ exam.academic_year?.title || 'N/A' }}
                            </td>
                            <td class="p-4 text-center text-xs font-mono">
                                {{ formatDate(exam.start_date) }}
                            </td>
                            <td class="p-4 text-center text-xs font-mono">
                                {{ formatDate(exam.end_date) }}
                            </td>
                            <td class="p-4">
                                <button 
                                    v-if="authStore.hasPermission('exam.edit') && isExamCurrentYear(exam)"
                                    @click="togglePublish(exam)"
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold border transition-colors cursor-pointer capitalize',
                                        exam.status === 'published' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border-amber-500/20'
                                    ]"
                                    :title="exam.status === 'published' ? 'Revert to Draft' : 'Publish Result scores'"
                                >
                                    {{ exam.status }}
                                </button>
                                <span v-else :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold border capitalize',
                                    exam.status === 'published' ? 'bg-emerald-500/10 text-emerald-650 border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-550/20'
                                ]">
                                    {{ exam.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit -->
                                    <button 
                                        v-if="authStore.hasPermission('exam.edit') && isExamCurrentYear(exam)"
                                        @click="openModal(exam)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
                                        title="Edit Exam Schedule"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('exam.delete') && isExamCurrentYear(exam)"
                                        @click="handleDelete(exam)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-455 border border-rose-500/20 rounded-lg transition-colors cursor-pointer"
                                        title="Delete Exam Master"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination list -->
            <div class="px-6 py-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-800">
                <span class="text-xs text-slate-500">Page {{ currentPage }} of {{ totalPages }}</span>
                <div class="flex items-center gap-1">
                    <button 
                        :disabled="currentPage === 1" 
                        @click="changePage(currentPage - 1)"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold rounded-lg disabled:opacity-50 cursor-pointer border-none"
                    >
                        Prev
                    </button>
                    <button 
                        :disabled="currentPage === totalPages" 
                        @click="changePage(currentPage + 1)"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold rounded-lg disabled:opacity-50 cursor-pointer border-none"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Exam Setup Form Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300 animate-fade-in">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Exam Setup' : 'Create Exam Setup' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors border-none bg-transparent cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveExam" class="p-6 space-y-4">
                    <!-- Academic Year -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Academic Session</label>
                        <select 
                            v-model="form.academic_year_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 cursor-pointer"
                        >
                            <option value="" disabled>Select Academic Year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current Active)</span>
                            </option>
                        </select>
                    </div>

                    <!-- Exam Type -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Exam Type Category</label>
                        <select 
                            v-model="form.exam_type_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 cursor-pointer"
                        >
                            <option value="" disabled>Select Category</option>
                            <option v-for="type in examTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Exam Name</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            required 
                            placeholder="e.g. Mid Term Examination"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
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
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="2"
                            placeholder="Provide exam instruction details"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all resize-none"
                        ></textarea>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                        <select 
                            v-model="form.status" 
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all cursor-pointer"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-650 dark:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all border-none bg-transparent cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1 border-none cursor-pointer"
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

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';

const authStore = useAuthStore();
const confirmStore = useConfirmStore();

const exams = ref([]);
const academicYears = ref([]);
const examTypes = ref([]);
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);

const showAdvancedFilters = ref(false);

const currentPage = ref(1);
const totalPages = ref(1);

const filters = reactive({
    search: '',
    exam_type_id: '',
    status: ''
});

const form = ref({
    academic_year_id: '',
    exam_type_id: '',
    name: '',
    start_date: '',
    end_date: '',
    description: '',
    status: 'draft'
});

const resetFilters = () => {
    filters.search = '';
    filters.exam_type_id = '';
    filters.status = '';
    currentPage.value = 1;
    fetchExams();
};

const isFormCurrentYear = computed(() => {
    if (!form.value.academic_year_id) return true;
    const year = academicYears.value.find(y => y.id === parseInt(form.value.academic_year_id));
    return year ? !!year.is_current : false;
});

const isExamCurrentYear = (exam) => {
    const year = academicYears.value.find(y => y.id === exam.academic_year_id);
    return year ? !!year.is_current : false;
};

let searchTimeout = null;

const fetchAcademicYears = async () => {
    try {
        const response = await window.axios.get('/api/academic-years');
        academicYears.value = response.data.academic_years;
        const currentYear = academicYears.value.find(y => y.is_current);
        if (currentYear) {
            form.value.academic_year_id = currentYear.id;
        }
    } catch (e) {
        window.toastr?.error('Failed to load academic sessions.');
    }
};

const fetchExamTypes = async () => {
    try {
        const response = await window.axios.get('/api/exam-types', { params: { all: true } });
        examTypes.value = response.data.exam_types || [];
    } catch (e) {
        window.toastr?.error('Failed to load exam categories.');
    }
};

const fetchExams = async () => {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            search: filters.search,
            exam_type_id: filters.exam_type_id,
            status: filters.status
        };
        const response = await window.axios.get('/api/exams', { params });
        exams.value = response.data.data;
        totalPages.value = response.data.last_page;
    } catch (e) {
        window.toastr?.error('Failed to load exams list.');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchExams();
    }, 300);
};

const changePage = (page) => {
    currentPage.value = page;
    fetchExams();
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
};

const togglePublish = async (exam) => {
    try {
        const response = await window.axios.patch(`/api/exams/${exam.id}/toggle-publish`);
        exam.status = response.data.exam.status;
        window.toastr?.success(response.data.message || 'Status updated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to update publish status.');
    }
};

const openModal = (exam = null) => {
    if (exam) {
        editingId.value = exam.id;
        form.value = {
            academic_year_id: exam.academic_year_id,
            exam_type_id: exam.exam_type_id,
            name: exam.name,
            start_date: exam.start_date,
            end_date: exam.end_date,
            description: exam.description || '',
            status: exam.status
        };
    } else {
        editingId.value = null;
        const currentYear = academicYears.value.find(y => y.is_current);
        form.value = {
            academic_year_id: currentYear ? currentYear.id : '',
            exam_type_id: '',
            name: '',
            start_date: '',
            end_date: '',
            description: '',
            status: 'draft'
        };
    }
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editingId.value = null;
};

const saveExam = async () => {
    // Validation checks
    if (new Date(form.value.start_date) > new Date(form.value.end_date)) {
        window.toastr?.error('Start date must be before or equal to End date.');
        return;
    }

    // Frontend validation preventing duplicate exam names in the same academic year
    const isDuplicate = exams.value.some(e => 
        e.name.toLowerCase() === form.value.name.trim().toLowerCase() && 
        e.academic_year_id === parseInt(form.value.academic_year_id) &&
        e.id !== editingId.value
    );
    if (isDuplicate) {
        window.toastr?.error('An exam with this name already exists in the selected academic session.');
        return;
    }

    saving.value = true;
    try {
        if (editingId.value) {
            await window.axios.put(`/api/exams/${editingId.value}`, form.value);
            window.toastr?.success('Exam updated successfully.');
        } else {
            await window.axios.post('/api/exams', form.value);
            window.toastr?.success('Exam created successfully.');
        }
        closeModal();
        fetchExams();
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

const handleDelete = async (exam) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Exam',
        message: `Are you sure you want to delete exam "${exam.name}"? This will soft delete all child marks and schedules associated with this exam.`,
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/exams/${exam.id}`);
            window.toastr?.success('Exam deleted successfully.');
            fetchExams();
        } catch (e) {
            window.toastr?.error('Failed to delete exam.');
        }
    }
};

onMounted(() => {
    fetchAcademicYears();
    fetchExamTypes();
    fetchExams();
});
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
