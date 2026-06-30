<template>
    <div v-if="loading" class="p-12 text-center animate-pulse space-y-4 max-w-4xl mx-auto">
        <div class="w-24 h-24 rounded-full bg-slate-200 dark:bg-slate-800 mx-auto"></div>
        <div class="h-6 w-48 bg-slate-200 dark:bg-slate-800 mx-auto rounded-lg"></div>
        <div class="h-4 w-72 bg-slate-200 dark:bg-slate-800 mx-auto rounded-lg"></div>
    </div>

    <div v-else-if="!student" class="p-12 text-center text-slate-500 max-w-4xl mx-auto">
        Student profile not found.
    </div>

    <div v-else class="max-w-4xl mx-auto space-y-6">
        <!-- Profile Overview Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="flex items-center gap-4">
                <!-- Photo -->
                <div class="w-20 h-20 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 flex items-center justify-center font-black text-2xl text-slate-500 overflow-hidden shadow-inner shrink-0">
                    <img v-if="student.photo" :src="student.photo" class="object-cover w-full h-full" />
                    <span v-else>{{ student.first_name.substring(0, 1) }}{{ student.last_name.substring(0, 1) }}</span>
                </div>
                <!-- Profile details -->
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl font-extrabold text-slate-850 dark:text-white leading-tight">
                            {{ student.first_name }} {{ student.last_name }}
                        </h2>
                        <span :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                            student.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 border border-rose-500/20'
                        ]">
                            {{ student.status }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Adm No: <span class="font-bold text-slate-700 dark:text-slate-200">{{ student.admission_no }}</span>
                        <span v-if="student.gr_no" class="ml-3">
                            GR No: <span class="font-bold text-slate-700 dark:text-slate-200">{{ student.gr_no }}</span>
                        </span>
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500" v-if="currentPlacement">
                        Placed In: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ currentPlacement.class?.name }} — {{ currentPlacement.section?.name }}</span>
                        <span class="ml-2 font-medium" v-if="currentPlacement.roll_no">(Roll: {{ currentPlacement.roll_no }})</span>
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 w-full md:w-auto">
                <router-link 
                    v-if="authStore.hasPermission('student.edit') && isCurrentYear"
                    :to="`/students/${student.id}/edit`"
                    class="flex-1 md:flex-none px-4 py-2 border border-indigo-200 dark:border-indigo-900 bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit Profile
                </router-link>
                <router-link 
                    to="/students"
                    class="flex-1 md:flex-none px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors text-center"
                >
                    Back to List
                </router-link>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 flex items-center gap-2 overflow-x-auto print:hidden">
            <button 
                v-for="tab in tabList" 
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 transition-all shrink-0',
                    activeTab === tab.id 
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                ]"
            >
                {{ tab.title }}
            </button>
        </div>

        <!-- TAB CONTENT AREA -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            
            <!-- Tab 1: Personal Profile Details -->
            <div v-show="activeTab === 'personal'" class="space-y-6">
                <!-- Section: Personal Identity -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">Personal Identity</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Date of Birth</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.date_of_birth ? formatDate(student.date_of_birth) : 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Gender</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.gender }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Blood Group</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.blood_group || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Aadhaar Card No</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.aadhaar_no || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">PEN (UDISE+)</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.pen_no || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">National UDISE ID</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.udise_no || 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Section: Caste & Demographics -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">Demographics & Caste Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Caste Category</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.category || 'General' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Religion</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.religion || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Nationality</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.nationality || 'Indian' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">House Assignment</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                <span v-if="student.house" :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border',
                                    student.house === 'Red' ? 'bg-red-500/10 text-red-600 border-red-500/20' : '',
                                    student.house === 'Green' ? 'bg-green-500/10 text-green-600 border-green-500/20' : '',
                                    student.house === 'Blue' ? 'bg-blue-500/10 text-blue-600 border-blue-500/20' : '',
                                    student.house === 'Yellow' ? 'bg-yellow-500/10 text-yellow-600 border-yellow-500/20' : ''
                                ]">{{ student.house }} House</span>
                                <span v-else>None</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Communication & Address -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">Contact Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Student Mobile</div>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.mobile || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Student Email</div>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.email || 'N/A' }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Residential Address</div>
                            <div class="text-sm font-semibold text-slate-850 dark:text-slate-200 mt-0.5 whitespace-pre-line leading-relaxed">{{ student.address || 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Section: Emergency Contact -->
                <div>
                    <h3 class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-widest border-b border-rose-500/10 pb-2 mb-4">Emergency Contact Person</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 p-4 border border-rose-500/10 rounded-xl bg-rose-500/[0.02]">
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Contact Name</div>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.emergency_contact_name || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Contact Mobile</div>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.emergency_contact_mobile || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Contact Email</div>
                            <div class="text-sm font-semibold text-slate-850 dark:text-slate-200 mt-0.5">{{ student.emergency_contact_email || 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Parent Profile Details -->
            <div v-show="activeTab === 'parents'" class="space-y-6">
                <div v-if="!student.parent" class="text-center p-6 text-slate-500">
                    No parent/guardian information has been recorded for this student.
                </div>
                
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Father -->
                    <div class="p-5 border border-slate-100 dark:border-slate-800 rounded-xl space-y-4 bg-slate-50/50 dark:bg-slate-900/40">
                        <h4 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-150 dark:border-slate-800 pb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Father Details
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <div class="text-[10px] text-slate-450 uppercase font-semibold">Father Name</div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ student.parent.father_name || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">Father Mobile</div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ student.parent.father_mobile || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">Father Email</div>
                                <div class="text-sm font-semibold text-slate-700 dark:text-slate-350 mt-0.5">{{ student.parent.father_email || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mother --                    <div class="p-5 border border-slate-100 dark:border-slate-800 rounded-xl space-y-4 bg-slate-50/50 dark:bg-slate-900/40">
                        <h4 class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-150 dark:border-slate-800 pb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Mother Details
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <div class="text-[10px] text-slate-450 uppercase font-semibold">Mother Name</div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ student.parent.mother_name || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">Mother Mobile</div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ student.parent.mother_mobile || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">Mother Email</div>
                                <div class="text-sm font-semibold text-slate-700 dark:text-slate-350 mt-0.5">{{ student.parent.mother_email || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>v>

                    <!-- Guardian details (Alternative) -->
                    <div class="p-5 border border-slate-100 dark:border-slate-800 rounded-xl space-y-4 bg-slate-50/50 dark:bg-slate-900/40 md:col-span-2">
                        <h4 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-150 dark:border-slate-800 pb-2">
                            Guardian Details (Alternative Placement Contact)
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] text-slate-450 uppercase font-semibold">Guardian Name</div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ student.parent.guardian_name || 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">Guardian Mobile</div>
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ student.parent.guardian_mobile || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Academic Timeline & History -->
            <div v-show="activeTab === 'academic'" class="space-y-6">
                <!-- Previous School Info -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">Previous School Transfer History</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 p-4 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50">
                        <div>
                            <div class="text-[10px] text-slate-450 font-bold uppercase">Previous School Name</div>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.previous_school_name || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-450 font-bold uppercase">T.C. Number (Transfer Certificate)</div>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ student.previous_school_tc_no || 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">T.C. Issue Date</div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                {{ student.previous_school_tc_date ? formatDate(student.previous_school_tc_date) : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic History Placement -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">Enrollment & Placements History</h3>
                    
                    <div class="relative border-l-2 border-indigo-100 dark:border-slate-800 ml-4 space-y-6 py-2">
                        <div 
                            v-for="rec in student.academic_records" 
                            :key="rec.id"
                            class="relative pl-6"
                        >
                            <!-- Circle marker -->
                            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-white dark:border-slate-900 bg-indigo-600 shadow-sm"></span>

                            <div class="bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-150 dark:border-slate-800">
                                <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block mb-1">
                                    Session: {{ rec.academic_year?.title }}
                                </span>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                    <div>
                                        <span class="text-slate-400 block">Class</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ rec.class?.name || 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Section</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ rec.section?.name || 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Roll Number</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ rec.roll_no || 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Placement Status</span>
                                        <span class="font-semibold text-emerald-600 capitalize">{{ rec.status }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Document Manager (NEW) -->
            <div v-show="activeTab === 'documents'" class="space-y-6">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Student Uploaded Documents</h3>
                    <button 
                        v-if="authStore.hasPermission('student.edit') && isCurrentYear"
                        @click="openUploadModal"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-[11px] font-bold rounded-lg shadow-sm transition-all flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Upload Document
                    </button>
                </div>

                <div v-if="!student.documents || student.documents.length === 0" class="text-center py-10 text-slate-500 text-xs">
                    No documents (e.g. Birth certificate, TC, Aadhaar Card copy) have been uploaded for this student yet.
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div 
                        v-for="doc in student.documents" 
                        :key="doc.id" 
                        class="p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-950/20 flex justify-between items-center"
                    >
                        <div class="flex items-center gap-3 overflow-hidden">
                            <!-- Doc Icon -->
                            <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-slate-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="text-xs font-bold text-slate-850 dark:text-slate-200 truncate leading-snug">{{ doc.document_name }}</h4>
                                <span class="text-[9px] text-slate-400 block mt-0.5">Uploaded on: {{ formatDate(doc.created_at) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <!-- View/Preview Modal -->
                            <button 
                                @click="openPreviewModal(doc)"
                                class="p-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-indigo-600 dark:text-indigo-400 rounded-lg border border-indigo-200/50 dark:border-slate-700/60 transition-colors"
                                title="Preview Document"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>

                            <!-- Direct Download -->
                            <a 
                                :href="`/${doc.document_path}`" 
                                :download="doc.document_name"
                                class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-350 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                title="Download Document File"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </a>

                            <!-- Delete -->
                            <button 
                                v-if="authStore.hasPermission('student.edit') && isCurrentYear"
                                @click="handleDeleteDocument(doc)"
                                class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                title="Delete Document"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Document Upload Modal -->
        <div v-if="uploadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-sm">Upload Student Document</h3>
                    <button @click="closeUploadModal" class="p-1 text-slate-400 hover:text-slate-655 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="submitDocumentUpload" class="p-6 space-y-4">
                    <!-- Document Name Preset / Custom -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Document Type <span class="text-rose-500">*</span></label>
                        <select 
                            v-model="selectedDocType"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-850 dark:text-slate-200 focus:outline-none"
                        >
                            <option value="Birth Certificate">Birth Certificate</option>
                            <option value="Transfer Certificate (T.C.)">Transfer Certificate (T.C.)</option>
                            <option value="Aadhaar Card Copy">Aadhaar Card Copy</option>
                            <option value="Report Card">Previous Report Card</option>
                            <option value="Caste Certificate">Caste Certificate</option>
                            <option value="Passport Copy">Passport Copy</option>
                            <option value="custom">Other (Type custom name...)</option>
                        </select>

                        <!-- Custom Name Text Field -->
                        <div v-if="selectedDocType === 'custom'" class="space-y-1 mt-2">
                            <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase">Specify Custom Document Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="customDocName" 
                                type="text" 
                                required 
                                placeholder="e.g. Migration Certificate"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- File Select -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Select File <span class="text-rose-500">*</span></label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full min-h-[112px] border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-950/20 transition-colors p-4">
                                <div class="flex flex-col items-center justify-center w-full">
                                    <!-- Image Preview Thumbnail -->
                                    <div v-if="newDocPreviewUrl" class="w-16 h-16 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm mb-2 shrink-0">
                                        <img :src="newDocPreviewUrl" class="w-full h-full object-cover" />
                                    </div>
                                    <!-- PDF Icon Preview -->
                                    <div v-else-if="newDocFile && newDocFile.type === 'application/pdf'" class="w-12 h-12 rounded-lg bg-rose-50 dark:bg-rose-950/20 flex items-center justify-center text-rose-600 dark:text-rose-450 mb-2 shrink-0 border border-rose-200/50">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <svg v-else-if="!newDocFile" class="w-6 h-6 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 11-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>

                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 text-center"><span class="font-bold text-indigo-600">Choose document</span> or drag & drop</p>
                                    <p class="text-[8px] text-slate-400 mt-0.5 text-center">PDF, PNG, JPG (Max 4MB)</p>
                                    <p v-if="newDocFileName" class="text-[10px] font-bold text-emerald-600 mt-2 truncate max-w-full text-center">{{ newDocFileName }}</p>
                                </div>
                                <input @change="onDocFileSelected" type="file" class="hidden" accept=".pdf,image/*" />
                            </label>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end gap-2 pt-2">
                        <button 
                            @click="closeUploadModal"
                            type="button" 
                            class="px-3.5 py-2 text-xs font-bold text-slate-655 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="uploadingDoc || !newDocFile"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl active:scale-95 disabled:opacity-50 transition-all"
                        >
                            {{ uploadingDoc ? 'Uploading...' : 'Confirm Upload' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Document Preview Modal (NEW) -->
        <div v-if="previewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-3xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/20">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Document Preview: {{ activePreviewDoc?.document_name }}
                    </h3>
                    <button @click="closePreviewModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Preview Area -->
                <div class="p-6 bg-slate-100/50 dark:bg-slate-950/40 flex items-center justify-center min-h-[300px] overflow-auto">
                    <!-- If Image -->
                    <div v-if="isPreviewImage" class="max-w-full flex justify-center">
                        <img 
                            :src="`/${activePreviewDoc.document_path}`" 
                            class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-sm border border-slate-200 dark:border-slate-800" 
                        />
                    </div>
                    <!-- If PDF -->
                    <div v-else-if="isPreviewPDF" class="w-full">
                        <iframe 
                            :src="`/${activePreviewDoc.document_path}`" 
                            class="w-full h-[60vh] rounded-xl border border-slate-200 dark:border-slate-800 bg-white" 
                            frameborder="0"
                        ></iframe>
                    </div>
                    <!-- Fallback / Unsupported preview -->
                    <div class="text-center py-10 text-slate-500 text-xs">
                        Preview is not supported for this file format. 
                        <a :href="`/${activePreviewDoc?.document_path}`" :download="activePreviewDoc?.document_name" class="text-indigo-600 dark:text-indigo-400 font-bold underline mt-2 block">Download to view</a>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/20">
                    <span class="text-[10px] text-slate-400">Uploaded on: {{ formatDate(activePreviewDoc?.created_at) }}</span>
                    <div class="flex items-center gap-2">
                        <a 
                            :href="`/${activePreviewDoc?.document_path}`" 
                            :download="activePreviewDoc?.document_name"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-650 text-white text-xs font-bold rounded-xl active:scale-95 transition-all flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download File
                        </a>
                        <button 
                            @click="closePreviewModal" 
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-350 transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'StudentShow',
    setup() {
        const route = useRoute();
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();

        const student = ref(null);
        const loading = ref(true);
        const activeTab = ref('personal');

        const tabList = [
            { id: 'personal', title: 'Personal Details' },
            { id: 'parents', title: 'Parent Details' },
            { id: 'academic', title: 'Academic & History' },
            { id: 'documents', title: 'Document Manager' }
        ];

        // Academic placement
        const currentPlacement = computed(() => {
            if (student.value && student.value.academic_records && student.value.academic_records.length > 0) {
                return student.value.academic_records[0];
            }
            return null;
        });

        const isCurrentYear = computed(() => {
            if (!currentPlacement.value || !currentPlacement.value.academic_year) return true;
            return !!currentPlacement.value.academic_year.is_current;
        });

        // Document uploading modal states
        const uploadModalOpen = ref(false);
        const selectedDocType = ref('Birth Certificate');
        const customDocName = ref('');
        const newDocFile = ref(null);
        const newDocFileName = ref('');
        const newDocPreviewUrl = ref('');
        const uploadingDoc = ref(false);

        // Document preview states
        const previewModalOpen = ref(false);
        const activePreviewDoc = ref(null);

        const openPreviewModal = (doc) => {
            activePreviewDoc.value = doc;
            previewModalOpen.value = true;
        };

        const closePreviewModal = () => {
            previewModalOpen.value = false;
            activePreviewDoc.value = null;
        };

        const isPreviewImage = computed(() => {
            if (!activePreviewDoc.value || !activePreviewDoc.value.document_path) return false;
            const path = activePreviewDoc.value.document_path.toLowerCase();
            return path.endsWith('.png') || 
                   path.endsWith('.jpg') || 
                   path.endsWith('.jpeg') || 
                   path.endsWith('.gif') || 
                   path.endsWith('.webp') || 
                   path.endsWith('.svg') || 
                   path.endsWith('.bmp') || 
                   path.endsWith('.jfif') ||
                   path.endsWith('.avif') ||
                   path.endsWith('.heic') ||
                   path.endsWith('.heif') ||
                   path.endsWith('.pjpeg') ||
                   path.endsWith('.pjpg');
        });

        const isPreviewPDF = computed(() => {
            if (!activePreviewDoc.value || !activePreviewDoc.value.document_path) return false;
            const path = activePreviewDoc.value.document_path.toLowerCase();
            return path.endsWith('.pdf');
        });

        const fetchStudentProfile = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get(`/api/students/${route.params.id}`);
                student.value = response.data.student;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load student profile.');
            } finally {
                loading.value = false;
            }
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        };

        // Document Actions
        const openUploadModal = () => {
            uploadModalOpen.value = true;
            selectedDocType.value = 'Birth Certificate';
            customDocName.value = '';
            newDocFile.value = null;
            newDocFileName.value = '';
            newDocPreviewUrl.value = '';
        };

        const closeUploadModal = () => {
            uploadModalOpen.value = false;
            newDocPreviewUrl.value = '';
        };

        const onDocFileSelected = (e) => {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 4 * 1024 * 1024) {
                    toastStore.error('Document size must be less than 4MB.');
                    return;
                }
                newDocFile.value = file;
                newDocFileName.value = file.name;

                if (file.type.startsWith('image/')) {
                    newDocPreviewUrl.value = URL.createObjectURL(file);
                } else {
                    newDocPreviewUrl.value = '';
                }
            }
        };

        const submitDocumentUpload = async () => {
            const finalName = selectedDocType.value === 'custom' ? customDocName.value : selectedDocType.value;
            if (!newDocFile.value || !finalName) return;
            uploadingDoc.value = true;

            const formData = new FormData();
            formData.append('document', newDocFile.value);
            formData.append('document_name', finalName);

            try {
                const response = await window.axios.post(`/api/students/${student.value.id}/documents`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                
                toastStore.success(response.data.message || 'Document uploaded successfully.');
                closeUploadModal();
                // Reload profile to refresh document listing
                await fetchStudentProfile();
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to upload document.');
            } finally {
                uploadingDoc.value = false;
            }
        };

        const handleDeleteDocument = async (doc) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Document',
                message: `Are you sure you want to permanently delete the document: "${doc.document_name}"? This action cannot be undone.`,
                type: 'danger',
                confirmText: 'Delete Document',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/students/${student.value.id}/documents/${doc.id}`);
                    toastStore.success('Document deleted successfully.');
                    // Reload profile to refresh list
                    await fetchStudentProfile();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete document.');
                }
            }
        };

        onMounted(() => {
            fetchStudentProfile();
            if (route.query.tab && tabList.some(t => t.id === route.query.tab)) {
                activeTab.value = route.query.tab;
            }
        });

        return {
            student,
            loading,
            activeTab,
            tabList,
            currentPlacement,
            isCurrentYear,
            authStore,
            formatDate,

            // Document manager actions
            uploadModalOpen,
            selectedDocType,
            customDocName,
            newDocFile,
            newDocFileName,
            newDocPreviewUrl,
            uploadingDoc,
            openUploadModal,
            closeUploadModal,
            onDocFileSelected,
            submitDocumentUpload,
            handleDeleteDocument,

            // Document preview variables & methods
            previewModalOpen,
            activePreviewDoc,
            openPreviewModal,
            closePreviewModal,
            isPreviewImage,
            isPreviewPDF
        };
    }
}
</script>
