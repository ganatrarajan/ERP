<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Fee Collection</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Search student accounts, review outstanding installments, calculate overdue fines, and record payments.</p>
            </div>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loadingDues" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Collecting payments or applying waivers is disabled.
            </div>
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

        <div v-if="loadingDues" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl animate-pulse space-y-4">
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
                            <span>Select Student</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Otherwise show fallback placeholder -->
            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-slate-400 shadow-sm flex flex-col justify-center items-center h-48">
                <svg class="w-16 h-16 text-slate-350 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Select a class above or search a student account to start billing or collection.
            </div>
        </div>

        <div v-else-if="!dues.has_assignment" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-rose-500 shadow-sm flex flex-col justify-center items-center h-48">
            <svg class="w-16 h-16 text-rose-350 dark:text-rose-900/40 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Warning: This student has not been assigned a fee structure for the selected academic year. Go to "Student Fee Assignment" first.
        </div>

        <!-- Student Account Details & Dues grid -->
        <div v-else class="space-y-6">
            <!-- Student Header Profile Details Banner -->
            <div v-if="studentProfile" class="grid grid-cols-1 lg:grid-cols-3 gap-4 animate-[fadeIn_0.2s_ease-out]">
                <!-- Student Card -->
                <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-lg shrink-0 uppercase">
                        {{ studentProfile.first_name?.substring(0, 1) }}{{ studentProfile.last_name?.substring(0, 1) }}
                    </div>
                    <div class="space-y-0.5 min-w-0 flex-1">
                        <h2 class="text-sm font-extrabold text-slate-800 dark:text-white truncate">
                            {{ studentProfile.first_name }} {{ studentProfile.last_name }}
                        </h2>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold">
                                Adm: {{ studentProfile.admission_no }}
                            </span>
                            <span v-if="studentProfile.gr_no" class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold">
                                GR No: {{ studentProfile.gr_no }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide">
                            {{ filters.class_id ? classes.find(c => c.id === filters.class_id)?.name : 'Class' }} - {{ filters.section_id ? sections.find(s => s.id === filters.section_id)?.name : 'Section' }}
                        </div>
                    </div>
                </div>

                <!-- KPI & Billing Summary -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="space-y-1 border-r border-slate-100 dark:border-slate-800/80 pr-2 last:border-none">
                        <span class="text-[9px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider block">Total Fees</span>
                        <div class="text-lg font-black text-slate-850 dark:text-white mt-1">₹{{ numberFormat(dues.total_fee) }}</div>
                        <div class="text-[9px] text-slate-400 font-semibold truncate">{{ dues.fee_structure_name }}</div>
                    </div>
                    <div class="space-y-1 border-r border-slate-100 dark:border-slate-800/80 pr-2 last:border-none">
                        <span class="text-[9px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider block">Paid Amount</span>
                        <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1">₹{{ numberFormat(dues.total_paid) }}</div>
                        <div v-if="dues.total_fine > 0" class="text-[9px] text-pink-500 font-bold uppercase">+₹{{ numberFormat(dues.total_fine) }} Fines</div>
                    </div>
                    <div class="space-y-1 border-r border-slate-100 dark:border-slate-800/80 pr-2 last:border-none">
                        <span class="text-[9px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider block">Remaining Dues</span>
                        <div class="text-lg font-black text-rose-600 dark:text-rose-455 mt-1">₹{{ numberFormat(dues.outstanding_balance) }}</div>
                        <span :class="[
                            'inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider border mt-1',
                            dues.outstanding_balance <= 0 
                                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' 
                                : 'bg-rose-500/10 text-rose-600 dark:text-rose-455 border-rose-500/20'
                        ]">
                            {{ dues.outstanding_balance <= 0 ? 'Fully Paid' : 'Pending' }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-[9px] font-bold text-slate-455 dark:text-slate-500 uppercase tracking-wider block">Last Payment</span>
                        <div v-if="lastPayment" class="space-y-0.5 mt-1">
                            <div class="text-xs font-black text-indigo-650 dark:text-indigo-400">₹{{ numberFormat(lastPayment.amount_paid) }}</div>
                            <div class="text-[9px] text-slate-400 font-bold uppercase">{{ formatDate(lastPayment.payment_date) }}</div>
                            <div class="text-[9px] text-slate-400 font-mono font-semibold">Ref: {{ lastPayment.receipt?.receipt_number }}</div>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic mt-1.5">No payments yet</div>
                    </div>
                </div>
            </div>

            <!-- Active Entitled Discounts Banner -->
            <div v-if="availableDiscounts && availableDiscounts.length > 0" class="p-4 bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200/50 dark:border-indigo-800/30 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">Entitled Discounts / Waivers</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            This student has active waivers: 
                            <span v-for="(d, idx) in availableDiscounts" :key="d.id" class="font-bold text-indigo-600 dark:text-indigo-400">
                                {{ d.discount_type === 'percentage' ? d.discount_value + '%' : '₹' + numberFormat(d.discount_value) }} ({{ d.reason || 'Waiver' }}){{ idx < availableDiscounts.length - 1 ? ', ' : '' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Installment Dues Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-850 dark:text-white text-sm uppercase tracking-wider">Installments Schedule</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Installment Name</th>
                                <th class="p-4">Due Date</th>
                                <th class="p-4 text-right">Target Amount</th>
                                <th class="p-4 text-right">Paid</th>
                                <th class="p-4 text-right">Discount</th>
                                <th class="p-4 text-right">Overdue Fine</th>
                                <th class="p-4 text-right">Remaining Dues</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 pr-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                            <tr v-for="inst in dues.installments" :key="inst.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                    {{ inst.name }}
                                </td>
                                <td class="p-4">
                                    <span :class="inst.is_overdue && inst.remaining_due > 0 ? 'text-rose-600 font-bold' : ''">
                                        {{ formatDate(inst.due_date) }}
                                    </span>
                                    <span v-if="inst.is_overdue && inst.remaining_due > 0" class="block text-[10px] text-rose-500 font-semibold uppercase">
                                        Overdue ({{ inst.overdue_days }} days)
                                    </span>
                                </td>
                                <td class="p-4 text-right font-medium">
                                    ₹{{ numberFormat(inst.amount) }}
                                </td>
                                <td class="p-4 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                    ₹{{ numberFormat(inst.paid) }}
                                </td>
                                <td class="p-4 text-right font-medium text-indigo-600 dark:text-indigo-400">
                                    ₹{{ numberFormat(inst.discount) }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="font-medium text-pink-600">₹{{ numberFormat(inst.fine_paid) }}</div>
                                    <div v-if="inst.fine_due > 0" class="text-[10px] text-rose-500 font-bold">₹{{ numberFormat(inst.fine_due) }} Due</div>
                                </td>
                                <td class="p-4 text-right font-black text-rose-600 dark:text-rose-400">
                                    ₹{{ numberFormat(inst.outstanding_balance) }}
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                        inst.status === 'Paid' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : '',
                                        inst.status === 'Partial' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : '',
                                        inst.status === 'Unpaid' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-455 border border-rose-500/20' : ''
                                    ]">
                                        {{ inst.status }}
                                    </span>
                                </td>
                                <td class="p-4 pr-6 text-right">
                                    <button 
                                        v-if="authStore.hasPermission('fee_collection.create') && inst.outstanding_balance > 0 && isCurrentYear"
                                        @click="openCollectModal(inst)"
                                        class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-lg active:scale-95 transition-all shadow-sm"
                                    >
                                        Collect Payment
                                    </button>
                                    <span v-else-if="inst.outstanding_balance > 0" class="text-xs text-slate-400 font-semibold">Locked</span>
                                    <span v-else class="text-xs text-slate-400">Paid</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collection Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md max-h-[90vh] rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">Record Payment: {{ selectedInstallment.name }}</h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="submitCollection" class="p-6 space-y-4 overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-150 rounded-xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Base Installment Dues</span>
                            <div class="text-sm font-black text-slate-800 dark:text-slate-200 mt-1">₹{{ numberFormat(selectedInstallment.remaining_due) }}</div>
                        </div>
                        <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-150 rounded-xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Calculated Fine Dues</span>
                            <div class="text-sm font-black text-rose-600 dark:text-rose-400 mt-1">₹{{ numberFormat(selectedInstallment.fine_due) }}</div>
                        </div>
                    </div>

                    <!-- Amount Paid -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Amount Paid (Excluding Fine)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">₹</span>
                            <input 
                                v-model.number="form.amount_paid" 
                                @input="handleAmountPaidInput"
                                type="number" 
                                step="any"
                                required 
                                min="0"
                                :max="selectedInstallment.remaining_due"
                                class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Discount amount -->
                    <div class="space-y-1">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Discount amount (Waiver)</label>
                            <span class="text-[10px] font-semibold text-slate-450 dark:text-slate-500">Max: ₹{{ numberFormat(selectedInstallment.remaining_due) }}</span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">₹</span>
                            <input 
                                v-model.number="form.discount_amount" 
                                @input="handleDiscountAmountInput"
                                type="number" 
                                step="any"
                                min="0"
                                :max="selectedInstallment.remaining_due"
                                class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Entitled discounts hints/selectors inside modal -->
                    <div v-if="availableDiscounts && availableDiscounts.length > 0" class="space-y-1.5 p-3 bg-indigo-500/5 rounded-xl border border-indigo-500/10">
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Entitled Waivers (Click to Apply)</span>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                v-for="d in availableDiscounts" 
                                :key="d.id"
                                type="button"
                                @click="applyEntitledDiscount(d)"
                                class="px-2.5 py-1 text-[10px] font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg active:scale-95 transition-all shadow-sm flex items-center gap-1 cursor-pointer"
                            >
                                <span>Apply {{ d.discount_type === 'percentage' ? d.discount_value + '%' : '₹' + numberFormat(d.discount_value) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Fine amount -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Fine Paid Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">₹</span>
                            <input 
                                v-model.number="form.fine_amount" 
                                type="number" 
                                step="any"
                                min="0"
                                class="w-full pl-8 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Totals collected -->
                    <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-150/40 dark:border-indigo-500/10 rounded-xl flex justify-between items-center text-xs tracking-wide">
                        <span class="font-bold text-slate-600 dark:text-slate-300 uppercase">Gross Payment Collected:</span>
                        <span class="font-black text-indigo-600 dark:text-indigo-400 text-sm">₹{{ numberFormat(form.amount_paid + form.fine_amount) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Payment Method</label>
                            <select 
                                v-model="form.payment_method" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Ref / Cheque #</label>
                            <input 
                                v-model="form.transaction_reference" 
                                type="text" 
                                placeholder="Ref txn number"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1 col-span-2">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Payment Date</label>
                            <input 
                                v-model="form.payment_date" 
                                v-datepicker
                                type="text" 
                                placeholder="YYYY-MM-DD"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Remarks</label>
                        <textarea 
                            v-model="form.remarks" 
                            rows="2"
                            placeholder="Optional payment notes..."
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                        ></textarea>
                    </div>

                    <div v-if="errors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                        {{ errors }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1"
                        >
                            <span v-if="saving">Collecting...</span>
                            <span v-else>Record Payment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'FeeCollectionIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const route = useRoute();

        const academicYears = ref([]);
        const classes = ref([]);
        const sections = ref([]);
        const studentList = ref([]);
        const selectedStudentId = ref('');
        const dues = ref({});
        const loadingDues = ref(false);
        const availableDiscounts = ref([]);

        // Autocomplete search refs
        const studentSearchQuery = ref('');
        const showSearchDropdown = ref(false);
        const searchingStudents = ref(false);
        const studentProfile = ref(null);
        const lastPayment = ref(null);

        const allClassStudents = ref([]);

        const isCurrentYear = computed(() => {
            const selected = academicYears.value.find(y => y.id === filters.value.academic_year_id);
            return selected ? !!selected.is_current : false;
        });

        const modalOpen = ref(false);
        const selectedInstallment = ref(null);
        const saving = ref(false);
        const errors = ref('');

        const filters = ref({
            academic_year_id: '',
            class_id: '',
            section_id: ''
        });

        const form = ref({
            academic_year_id: '',
            student_id: '',
            installment_id: '',
            amount_paid: 0,
            discount_id: null,
            discount_amount: 0,
            fine_amount: 0,
            payment_date: '',
            payment_method: 'Cash',
            transaction_reference: '',
            remarks: ''
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
            fetchStudentDues();
        };

        const clearSelectedStudent = () => {
            selectedStudentId.value = '';
            studentSearchQuery.value = '';
            dues.value = {};
            studentProfile.value = null;
            lastPayment.value = null;
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

        const fetchStudentDues = async () => {
            if (!selectedStudentId.value) return;
            loadingDues.value = true;
            studentProfile.value = null;
            lastPayment.value = null;
            try {
                const [duesRes, studentRes, historyRes] = await Promise.all([
                    window.axios.get(`/api/fee-collections/dues/${selectedStudentId.value}`, {
                        params: { academic_year_id: filters.value.academic_year_id }
                    }),
                    window.axios.get(`/api/students/${selectedStudentId.value}`),
                    window.axios.get('/api/fee-collections/history', {
                        params: { 
                            student_id: selectedStudentId.value,
                            academic_year_id: filters.value.academic_year_id
                        }
                    })
                ]);
                dues.value = duesRes.data.dues;
                availableDiscounts.value = duesRes.data.available_discounts || [];
                studentProfile.value = studentRes.data.student;
                
                const collections = historyRes.data.collections || [];
                if (collections.length > 0) {
                    lastPayment.value = collections[0];
                }
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load dues details.');
            } finally {
                loadingDues.value = false;
            }
        };

        const openCollectModal = (inst) => {
            errors.value = '';
            selectedInstallment.value = inst;
            form.value = {
                academic_year_id: filters.value.academic_year_id,
                student_id: selectedStudentId.value,
                installment_id: inst.id,
                amount_paid: Number(inst.remaining_due),
                discount_id: null,
                discount_amount: 0,
                fine_amount: Number(inst.fine_due),
                payment_date: new Date().toISOString().substring(0, 10),
                payment_method: 'Cash',
                transaction_reference: '',
                remarks: ''
            };
            modalOpen.value = true;
        };

        const closeModal = () => {
            modalOpen.value = false;
        };

        const submitCollection = async () => {
            saving.value = true;
            errors.value = '';
            try {
                const response = await window.axios.post('/api/fee-collections/collect', form.value);
                toastStore.success('Payment recorded successfully.');
                closeModal();
                fetchStudentDues();

                // Proactively stream PDF copy of receipt
                const receiptId = response.data.collection?.receipt?.id;
                if (receiptId) {
                    window.open(`/api/fee-receipts/${receiptId}/pdf`, '_blank');
                }
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Failed to submit payment.';
            } finally {
                saving.value = false;
            }
        };

        const numberFormat = (val) => {
            return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const applyEntitledDiscount = (d) => {
            let val = 0;
            if (d.discount_type === 'percentage') {
                val = (selectedInstallment.value.amount * Number(d.discount_value)) / 100;
            } else {
                val = Number(d.discount_value);
            }
            val = Math.round(val * 100) / 100;
            const remaining = Number(selectedInstallment.value.remaining_due);
            
            form.value.discount_id = d.id;
            form.value.discount_amount = Math.min(val, remaining);
            form.value.amount_paid = Math.round((remaining - form.value.discount_amount) * 100) / 100;
        };

        const handleAmountPaidInput = () => {
            const remaining = Number(selectedInstallment.value.remaining_due);
            let amt = Number(form.value.amount_paid) || 0;
            if (amt < 0) amt = 0;
            if (amt > remaining) amt = remaining;
            form.value.amount_paid = amt;

            if (form.value.amount_paid + form.value.discount_amount > remaining) {
                form.value.discount_amount = Math.round((remaining - form.value.amount_paid) * 100) / 100;
            }
        };

        const handleDiscountAmountInput = () => {
            form.value.discount_id = null;
            const remaining = Number(selectedInstallment.value.remaining_due);
            let disc = Number(form.value.discount_amount) || 0;
            if (disc < 0) disc = 0;
            if (disc > remaining) disc = remaining;
            form.value.discount_amount = disc;

            if (form.value.amount_paid + form.value.discount_amount > remaining) {
                form.value.amount_paid = Math.round((remaining - form.value.discount_amount) * 100) / 100;
            }
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
            
            if (route.query.student_id) {
                const sId = route.query.student_id;
                try {
                    const response = await window.axios.get(`/api/students/${sId}`);
                    const student = response.data.student;
                    if (student) {
                        const currentYearId = filters.value.academic_year_id;
                        const matchingRecord = student.academic_records.find(
                            r => r.academic_year_id === currentYearId
                        );
                        if (matchingRecord) {
                            filters.value.class_id = matchingRecord.class_id;
                            
                            const secRes = await window.axios.get('/api/sections', {
                                params: { class_id: matchingRecord.class_id }
                            });
                            sections.value = secRes.data.sections;
                            filters.value.section_id = matchingRecord.section_id || '';
                            
                            // Initialize list to make autocomplete text show
                            studentList.value = [{
                                id: student.id,
                                first_name: student.first_name,
                                last_name: student.last_name,
                                admission_no: student.admission_no,
                                section: matchingRecord.section ? matchingRecord.section.name : '',
                                class_name: matchingRecord.class ? matchingRecord.class.name : ''
                            }];
                            
                            selectedStudentId.value = Number(sId);
                            studentSearchQuery.value = `${student.first_name} ${student.last_name} (Adm: ${student.admission_no})`;
                            await fetchStudentDues();
                        }
                    }
                } catch (error) {
                    console.error('Failed to auto-select student from dashboard query:', error);
                }
            }
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
            dues,
            loadingDues,
            availableDiscounts,
            modalOpen,
            selectedInstallment,
            saving,
            errors,
            filters,
            form,
            isCurrentYear,
            studentSearchQuery,
            showSearchDropdown,
            searchingStudents,
            studentProfile,
            lastPayment,
            allClassStudents,
            handleAcademicYearChange,
            handleClassChange,
            handleSectionChange,
            handleStudentSearchInput,
            selectStudent,
            clearSelectedStudent,
            fetchStudents,
            fetchStudentDues,
            openCollectModal,
            applyEntitledDiscount,
            handleAmountPaidInput,
            handleDiscountAmountInput,
            closeModal,
            submitCollection,
            numberFormat,
            formatDate
        };
    }
}
</script>
