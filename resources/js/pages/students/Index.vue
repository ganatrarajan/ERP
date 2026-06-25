<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Student Directory</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage admission records, track student profiles, and historical academic data.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                 <!-- Export Excel -->
                <button 
                    @click="exportExcel"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center gap-1.5 active:scale-95"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export Excel
                </button>
                
                <!-- Export PDF (Print) -->
                <button 
                    @click="exportPDF"
                    class="px-4 py-2.5 bg-slate-600 hover:bg-slate-700 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center gap-1.5 active:scale-95"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Profile / PDF
                </button>

                <!-- Import Students -->
                <button 
                    v-if="authStore.hasPermission('student.create') && isCurrentYear"
                    @click="openImportModal"
                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-sm rounded-xl shadow-md transition-all flex items-center gap-1.5 active:scale-95 border border-slate-200 dark:border-slate-750"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import Students
                </button>

                <!-- Add Student -->
                <router-link 
                    v-if="authStore.hasPermission('student.create') && isCurrentYear"
                    to="/students/create"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Admit Student
                </router-link>
            </div>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loading" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Creating, editing, or deleting student records is disabled.
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <!-- Academic Year -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Academic Year</label>
                    <select 
                        v-model="filters.academic_year_id" 
                        @change="onAcademicYearChange"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">All Academic Years</option>
                        <option v-for="year in academicYears" :key="year.id" :value="year.id">
                            {{ year.title }} <span v-if="year.is_current">(Current)</span>
                        </option>
                    </select>
                </div>

                <!-- Class -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Class</label>
                    <select 
                        v-model="filters.class_id" 
                        @change="onClassChange"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">All Classes</option>
                        <option v-for="cls in filteredClasses" :key="cls.id" :value="cls.id">
                            {{ cls.name }}
                        </option>
                    </select>
                </div>

                <!-- Section -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Section</label>
                    <select 
                        v-model="filters.section_id" 
                        @change="fetchStudents(1)"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">All Sections</option>
                        <option v-for="sec in filteredSections" :key="sec.id" :value="sec.id">
                            {{ sec.name }}
                        </option>
                    </select>
                </div>

                <!-- Status -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</label>
                    <select 
                        v-model="filters.status" 
                        @change="fetchStudents(1)"
                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Search</label>
                    <div class="relative">
                        <input 
                            v-model="filters.search" 
                            type="text" 
                            placeholder="Name, Reg No..." 
                            @input="debouncedSearch"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Listing Table -->
        <div id="print-area" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="students.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                No student admission records found matching filters.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60 print:bg-transparent">
                            <th class="p-4 pl-6">Admission No</th>
                            <th class="p-4">Student Name</th>
                            <th class="p-4">Class</th>
                            <th class="p-4">Section</th>
                            <th class="p-4">Roll No</th>
                            <th class="p-4">Mobile</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right print:hidden">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="std in students" :key="std.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-bold text-indigo-600 dark:text-indigo-400">
                                {{ std.admission_no }}
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-slate-600 dark:text-slate-300 overflow-hidden">
                                        <img v-if="std.photo" :src="std.photo" class="object-cover w-full h-full" />
                                        <span v-else>{{ std.first_name.substring(0, 1) }}{{ std.last_name.substring(0, 1) }}</span>
                                    </div>
                                    <div>
                                        <router-link :to="`/students/${std.id}`" class="font-bold text-slate-800 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                            {{ std.first_name }} {{ std.last_name }}
                                        </router-link>
                                        <p class="text-xs text-slate-500">{{ std.gender }} • {{ std.blood_group || 'No blood group' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 font-semibold text-slate-700 dark:text-slate-250">
                                {{ std.class_name }}
                            </td>
                            <td class="p-4 font-semibold text-slate-700 dark:text-slate-250">
                                {{ std.section_name }}
                            </td>
                            <td class="p-4 font-medium">
                                {{ std.roll_no || 'N/A' }}
                            </td>
                            <td class="p-4 text-slate-500">
                                {{ std.mobile || 'N/A' }}
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-bold capitalize',
                                    std.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                ]">
                                    {{ std.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right print:hidden">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View/Profile -->
                                    <router-link 
                                        :to="`/students/${std.id}`"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="View Profile"
                                    >
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </router-link>

                                    <!-- Edit -->
                                    <router-link 
                                        v-if="authStore.hasPermission('student.edit') && isCurrentYear"
                                        :to="`/students/${std.id}/edit`"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Student"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </router-link>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('student.delete') && isCurrentYear"
                                        @click="handleDelete(std)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Admission Record"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between print:hidden">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} entries
                </span>
                <div class="flex items-center gap-1.5">
                    <button 
                        :disabled="!pagination.prev_page_url" 
                        @click="fetchStudents(pagination.current_page - 1)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 transition-all bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                    >
                        Previous
                    </button>
                    <button 
                        :disabled="!pagination.next_page_url" 
                        @click="fetchStudents(pagination.current_page + 1)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-50 transition-all bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div v-if="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Students CSV
                    </h3>
                    <button @click="closeImportModal" class="p-1 text-slate-400 hover:text-slate-655 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 overflow-y-auto max-h-[70vh]">
                    <!-- Step 1: Download Demo File -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">1. Download Template</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Download the structured sample CSV containing 2 sample student records. Populate it with your student directory keeping columns identical.</p>
                        <button 
                            @click="downloadDemo"
                            type="button"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-xs font-bold rounded-lg transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download Demo CSV
                        </button>
                    </div>

                    <!-- Step 2: Upload File -->
                    <div class="space-y-2">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">2. Upload Filled CSV</h4>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl cursor-pointer bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-950/50 dark:hover:bg-slate-950 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><span class="font-bold text-indigo-600 dark:text-indigo-400">Click to upload</span> or drag and drop</p>
                                    <p class="text-[10px] text-slate-450 dark:text-slate-500 mt-1">Only CSV files supported (Max 4MB)</p>
                                    <p v-if="selectedFileName" class="text-xs font-semibold text-emerald-600 dark:text-emerald-450 mt-2 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ selectedFileName }}
                                    </p>
                                </div>
                                <input @change="onFileSelected" type="file" ref="fileInput" accept=".csv,.txt" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <!-- Step 3: Validation Errors Display -->
                    <div v-if="importErrors.length > 0" class="space-y-3">
                        <h4 class="text-sm font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Import Validation Errors (No rows imported)
                        </h4>
                        <div class="border border-rose-500/20 bg-rose-500/5 rounded-xl divide-y divide-rose-500/10 max-h-56 overflow-y-auto">
                            <div v-for="(err, idx) in importErrors" :key="idx" class="p-3 text-xs space-y-1">
                                <div class="flex justify-between items-center font-bold text-slate-700 dark:text-slate-350">
                                    <span>Row {{ err.row }} (Adm: {{ err.admission_no }})</span>
                                    <span class="text-slate-500 font-normal">{{ err.name }}</span>
                                </div>
                                <ul class="list-disc pl-4 text-rose-600 dark:text-rose-400 space-y-0.5">
                                    <li v-for="(msg, eIdx) in err.errors" :key="eIdx">{{ msg }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    <button 
                        @click="closeImportModal"
                        type="button" 
                        class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="submitImport"
                        :disabled="!selectedFile || importing"
                        type="button" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1.5"
                    >
                        <span v-if="importing">Importing...</span>
                        <span v-else>Start Import</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'StudentsIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();

        const students = ref([]);
        const academicYears = ref([]);

        const isCurrentYear = computed(() => {
            if (!filters.value.academic_year_id) return true;
            const selected = academicYears.value.find(y => y.id === filters.value.academic_year_id);
            return selected ? !!selected.is_current : false;
        });
        
        // Import Student Refs
        const importModalOpen = ref(false);
        const selectedFile = ref(null);
        const selectedFileName = ref('');
        const importing = ref(false);
        const importErrors = ref([]);
        const fileInput = ref(null);
        
        // Filtered lists based on hierarchy selection
        const filteredClasses = ref([]);
        const filteredSections = ref([]);

        const fetchClasses = async () => {
            try {
                const response = await window.axios.get('/api/classes', {
                    params: {
                        status: 'active',
                        academic_year_id: filters.value.academic_year_id
                    }
                });
                filteredClasses.value = response.data.classes || [];
            } catch (error) {
                console.error(error);
            }
        };

        const fetchSections = async () => {
            if (!filters.value.class_id) {
                filteredSections.value = [];
                return;
            }
            try {
                const response = await window.axios.get('/api/sections', {
                    params: {
                        status: 'active',
                        class_id: filters.value.class_id
                    }
                });
                filteredSections.value = response.data.sections || [];
            } catch (error) {
                console.error(error);
            }
        };

        const loading = ref(true);
        const pagination = ref({});
        
        const filters = ref({
            academic_year_id: '',
            class_id: '',
            section_id: '',
            status: '',
            search: ''
        });

        let searchTimeout = null;

        const fetchFiltersData = async () => {
            try {
                // Fetch Academic Years
                const yResponse = await window.axios.get('/api/academic-years', { params: { status: 'active' } });
                academicYears.value = yResponse.data.academic_years;

                // Set default to current session
                const current = academicYears.value.find(y => y.is_current);
                if (current) {
                    filters.value.academic_year_id = current.id;
                }

                // Load classes for the default academic year
                await fetchClasses();

            } catch (error) {
                console.error('Failed to load filter metadata', error);
            }
        };

        const onAcademicYearChange = async () => {
            filters.value.class_id = '';
            filters.value.section_id = '';
            filteredSections.value = [];
            await fetchClasses();
            fetchStudents(1);
        };

        const onClassChange = async () => {
            filters.value.section_id = '';
            await fetchSections();
            fetchStudents(1);
        };

        const fetchStudents = async (page = 1) => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/students', {
                    params: {
                        page,
                        academic_year_id: filters.value.academic_year_id,
                        class_id: filters.value.class_id,
                        section_id: filters.value.section_id,
                        status: filters.value.status,
                        search: filters.value.search
                    }
                });
                students.value = response.data.data;
                pagination.value = {
                    current_page: response.data.current_page,
                    from: response.data.from,
                    to: response.data.to,
                    total: response.data.total,
                    prev_page_url: response.data.prev_page_url,
                    next_page_url: response.data.next_page_url
                };
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load students.');
            } finally {
                loading.value = false;
            }
        };

        const debouncedSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchStudents(1);
            }, 300);
        };

        const handleDelete = async (std) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Student Admission',
                message: `Are you sure you want to delete the student: "${std.first_name} ${std.last_name}"? This will delete the student profile, parental data, and all academic records permanently.`,
                type: 'danger',
                confirmText: 'Confirm Delete',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/students/${std.id}`);
                    toastStore.success('Student admission deleted successfully.');
                    fetchStudents(pagination.value.current_page);
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete student.');
                }
            }
        };

        const exportExcel = () => {
            const params = new URLSearchParams({
                export: 'csv',
                academic_year_id: filters.value.academic_year_id || '',
                class_id: filters.value.class_id || '',
                section_id: filters.value.section_id || '',
                status: filters.value.status || '',
                search: filters.value.search || ''
            });
            window.location.href = `/api/students?${params.toString()}`;
        };

        const exportPDF = () => {
            window.print();
        };

        const openImportModal = () => {
            importModalOpen.value = true;
            selectedFile.value = null;
            selectedFileName.value = '';
            importErrors.value = [];
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        };

        const closeImportModal = () => {
            importModalOpen.value = false;
        };

        const onFileSelected = (e) => {
            const file = e.target.files[0];
            if (file) {
                selectedFile.value = file;
                selectedFileName.value = file.name;
            }
        };

        const downloadDemo = () => {
            window.location.href = '/api/students/import/demo';
        };

        const submitImport = async () => {
            if (!selectedFile.value) return;
            importing.value = true;
            importErrors.value = [];

            const formData = new FormData();
            formData.append('file', selectedFile.value);

            try {
                const response = await window.axios.post('/api/students/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                toastStore.success(response.data.message || 'Students imported successfully.');
                importModalOpen.value = false;
                selectedFile.value = null;
                selectedFileName.value = '';
                fetchStudents(1);
            } catch (error) {
                console.error(error);
                if (error.response && error.response.status === 422) {
                    const data = error.response.data;
                    if (data.errors && Array.isArray(data.errors)) {
                        importErrors.value = data.errors;
                        toastStore.error(data.message || 'Validation failed. Please correct the errors in your CSV file.');
                    } else {
                        toastStore.error(data.message || 'Validation failed.');
                    }
                } else {
                    toastStore.error(error.response?.data?.message || 'An error occurred during import.');
                }
            } finally {
                importing.value = false;
            }
        };

        onMounted(async () => {
            await fetchFiltersData();
            await fetchStudents(1);
        });

        return {
            authStore,
            students,
            academicYears,
            filteredClasses,
            filteredSections,
            loading,
            pagination,
            filters,
            onAcademicYearChange,
            onClassChange,
            debouncedSearch,
            fetchStudents,
            handleDelete,
            exportExcel,
            exportPDF,
            importModalOpen,
            selectedFile,
            selectedFileName,
            importing,
            importErrors,
            fileInput,
            openImportModal,
            closeImportModal,
            onFileSelected,
            downloadDemo,
            submitImport,
            isCurrentYear
        };
    }
}
</script>

<style scoped>
@media print {
    /* Hide layout chrome and show just the printable grid content */
    body * {
        visibility: hidden;
    }
    #print-area, #print-area * {
        visibility: visible;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
