<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Student Directory</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage admission records, track student profiles, and historical academic data.</p>
            </div>
            <div class="flex items-center gap-2 flex-nowrap shrink-0">
                 <!-- Custom Reports Builder -->
                <button 
                    @click="openReportModal"
                    class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-indigo-700 dark:text-indigo-400 font-bold text-sm rounded-xl transition-all flex items-center gap-1.5 active:scale-95 border border-indigo-100 dark:border-indigo-900/50"
                >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Custom Report Builder
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
                            placeholder="Name, GR No, Aadhaar..." 
                            @input="debouncedSearch"
                            class="w-full pl-8 pr-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Toggle & Drawer -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-850">
                <button 
                    @click="advancedFiltersOpen = !advancedFiltersOpen"
                    class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition-colors flex items-center gap-1 focus:outline-none"
                >
                    <svg :class="['w-3.5 h-3.5 transition-transform duration-200', advancedFiltersOpen ? 'rotate-90' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                    {{ advancedFiltersOpen ? 'Hide' : 'Show' }} Advanced Filters (Gender, Category, House, Religion)
                </button>

                <div v-show="advancedFiltersOpen" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-3 pt-3 border-t border-dashed border-slate-150 dark:border-slate-800">
                    <!-- Gender -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Gender</label>
                        <select 
                            v-model="filters.gender" 
                            @change="fetchStudents(1)"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
                        >
                            <option value="">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Caste Category</label>
                        <select 
                            v-model="filters.category" 
                            @change="fetchStudents(1)"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
                        >
                            <option value="">All Categories</option>
                            <option value="General">General/Open</option>
                            <option value="SEBC">SEBC/OBC</option>
                            <option value="SC">SC</option>
                            <option value="ST">ST</option>
                            <option value="EWS">EWS</option>
                        </select>
                    </div>

                    <!-- House -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">House</label>
                        <select 
                            v-model="filters.house" 
                            @change="fetchStudents(1)"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
                        >
                            <option value="">All Houses</option>
                            <option value="Red">Red</option>
                            <option value="Green">Green</option>
                            <option value="Blue">Blue</option>
                            <option value="Yellow">Yellow</option>
                        </select>
                    </div>

                    <!-- Religion -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Religion</label>
                        <select 
                            v-model="filters.religion" 
                            @change="fetchStudents(1)"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
                        >
                            <option value="">All Religions</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Muslim">Muslim</option>
                            <option value="Christian">Christian</option>
                            <option value="Sikh">Sikh</option>
                            <option value="Jain">Jain</option>
                            <option value="Buddhist">Buddhist</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Listing Table -->
        <div id="print-area" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="!filters.class_id && !filters.section_id && !filters.search" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-350">Please select a Class or Section</h4>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Select class/section dropdown filters or type a search query to load student listing.</p>
            </div>

            <div v-else-if="students.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                No student admission records found matching filters.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase border-b border-slate-200 dark:border-slate-800">
                            <th class="p-4 pl-6">Roll No</th>
                            <th class="p-4">Student</th>
                            <th class="p-4">Admission / GR No</th>
                            <th class="p-4">Class / Section</th>
                            <th class="p-4">Category / House</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right print:hidden">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr 
                            v-for="std in students" 
                            :key="std.id" 
                            class="border-b border-slate-100 dark:border-slate-800/80 hover:bg-slate-50/50 dark:hover:bg-slate-900/40 text-slate-700 dark:text-slate-350 transition-colors"
                        >
                            <td class="p-4 pl-6 font-bold text-xs">
                                {{ std.roll_no || 'N/A' }}
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-600 dark:text-slate-300 overflow-hidden shadow-inner shrink-0">
                                        <img v-if="std.photo" :src="std.photo" class="object-cover w-full h-full" />
                                        <span v-else>{{ std.first_name.substring(0, 1) }}{{ std.last_name.substring(0, 1) }}</span>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-none">{{ std.first_name }} {{ std.last_name }}</h4>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">{{ std.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-xs font-semibold">
                                <div class="font-bold text-slate-800 dark:text-slate-200">Adm: {{ std.admission_no }}</div>
                                <div v-if="std.gr_no" class="text-[10px] text-indigo-600 dark:text-indigo-400 mt-0.5">GR No: {{ std.gr_no }}</div>
                            </td>
                            <td class="p-4 text-xs font-medium">
                                {{ std.class_name }} — {{ std.section_name }}
                            </td>
                            <td class="p-4 text-xs font-medium">
                                <div>{{ std.category || 'General' }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5" v-if="std.house">House: {{ std.house }}</div>
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize',
                                    std.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 border border-rose-500/20'
                                ]">
                                    {{ std.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right print:hidden">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View/Profile -->
                                    <router-link 
                                        :to="`/students/${std.id}`"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="View Profile"
                                    >
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </router-link>

                                    <!-- Documents Folder Access -->
                                    <router-link 
                                        :to="`/students/${std.id}?tab=documents`"
                                        class="p-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-800/80 dark:hover:bg-slate-700/80 text-indigo-650 dark:text-indigo-400 rounded-lg border border-indigo-200/50 dark:border-slate-700/60 transition-colors"
                                        title="Student Documents"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
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

        <!-- Custom Report Builder Modal -->
        <div v-if="customReportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-4xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Custom Student Report Builder
                    </h3>
                    <button @click="closeReportModal" class="p-1 text-slate-400 hover:text-slate-655 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>                <div class="p-6 space-y-6 overflow-y-auto max-h-[75vh]">
                    <!-- Top Columns Selection Panel -->
                    <div class="bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200/60 dark:border-slate-800 space-y-3 shrink-0">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    Report Columns
                                </h4>
                                <!-- Preset Dropdown -->
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-[9px] font-extrabold text-slate-400 uppercase dark:text-slate-500">Preset Layout:</span>
                                    <select 
                                        v-model="selectedPreset" 
                                        @change="applyPreset"
                                        class="px-2 py-0.5 text-[10px] rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-bold focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                    >
                                        <option value="general">General Listing (Standard)</option>
                                        <option value="contacts">Contact & Addresses</option>
                                        <option value="parents">Parental Information</option>
                                        <option value="academic">Demographics & Birth Info</option>
                                        <option value="custom">Custom (Specified Below)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button 
                                    type="button" 
                                    @click="selectAllReportColumns"
                                    class="text-[10px] font-extrabold text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    Check All
                                </button>
                                <span class="text-slate-300 dark:text-slate-800 text-xs">|</span>
                                <button 
                                    type="button" 
                                    @click="unselectAllReportColumns"
                                    class="text-[10px] font-extrabold text-rose-500 hover:underline"
                                >
                                    Uncheck All
                                </button>
                                <span class="text-slate-300 dark:text-slate-800 text-xs">|</span>
                                <button 
                                    type="button" 
                                    @click="applyReportColumns"
                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-[10px] font-extrabold rounded-lg active:scale-95 transition-all shadow-md shadow-indigo-600/10"
                                >
                                    Apply & Preview
                                </button>
                            </div>
                        </div>
                        
                        <!-- Multi-column Grid layout for checkboxes, NO scrollbar! -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-2.5">
                            <label v-for="(label, key) in reportColumnOptions" :key="key" class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-350 cursor-pointer select-none hover:text-slate-900 dark:hover:text-white transition-colors">
                                <input 
                                    type="checkbox" 
                                    :checked="tempReportColumns.includes(key)"
                                    @change="toggleReportColumn(key)"
                                    class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 w-3.5 h-3.5" 
                                />
                                <span class="truncate">{{ label }}</span>
                            </label>
                        </div>

                        <!-- Selected Columns Sequence & Reordering -->
                        <div v-if="tempReportColumns.length > 0" class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Columns Layout Sequence (Click arrows to reorder 1st, 2nd, etc.)</span>
                            <div class="flex flex-wrap gap-1.5 p-2 bg-slate-100/50 dark:bg-slate-950/40 rounded-xl border border-slate-200/50 dark:border-slate-800/80">
                                <div v-for="(col, index) in tempReportColumns" :key="col" class="flex items-center gap-1 px-2.5 py-1 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 rounded-lg text-[10px] font-bold text-slate-700 dark:text-slate-350 shadow-sm">
                                    <span class="text-indigo-600 dark:text-indigo-400 mr-0.5">{{ index + 1 }}.</span>
                                    <span class="truncate max-w-[100px]">{{ reportColumnOptions[col] }}</span>
                                    
                                    <div class="flex items-center gap-0.5 ml-1.5 border-l border-slate-200 dark:border-slate-800 pl-1 shrink-0">
                                        <!-- Move Left / Up -->
                                        <button 
                                            v-if="index > 0" 
                                            @click="moveColumn(index, -1)" 
                                            class="p-0.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" 
                                            type="button"
                                            title="Move Left"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                                        </button>
                                        <!-- Move Right / Down -->
                                        <button 
                                            v-if="index < tempReportColumns.length - 1" 
                                            @click="moveColumn(index, 1)" 
                                            class="p-0.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" 
                                            type="button"
                                            title="Move Right"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Live Table Preview -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest flex items-center justify-between">
                            <span>Live Data Preview (First 5 records)</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-600 font-normal">Orientation: {{ selectedReportColumns.length > 6 ? 'Landscape' : 'Portrait' }}</span>
                        </h4>

                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden max-h-80 overflow-y-auto bg-slate-50/50 dark:bg-slate-950/20">
                            <div v-if="loadingReportPreview" class="p-8 text-center text-xs text-slate-500 animate-pulse flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Loading preview...
                            </div>
                            <div v-else-if="reportPreviewData.length === 0" class="p-8 text-center text-xs text-slate-500">
                                No records match the active directory filters.
                            </div>
                            <table v-else class="w-full text-left border-collapse text-[10px]">
                                <thead class="bg-slate-100 dark:bg-slate-850 font-bold border-b border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                                    <tr>
                                        <th v-for="col in selectedReportColumns" :key="col" class="p-2 border-r border-slate-200 dark:border-slate-800 last:border-0 whitespace-nowrap">
                                            {{ reportColumnOptions[col] }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-150 dark:divide-slate-800 text-slate-600 dark:text-slate-350">
                                    <tr v-for="row in reportPreviewData" :key="row.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors">
                                        <td v-for="col in selectedReportColumns" :key="col" class="p-2 border-r border-slate-150 dark:border-slate-800 last:border-0 whitespace-nowrap">
                                            <span v-if="col === 'father_name'">{{ row.parent?.father_name || 'N/A' }}</span>
                                            <span v-else-if="col === 'father_mobile'">{{ row.parent?.father_mobile || 'N/A' }}</span>
                                            <span v-else-if="col === 'mother_name'">{{ row.parent?.mother_name || 'N/A' }}</span>
                                            <span v-else-if="col === 'mother_mobile'">{{ row.parent?.mother_mobile || 'N/A' }}</span>
                                            <span v-else-if="col === 'guardian_name'">{{ row.parent?.guardian_name || 'N/A' }}</span>
                                            <span v-else-if="col === 'guardian_mobile'">{{ row.parent?.guardian_mobile || 'N/A' }}</span>
                                            <span v-else>{{ row[col] || 'N/A' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:justify-between items-center gap-3">
                    <span class="text-xs text-slate-400 font-medium">Reports respect all active sidebar filters and search criteria.</span>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="closeReportModal"
                            type="button" 
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        
                        <!-- Print -->
                        <button 
                            @click="printReport"
                            class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl active:scale-95 transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print
                        </button>

                        <!-- PDF -->
                        <button 
                            @click="exportReportPDF"
                            class="px-3 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl active:scale-95 transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            PDF
                        </button>

                        <!-- Excel -->
                        <button 
                            @click="exportReportExcel"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl active:scale-95 transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Excel
                        </button>
                    </div>
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
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Only CSV files supported (Max 4MB)</p>
                                    <p v-if="selectedFileName" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-2 flex items-center gap-1">
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
        const advancedFiltersOpen = ref(false);

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
            search: '',
            gender: '',
            category: '',
            house: '',
            religion: ''
        });

        let searchTimeout = null;

        const fetchFiltersData = async () => {
            try {
                // Fetch Academic Years
                const yResponse = await window.axios.get('/api/academic-years', { params: { status: 'active' } });
                academicYears.value = yResponse.data.academic_years;

                // Restore filters from sessionStorage if available
                const saved = sessionStorage.getItem('student_filters');
                if (saved) {
                    const parsed = JSON.parse(saved);
                    filters.value = { ...filters.value, ...parsed };
                    
                    // Fetch options based on restored filters
                    await fetchClasses();
                    if (filters.value.class_id) {
                        await fetchSections();
                    }
                } else {
                    // Set default to current session
                    const current = academicYears.value.find(y => y.is_current);
                    if (current) {
                        filters.value.academic_year_id = current.id;
                    }
                    // Load classes for the default academic year
                    await fetchClasses();
                }

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
            // Save filters state to sessionStorage whenever we list/load
            sessionStorage.setItem('student_filters', JSON.stringify(filters.value));

            if (!filters.value.class_id && !filters.value.section_id && !filters.value.search) {
                students.value = [];
                pagination.value = {};
                loading.value = false;
                return;
            }

            loading.value = true;
            try {
                const response = await window.axios.get('/api/students', {
                    params: {
                        page,
                        academic_year_id: filters.value.academic_year_id,
                        class_id: filters.value.class_id,
                        section_id: filters.value.section_id,
                        status: filters.value.status,
                        search: filters.value.search,
                        gender: filters.value.gender,
                        category: filters.value.category,
                        house: filters.value.house,
                        religion: filters.value.religion
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

        // Report Builder Modal states
        const customReportModalOpen = ref(false);
        const selectedReportColumns = ref(['roll_no', 'admission_no', 'gr_no', 'first_name', 'last_name', 'class_name', 'section_name']);
        const reportPreviewData = ref([]);
        const loadingReportPreview = ref(false);

        const reportColumnOptions = {
            roll_no: 'Roll No',
            admission_no: 'Admission No',
            gr_no: 'GR No',
            first_name: 'First Name',
            last_name: 'Last Name',
            class_name: 'Class',
            section_name: 'Section',
            academic_year_title: 'Session',
            gender: 'Gender',
            date_of_birth: 'DOB',
            blood_group: 'Blood Group',
            category: 'Category',
            religion: 'Religion',
            nationality: 'Nationality',
            aadhaar_no: 'Aadhaar No',
            pen_no: 'PEN No',
            udise_no: 'UDISE No',
            house: 'House',
            mobile: 'Student Mobile',
            email: 'Student Email',
            address: 'Address',
            father_name: 'Father Name',
            father_mobile: 'Father Mobile',
            mother_name: 'Mother Name',
            mother_mobile: 'Mother Mobile',
            guardian_name: 'Guardian Name',
            guardian_mobile: 'Guardian Mobile',
            emergency_contact_name: 'Emergency Name',
            emergency_contact_mobile: 'Emergency Phone',
            previous_school_name: 'Previous School',
            admission_date: 'Admission Date',
            status: 'Status'
        };

        const tempReportColumns = ref([]);
        const selectedPreset = ref('general');

        const reportPresets = {
            general: {
                columns: ['roll_no', 'admission_no', 'gr_no', 'first_name', 'last_name', 'class_name', 'section_name']
            },
            contacts: {
                columns: ['roll_no', 'first_name', 'last_name', 'mobile', 'email', 'address', 'emergency_contact_mobile']
            },
            parents: {
                columns: ['roll_no', 'first_name', 'last_name', 'father_name', 'father_mobile', 'mother_name', 'mother_mobile']
            },
            academic: {
                columns: ['roll_no', 'admission_no', 'gr_no', 'first_name', 'last_name', 'date_of_birth', 'gender', 'category', 'religion']
            }
        };

        const openReportModal = () => {
            tempReportColumns.value = [...selectedReportColumns.value];
            customReportModalOpen.value = true;
            fetchReportPreview();
        };

        const closeReportModal = () => {
            customReportModalOpen.value = false;
        };

        const selectAllReportColumns = () => {
            selectedPreset.value = 'custom';
            tempReportColumns.value = Object.keys(reportColumnOptions);
        };

        const unselectAllReportColumns = () => {
            selectedPreset.value = 'custom';
            tempReportColumns.value = [];
        };

        const toggleReportColumn = (key) => {
            selectedPreset.value = 'custom';
            const index = tempReportColumns.value.indexOf(key);
            if (index > -1) {
                tempReportColumns.value.splice(index, 1);
            } else {
                tempReportColumns.value.push(key);
            }
        };

        const moveColumn = (index, direction) => {
            selectedPreset.value = 'custom';
            const targetIndex = index + direction;
            if (targetIndex >= 0 && targetIndex < tempReportColumns.value.length) {
                const temp = tempReportColumns.value[index];
                tempReportColumns.value[index] = tempReportColumns.value[targetIndex];
                tempReportColumns.value[targetIndex] = temp;
            }
        };

        const applyPreset = () => {
            if (selectedPreset.value && reportPresets[selectedPreset.value]) {
                tempReportColumns.value = [...reportPresets[selectedPreset.value].columns];
                selectedReportColumns.value = [...tempReportColumns.value];
                fetchReportPreview();
            }
        };

        const applyReportColumns = () => {
            if (tempReportColumns.value.length === 0) {
                toastStore.error('Please select at least one column to display.');
                return;
            }
            selectedReportColumns.value = [...tempReportColumns.value];
            fetchReportPreview();
        };

        const fetchReportPreview = async () => {
            loadingReportPreview.value = true;
            try {
                const response = await window.axios.get('/api/students/reports', {
                    params: {
                        academic_year_id: filters.value.academic_year_id,
                        class_id: filters.value.class_id,
                        section_id: filters.value.section_id,
                        status: filters.value.status,
                        search: filters.value.search,
                        gender: filters.value.gender,
                        category: filters.value.category,
                        house: filters.value.house,
                        religion: filters.value.religion,
                        columns: selectedReportColumns.value.join(',')
                    }
                });
                // Take first 5 for preview
                reportPreviewData.value = (response.data.students || []).slice(0, 5);
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load report preview.');
            } finally {
                loadingReportPreview.value = false;
            }
        };

        const exportReportExcel = () => {
            const params = new URLSearchParams({
                export: 'csv',
                academic_year_id: filters.value.academic_year_id || '',
                class_id: filters.value.class_id || '',
                section_id: filters.value.section_id || '',
                status: filters.value.status || '',
                search: filters.value.search || '',
                gender: filters.value.gender || '',
                category: filters.value.category || '',
                house: filters.value.house || '',
                religion: filters.value.religion || '',
                columns: selectedReportColumns.value.join(',')
            });
            window.location.href = `/api/students/reports?${params.toString()}`;
        };

        const exportReportPDF = () => {
            const params = new URLSearchParams({
                export: 'pdf',
                academic_year_id: filters.value.academic_year_id || '',
                class_id: filters.value.class_id || '',
                section_id: filters.value.section_id || '',
                status: filters.value.status || '',
                search: filters.value.search || '',
                gender: filters.value.gender || '',
                category: filters.value.category || '',
                house: filters.value.house || '',
                religion: filters.value.religion || '',
                columns: selectedReportColumns.value.join(',')
            });
            window.location.href = `/api/students/reports?${params.toString()}`;
        };

        const printReport = () => {
            // Print report by opening a clean report HTML layout in a new tab
            const params = new URLSearchParams({
                academic_year_id: filters.value.academic_year_id || '',
                class_id: filters.value.class_id || '',
                section_id: filters.value.section_id || '',
                status: filters.value.status || '',
                search: filters.value.search || '',
                gender: filters.value.gender || '',
                category: filters.value.category || '',
                house: filters.value.house || '',
                religion: filters.value.religion || '',
                columns: selectedReportColumns.value.join(',')
            });
            const printWindow = window.open(`/api/students/reports?${params.toString()}`, '_blank');
            printWindow.onload = () => {
                printWindow.print();
            };
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
                    toastStore.error(error.response?.data?.message || 'Import failed.');
                }
            } finally {
                importing.value = false;
            }
        };

        onMounted(async () => {
            await fetchFiltersData();
            fetchStudents(1);
        });

        return {
            authStore,
            students,
            academicYears,
            isCurrentYear,
            filteredClasses,
            filteredSections,
            loading,
            pagination,
            filters,
            advancedFiltersOpen,
            debouncedSearch,
            handleDelete,
            onAcademicYearChange,
            onClassChange,
            fetchStudents,

            // Import
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

            // Custom Report States & Actions
            customReportModalOpen,
            selectedReportColumns,
            tempReportColumns,
            selectedPreset,
            reportPreviewData,
            loadingReportPreview,
            reportColumnOptions,
            openReportModal,
            closeReportModal,
            fetchReportPreview,
            selectAllReportColumns,
            unselectAllReportColumns,
            toggleReportColumn,
            moveColumn,
            applyPreset,
            applyReportColumns,
            exportReportExcel,
            exportReportPDF,
            printReport
        };
    }
}
</script>
