<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Password Reset Requests</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Manage and approve pending user password reset requests. Approved requests will reset passwords to <span class="font-semibold text-slate-750 dark:text-slate-200">12345678</span>.
                </p>
            </div>
        </div>

        <!-- Notification Banner -->
        <div v-if="successMsg" class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex items-center justify-between text-emerald-700 dark:text-emerald-350 text-sm">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="hover:text-emerald-900 dark:hover:text-emerald-250 font-bold">&times;</button>
        </div>

        <div v-if="errorMsg" class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800/60 rounded-xl flex items-center justify-between text-rose-700 dark:text-rose-350 text-sm">
            <span>{{ errorMsg }}</span>
            <button @click="errorMsg = ''" class="hover:text-rose-900 dark:hover:text-rose-200 font-bold">&times;</button>
        </div>

        <!-- Requests List -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div v-if="loading" class="p-12 flex justify-center items-center">
                <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <div v-else-if="requests.length === 0" class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m0 0v3m0-3h3m-3 0H9m12-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No requests found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">There are no password reset requests at the moment.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email Address</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Message / Details</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Requested At</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr v-for="req in requests" :key="req.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white">
                                {{ req.email }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                {{ req.message || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-550 dark:text-slate-400">
                                {{ formatDate(req.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                                    req.status === 'pending' ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-700 dark:text-amber-400' : '',
                                    req.status === 'approved' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400' : '',
                                    req.status === 'rejected' ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400' : '',
                                ]">
                                    {{ req.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right space-x-2">
                                <template v-if="req.status === 'pending'">
                                    <button 
                                        @click="handleAction(req.id, 'approve')" 
                                        :disabled="processingId === req.id"
                                        class="inline-flex items-center px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow active:scale-95 disabled:opacity-50"
                                    >
                                        Approve
                                    </button>
                                    <button 
                                        @click="handleAction(req.id, 'reject')" 
                                        :disabled="processingId === req.id"
                                        class="inline-flex items-center px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow active:scale-95 disabled:opacity-50"
                                    >
                                        Reject
                                    </button>
                                </template>
                                <span v-else class="text-slate-400 dark:text-slate-600 text-xs">No actions</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
    name: 'PasswordResetIndex',
    setup() {
        const requests = ref([]);
        const loading = ref(true);
        const processingId = ref(null);
        const successMsg = ref('');
        const errorMsg = ref('');

        const fetchRequests = async () => {
            loading.value = true;
            errorMsg.value = '';
            try {
                const response = await window.axios.get('/api/password-resets');
                requests.value = response.data.requests;
            } catch (error) {
                console.error(error);
                errorMsg.value = 'Failed to load password reset requests.';
            } finally {
                loading.value = false;
            }
        };

        const handleAction = async (id, action) => {
            processingId.value = id;
            successMsg.value = '';
            errorMsg.value = '';
            try {
                const response = await window.axios.post(`/api/password-resets/${id}/action`, { action });
                successMsg.value = response.data.message;
                // Update list locally
                const index = requests.value.findIndex(r => r.id === id);
                if (index !== -1) {
                    requests.value[index] = response.data.request;
                }
            } catch (error) {
                console.error(error);
                errorMsg.value = error.response?.data?.message || 'Failed to process request.';
            } finally {
                processingId.value = null;
            }
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleString();
        };

        onMounted(() => {
            fetchRequests();
        });

        return {
            requests,
            loading,
            processingId,
            successMsg,
            errorMsg,
            formatDate,
            handleAction
        };
    }
};
</script>
