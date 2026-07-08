<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">Online Collections & Ledger</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">View real-time payment transactions and online vs offline collection metrics.</p>
            </div>
            <!-- Tabs -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200 dark:border-slate-700/50">
                <button 
                    @click="activeTab = 'transactions'"
                    :class="[
                        'px-4 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        activeTab === 'transactions' ? 'bg-white dark:bg-slate-900 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-850 dark:hover:text-slate-350'
                    ]"
                >
                    Transactions Log
                </button>
                <button 
                    @click="activeTab = 'reports'"
                    :class="[
                        'px-4 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                        activeTab === 'reports' ? 'bg-white dark:bg-slate-900 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-850 dark:hover:text-slate-350'
                    ]"
                >
                    Collection Reports
                </button>
            </div>
        </div>

        <div v-if="loading" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-12 flex flex-col items-center justify-center space-y-3">
            <div class="w-8 h-8 rounded-full border-2 border-indigo-600 border-t-transparent animate-spin"></div>
            <p class="text-xs text-slate-500 dark:text-slate-400">Fetching online payment history...</p>
        </div>

        <div v-else>
            <!-- TAB 1: TRANSACTIONS LOG -->
            <div v-if="activeTab === 'transactions'" class="space-y-6">
                <!-- Filters Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 dark:text-slate-450 uppercase">Filter Status</label>
                            <select 
                                v-model="filters.status"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="successful">Successful</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 dark:text-slate-455 uppercase">Start Date</label>
                            <input 
                                v-model="filters.start_date"
                                type="date"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 dark:text-slate-455 uppercase">End Date</label>
                            <input 
                                v-model="filters.end_date"
                                type="date"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex gap-2">
                            <button 
                                @click="fetchTransactions"
                                class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-750 text-white rounded-lg text-xs font-bold transition-all shadow-sm active:scale-95"
                            >
                                Apply Filters
                            </button>
                            <button 
                                @click="resetFilters"
                                class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 transition-all active:scale-95"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ledger List Table -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">
                                    <th class="px-6 py-4">Transaction Details</th>
                                    <th class="px-6 py-4">Student</th>
                                    <th class="px-6 py-4">Fee Installment</th>
                                    <th class="px-6 py-4">Amount</th>
                                    <th class="px-6 py-4">Payment ID / Order ID</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-center">Receipt</th>
                                    <th class="px-6 py-4 text-center">Payload</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-850">
                                <tr v-for="txn in transactions" :key="txn.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 dark:text-slate-100">#{{ txn.id }}</div>
                                        <div class="text-[10px] text-slate-450 mt-0.5">{{ formatDateTime(txn.created_at) }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-750 dark:text-slate-200" v-if="txn.student">
                                            {{ txn.student.first_name }} {{ txn.student.last_name }}
                                        </div>
                                        <div class="text-[10px] text-slate-500" v-if="txn.student">Adm: {{ txn.student.admission_no }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700 dark:text-slate-350">
                                        {{ txn.installment ? txn.installment.installment_name : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-slate-800 dark:text-slate-100">
                                        ₹{{ numberFormat(txn.amount) }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-[10px] text-slate-600 dark:text-slate-400">
                                        <div>P: {{ txn.payment_id || '—' }}</div>
                                        <div class="text-slate-400 dark:text-slate-550 mt-0.5">O: {{ txn.order_id || '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border capitalize',
                                            txn.status === 'successful' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 
                                            txn.status === 'failed' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' : 
                                            'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'
                                        ]">
                                            <span :class="['w-1.2 h-1.2 rounded-full', txn.status === 'successful' ? 'bg-emerald-500' : txn.status === 'failed' ? 'bg-rose-500' : 'bg-slate-400']"></span>
                                            {{ txn.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a 
                                            v-if="txn.status === 'successful' && txn.receipt_no"
                                            :href="`/api/fee-receipts/${txn.id}/pdf`" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-[11px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            PDF Receipt
                                        </a>
                                        <span v-else class="text-slate-400 dark:text-slate-600">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button 
                                            @click="inspectPayload(txn)"
                                            class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md text-slate-500 dark:text-slate-400 transition-colors"
                                            title="Inspect payload"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="transactions.length === 0">
                                    <td colspan="8" class="text-center py-8 text-slate-500 dark:text-slate-400">No transactions found under current filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: REPORTS & COLLECTION METRICS -->
            <div v-if="activeTab === 'reports'" class="space-y-6">
                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase">Online Collection</p>
                            <h3 class="text-2xl font-extrabold text-indigo-600 mt-1">₹{{ numberFormat(reportData.summary.total_online) }}</h3>
                        </div>
                        <div class="p-3 bg-indigo-500/10 text-indigo-650 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase">Offline Collection</p>
                            <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">₹{{ numberFormat(reportData.summary.total_offline) }}</h3>
                        </div>
                        <div class="p-3 bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase">Total Consolidated Dues Collected</p>
                            <h3 class="text-2xl font-extrabold text-emerald-600 mt-1">₹{{ numberFormat(reportData.summary.total_collection) }}</h3>
                        </div>
                        <div class="p-3 bg-emerald-500/10 text-emerald-650 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Panels -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Method Wise Collection -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Collection by Payment Method</h4>
                        <div class="space-y-3">
                            <div v-for="item in reportData.method_wise" :key="item.method" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-slate-600 dark:text-slate-400">{{ item.method }}</span>
                                    <span class="text-slate-800 dark:text-slate-100">₹{{ numberFormat(item.total) }} <span class="text-[10px] text-slate-500">({{ item.count }} txns)</span></span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-indigo-500" 
                                        :style="{ width: `${(item.total / reportData.summary.total_collection) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="reportData.method_wise.length === 0" class="text-center py-6 text-slate-500 text-xs">No records available.</div>
                        </div>
                    </div>

                    <!-- Gateway Wise Collection -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Gateway Transactions Share</h4>
                        <div class="space-y-3">
                            <div v-for="item in reportData.gateway_wise" :key="item.gateway" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-slate-600 dark:text-slate-400">{{ item.gateway }}</span>
                                    <span class="text-slate-800 dark:text-slate-100">₹{{ numberFormat(item.total) }} <span class="text-[10px] text-slate-500">({{ item.count }} captures)</span></span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-emerald-500" 
                                        :style="{ width: `${(item.total / reportData.summary.total_online) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="reportData.gateway_wise.length === 0" class="text-center py-6 text-slate-500 text-xs">No gateway captures recorded.</div>
                        </div>
                    </div>
                </div>

                <!-- Daily / Monthly trends -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
                    <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Recent Collection Trends (Last 15 Days)</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/30 border-b border-slate-200 dark:border-slate-800 text-slate-500 font-semibold uppercase">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 text-right">Collection amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="day in reportData.daily" :key="day.date" class="border-b border-slate-150 dark:border-slate-850 hover:bg-slate-50/50">
                                    <td class="px-4 py-2.5 font-mono">{{ day.date }}</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-slate-800 dark:text-slate-200">₹{{ numberFormat(day.total) }}</td>
                                </tr>
                                <tr v-if="reportData.daily.length === 0">
                                    <td colspan="2" class="text-center py-6 text-slate-500">No daily data logs recorded recently.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- INSPECT PAYLOAD MODAL -->
        <div v-if="inspectingTxn" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full overflow-hidden flex flex-col max-h-[85vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                    <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase">Inspect Transaction Gateway Response</h4>
                    <button @click="inspectingTxn = null" class="text-slate-550 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white p-1 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto flex-1 font-mono text-[10px] bg-slate-950 text-emerald-400 p-4 rounded-b-xl leading-relaxed whitespace-pre-wrap select-all">
                    {{ JSON.stringify(inspectingTxn.gateway_response, null, 4) || '// No gateway payload captured.' }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'OnlinePayments',
    setup() {
        const toastStore = useToastStore();
        const loading = ref(true);
        const activeTab = ref('transactions');
        const transactions = ref([]);
        const inspectingTxn = ref(null);

        const filters = reactive({
            status: '',
            start_date: '',
            end_date: '',
        });

        const reportData = reactive({
            summary: {
                total_online: 0,
                total_offline: 0,
                total_collection: 0,
                successful_count: 0,
                failed_count: 0
            },
            method_wise: [],
            gateway_wise: [],
            daily: [],
            monthly: []
        });

        const fetchTransactions = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/online-payments', {
                    params: filters
                });
                transactions.value = response.data.transactions;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load transaction history logs.');
            } finally {
                loading.value = false;
            }
        };

        const fetchReports = async () => {
            try {
                const response = await window.axios.get('/api/online-payments/reports');
                Object.assign(reportData, response.data);
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load online fee reports.');
            }
        };

        const resetFilters = () => {
            filters.status = '';
            filters.start_date = '';
            filters.end_date = '';
            fetchTransactions();
        };

        const inspectPayload = (txn) => {
            inspectingTxn.value = txn;
        };

        const formatDateTime = (dateStr) => {
            if (!dateStr) return '—';
            const date = new Date(dateStr);
            return date.toLocaleString('en-IN', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        };

        const numberFormat = (num) => {
            return parseFloat(num || 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };

        onMounted(async () => {
            await fetchTransactions();
            await fetchReports();
        });

        return {
            loading,
            activeTab,
            transactions,
            filters,
            reportData,
            inspectingTxn,
            fetchTransactions,
            resetFilters,
            inspectPayload,
            formatDateTime,
            numberFormat
        };
    }
}
</script>
