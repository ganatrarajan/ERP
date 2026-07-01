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
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5"
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

            <!-- Column selection checkboxes -->
            <div v-if="availableColumns.length > 0" class="pt-4 border-t border-slate-100 dark:border-slate-800/80">
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-2">Display Columns</span>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <label 
                        v-for="col in availableColumns" 
                        :key="col.key" 
                        class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-350 font-semibold cursor-pointer select-none hover:text-indigo-650 dark:hover:text-indigo-400 transition-colors"
                    >
                        <input 
                            type="checkbox" 
                            :value="col.key" 
                            v-model="selectedColumns" 
                            class="rounded border-slate-300 text-indigo-650 h-3.5 w-3.5 focus:ring-indigo-500" 
                        />
                        {{ col.label }}
                    </label>
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
                        <span class="text-[10px] font-bold text-slate-405 dark:text-slate-500 uppercase tracking-wide">{{ summaryAggregates.card1Label }}</span>
                        <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">₹{{ numberFormat(summaryAggregates.card1Value) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-405 dark:text-slate-500 uppercase tracking-wide">{{ summaryAggregates.card2Label }}</span>
                        <h3 class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1">₹{{ numberFormat(summaryAggregates.card2Value) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4"></path></svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-405 dark:text-slate-500 uppercase tracking-wide">{{ summaryAggregates.card3Label }}</span>
                        <h3 class="text-xl font-black mt-1" :class="summaryAggregates.type === 'pending' ? 'text-rose-600 dark:text-rose-400' : 'text-pink-600 dark:text-pink-400'">₹{{ numberFormat(summaryAggregates.card3Value) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="summaryAggregates.type === 'pending' ? 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-pink-50 dark:bg-pink-500/10 text-pink-600 dark:text-pink-400'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Report Grid Table -->
            <div id="print-report-area" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="font-extrabold text-slate-800 dark:text-white text-sm uppercase tracking-wider">Report Output: {{ getReportTitle() }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Academic session: {{ getSessionTitle() }} | Dates: {{ filters.start_date || 'Inception' }} to {{ filters.end_date || 'Present' }}</p>
                    </div>
                    <button 
                        @click="printReport"
                        type="button"
                        class="print:hidden px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-indigo-650 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs transition-all active:scale-95 flex items-center gap-1.5 cursor-pointer ml-auto shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print / Download PDF
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <!-- 1. COLLECTION REGISTER -->
                    <table v-if="filters.report_type === 'collection'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('receipt_number')">Receipt #</th>
                                <th class="p-4" v-if="selectedColumns.includes('student_name')">Student Name</th>
                                <th class="p-4" v-if="selectedColumns.includes('class_name')">Class</th>
                                <th class="p-4" v-if="selectedColumns.includes('installment')">Installment</th>
                                <th class="p-4" v-if="selectedColumns.includes('payment_date')">Date</th>
                                <th class="p-4" v-if="selectedColumns.includes('payment_method')">Method</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('amount_paid')">Amount Paid</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('discount_amount')">Discount</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('fine_amount')">Fine</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No collection records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-mono text-xs" v-if="selectedColumns.includes('receipt_number')">{{ row.receipt_number }}</td>
                                <td class="p-4 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('student_name')">
                                    {{ row.student_name }}
                                    <div class="text-[10px] text-slate-400 font-mono">Adm #: {{ row.admission_no }}</div>
                                </td>
                                <td class="p-4" v-if="selectedColumns.includes('class_name')">{{ row.class_name }}</td>
                                <td class="p-4" v-if="selectedColumns.includes('installment')">{{ row.installment }}</td>
                                <td class="p-4 whitespace-nowrap" v-if="selectedColumns.includes('payment_date')">{{ formatDate(row.payment_date) }}</td>
                                <td class="p-4" v-if="selectedColumns.includes('payment_method')">{{ row.payment_method }}</td>
                                <td class="p-4 text-right font-medium text-emerald-600 dark:text-emerald-400" v-if="selectedColumns.includes('amount_paid')">₹{{ numberFormat(row.amount_paid) }}</td>
                                <td class="p-4 text-right text-indigo-500" v-if="selectedColumns.includes('discount_amount')">₹{{ numberFormat(row.discount_amount) }}</td>
                                <td class="p-4 text-right text-pink-500" v-if="selectedColumns.includes('fine_amount')">₹{{ numberFormat(row.fine_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                                      <!-- 2. PENDING FEES -->
                    <div v-else-if="filters.report_type === 'pending'" class="p-6 space-y-4">
                        <div v-if="reportData.length === 0" class="text-center text-slate-400 py-10 font-bold">
                            No outstanding balance records found.
                        </div>
                        <div v-else class="space-y-4">
                            <!-- Class Level Iteration -->
                            <div v-for="cGroup in groupedPendingData" :key="cGroup.className" class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm bg-slate-50/20 dark:bg-slate-900/10">
                                <!-- Class Accordion Header -->
                                <div 
                                    @click="toggleClass(cGroup.className)" 
                                    class="p-4 bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100/60 dark:hover:bg-slate-800/40 cursor-pointer flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-colors select-none"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded bg-indigo-500"></span>
                                            <h4 class="font-extrabold text-slate-800 dark:text-white uppercase tracking-wider text-sm">Class: {{ cGroup.className }}</h4>
                                        </div>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider">
                                            Total Fee: ₹{{ numberFormat(cGroup.total_fee) }} | Paid: ₹{{ numberFormat(cGroup.total_paid) }} | Waivers: ₹{{ numberFormat(cGroup.total_discount) }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                        <div class="text-right">
                                            <span class="text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase block tracking-wider">Pending Dues</span>
                                            <span class="text-sm font-black text-rose-600 dark:text-rose-400">₹{{ numberFormat(cGroup.outstanding_balance) }}</span>
                                        </div>
                                        <svg 
                                            :class="['w-5 h-5 text-slate-400 transition-transform duration-200', expandedClasses[cGroup.className] ? 'rotate-180' : '']" 
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Class Body (Sections list) -->
                                <div v-show="expandedClasses[cGroup.className]" class="p-4 border-t border-slate-150 dark:border-slate-800/80 space-y-3 bg-white dark:bg-slate-900/20">
                                    <!-- Section Level Iteration -->
                                    <div v-for="sec in cGroup.sections" :key="sec.sectionName" class="border border-slate-150 dark:border-slate-800 rounded-xl overflow-hidden bg-slate-50/10 dark:bg-slate-950/5">
                                        <!-- Section Accordion Header -->
                                        <div 
                                            @click="toggleSection(cGroup.className + '_' + sec.sectionName)"
                                            class="p-3 bg-slate-50/50 dark:bg-slate-950/20 hover:bg-slate-50 dark:hover:bg-slate-950/40 cursor-pointer flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 transition-colors select-none"
                                        >
                                            <div class="space-y-0.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded bg-sky-500"></span>
                                                    <h5 class="font-bold text-slate-700 dark:text-slate-200 text-xs uppercase tracking-wide">Section: {{ sec.sectionName }}</h5>
                                                </div>
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                                                    Total Fee: ₹{{ numberFormat(sec.total_fee) }} | Paid: ₹{{ numberFormat(sec.total_paid) }} | Waivers: ₹{{ numberFormat(sec.total_discount) }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                                                <div class="text-right">
                                                    <span class="text-[9px] font-bold text-slate-400 uppercase block">Pending</span>
                                                    <span class="text-xs font-black text-rose-500">₹{{ numberFormat(sec.outstanding_balance) }}</span>
                                                </div>
                                                <svg 
                                                    :class="['w-4 h-4 text-slate-400 transition-transform duration-200', expandedSections[cGroup.className + '_' + sec.sectionName] ? 'rotate-180' : '']" 
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Section Body (Student List Table) -->
                                        <div v-show="expandedSections[cGroup.className + '_' + sec.sectionName]" class="overflow-x-auto border-t border-slate-150 dark:border-slate-800">
                                            <table class="w-full text-left border-collapse">
                                                <thead>
                                                    <tr class="border-b border-slate-150 dark:border-slate-800 text-[10px] font-bold text-slate-500 uppercase bg-slate-50/40 dark:bg-slate-950/20">
                                                        <th class="p-3 pl-4" v-if="selectedColumns.includes('student_name')">Student Name</th>
                                                        <th class="p-3" v-if="selectedColumns.includes('admission_no')">Adm No</th>
                                                        <th class="p-3 text-right" v-if="selectedColumns.includes('total_fee')">Total Assigned</th>
                                                        <th class="p-3 text-right" v-if="selectedColumns.includes('total_paid')">Paid</th>
                                                        <th class="p-3 text-right" v-if="selectedColumns.includes('total_discount')">Waiver</th>
                                                        <th class="p-3 text-right" v-if="selectedColumns.includes('total_fine')">Fines Paid</th>
                                                        <th class="p-3 text-right text-rose-500 font-bold" v-if="selectedColumns.includes('outstanding_balance')">Outstanding</th>
                                                        <th class="p-3 pr-4 text-right">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-150 dark:divide-slate-800 text-xs text-slate-600 dark:text-slate-350 bg-white dark:bg-slate-900/30">
                                                    <tr v-for="stu in sec.students" :key="stu.admission_no" class="hover:bg-slate-50 dark:hover:bg-slate-800/10 transition-colors">
                                                        <td class="p-3 pl-4 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('student_name')">{{ stu.student_name }}</td>
                                                        <td class="p-3 font-mono text-[11px]" v-if="selectedColumns.includes('admission_no')">{{ stu.admission_no }}</td>
                                                        <td class="p-3 text-right" v-if="selectedColumns.includes('total_fee')">₹{{ numberFormat(stu.total_fee) }}</td>
                                                        <td class="p-3 text-right text-emerald-600 font-medium" v-if="selectedColumns.includes('total_paid')">₹{{ numberFormat(stu.total_paid) }}</td>
                                                        <td class="p-3 text-right text-indigo-500" v-if="selectedColumns.includes('total_discount')">₹{{ numberFormat(stu.total_discount) }}</td>
                                                        <td class="p-3 text-right text-pink-500" v-if="selectedColumns.includes('total_fine')">₹{{ numberFormat(stu.total_fine) }}</td>
                                                        <td class="p-3 text-right text-rose-600 font-black" v-if="selectedColumns.includes('outstanding_balance')">₹{{ numberFormat(stu.outstanding_balance) }}</td>
                                                        <td class="p-3 pr-4 text-right">
                                                            <button 
                                                                v-if="authStore.hasPermission('fee_collection.create')"
                                                                @click="router.push({ path: '/fees/collection', query: { student_id: stu.student_id } })"
                                                                class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] rounded-lg active:scale-95 transition-all shadow-sm cursor-pointer border-none"
                                                            >
                                                                Collect Payment
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                                         <!-- 3. INSTALLMENT WISE -->
                    <table v-else-if="filters.report_type === 'installment_wise'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('installment_name')">Installment Name</th>
                                <th class="p-4" v-if="selectedColumns.includes('due_date')">Due Date</th>
                                <th class="p-4 text-center" v-if="selectedColumns.includes('transactions_count')">Txns Count</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_collected')">Total Collected</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_discount')">Total Discounts</th>
                                <th class="p-4 pr-6 text-right" v-if="selectedColumns.includes('total_fine')">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No installment records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('installment_name')">{{ row.installment_name }}</td>
                                <td class="p-4" v-if="selectedColumns.includes('due_date')">{{ formatDate(row.due_date) }}</td>
                                <td class="p-4 text-center font-mono" v-if="selectedColumns.includes('transactions_count')">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600" v-if="selectedColumns.includes('total_collected')">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500" v-if="selectedColumns.includes('total_discount')">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500" v-if="selectedColumns.includes('total_fine')">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 4. CLASS WISE -->
                    <table v-else-if="filters.report_type === 'class_wise'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('class_name')">Class Name</th>
                                <th class="p-4 text-center" v-if="selectedColumns.includes('transactions_count')">Txns Count</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_collected')">Total Collected</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_discount')">Total Discounts</th>
                                <th class="p-4 pr-6 text-right" v-if="selectedColumns.includes('total_fine')">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No class collections recorded.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('class_name')">{{ row.class_name }}</td>
                                <td class="p-4 text-center font-mono" v-if="selectedColumns.includes('transactions_count')">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600" v-if="selectedColumns.includes('total_collected')">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500" v-if="selectedColumns.includes('total_discount')">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500" v-if="selectedColumns.includes('total_fine')">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 5. STUDENT WISE -->
                    <table v-else-if="filters.report_type === 'student_wise'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('student_name')">Student Name</th>
                                <th class="p-4" v-if="selectedColumns.includes('admission_no')">Admission No</th>
                                <th class="p-4" v-if="selectedColumns.includes('class_name')">Class</th>
                                <th class="p-4 text-center" v-if="selectedColumns.includes('transactions_count')">Txns Count</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_collected')">Total Collected</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_discount')">Total Discounts</th>
                                <th class="p-4 pr-6 text-right" v-if="selectedColumns.includes('total_fine')">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No student collections recorded.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('student_name')">{{ row.student_name }}</td>
                                <td class="p-4 font-mono text-xs" v-if="selectedColumns.includes('admission_no')">{{ row.admission_no }}</td>
                                <td class="p-4" v-if="selectedColumns.includes('class_name')">{{ row.class_name }}</td>
                                <td class="p-4 text-center font-mono" v-if="selectedColumns.includes('transactions_count')">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600" v-if="selectedColumns.includes('total_collected')">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500" v-if="selectedColumns.includes('total_discount')">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500" v-if="selectedColumns.includes('total_fine')">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 6. DAILY LOG -->
                    <table v-else-if="filters.report_type === 'daily'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('date')">Date</th>
                                <th class="p-4 text-center" v-if="selectedColumns.includes('transactions_count')">Txns Count</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_collected')">Total Collected</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_discount')">Total Discounts</th>
                                <th class="p-4 pr-6 text-right" v-if="selectedColumns.includes('total_fine')">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No collections for the range.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-medium" v-if="selectedColumns.includes('date')">{{ formatDate(row.date) }}</td>
                                <td class="p-4 text-center font-mono" v-if="selectedColumns.includes('transactions_count')">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600" v-if="selectedColumns.includes('total_collected')">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500" v-if="selectedColumns.includes('total_discount')">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500" v-if="selectedColumns.includes('total_fine')">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 7. MONTHLY LOG -->
                    <table v-else-if="filters.report_type === 'monthly'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('month')">Month</th>
                                <th class="p-4 text-center" v-if="selectedColumns.includes('transactions_count')">Txns Count</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_collected')">Total Collected</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('total_discount')">Total Discounts</th>
                                <th class="p-4 pr-6 text-right" v-if="selectedColumns.includes('total_fine')">Total Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No collections recorded in this session.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-bold text-slate-800 dark:text-slate-200" v-if="selectedColumns.includes('month')">{{ formatMonth(row.month) }}</td>
                                <td class="p-4 text-center font-mono" v-if="selectedColumns.includes('transactions_count')">{{ row.transactions_count }}</td>
                                <td class="p-4 text-right font-bold text-emerald-600" v-if="selectedColumns.includes('total_collected')">₹{{ numberFormat(row.total_collected) }}</td>
                                <td class="p-4 text-right text-indigo-500" v-if="selectedColumns.includes('total_discount')">₹{{ numberFormat(row.total_discount) }}</td>
                                <td class="p-4 pr-6 text-right text-pink-500" v-if="selectedColumns.includes('total_fine')">₹{{ numberFormat(row.total_fine) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 8. DISCOUNT WAIVERS -->
                    <table v-else-if="filters.report_type === 'discount'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('receipt_number')">Receipt #</th>
                                <th class="p-4" v-if="selectedColumns.includes('student_name')">Student Name</th>
                                <th class="p-4" v-if="selectedColumns.includes('class_name')">Class</th>
                                <th class="p-4" v-if="selectedColumns.includes('installment')">Installment</th>
                                <th class="p-4" v-if="selectedColumns.includes('payment_date')">Date</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('discount_amount')">Discount Amount</th>
                                <th class="p-4 pr-6" v-if="selectedColumns.includes('remarks')">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No discount records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-mono text-xs" v-if="selectedColumns.includes('receipt_number')">{{ row.receipt_number }}</td>
                                <td class="p-4 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('student_name')">
                                    {{ row.student_name }}
                                    <div class="text-[10px] text-slate-400 font-mono">Adm #: {{ row.admission_no }}</div>
                                </td>
                                <td class="p-4" v-if="selectedColumns.includes('class_name')">{{ row.class_name }}</td>
                                <td class="p-4" v-if="selectedColumns.includes('installment')">{{ row.installment }}</td>
                                <td class="p-4 whitespace-nowrap" v-if="selectedColumns.includes('payment_date')">{{ formatDate(row.payment_date) }}</td>
                                <td class="p-4 text-right font-black text-indigo-650 dark:text-indigo-400" v-if="selectedColumns.includes('discount_amount')">₹{{ numberFormat(row.discount_amount) }}</td>
                                <td class="p-4 pr-6 text-slate-500 text-xs" v-if="selectedColumns.includes('remarks')">{{ row.remarks || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- 9. OVERDUE FINES -->
                    <table v-else-if="filters.report_type === 'fine'" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6" v-if="selectedColumns.includes('receipt_number')">Receipt #</th>
                                <th class="p-4" v-if="selectedColumns.includes('student_name')">Student Name</th>
                                <th class="p-4" v-if="selectedColumns.includes('class_name')">Class</th>
                                <th class="p-4" v-if="selectedColumns.includes('installment')">Installment</th>
                                <th class="p-4" v-if="selectedColumns.includes('payment_date')">Date</th>
                                <th class="p-4 text-right" v-if="selectedColumns.includes('fine_amount')">Fine Amount</th>
                                <th class="p-4 pr-6" v-if="selectedColumns.includes('remarks')">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-if="reportData.length === 0">
                                <td colspan="100" class="p-10 text-center text-slate-400">No late fine records found.</td>
                            </tr>
                            <tr v-else v-for="(row, idx) in reportData" :key="idx" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-mono text-xs" v-if="selectedColumns.includes('receipt_number')">{{ row.receipt_number }}</td>
                                <td class="p-4 font-semibold text-slate-800 dark:text-white" v-if="selectedColumns.includes('student_name')">
                                    {{ row.student_name }}
                                    <div class="text-[10px] text-slate-400 font-mono">Adm #: {{ row.admission_no }}</div>
                                </td>
                                <td class="p-4" v-if="selectedColumns.includes('class_name')">{{ row.class_name }}</td>
                                <td class="p-4" v-if="selectedColumns.includes('installment')">{{ row.installment }}</td>
                                <td class="p-4 whitespace-nowrap" v-if="selectedColumns.includes('payment_date')">{{ formatDate(row.payment_date) }}</td>
                                <td class="p-4 text-right font-black text-pink-600 dark:text-pink-400" v-if="selectedColumns.includes('fine_amount')">₹{{ numberFormat(row.fine_amount) }}</td>
                                <td class="p-4 pr-6 text-slate-500 text-xs" v-if="selectedColumns.includes('remarks')">{{ row.remarks || '-' }}</td>
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
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'FeesReportsIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const route = useRoute();
        const router = useRouter();

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

        // Config of columns per report type
        const columnsConfig = {
            collection: [
                { key: 'receipt_number', label: 'Receipt #' },
                { key: 'student_name', label: 'Student Name' },
                { key: 'class_name', label: 'Class' },
                { key: 'installment', label: 'Installment' },
                { key: 'payment_date', label: 'Date' },
                { key: 'payment_method', label: 'Method' },
                { key: 'amount_paid', label: 'Amount Paid' },
                { key: 'discount_amount', label: 'Discount' },
                { key: 'fine_amount', label: 'Fine' }
            ],
            pending: [
                { key: 'student_name', label: 'Student Name' },
                { key: 'admission_no', label: 'Adm No' },
                { key: 'total_fee', label: 'Total Assigned' },
                { key: 'total_paid', label: 'Paid' },
                { key: 'total_discount', label: 'Waiver' },
                { key: 'total_fine', label: 'Fines Paid' },
                { key: 'outstanding_balance', label: 'Outstanding' }
            ],
            installment_wise: [
                { key: 'installment_name', label: 'Installment Name' },
                { key: 'due_date', label: 'Due Date' },
                { key: 'transactions_count', label: 'Txns Count' },
                { key: 'total_collected', label: 'Total Collected' },
                { key: 'total_discount', label: 'Total Discounts' },
                { key: 'total_fine', label: 'Total Fines' }
            ],
            class_wise: [
                { key: 'class_name', label: 'Class Name' },
                { key: 'transactions_count', label: 'Txns Count' },
                { key: 'total_collected', label: 'Total Collected' },
                { key: 'total_discount', label: 'Total Discounts' },
                { key: 'total_fine', label: 'Total Fines' }
            ],
            student_wise: [
                { key: 'student_name', label: 'Student Name' },
                { key: 'admission_no', label: 'Admission No' },
                { key: 'class_name', label: 'Class' },
                { key: 'transactions_count', label: 'Txns Count' },
                { key: 'total_collected', label: 'Total Collected' },
                { key: 'total_discount', label: 'Total Discounts' },
                { key: 'total_fine', label: 'Total Fines' }
            ],
            daily: [
                { key: 'date', label: 'Date' },
                { key: 'transactions_count', label: 'Txns Count' },
                { key: 'total_collected', label: 'Total Collected' },
                { key: 'total_discount', label: 'Total Discounts' },
                { key: 'total_fine', label: 'Total Fines' }
            ],
            monthly: [
                { key: 'month', label: 'Month' },
                { key: 'transactions_count', label: 'Txns Count' },
                { key: 'total_collected', label: 'Total Collected' },
                { key: 'total_discount', label: 'Total Discounts' },
                { key: 'total_fine', label: 'Total Fines' }
            ],
            discount: [
                { key: 'receipt_number', label: 'Receipt #' },
                { key: 'student_name', label: 'Student Name' },
                { key: 'class_name', label: 'Class' },
                { key: 'installment', label: 'Installment' },
                { key: 'payment_date', label: 'Date' },
                { key: 'discount_amount', label: 'Discount Amount' },
                { key: 'remarks', label: 'Remarks' }
            ],
            fine: [
                { key: 'receipt_number', label: 'Receipt #' },
                { key: 'student_name', label: 'Student Name' },
                { key: 'class_name', label: 'Class' },
                { key: 'installment', label: 'Installment' },
                { key: 'payment_date', label: 'Date' },
                { key: 'fine_amount', label: 'Fine Amount' },
                { key: 'remarks', label: 'Remarks' }
            ]
        };

        const selectedColumns = ref([]);

        const availableColumns = computed(() => {
            return columnsConfig[filters.value.report_type] || [];
        });

        // Determine when to show student filter
        const showStudentFilter = computed(() => {
            const rt = filters.value.report_type;
            return ['collection', 'pending', 'discount', 'fine', 'student_wise'].includes(rt);
        });

        // Expanded state tracking for hierarchical pending dues report
        const expandedClasses = ref({});
        const expandedSections = ref({});

        const toggleClass = (className) => {
            expandedClasses.value[className] = !expandedClasses.value[className];
        };

        const toggleSection = (classSectionKey) => {
            expandedSections.value[classSectionKey] = !expandedSections.value[classSectionKey];
        };

        const groupedPendingData = computed(() => {
            if (filters.value.report_type !== 'pending' || reportData.value.length === 0) return [];

            const groups = {};

            reportData.value.forEach(row => {
                const className = row.class_name || 'N/A';
                const sectionName = row.section_name || 'N/A';

                if (!groups[className]) {
                    groups[className] = {
                        className,
                        total_fee: 0,
                        total_paid: 0,
                        total_discount: 0,
                        outstanding_balance: 0,
                        sections: {}
                    };
                }

                const cGroup = groups[className];
                cGroup.total_fee += Number(row.total_fee || 0);
                cGroup.total_paid += Number(row.total_paid || 0);
                cGroup.total_discount += Number(row.total_discount || 0);
                cGroup.outstanding_balance += Number(row.outstanding_balance || 0);

                if (!cGroup.sections[sectionName]) {
                    cGroup.sections[sectionName] = {
                        sectionName,
                        total_fee: 0,
                        total_paid: 0,
                        total_discount: 0,
                        outstanding_balance: 0,
                        students: []
                    };
                }

                const sGroup = cGroup.sections[sectionName];
                sGroup.total_fee += Number(row.total_fee || 0);
                sGroup.total_paid += Number(row.total_paid || 0);
                sGroup.total_discount += Number(row.total_discount || 0);
                sGroup.outstanding_balance += Number(row.outstanding_balance || 0);

                sGroup.students.push(row);
            });

            return Object.values(groups).map(c => {
                return {
                    ...c,
                    sections: Object.values(c.sections)
                };
            });
        });

        // Compute aggregates dynamically from dataset
        const summaryAggregates = computed(() => {
            if (!hasGenerated.value || reportData.value.length === 0) return null;

            let card1Val = 0;
            let card2Val = 0;
            let card3Val = 0;

            const rt = filters.value.report_type;

            if (rt === 'pending') {
                reportData.value.forEach(row => {
                    card1Val += Number(row.total_fee || 0);
                    card2Val += Number(row.total_paid || 0);
                    card3Val += Number(row.outstanding_balance || 0);
                });
                return {
                    type: 'pending',
                    card1Label: 'Total Assigned Fees',
                    card1Value: card1Val,
                    card2Label: 'Total Collected (Paid)',
                    card2Value: card2Val,
                    card3Label: 'Total Outstanding Dues',
                    card3Value: card3Val
                };
            } else if (rt === 'collection') {
                reportData.value.forEach(row => {
                    card1Val += Number(row.amount_paid || 0);
                    card2Val += Number(row.discount_amount || 0);
                    card3Val += Number(row.fine_amount || 0);
                });
                return {
                    type: 'collection',
                    card1Label: 'Total Collected',
                    card1Value: card1Val,
                    card2Label: 'Total Discounts (Waivers)',
                    card2Value: card2Val,
                    card3Label: 'Total Fines Collected',
                    card3Value: card3Val
                };
            } else if (['installment_wise', 'class_wise', 'student_wise', 'daily', 'monthly'].includes(rt)) {
                reportData.value.forEach(row => {
                    card1Val += Number(row.total_collected || 0);
                    card2Val += Number(row.total_discount || 0);
                    card3Val += Number(row.total_fine || 0);
                });
                return {
                    type: 'other',
                    card1Label: 'Total Collected',
                    card1Value: card1Val,
                    card2Label: 'Total Discounts (Waivers)',
                    card2Value: card2Val,
                    card3Label: 'Total Fines Collected',
                    card3Value: card3Val
                };
            } else if (rt === 'discount') {
                reportData.value.forEach(row => {
                    card2Val += Number(row.discount_amount || 0);
                });
                return {
                    type: 'discount',
                    card1Label: 'Total Collected',
                    card1Value: 0,
                    card2Label: 'Total Discounts (Waivers)',
                    card2Value: card2Val,
                    card3Label: 'Total Fines Collected',
                    card3Value: 0
                };
            } else if (rt === 'fine') {
                reportData.value.forEach(row => {
                    card3Val += Number(row.fine_amount || 0);
                });
                return {
                    type: 'fine',
                    card1Label: 'Total Collected',
                    card1Value: 0,
                    card2Label: 'Total Discounts (Waivers)',
                    card2Value: 0,
                    card3Label: 'Total Fines Collected',
                    card3Value: card3Val
                };
            }

            return null;
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
            // Initialize checked columns to all available columns
            selectedColumns.value = availableColumns.value.map(c => c.key);
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
            const params = new URLSearchParams({
                academic_year_id: filters.value.academic_year_id,
                report_type: filters.value.report_type,
                class_id: filters.value.class_id || '',
                section_id: filters.value.section_id || '',
                student_id: filters.value.student_id || '',
                start_date: filters.value.start_date || '',
                end_date: filters.value.end_date || '',
                selected_columns: selectedColumns.value.join(',')
            });
            window.open(`/api/fee-reports/pdf?${params.toString()}`, '_blank');
        };

        onMounted(async () => {
            await fetchFiltersData();
            if (route.query.report_type) {
                filters.value.report_type = route.query.report_type;
            }
            // Auto initialize column options
            selectedColumns.value = availableColumns.value.map(c => c.key);
            
            if (route.query.auto === 'true') {
                await generateReport();
            }
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
            expandedClasses,
            expandedSections,
            toggleClass,
            toggleSection,
            groupedPendingData,
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
            formatMonth,
            router,
            selectedColumns,
            availableColumns
        };
    }
}
</script>
