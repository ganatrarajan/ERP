<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Fees Financial Reports</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Generate, analyze, and print statements for collections, overdue dues, waivers, and penalties.</p>
            </div>
            <button
                v-if="reportData.length > 0"
                @click="printReport"
                class="px-4 py-2 bg-indigo-650 hover:bg-indigo-600 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </button>
        </div>

        <!-- Filters Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Academic Session -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Academic Session *</label>
                    <select 
                        v-model="filters.academic_year_id" 
                        @change="handleAcademicYearChange"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none"
                    >
                        <option v-for="year in academicYears" :key="year.id" :value="year.id">
                            {{ year.title }}
                        </option>
                    </select>
                </div>

                <!-- Report Type -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Report Variant *</label>
                    <select 
                        v-model="filters.report_type" 
                        @change="handleReportTypeChange"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none"
                    >
                        <option value="collection">Collections Register (Detailed)</option>
                        <option value="pending">Outstanding Balances (Dues)</option>
                        <option value="installment_wise">Installment-wise Summary</option>
                        <option value="class_wise">Class-wise Summary</option>
                        <option value="student_wise">Student-wise Summary</option>
                        <option value="daily">Daily Collection Log</option>
                        <option value="monthly">Monthly Collection Log</option>
                        <option value="discount">Discounts / Waivers Applied</option>
                        <option value="fine">Late Fines Register</option>
                    </select>
                </div>

                <!-- Class -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Class Filter</label>
                    <select 
                        v-model="filters.class_id" 
                        @change="handleClassChange"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none"
                    >
                        <option value="">All Classes</option>
                        <option v-for="c in classes" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>
                </div>

                <!-- Section -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Section Filter</label>
                    <select 
                        v-model="filters.section_id" 
                        @change="handleSectionChange"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none"
                    >
                        <option value="">All Sections</option>
                        <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                            {{ sec.name }}
                        </option>
                    </select>
                </div>

                <!-- Student Filter (only for collection/pending/discount/fine/student_wise) -->
                <div v-if="showStudentFilter" class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Student Account</label>
                    <select 
                        v-model="filters.student_id" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-750 dark:text-slate-350 focus:outline-none"
                    >
                        <option value="">All Students</option>
                        <option v-for="stu in studentList" :key="stu.id" :value="stu.id">
                            {{ stu.first_name }} {{ stu.last_name }} (Adm #: {{ stu.admission_no }})
                        </option>
                    </select>
                </div>

                <!-- Start Date -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">From Date</label>
                    <input 
                        v-model="filters.start_date" 
                        v-datepicker
                        type="text" 
                        placeholder="YYYY-MM-DD"
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                    />
                </div>

                <!-- End Date -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">To Date</label>
                    <input 
                        v-model="filters.end_date" 
                        v-datepicker
                        type="text" 
                        placeholder="YYYY-MM-DD"
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                    />
                </div>

                <!-- Action Button -->
                <div class="flex items-end md:col-span-1">
                    <button 
                        @click="generateReport"
                        :disabled="generating"
                        class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm rounded-xl shadow-lg active:scale-95 disabled:scale-100 disabled:opacity-60 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <svg v-if="!generating" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m14 0h-3a2 2 0 00-2 2v3m2 4H9m6 0a3 3 0 11-6 0v-1m6 0H9m11-4V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2z"></path></svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>{{ generating ? 'Generating...' : 'Generate Report' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Report Statements Result -->
        <div v-if="loading" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl animate-pulse space-y-4">
            <div class="h-8 bg-slate-200 dark:bg-slate-800 rounded max-w-sm mx-auto"></div>
            <div class="h-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
        </div>

        <div v-else-if="!hasGenerated" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-slate-400 shadow-sm flex flex-col justify-center items-center h-48">
            <svg class="w-16 h-16 text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m14 0h-3a2 2 0 00-2 2v3m2 4H9m6 0a3 3 0 11-6 0v-1m6 0H9m11-4V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2z"></path></svg>
            Choose a report type and filter parameters above, then click "Generate Report".
        </div>

        <div v-else class="space-y-6">
            <!-- Aggregate Summary Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="summaryAggregates">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Collected</span>
                        <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">₹{{ numberFormat(summaryAggregates.collected) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Discounts</span>
                        <h3 class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1">₹{{ numberFormat(summaryAggregates.discounts) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4"></path></svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Fines Collected</span>
                        <h3 class="text-xl font-black text-pink-650 dark:text-pink-400 mt-1">₹{{ numberFormat(summaryAggregates.fines) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-pink-50 dark:bg-pink-500/10 flex items-center justify-center text-pink-600 dark:text-pink-450">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Report Grid Table -->
            <div id="print-report-area" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="font-extrabold text-slate-800 dark:text-white text-sm uppercase tracking-wider">Report Output: {{ getReportTitle() }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Academic session: {{ getSessionTitle() }} | Dates: {{ filters.start_date || 'Inception' }} to {{ filters.end_date || 'Present' }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <!-- 1. COLLECTION REGISTER -->
                    <table v-if="filters.report_type === 'collection'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Receipt #</th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4">Class</th>
                                <th class="p-4">Installment</th>
                                <th class="p-4">Date</th>
                                <th class="p-4">Method</th>
                                <th class="p-4 text-right">Amount Paid</th>
                                <th class="p-4 text-right">Discount</th>
                                <th class="p-4 text-right">Fine</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="9" class="p-10 text-center text-slate-400">No collection records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-mono text-xs">{{ row.receipt_number }}</td>
                                <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                    {{ row.student_name }}
                                    <div class="text-[10px] text-slate-400 font-mono">Adm #: {{ row.admission_no }}</div>
                                </td>
                                <td class="p-4">{{ row.class_name }}</td>
                                <td class="p-4">{{ row.installment }}</td>
                                <td class="p-4 whitespace-nowrap">{{ formatDate(row.payment_date) }}</td>
                                <td class="p-4">{{ row.payment_method }}</td>
                                <td class="p-4 text-right font-medium text-emerald-600 dark:text-emerald-400">₹{{ numberFormat(row.amount_paid) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.discount_amount) }}</td>
                                <td class="p-4 text-right text-pink-500">₹{{ numberFormat(row.fine_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 2. PENDING FEES -->
                    <table v-else-if="filters.report_type === 'pending'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Student Name</th>
                                <th class="p-4">Admission No</th>
                                <th class="p-4">Class / Section</th>
                                <th class="p-4 text-right">Total Fees</th>
                                <th class="p-4 text-right">Paid</th>
                                <th class="p-4 text-right">Discount</th>
                                <th class="p-4 text-right">Fines Paid</th>
                                <th class="p-4 pr-6 text-right">Outstanding Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="8" class="p-10 text-center text-slate-400">No outstanding balance records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">{{ row.student_name }}</td>
                                <td class="p-4 font-mono text-xs">{{ row.admission_no }}</td>
                                <td class="p-4">{{ row.class_name }} {{ row.section_name ? '('+row.section_name+')' : '' }}</td>
                                <td class="p-4 text-right">₹{{ numberFormat(row.total_fee) }}</td>
                                <td class="p-4 text-right text-emerald-600 dark:text-emerald-450">₹{{ numberFormat(row.total_paid) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 text-right text-pink-500">₹{{ numberFormat(row.total_fine) }}</td>
                                <td class="p-4 pr-6 text-right font-black text-rose-600 dark:text-rose-400">₹{{ numberFormat(row.outstanding_balance) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 3. INSTALLMENT WISE -->
                    <table v-else-if="filters.report_type === 'installment_wise'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Installment Name</th>
                                <th class="p-4">Due Date</th>
                                <th class="p-4 text-center">Txns Count</th>
                                <th class="p-4 text-right">Total Collected</th>
                                <th class="p-4 text-right">Total Discounts</th>
                                <th class="p-4 pr-6 text-right">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="6" class="p-10 text-center text-slate-400">No installment records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-850 dark:text-white">{{ row.installment_name }}</td>
                                <td class="p-4">{{ formatDate(row.due_date) }}</td>
                                <td class="p-4 text-center font-mono">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 4. CLASS WISE -->
                    <table v-else-if="filters.report_type === 'class_wise'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Class Name</th>
                                <th class="p-4 text-center">Txns Count</th>
                                <th class="p-4 text-right">Total Collected</th>
                                <th class="p-4 text-right">Total Discounts</th>
                                <th class="p-4 pr-6 text-right">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="5" class="p-10 text-center text-slate-400">No class collections recorded.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-850 dark:text-white">{{ row.class_name }}</td>
                                <td class="p-4 text-center font-mono">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 5. STUDENT WISE -->
                    <table v-else-if="filters.report_type === 'student_wise'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Student Name</th>
                                <th class="p-4">Admission No</th>
                                <th class="p-4">Class</th>
                                <th class="p-4 text-center">Txns Count</th>
                                <th class="p-4 text-right">Total Collected</th>
                                <th class="p-4 text-right">Total Discounts</th>
                                <th class="p-4 pr-6 text-right">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="7" class="p-10 text-center text-slate-400">No student collections recorded.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-850 dark:text-white">{{ row.student_name }}</td>
                                <td class="p-4 font-mono text-xs">{{ row.admission_no }}</td>
                                <td class="p-4">{{ row.class_name }}</td>
                                <td class="p-4 text-center font-mono">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 6. DAILY LOG -->
                    <table v-else-if="filters.report_type === 'daily'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Date</th>
                                <th class="p-4 text-center">Txns Count</th>
                                <th class="p-4 text-right">Total Collected</th>
                                <th class="p-4 text-right">Total Discounts</th>
                                <th class="p-4 pr-6 text-right">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="5" class="p-10 text-center text-slate-400">No collections for the range.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-medium">{{ formatDate(row.date) }}</td>
                                <td class="p-4 text-center font-mono">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 7. MONTHLY LOG -->
                    <table v-else-if="filters.report_type === 'monthly'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Month</th>
                                <th class="p-4 text-center">Txns Count</th>
                                <th class="p-4 text-right">Total Collected</th>
                                <th class="p-4 text-right">Total Discounts</th>
                                <th class="p-4 pr-6 text-right">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="5" class="p-10 text-center text-slate-400">No collections recorded in this session.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-bold text-slate-800 dark:text-slate-200">{{ formatMonth(row.month) }}</td>
                                <td class="p-4 text-center font-mono">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 8. DISCOUNT WAIVERS -->
                    <table v-else-if="filters.report_type === 'discount'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Receipt #</th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4">Class</th>
                                <th class="p-4">Installment</th>
                                <th class="p-4">Date</th>
                                <th class="p-4 text-right">Discount Amount</th>
                                <th class="p-4 pr-6">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="7" class="p-10 text-center text-slate-400">No discount records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-mono text-xs">{{ row.receipt_number }}</td>
                                <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                    {{ row.student_name }}
                                    <div class="text-[10px] text-slate-400 font-mono">Adm #: {{ row.admission_no }}</div>
                                </td>
                                <td class="p-4">{{ row.class_name }}</td>
                                <td class="p-4">{{ row.installment }}</td>
                                <td class="p-4 whitespace-nowrap">{{ formatDate(row.payment_date) }}</td>
                                <td class="p-4 text-right font-black text-indigo-650 dark:text-indigo-400">₹{{ numberFormat(row.discount_amount) }}</td>
                                <td class="p-4 pr-6 text-slate-500 text-xs">{{ row.remarks || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 9. OVERDUE FINES -->
                    <table v-else-if="filters.report_type === 'fine'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Receipt #</th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4">Class</th>
                                <th class="p-4">Installment</th>
                                <th class="p-4">Date</th>
                                <th class="p-4 text-right">Fine Amount</th>
                                <th class="p-4 pr-6">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="7" class="p-10 text-center text-slate-400">No late fine records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-mono text-xs">{{ row.receipt_number }}</td>
                                <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                    {{ row.student_name }}
                                    <div class="text-[10px] text-slate-400 font-mono">Adm #: {{ row.admission_no }}</div>
                                </td>
                                <td class="p-4">{{ row.class_name }}</td>
                                <td class="p-4">{{ row.installment }}</td>
                                <td class="p-4 whitespace-nowrap">{{ formatDate(row.payment_date) }}</td>
                                <td class="p-4 text-right font-black text-pink-600 dark:text-pink-400">₹{{ numberFormat(row.fine_amount) }}</td>
                                <td class="p-4 pr-6 text-slate-500 text-xs">{{ row.remarks || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'FeesReportsIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const academicYears = ref([]);
        const classes = ref([]);
        const sections = ref([]);
        const studentList = ref([]);

        const reportData = ref([]);
        const loading = ref(false);
        const generating = ref(false);
        const hasGenerated = ref(false);

        const filters = ref({
            academic_year_id: '',
            report_type: 'collection',
            class_id: '',
            section_id: '',
            student_id: '',
            start_date: '',
            end_date: ''
        });

        // Determine when to show student filter
        const showStudentFilter = computed(() => {
            const rt = filters.value.report_type;
            return ['collection', 'pending', 'discount', 'fine', 'student_wise'].includes(rt);
        });

        // Compute aggregates dynamically from dataset
        const summaryAggregates = computed(() => {
            if (!hasGenerated.value || reportData.value.length === 0) return null;

            let collected = 0;
            let discounts = 0;
            let fines = 0;

            const rt = filters.value.report_type;

            reportData.value.forEach(row => {
                if (rt === 'collection') {
                    collected += Number(row.amount_paid || 0);
                    discounts += Number(row.discount_amount || 0);
                    fines += Number(row.fine_amount || 0);
                } else if (rt === 'pending') {
                    collected += Number(row.total_paid || 0);
                    discounts += Number(row.total_discount || 0);
                    fines += Number(row.total_fine || 0);
                } else if (['installment_wise', 'class_wise', 'student_wise', 'daily', 'monthly'].includes(rt)) {
                    collected += Number(row.total_collected || 0);
                    discounts += Number(row.total_discount || 0);
                    fines += Number(row.total_fine || 0);
                } else if (rt === 'discount') {
                    discounts += Number(row.discount_amount || 0);
                } else if (rt === 'fine') {
                    fines += Number(row.fine_amount || 0);
                }
            });

            return { collected, discounts, fines };
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
                toastStore.error('Failed to load filter metrics.');
            }
        };

        const handleAcademicYearChange = async () => {
            filters.value.class_id = '';
            filters.value.section_id = '';
            filters.value.student_id = '';
            sections.value = [];
            studentList.value = [];
            reportData.value = [];
            hasGenerated.value = false;
            await fetchClasses();
        };

        const handleClassChange = async () => {
            filters.value.section_id = '';
            filters.value.student_id = '';
            sections.value = [];
            studentList.value = [];

            if (filters.value.class_id) {
                try {
                    const response = await window.axios.get('/api/sections', {
                        params: { class_id: filters.value.class_id }
                    });
                    sections.value = response.data.sections;
                } catch (error) {
                    console.error(error);
                }
                fetchStudents();
            }
        };

        const handleSectionChange = () => {
            filters.value.student_id = '';
            studentList.value = [];
            fetchStudents();
        };

        const handleReportTypeChange = () => {
            // Reset report output and states
            reportData.value = [];
            hasGenerated.value = false;
        };

        const fetchStudents = async () => {
            if (!filters.value.academic_year_id || !filters.value.class_id) return;
            try {
                const response = await window.axios.get('/api/fee-assignments', {
                    params: {
                        academic_year_id: filters.value.academic_year_id,
                        class_id: filters.value.class_id,
                        section_id: filters.value.section_id
                    }
                });
                studentList.value = response.data.students.map(s => ({
                    id: s.student_id,
                    first_name: s.name,
                    last_name: '',
                    admission_no: s.admission_no
                }));
            } catch (error) {
                console.error(error);
            }
        };

        const generateReport = async () => {
            if (!filters.value.academic_year_id) {
                toastStore.error('Academic Session is required.');
                return;
            }
            generating.value = true;
            loading.value = true;
            try {
                const response = await window.axios.get('/api/fee-reports', {
                    params: filters.value
                });
                reportData.value = response.data.data;
                hasGenerated.value = true;
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to generate fees statement report.');
            } finally {
                generating.value = false;
                loading.value = false;
            }
        };

        const getReportTitle = () => {
            switch (filters.value.report_type) {
                case 'collection': return 'Collections Register (Detailed)';
                case 'pending': return 'Outstanding Balances (Pending Dues)';
                case 'installment_wise': return 'Installment-wise Collections Summary';
                case 'class_wise': return 'Class-wise Collections Summary';
                case 'student_wise': return 'Student-wise Collections Summary';
                case 'daily': return 'Daily Timeline Collection Log';
                case 'monthly': return 'Monthly Timeline Collection Log';
                case 'discount': return 'Discount Waivers Statement Log';
                case 'fine': return 'Late Payment Fines Statement Log';
                default: return 'Fees Report Statement';
            }
        };

        const getSessionTitle = () => {
            const year = academicYears.value.find(y => y.id === filters.value.academic_year_id);
            return year ? year.title : 'N/A';
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

        const formatMonth = (monthString) => {
            if (!monthString) return 'N/A';
            const [year, month] = monthString.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString(undefined, { year: 'numeric', month: 'long' });
        };

        const printReport = () => {
            const printContent = document.getElementById('print-report-area').innerHTML;
            const rt = filters.value.report_type;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>${getReportTitle()} - ${getSessionTitle()}</title>
                        <style>
                            body { font-family: system-ui, -apple-system, sans-serif; color: #1e293b; padding: 24px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th, td { border-bottom: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; font-size: 12px; }
                            th { background-color: #f8fafc; font-weight: bold; color: #475569; text-transform: uppercase; font-size: 10px; }
                            .text-right { text-align: right; }
                            .text-center { text-align: center; }
                            .font-mono { font-family: monospace; }
                            .header-container { display: flex; justify-content: space-between; border-bottom: 2px solid #cbd5e1; padding-bottom: 12px; margin-bottom: 20px; }
                            .summary-grid { display: grid; grid-template-cols: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; text-align: center; }
                            .summary-card { border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; background-color: #f8fafc; }
                            .summary-title { font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; }
                            .summary-value { font-size: 16px; font-weight: 850; margin-top: 4px; }
                        </style>
                    </head>
                    <body>
                        <div class="header-container">
                            <div>
                                <h2 style="margin: 0; font-size: 18px; font-weight: 800;">${getReportTitle()}</h2>
                                <p style="margin: 4px 0 0 0; font-size: 11px; color: #64748b;">Academic Session: ${getSessionTitle()} | Range: ${filters.value.start_date || 'Inception'} to ${filters.value.end_date || 'Present'}</p>
                            </div>
                            <div style="text-align: right; font-size: 11px; color: #64748b;">
                                <div>Generated: ${new Date().toLocaleString()}</div>
                                <div style="margin-top: 2px;">ERP SaaS Financial Module</div>
                            </div>
                        </div>

                        ${summaryAggregates.value ? `
                        <div class="summary-grid">
                            <div class="summary-card">
                                <div class="summary-title">Total Collected</div>
                                <div class="summary-value" style="color: #16a34a;">₹${numberFormat(summaryAggregates.value.collected)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Total Discounts</div>
                                <div class="summary-value" style="color: #4f46e5;">₹${numberFormat(summaryAggregates.value.discounts)}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Total Fines Collected</div>
                                <div class="summary-value" style="color: #db2777;">₹${numberFormat(summaryAggregates.value.fines)}</div>
                            </div>
                        </div>
                        ` : ''}

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

        onMounted(async () => {
            await fetchFiltersData();
        });

        return {
            authStore,
            academicYears,
            classes,
            sections,
            studentList,
            reportData,
            loading,
            generating,
            hasGenerated,
            filters,
            showStudentFilter,
            summaryAggregates,
            handleAcademicYearChange,
            handleClassChange,
            handleSectionChange,
            handleReportTypeChange,
            generateReport,
            getReportTitle,
            getSessionTitle,
            printReport,
            numberFormat,
            formatDate,
            formatMonth
        };
    }
}
</script>
