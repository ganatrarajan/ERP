<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Receipts</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Search and manage system-generated fee collection transaction invoices and printable PDF receipts.</p>
            </div>
        </div>

        <!-- Filters Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Academic Session</label>
                <select 
                    v-model="filters.academic_year_id" 
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }}
                    </option>
                </select>
            </div>

            <!-- Payment Date Filter -->
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Payment Date</label>
                <div class="relative flex items-center">
                    <input 
                        v-model="filters.payment_date" 
                        type="date" 
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                    />
                    <button 
                        v-if="filters.payment_date"
                        @click="clearDateFilter"
                        class="absolute right-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                        title="Clear date filter"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-505 uppercase tracking-wider">Search Receipt Number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        type="text" 
                        placeholder="Search by receipt number..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                    />
                </div>
            </div>

            <div>
                <button 
                    type="button"
                    @click="fetchReceipts(1)"
                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center justify-center gap-1.5 cursor-pointer h-[38px]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Search Receipts
                </button>
            </div>
        </div>

        <!-- Quick Filters for Payment Methods -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <button 
                @click="setPaymentMethodFilter('')"
                :class="[
                    'px-4 py-2 text-xs font-bold rounded-xl border transition-all active:scale-95 cursor-pointer',
                    !filters.payment_method 
                        ? 'bg-indigo-600 border-indigo-650 text-white shadow-sm' 
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800/60'
                ]"
            >
                All Methods
            </button>
            <button 
                v-for="method in ['Cash', 'UPI', 'Cheque', 'Bank Transfer']" 
                :key="method"
                @click="setPaymentMethodFilter(method)"
                :class="[
                    'px-4 py-2 text-xs font-bold rounded-xl border transition-all active:scale-95 cursor-pointer',
                    filters.payment_method === method 
                        ? 'bg-indigo-600 border-indigo-650 text-white shadow-sm' 
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800/80 text-slate-655 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800/60'
                ]"
            >
                {{ method }}
            </button>
        </div>

        <!-- Table Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="receipts.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                No fee collection receipts found matching the filters.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Receipt No</th>
                            <th class="p-4">Student Name</th>
                            <th class="p-4">Adm No</th>
                            <th class="p-4">Installment</th>
                            <th class="p-4">Payment Date</th>
                            <th class="p-4 text-right">Total Dues</th>
                            <th class="p-4 text-right">Amount Paid</th>
                            <th class="p-4 text-right">Remaining Dues</th>
                            <th class="p-4">Payment Mode</th>
                            <th class="p-4 pr-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="receipt in receipts" :key="receipt.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-bold text-indigo-600 dark:text-indigo-400">
                                {{ receipt.receipt_number }}
                            </td>
                            <td class="p-4 font-semibold text-slate-855 dark:text-slate-100">
                                {{ receipt.collection?.student ? (receipt.collection.student.first_name + ' ' + receipt.collection.student.last_name) : 'N/A' }}
                            </td>
                            <td class="p-4 font-mono text-xs text-slate-500">
                                {{ receipt.collection?.student ? receipt.collection.student.admission_no : 'N/A' }}
                            </td>
                            <td class="p-4">
                                {{ receipt.collection?.installment ? receipt.collection.installment.installment_name : 'N/A' }}
                            </td>
                            <td class="p-4">
                                {{ formatDate(receipt.collection?.payment_date) }}
                            </td>
                            <td class="p-4 text-right font-medium text-slate-600 dark:text-slate-400">
                                ₹{{ numberFormat(receipt.collection?.amount_due || 0) }}
                            </td>
                            <td class="p-4 text-right font-black text-emerald-600 dark:text-emerald-450">
                                ₹{{ numberFormat(receipt.collection?.amount_paid || 0) }}
                            </td>
                            <td class="p-4 text-right">
                                <div :class="(receipt.collection?.amount_due - receipt.collection?.amount_paid - receipt.collection?.discount_amount) > 0 ? 'text-rose-600 font-black' : 'text-slate-400 font-semibold'">
                                    ₹{{ numberFormat(Math.max(0, receipt.collection?.amount_due - receipt.collection?.amount_paid - receipt.collection?.discount_amount)) }}
                                </div>
                                <div v-if="(receipt.collection?.amount_due - receipt.collection?.amount_paid - receipt.collection?.discount_amount) > 0" class="text-[9px] text-rose-500 font-bold uppercase tracking-wide mt-0.5">
                                    Partially Paid
                                </div>
                                <div v-else class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wide mt-0.5">
                                    Fully Paid
                                </div>
                            </td>
                            <td class="p-4">
                                {{ receipt.collection?.payment_method }}
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <button 
                                    @click="printReceipt(receipt)"
                                    class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-250 dark:border-slate-700 rounded-lg text-indigo-600 dark:text-indigo-400 transition-all active:scale-95 flex items-center justify-center gap-1.5 ml-auto"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    View PDF
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex items-center justify-between">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} receipts
                </div>
                <div class="flex items-center gap-1.5">
                    <button 
                        @click="changePage(pagination.current_page - 1)" 
                        :disabled="pagination.current_page <= 1"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Previous
                    </button>
                    <button 
                        @click="changePage(pagination.current_page + 1)" 
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'ReceiptsIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const route = useRoute();

        const receipts = ref([]);
        const academicYears = ref([]);
        const loading = ref(true);
        const hasSearched = ref(false);

        const filters = ref({
            academic_year_id: '',
            search: '',
            payment_date: '',
            payment_method: ''
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        });

        let searchTimeout = null;

        const fetchFiltersData = async () => {
            try {
                // Fetch Academic Years
                const yRes = await window.axios.get('/api/academic-years');
                academicYears.value = yRes.data.academic_years;
                const activeYear = academicYears.value.find(y => y.is_current);
                if (activeYear) {
                    filters.value.academic_year_id = activeYear.id;
                } else if (academicYears.value.length > 0) {
                    filters.value.academic_year_id = academicYears.value[0].id;
                }
            } catch (error) {
                console.error(error);
            }
        };

        const fetchReceipts = async (page = 1) => {
            if (!filters.value.academic_year_id) return;
            loading.value = true;
            hasSearched.value = true;
            try {
                const response = await window.axios.get('/api/fee-receipts', {
                    params: {
                        page,
                        academic_year_id: filters.value.academic_year_id,
                        search: filters.value.search,
                        payment_date: filters.value.payment_date,
                        payment_method: filters.value.payment_method
                    }
                });
                receipts.value = response.data.data;
                pagination.value = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    from: response.data.from,
                    to: response.data.to,
                    total: response.data.total
                };
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load receipts.');
            } finally {
                loading.value = false;
            }
        };

        const setPaymentMethodFilter = (method) => {
            filters.value.payment_method = method;
            if (hasSearched.value) {
                fetchReceipts(1);
            }
        };

        const handleSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchReceipts(1);
            }, 300);
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                fetchReceipts(page);
            }
        };

        const clearDateFilter = () => {
            filters.value.payment_date = '';
            if (hasSearched.value) {
                fetchReceipts(1);
            }
        };

        const printReceipt = (receipt) => {
            window.open(`/api/fee-receipts/${receipt.id}/pdf`, '_blank');
        };

        const numberFormat = (val) => {
            return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        };

        onMounted(async () => {
            await fetchFiltersData();
            if (route.query.date) {
                filters.value.payment_date = route.query.date;
            }
            await fetchReceipts();
        });

        return {
            authStore,
            receipts,
            academicYears,
            loading,
            hasSearched,
            filters,
            pagination,
            fetchReceipts,
            setPaymentMethodFilter,
            handleSearch,
            changePage,
            printReceipt,
            numberFormat,
            formatDate,
            clearDateFilter
        };
    }
}
</script>
