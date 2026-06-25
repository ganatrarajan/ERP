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
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Exam
            </button>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="col-span-1 sm:col-span-2">
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="handleSearch"
                        type="text" 
                        placeholder="Search exams..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Exam Type</label>
                <select 
                    v-model="filters.exam_type_id" 
                    @change="fetchExams"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Types</option>
                    <option v-for="type in examTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select 
                    v-model="filters.status" 
                    @change="fetchExams"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="exams.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                No exams found.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Exam Name</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Academic Year</th>
                            <th class="p-4 text-center">Start Date</th>
                            <th class="p-4 text-center">End Date</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="exam in exams" :key="exam.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ exam.name }}
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400">
                                {{ exam.exam_type?.name || 'N/A' }}
                            </td>
                            <td class="p-4 text-slate-600 dark:text-slate-400 font-medium">
                                {{ exam.academic_year?.title || 'N/A' }}
                            </td>
                            <td class="p-4 text-center text-slate-600 dark:text-slate-400 font-mono">
                                {{ formatDate(exam.start_date) }}
                            </td>
                            <td class="p-4 text-center text-slate-600 dark:text-slate-400 font-mono">
                                {{ formatDate(exam.end_date) }}
                            </td>
                            <td class="p-4">
                                <button 
                                    v-if="authStore.hasPermission('exam.edit') && isExamCurrentYear(exam)"
                                    @click="togglePublish(exam)"
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize transition-all border active:scale-95',
                                        exam.status === 'published' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border-amber-500/20 hover:bg-amber-500/20'
                                    ]"
                                >
                                    {{ exam.status }}
                                </button>
                                <span 
                                    v-else
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize border',
                                        exam.status === 'published' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border-amber-500/20'
                                    ]"
                                >
                                    {{ exam.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="authStore.hasPermission('exam.edit') && isExamCurrentYear(exam)"
                                        @click="openModal(exam)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Exam"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('exam.delete') && isExamCurrentYear(exam)"
                                        @click="handleDelete(exam)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Exam"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="border-t border-slate-200 dark:border-slate-800 p-4 flex items-center justify-between">
                <button 
                    :disabled="currentPage === 1"
                    @click="changePage(currentPage - 1)"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-slate-700 dark:text-slate-300"
                >
                    Previous
                </button>
                <span class="text-xs text-slate-500">Page {{ currentPage }} of {{ totalPages }}</span>
                <button 
                    :disabled="currentPage === totalPages"
                    @click="changePage(currentPage + 1)"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-slate-700 dark:text-slate-300"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl animate-fade-in">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ editingId ? 'Edit Exam Details' : 'Create New Exam' }}</h3>
                    <button @click="closeModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveExam" class="p-6 space-y-4">
                    <!-- <div class="grid grid-cols-2 gap-4"> -->
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.academic_year_id"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Exam Type <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.exam_type_id"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="">Select Exam Type</option>
                                <option v-for="type in examTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Exam Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="e.g. Mid-Term Theory Exams"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Start Date <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.start_date"
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">End Date <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.end_date"
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Description</label>
                            <textarea 
                                v-model="form.description"
                                rows="2"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="Details or instructions for the exam..."
                            ></textarea>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Publish Status</label>
                            <select 
                                v-model="form.status"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="draft">Draft (Hidden from Report Cards)</option>
                                <option value="published">Published (Visible on Report Cards)</option>
                            </select>
                        </div>
                        <div v-if="!isFormCurrentYear" class="col-span-2 bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-3 rounded-xl text-xs flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Historical Session: Selecting a locked academic session disables modifications.</span>
                        </div>

                        <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300 text-sm font-semibold transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving || !isFormCurrentYear"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save Exam' }}
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
