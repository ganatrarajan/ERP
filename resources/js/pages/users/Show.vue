<template>
    <div v-if="loading" class="max-w-5xl mx-auto p-6 space-y-6 animate-pulse">
        <div class="h-40 bg-slate-100 dark:bg-slate-800 rounded-3xl"></div>
        <div class="h-10 w-96 bg-slate-150 dark:bg-slate-850 rounded-lg"></div>
        <div class="h-64 bg-slate-100 dark:bg-slate-800 rounded-3xl"></div>
    </div>

    <div v-else-if="!user" class="max-w-5xl mx-auto p-12 text-center text-slate-500">
        <p class="text-base font-bold text-slate-800 dark:text-white">Staff Member Profile Not Found</p>
        <router-link to="/users" class="mt-4 inline-block text-indigo-650 font-bold hover:underline">Back to Directory</router-link>
    </div>

    <div v-else class="max-w-5xl mx-auto space-y-6 animate-[fadeIn_0.2s_ease-out]">
        <!-- Breadcrumbs & Actions -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <router-link to="/users" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 rounded-xl transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </router-link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">Staff Member Profile</h1>
                    <p class="text-xs text-slate-500">Viewing file history and role config for {{ user.name }}.</p>
                </div>
            </div>

            <div class="flex gap-2">
                <router-link 
                    v-if="authStore.hasPermission('user.edit')"
                    :to="`/users/${user.id}/edit`"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-650/15 transition-all flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit Details
                </router-link>
            </div>
        </div>

        <!-- Profile Hero Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row gap-6 items-center">
            <!-- Avatar -->
            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center shrink-0">
                <img v-if="user.profile_photo" :src="'/' + user.profile_photo" class="w-full h-full object-cover" />
                <svg v-else class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>

            <!-- Profile Details -->
            <div class="text-center md:text-left space-y-2 w-full">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-white">{{ user.name }}</h2>
                        <p class="text-xs text-slate-400 font-semibold">{{ user.email }} | {{ user.mobile || 'No Phone' }}</p>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold border uppercase bg-indigo-50 dark:bg-indigo-500/10 text-indigo-650 dark:text-indigo-400 border-indigo-200">
                            {{ user.roles?.[0]?.name }}
                        </span>
                        <span :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border capitalize',
                            user.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-550/20' : 'bg-rose-500/10 text-rose-600 border-rose-550/20'
                        ]">
                            {{ user.status }}
                        </span>
                    </div>
                </div>

                <!-- Small Grid Info -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-3 border-t border-slate-100 dark:border-slate-850">
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Employee ID</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ user.employee_id || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Department</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ user.department || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Designation</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ user.designation || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Associated School</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ user.school?.name || 'SaaS Admin' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Tabs Control -->
        <div class="flex overflow-x-auto gap-2 p-1.5 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'px-4 py-2 text-xs font-bold rounded-xl transition-all whitespace-nowrap cursor-pointer',
                    activeTab === tab.id 
                        ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-white shadow-sm' 
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                ]"
            >
                {{ tab.name }}
            </button>
        </div>

        <!-- Tab Content Cards -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm min-h-[300px]">
            
            <!-- Tab 1: Basic Information -->
            <div v-if="activeTab === 'basic'" class="space-y-6">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">Basic Profile Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Full Name</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.name }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Email Address</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.email }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Mobile Phone</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.mobile || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Gender</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white capitalize">{{ user.gender || 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Date of Birth</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ formatDate(user.dob) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Aadhaar Card No</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.aadhaar_no || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">PAN Card No</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.pan_no || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">System Role</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.roles?.[0]?.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Employment Details -->
            <div v-if="activeTab === 'employment'" class="space-y-6">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">Employment & Address Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Emergency Info -->
                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Designation</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.designation || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Department</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.department || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Emergency Contact Person</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.emergency_contact_name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                            <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Emergency Phone Number</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.emergency_contact_mobile || 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Address Info -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex flex-col justify-start">
                        <span class="text-xs font-bold text-slate-500 uppercase mb-2">Residential Address</span>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 leading-relaxed">{{ user.address || 'No residential address has been saved.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Teacher-Specific Info (Only for Teachers) -->
            <div v-if="activeTab === 'teacher'" class="space-y-6">
                <!-- Credentials -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">Academic Teacher Credentials</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                                <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Teacher Code</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.teacher_code || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                                <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Joining Date</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white">{{ formatDate(user.joining_date) }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                                <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Employment Type</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.employment_type || 'N/A' }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                                <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Educational Qualifications</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white text-right max-w-xs">{{ user.qualification || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-100 dark:border-slate-850/60 pb-2.5">
                                <span class="text-xs text-slate-450 dark:text-slate-400 font-semibold">Total Experience</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.experience || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignments List -->
                <div class="space-y-4 pt-4">
                    <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-850 pb-2">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Class, Section & Subject Assignments</h3>
                        <button 
                            @click="openAssignmentModal"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            Add Assignment
                        </button>
                    </div>

                    <div v-if="user.assignments?.length === 0" class="p-8 text-center text-slate-400 bg-slate-50 dark:bg-slate-950 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                        No classes or subjects have been assigned to this teacher yet.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="assign in user.assignments" :key="assign.id" class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between">
                            <div class="space-y-1">
                                <h4 class="text-sm font-bold text-slate-805 dark:text-white flex items-center gap-2">
                                    {{ assign.class?.name }} - {{ assign.section?.name }}
                                    <span v-if="assign.is_class_teacher" class="px-1.5 py-0.5 rounded text-[8px] bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 font-bold uppercase">Class Teacher</span>
                                </h4>
                                <p class="text-xs text-slate-450 font-medium">Subject: <span class="font-semibold text-indigo-500">{{ assign.subject?.name || 'Generic / Admin Duties' }}</span></p>
                            </div>
                            
                            <button 
                                type="button"
                                @click="removeAssignment(assign.id)"
                                class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 dark:bg-rose-500/20 dark:hover:bg-rose-500/30 border border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400 rounded-lg transition-colors cursor-pointer border-none"
                                title="Remove Assignment"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Documents Upload & Management -->
            <div v-if="activeTab === 'documents'" class="space-y-6">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">Staff Record Documents</h3>
                
                <!-- Document Upload Form -->
                <form @submit.prevent="uploadDocument" class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Document Type</label>
                        <select 
                            v-model="selectedDocTemplate" 
                            required
                            class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-705 dark:text-slate-200 focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="" disabled>Choose Document Type...</option>
                            <option v-for="t in docTemplates" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>

                    <div v-if="selectedDocTemplate === 'custom'" class="w-full md:w-1/3 animate-[fadeIn_0.15s_ease-out]">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Custom Document Name</label>
                        <input 
                            type="text" 
                            v-model="customDocName" 
                            required
                            placeholder="Enter document title..."
                            class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>

                    <div class="w-full">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Select File</label>
                        <input 
                            type="file" 
                            ref="docFileInput"
                            required
                            @change="onDocFileChange"
                            class="w-full px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>
                    
                    <button 
                        type="submit" 
                        :disabled="uploadingDoc"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-650 dark:hover:bg-indigo-600 text-white font-bold text-xs rounded-xl shadow-md transition-all whitespace-nowrap shrink-0 disabled:opacity-50 cursor-pointer border-none"
                    >
                        {{ uploadingDoc ? 'Uploading...' : 'Upload File' }}
                    </button>
                </form>

                <!-- Documents List -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-slate-450 uppercase tracking-wider">Uploaded Documents Directory</h4>
                    
                    <div v-if="user.documents?.length === 0" class="p-6 text-center text-slate-400 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50">
                        No documents are currently uploaded for this staff member.
                    </div>

                    <div v-else class="space-y-2">
                        <div v-for="doc in user.documents" :key="doc.id" class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <!-- File Icon placeholder -->
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                    {{ getFileExt(doc.document_path) }}
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800 dark:text-white">{{ doc.document_name }}</h5>
                                    <p class="text-[10px] text-slate-400 font-semibold">Uploaded on: {{ new Date(doc.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    type="button"
                                    @click="previewDocument(doc)"
                                    class="p-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-lg border border-indigo-150 dark:border-indigo-900/50 transition-colors cursor-pointer border-none"
                                    title="Quick Preview"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>

                                <a 
                                    :href="'/' + doc.document_path" 
                                    target="_blank"
                                    class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-250 dark:border-slate-750 transition-colors"
                                    title="Open / Download"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </a>

                                <button 
                                    type="button"
                                    @click="deleteDocument(doc.id)"
                                    class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 dark:bg-rose-500/25 dark:hover:bg-rose-500/35 border border-rose-250 dark:border-rose-900/50 text-rose-600 dark:text-rose-455 rounded-lg transition-colors cursor-pointer border-none"
                                    title="Delete File"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Activity Logs -->
            <div v-if="activeTab === 'activity'" class="space-y-6">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">Profile Log History</h3>
                
                <div class="space-y-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
                        <div class="flex justify-between border-b border-slate-200/50 dark:border-slate-850/60 pb-2">
                            <span class="text-xs text-slate-400 font-semibold">Account Created On</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ new Date(user.created_at).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200/50 dark:border-slate-850/60 pb-2">
                            <span class="text-xs text-slate-400 font-semibold">Profile Last Updated On</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ new Date(user.updated_at).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between pb-1">
                            <span class="text-xs text-slate-400 font-semibold">Last Active Connection</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-white">{{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never logged in' }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Teacher Assignment Modal Workflow -->
        <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl animate-[modalIn_0.18s_ease-out] space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-850 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        New Teacher Assignment
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>

                <div v-if="modalError" class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-600 rounded-xl text-xs font-semibold">
                    {{ modalError }}
                </div>

                <div class="space-y-3.5">
                    <!-- Academic Year selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider mb-1">Academic Session</label>
                        <select v-model="assignForm.academic_year_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                            <option value="">Select Academic Session...</option>
                            <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.title }}</option>
                        </select>
                    </div>

                    <!-- Class Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Select Class</label>
                        <select v-model="assignForm.class_id" @change="loadModalSections" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                            <option value="">Choose Class...</option>
                            <option v-for="c in modalClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <!-- Section Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Select Section</label>
                        <select v-model="assignForm.section_id" @change="loadModalSubjects" :disabled="!assignForm.class_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                            <option value="">All Sections (For Class Teacher designation)</option>
                            <option v-for="s in modalSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <!-- Subject Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-555 dark:text-slate-400 uppercase tracking-wider mb-1">Select Subject (Optional)</label>
                        <select v-model="assignForm.subject_id" :disabled="!assignForm.section_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                            <option :value="null">No Subject (Class Teacher / Admin Only)</option>
                            <option v-for="sub in modalSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                        </select>
                    </div>

                    <!-- Class Teacher Status -->
                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                            <input type="checkbox" v-model="assignForm.is_class_teacher" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                            Designate as Class Teacher
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-850">
                    <button 
                        @click="showModal = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-all border-none cursor-pointer"
                    >
                        {{ savedCount > 0 ? 'Close & Finish' : 'Cancel' }}
                    </button>
                    <button 
                        @click="saveAssignment"
                        :disabled="submittingAssignment"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition-all disabled:opacity-50 border-none cursor-pointer"
                    >
                        {{ submittingAssignment ? 'Saving...' : 'Save Assignment' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Document Preview Modal -->
        <div v-if="previewDoc" class="fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-4xl w-full p-6 shadow-2xl animate-[modalIn_0.18s_ease-out] flex flex-col max-h-[90vh]">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-850 pb-3 mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Preview: {{ previewDoc.document_name }}
                        </h3>
                        <p class="text-[10px] text-slate-405 font-semibold mt-0.5">File format: {{ getFileExt(previewDoc.document_path).toUpperCase() }}</p>
                    </div>
                    <button @click="previewDoc = null" class="text-slate-400 hover:text-slate-600 cursor-pointer border-none bg-transparent"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>

                <!-- Preview Content -->
                <div class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-950 rounded-2xl p-2 border border-slate-150 dark:border-slate-850 flex items-center justify-center min-h-[400px]">
                    <!-- Image Preview -->
                    <img 
                        v-if="isImageFile(previewDoc.document_path)" 
                        :src="'/' + previewDoc.document_path" 
                        class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-md" 
                    />
                    
                    <!-- PDF Preview -->
                    <iframe 
                        v-else-if="isPdfFile(previewDoc.document_path)"
                        :src="'/' + previewDoc.document_path" 
                        class="w-full h-[60vh] rounded-lg border-none"
                    ></iframe>

                    <!-- Fallback -->
                    <div v-else class="text-center p-8 space-y-3">
                        <svg class="w-16 h-16 mx-auto text-slate-350" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Inline Preview Not Supported</p>
                        <p class="text-[11px] text-slate-400">This file format cannot be rendered directly in the browser.</p>
                        <a 
                            :href="'/' + previewDoc.document_path" 
                            target="_blank"
                            class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-sm border-none"
                        >
                            Open in New Tab
                        </a>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-105 dark:border-slate-850">
                    <a 
                        :href="'/' + previewDoc.document_path" 
                        download
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 border border-slate-200 dark:border-slate-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download Original
                    </a>
                    <button 
                        @click="previewDoc = null"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition-all border-none cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'UserShow',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const route = useRoute();
        const router = useRouter();

        const user = ref(null);
        const loading = ref(true);
        const activeTab = ref(route.query.tab || 'basic');

        watch(() => route.query.tab, (newTab) => {
            if (newTab) {
                activeTab.value = newTab;
            }
        });

        // Document Uploader
        const selectedDocTemplate = ref('');
        const customDocName = ref('');
        const docFile = ref(null);
        const docFileInput = ref(null);
        const uploadingDoc = ref(false);
        const previewDoc = ref(null);

        const previewDocument = (doc) => {
            previewDoc.value = doc;
        };

        const isImageFile = (path) => {
            if (!path) return false;
            const ext = getFileExt(path).toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif', 'bmp'].includes(ext);
        };

        const isPdfFile = (path) => {
            if (!path) return false;
            const ext = getFileExt(path).toLowerCase();
            return ['pdf'].includes(ext);
        };

        const docTemplates = [
            { label: 'Aadhaar Card (આધાર કાર્ડ)', value: 'Aadhaar Card' },
            { label: 'PAN Card (પાન કાર્ડ)', value: 'PAN Card' },
            { label: 'School Leaving Certificate (L.C.)', value: 'School Leaving Certificate' },
            { label: 'Marksheet - SSC (10th)', value: 'Marksheet - SSC' },
            { label: 'Marksheet - HSC (12th)', value: 'Marksheet - HSC' },
            { label: 'Graduation Degree Certificate', value: 'Graduation Degree' },
            { label: 'B.Ed. Degree Certificate', value: 'B.Ed Degree' },
            { label: 'Experience Certificate', value: 'Experience Certificate' },
            { label: 'Police Verification Certificate', value: 'Police Verification' },
            { label: 'Joining Report (હાજર રીપોર્ટ)', value: 'Joining Report' },
            { label: 'Salary Slip / LPC', value: 'Salary Slip' },
            { label: 'Other / Custom Document', value: 'custom' },
        ];

        // Assignment Modal Workflow
        const showModal = ref(false);
        const submittingAssignment = ref(false);
        const modalError = ref('');
        const savedCount = ref(0);
        const academicYears = ref([]);
        const modalClasses = ref([]);
        const modalSections = ref([]);
        const modalSubjects = ref([]);

        const assignForm = ref({
            academic_year_id: '',
            class_id: '',
            section_id: '',
            subject_id: null,
            is_class_teacher: false,
        });

        // Tab list items
        const tabs = computed(() => {
            const list = [
                { id: 'basic', name: 'Basic Details' },
                { id: 'employment', name: 'Employment & Address' },
            ];

            // Teacher credentials tab visible only to teachers
            if (user.value?.roles?.some(role => role.name === 'Teacher')) {
                list.push({ id: 'teacher', name: 'Teacher Assignments' });
            }

            list.push(
                { id: 'documents', name: 'Documents' },
                { id: 'activity', name: 'Logs & History' }
            );

            return list;
        });

        const fetchUserProfile = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get(`/api/users/${route.params.id}`);
                user.value = response.data.user;
            } catch (err) {
                console.error(err);
                toastStore.error('Could not load user profile details.');
                router.push('/users');
            } finally {
                loading.value = false;
            }
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            return new Date(dateStr).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        };

        // File Uploader Functions
        const onDocFileChange = (event) => {
            docFile.value = event.target.files[0];
        };

        const getFileExt = (filepath) => {
            if (!filepath) return 'file';
            const split = filepath.split('.');
            return split[split.length - 1];
        };

        const uploadDocument = async () => {
            const docNameValue = selectedDocTemplate.value === 'custom' 
                ? customDocName.value 
                : selectedDocTemplate.value;

            if (!docFile.value || !docNameValue) {
                toastStore.error('Please specify a document type and file.');
                return;
            }

            const formData = new FormData();
            formData.append('document', docFile.value);
            formData.append('document_name', docNameValue);

            uploadingDoc.value = true;
            try {
                await window.axios.post(`/api/users/${user.value.id}/documents`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                toastStore.success('Document uploaded successfully.');
                selectedDocTemplate.value = '';
                customDocName.value = '';
                docFile.value = null;
                if (docFileInput.value) {
                    docFileInput.value.value = '';
                }
                fetchUserProfile();
            } catch (err) {
                console.error(err);
                toastStore.error(err.response?.data?.message || 'Failed to upload document.');
            } finally {
                uploadingDoc.value = false;
            }
        };

        const deleteDocument = async (docId) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Document',
                message: 'Are you sure you want to permanently remove this document from the staff profile?',
                type: 'danger',
                confirmText: 'Delete Document',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    await window.axios.delete(`/api/users/${user.value.id}/documents/${docId}`);
                    toastStore.success('Document deleted successfully.');
                    fetchUserProfile();
                } catch (err) {
                    console.error(err);
                    toastStore.error(err.response?.data?.message || 'Failed to delete document.');
                }
            }
        };

        // Teacher Assignment Modal Functions
        const openAssignmentModal = async () => {
            modalError.value = '';
            savedCount.value = 0;
            showModal.value = true;
            
            // Set defaults in form
            assignForm.value = {
                academic_year_id: user.value.selected_academic_year_id || '',
                class_id: '',
                section_id: '',
                subject_id: null,
                is_class_teacher: false,
            };

            // Fetch filter helpers
            try {
                const ayRes = await window.axios.get('/api/academic-years');
                academicYears.value = ayRes.data.academic_years;

                const classRes = await window.axios.get('/api/classes');
                modalClasses.value = classRes.data.classes;
            } catch (err) {
                console.error(err);
            }
        };

        const loadModalSections = async () => {
            assignForm.value.section_id = '';
            assignForm.value.subject_id = null;
            modalSections.value = [];
            modalSubjects.value = [];

            if (assignForm.value.class_id) {
                try {
                    const sectionsRes = await window.axios.get('/api/sections', {
                        params: { class_id: assignForm.value.class_id }
                    });
                    modalSections.value = sectionsRes.data.sections;
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const loadModalSubjects = async () => {
            assignForm.value.subject_id = null;
            modalSubjects.value = [];

            if (assignForm.value.class_id && assignForm.value.section_id) {
                try {
                    const subjectsRes = await window.axios.get('/api/subjects', {
                        params: { 
                            class_id: assignForm.value.class_id, 
                            section_id: assignForm.value.section_id,
                            all: true
                        }
                    });
                    modalSubjects.value = subjectsRes.data.subjects;
                } catch (err) {
                    console.error(err);
                }
            }
        };

        const saveAssignment = async () => {
            modalError.value = '';
            submittingAssignment.value = true;

            const payload = {
                teacher_id: user.value.id,
                ...assignForm.value
            };

            try {
                await window.axios.post('/api/teacher-assignments', payload);
                toastStore.success('Teacher assigned successfully.');
                savedCount.value++;
                
                // Clear inputs for next entry, keeping Class/Section selected
                assignForm.value.subject_id = null;
                assignForm.value.is_class_teacher = false;
                
                fetchUserProfile();
            } catch (err) {
                console.error(err);
                modalError.value = err.response?.data?.message || 'Failed to complete assignment.';
            } finally {
                submittingAssignment.value = false;
            }
        };

        const removeAssignment = async (assignId) => {
            const confirmed = await confirmStore.show({
                title: 'Remove Assignment',
                message: 'Are you sure you want to remove this academic class/subject assignment?',
                type: 'danger',
                confirmText: 'Remove Assignment',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    await window.axios.delete(`/api/teacher-assignments/${assignId}`);
                    toastStore.success('Assignment removed successfully.');
                    fetchUserProfile();
                } catch (err) {
                    console.error(err);
                    toastStore.error(err.response?.data?.message || 'Failed to remove assignment.');
                }
            }
        };

        onMounted(() => {
            fetchUserProfile();
        });

        return {
            authStore,
            user,
            loading,
            activeTab,
            tabs,
            formatDate,
            
            // Document management
            selectedDocTemplate,
            customDocName,
            docTemplates,
            uploadingDoc,
            docFileInput,
            onDocFileChange,
            uploadDocument,
            deleteDocument,
            getFileExt,
            previewDoc,
            previewDocument,
            isImageFile,
            isPdfFile,

            // Assignments
            showModal,
            submittingAssignment,
            modalError,
            savedCount,
            academicYears,
            modalClasses,
            modalSections,
            modalSubjects,
            assignForm,
            openAssignmentModal,
            loadModalSections,
            loadModalSubjects,
            saveAssignment,
            removeAssignment
        };
    }
}
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
