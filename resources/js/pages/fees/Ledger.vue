<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Student Fee Ledger</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">View detailed transaction ledger histories, debit schedules, and collected credits with running balances.</p>
            </div>
            <button
                v-if="selectedStudentId && ledgerData.transactions && ledgerData.transactions.length > 0"
                @click="printLedger"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-600 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Ledger
            </button>
        </div>

        <!-- Student Selector Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Academic Session</label>
                <select 
                    v-model="filters.academic_year_id" 
                    @change="handleAcademicYearChange"
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none"
                >
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Class</label>
                <select 
                    v-model="filters.class_id" 
                    @change="handleClassChange"
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option value="">Select Class</option>
                    <option v-for="c in classes" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Section</label>
                <select 
                    v-model="filters.section_id" 
                    @change="handleSectionChange"
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option value="">All Sections</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                        {{ sec.name }}
                    </option>
                </select>
            </div>

            <div>
                <button 
                    type="button"
                    @click="fetchStudents"
                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center justify-center gap-1.5 cursor-pointer h-[38px]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Apply Filter
                </button>
            </div>
        </div>

        <div v-if="loading" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl animate-pulse space-y-4">
            <div class="h-8 bg-slate-200 dark:bg-slate-800 rounded max-w-sm mx-auto"></div>
            <div class="h-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
        </div>

        <div v-else-if="!selectedStudentId">
            <!-- If Class is selected, show list of students in the class -->
            <div v-if="filters.class_id" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-3 border-b border-slate-100 dark:border-slate-850">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-xs uppercase tracking-wider">
                        Students in {{ classes.find(c => c.id === filters.class_id)?.name }} {{ filters.section_id ? '-' + (sections.find(s => s.id === filters.section_id)?.name || '') : '' }}
                    </h3>
                    <div class="w-full sm:w-72 relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input 
                            type="text" 
                            v-model="studentSearchQuery" 
                            @input="handleStudentSearchInput"
                            placeholder="Search by name or Adm No..." 
                            class="w-full pl-9 pr-8 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold"
                        />
                    </div>
                </div>
                <div v-if="studentList.length === 0" class="text-center py-12 text-slate-400 dark:text-slate-600 text-xs">
                    No matching student records found.
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="stu in studentList" 
                        :key="stu.id" 
                        @click="selectStudent(stu)"
                        class="p-4 border border-slate-150 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500/50 bg-slate-50/50 dark:bg-slate-950/20 hover:bg-white dark:hover:bg-slate-900 rounded-2xl cursor-pointer transition-all hover:shadow-md flex flex-col justify-between gap-3 group active:scale-95"
                    >
                        <div class="space-y-1">
                            <div class="font-extrabold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors text-xs truncate">
                                {{ stu.first_name }}
                            </div>
                            <div class="text-[9px] text-slate-450 dark:text-slate-500 font-bold uppercase tracking-wider">
                                Adm: {{ stu.admission_no }}
                            </div>
                            <div v-if="stu.section" class="text-[9px] text-slate-400 dark:text-slate-650 font-bold uppercase">
                                Sec: {{ stu.section }}
                            </div>
                        </div>
                        <button 
                            type="button"
                            class="w-full py-1.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-650 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 dark:hover:text-white font-bold text-[10px] rounded-xl transition-all border-none cursor-pointer flex items-center justify-center gap-1"
                        >
                            <span>View Ledger</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Otherwise show fallback placeholder -->
            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-slate-400 shadow-sm flex flex-col justify-center items-center h-48">
                <svg class="w-16 h-16 text-slate-350 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m14 0h-3a2 2 0 00-2 2v3m2 4H9m6 0a3 3 0 11-6 0v-1m6 0H9m11-4V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2z"></path></svg>
                Select a class above or search a student account to view their financial transaction ledger.
            </div>
        </div>

        <!-- Ledger View -->
        <div v-else class="space-y-6">
            <!-- Summary Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Assigned</span>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white mt-1.5">₹{{ numberFormat(ledgerData.summary?.total_fees) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Paid</span>
                    <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-450 mt-1.5">₹{{ numberFormat(ledgerData.summary?.total_paid) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Discount</span>
                    <h3 class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1.5">₹{{ numberFormat(ledgerData.summary?.total_discount) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Fines Paid</span>
                    <h3 class="text-xl font-black text-pink-600 dark:text-pink-400 mt-1.5">₹{{ numberFormat(ledgerData.summary?.total_fine) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center col-span-2 md:col-span-1">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Remaining Amount</span>
                    <h3 class="text-xl font-black text-rose-600 dark:text-rose-450 mt-1.5">₹{{ numberFormat(ledgerData.summary?.outstanding_balance) }}</h3>
                </div>
            </div>

            <!-- Ledger Table -->
            <div id="print-ledger-area" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-extrabold text-slate-800 dark:text-white text-sm uppercase tracking-wider">Account Statements</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Chronological record of fee assignments, penalties, and collection credits.</p>
                    </div>
                    <div class="text-xs text-slate-600 dark:text-slate-300 font-semibold bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-xl">
                        Student: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ ledgerData.student?.name }}</span> | Adm #: {{ ledgerData.student?.admission_no }}
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6 w-32">Date</th>
                                <th class="p-4">Transaction Details</th>
                                <th class="p-4 w-32 text-center">Type</th>
                                <th class="p-4 w-36 text-right">Debit (+)</th>
                                <th class="p-4 w-36 text-right">Credit (-)</th>
                                <th class="p-4 pr-6 w-40 text-right">Remaining Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                            <tr v-if="!ledgerData.transactions || ledgerData.transactions.length === 0">
                                <td colspan="6" class="p-12 text-center text-slate-400">
                                    No financial transaction entries recorded for this student in the selected session.
                                </td>
                            </tr>
                            <tr v-else v-for="(tx, idx) in ledgerData.transactions" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-medium whitespace-nowrap">
                                    {{ formatDate(tx.date) }}
                                </td>
                                <td class="p-4">
                                    <div class="font-semibold text-slate-850 dark:text-white">
                                        {{ tx.description }}
                                    </div>
                                    <div v-if="tx.reference" class="text-xs text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                        Ref: {{ tx.reference }}
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider',
                                        tx.type === 'debit' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                    ]">
                                        {{ tx.type }}
                                    </span>
                                </td>
                                <td class="p-4 text-right font-semibold text-rose-600 dark:text-rose-400">
                                    <span v-if="tx.type === 'debit'">₹{{ numberFormat(tx.amount) }}</span>
                                    <span v-else class="text-slate-300 dark:text-slate-700">-</span>
                                </td>
                                <td class="p-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                    <span v-if="tx.type === 'credit'">-₹{{ numberFormat(tx.amount) }}</span>
                                    <span v-else class="text-slate-300 dark:text-slate-700">-</span>
                                </td>
                                <td class="p-4 pr-6 text-right font-black text-slate-900 dark:text-white">
                                    ₹{{ numberFormat(tx.running_balance) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'StudentLedgerIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const academicYears = ref([]);
        const classes = ref([]);
        const sections = ref([]);
        const studentList = ref([]);
        const selectedStudentId = ref('');
        const ledgerData = ref({});
        const loading = ref(false);

        // Autocomplete search refs
        const studentSearchQuery = ref('');
        const showSearchDropdown = ref(false);
        const searchingStudents = ref(false);

        const allClassStudents = ref([]);

        const filters = ref({
            academic_year_id: '',
            class_id: '',
            section_id: ''
        });

        const fetchClasses = async () => {
            try {
                const params = {};
                if (filters.value.academic_year_id) {
                    params.academic_year_id = filters.value.academic_year_id;
                }
                const cRes = await window.axios.get('/api/classes', { params });
                classes.value = cRes.data.classes || cRes.data;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load classes.');
            }
        };

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

                // Fetch Classes
                await fetchClasses();
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load filter configurations.');
            }
        };

        const handleAcademicYearChange = async () => {
            filters.value.class_id = '';
            filters.value.section_id = '';
            sections.value = [];
            clearSelectedStudent();
            await fetchClasses();
        };

        const handleClassChange = async () => {
            filters.value.section_id = '';
            sections.value = [];
            clearSelectedStudent();

            if (filters.value.class_id) {
                try {
                    const response = await window.axios.get('/api/sections', {
                        params: { class_id: filters.value.class_id }
                    });
                    sections.value = response.data.sections;
                } catch (error) {
                    console.error(error);
                }
            }
        };

        const handleSectionChange = () => {
            clearSelectedStudent();
        };

        const fetchStudents = async () => {
            if (!filters.value.academic_year_id || !filters.value.class_id) {
                allClassStudents.value = [];
                studentList.value = [];
                return;
            }
            try {
                const response = await window.axios.get('/api/fee-assignments', {
                    params: {
                        academic_year_id: filters.value.academic_year_id,
                        class_id: filters.value.class_id,
                        section_id: filters.value.section_id
                    }
                });
                allClassStudents.value = (response.data.students || []).map(s => ({
                    id: s.student_id,
                    first_name: s.name,
                    last_name: '',
                    admission_no: s.admission_no,
                    section: s.section,
                    class_name: s.class
                }));
                studentList.value = allClassStudents.value;
            } catch (error) {
                console.error(error);
            }
        };

        const handleStudentSearchInput = async () => {
            if (!filters.value.academic_year_id) return;
            const q = studentSearchQuery.value.trim();

            if (filters.value.class_id) {
                // Local filter from pre-fetched class list
                if (!q) {
                    studentList.value = allClassStudents.value;
                } else {
                    const lowQ = q.toLowerCase();
                    studentList.value = allClassStudents.value.filter(s => 
                        s.first_name.toLowerCase().includes(lowQ) || 
                        s.admission_no.toLowerCase().includes(lowQ)
                    );
                }
                return;
            }

            // Global search on server (when class is not selected)
            if (q.length < 2) {
                studentList.value = [];
                return;
            }
            searchingStudents.value = true;
            try {
                const response = await window.axios.get('/api/fee-assignments', {
                    params: {
                        academic_year_id: filters.value.academic_year_id,
                        search: q
                    }
                });
                studentList.value = response.data.students.map(s => ({
                    id: s.student_id,
                    first_name: s.name,
                    last_name: '',
                    admission_no: s.admission_no,
                    section: s.section,
                    class_name: s.class
                }));
            } catch (error) {
                console.error(error);
            } finally {
                searchingStudents.value = false;
            }
        };

        const selectStudent = (stu) => {
            selectedStudentId.value = stu.id;
            studentSearchQuery.value = `${stu.first_name} ${stu.last_name} (Adm: ${stu.admission_no})`;
            showSearchDropdown.value = false;
            fetchLedger();
        };

        const clearSelectedStudent = () => {
            selectedStudentId.value = '';
            studentSearchQuery.value = '';
            ledgerData.value = {};
            showSearchDropdown.value = false;
            if (filters.value.class_id) {
                studentList.value = allClassStudents.value;
            } else {
                studentList.value = [];
            }
        };

        const closeDropdownOnOutsideClick = (e) => {
            const container = document.getElementById('student-search-container');
            if (container && !container.contains(e.target)) {
                showSearchDropdown.value = false;
            }
        };

        const fetchLedger = async () => {
            if (!selectedStudentId.value) return;
            loading.value = true;
            try {
                const response = await window.axios.get(`/api/student-ledgers/${selectedStudentId.value}`, {
                    params: { academic_year_id: filters.value.academic_year_id }
                });
                ledgerData.value = response.data;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load student ledger transactions.');
            } finally {
                loading.value = false;
            }
        };

        const printLedger = () => {
            const printContent = document.getElementById('print-ledger-area').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Student Fee Ledger - ${ledgerData.value.student?.name}</title>
                        <style>
                            body { font-family: system-ui, -apple-system, sans-serif; color: #1e293b; padding: 24px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th, td { border-bottom: 1px solid #e2e8f0; padding: 12px; text-align: left; font-size: 13px; }
                            th { background-color: #f8fafc; font-weight: bold; color: #475569; text-transform: uppercase; font-size: 11px; }
                            .text-right { text-align: right; }
                            .text-center { text-align: center; }
                            .font-mono { font-family: monospace; }
                            .header-container { display: flex; justify-content: space-between; border-bottom: 2px solid #cbd5e1; padding-bottom: 12px; margin-bottom: 20px; }
                            .student-details { font-weight: 600; color: #4f46e5; }
                            .debit { color: #dc2626; font-weight: 600; }
                            .credit { color: #16a34a; font-weight: 600; }
                            .balance { font-weight: 800; color: #0f172a; }
                            .summary-grid { display: grid; grid-template-cols: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; text-align: center; }
                            .summary-card { border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; background-color: #f8fafc; }
                            .summary-title { font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; }
                            .summary-value { font-size: 15px; font-weight: 800; margin-top: 4px; }
                        </style>
                    </head>
                    <body>
                        <div class="header-container">
                            <div>
                                <h2 style="margin: 0; font-size: 20px; font-weight: 800;">Student Fee Ledger Statement</h2>
                                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">Generated on ${new Date().toLocaleDateString()}</p>
                            </div>
                            <div style="text-align: right; font-size: 13px;">
                                <div class="student-details">${ledgerData.value.student?.name}</div>
                                <div style="color: #64748b; margin-top: 2px;">Admission # ${ledgerData.value.student?.admission_no}</div>
                            </div>
                        </div>

                        <div class="summary-grid">
                            <div class="summary-card">
                                <div class="summary-title">Total Assigned</div>
                                <div class="summary-value">₹${numberFormat(ledgerData.value.summary?.total_fees)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Total Paid</div>
                                <div class="summary-value" style="color: #16a34a;">₹${numberFormat(ledgerData.value.summary?.total_paid)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Total Discount</div>
                                <div class="summary-value" style="color: #4f46e5;">₹${numberFormat(ledgerData.value.summary?.total_discount)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Fines Paid</div>
                                <div class="summary-value" style="color: #db2777;">₹${numberFormat(ledgerData.value.summary?.total_fine)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Remaining Amount</div>
                                <div class="summary-value" style="color: #dc2626;">₹${numberFormat(ledgerData.value.summary?.outstanding_balance)}</div>
                            </div>
                        </div>

                        ${printContent.replace(/class="hover:bg-slate-50 dark:hover:bg-slate-800\/30 transition-colors"/g, '')}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
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
            document.addEventListener('click', closeDropdownOnOutsideClick);
        });

        onUnmounted(() => {
            document.removeEventListener('click', closeDropdownOnOutsideClick);
        });

        return {
            authStore,
            academicYears,
            classes,
            sections,
            studentList,
            selectedStudentId,
            ledgerData,
            loading,
            filters,
            studentSearchQuery,
            showSearchDropdown,
            searchingStudents,
            allClassStudents,
            handleAcademicYearChange,
            handleClassChange,
            handleSectionChange,
            handleStudentSearchInput,
            selectStudent,
            clearSelectedStudent,
            fetchStudents,
            fetchLedger,
            printLedger,
            numberFormat,
            formatDate
        };
    }
}
</script>
