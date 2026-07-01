<template>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6 animate-[fadeIn_0.2s_ease-out]">
        <!-- Page Title & Header Actions -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Academic Teacher Assignments
                </h1>
                <p class="text-xs text-slate-500">Configure academic year class teachers and subject allocations for staff members.</p>
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <router-link
                    to="/assignments/reports"
                    class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-xs rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Assignment Reports
                </router-link>
                
                <button
                    v-if="authStore.hasPermission('user.edit')"
                    @click="openBulkModal"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-750 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/15 transition-all flex items-center justify-center gap-1.5 border-none cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    + Bulk Assign
                </button>
            </div>
        </div>

        <!-- Academic Session Select Hero Banner -->
        <div v-if="!selectedYearId" class="bg-gradient-to-r from-indigo-50/60 to-sky-50/50 dark:from-indigo-950/20 dark:to-slate-900 border border-indigo-100 dark:border-indigo-900/30 rounded-3xl p-8 text-center max-w-2xl mx-auto shadow-sm space-y-4">
            <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-2xl flex items-center justify-center mx-auto text-indigo-600 dark:text-indigo-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div class="space-y-1">
                <h2 class="text-base font-bold text-slate-800 dark:text-white">Select Academic Session</h2>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Please pick an active academic year session first to query and manage teacher allocations.</p>
            </div>
            <div class="max-w-xs mx-auto">
                <select 
                    v-model="selectedYearId"
                    @change="handleYearChange"
                    class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-2xl shadow-sm focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                    <option value="">Select Academic Year...</option>
                    <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">
                        {{ ay.title }} <span v-if="ay.is_current">(Current)</span>
                    </option>
                </select>
            </div>
        </div>

        <div v-else class="space-y-6">
            <!-- Active Session Ribbon & Workspace Filters -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 p-4 rounded-3xl shadow-sm space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-850">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            Active Session Workspace: <span class="text-indigo-600 dark:text-indigo-400 font-extrabold">{{ currentYearTitle }}</span>
                        </span>
                    </div>
                    <button 
                        @click="selectedYearId = ''" 
                        class="text-[10px] font-bold text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 bg-transparent border-none cursor-pointer flex items-center gap-1"
                    >
                        Change Session &larr;
                    </button>
                </div>

                <!-- Advanced Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Teacher search -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Filter Teacher</label>
                        <select v-model="filters.teacher_id" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <option value="">All Teachers...</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>

                    <!-- Class Select -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Filter Class</label>
                        <select v-model="filters.class_id" @change="handleClassFilterChange" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <option value="">All Classes...</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Section Select -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Filter Section</label>
                        <select v-model="filters.section_id" @change="handleSectionFilterChange" :disabled="!filters.class_id" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer disabled:opacity-50">
                            <option value="">All Sections...</option>
                            <option v-for="s in filterSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Subject Select -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Filter Subject</label>
                        <select v-model="filters.subject_id" :disabled="!filters.section_id" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer disabled:opacity-50">
                            <option value="">All Subjects...</option>
                            <option v-for="sub in filterSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1 invisible">Action</label>
                        <button 
                            @click="fetchAssignments" 
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-750 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/15 transition-all flex items-center justify-center gap-1.5 border-none cursor-pointer h-[34px]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Filter
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-1" v-if="hasActiveFilters">
                    <button 
                        @click="resetFilters" 
                        class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline bg-transparent border-none cursor-pointer"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Assignments Spreadsheet Data Grid -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
                <div v-if="loading" class="p-12 text-center text-slate-500 space-y-3">
                    <div class="w-8 h-8 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin mx-auto"></div>
                    <p class="text-xs font-bold text-slate-400">Loading assignments directory...</p>
                </div>

                <div v-else-if="!hasFiltered" class="p-12 text-center text-slate-500 space-y-3 animate-[fadeIn_0.2s_ease-out]">
                    <svg class="w-12 h-12 text-indigo-500/80 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    <p class="text-xs font-bold text-slate-400">Please select filters and click 'Filter' to display assignments.</p>
                </div>

                <div v-else-if="assignments.length === 0" class="p-12 text-center text-slate-500 space-y-3">
                    <svg class="w-12 h-12 text-slate-350 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <p class="text-xs font-bold text-slate-400">No teacher assignments configured for this criteria.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-100 dark:border-slate-850/80 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="p-4 pl-6 w-12">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll" 
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer"
                                    />
                                </th>
                                <th class="p-4">Teacher</th>
                                <th class="p-4">Class & Section</th>
                                <th class="p-4">Allocated Subject</th>
                                <th class="p-4">Role / Status</th>
                                <th class="p-4 pr-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-850 text-xs text-slate-700 dark:text-slate-300">
                            <tr 
                                v-for="item in assignments" 
                                :key="item.id" 
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-950/40 transition-colors"
                            >
                                <td class="p-4 pl-6">
                                    <input 
                                        type="checkbox" 
                                        v-model="selectedIds" 
                                        :value="item.id" 
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer"
                                    />
                                </td>
                                <td class="p-4 font-bold text-slate-800 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-[10px] text-slate-500 font-bold uppercase flex items-center justify-center shrink-0">
                                            {{ item.teacher?.name?.substring(0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-bold">{{ item.teacher?.name }}</div>
                                            <div class="text-[9px] text-slate-400 font-normal">Code: {{ item.teacher?.employee_id || 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 font-semibold text-slate-700 dark:text-slate-200">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                                        {{ item.class?.name }} - {{ item.section?.name }}
                                    </span>
                                </td>
                                <td class="p-4 font-semibold">
                                    <span v-if="item.subject" class="text-indigo-600 dark:text-indigo-400 font-bold">
                                        {{ item.subject?.name }}
                                    </span>
                                    <span v-else class="text-slate-400 font-normal italic">
                                        No Subject Assigned
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span 
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border',
                                            item.is_class_teacher 
                                                ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' 
                                                : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'
                                        ]"
                                    >
                                        {{ item.is_class_teacher ? 'Class Teacher' : 'Subject Teacher' }}
                                    </span>
                                </td>
                                <td class="p-4 pr-6 text-right space-x-1.5">
                                    <button 
                                        v-if="authStore.hasPermission('user.edit')"
                                        @click="openEditModal(item)"
                                        class="p-1.5 bg-transparent border-none text-slate-400 hover:text-indigo-600 rounded-lg cursor-pointer transition-colors"
                                        title="Edit Assignment"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button 
                                        v-if="authStore.hasPermission('user.edit')"
                                        @click="deleteSingleAssignment(item)"
                                        class="p-1.5 bg-transparent border-none text-slate-400 hover:text-rose-600 rounded-lg cursor-pointer transition-colors"
                                        title="Delete Assignment"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Floating Action Footer for Bulk Operations -->
        <div 
            v-if="selectedIds.length > 0"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-6 border border-slate-800 animate-slide-up"
        >
            <span class="text-xs font-bold">Selected <span class="text-indigo-400 font-extrabold">{{ selectedIds.length }}</span> allocation records</span>
            <div class="flex gap-2">
                <button 
                    @click="selectedIds = []" 
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-lg border-none cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    @click="deleteSelectedAssignments"
                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-lg border-none cursor-pointer shadow-md"
                >
                    Bulk Delete ({{ selectedIds.length }})
                </button>
            </div>
        </div>

        <!-- BULK ASSIGNMENT MODAL -->
        <div v-if="showBulkModal" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl animate-[modalIn_0.18s_ease-out] flex flex-col max-h-[90vh]">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-850 pb-3 mb-4">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-1.5">
                            Bulk Teacher Allocation
                        </h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Assign multiple subjects and class teacher status to multiple staff members simultaneously.</p>
                    </div>
                    <button @click="showBulkModal = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer border-none bg-transparent">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-4 pr-1">
                    <div v-if="modalError" class="p-3.5 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-2xl border border-rose-100 dark:border-rose-900/30">
                        {{ modalError }}
                    </div>

                    <div v-if="bulkErrors.length > 0" class="p-3.5 bg-amber-50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 text-xs rounded-2xl border border-amber-200 dark:border-amber-900/30 space-y-1">
                        <div class="font-extrabold flex items-center gap-1.5 mb-1 text-amber-950 dark:text-amber-200">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Some allocations were skipped / already assigned:
                        </div>
                        <ul class="list-disc pl-4 space-y-0.5 max-h-24 overflow-y-auto">
                            <li v-for="(err, idx) in bulkErrors" :key="idx" class="font-semibold">{{ err }}</li>
                        </ul>
                    </div>

                    <!-- Step 1: Select Teachers -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Select Teachers (Choose multiple)</label>
                        <div class="max-h-36 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-xl p-3 bg-slate-50/50 dark:bg-slate-950/40 space-y-2">
                            <input 
                                type="text" 
                                v-model="teacherSearchQuery" 
                                placeholder="Search teacher by name..." 
                                class="w-full px-2.5 py-1.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:border-indigo-500 mb-2 font-semibold text-slate-700 dark:text-slate-200"
                            />
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label v-for="t in filteredTeachers" :key="t.id" class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                                    <input type="checkbox" :value="t.id" v-model="bulkForm.teacher_ids" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    {{ t.name }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Select Academic Session -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Academic Session</label>
                        <select v-model="bulkForm.academic_year_id" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-bold">
                            <option value="">Choose Session...</option>
                            <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.title }}</option>
                        </select>
                    </div>

                    <!-- Step 3: Select Class -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Select Classes (Choose multiple)</label>
                        <div class="flex flex-wrap gap-2 p-2 border border-slate-200 dark:border-slate-855 bg-slate-50/50 dark:bg-slate-950/20 rounded-xl">
                            <label v-for="c in classes" :key="c.id" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer flex items-center gap-1.5">
                                <input type="checkbox" :value="c.id" v-model="bulkForm.class_ids" @change="loadBulkSections" class="rounded border-slate-300 text-indigo-600" />
                                {{ c.name }}
                            </label>
                        </div>
                    </div>

                    <!-- Step 4: Select Section -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-550 uppercase tracking-wider mb-1.5">Select Sections (Leave blank to assign to all sections)</label>
                        <div v-if="bulkForm.class_ids.length === 0" class="text-xs text-slate-400 font-semibold italic pl-1">
                            Choose classes first to load sections...
                        </div>
                        <div v-else class="flex flex-wrap gap-2 p-2 border border-slate-200 dark:border-slate-855 bg-slate-50/50 dark:bg-slate-950/20 rounded-xl">
                            <label v-for="s in bulkSections" :key="s.id" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer flex items-center gap-1.5">
                                <input type="checkbox" :value="s.id" v-model="bulkForm.section_ids" @change="loadBulkSubjects" class="rounded border-slate-300 text-indigo-600" />
                                {{ s.name }} ({{ s.class?.name }})
                            </label>
                        </div>
                    </div>

                    <!-- Step 5: Select Subject -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-550 uppercase tracking-wider mb-1.5">Select Subjects (Leave blank to design as Class Teacher / Admin role)</label>
                        <div v-if="bulkForm.section_ids.length === 0" class="text-xs text-slate-400 font-semibold italic pl-1">
                            Choose sections first to load subjects...
                        </div>
                        <div v-else class="flex flex-wrap gap-2 p-2 border border-slate-200 dark:border-slate-855 bg-slate-50/50 dark:bg-slate-950/20 rounded-xl">
                            <label v-for="sub in bulkSubjects" :key="sub.id" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer flex items-center gap-1.5">
                                <input type="checkbox" :value="sub.id" v-model="bulkForm.subject_ids" class="rounded border-slate-300 text-indigo-600" />
                                {{ sub.name }} ({{ sub.class?.name }} - {{ sub.section?.name || 'All' }})
                            </label>
                        </div>
                    </div>

                    <!-- Step 6: Designation -->
                    <div class="pt-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="bulkForm.is_class_teacher" class="rounded border-slate-300 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 h-4 w-4" />
                            Designate all chosen selections as Class Teacher
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-850 pt-4 mt-4">
                    <button 
                        @click="showBulkModal = false" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-all border-none cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveBulkAssignments" 
                        :disabled="savingBulk"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-750 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md border-none cursor-pointer disabled:opacity-50"
                    >
                        {{ savingBulk ? 'Saving...' : 'Apply Assignments' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- SINGLE EDIT MODAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl animate-[modalIn_0.18s_ease-out]">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-850 pb-3 mb-4">
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white">Edit Teacher Assignment</h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer border-none bg-transparent">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div v-if="modalError" class="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-xl border border-rose-100 dark:border-rose-900/30">
                        {{ modalError }}
                    </div>

                    <!-- Teacher Display -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Teacher</label>
                        <select v-model="editForm.teacher_id" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>

                    <!-- Academic Session -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Academic Session</label>
                        <select v-model="editForm.academic_year_id" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-bold">
                            <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.title }}</option>
                        </select>
                    </div>

                    <!-- Class select -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Class</label>
                        <select v-model="editForm.class_id" @change="loadEditSections" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold">
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Section select -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Section</label>
                        <select v-model="editForm.section_id" @change="loadEditSubjects" class="w-full px-3 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 font-semibold">
                            <option v-for="s in editSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Subject select -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Select Subject (Optional)</label>
                        <select v-model="editForm.subject_id" :disabled="!editForm.section_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                            <option :value="null">No Subject (Class Teacher / Admin Only)</option>
                            <option v-for="sub in editSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                        </select>
                    </div>

                    <!-- Class Teacher status checkbox -->
                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="editForm.is_class_teacher" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                            Designate as Class Teacher
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-850 pt-4 mt-4">
                    <button 
                        @click="showEditModal = false" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-all border-none cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveSingleEdit" 
                        :disabled="savingEdit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-750 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md border-none cursor-pointer disabled:opacity-50"
                    >
                        {{ savingEdit ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'TeacherAssignmentsIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();

        const academicYears = ref([]);
        const selectedYearId = ref('');
        const classes = ref([]);
        const teachers = ref([]);
        const assignments = ref([]);

        const loading = ref(false);
        const selectedIds = ref([]);

        // Filter references
        const filters = ref({
            teacher_id: '',
            class_id: '',
            section_id: '',
            subject_id: ''
        });

        const filterSections = ref([]);
        const filterSubjects = ref([]);
        const hasFiltered = ref(false);

        // Modal triggers
        const showBulkModal = ref(false);
        const showEditModal = ref(false);
        const modalError = ref('');
        const bulkErrors = ref([]);

        // Bulk form references
        const teacherSearchQuery = ref('');
        const bulkForm = ref({
            teacher_ids: [],
            academic_year_id: '',
            class_ids: [],
            section_ids: [],
            subject_ids: [],
            is_class_teacher: false
        });
        const bulkSections = ref([]);
        const bulkSubjects = ref([]);
        const savingBulk = ref(false);

        // Edit form references
        const activeEditingItem = ref(null);
        const editForm = ref({
            teacher_id: '',
            academic_year_id: '',
            class_id: '',
            section_id: '',
            subject_id: null,
            is_class_teacher: false
        });
        const editSections = ref([]);
        const editSubjects = ref([]);
        const savingEdit = ref(false);

        const currentYearTitle = computed(() => {
            const yr = academicYears.value.find(ay => ay.id === selectedYearId.value);
            return yr ? yr.title : '';
        });

        const hasActiveFilters = computed(() => {
            return filters.value.teacher_id || filters.value.class_id || filters.value.section_id || filters.value.subject_id;
        });

        const isAllSelected = computed(() => {
            return assignments.value.length > 0 && selectedIds.value.length === assignments.value.length;
        });

        const filteredTeachers = computed(() => {
            if (!teacherSearchQuery.value) return teachers.value;
            const q = teacherSearchQuery.value.toLowerCase();
            return teachers.value.filter(t => t.name.toLowerCase().includes(q));
        });

        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                selectedIds.value = [];
            } else {
                selectedIds.value = assignments.value.map(a => a.id);
            }
        };

        const resetFilters = () => {
            filters.value = {
                teacher_id: '',
                class_id: '',
                section_id: '',
                subject_id: ''
            };
            filterSections.value = [];
            filterSubjects.value = [];
            assignments.value = [];
            hasFiltered.value = false;
        };

        const loadInitData = async () => {
            try {
                const ayRes = await window.axios.get('/api/academic-years');
                academicYears.value = ayRes.data.academic_years;

                const curr = academicYears.value.find(ay => ay.is_current);
                if (curr) {
                    selectedYearId.value = curr.id;
                }

                const classRes = await window.axios.get('/api/classes');
                classes.value = classRes.data.classes;

                const teachRes = await window.axios.get('/api/users', { params: { role: 'Teacher' } });
                teachers.value = teachRes.data.users;

                if (selectedYearId.value) {
                    // Do not auto-fetch assignments on initial load
                }
            } catch (err) {
                console.error(err);
                toastStore.error('Failed to initialize workspace data.');
            }
        };

        const fetchAssignments = async () => {
            if (!selectedYearId.value) return;
            hasFiltered.value = true;
            loading.value = true;
            try {
                const payload = {
                    academic_year_id: selectedYearId.value,
                    teacher_id: filters.value.teacher_id,
                    class_id: filters.value.class_id,
                    section_id: filters.value.section_id,
                    subject_id: filters.value.subject_id
                };
                const res = await window.axios.get('/api/teacher-assignments', { params: payload });
                assignments.value = res.data.assignments;
            } catch (err) {
                console.error(err);
                toastStore.error('Could not fetch teacher assignment mappings.');
            } finally {
                loading.value = false;
            }
        };

        const handleYearChange = () => {
            selectedIds.value = [];
            assignments.value = [];
            hasFiltered.value = false;
        };

        const handleClassFilterChange = async () => {
            filters.value.section_id = '';
            filters.value.subject_id = '';
            filterSections.value = [];
            filterSubjects.value = [];
            if (filters.value.class_id) {
                try {
                    const secRes = await window.axios.get('/api/sections', { params: { class_id: filters.value.class_id } });
                    filterSections.value = secRes.data.sections;
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const handleSectionFilterChange = async () => {
            filters.value.subject_id = '';
            filterSubjects.value = [];
            if (filters.value.class_id && filters.value.section_id) {
                try {
                    const subRes = await window.axios.get('/api/subjects', {
                        params: { 
                            class_id: filters.value.class_id, 
                            section_id: filters.value.section_id,
                            all: true
                        }
                    });
                    filterSubjects.value = subRes.data.subjects;
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const openBulkModal = () => {
            modalError.value = '';
            bulkForm.value = {
                teacher_ids: [],
                academic_year_id: selectedYearId.value,
                class_ids: [],
                section_ids: [],
                subject_ids: [],
                is_class_teacher: false
            };
            bulkSections.value = [];
            bulkSubjects.value = [];
            showBulkModal.value = true;
        };

        const loadBulkSections = async () => {
            bulkSections.value = [];
            bulkForm.value.section_ids = [];
            bulkForm.value.subject_ids = [];
            bulkSubjects.value = [];

            if (bulkForm.value.class_ids.length > 0) {
                try {
                    for (const cid of bulkForm.value.class_ids) {
                        const secRes = await window.axios.get('/api/sections', { params: { class_id: cid } });
                        bulkSections.value.push(...secRes.data.sections);
                    }
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const loadBulkSubjects = async () => {
            bulkForm.value.subject_ids = [];
            bulkSubjects.value = [];

            if (bulkForm.value.section_ids.length > 0) {
                try {
                    for (const sid of bulkForm.value.section_ids) {
                        const secObj = bulkSections.value.find(s => s.id === sid);
                        if (secObj) {
                            const subRes = await window.axios.get('/api/subjects', {
                                params: { 
                                    class_id: secObj.class_id, 
                                    section_id: sid,
                                    all: true
                                }
                            });
                            bulkSubjects.value.push(...subRes.data.subjects);
                        }
                    }
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const saveBulkAssignments = async () => {
            modalError.value = '';
            bulkErrors.value = [];
            if (bulkForm.value.teacher_ids.length === 0) {
                modalError.value = 'Please select at least one teacher.';
                return;
            }
            if (!bulkForm.value.academic_year_id) {
                modalError.value = 'Academic Session is required.';
                return;
            }
            if (bulkForm.value.class_ids.length === 0) {
                modalError.value = 'Please select at least one class.';
                return;
            }

            savingBulk.value = true;
            try {
                const res = await window.axios.post('/api/teacher-assignments/bulk', bulkForm.value);
                
                if (res.data.errors && res.data.errors.length > 0) {
                    bulkErrors.value = res.data.errors;
                    
                    if (res.data.created > 0) {
                        toastStore.warning(`Processed: created ${res.data.created} allocations, but skipped some duplicates.`);
                    } else {
                        toastStore.error('All selected allocations were skipped/already assigned.');
                    }
                    fetchAssignments();
                } else {
                    toastStore.success(res.data.message || 'Bulk assignments applied successfully.');
                    showBulkModal.value = false;
                    fetchAssignments();
                }
            } catch (err) {
                console.error(err);
                modalError.value = err.response?.data?.message || 'Error occurred while saving assignments.';
            } finally {
                savingBulk.value = false;
            }
        };

        const openEditModal = async (item) => {
            modalError.value = '';
            activeEditingItem.value = item;
            editForm.value = {
                teacher_id: item.teacher_id,
                academic_year_id: item.academic_year_id,
                class_id: item.class_id,
                section_id: item.section_id,
                subject_id: item.subject_id,
                is_class_teacher: !!item.is_class_teacher
            };
            
            editSections.value = [];
            editSubjects.value = [];
            
            try {
                const secRes = await window.axios.get('/api/sections', { params: { class_id: item.class_id } });
                editSections.value = secRes.data.sections;

                if (item.section_id) {
                    const subRes = await window.axios.get('/api/subjects', {
                        params: { 
                            class_id: item.class_id, 
                            section_id: item.section_id,
                            all: true
                        }
                    });
                    editSubjects.value = subRes.data.subjects;
                }
            } catch (err) {
                console.error(err);
            }

            showEditModal.value = true;
        };

        const loadEditSections = async () => {
            editForm.value.section_id = '';
            editForm.value.subject_id = null;
            editSections.value = [];
            editSubjects.value = [];

            if (editForm.value.class_id) {
                try {
                    const secRes = await window.axios.get('/api/sections', { params: { class_id: editForm.value.class_id } });
                    editSections.value = secRes.data.sections;
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const loadEditSubjects = async () => {
            editForm.value.subject_id = null;
            editSubjects.value = [];

            if (editForm.value.class_id && editForm.value.section_id) {
                try {
                    const subRes = await window.axios.get('/api/subjects', {
                        params: { 
                            class_id: editForm.value.class_id, 
                            section_id: editForm.value.section_id,
                            all: true
                        }
                    });
                    editSubjects.value = subRes.data.subjects;
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const saveSingleEdit = async () => {
            modalError.value = '';
            if (!editForm.value.teacher_id) {
                modalError.value = 'Teacher is required.';
                return;
            }
            if (!editForm.value.academic_year_id) {
                modalError.value = 'Academic Session is required.';
                return;
            }
            if (!editForm.value.class_id) {
                modalError.value = 'Class is required.';
                return;
            }
            if (!editForm.value.section_id) {
                modalError.value = 'Section is required.';
                return;
            }

            savingEdit.value = true;
            try {
                const res = await window.axios.put(`/api/teacher-assignments/${activeEditingItem.value.id}`, editForm.value);
                toastStore.success(res.data.message || 'Assignment updated successfully.');
                showEditModal.value = false;
                fetchAssignments();
            } catch (err) {
                console.error(err);
                modalError.value = err.response?.data?.message || 'Error occurred while saving modifications.';
            } finally {
                savingEdit.value = false;
            }
        };

        const deleteSingleAssignment = async (item) => {
            const confirmed = await confirmStore.show({
                title: 'Remove Assignment',
                message: `Are you sure you want to delete this allocation for ${item.teacher?.name}?`,
                type: 'danger',
                confirmText: 'Remove',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/teacher-assignments/${item.id}`);
                    toastStore.success('Assignment allocation deleted successfully.');
                    fetchAssignments();
                } catch (err) {
                    console.error(err);
                    toastStore.error('Could not remove teacher assignment.');
                }
            }
        };

        const deleteSelectedAssignments = async () => {
            const count = selectedIds.value.length;
            const confirmed = await confirmStore.show({
                title: 'Bulk Remove Allocations',
                message: `Are you sure you want to delete all ${count} selected teacher assignments? This action is irreversible.`,
                type: 'danger',
                confirmText: `Delete ${count}`,
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    const res = await window.axios.post('/api/teacher-assignments/bulk-delete', { ids: selectedIds.value });
                    toastStore.success(res.data.message || 'Selected assignments deleted successfully.');
                    selectedIds.value = [];
                    fetchAssignments();
                } catch (err) {
                    console.error(err);
                    toastStore.error('Could not process bulk deletion.');
                }
            }
        };

        onMounted(() => {
            loadInitData();
        });

        return {
            authStore,
            confirmStore,
            toastStore,
            academicYears,
            selectedYearId,
            classes,
            teachers,
            assignments,
            loading,
            selectedIds,
            filters,
            filterSections,
            filterSubjects,
            hasActiveFilters,
            hasFiltered,
            isAllSelected,
            filteredTeachers,
            showBulkModal,
            showEditModal,
            modalError,
            bulkErrors,
            teacherSearchQuery,
            bulkForm,
            bulkSections,
            bulkSubjects,
            savingBulk,
            editForm,
            editSections,
            editSubjects,
            savingEdit,
            currentYearTitle,
            toggleSelectAll,
            resetFilters,
            fetchAssignments,
            handleYearChange,
            handleClassFilterChange,
            handleSectionFilterChange,
            openBulkModal,
            loadBulkSections,
            loadBulkSubjects,
            saveBulkAssignments,
            openEditModal,
            loadEditSections,
            loadEditSubjects,
            saveSingleEdit,
            deleteSingleAssignment,
            deleteSelectedAssignments
        };
    }
};
</script>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        transform: translate(-50%, 100%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}
</style>
