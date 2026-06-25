<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Subjects</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage school curriculum subjects and assign them to classes, sections, and sessions.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('subject.create') && isCurrentYear"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Subject
            </button>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loading" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Creating, editing, or deleting subjects is disabled.
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm grid grid-cols-1 sm:grid-cols-6 gap-4">
            <div class="col-span-1 sm:col-span-2">
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="handleSearch"
                        type="text" 
                        placeholder="Search subjects..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Session / Academic Year</label>
                <select 
                    v-model="filters.academic_year_id" 
                    @change="handleAcademicYearChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Sessions</option>
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }} <span v-if="year.is_current">(Current)</span>
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class</label>
                <select 
                    v-model="filters.class_id" 
                    @change="fetchFilterSections"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Classes</option>
                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section</label>
                <select 
                    v-model="filters.section_id" 
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Sections</option>
                    <option v-for="sec in filterSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <select 
                    v-model="filters.status" 
                    @change="fetchSubjects"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="subjects.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                No subjects defined yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Subject Code</th>
                            <th class="p-4">Subject Name</th>
                            <th class="p-4">Session / Academic Year</th>
                            <th class="p-4">Assigned Class/Section</th>
                            <th class="p-4">Evaluation Type</th>
                            <th class="p-4">Subject Category</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="subj in subjects" :key="subj.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700/60 text-slate-655 dark:text-slate-300 font-mono">
                                    {{ subj.code || 'N/A' }}
                                </span>
                            </td>
                            <td class="p-4 font-semibold text-slate-800 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <span>{{ subj.name }}</span>
                                    <span v-if="subj.is_optional" class="px-1.5 py-0.5 text-[9px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-md">
                                        Optional
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 font-normal truncate max-w-xs">{{ subj.description || 'No description' }}</div>
                            </td>
                            <td class="p-4 font-medium text-slate-600 dark:text-slate-400">
                                {{ subj.academic_year?.title || 'N/A' }}
                            </td>
                            <td class="p-4">
                                <span v-if="subj.class" class="px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/40">
                                    {{ subj.class?.name }} - {{ subj.section?.name || 'All' }}
                                </span>
                                <span v-else class="text-xs text-slate-400 italic">Unassigned</span>
                            </td>
                            <td class="p-4">
                                <span class="capitalize px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 text-slate-700 dark:text-slate-300">
                                    {{ subj.evaluation_type }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="capitalize px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 text-slate-700 dark:text-slate-300">
                                    {{ subj.subject_category ? subj.subject_category.replace('_', ' ') : 'N/A' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <button 
                                    v-if="authStore.hasPermission('subject.edit') && isCurrentYear"
                                    @click="toggleStatus(subj)"
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize border transition-all active:scale-95',
                                        subj.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25 hover:bg-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25 hover:bg-rose-500/25'
                                    ]"
                                >
                                    {{ subj.status }}
                                </button>
                                <span 
                                    v-else
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize border',
                                        subj.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25'
                                    ]"
                                >
                                    {{ subj.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit -->
                                    <button 
                                        v-if="authStore.hasPermission('subject.edit') && isCurrentYear"
                                        @click="openModal(subj)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Subject"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('subject.delete') && isCurrentYear"
                                        @click="handleDelete(subj)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Subject"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="border-t border-slate-200 dark:border-slate-800 p-4 flex items-center justify-between">
                <button 
                    :disabled="currentPage === 1"
                    @click="changePage(currentPage - 1)"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-slate-700 dark:text-slate-300"
                >
                    Previous
                </button>
                <span class="text-xs text-slate-500">Page {{ currentPage }} of {{ totalPages }}</span>
                <button 
                    :disabled="currentPage === totalPages"
                    @click="changePage(currentPage + 1)"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-slate-700 dark:text-slate-300"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl animate-fade-in max-h-[calc(100vh-2rem)] flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ editingId ? 'Edit Subject' : 'Add Subject' }}</h3>
                    <button @click="closeModal" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveSubject" class="flex flex-col flex-1 min-h-0">
                    <!-- Form Body (Scrollable) -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Year <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.academic_year_id"
                                    required
                                    @change="handleModalAcademicYearChange"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.title }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Class <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.class_id"
                                    required
                                    @change="fetchModalSections"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="">Select Class</option>
                                    <option v-for="cls in modalClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Section</label>
                                <select 
                                    v-model="form.section_id"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="">All Sections (Common Subject)</option>
                                    <option v-for="sec in modalSections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Subject Code</label>
                                <input 
                                    v-model="form.code"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    placeholder="e.g. MATH101"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Subject Name <span class="text-rose-500">*</span></label>
                                <input 
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    placeholder="e.g. Mathematics"
                                />
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Description</label>
                                <textarea 
                                    v-model="form.description"
                                    rows="2"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    placeholder="Subject syllabus or information..."
                                ></textarea>
                            </div>

                            <div class="col-span-2 border-t border-slate-100 dark:border-slate-800/80 pt-4 mt-2">
                                <h4 class="text-xs font-bold text-indigo-650 dark:text-indigo-400 uppercase tracking-wider mb-3">Examination & Grading Configuration</h4>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Evaluation Type</label>
                                <select 
                                    v-model="form.evaluation_type"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="marks">Marks Based</option>
                                    <option value="grades">Grades Based</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Subject Category</label>
                                <select 
                                    v-model="form.subject_category"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="scholastic">Scholastic (Academic)</option>
                                    <option value="co_scholastic">Co-Scholastic (Co-curricular)</option>
                                </select>
                            </div>

                            <template v-if="form.evaluation_type === 'marks'">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Max Marks</label>
                                    <input 
                                        v-model.number="form.maximum_marks"
                                        type="number"
                                        min="1"
                                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Passing Marks</label>
                                    <input 
                                        v-model.number="form.passing_marks"
                                        type="number"
                                        min="1"
                                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                    />
                                </div>
                            </template>


                            <div v-if="form.evaluation_type === 'grades'" class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Associated Grades / Scale</label>
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 max-h-40 overflow-y-auto">
                                    <div v-for="scale in gradeScales" :key="scale.id" class="flex items-center gap-2">
                                        <input 
                                            type="checkbox" 
                                            :id="'grade_' + scale.id" 
                                            :value="scale.id" 
                                            v-model="form.grade_scale_id"
                                            class="rounded border-slate-300 dark:border-slate-800 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-900 cursor-pointer"
                                        />
                                        <label :for="'grade_' + scale.id" class="text-sm text-slate-700 dark:text-slate-350 cursor-pointer select-none">
                                            {{ scale.grade }}
                                        </label>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-450 dark:text-slate-500 mt-1.5">Select the grades that belong to this subject's specific scale. If no grades are selected, the general school grade scale will be used.</p>
                            </div>

                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status</label>
                                <select 
                                    v-model="form.status"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="col-span-1 flex items-end pb-2">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.is_optional"
                                        class="rounded border-slate-300 dark:border-slate-800 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-900 cursor-pointer"
                                    />
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Is Optional Subject?</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions (Fixed) -->
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 flex-shrink-0 bg-slate-50/50 dark:bg-slate-900/60">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-slate-655 dark:text-slate-350 text-sm font-semibold transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/10 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save Subject' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';

const authStore = useAuthStore();
const confirmStore = useConfirmStore();

const subjects = ref([]);
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);

const academicYears = ref([]);
const classes = ref([]);
const filterSections = ref([]);
const modalClasses = ref([]);
const modalSections = ref([]);
const gradeScales = ref([]);
let isModalLoading = false;

const isCurrentYear = computed(() => {
    if (!filters.academic_year_id) return true;
    const selected = academicYears.value.find(y => y.id === filters.academic_year_id);
    return selected ? !!selected.is_current : false;
});

const currentPage = ref(1);
const totalPages = ref(1);

const filters = reactive({
    academic_year_id: '',
    search: '',
    class_id: '',
    section_id: '',
    status: ''
});

const form = ref({
    academic_year_id: '',
    class_id: '',
    section_id: '',
    name: '',
    code: '',
    description: '',
    status: 'active',
    is_optional: false,
    evaluation_type: 'marks',
    subject_category: 'scholastic',
    maximum_marks: 100,
    passing_marks: 35,
    grade_scale_id: []
});

let searchTimeout = null;

const fetchAcademicYears = async () => {
    try {
        const response = await window.axios.get('/api/academic-years');
        academicYears.value = response.data.academic_years;
        const currentYear = academicYears.value.find(y => y.is_current);
        if (currentYear) {
            form.value.academic_year_id = currentYear.id;
            filters.academic_year_id = currentYear.id;
        }
    } catch (e) {
        window.toastr?.error('Failed to load academic sessions.');
    }
};

const fetchClasses = async () => {
    try {
        const params = { all: true };
        if (filters.academic_year_id) {
            params.academic_year_id = filters.academic_year_id;
        }
        const response = await window.axios.get('/api/classes', { params });
        classes.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const handleAcademicYearChange = async () => {
    filters.class_id = '';
    filters.section_id = '';
    filterSections.value = [];
    await fetchClasses();
    fetchSubjects();
};

const fetchGradeScales = async () => {
    try {
        const response = await window.axios.get('/api/grade-scales', { params: { all: true } });
        gradeScales.value = response.data.grade_scales || [];
    } catch (e) {
        // Soft fail
    }
};

const fetchFilterSections = async () => {
    if (!filters.class_id) {
        filterSections.value = [];
        filters.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: filters.class_id, all: true } });
        filterSections.value = response.data.sections || response.data;
        filters.section_id = '';
        fetchSubjects();
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const fetchModalSections = async () => {
    if (!form.value.class_id) {
        modalSections.value = [];
        form.value.section_id = '';
        return;
    }
    try {
        const response = await window.axios.get('/api/sections', { params: { class_id: form.value.class_id, all: true } });
        modalSections.value = response.data.sections || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load sections.');
    }
};

const fetchModalClasses = async () => {
    if (!form.value.academic_year_id) {
        modalClasses.value = [];
        return;
    }
    try {
        const response = await window.axios.get('/api/classes', {
            params: {
                all: true,
                academic_year_id: form.value.academic_year_id
            }
        });
        modalClasses.value = response.data.classes || response.data;
    } catch (e) {
        window.toastr?.error('Failed to load classes.');
    }
};

const handleModalAcademicYearChange = async () => {
    if (isModalLoading) return;
    await fetchModalClasses();
    form.value.class_id = '';
    form.value.section_id = '';
    modalSections.value = [];
};

const fetchSubjects = async () => {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            academic_year_id: filters.academic_year_id,
            search: filters.search,
            class_id: filters.class_id,
            section_id: filters.section_id,
            status: filters.status
        };
        const response = await window.axios.get('/api/subjects', { params });
        subjects.value = response.data.data;
        totalPages.value = response.data.last_page;
    } catch (e) {
        window.toastr?.error('Failed to load subjects.');
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchSubjects();
    }, 300);
};

const changePage = (page) => {
    currentPage.value = page;
    fetchSubjects();
};

const toggleStatus = async (subj) => {
    try {
        const response = await window.axios.patch(`/api/subjects/${subj.id}/toggle-status`);
        subj.status = response.data.subject.status;
        window.toastr?.success(response.data.message || 'Status updated successfully.');
    } catch (e) {
        window.toastr?.error('Failed to update status.');
    }
};

const openModal = async (subj = null) => {
    isModalLoading = true;
    if (subj) {
        editingId.value = subj.id;

        let selectedGrades = [];
        if (subj.grade_scale_id) {
            if (Array.isArray(subj.grade_scale_id)) {
                selectedGrades = subj.grade_scale_id.map(id => Number(id));
            } else if (typeof subj.grade_scale_id === 'string') {
                try {
                    const parsed = JSON.parse(subj.grade_scale_id);
                    selectedGrades = Array.isArray(parsed) ? parsed.map(id => Number(id)) : [Number(subj.grade_scale_id)];
                } catch (e) {
                    selectedGrades = subj.grade_scale_id.split(',').map(id => Number(id.trim())).filter(id => !isNaN(id));
                }
            } else {
                selectedGrades = [Number(subj.grade_scale_id)];
            }
        }

        form.value = {
            academic_year_id: subj.academic_year_id || '',
            class_id: subj.class_id || '',
            section_id: subj.section_id || '',
            name: subj.name,
            code: subj.code || '',
            description: subj.description || '',
            status: subj.status,
            is_optional: !!subj.is_optional,
            evaluation_type: subj.evaluation_type || 'marks',
            subject_category: subj.subject_category || 'scholastic',
            maximum_marks: subj.maximum_marks ?? 100,
            passing_marks: subj.passing_marks ?? 35,
            grade_scale_id: selectedGrades
        };
        await fetchModalClasses();
        if (subj.class_id) {
            await fetchModalSections();
        }
        form.value.class_id = subj.class_id || '';
        form.value.section_id = subj.section_id || '';
    } else {
        editingId.value = null;
        const currentYear = academicYears.value.find(y => y.is_current);
        const defaultYearId = filters.academic_year_id || (currentYear ? currentYear.id : '');
        form.value = {
            academic_year_id: defaultYearId,
            class_id: '',
            section_id: '',
            name: '',
            code: '',
            description: '',
            status: 'active',
            is_optional: false,
            evaluation_type: 'marks',
            subject_category: 'scholastic',
            maximum_marks: 100,
            passing_marks: 35,
            grade_scale_id: []
        };
        await fetchModalClasses();
        modalSections.value = [];
    }
    isModalLoading = false;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    editingId.value = null;
};

const saveSubject = async () => {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (!payload.section_id) {
            payload.section_id = null;
        }
        if (payload.evaluation_type === 'grades') {
            payload.maximum_marks = null;
            payload.passing_marks = null;
        } else {
            payload.grade_scale_id = [];
        }
        if (editingId.value) {
            await window.axios.put(`/api/subjects/${editingId.value}`, payload);
            window.toastr?.success('Subject updated successfully.');
        } else {
            await window.axios.post('/api/subjects', payload);
            window.toastr?.success('Subject created successfully.');
        }
        closeModal();
        fetchSubjects();
    } catch (e) {
        if (e.response?.status === 422) {
            const errors = Object.values(e.response.data.errors).flat().join('\n');
            window.toastr?.error(errors);
        } else {
            window.toastr?.error('An error occurred while saving.');
        }
    } finally {
        saving.value = false;
    }
};

const handleDelete = async (subj) => {
    const confirmed = await confirmStore.show({
        title: 'Delete Subject',
        message: `Are you sure you want to delete "${subj.name}"?`,
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel'
    });

    if (confirmed) {
        try {
            await window.axios.delete(`/api/subjects/${subj.id}`);
            window.toastr?.success('Subject deleted successfully.');
            fetchSubjects();
        } catch (e) {
            window.toastr?.error('Failed to delete subject.');
        }
    }
};

onMounted(async () => {
    await fetchAcademicYears();
    await fetchClasses();
    fetchGradeScales();
    fetchSubjects();
});
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
