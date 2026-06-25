<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Notice Board</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Post announcements and updates for students, classes, or the entire school.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('notice.create')"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Post Notice
            </button>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Target Audience</label>
                <select 
                    v-model="filters.target_type" 
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Audiences</option>
                    <option value="Entire School">Entire School</option>
                    <option value="Class Wise">Class Wise</option>
                    <option value="Section Wise">Section Wise</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select 
                    v-model="filters.status" 
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button 
                    @click="fetchNotices"
                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 transition-all text-sm"
                >
                    Filter
                </button>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="notices.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                No notices posted yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Notice Date</th>
                            <th class="p-4">Title</th>
                            <th class="p-4">Audience</th>
                            <th class="p-4">Target Detail</th>
                            <th class="p-4">Attachment</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="notice in notices" :key="notice.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-medium text-slate-500">{{ notice.notice_date }}</td>
                            <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                <div>{{ notice.title }}</div>
                                <div class="text-xs text-slate-400 font-normal truncate max-w-xs">{{ notice.description }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/60">
                                    {{ notice.target_type }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span v-if="notice.target_type === 'Entire School'" class="text-xs text-slate-450 italic">All School</span>
                                <span v-else-if="notice.target_type === 'Class Wise'" class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80">
                                    Class: {{ notice.class?.name || 'N/A' }}
                                </span>
                                <span v-else-if="notice.target_type === 'Section Wise'" class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80">
                                    {{ notice.class?.name || 'N/A' }} - {{ notice.section?.name || 'N/A' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <a 
                                    v-if="notice.attachment" 
                                    :href="'/storage/' + notice.attachment" 
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
                                    notice.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ notice.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="authStore.hasPermission('notice.edit')"
                                        @click="openModal(notice)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('notice.delete')"
                                        @click="handleDelete(notice)"
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
            <div v-if="totalPages > 1" class="border-t border-slate-200 dark:border-slate-800 p-4 flex items-center justify-between">
                <button 
                    :disabled="currentPage === 1"
                    @click="changePage(currentPage - 1)"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 text-slate-700 dark:text-slate-300"
                >
                    Previous
                </button>
                <span class="text-xs text-slate-500">Page {{ currentPage }} of {{ totalPages }}</span>
                <button 
                    :disabled="currentPage === totalPages"
                    @click="changePage(currentPage + 1)"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 text-slate-700 dark:text-slate-300"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl animate-fade-in">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ editingId ? 'Edit Notice' : 'Post Notice' }}</h3>
                    <button @click="closeModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveNotice" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Title <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="e.g. Independence Day Holiday"
                            />
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Description <span class="text-rose-500">*</span></label>
                            <textarea 
                                v-model="form.description"
                                required
                                rows="3"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                placeholder="Details of the announcement..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Notice Date <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.notice_date"
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Target Audience <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.target_type"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="Entire School">Entire School</option>
                                <option value="Class Wise">Class Wise</option>
                                <option value="Section Wise">Section Wise</option>
                            </select>
                        </div>

                        <!-- Class Selector (Visible if class wise or section wise) -->
                        <div v-if="form.target_type === 'Class Wise' || form.target_type === 'Section Wise'" class="col-span-1">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.class_id"
                                required
                                @change="fetchModalSections"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>

                        <!-- Section Selector (Visible if section wise) -->
                        <div v-if="form.target_type === 'Section Wise'" class="col-span-1">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.section_id"
                                required
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="">Select Section</option>
                                <option v-for="sec in modalSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                            </select>
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

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-350 text-sm font-semibold transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Post Notice' }}
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

const notices = ref([]);
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);

const classes = ref([]);
const modalSections = ref([]);

const currentPage = ref(1);
const totalPages = ref(1);

const filters = reactive({
    target_type: '',
    status: ''
});

const form = ref({
    title: '',
    description: '',
    notice_date: '',
    target_type: 'Entire School',
    class_id: '',
    section_id: '',
    attachment: null,
    status: 'active'
});

const fetchClasses = async () => {
    try {
        const response = await window.axios.get('/api/classes', { params: { all: true } });
        classes.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const fetchModalSections = async () => {
    if (!form.value.class_id) {
        modalSections.value = [];
        form.value.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: form.value.class_id, all: true } });
        modalSections.value = response.data.sections || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const fetchNotices = async () => {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            target_type: filters.target_type,
            status: filters.status
        };
        const response = await window.axios.get('/api/notices', { params });
        notices.value = response.data.data;
        totalPages.value = response.data.last_page;
    } catch (e) {
        window.toastr?.error('Failed to load notices.');
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

const openModal = async (notice = null) => {
    if (notice) {
        editingId.value = notice.id;
        form.value = {
            title: notice.title,
            description: notice.description,
            notice_date: notice.notice_date,
            target_type: notice.target_type,
            class_id: notice.class_id || '',
            section_id: notice.section_id || '',
            attachment: notice.attachment || null,
            status: notice.status
        };
        if (notice.class_id) {
            await fetchModalSections();
        }
    } else {
        editingId.value = null;
        form.value = {
            title: '',
            description: '',
            notice_date: new Date().toISOString().split('T')[0],
            target_type: 'Entire School',
            class_id: '',
            section_id: '',
            attachment: null,
            status: 'active'
        };
        modalSections.value = [];
    }
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editingId.value = null;
};

const saveNotice = async () => {
    saving.value = true;
    try {
        const payload = new FormData();
        payload.append('title', form.value.title);
        payload.append('description', form.value.description);
        payload.append('notice_date', form.value.notice_date);
        payload.append('target_type', form.value.target_type);
        payload.append('status', form.value.status);

        if (form.value.target_type === 'Class Wise' || form.value.target_type === 'Section Wise') {
            payload.append('class_id', form.value.class_id);
        }
        if (form.value.target_type === 'Section Wise') {
            payload.append('section_id', form.value.section_id);
        }

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
            await window.axios.post(`/api/notices/${editingId.value}`, payload, config);
            window.toastr?.success('Notice updated successfully.');
        } else {
            await window.axios.post('/api/notices', payload, config);
            window.toastr?.success('Notice posted successfully.');
        }
        closeModal();
        fetchNotices();
    } catch (e) {
        if (e.response?.status === 422) {
            const errors = Object.values(e.response.data.errors).flat().join('\n');
            window.toastr?.error(errors);
        } else {
            window.toastr?.error('Failed to save notice.');
        }
    } finally {
        saving.value = false;
    }
};

const handleDelete = async (notice) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Notice',
        message: `Are you sure you want to delete notice "${notice.title}"?`,
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/notices/${notice.id}`);
            window.toastr?.success('Notice deleted successfully.');
            fetchNotices();
        } catch (e) {
            window.toastr?.error('Failed to delete notice.');
        }
    }
};

const changePage = (page) => {
    currentPage.value = page;
    fetchNotices();
};

onMounted(() => {
    fetchClasses();
    fetchNotices();
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
