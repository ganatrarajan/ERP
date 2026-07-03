<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Leave Approvals</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Review, approve, or reject leave requests submitted by teachers and staff.</p>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select 
                    v-model="filters.status" 
                    @change="fetchLeaves"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                >
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Search Staff</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="debounceSearch"
                        type="text" 
                        placeholder="Search name or email..." 
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

            <div v-else-if="leaves.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                No leave requests found.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Staff Member</th>
                            <th class="p-4">Leave Type</th>
                            <th class="p-4">Duration</th>
                            <th class="p-4">Reason</th>
                            <th class="p-4">Requested Date</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6">
                                <div class="font-bold text-slate-800 dark:text-white">{{ leave.user?.name || 'N/A' }}</div>
                                <div class="text-xs text-slate-450">{{ leave.user?.email || 'N/A' }}</div>
                            </td>
                            <td class="p-4 font-semibold">
                                {{ leave.leave_type }}
                            </td>
                            <td class="p-4">
                                <div class="font-semibold text-slate-800 dark:text-white">{{ leave.start_date }}</div>
                                <div class="text-xs text-slate-450">to {{ leave.end_date }}</div>
                            </td>
                            <td class="p-4 max-w-xs truncate" :title="leave.reason">
                                {{ leave.reason }}
                            </td>
                            <td class="p-4 text-slate-500">
                                {{ leave.requested_date }}
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border',
                                    leave.status === 'Approved' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25' :
                                    leave.status === 'Rejected' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25' :
                                    'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/25'
                                ]">
                                    {{ leave.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="leave.status === 'Pending' && authStore.hasPermission('attendance.edit')"
                                        @click="handleStatus(leave.id, 'approve')"
                                        title="Approve Leave"
                                        class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-lg transition-colors cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </button>

                                    <button 
                                        v-if="leave.status === 'Pending' && authStore.hasPermission('attendance.edit')"
                                        @click="handleStatus(leave.id, 'reject')"
                                        title="Reject Leave"
                                        class="p-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-lg transition-colors cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('attendance.delete')"
                                        @click="handleDelete(leave.id)"
                                        title="Delete Request"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors cursor-pointer"
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
            <div v-if="pagination.total > pagination.per_page" class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/60">
                <span class="text-xs text-slate-500">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} requests
                </span>
                <div class="flex gap-2">
                    <button 
                        :disabled="pagination.current_page === 1"
                        @click="changePage(pagination.current_page - 1)"
                        class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-350 disabled:opacity-50 transition-all cursor-pointer"
                    >
                        Prev
                    </button>
                    <button 
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="changePage(pagination.current_page + 1)"
                        class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-350 disabled:opacity-50 transition-all cursor-pointer"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { useConfirmStore } from '../../stores/confirm';

const authStore = useAuthStore();
const toastStore = useToastStore();
const confirmStore = useConfirmStore();

const loading = ref(false);
const leaves = ref([]);

const filters = reactive({
    status: '',
    search: ''
});

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
});

let searchTimeout = null;

const fetchLeaves = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/staff-leaves', {
            params: {
                page,
                status: filters.status,
                search: filters.search,
                per_page: pagination.per_page
            }
        });
        
        leaves.value = response.data.data;
        pagination.current_page = response.data.current_page;
        pagination.last_page = response.data.last_page;
        pagination.total = response.data.total;
        pagination.from = response.data.from;
        pagination.to = response.data.to;
    } catch (error) {
        console.error('Error fetching leaves:', error);
        toastStore.error(error.response?.data?.message || 'Failed to fetch leave requests.');
    } finally {
        loading.value = false;
    }
};

const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchLeaves(1);
    }, 500);
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
        fetchLeaves(page);
    }
};

const handleStatus = async (id, action) => {
    const actionText = action === 'approve' ? 'approve' : 'reject';
    const titleText = action === 'approve' ? 'Approve Leave' : 'Reject Leave';
    const typeVal = action === 'approve' ? 'info' : 'warning';
    const confirmButtonText = action === 'approve' ? 'Approve' : 'Reject';

    const confirmed = await confirmStore.show({
        title: titleText,
        message: `Are you sure you want to ${actionText} this leave request?`,
        type: typeVal,
        confirmText: confirmButtonText,
        cancelText: 'Cancel'
    });

    if (confirmed) {
        loading.value = true;
        try {
            const response = await axios.post(`/api/staff-leaves/${id}/${action}`);
            toastStore.success(response.data.message || `Leave request ${actionText}d successfully.`);
            fetchLeaves(pagination.current_page);
        } catch (error) {
            console.error(`Error performing ${action}:`, error);
            toastStore.error(error.response?.data?.message || `Failed to ${action} leave request.`);
        } finally {
            loading.value = false;
        }
    }
};

const handleDelete = async (id) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Leave Request',
        message: 'Are you sure you want to delete this request? This action is permanent and will clear any mapped leave attendance.',
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        loading.value = true;
        try {
            await axios.delete(`/api/staff-leaves/${id}`);
            toastStore.success('Leave request has been deleted.');
            fetchLeaves(pagination.current_page);
        } catch (error) {
            console.error('Error deleting leave:', error);
            toastStore.error(error.response?.data?.message || 'Failed to delete leave request.');
        } finally {
            loading.value = false;
        }
    }
};

onMounted(() => {
    fetchLeaves();
});
</script>
