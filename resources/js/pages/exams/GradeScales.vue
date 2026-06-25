<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Grade Scales</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Configure grading ranges, percentages, and grade points for subjects and report cards.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('exam.create')"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Grade
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
                        placeholder="Search grades (e.g. A1, Outstanding)..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select 
                    v-model="filters.status" 
                    @change="fetchGradeScales"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="gradeScales.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                No grades defined yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6" style="width: 30%;">Grade Symbol</th>
                            <th class="p-4 text-center" style="width: 25%;">Min Percentage (%)</th>
                            <th class="p-4" style="width: 30%;">Description</th>
                            <th class="p-4" style="width: 10%;">Status</th>
                            <th class="p-4 pr-6 text-right" style="width: 5%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="scale in gradeScales" :key="scale.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-bold text-indigo-600 dark:text-indigo-400">
                                <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/40 rounded-lg border border-indigo-100 dark:border-indigo-900/40">
                                    {{ scale.grade }}
                                </span>
                            </td>
                            <td class="p-4 text-center font-medium font-mono text-slate-800 dark:text-white">
                                &ge; {{ scale.min_percentage }}%
                            </td>
                            <td class="p-4 text-slate-500 dark:text-slate-400">
                                {{ scale.description || '-' }}
                            </td>
                            <td class="p-4">
                                <button 
                                    v-if="authStore.hasPermission('exam.edit')"
                                    @click="toggleStatus(scale)"
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize border transition-all active:scale-95',
                                        scale.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25 hover:bg-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25 hover:bg-rose-500/25'
                                    ]"
                                >
                                    {{ scale.status }}
                                </button>
                                <span 
                                    v-else
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize border',
                                        scale.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25'
                                    ]"
                                >
                                    {{ scale.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="authStore.hasPermission('exam.edit')"
                                        @click="openModal(scale)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Grade"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('exam.delete')"
                                        @click="handleDelete(scale)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Grade"
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
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-2xl overflow-hidden shadow-2xl animate-fade-in">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ editingId ? 'Edit Grade Range' : 'Add Grade Range' }}</h3>
                    <button @click="closeModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveGradeScale" class="p-6 space-y-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Grade Symbol / Letter <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.grade"
                                type="text"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="e.g. A1, B+, Pass"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Min Percentage (%) <span class="text-rose-500">*</span></label>
                            <input 
                                v-model.number="form.min_percentage"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="e.g. 91.00"
                            />
                        </div>



                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Description / Remarks</label>
                            <input 
                                v-model="form.description"
                                type="text"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="e.g. Outstanding, Excellent"
                            />
                        </div>

                        <div>
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
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save Grade' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';

const authStore = useAuthStore();
const confirmStore = useConfirmStore();

const gradeScales = ref([]);
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);

const currentPage = ref(1);
const totalPages = ref(1);

const filters = reactive({
    search: '',
    status: ''
});

const form = ref({
    grade: '',
    min_percentage: 0,
    grade_point: 0,
    description: '',
    status: 'active'
});

let searchTimeout = null;

const fetchGradeScales = async () => {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            search: filters.search,
            status: filters.status
        };
        const response = await window.axios.get('/api/grade-scales', { params });
        gradeScales.value = response.data.data;
        totalPages.value = response.data.last_page;
    } catch (e) {
        window.toastr?.error('Failed to load grade scales.');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchGradeScales();
    }, 300);
};

const changePage = (page) => {
    currentPage.value = page;
    fetchGradeScales();
};

const toggleStatus = async (scale) => {
    try {
        const response = await window.axios.patch(`/api/grade-scales/${scale.id}/toggle-status`);
        scale.status = response.data.grade_scale.status;
        window.toastr?.success(response.data.message || 'Status updated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to update status.');
    }
};

const openModal = (scale = null) => {
    if (scale) {
        editingId.value = scale.id;
        form.value = {
            grade: scale.grade,
            min_percentage: scale.min_percentage,
            grade_point: scale.grade_point,
            description: scale.description || '',
            status: scale.status
        };
    } else {
        editingId.value = null;
        form.value = {
            grade: '',
            min_percentage: 0,
            grade_point: 0,
            description: '',
            status: 'active'
        };
    }
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editingId.value = null;
};

const saveGradeScale = async () => {
    saving.value = true;
    try {
        if (editingId.value) {
            await window.axios.put(`/api/grade-scales/${editingId.value}`, form.value);
            window.toastr?.success('Grade scale updated successfully.');
        } else {
            await window.axios.post('/api/grade-scales', form.value);
            window.toastr?.success('Grade scale created successfully.');
        }
        closeModal();
        fetchGradeScales();
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

const handleDelete = async (scale) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Grade',
        message: `Are you sure you want to delete grade "${scale.grade}" (>= ${scale.min_percentage}%)?`,
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/grade-scales/${scale.id}`);
            window.toastr?.success('Grade deleted successfully.');
            fetchGradeScales();
        } catch (e) {
            window.toastr?.error('Failed to delete grade.');
        }
    }
};

onMounted(() => {
    fetchGradeScales();
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
