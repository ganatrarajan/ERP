<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Attendance & Holidays</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Track student and staff attendance, manage school holidays, and generate printable PDF reports.</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 flex gap-4 overflow-x-auto pb-1">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="changeTab(tab.id)"
                :class="[
                    'pb-3 text-sm font-semibold border-b-2 transition-all px-1 whitespace-nowrap',
                    currentTab === tab.id 
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' 
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                ]"
            >
                {{ tab.name }}
            </button>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm mb-6">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Modifying attendance or holidays is disabled.
            </div>
        </div>

        <!-- Tab 1: Student Attendance -->
        <div v-if="currentTab === 'mark'" class="space-y-6">
            <!-- Redesigned Inline Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year</label>
                        <select 
                            v-model="filters.academic_year_id" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-850 dark:text-slate-200 font-semibold"
                        >
                            <option value="">Select Year</option>
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current)</span>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Class</label>
                        <select 
                            v-model="filters.class_id" 
                            @change="fetchSections"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                        >
                            <option value="">Select Class</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Section</label>
                        <select 
                            v-model="filters.section_id" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-850 dark:text-slate-200 font-semibold"
                        >
                            <option value="">Select Section</option>
                            <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Date</label>
                        <input 
                            v-model="filters.attendance_date" 
                            v-datepicker
                            type="text" 
                            placeholder="YYYY-MM-DD"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="flex items-center gap-2">
                        <!-- Quick badge details of selected class -->
                        <div v-if="hasLoadedMark" class="text-xs text-slate-550 dark:text-slate-400 font-semibold flex items-center gap-1.5 flex-wrap">
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Class: {{ getClassName(filters.class_id) }}</span>
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Section: {{ getSectionName(filters.section_id) }}</span>
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Date: {{ filters.attendance_date }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            @click="resetTabFilter"
                            type="button"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all cursor-pointer border-none"
                        >
                            Reset
                        </button>
                        <button 
                            @click="applyTabFilter"
                            :disabled="!filters.academic_year_id || !filters.class_id || !filters.section_id || !filters.attendance_date || loadingStudents"
                            type="button"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl transition-all cursor-pointer shadow-lg shadow-indigo-600/10 disabled:opacity-50"
                        >
                            {{ loadingStudents ? 'Loading...' : 'Apply Filter' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Local Student Search input right on the page -->
            <div v-if="hasLoadedMark && students.length > 0" class="flex justify-end">
                <div class="relative max-w-xs w-full">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="localStudentSearch" 
                        type="text" 
                        placeholder="Search student in list..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-805 dark:text-slate-200 font-semibold"
                    />
                </div>
            </div>

            <!-- Holiday Warning Notice -->
            <div v-if="studentHolidayActive" class="p-4 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 rounded-2xl flex items-center gap-3">
                <span class="text-sky-600 dark:text-sky-400 text-lg">ℹ️</span>
                <div>
                    <h4 class="text-sm font-bold text-sky-800 dark:text-sky-300">Holiday Marked for this Date</h4>
                    <p class="text-xs text-sky-600 dark:text-sky-400">Locked: "{{ studentHolidayTitle }}". Student attendance cannot be marked or modified on a school holiday.</p>
                </div>
            </div>
            <!-- Student List Grid -->
            <div v-if="loadingStudents" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="!hasLoadedMark" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Attendance Data Loaded</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">Click the "Filters" button above, select Class/Section/Date, and apply the filter to load the student list.</p>
            </div>

            <div v-else-if="students.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Students Found</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">No active student records matched the selected filters.</p>
            </div>

            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <!-- Mark All Selector -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Marking attendance for {{ filteredStudents.length }} of {{ students.length }} students
                    </div>
                    <div v-if="!studentHolidayActive" class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Mark All As:</span>
                        <button 
                            v-for="status in ['Present', 'Absent', 'Late', 'Half Day', 'Leave']" 
                            :key="status"
                            @click="markAllStudents(status)"
                            type="button"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-850 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                        >
                            {{ status }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60 sticky top-0">
                                <th class="p-4 pl-6">Roll No</th>
                                <th class="p-4">Admission No</th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 pr-6">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                            <tr v-for="student in filteredStudents" :key="student.student_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold">{{ student.roll_no }}</td>
                                <td class="p-4 font-mono text-xs text-slate-550">{{ student.admission_no }}</td>
                                <td class="p-4 font-bold text-slate-800 dark:text-white">
                                    {{ student.first_name }} {{ student.last_name }}
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <template v-if="studentHolidayActive || student.is_holiday">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-extrabold bg-sky-500 border border-sky-600 text-white shadow-lg shadow-sky-500/20">
                                                Holiday (H)
                                            </span>
                                        </template>
                                        <template v-else>
                                            <label 
                                                v-for="status in ['Present', 'Absent', 'Late', 'Half Day', 'Leave']" 
                                                :key="status"
                                                :class="[
                                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border cursor-pointer select-none transition-all active:scale-95',
                                                    !isCurrentYear ? 'opacity-65 cursor-not-allowed pointer-events-none' : '',
                                                    student.attendance_status === status 
                                                        ? getStatusColorClass(status) 
                                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                                                ]"
                                            >
                                                <input 
                                                    type="radio" 
                                                    v-model="student.attendance_status" 
                                                    :value="status" 
                                                    class="hidden"
                                                />
                                                {{ status }}
                                            </label>
                                        </template>
                                    </div>
                                </td>
                                <td class="p-4 pr-6">
                                    <input 
                                        type="text" 
                                        v-model="student.remarks"
                                        :disabled="studentHolidayActive || student.is_holiday || !isCurrentYear"
                                        placeholder="Optional remark" 
                                        class="w-full px-2.5 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-850 dark:text-slate-250 disabled:opacity-50"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!studentHolidayActive && isCurrentYear" class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button 
                        @click="saveAttendance"
                        :disabled="saving"
                        type="button"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 disabled:opacity-50 transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        {{ saving ? 'Saving...' : 'Save Attendance' }}
                    </button>
                </div>
        </div>
    </div>

        <!-- Tab 2: Student Monthly Grid -->
        <div v-if="currentTab === 'monthly'" class="space-y-6">
            <!-- Redesigned Inline Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year</label>
                        <select 
                            v-model="filters.academic_year_id" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                        >
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Class</label>
                        <select 
                            v-model="filters.class_id" 
                            @change="fetchSections"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                        >
                            <option value="">Select Class</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Section</label>
                        <select 
                            v-model="filters.section_id" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-850 dark:text-slate-200 font-semibold"
                        >
                            <option value="">Select Section</option>
                            <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Month</label>
                        <input 
                            v-monthpicker
                            v-model="monthlyFilter.month" 
                            type="text" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold cursor-pointer"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="flex items-center gap-2">
                        <!-- Quick badge details of selected class -->
                        <div v-if="hasLoadedMonthly" class="text-xs text-slate-550 dark:text-slate-400 font-semibold flex items-center gap-1.5 flex-wrap">
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Class: {{ getClassName(filters.class_id) }}</span>
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Section: {{ getSectionName(filters.section_id) }}</span>
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Month: {{ monthlyFilter.month }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            v-if="hasLoadedMonthly && monthlyGrid.length > 0"
                            @click="downloadMonthlyPdf"
                            :disabled="downloadingPdf"
                            type="button"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/10 disabled:opacity-50 transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>{{ downloadingPdf ? 'Generating PDF...' : 'Download PDF' }}</span>
                        </button>
                        <button 
                            @click="resetTabFilter"
                            type="button"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all cursor-pointer border-none"
                        >
                            Reset
                        </button>
                        <button 
                            @click="applyTabFilter"
                            :disabled="!filters.academic_year_id || !filters.class_id || !filters.section_id || !monthlyFilter.month || loadingMonthly"
                            type="button"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl transition-all cursor-pointer shadow-lg shadow-indigo-600/10 disabled:opacity-50"
                        >
                            {{ loadingMonthly ? 'Loading...' : 'Apply Filter' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grid Content -->
            <div v-if="loadingMonthly" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="!hasLoadedMonthly" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Monthly Data Loaded</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">Select Class/Section/Month above and click Apply Filter to load the register.</p>
            </div>

            <div v-else-if="monthlyGrid.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Register Records Found</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">No students found matching filters for this month register.</p>
            </div>

            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <!-- Legend summary bar -->
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-4 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500"></span> Present (P)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-rose-500"></span> Absent (A)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> Late (L)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-orange-400"></span> Half Day (HD)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-indigo-500"></span> Leave (LV)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-sky-500"></span> Holiday (H)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-3 pl-6 sticky left-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 z-10 min-w-[150px]">Student</th>
                                <th 
                                    v-for="day in daysInMonth" 
                                    :key="day" 
                                    :class="[
                                        'p-2 text-center text-[10px] min-w-[28px] border-r border-slate-100 dark:border-slate-800/45',
                                        isDayWeekend(day) ? 'bg-slate-100 dark:bg-slate-850 text-slate-400 dark:text-slate-500' : ''
                                    ]"
                                >
                                    {{ day }}
                                </th>
                                <th class="p-3 pr-6 text-center min-w-[180px] border-l border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40">Stats</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-350">
                            <tr v-for="student in monthlyGrid" :key="student.student_id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                <td class="p-3 pl-6 font-semibold text-slate-800 dark:text-white sticky left-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 z-10 truncate">
                                    {{ student.name }}
                                </td>
                                <td 
                                    v-for="day in daysInMonth" 
                                    :key="day" 
                                    :class="[
                                        'p-1.5 text-center border-r border-slate-100 dark:border-slate-800/40',
                                        isDayWeekend(day) ? 'bg-slate-50/50 dark:bg-slate-800/5 text-slate-400/80' : ''
                                    ]"
                                >
                                    <span 
                                        v-if="student.days[day] !== '-'" 
                                        :class="[
                                            'inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-extrabold',
                                            getBadgeColorClass(student.days[day])
                                        ]"
                                        :title="student.days[day]"
                                    >
                                        {{ student.days[day] === 'Half Day' ? 'HD' : (student.days[day] === 'Holiday' ? 'H' : (student.days[day] === 'Leave' ? 'LV' : student.days[day].charAt(0))) }}
                                    </span>
                                    <span v-else class="text-slate-300 dark:text-slate-700">-</span>
                                </td>
                                <td class="p-3 pr-6 text-center font-semibold text-slate-500 border-l border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40 flex justify-center gap-1.5">
                                    <span class="text-emerald-650 dark:text-emerald-450">{{ student.stats.Present }}P</span>
                                    <span class="text-rose-650 dark:text-rose-450">{{ student.stats.Absent }}A</span>
                                    <span class="text-amber-650 dark:text-amber-450">{{ student.stats.Late }}L</span>
                                    <span class="text-indigo-650 dark:text-indigo-455">{{ student.stats.Leave }}Lv</span>
                                    <span class="text-sky-655 dark:text-sky-455">{{ student.stats.Holiday || 0 }}H</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Staff Attendance -->
        <div v-if="currentTab === 'staff'" class="space-y-6">
            <!-- Redesigned Inline Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Date</label>
                        <input 
                            v-model="filters.attendance_date" 
                            v-datepicker
                            type="text" 
                            placeholder="YYYY-MM-DD"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-semibold"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="flex items-center gap-2">
                        <!-- Quick badge details -->
                        <div v-if="hasLoadedStaff" class="text-xs text-slate-550 dark:text-slate-400 font-semibold flex items-center gap-1.5 flex-wrap">
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Date: {{ filters.attendance_date }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            @click="resetTabFilter"
                            type="button"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all cursor-pointer border-none"
                        >
                            Reset
                        </button>
                        <button 
                            @click="applyTabFilter"
                            :disabled="!filters.attendance_date || loadingStaff"
                            type="button"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl transition-all cursor-pointer shadow-lg shadow-indigo-600/10 disabled:opacity-50"
                        >
                            {{ loadingStaff ? 'Loading...' : 'Apply Filter' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Holiday Warning Notice -->
            <div v-if="staffHolidayActive" class="p-4 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 rounded-2xl flex items-center gap-3">
                <span class="text-sky-600 dark:text-sky-400 text-lg">ℹ️</span>
                <div>
                    <h4 class="text-sm font-bold text-sky-800 dark:text-sky-300">Holiday Marked for this Date</h4>
                    <p class="text-xs text-sky-600 dark:text-sky-400">Locked: "{{ staffHolidayTitle }}". Staff attendance cannot be modified on school holiday dates.</p>
                </div>
            </div>

            <!-- Staff List Grid -->
            <div v-if="loadingStaff" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="!hasLoadedStaff" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Staff Attendance Data Loaded</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">Select Date above and click Apply Filter to load the staff list.</p>
            </div>

            <div v-else-if="staffs.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Staff Found</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">No active staff records found in the school system.</p>
            </div>

            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <!-- Mark All Selector -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Marking attendance for {{ staffs.length }} staff members
                    </div>
                    <div v-if="!staffHolidayActive" class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Mark All As:</span>
                        <button 
                            v-for="status in ['Present', 'Absent', 'Late', 'Half Day', 'Leave']" 
                            :key="status"
                            @click="markAllStaff(status)"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-slate-700 dark:text-slate-300 transition-colors"
                        >
                            {{ status }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Staff Member</th>
                                <th class="p-4">Email</th>
                                <th class="p-4">Mobile</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 pr-6">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                            <tr v-for="member in staffs" :key="member.user_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                    {{ member.name }}
                                </td>
                                <td class="p-4 font-mono text-xs text-slate-500">{{ member.email }}</td>
                                <td class="p-4 text-slate-500">{{ member.mobile || '-' }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <template v-if="staffHolidayActive || member.is_holiday">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-extrabold bg-sky-500 border border-sky-600 text-white shadow-lg shadow-sky-500/20">
                                                Holiday (H)
                                            </span>
                                        </template>
                                        <template v-else>
                                            <label 
                                                v-for="status in ['Present', 'Absent', 'Late', 'Half Day', 'Leave']" 
                                                :key="status"
                                                :class="[
                                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border cursor-pointer select-none transition-all active:scale-95',
                                                    member.attendance_status === status 
                                                        ? getStatusColorClass(status) 
                                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
                                                ]"
                                            >
                                                <input 
                                                    type="radio" 
                                                    v-model="member.attendance_status" 
                                                    :value="status" 
                                                    class="hidden"
                                                />
                                                {{ status }}
                                            </label>
                                        </template>
                                    </div>
                                </td>
                                <td class="p-4 pr-6">
                                    <input 
                                        type="text" 
                                        v-model="member.remarks"
                                        :disabled="staffHolidayActive || member.is_holiday"
                                        placeholder="Optional remark" 
                                        class="w-full px-2.5 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!staffHolidayActive" class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button 
                        @click="saveStaffAttendance"
                        :disabled="saving"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 disabled:opacity-50 transition-all flex items-center gap-1.5"
                    >
                        {{ saving ? 'Saving...' : 'Save Staff Attendance' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab 3.5: Staff Monthly Grid -->
        <div v-if="currentTab === 'staff_monthly'" class="space-y-6">
            <!-- Redesigned Inline Filter Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Month</label>
                        <input 
                            v-monthpicker
                            v-model="staffMonthlyFilter.month" 
                            type="text" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-955 text-slate-850 dark:text-slate-200 font-semibold cursor-pointer"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="flex items-center gap-2">
                        <!-- Quick badge details -->
                        <div v-if="hasLoadedStaffMonthly" class="text-xs text-slate-555 dark:text-slate-400 font-semibold flex items-center gap-1.5 flex-wrap">
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Month: {{ staffMonthlyFilter.month }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            v-if="hasLoadedStaffMonthly && staffMonthlyGrid.length > 0"
                            @click="downloadStaffMonthlyPdf"
                            :disabled="downloadingPdf"
                            type="button"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/10 disabled:opacity-50 transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>{{ downloadingPdf ? 'Generating PDF...' : 'Download PDF' }}</span>
                        </button>
                        <button 
                            @click="resetTabFilter"
                            type="button"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all cursor-pointer border-none"
                        >
                            Reset
                        </button>
                        <button 
                            @click="applyTabFilter"
                            :disabled="!staffMonthlyFilter.month || loadingStaffMonthly"
                            type="button"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl transition-all cursor-pointer shadow-lg shadow-indigo-600/10 disabled:opacity-50"
                        >
                            {{ loadingStaffMonthly ? 'Loading...' : 'Apply Filter' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grid Content -->
            <div v-if="loadingStaffMonthly" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="!hasLoadedStaffMonthly" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Monthly Data Loaded</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">Select Month above and click Apply Filter to load the register.</p>
            </div>

            <div v-else-if="staffMonthlyGrid.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Register Records Found</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">No staff found matching filters for this month register.</p>
            </div>

            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <!-- Legend summary bar -->
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap gap-4 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500"></span> Present (P)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-rose-500"></span> Absent (A)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> Late (L)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-orange-400"></span> Half Day (HD)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-indigo-500"></span> Leave (LV)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-sky-500"></span> Holiday (H)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-3 pl-6 sticky left-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 z-10 min-w-[150px]">Staff Member</th>
                                <th 
                                    v-for="day in daysInStaffMonth" 
                                    :key="day" 
                                    :class="[
                                        'p-2 text-center text-[10px] min-w-[28px] border-r border-slate-100 dark:border-slate-800/45',
                                        isStaffDayWeekend(day) ? 'bg-slate-100 dark:bg-slate-850 text-slate-400 dark:text-slate-500' : ''
                                    ]"
                                >
                                    {{ day }}
                                </th>
                                <th class="p-3 pr-6 text-center min-w-[180px] border-l border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40">Stats</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-350">
                            <tr v-for="staff in staffMonthlyGrid" :key="staff.user_id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                <td class="p-3 pl-6 font-semibold text-slate-800 dark:text-white sticky left-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 z-10 truncate">
                                    {{ staff.name }}
                                </td>
                                <td 
                                    v-for="day in daysInStaffMonth" 
                                    :key="day" 
                                    :class="[
                                        'p-1.5 text-center border-r border-slate-100 dark:border-slate-800/40',
                                        isStaffDayWeekend(day) ? 'bg-slate-50/50 dark:bg-slate-800/5 text-slate-400/80' : ''
                                    ]"
                                >
                                    <span 
                                        v-if="staff.days[day] !== '-'" 
                                        :class="[
                                            'inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-extrabold',
                                            getBadgeColorClass(staff.days[day])
                                        ]"
                                        :title="staff.days[day]"
                                    >
                                        {{ staff.days[day] === 'Half Day' ? 'HD' : (staff.days[day] === 'Holiday' ? 'H' : (staff.days[day] === 'Leave' ? 'LV' : staff.days[day].charAt(0))) }}
                                    </span>
                                    <span v-else class="text-slate-300 dark:text-slate-700">-</span>
                                </td>
                                <td class="p-3 pr-6 text-center font-semibold text-slate-555 border-l border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40 flex justify-center gap-1.5">
                                    <span class="text-emerald-650 dark:text-emerald-450">{{ staff.stats.Present }}P</span>
                                    <span class="text-rose-650 dark:text-rose-450">{{ staff.stats.Absent }}A</span>
                                    <span class="text-amber-650 dark:text-amber-450">{{ staff.stats.Late }}L</span>
                                    <span class="text-indigo-655 dark:text-indigo-455">{{ staff.stats.Leave }}Lv</span>
                                    <span class="text-sky-655 dark:text-sky-455">{{ staff.stats.Holiday || 0 }}H</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Manage Holidays -->
        <div v-if="currentTab === 'holidays'" class="space-y-6">
            <!-- Filter & Action Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row items-stretch md:items-end justify-between gap-4">
                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-4 flex-1">
                    <div class="w-full md:max-w-xs">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year</label>
                        <select 
                            v-model="filters.academic_year_id" 
                            @change="loadHolidayList"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                        >
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                        </select>
                    </div>

                    <!-- Datatable-style Search Input -->
                    <div class="w-full md:max-w-xs">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Holiday</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input 
                                v-model="holidaySearchQuery" 
                                type="text" 
                                placeholder="Search by title, target..." 
                                class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-end justify-end">
                    <button 
                        v-if="isCurrentYear"
                        @click="openAddHolidayModal"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5 cursor-pointer border-none"
                    >
                        Add School Holiday
                    </button>
                </div>
            </div>

            <!-- Holiday list table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="p-4 pl-6">Holiday Title</th>
                                <th class="p-4">Date</th>
                                <th class="p-4">Target Audience</th>
                                <th class="p-4">Detailed Target</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 pr-6 text-right" v-if="isCurrentYear">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                            <tr v-for="holiday in paginatedHolidays" :key="holiday.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                <td class="p-4 pl-6">
                                    <div class="font-semibold text-slate-800 dark:text-white">{{ holiday.title }}</div>
                                    <div class="text-xs text-slate-400 max-w-[250px] truncate" v-if="holiday.description">{{ holiday.description }}</div>
                                </td>
                                <td class="p-4 font-semibold text-slate-700 dark:text-slate-305">{{ holiday.holiday_date }}</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ holiday.target_type === 'all' ? 'All Students & Staff' : (holiday.target_type === 'staff' ? 'Staff Only' : (holiday.target_type === 'students' ? 'All Students Only' : holiday.target_type)) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div v-if="holiday.target_type === 'class' && holiday.class" class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        Class: {{ holiday.class.name }}
                                    </div>
                                    <div v-else-if="holiday.target_type === 'section' && holiday.section" class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        Class: {{ holiday.class?.name }} - Sec: {{ holiday.section.name }}
                                    </div>
                                    <div v-else-if="holiday.target_type === 'student' && holiday.student" class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                        Student: {{ holiday.student.first_name }} {{ holiday.student.last_name }} ({{ holiday.student.admission_no }})
                                    </div>
                                    <div v-else class="text-xs text-slate-400">-</div>
                                </td>
                                <td class="p-4 text-center">
                                    <button 
                                        :disabled="!isCurrentYear"
                                        @click="toggleHolidayStatus(holiday)"
                                        :class="[
                                            'px-2 py-0.5 rounded text-xs font-semibold uppercase active:scale-95 transition-all',
                                            !isCurrentYear ? 'opacity-60 cursor-not-allowed pointer-events-none' : '',
                                            holiday.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                        ]"
                                    >
                                        {{ holiday.status }}
                                    </button>
                                </td>
                                <td class="p-4 pr-6 text-right" v-if="isCurrentYear">
                                    <button 
                                        @click="deleteHoliday(holiday.id)"
                                        class="px-2 py-1 text-xs font-bold text-rose-500 hover:text-rose-600 active:scale-95 rounded-lg border border-transparent hover:border-rose-100 dark:hover:border-rose-950 transition-colors"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="processedHolidays.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-400 italic">No school holidays defined for this session matching search query.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden bg-white dark:bg-slate-900 rounded-b-2xl">
                    <div class="flex items-center gap-4">
                        <!-- Page Size Selector -->
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-semibold">
                            <span>Show</span>
                            <select 
                                v-model="holidayPerPage" 
                                class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold focus:outline-none text-[11px]"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            <span>entries</span>
                        </div>

                        <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                            Showing {{ holidayFrom }} to {{ holidayTo }} of {{ holidayTotalEntries }} entries
                        </span>
                    </div>

                    <div v-if="holidayTotalPages > 1" class="flex items-center gap-1">
                        <!-- Previous -->
                        <button 
                            :disabled="holidayCurrentPage === 1" 
                            @click="holidayCurrentPage--"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 transition-all bg-slate-50 dark:bg-slate-800 text-slate-707 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 cursor-pointer"
                        >
                            Previous
                        </button>

                        <!-- Numeric Pages Loop -->
                        <button 
                            v-for="page in holidayPageNumbers" 
                            :key="page"
                            @click="holidayCurrentPage = page"
                            :class="[
                                'px-2.5 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer',
                                holidayCurrentPage === page 
                                    ? 'bg-indigo-600 border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500 text-white shadow-sm shadow-indigo-600/20' 
                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-707 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/80'
                            ]"
                        >
                            {{ page }}
                        </button>

                        <!-- Next -->
                        <button 
                            :disabled="holidayCurrentPage === holidayTotalPages" 
                            @click="holidayCurrentPage++"
                            class="px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 transition-all bg-slate-50 dark:bg-slate-800 text-slate-707 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 cursor-pointer"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4.5: Weekend Settings -->
        <div v-if="currentTab === 'weekend_settings'" class="space-y-6">
            <!-- School Wide Saturday Toggle Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Saturday Weekend Holiday</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    By default, Sundays are always holidays across the entire application. Configure how Saturday holidays should behave.
                </p>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            :checked="isSchoolWideSaturdayHoliday" 
                            @change="toggleSchoolWideSaturdayHoliday"
                            class="sr-only peer"
                            :disabled="savingWeekend || !isCurrentYear"
                        >
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                        <span class="ml-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Set Saturday as a School-Wide Holiday (applies to all classes, sections, and staff)
                        </span>
                    </label>
                </div>
            </div>

            <!-- Class & Section Wise Settings Card -->
            <div v-if="!isSchoolWideSaturdayHoliday" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Setting Form -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm h-fit">
                    <h4 class="text-base font-bold text-slate-800 dark:text-white mb-4">Add Class / Section Holiday</h4>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="weekendForm.class_id" 
                                @change="fetchWeekendSections"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section (Optional)</label>
                            <select 
                                v-model="weekendForm.section_id" 
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                :disabled="!weekendForm.class_id"
                            >
                                <option value="">All Sections</option>
                                <option v-for="sec in weekendSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                            </select>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Leave empty to apply to all sections of the selected class.</p>
                        </div>

                        <button 
                            @click="saveClassSectionWeekendHoliday"
                            :disabled="!weekendForm.class_id || savingWeekend || !isCurrentYear"
                            class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/10 disabled:opacity-50 transition-all"
                        >
                            {{ savingWeekend ? 'Saving...' : 'Add Saturday Holiday' }}
                        </button>
                    </div>
                </div>

                <!-- Settings List Table -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm lg:col-span-2">
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800">
                        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Configured Saturday Class/Section Holidays</h4>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                    <th class="p-4 pl-6">Class</th>
                                    <th class="p-4">Section</th>
                                    <th class="p-4">Day</th>
                                    <th class="p-4 pr-6 text-right" v-if="isCurrentYear">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                                <tr v-for="setting in weekendSettings" :key="setting.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                    <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                        {{ setting.class?.name || '-' }}
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ setting.section?.name || 'All Sections' }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-semibold text-slate-600 dark:text-slate-400">{{ setting.day_name }}</td>
                                    <td class="p-4 pr-6 text-right" v-if="isCurrentYear">
                                        <button 
                                            @click="deleteWeekendSetting(setting.id)"
                                            class="px-2 py-1 text-xs font-bold text-rose-500 hover:text-rose-600 active:scale-95 rounded-lg border border-transparent hover:border-rose-100 dark:hover:border-rose-950 transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="weekendSettings.filter(s => s.target_type === 'class_section').length === 0">
                                    <td colspan="4" class="p-8 text-center text-slate-400 italic">No class/section specific Saturday holidays configured. Saturday is a standard working day.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Active school-wide alert -->
            <div v-else class="p-4 bg-emerald-50 dark:bg-emerald-950/25 border border-emerald-200 dark:border-emerald-900/40 rounded-2xl flex items-center gap-3">
                <span class="text-emerald-600 dark:text-emerald-400 text-lg">✅</span>
                <div>
                    <h4 class="text-sm font-bold text-emerald-800 dark:text-emerald-300">School-Wide Saturday Holiday Active</h4>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400">All classes, sections, and staff members are currently configured to have Saturday as a holiday.</p>
                </div>
            </div>
        </div>

        <!-- Tab 5: Reports -->
        <div v-if="currentTab === 'reports'" class="space-y-6">
            <!-- Reports Type Picker -->
            <div class="flex gap-4">
                <button 
                    @click="reportType = 'class'"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold border transition-all',
                        reportType === 'class' 
                            ? 'bg-indigo-600 text-white border-transparent shadow-lg shadow-indigo-600/10' 
                            : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 hover:bg-slate-50'
                    ]"
                >
                    Class Attendance Report
                </button>
                <button 
                    @click="reportType = 'student'"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold border transition-all',
                        reportType === 'student' 
                            ? 'bg-indigo-600 text-white border-transparent shadow-lg shadow-indigo-600/10' 
                            : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 hover:bg-slate-50'
                    ]"
                >
                    Student Attendance Report
                </button>
                <button 
                    @click="reportType = 'staff'"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold border transition-all',
                        reportType === 'staff' 
                            ? 'bg-indigo-600 text-white border-transparent shadow-lg shadow-indigo-600/10' 
                            : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 hover:bg-slate-50'
                    ]"
                >
                    Staff Attendance Report
                </button>
            </div>

            <!-- Class Report Filters -->
            <div v-if="reportType === 'class'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year</label>
                        <select 
                            v-model="classReportFilters.academic_year_id" 
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-850 dark:text-slate-200 font-semibold"
                        >
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Start Date</label>
                        <input 
                            v-model="classReportFilters.start_date" 
                            v-datepicker
                            type="text" 
                            placeholder="YYYY-MM-DD"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 font-semibold"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">End Date</label>
                        <input 
                            v-model="classReportFilters.end_date" 
                            v-datepicker
                            type="text" 
                            placeholder="YYYY-MM-DD"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-955 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-805 dark:text-slate-200 font-semibold"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="flex items-center gap-2">
                        <!-- Quick badge details -->
                        <div v-if="hasLoadedClassReport" class="text-xs text-slate-550 dark:text-slate-400 font-semibold flex items-center gap-1.5 flex-wrap">
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">Start: {{ classReportFilters.start_date }}</span>
                            <span class="bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-lg">End: {{ classReportFilters.end_date }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            @click="resetTabFilter"
                            type="button"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all cursor-pointer border-none"
                        >
                            Reset
                        </button>
                        <button 
                            @click="applyTabFilter"
                            :disabled="!classReportFilters.academic_year_id || !classReportFilters.start_date || !classReportFilters.end_date || loadingReport"
                            type="button"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl transition-all cursor-pointer shadow-lg shadow-indigo-600/10 disabled:opacity-50"
                        >
                            {{ loadingReport ? 'Generating...' : 'Apply Filter' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student Report Filters -->
            <div v-if="reportType === 'student'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year</label>
                    <select 
                        v-model="studentReportFilters.academic_year_id" 
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Student ID / Roll / Name</label>
                    <div class="relative">
                        <input 
                            v-model="studentSearchQuery" 
                            @input="handleStudentSearch"
                            type="text" 
                            placeholder="Type student name..." 
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        />
                        <!-- Dropdown Suggestion -->
                        <div v-if="studentSuggestions.length > 0" class="absolute z-20 top-full left-0 right-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl mt-1 shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <button 
                                v-for="s in studentSuggestions" 
                                :key="s.id"
                                @click="selectStudent(s)"
                                class="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm text-slate-800 dark:text-slate-200"
                            >
                                {{ s.first_name }} {{ s.last_name }} ({{ s.admission_no }})
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Selected Student</label>
                    <input 
                        type="text" 
                        readonly 
                        :value="selectedStudent ? `${selectedStudent.first_name} ${selectedStudent.last_name}` : 'None'"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-slate-500 focus:outline-none"
                    />
                </div>
                <div class="flex items-end">
                    <button 
                        @click="generateStudentReport"
                        :disabled="!studentReportFilters.academic_year_id || !studentReportFilters.student_id || loadingReport"
                        class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 transition-all disabled:opacity-50"
                    >
                        {{ loadingReport ? 'Generating...' : 'Generate Report' }}
                    </button>
                </div>
            </div>

            <!-- Staff Report Filters -->
            <div v-if="reportType === 'staff'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search Staff Name / Email</label>
                    <div class="relative">
                        <input 
                            v-model="staffSearchQuery" 
                            @input="handleStaffSearch"
                            type="text" 
                            placeholder="Type staff name..." 
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        />
                        <!-- Dropdown Suggestion -->
                        <div v-if="staffSuggestions.length > 0" class="absolute z-20 top-full left-0 right-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl mt-1 shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <button 
                                v-for="s in staffSuggestions" 
                                :key="s.id"
                                @click="selectStaff(s)"
                                class="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm text-slate-800 dark:text-slate-200"
                            >
                                {{ s.name }} ({{ s.email }})
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Selected Staff Member</label>
                    <input 
                        type="text" 
                        readonly 
                        :value="selectedStaff ? selectedStaff.name : 'None'"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-slate-500 focus:outline-none"
                    />
                </div>
                <div class="flex items-end">
                    <button 
                        @click="generateStaffReport"
                        :disabled="!staffReportFilters.user_id || loadingReport"
                        class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 transition-all disabled:opacity-50"
                    >
                        {{ loadingReport ? 'Generating...' : 'Generate Report' }}
                    </button>
                </div>
            </div>

            <!-- Class Report Table -->
            <div v-if="loadingReport && reportType === 'class'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="reportType === 'class' && !hasLoadedClassReport" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Class Report Loaded</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">Select Academic Year, Start Date, and End Date above and click Apply Filter to generate the report.</p>
            </div>

            <div v-else-if="reportType === 'class' && classReport.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-350 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">No Records Found</h3>
                <p class="text-xs text-slate-550 dark:text-slate-450">No class aggregate attendance data matches the filter criteria.</p>
            </div>

            <div v-else-if="reportType === 'class'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm space-y-4">
                <!-- Action bar with Search & Excel Export -->
                <div class="p-4 bg-slate-50/50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Class Attendance Directory</span>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <div class="relative w-full md:w-60">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input 
                                v-model="attendanceLocalSearch"
                                type="text"
                                placeholder="Filter class list..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <button 
                            @click="exportAttendanceExcel"
                            type="button"
                            class="px-3.5 py-1.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1 cursor-pointer border-none"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Excel Export
                        </button>
                    </div>
                </div>

                <table id="attendance-class-table" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Class</th>
                            <th class="p-4">Section</th>
                            <th class="p-4 text-center">Total Records</th>
                            <th class="p-4 text-center">Presents</th>
                            <th class="p-4 text-center">Absents</th>
                            <th class="p-4 text-center">Leaves</th>
                            <th class="p-4 pr-6 text-right">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="row in filteredClassReport" :key="row.section_id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/25">
                            <td class="p-4 pl-6 font-semibold">{{ row.class_name }}</td>
                            <td class="p-4 font-semibold">{{ row.section_name }}</td>
                            <td class="p-4 text-center">{{ row.total_records }}</td>
                            <td class="p-4 text-center text-emerald-600 font-semibold">{{ row.presents }}</td>
                            <td class="p-4 text-center text-rose-600 font-semibold">{{ row.absents }}</td>
                            <td class="p-4 text-center text-indigo-600 font-semibold">{{ row.leaves }}</td>
                            <td class="p-4 pr-6 text-right font-extrabold text-indigo-600 dark:text-indigo-400">
                                {{ row.attendance_rate }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Student Report Results -->
            <div v-if="reportType === 'student' && studentReportData" class="space-y-4">
                <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div>
                        <h4 class="text-base font-bold text-slate-800 dark:text-white">
                            Attendance Log for {{ studentReportData.student.first_name }} {{ studentReportData.student.last_name }}
                        </h4>
                        <p class="text-xs text-slate-505 dark:text-slate-400">Admission No: {{ studentReportData.student.admission_no }}</p>
                    </div>
                    <button 
                        @click="downloadStudentReportPdf"
                        :disabled="downloadingPdf"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-rose-600/10 transition-all flex items-center gap-1.5 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        {{ downloadingPdf ? 'Downloading...' : 'Download PDF' }}
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <!-- Stats Summary Cards -->
                    <div class="col-span-3 grid grid-cols-2 sm:grid-cols-5 gap-4">
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase">Total Days</div>
                            <div class="text-2xl font-extrabold mt-1">{{ studentReportData.stats.total }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase text-emerald-500">Present</div>
                            <div class="text-2xl font-extrabold mt-1 text-emerald-600">{{ studentReportData.stats.Present }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase text-rose-500">Absent</div>
                            <div class="text-2xl font-extrabold mt-1 text-rose-600">{{ studentReportData.stats.Absent }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase text-amber-500">Late</div>
                            <div class="text-2xl font-extrabold mt-1 text-amber-600">{{ studentReportData.stats.Late }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm col-span-2 sm:col-span-1">
                            <div class="text-xs font-bold text-slate-400 uppercase text-indigo-500">Attendance Rate</div>
                            <div class="text-2xl font-extrabold mt-1 text-indigo-600 dark:text-indigo-400">
                                {{ studentReportData.stats.total > 0 ? Math.round(((studentReportData.stats.Present + studentReportData.stats.Late + studentReportData.stats.HalfDay) / studentReportData.stats.total) * 100) : 100 }}%
                            </div>
                        </div>
                    </div>

                    <!-- Logs table -->
                    <div class="col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                    <th class="p-4 pl-6">Date</th>
                                    <th class="p-4">Class</th>
                                    <th class="p-4">Section</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 pr-6">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                                <tr v-for="att in studentReportData.attendances" :key="att.id">
                                    <td class="p-4 pl-6 font-semibold">{{ att.attendance_date }}</td>
                                    <td class="p-4">{{ att.class?.name }}</td>
                                    <td class="p-4">{{ att.section?.name }}</td>
                                    <td class="p-4">
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase', getBadgeColorClass(att.status)]">
                                            {{ att.status }}
                                        </span>
                                    </td>
                                    <td class="p-4 pr-6 text-slate-500 text-xs italic">{{ att.remarks || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Staff Report Results -->
            <div v-if="reportType === 'staff' && staffReportData" class="space-y-4">
                <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div>
                        <h4 class="text-base font-bold text-slate-800 dark:text-white">
                            Attendance Log for {{ staffReportData.staff.name }}
                        </h4>
                        <p class="text-xs text-slate-505 dark:text-slate-400">Email: {{ staffReportData.staff.email }}</p>
                    </div>
                    <button 
                        @click="downloadStaffReportPdf"
                        :disabled="downloadingPdf"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-rose-600/10 transition-all flex items-center gap-1.5 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        {{ downloadingPdf ? 'Downloading...' : 'Download PDF' }}
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <!-- Stats Summary Cards -->
                    <div class="col-span-3 grid grid-cols-2 sm:grid-cols-5 gap-4">
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase">Total Days</div>
                            <div class="text-2xl font-extrabold mt-1">{{ staffReportData.stats.total }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase text-emerald-500">Present</div>
                            <div class="text-2xl font-extrabold mt-1 text-emerald-600">{{ staffReportData.stats.Present }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase text-rose-500">Absent</div>
                            <div class="text-2xl font-extrabold mt-1 text-rose-600">{{ staffReportData.stats.Absent }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm">
                            <div class="text-xs font-bold text-slate-400 uppercase text-amber-500">Late</div>
                            <div class="text-2xl font-extrabold mt-1 text-amber-600">{{ staffReportData.stats.Late }}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl text-center shadow-sm col-span-2 sm:col-span-1">
                            <div class="text-xs font-bold text-slate-400 uppercase text-indigo-500">Attendance Rate</div>
                            <div class="text-2xl font-extrabold mt-1 text-indigo-600 dark:text-indigo-400">
                                {{ staffReportData.stats.total > 0 ? Math.round(((staffReportData.stats.Present + staffReportData.stats.Late + staffReportData.stats.HalfDay) / staffReportData.stats.total) * 100) : 100 }}%
                            </div>
                        </div>
                    </div>

                    <!-- Logs table -->
                    <div class="col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                                    <th class="p-4 pl-6">Date</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 pr-6">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm">
                                <tr v-for="att in staffReportData.attendances" :key="att.id">
                                    <td class="p-4 pl-6 font-semibold">{{ att.attendance_date }}</td>
                                    <td class="p-4">
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase', getBadgeColorClass(att.status)]">
                                            {{ att.status }}
                                        </span>
                                    </td>
                                    <td class="p-4 pr-6 text-slate-500 text-xs italic">{{ att.remarks || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Holiday Modal -->
        <div v-if="showHolidayModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-6 relative overflow-hidden transition-all scale-100">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Add School Holiday</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Holiday Title <span class="text-rose-500">*</span></label>
                        <input 
                            v-model="holidayForm.title" 
                            type="text" 
                            placeholder="e.g. Christmas Day, Summer Vacation" 
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Date <span class="text-rose-500">*</span></label>
                        <input 
                            v-model="holidayForm.holiday_date" 
                            v-datepicker
                            type="text" 
                            placeholder="YYYY-MM-DD"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Target Audience <span class="text-rose-500">*</span></label>
                        <select 
                            v-model="holidayForm.target_type" 
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        >
                            <option value="all">All Students & Staff</option>
                            <option value="staff">Staff Only</option>
                            <option value="class">Class Wise Only</option>
                            <option value="section">Section Wise Only</option>
                            <option value="students">All Students Only</option>
                        </select>
                    </div>

                    <!-- Target Dependencies -->
                    <div v-if="holidayForm.target_type === 'class' || holidayForm.target_type === 'section'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="holidayForm.class_id" 
                                @change="fetchHolidaySections"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>
                        <div v-if="holidayForm.target_type === 'section'">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="holidayForm.section_id" 
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            >
                                <option value="">Select Section</option>
                                <option v-for="sec in holidaySections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="holidayForm.target_type === 'student'">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search & Select Student <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input 
                                v-model="studentSearchQuery" 
                                @input="handleStudentSearch"
                                type="text" 
                                placeholder="Type student name..." 
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                            />
                            <!-- Dropdown Suggestion -->
                            <div v-if="studentSuggestions.length > 0" class="absolute z-20 top-full left-0 right-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl mt-1 shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                <button 
                                    v-for="s in studentSuggestions" 
                                    :key="s.id"
                                    @click="selectHolidayStudent(s)"
                                    class="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm text-slate-805 dark:text-slate-200"
                                >
                                    {{ s.first_name }} {{ s.last_name }} ({{ s.admission_no }})
                                </button>
                            </div>
                        </div>
                        <div v-if="selectedHolidayStudentName" class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                            Selected student: {{ selectedHolidayStudentName }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Description / Notes</label>
                        <textarea 
                            v-model="holidayForm.description" 
                            rows="2"
                            placeholder="Optional additional notes..." 
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button 
                        @click="showHolidayModal = false"
                        class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveHoliday"
                        :disabled="!holidayForm.title || !holidayForm.holiday_date || saving"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/10 disabled:opacity-50 transition-all"
                    >
                        {{ saving ? 'Saving...' : 'Save Holiday' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { exportToExcel } from '../../utils/reportExporter';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect/index.js';
import 'flatpickr/dist/plugins/monthSelect/style.css';

const vDatepicker = {
    mounted(el, binding) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (!inputEl) return;

        const config = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            monthSelectorType: 'dropdown',
            onChange: (selectedDates, dateStr) => {
                inputEl.value = dateStr;
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));
            },
            ...(binding.value || {})
        };
        inputEl._flatpickr = flatpickr(inputEl, config);
    },
    updated(el) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (inputEl && inputEl._flatpickr) {
            inputEl._flatpickr.setDate(inputEl.value, false);
        }
    },
    unmounted(el) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (inputEl && inputEl._flatpickr) {
            inputEl._flatpickr.destroy();
        }
    }
};

const vMonthpicker = {
    mounted(el, binding) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (!inputEl) return;

        const config = {
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: 'Y-m',
                    altFormat: 'F Y',
                    theme: 'light'
                })
            ],
            onChange: (selectedDates, dateStr) => {
                inputEl.value = dateStr;
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));
            },
            ...(binding.value || {})
        };
        inputEl._flatpickr = flatpickr(inputEl, config);
    },
    updated(el) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (inputEl && inputEl._flatpickr) {
            inputEl._flatpickr.setDate(inputEl.value, false);
        }
    },
    unmounted(el) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (inputEl && inputEl._flatpickr) {
            inputEl._flatpickr.destroy();
        }
    }
};

const authStore = useAuthStore();
const confirmStore = useConfirmStore();
const route = useRoute();
const router = useRouter();

const tabs = [
    { id: 'mark', name: 'Student Attendance' },
    { id: 'monthly', name: 'Student Monthly Grid' },
    { id: 'staff', name: 'Staff Attendance' },
    { id: 'staff_monthly', name: 'Staff Monthly Grid' },
    { id: 'holidays', name: 'Manage Holidays' },
    { id: 'weekend_settings', name: 'Weekend Settings' },
    { id: 'reports', name: 'Reports' }
];

const currentTab = ref('mark');
watch(() => route.query.tab, (newTab) => {
    if (newTab && tabs.some(t => t.id === newTab)) {
        currentTab.value = newTab;
    }
}, { immediate: true });
const reportType = ref('class');

const academicYears = ref([]);
const classes = ref([]);
const sections = ref([]);
const students = ref([]);
const staffs = ref([]);
const holidays = ref([]);
const holidaySearchQuery = ref('');
const holidayCurrentPage = ref(1);
const holidayPerPage = ref(10);

const processedHolidays = computed(() => {
    let list = [...holidays.value];
    
    // Sort descending by date
    list.sort((a, b) => {
        if (a.holiday_date < b.holiday_date) return 1;
        if (a.holiday_date > b.holiday_date) return -1;
        return b.id - a.id;
    });

    // Search filter
    if (holidaySearchQuery.value) {
        const query = holidaySearchQuery.value.toLowerCase();
        list = list.filter(h => {
            const titleMatch = h.title?.toLowerCase().includes(query);
            const descMatch = h.description?.toLowerCase().includes(query);
            const audienceMatch = (h.target_type === 'all' ? 'all students & staff' : (h.target_type === 'staff' ? 'staff only' : h.target_type))?.toLowerCase().includes(query);
            return titleMatch || descMatch || audienceMatch;
        });
    }

    return list;
});

const holidayTotalEntries = computed(() => processedHolidays.value.length);

const holidayTotalPages = computed(() => {
    return Math.ceil(holidayTotalEntries.value / holidayPerPage.value) || 1;
});

const holidayFrom = computed(() => {
    if (holidayTotalEntries.value === 0) return 0;
    return (holidayCurrentPage.value - 1) * holidayPerPage.value + 1;
});

const holidayTo = computed(() => {
    const toVal = holidayCurrentPage.value * holidayPerPage.value;
    return toVal > holidayTotalEntries.value ? holidayTotalEntries.value : toVal;
});

const holidayPageNumbers = computed(() => {
    const pages = [];
    for (let i = 1; i <= holidayTotalPages.value; i++) {
        pages.push(i);
    }
    return pages;
});

const paginatedHolidays = computed(() => {
    const start = (holidayCurrentPage.value - 1) * holidayPerPage.value;
    const end = start + holidayPerPage.value;
    return processedHolidays.value.slice(start, end);
});

const loadingStudents = ref(false);
const loadingStaff = ref(false);
const loadingMonthly = ref(false);
const loadingReport = ref(false);
const saving = ref(false);
const downloadingPdf = ref(false);

const studentHolidayActive = ref(false);
const studentHolidayTitle = ref('');
const staffHolidayActive = ref(false);
const staffHolidayTitle = ref('');

// Custom filter & local search state
const localStudentSearch = ref('');
const hasLoadedMark = ref(false);
const hasLoadedMonthly = ref(false);
const hasLoadedStaff = ref(false);
const hasLoadedStaffMonthly = ref(false);
const hasLoadedClassReport = ref(false);

const filters = reactive({
    academic_year_id: '',
    class_id: '',
    section_id: '',
    attendance_date: new Date().toISOString().split('T')[0]
});

watch(holidaySearchQuery, () => {
    holidayCurrentPage.value = 1;
});
watch(holidayPerPage, () => {
    holidayCurrentPage.value = 1;
});
watch(() => filters.academic_year_id, () => {
    holidayCurrentPage.value = 1;
});

const isCurrentYear = computed(() => {
    if (!filters.academic_year_id) return true;
    const selected = academicYears.value.find(y => y.id === parseInt(filters.academic_year_id));
    return selected ? !!selected.is_current : false;
});

// Computed search filter for daily student list
const filteredStudents = computed(() => {
    const q = localStudentSearch.value.trim().toLowerCase();
    if (!q) return students.value;
    return students.value.filter(s => {
        const fullName = `${s.first_name || ''} ${s.last_name || ''}`.trim().toLowerCase();
        return fullName.includes(q) ||
            (s.admission_no && s.admission_no.toLowerCase().includes(q)) ||
            (s.roll_no && s.roll_no.toString().includes(q));
    });
});

// Get class/section name helpers for badge detail info in view
const getClassName = (classId) => {
    if (!classId) return 'N/A';
    const found = classes.value.find(c => c.id === parseInt(classId));
    return found ? found.name : 'N/A';
};

const getSectionName = (sectionId) => {
    if (!sectionId) return 'All';
    const found = sections.value.find(s => s.id === parseInt(sectionId));
    return found ? found.name : 'All';
};

// Filter drawer controls
const applyTabFilter = () => {
    if (currentTab.value === 'mark') {
        loadStudentList();
    } else if (currentTab.value === 'monthly') {
        loadMonthlyAttendance();
    } else if (currentTab.value === 'staff') {
        loadStaffList();
    } else if (currentTab.value === 'staff_monthly') {
        loadStaffMonthlyAttendance();
    } else if (currentTab.value === 'reports') {
        if (reportType.value === 'class') {
            generateClassReport();
        }
    }
};

const resetTabFilter = () => {
    if (currentTab.value === 'mark') {
        filters.class_id = '';
        filters.section_id = '';
        sections.value = [];
        filters.attendance_date = new Date().toISOString().split('T')[0];
        students.value = [];
        hasLoadedMark.value = false;
    } else if (currentTab.value === 'monthly') {
        filters.class_id = '';
        filters.section_id = '';
        sections.value = [];
        monthlyFilter.month = new Date().toISOString().slice(0, 7);
        monthlyGrid.value = [];
        hasLoadedMonthly.value = false;
    } else if (currentTab.value === 'staff') {
        filters.attendance_date = new Date().toISOString().split('T')[0];
        staffs.value = [];
        hasLoadedStaff.value = false;
    } else if (currentTab.value === 'staff_monthly') {
        staffMonthlyFilter.month = new Date().toISOString().slice(0, 7);
        staffMonthlyGrid.value = [];
        hasLoadedStaffMonthly.value = false;
    } else if (currentTab.value === 'reports') {
        if (reportType.value === 'class') {
            classReportFilters.start_date = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            classReportFilters.end_date = new Date().toISOString().split('T')[0];
            classReport.value = [];
            hasLoadedClassReport.value = false;
        } else if (reportType.value === 'student') {
            studentReportFilters.student_id = '';
            studentSearchQuery.value = '';
            studentReportData.value = null;
        } else if (reportType.value === 'staff') {
            staffReportFilters.user_id = '';
            staffSearchQuery.value = '';
            staffReportData.value = null;
        }
    }
};

const monthlyFilter = reactive({
    month: new Date().toISOString().slice(0, 7) // e.g. "2026-06"
});

const monthlyGrid = ref([]);
const daysInMonth = ref(30);

const staffMonthlyFilter = reactive({
    month: new Date().toISOString().slice(0, 7)
});
const staffMonthlyGrid = ref([]);
const daysInStaffMonth = ref(30);
const loadingStaffMonthly = ref(false);

// Reports
const classReportFilters = reactive({
    academic_year_id: '',
    start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0]
});
const classReport = ref([]);
const attendanceLocalSearch = ref('');

const filteredClassReport = computed(() => {
    if (!attendanceLocalSearch.value) return classReport.value;
    const q = attendanceLocalSearch.value.toLowerCase();
    return classReport.value.filter(row => {
        return (row.class_name && row.class_name.toLowerCase().includes(q)) ||
               (row.section_name && row.section_name.toLowerCase().includes(q)) ||
               (row.attendance_rate && row.attendance_rate.toString().includes(q));
    });
});

const exportAttendanceExcel = () => {
    exportToExcel('#attendance-class-table', 'Class_Attendance_Report');
};

const studentReportFilters = reactive({
    academic_year_id: '',
    student_id: ''
});
const studentSearchQuery = ref('');
const studentSuggestions = ref([]);
const selectedStudent = ref(null);
const studentReportData = ref(null);

let studentTimeout = null;

const staffReportFilters = reactive({
    user_id: ''
});
const staffSearchQuery = ref('');
const staffSuggestions = ref([]);
const selectedStaff = ref(null);
const staffReportData = ref(null);

let staffTimeout = null;

// Holiday CRUD
const showHolidayModal = ref(false);
const holidaySections = ref([]);
const selectedHolidayStudentName = ref('');
const holidayForm = reactive({
    academic_year_id: '',
    title: '',
    description: '',
    holiday_date: new Date().toISOString().split('T')[0],
    target_type: 'all',
    class_id: '',
    section_id: '',
    student_id: ''
});

const isDayWeekend = (day) => {
    if (!monthlyFilter.month) return false;
    try {
        const dateStr = `${monthlyFilter.month}-${String(day).padStart(2, '0')}`;
        const d = new Date(dateStr);
        const dayOfWeek = d.getDay(); // 0 is Sunday, 6 is Saturday
        return dayOfWeek === 0 || dayOfWeek === 6;
    } catch (e) {
        return false;
    }
};

const isStaffDayWeekend = (day) => {
    if (!staffMonthlyFilter.month) return false;
    try {
        const dateStr = `${staffMonthlyFilter.month}-${String(day).padStart(2, '0')}`;
        const d = new Date(dateStr);
        const dayOfWeek = d.getDay(); // 0 is Sunday, 6 is Saturday
        return dayOfWeek === 0 || dayOfWeek === 6;
    } catch (e) {
        return false;
    }
};

const changeTab = (tabId) => {
    currentTab.value = tabId;
    router.replace({ query: { ...route.query, tab: tabId } });
    if (tabId === 'holidays') {
        loadHolidayList();
    }
    if (tabId === 'weekend_settings') {
        loadWeekendSettings();
    }
};

const fetchAcademicYears = async () => {
    try {
        const response = await window.axios.get('/api/academic-years');
        academicYears.value = response.data.academic_years;
        const currentYear = academicYears.value.find(y => y.is_current);
        if (currentYear) {
            filters.academic_year_id = currentYear.id;
            classReportFilters.academic_year_id = currentYear.id;
            studentReportFilters.academic_year_id = currentYear.id;
            holidayForm.academic_year_id = currentYear.id;
        }
    } catch (e) {
        window.toastr?.error('Failed to load academic sessions.');
    }
};

const hasInitializedQuery = ref(false);

const fetchClasses = async () => {
    try {
        const response = await window.axios.get('/api/classes', { 
            params: { 
                all: true,
                academic_year_id: filters.academic_year_id
            } 
        });
        classes.value = response.data.classes || response.data;

        // Auto select and load from query string if available
        if (!hasInitializedQuery.value && route.query.class_id) {
            const classId = parseInt(route.query.class_id);
            if (classes.value.some(c => c.id === classId)) {
                filters.class_id = classId;

                // Load sections immediately
                const secResponse = await window.axios.get('/api/sections', { params: { class_id: classId, all: true } });
                sections.value = secResponse.data.sections || secResponse.data;

                if (route.query.section_id) {
                    const sectionId = parseInt(route.query.section_id);
                    if (sections.value.some(s => s.id === sectionId)) {
                        filters.section_id = sectionId;

                        if (route.query.date) {
                            filters.attendance_date = route.query.date;
                        }

                        if (route.query.auto === 'true') {
                            loadStudentList();
                        }
                    }
                }
            }
            hasInitializedQuery.value = true;
        }
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const fetchSections = async () => {
    if (!filters.class_id) {
        sections.value = [];
        filters.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: filters.class_id, all: true } });
        sections.value = response.data.sections || response.data;
        filters.section_id = '';
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const fetchHolidaySections = async () => {
    if (!holidayForm.class_id) {
        holidaySections.value = [];
        holidayForm.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: holidayForm.class_id, all: true } });
        holidaySections.value = response.data.sections || response.data;
        holidayForm.section_id = '';
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const loadStudentList = async () => {
    loadingStudents.value = true;
    hasLoadedMark.value = true;
    studentHolidayActive.value = false;
    studentHolidayTitle.value = '';
    try {
        const response = await window.axios.get('/api/attendances/students', { params: filters });
        students.value = response.data.students;
        if (response.data.is_holiday) {
            studentHolidayActive.value = true;
            studentHolidayTitle.value = response.data.holiday_title || 'General Holiday';
        }
        if (students.value.length === 0) {
            window.toastr?.warning('No active students found in this class & section.');
        }
    } catch (e) {
        window.toastr?.error('Failed to load students.');
    } finally {
        loadingStudents.value = false;
    }
};

const markAllStudents = (status) => {
    if (studentHolidayActive.value) return;
    students.value.forEach(s => s.attendance_status = status);
};

const saveAttendance = async () => {
    if (studentHolidayActive.value) {
        window.toastr?.error('Cannot mark attendance on a holiday.');
        return;
    }
    saving.value = true;
    try {
        const payload = {
            academic_year_id: filters.academic_year_id,
            class_id: filters.class_id,
            section_id: filters.section_id,
            attendance_date: filters.attendance_date,
            students: students.value.map(s => ({
                student_id: s.student_id,
                status: s.attendance_status,
                remarks: s.remarks
            }))
        };
        const response = await window.axios.post('/api/attendances/save', payload);
        window.toastr?.success(response.data.message || 'Attendance saved successfully.');
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to save attendance.';
        window.toastr?.error(msg);
    } finally {
        saving.value = false;
    }
};

// Staff Attendance
const loadStaffList = async () => {
    loadingStaff.value = true;
    hasLoadedStaff.value = true;
    staffHolidayActive.value = false;
    staffHolidayTitle.value = '';
    try {
        const response = await window.axios.get('/api/staff-attendances/load', {
            params: {
                attendance_date: filters.attendance_date
            }
        });
        staffs.value = response.data.staff;
        if (response.data.is_holiday) {
            staffHolidayActive.value = true;
            staffHolidayTitle.value = response.data.holiday_title || 'General Holiday';
        }
        if (staffs.value.length === 0) {
            window.toastr?.warning('No staff members registered in the system.');
        }
    } catch (e) {
        window.toastr?.error('Failed to load staff list.');
    } finally {
        loadingStaff.value = false;
    }
};

const markAllStaff = (status) => {
    if (staffHolidayActive.value) return;
    staffs.value.forEach(s => s.attendance_status = status);
};

const saveStaffAttendance = async () => {
    if (staffHolidayActive.value) {
        window.toastr?.error('Cannot mark staff attendance on a holiday.');
        return;
    }
    saving.value = true;
    try {
        const payload = {
            attendance_date: filters.attendance_date,
            staff: staffs.value.map(s => ({
                user_id: s.user_id,
                status: s.attendance_status,
                remarks: s.remarks
            }))
        };
        const response = await window.axios.post('/api/staff-attendances/save', payload);
        window.toastr?.success(response.data.message || 'Staff attendance saved successfully.');
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to save staff attendance.';
        window.toastr?.error(msg);
    } finally {
        saving.value = false;
    }
};

// Monthly Grid
const loadMonthlyAttendance = async () => {
    loadingMonthly.value = true;
    hasLoadedMonthly.value = true;
    try {
        const response = await window.axios.get('/api/attendances/monthly', {
            params: {
                academic_year_id: filters.academic_year_id,
                class_id: filters.class_id,
                section_id: filters.section_id,
                month: monthlyFilter.month
            }
        });
        monthlyGrid.value = response.data.matrix;
        daysInMonth.value = response.data.days_in_month;
    } catch (e) {
        window.toastr?.error('Failed to load monthly attendance grid.');
    } finally {
        loadingMonthly.value = false;
    }
};

const downloadMonthlyPdf = async () => {
    downloadingPdf.value = true;
    try {
        const response = await window.axios.get('/api/attendances/pdf', {
            params: {
                academic_year_id: filters.academic_year_id,
                class_id: filters.class_id,
                section_id: filters.section_id,
                month: monthlyFilter.month
            },
            responseType: 'blob'
        });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const fileURL = window.URL.createObjectURL(blob);
        window.open(fileURL, '_blank');
        window.toastr?.success('PDF report generated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to generate PDF.');
    } finally {
        downloadingPdf.value = false;
    }
};

// Staff Monthly Grid
const loadStaffMonthlyAttendance = async () => {
    loadingStaffMonthly.value = true;
    hasLoadedStaffMonthly.value = true;
    try {
        const response = await window.axios.get('/api/staff-attendances/monthly', {
            params: {
                month: staffMonthlyFilter.month
            }
        });
        staffMonthlyGrid.value = response.data.matrix;
        daysInStaffMonth.value = response.data.days_in_month;
    } catch (e) {
        window.toastr?.error('Failed to load staff monthly attendance grid.');
    } finally {
        loadingStaffMonthly.value = false;
    }
};

const downloadStaffMonthlyPdf = async () => {
    downloadingPdf.value = true;
    try {
        const response = await window.axios.get('/api/staff-attendances/monthly/pdf', {
            params: {
                month: staffMonthlyFilter.month
            },
            responseType: 'blob'
        });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const fileURL = window.URL.createObjectURL(blob);
        window.open(fileURL, '_blank');
        window.toastr?.success('PDF report generated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to generate PDF.');
    } finally {
        downloadingPdf.value = false;
    }
};

// Holidays Management
const loadHolidayList = async () => {
    if (!filters.academic_year_id) return;
    try {
        const response = await window.axios.get('/api/holidays', {
            params: { academic_year_id: filters.academic_year_id }
        });
        holidays.value = response.data.holidays;
    } catch (e) {
        window.toastr?.error('Failed to load holidays list.');
    }
};

const openAddHolidayModal = () => {
    holidayForm.academic_year_id = filters.academic_year_id || (academicYears.value.find(y => y.is_current)?.id || '');
    holidayForm.title = '';
    holidayForm.description = '';
    holidayForm.holiday_date = new Date().toISOString().split('T')[0];
    holidayForm.target_type = 'all';
    holidayForm.class_id = '';
    holidayForm.section_id = '';
    holidayForm.student_id = '';
    selectedHolidayStudentName.value = '';
    showHolidayModal.value = true;
};

const selectHolidayStudent = (student) => {
    holidayForm.student_id = student.id;
    selectedHolidayStudentName.value = `${student.first_name} ${student.last_name}`;
    studentSearchQuery.value = '';
    studentSuggestions.value = [];
};

const saveHoliday = async () => {
    saving.value = true;
    try {
        const response = await window.axios.post('/api/holidays', holidayForm);
        window.toastr?.success(response.data.message || 'Holiday created successfully.');
        showHolidayModal.value = false;
        loadHolidayList();
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to create holiday.';
        window.toastr?.error(msg);
    } finally {
        saving.value = false;
    }
};

const toggleHolidayStatus = async (holiday) => {
    try {
        const newStatus = holiday.status === 'active' ? 'inactive' : 'active';
        const response = await window.axios.put(`/api/holidays/${holiday.id}`, {
            title: holiday.title,
            description: holiday.description,
            status: newStatus
        });
        window.toastr?.success(response.data.message || 'Holiday updated.');
        loadHolidayList();
    } catch (e) {
        window.toastr?.error('Failed to update holiday status.');
    }
};

const deleteHoliday = async (id) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Holiday',
        message: 'Are you sure you want to delete this holiday? Associated attendance records will be updated.',
        type: 'danger',
        confirmText: 'Confirm Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/holidays/${id}`);
            window.toastr?.success('Holiday deleted successfully.');
            loadHolidayList();
        } catch (e) {
            window.toastr?.error('Failed to delete holiday.');
        }
    }
};

// Reports
const generateClassReport = async () => {
    loadingReport.value = true;
    hasLoadedClassReport.value = true;
    try {
        const response = await window.axios.get('/api/attendances/class-report', { params: classReportFilters });
        classReport.value = response.data.report;
    } catch (e) {
        window.toastr?.error('Failed to generate class report.');
    } finally {
        loadingReport.value = false;
    }
};

const handleStudentSearch = () => {
    clearTimeout(studentTimeout);
    if (!studentSearchQuery.value) {
        studentSuggestions.value = [];
        return;
    }
    studentTimeout = setTimeout(async () => {
        try {
            const response = await window.axios.get('/api/students', {
                params: { search: studentSearchQuery.value, per_page: 5 }
            });
            studentSuggestions.value = response.data.data;
        } catch (e) {
            // fail silently
        }
    }, 300);
};

const selectStudent = (student) => {
    selectedStudent.value = student;
    studentReportFilters.student_id = student.id;
    studentSearchQuery.value = '';
    studentSuggestions.value = [];
};

const generateStudentReport = async () => {
    loadingReport.value = true;
    try {
        const response = await window.axios.get('/api/attendances/student-report', { params: studentReportFilters });
        studentReportData.value = response.data;
    } catch (e) {
        window.toastr?.error('Failed to generate student report.');
    } finally {
        loadingReport.value = false;
    }
};

const handleStaffSearch = () => {
    clearTimeout(staffTimeout);
    if (!staffSearchQuery.value) {
        staffSuggestions.value = [];
        return;
    }
    staffTimeout = setTimeout(async () => {
        try {
            const response = await window.axios.get('/api/users');
            const allUsers = response.data.users || [];
            const query = staffSearchQuery.value.toLowerCase();
            staffSuggestions.value = allUsers.filter(u => 
                (u.name && u.name.toLowerCase().includes(query)) || 
                (u.email && u.email.toLowerCase().includes(query))
            ).slice(0, 5);
        } catch (e) {
            // fail silently
        }
    }, 300);
};

const selectStaff = (staff) => {
    selectedStaff.value = staff;
    staffReportFilters.user_id = staff.id;
    staffSearchQuery.value = '';
    staffSuggestions.value = [];
};

const generateStaffReport = async () => {
    loadingReport.value = true;
    try {
        const response = await window.axios.get('/api/staff-attendances/report', { params: staffReportFilters });
        staffReportData.value = response.data;
    } catch (e) {
        window.toastr?.error('Failed to generate staff report.');
    } finally {
        loadingReport.value = false;
    }
};

const downloadStudentReportPdf = async () => {
    if (!studentReportFilters.student_id) return;
    downloadingPdf.value = true;
    try {
        const response = await window.axios.get('/api/attendances/student-report/pdf', {
            params: studentReportFilters,
            responseType: 'blob'
        });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const fileURL = window.URL.createObjectURL(blob);
        window.open(fileURL, '_blank');
        window.toastr?.success('PDF report generated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to generate PDF.');
    } finally {
        downloadingPdf.value = false;
    }
};

const downloadStaffReportPdf = async () => {
    if (!staffReportFilters.user_id) return;
    downloadingPdf.value = true;
    try {
        const response = await window.axios.get('/api/staff-attendances/report/pdf', {
            params: staffReportFilters,
            responseType: 'blob'
        });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const fileURL = window.URL.createObjectURL(blob);
        window.open(fileURL, '_blank');
        window.toastr?.success('PDF report generated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to generate PDF.');
    } finally {
        downloadingPdf.value = false;
    }
};

// Style Helpers
const getStatusColorClass = (status) => {
    if (status === 'Present') return 'bg-emerald-500 border-emerald-600 text-white shadow-lg shadow-emerald-500/20';
    if (status === 'Absent') return 'bg-rose-500 border-rose-600 text-white shadow-lg shadow-rose-500/20';
    if (status === 'Late') return 'bg-amber-500 border-amber-600 text-white shadow-lg shadow-amber-500/20';
    if (status === 'Half Day') return 'bg-orange-400 border-orange-500 text-white shadow-lg shadow-orange-400/20';
    if (status === 'Leave') return 'bg-indigo-500 border-indigo-600 text-white shadow-lg shadow-indigo-500/20';
    return 'bg-slate-100 border-slate-200 text-slate-600';
};

const getBadgeColorClass = (status) => {
    if (status === 'Present') return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
    if (status === 'Absent') return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20';
    if (status === 'Late') return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
    if (status === 'Half Day') return 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20';
    if (status === 'Leave') return 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20';
    if (status === 'Holiday' || status === 'H') return 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20';
    return 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700';
};

// Weekend Settings State & Methods
const weekendSettings = ref([]);
const weekendSections = ref([]);
const savingWeekend = ref(false);
const weekendForm = reactive({
    class_id: '',
    section_id: ''
});

const isSchoolWideSaturdayHoliday = computed(() => {
    return weekendSettings.value.some(s => s.target_type === 'all' && s.day_name === 'Saturday');
});

const loadWeekendSettings = async () => {
    try {
        const response = await window.axios.get('/api/weekend-settings', {
            params: { academic_year_id: filters.academic_year_id }
        });
        weekendSettings.value = response.data.settings;
    } catch (e) {
        window.toastr?.error('Failed to load weekend settings.');
    }
};

const fetchWeekendSections = async () => {
    if (!weekendForm.class_id) {
        weekendSections.value = [];
        weekendForm.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: weekendForm.class_id, all: true } });
        weekendSections.value = response.data.sections || response.data;
        weekendForm.section_id = '';
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const toggleSchoolWideSaturdayHoliday = async () => {
    savingWeekend.value = true;
    try {
        const isCurrentActive = isSchoolWideSaturdayHoliday.value;
        if (isCurrentActive) {
            const schoolWideSetting = weekendSettings.value.find(s => s.target_type === 'all' && s.day_name === 'Saturday');
            if (schoolWideSetting) {
                await window.axios.delete(`/api/weekend-settings/${schoolWideSetting.id}`);
                window.toastr?.success('School-wide Saturday holiday disabled.');
            }
        } else {
            await window.axios.post('/api/weekend-settings', {
                target_type: 'all'
            });
            window.toastr?.success('School-wide Saturday holiday enabled.');
        }
        await loadWeekendSettings();
    } catch (e) {
        window.toastr?.error('Failed to update Saturday weekend holiday settings.');
    } finally {
        savingWeekend.value = false;
    }
};

const saveClassSectionWeekendHoliday = async () => {
    if (!weekendForm.class_id) return;
    savingWeekend.value = true;
    try {
        const payload = {
            target_type: 'class_section',
            class_id: weekendForm.class_id,
            section_id: weekendForm.section_id ? weekendForm.section_id : null
        };
        const response = await window.axios.post('/api/weekend-settings', payload);
        window.toastr?.success(response.data.message || 'Saturday holiday setting saved.');
        weekendForm.class_id = '';
        weekendForm.section_id = '';
        weekendSections.value = [];
        await loadWeekendSettings();
    } catch (e) {
        const msg = e.response?.data?.message || 'Failed to save weekend settings.';
        window.toastr?.error(msg);
    } finally {
        savingWeekend.value = false;
    }
};

const deleteWeekendSetting = async (id) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Weekend Setting',
        message: 'Are you sure you want to delete this weekend settings holiday? Saturday will become a working day for this class/section.',
        type: 'warning',
        confirmText: 'Confirm Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/weekend-settings/${id}`);
            window.toastr?.success('Weekend holiday setting removed.');
            await loadWeekendSettings();
        } catch (e) {
            window.toastr?.error('Failed to remove weekend holiday setting.');
        }
    }
};

watch(() => filters.academic_year_id, (newYear) => {
    filters.class_id = '';
    filters.section_id = '';
    sections.value = [];
    if (newYear) {
        fetchClasses();
    } else {
        classes.value = [];
    }
});

watch([() => currentTab.value, () => filters.academic_year_id], ([newTab, newYear]) => {
    if (newTab === 'holidays' && newYear) {
        loadHolidayList();
    }
    if (newTab === 'weekend_settings') {
        loadWeekendSettings();
    }
    if (newTab === 'staff' && route.query.auto === 'true') {
        loadStaffList();
    }
}, { immediate: true });

onMounted(() => {
    fetchAcademicYears();
});
</script>
