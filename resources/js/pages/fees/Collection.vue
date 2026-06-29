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
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
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
                    @change="handleFilterChange"
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option value="">All Sections</option>
                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">
                        {{ sec.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Student Account</label>
                <select 
                    v-model="selectedStudentId" 
                    @change="fetchStudentDues"
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none"
                >
                    <option value="" disabled>Choose Student</option>
                    <option v-for="stu in studentList" :key="stu.id" :value="stu.id">
                        {{ stu.first_name }} {{ stu.last_name }} (Adm #: {{ stu.admission_no }}{{ stu.section ? ' - Sec: ' + stu.section : '' }})
                    </option>
                </select>
            </div>
        </div>

        <div v-if="loadingDues" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl animate-pulse space-y-4">
            <div class="h-8 bg-slate-200 dark:bg-slate-800 rounded max-w-sm mx-auto"></div>
            <div class="h-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
        </div>

        <div v-else-if="!selectedStudentId" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-slate-400 shadow-sm flex flex-col justify-center items-center h-48">
            <svg class="w-16 h-16 text-slate-300 dark:text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Select a student account above to start billing or collection.
        </div>

        <div v-else-if="!dues.has_assignment" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-rose-500 shadow-sm flex flex-col justify-center items-center h-48">
            <svg class="w-16 h-16 text-rose-300 dark:text-rose-900/40 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Warning: This student has not been assigned a fee structure for the selected academic year. Go to "Student Fee Assignment" first.
        </div>

        <!-- Student Account Details & Dues grid -->
        <div v-else class="space-y-6">
            <!-- Summary stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Total Dues</span>
                    <h3 class="text-xl font-black text-slate-800 dark:text-white mt-1.5">₹{{ numberFormat(dues.total_fee) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">Total Paid</span>
                    <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-450 mt-1.5">₹{{ numberFormat(dues.total_paid) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">Total Discount</span>
                    <h3 class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1.5">₹{{ numberFormat(dues.total_discount) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">Total Fine Paid</span>
                    <h3 class="text-xl font-black text-pink-600 dark:text-pink-400 mt-1.5">₹{{ numberFormat(dues.total_fine) }}</h3>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm text-center col-span-2 md:col-span-1">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold">Balance Due</span>
                    <h3 class="text-xl font-black text-rose-600 dark:text-rose-450 mt-1.5">₹{{ numberFormat(dues.outstanding_balance) }}</h3>
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
import { ref, onMounted, computed } from 'vue';
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
            selectedStudentId.value = '';
            studentList.value = [];
            dues.value = {};
            await fetchClasses();
        };

        const handleClassChange = async () => {
            filters.value.section_id = '';
            sections.value = [];
            selectedStudentId.value = '';
            studentList.value = [];
            dues.value = {};
            
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
            fetchStudents();
        };

        const handleFilterChange = () => {
            selectedStudentId.value = '';
            studentList.value = [];
            dues.value = {};
            fetchStudents();
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
                // Map the structured results back into basic student selection info
                studentList.value = response.data.students.map(s => ({
                    id: s.student_id,
                    first_name: s.name,
                    last_name: '',
                    admission_no: s.admission_no,
                    section: s.section
                }));
            } catch (error) {
                console.error(error);
            }
        };

        const fetchStudentDues = async () => {
            if (!selectedStudentId.value) return;
            loadingDues.value = true;
            try {
                const response = await window.axios.get(`/api/fee-collections/dues/${selectedStudentId.value}`, {
                    params: { academic_year_id: filters.value.academic_year_id }
                });
                dues.value = response.data.dues;
                availableDiscounts.value = response.data.available_discounts || [];
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
            return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                            
                            await fetchStudents();
                            selectedStudentId.value = Number(sId);
                            await fetchStudentDues();
                        }
                    }
                } catch (error) {
                    console.error('Failed to auto-select student from dashboard query:', error);
                }
            }
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
            handleAcademicYearChange,
            handleClassChange,
            handleFilterChange,
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
