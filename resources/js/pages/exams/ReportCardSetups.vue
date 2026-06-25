<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Report Card Setup Publications</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Define custom publication names and link them to classes, sections, and exams.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-805 rounded-2xl p-6 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Form Area (New/Edit Setup) -->
            <div class="md:col-span-1 border-r border-slate-200 dark:border-slate-800 pr-0 md:pr-6 space-y-4">
                <h3 class="font-bold text-sm text-slate-850 dark:text-white uppercase tracking-wider">
                    {{ setupForm.id ? 'Edit Setup Configuration' : 'New Setup Configuration' }}
                </h3>
                
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Custom Name *</label>
                    <input 
                        v-model="setupForm.name" 
                        type="text" 
                        placeholder="e.g. Annual Report Card 2026" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200" 
                    />
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Class *</label>
                    <select 
                        v-model="setupForm.class_id" 
                        @change="handleSetupClassChange" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">Select Class</option>
                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Section (Optional)</label>
                    <select 
                        v-model="setupForm.section_id" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">All Sections</option>
                        <option v-for="sec in setupSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Exam 1 *</label>
                    <select 
                        v-model="setupForm.exam_id_1" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">Select Exam</option>
                        <option v-for="ex in exams" :key="ex.id" :value="ex.id">{{ ex.name }}</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Exam 2 (Optional)</label>
                    <select 
                        v-model="setupForm.exam_id_2" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">None (Single Exam)</option>
                        <option v-for="ex in exams.filter(e => e.id !== setupForm.exam_id_1)" :key="ex.id" :value="ex.id">{{ ex.name }}</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Template Layout *</label>
                    <select 
                        v-model="setupForm.template" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="basic">Standard Layout</option>
                        <option value="detailed">Detailed Layout</option>
                        <option value="cbse">CBSE Format</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Include Graded Subjects</label>
                    <select 
                        v-model="setupForm.include_graded" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <div class="flex gap-2 pt-2">
                    <button 
                        @click="saveSetup" 
                        :disabled="!setupForm.name || !setupForm.class_id || !setupForm.exam_id_1" 
                        class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm rounded-xl shadow-lg disabled:opacity-50 transition-all"
                    >
                        {{ setupForm.id ? 'Update Setup' : 'Create Setup' }}
                    </button>
                    <button 
                        @click="closeSetupForm" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-sm rounded-xl transition-all"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- List Area -->
            <div class="md:col-span-2 space-y-4">
                <h3 class="font-bold text-sm text-slate-850 dark:text-white uppercase tracking-wider">Existing Setups</h3>
                <div class="border border-slate-205 dark:border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-bold text-xs uppercase select-none">
                                <th class="p-3">Name</th>
                                <th class="p-3">Target</th>
                                <th class="p-3">Exams Linked</th>
                                <th class="p-3">Layout</th>
                                <th class="p-3 text-center">Graded</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-slate-750 dark:text-slate-300">
                            <tr v-for="s in setups" :key="s.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-3 font-semibold text-slate-850 dark:text-white">{{ s.name }}</td>
                                <td class="p-3 text-xs">{{ s.class?.name }} <span v-if="s.section">- {{ s.section?.name }}</span><span v-else> (All)</span></td>
                                <td class="p-3 text-xs font-medium">
                                    {{ s.exam1?.name }}
                                    <div v-if="s.exam2" class="text-indigo-500 dark:text-indigo-400 font-bold">+ {{ s.exam2?.name }}</div>
                                </td>
                                <td class="p-3 text-xs capitalize font-medium text-indigo-600 dark:text-indigo-400">
                                    {{ s.template === 'basic' ? 'Standard' : s.template === 'detailed' ? 'Detailed' : 'CBSE' }}
                                </td>
                                <td class="p-3 text-center text-xs font-medium">
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-bold border', s.include_graded === 'yes' ? 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20']">
                                        {{ s.include_graded === 'yes' ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <button 
                                        @click="toggleSetupPublish(s)" 
                                        :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border transition-colors', s.status === 'published' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20 hover:bg-slate-500/20']"
                                    >
                                        {{ s.status === 'published' ? 'Published' : 'Draft' }}
                                    </button>
                                </td>
                                <td class="p-3 text-right space-x-1.5">
                                    <button 
                                        @click="editSetup(s)" 
                                        class="p-1 bg-slate-100 hover:bg-indigo-500 hover:text-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded transition-colors" 
                                        title="Edit"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button 
                                        @click="deleteSetup(s.id)" 
                                        class="p-1 bg-slate-100 hover:bg-rose-500 hover:text-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded transition-colors" 
                                        title="Delete"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="setups.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-450 dark:text-slate-600">No report card setup configurations created yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();

const exams = ref([]);
const classes = ref([]);
const setups = ref([]);
const setupSections = ref([]);

const setupForm = reactive({
    id: null,
    name: '',
    class_id: '',
    section_id: '',
    exam_id_1: '',
    exam_id_2: '',
    status: 'draft',
    template: 'basic',
    include_graded: 'no'
});

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
        const response = await window.axios.get('/api/classes', { params: { all: true } });
        classes.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const fetchSetups = async () => {
    try {
        const response = await window.axios.get('/api/report-card-setups');
        setups.value = response.data.report_card_setups || [];
    } catch (e) {
        console.error('Failed to load setups.');
    }
};

const closeSetupForm = () => {
    setupForm.id = null;
    setupForm.name = '';
    setupForm.class_id = '';
    setupForm.section_id = '';
    setupForm.exam_id_1 = '';
    setupForm.exam_id_2 = '';
    setupForm.status = 'draft';
    setupForm.template = 'basic';
    setupForm.include_graded = 'no';
    setupSections.value = [];
};

const handleSetupClassChange = async () => {
    if (!setupForm.class_id) {
        setupSections.value = [];
        setupForm.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: setupForm.class_id, all: true } });
        setupSections.value = response.data.sections || response.data;
        setupForm.section_id = '';
    } catch (e) {
        console.error(e);
    }
};

const saveSetup = async () => {
    try {
        const activeYearId = authStore.user?.school?.active_academic_year_id || 1;
        const payload = {
            ...setupForm,
            academic_year_id: activeYearId
        };

        if (setupForm.id) {
            await window.axios.put(`/api/report-card-setups/${setupForm.id}`, payload);
            window.toastr?.success('Report card setup configuration updated successfully.');
        } else {
            await window.axios.post('/api/report-card-setups', payload);
            window.toastr?.success('Report card setup configuration created successfully.');
        }
        closeSetupForm();
        await fetchSetups();
    } catch (e) {
        window.toastr?.error(e.response?.data?.message || 'Failed to save setup.');
    }
};

const editSetup = async (setup) => {
    setupForm.id = setup.id;
    setupForm.name = setup.name;
    setupForm.class_id = setup.class_id;
    setupForm.exam_id_1 = setup.exam_id_1;
    setupForm.exam_id_2 = setup.exam_id_2 || '';
    setupForm.status = setup.status;
    setupForm.template = setup.template || 'basic';
    setupForm.include_graded = setup.include_graded || 'no';
    
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: setup.class_id, all: true } });
        setupSections.value = response.data.sections || response.data;
        setupForm.section_id = setup.section_id || '';
    } catch (e) {
        console.error(e);
    }
};

const toggleSetupPublish = async (setup) => {
    try {
        const response = await window.axios.patch(`/api/report-card-setups/${setup.id}/toggle-publish`);
        window.toastr?.success(response.data.message);
        await fetchSetups();
    } catch (e) {
        window.toastr?.error('Failed to change publish status.');
    }
};

const deleteSetup = async (id) => {
    if (!confirm('Are you sure you want to delete this configuration?')) return;
    try {
        await window.axios.delete(`/api/report-card-setups/${id}`);
        window.toastr?.success('Report card setup configuration deleted.');
        await fetchSetups();
    } catch (e) {
        window.toastr?.error('Failed to delete configuration.');
    }
};

onMounted(() => {
    fetchExams();
    fetchClasses();
    fetchSetups();
});
</script>
