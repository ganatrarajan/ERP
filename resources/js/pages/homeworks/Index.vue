<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Homework</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Publish and manage homework assignments for classes.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('homework.create')"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Publish Homework
            </button>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class</label>
                <select 
                    v-model="filters.class_id" 
                    @change="handleFilterClassChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                >
                    <option value="">All Classes</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section</label>
                <select 
                    v-model="filters.section_id" 
                    @change="handleFilterSectionChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                >
                    <option value="">All Sections</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Subject</label>
                <select 
                    v-model="filters.subject_id" 
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                >
                    <option value="">All Subjects</option>
                    <option v-for="subj in filterSubjects" :key="subj.id" :value="subj.id">{{ subj.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Homework</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        type="text" 
                        placeholder="Search title, desc..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                    />
                </div>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="homeworks.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                No homework assigned yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Title</th>
                            <th class="p-4">Class/Section</th>
                            <th class="p-4">Subject</th>
                            <th class="p-4">Submission Date</th>
                            <th class="p-4">Attachment</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="hw in homeworks" :key="hw.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                <div>{{ hw.title }}</div>
                                <div class="text-xs text-slate-400 font-normal">By {{ hw.creator?.name || 'N/A' }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-655 dark:text-slate-300">
                                    {{ hw.class?.name }} - {{ hw.section?.name }}
                                </span>
                            </td>
                            <td class="p-4 font-semibold">{{ hw.subject?.name }}</td>
                            <td class="p-4 font-medium text-slate-500">{{ hw.submission_date }}</td>
                            <td class="p-4">
                                <a 
                                    v-if="hw.attachment" 
                                    :href="'/storage/' + hw.attachment" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                                <span v-else class="text-slate-400 text-xs">No file</span>
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                    hw.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ hw.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="authStore.hasPermission('homework.edit')"
                                        @click="openModal(hw)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('homework.delete')"
                                        @click="handleDelete(hw)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
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
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden bg-white dark:bg-slate-900 rounded-b-2xl">
                <div class="flex items-center gap-4">
                    <!-- Page Size Selector -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-semibold">
                        <span>Show</span>
                        <select 
                            v-model="perPage" 
                            class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold focus:outline-none text-[11px]"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span>entries</span>
                    </div>

                    <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                        Showing {{ from }} to {{ to }} of {{ totalEntries }} entries
                    </span>
                </div>

                <div v-if="totalPages > 1" class="flex items-center gap-1">
                    <!-- Previous -->
                    <button 
                        :disabled="currentPage === 1" 
                        @click="currentPage--"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 transition-all bg-slate-50 dark:bg-slate-800 text-slate-707 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 cursor-pointer"
                    >
                        Previous
                    </button>

                    <!-- Numeric Pages Loop -->
                    <button 
                        v-for="page in pageNumbers" 
                        :key="page"
                        @click="currentPage = page"
                        :class="[
                            'px-2.5 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer',
                            currentPage === page 
                                ? 'bg-indigo-600 border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500 text-white shadow-sm shadow-indigo-600/20' 
                                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-707 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/80'
                        ]"
                    >
                        {{ page }}
                    </button>

                    <!-- Next -->
                    <button 
                        :disabled="currentPage === totalPages" 
                        @click="currentPage++"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 transition-all bg-slate-50 dark:bg-slate-800 text-slate-707 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 cursor-pointer"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl animate-fade-in">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ editingId ? 'Edit Homework' : 'Publish Homework' }}</h3>
                    <button @click="closeModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 hover:text-slate-655">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveHomework" class="flex flex-col max-h-[85vh] md:max-h-[80vh]">
                    <!-- Scrollable Content Wrapper -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.academic_year_id"
                                    required
                                    @change="handleModalAcademicYearChange"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.class_id"
                                    required
                                    @change="handleModalClassChange"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="">Select Class</option>
                                    <option v-for="cls in modalClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.section_id"
                                    required
                                    @change="handleModalSectionChange"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="">Select Section</option>
                                    <option v-for="sec in modalSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Subject <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.subject_id"
                                    required
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="">Select Subject</option>
                                    <option v-for="subj in modalSubjects" :key="subj.id" :value="subj.id">{{ subj.name }}</option>
                                </select>
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Homework Title <span class="text-rose-500">*</span></label>
                                <input 
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    placeholder="e.g. Solve Chapter 3 Exercises"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Instructions / Description <span class="text-rose-500">*</span></label>
                                <textarea 
                                    v-model="form.description"
                                    required
                                    rows="3"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    placeholder="Write detailed instructions for the homework..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Submission Date <span class="text-rose-500">*</span></label>
                                <input 
                                    v-model="form.submission_date"
                                    v-datepicker
                                    type="text"
                                    placeholder="YYYY-MM-DD"
                                    required
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Attachment</label>
                                <input 
                                    type="file"
                                    @change="handleFileUpload"
                                    class="w-full text-sm text-slate-550 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                <div v-if="editingId && form.attachment && typeof form.attachment === 'string'" class="text-xs text-slate-450 mt-1 truncate">
                                    Current file: {{ form.attachment.split('/').pop() }}
                                </div>
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                                <select 
                                    v-model="form.status"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 flex-shrink-0 bg-slate-50 dark:bg-slate-900/50">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-slate-655 dark:text-slate-350 text-sm font-semibold transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ saving ? 'Publishing...' : 'Publish' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';

const authStore = useAuthStore();
const confirmStore = useConfirmStore();

const rawHomeworks = ref([]);
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);

const academicYears = ref([]);
const classes = ref([]);
const modalClasses = ref([]);
const sections = ref([]);
const modalSections = ref([]);
const filterSubjects = ref([]);
const modalSubjects = ref([]);
let isModalLoading = false;

const currentPage = ref(1);
const perPage = ref(10);

const filters = reactive({
    class_id: '',
    section_id: '',
    subject_id: '',
    search: ''
});

const processedHomeworks = computed(() => {
    let list = [...rawHomeworks.value];

    // Filter by class_id
    if (filters.class_id) {
        list = list.filter(h => h.class_id === parseInt(filters.class_id));
    }
    // Filter by section_id
    if (filters.section_id) {
        list = list.filter(h => h.section_id === parseInt(filters.section_id));
    }
    // Filter by subject_id
    if (filters.subject_id) {
        list = list.filter(h => h.subject_id === parseInt(filters.subject_id));
    }
    // Filter by search query (instant client-side!)
    if (filters.search) {
        const query = filters.search.toLowerCase();
        list = list.filter(h => 
            h.title?.toLowerCase().includes(query) || 
            h.description?.toLowerCase().includes(query) ||
            h.subject?.name?.toLowerCase().includes(query) ||
            h.class?.name?.toLowerCase().includes(query)
        );
    }

    // Default sorting: descending by id
    list.sort((a, b) => b.id - a.id);

    return list;
});

const totalEntries = computed(() => processedHomeworks.value.length);

const totalPages = computed(() => {
    return Math.ceil(totalEntries.value / perPage.value) || 1;
});

const from = computed(() => {
    if (totalEntries.value === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const to = computed(() => {
    const toVal = currentPage.value * perPage.value;
    return toVal > totalEntries.value ? totalEntries.value : toVal;
});

const pageNumbers = computed(() => {
    const pages = [];
    for (let i = 1; i <= totalPages.value; i++) {
        pages.push(i);
    }
    return pages;
});

// The list of paginated homeworks displayed in the table!
const homeworks = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return processedHomeworks.value.slice(start, end);
});

// Watch parameters to reset page
watch([() => filters.class_id, () => filters.section_id, () => filters.subject_id, () => filters.search, perPage], () => {
    currentPage.value = 1;
});

const form = ref({
    academic_year_id: '',
    class_id: '',
    section_id: '',
    subject_id: '',
    title: '',
    description: '',
    submission_date: '',
    attachment: null,
    status: 'active'
});

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

const fetchClasses = async () => {
    try {
        const response = await window.axios.get('/api/classes', { params: { all: true } });
        classes.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const handleFilterClassChange = async () => {
    if (!filters.class_id) {
        sections.value = [];
        filters.section_id = '';
        filterSubjects.value = [];
        filters.subject_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: filters.class_id, all: true } });
        sections.value = response.data.sections || response.data;
        filters.section_id = '';
        filterSubjects.value = [];
        filters.subject_id = '';
        fetchFilterSubjects();
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const handleFilterSectionChange = () => {
    fetchFilterSubjects();
};

const fetchFilterSubjects = async () => {
    try {
        const params = { all: true };
        if (filters.class_id) params.class_id = filters.class_id;
        if (filters.section_id) params.section_id = filters.section_id;
        const response = await window.axios.get('/api/subjects', { params });
        filterSubjects.value = response.data.subjects || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load subjects.');
    }
};

const fetchModalClasses = async () => {
    if (!form.value.academic_year_id) {
        modalClasses.value = [];
        return;
    }
    try {
        const response = await window.axios.get('/api/classes', {
            params: {
                all: true,
                academic_year_id: form.value.academic_year_id
            }
        });
        modalClasses.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const handleModalAcademicYearChange = async () => {
    if (isModalLoading) return;
    await fetchModalClasses();
    form.value.class_id = '';
    form.value.section_id = '';
    modalSections.value = [];
    form.value.subject_id = '';
    modalSubjects.value = [];
};

const fetchModalSections = async () => {
    if (!form.value.class_id) {
        modalSections.value = [];
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: form.value.class_id, all: true } });
        modalSections.value = response.data.sections || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const handleModalClassChange = async () => {
    if (!form.value.class_id) {
        modalSections.value = [];
        form.value.section_id = '';
        modalSubjects.value = [];
        form.value.subject_id = '';
        return;
    }
    try {
        await fetchModalSections();
        form.value.section_id = '';
        modalSubjects.value = [];
        form.value.subject_id = '';
        fetchModalSubjects();
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const handleModalSectionChange = () => {
    fetchModalSubjects();
};

const fetchModalSubjects = async () => {
    try {
        const params = { all: true };
        if (form.value.academic_year_id) params.academic_year_id = form.value.academic_year_id;
        if (form.value.class_id) params.class_id = form.value.class_id;
        if (form.value.section_id) params.section_id = form.value.section_id;
        const response = await window.axios.get('/api/subjects', { params });
        modalSubjects.value = response.data.subjects || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load subjects.');
    }
};

const fetchHomeworks = async () => {
    loading.value = true;
    try {
        const params = {
            per_page: 10000
        };
        const response = await window.axios.get('/api/homeworks', { params });
        rawHomeworks.value = response.data.data || response.data || [];
    } catch (e) {
        window.toastr?.error('Failed to load homework.');
    } finally {
        loading.value = false;
    }
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.value.attachment = file;
    }
};

const openModal = async (hw = null) => {
    isModalLoading = true;
    if (hw) {
        editingId.value = hw.id;
        form.value = {
            academic_year_id: hw.academic_year_id,
            class_id: hw.class_id,
            section_id: hw.section_id,
            subject_id: hw.subject_id,
            title: hw.title,
            description: hw.description,
            submission_date: hw.submission_date,
            attachment: hw.attachment || null,
            status: hw.status
        };
        await fetchModalClasses();
        await fetchModalSections();
        await fetchModalSubjects();
        form.value.class_id = hw.class_id;
        form.value.section_id = hw.section_id;
        form.value.subject_id = hw.subject_id;
    } else {
        editingId.value = null;
        const currentYear = academicYears.value.find(y => y.is_current);
        form.value = {
            academic_year_id: currentYear ? currentYear.id : '',
            class_id: '',
            section_id: '',
            subject_id: '',
            title: '',
            description: '',
            submission_date: new Date(Date.now() + 86400000).toISOString().split('T')[0], // tomorrow
            attachment: null,
            status: 'active'
        };
        await fetchModalClasses();
        modalSections.value = [];
        modalSubjects.value = [];
    }
    isModalLoading = false;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editingId.value = null;
};

const saveHomework = async () => {
    saving.value = true;
    try {
        const payload = new FormData();
        payload.append('academic_year_id', form.value.academic_year_id);
        payload.append('class_id', form.value.class_id);
        payload.append('section_id', form.value.section_id);
        payload.append('subject_id', form.value.subject_id);
        payload.append('title', form.value.title);
        payload.append('description', form.value.description);
        payload.append('submission_date', form.value.submission_date);
        payload.append('status', form.value.status);

        if (form.value.attachment instanceof File) {
            payload.append('attachment', form.value.attachment);
        } else if (form.value.attachment) {
            payload.append('attachment', form.value.attachment);
        }

        const config = {
            headers: { 'Content-Type': 'multipart/form-data' }
        };

        if (editingId.value) {
            payload.append('_method', 'PUT');
            await window.axios.post(`/api/homeworks/${editingId.value}`, payload, config);
            window.toastr?.success('Homework updated successfully.');
        } else {
            await window.axios.post('/api/homeworks', payload, config);
            window.toastr?.success('Homework published successfully.');
        }
        closeModal();
        fetchHomeworks();
    } catch (e) {
        if (e.response?.status === 422) {
            const errors = Object.values(e.response.data.errors).flat().join('\n');
            window.toastr?.error(errors);
        } else {
            window.toastr?.error('Failed to save homework.');
        }
    } finally {
        saving.value = false;
    }
};

const handleDelete = async (hw) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Homework',
        message: `Are you sure you want to delete homework "${hw.title}"?`,
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/homeworks/${hw.id}`);
            window.toastr?.success('Homework deleted successfully.');
            fetchHomeworks();
        } catch (e) {
            window.toastr?.error('Failed to delete homework.');
        }
    }
};

const changePage = (page) => {
    currentPage.value = page;
};

onMounted(() => {
    fetchAcademicYears();
    fetchClasses();
    fetchFilterSubjects();
    fetchHomeworks();
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
