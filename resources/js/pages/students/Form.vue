<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                    {{ isEdit ? 'Edit Student Details' : 'Student Admission Wizard' }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ isEdit ? 'Modify student profile, guardian details, and academic placement.' : 'Register a new student in the system and assign their class placement.' }}
                </p>
            </div>
            <router-link 
                to="/students"
                class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors"
            >
                Back to Directory
            </router-link>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing or assigning a student to a locked academic session. Creating or updating student records is disabled.
            </div>
        </div>

        <!-- Wizard Steps Indicator -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-2 md:gap-4 w-full justify-around">
                <button 
                    v-for="step in steps" 
                    :key="step.number"
                    @click="goToStep(step.number)"
                    :disabled="step.number > maxStepReached"
                    class="flex items-center gap-2 text-left disabled:opacity-50 transition-all focus:outline-none"
                >
                    <span :class="[
                        'w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-colors',
                        currentStep === step.number 
                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-md shadow-indigo-500/20' 
                            : (currentStep > step.number ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-450')
                    ]">
                        <svg v-if="currentStep > step.number" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        <span v-else>{{ step.number }}</span>
                    </span>
                    <div class="hidden md:block">
                        <h4 class="text-xs font-bold uppercase tracking-wider" :class="currentStep === step.number ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500'">
                            {{ step.title }}
                        </h4>
                        <p class="text-[10px] text-slate-400 dark:text-slate-550">{{ step.subtitle }}</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <form @submit.prevent="submitForm">
                
                <!-- STEP 1: Personal Details -->
                <div v-show="currentStep === 1" class="p-6 space-y-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2">
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Admission Number -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Admission No <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.admission_no" 
                                type="text" 
                                required 
                                placeholder="e.g. ADM-2026-001"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Admission Date -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Admission Date <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.admission_date" 
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Status -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                            <select 
                                v-model="form.status" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- First Name -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">First Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.first_name" 
                                type="text" 
                                required 
                                placeholder="e.g. John"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Last Name -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Last Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.last_name" 
                                type="text" 
                                required 
                                placeholder="e.g. Doe"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Date of Birth -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Date of Birth <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.date_of_birth" 
                                v-datepicker
                                type="text"
                                placeholder="YYYY-MM-DD"
                                required 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Gender -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Gender <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.gender" 
                                required 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            >
                                <option value="" disabled>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Blood Group -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Blood Group</label>
                            <input 
                                v-model="form.blood_group" 
                                type="text" 
                                placeholder="e.g. O+"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Photo Upload -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Student Photo</label>
                            <div class="flex items-center gap-3">
                                <!-- Photo Preview -->
                                <div class="relative w-11 h-11 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden group shadow-sm flex-shrink-0">
                                    <img 
                                        v-if="form.photo" 
                                        :src="form.photo" 
                                        class="w-full h-full object-cover"
                                    />
                                    <svg v-else class="w-5 h-5 text-slate-400 dark:text-slate-550" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <button 
                                        v-if="form.photo" 
                                        type="button" 
                                        @click="clearPhoto"
                                        class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-[10px] font-bold"
                                    >
                                        Clear
                                    </button>
                                </div>

                                <!-- File Select Trigger -->
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        ref="photoInput"
                                        @change="onPhotoSelected"
                                        accept="image/*"
                                        class="hidden"
                                    />
                                    <button 
                                        type="button" 
                                        @click="$refs.photoInput.click()"
                                        :disabled="uploadingPhoto"
                                        class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center gap-1.5"
                                    >
                                        <svg v-if="uploadingPhoto" class="animate-spin h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <svg v-else class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                        </svg>
                                        {{ uploadingPhoto ? 'Uploading...' : 'Upload JPG/PNG' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Student Mobile</label>
                            <input 
                                v-model="form.mobile" 
                                type="text" 
                                placeholder="e.g. +1 (555) 123-4567"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>

                        <!-- Email -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Student Email</label>
                            <input 
                                v-model="form.email" 
                                type="email" 
                                placeholder="e.g. john.doe@school.com"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Residential Address</label>
                        <textarea 
                            v-model="form.address" 
                            rows="2"
                            placeholder="e.g. 123 Main St, City, Country"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- STEP 2: Parents Information -->
                <div v-show="currentStep === 2" class="p-6 space-y-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2">
                        Parental & Guardian Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Father's Info -->
                        <div class="space-y-4 p-4 border border-slate-100 dark:border-slate-800/80 rounded-xl bg-slate-50/50 dark:bg-slate-900/40">
                            <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Father Details</h4>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Father's Name</label>
                                <input v-model="form.father_name" type="text" placeholder="e.g. Richard Doe" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Father's Mobile</label>
                                <input v-model="form.father_mobile" type="text" placeholder="e.g. +1 (555) 987-6543" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Father's Email</label>
                                <input v-model="form.father_email" type="email" placeholder="e.g. richard.doe@email.com" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                        </div>

                        <!-- Mother's Info -->
                        <div class="space-y-4 p-4 border border-slate-100 dark:border-slate-800/80 rounded-xl bg-slate-50/50 dark:bg-slate-900/40">
                            <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Mother Details</h4>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Mother's Name</label>
                                <input v-model="form.mother_name" type="text" placeholder="e.g. Mary Doe" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Mother's Mobile</label>
                                <input v-model="form.mother_mobile" type="text" placeholder="e.g. +1 (555) 876-5432" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Mother's Email</label>
                                <input v-model="form.mother_email" type="email" placeholder="e.g. mary.doe@email.com" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Details -->
                    <div class="p-4 border border-slate-100 dark:border-slate-800/80 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 space-y-4">
                        <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Guardian Details (If other than Parents)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Guardian's Name</label>
                                <input v-model="form.guardian_name" type="text" placeholder="e.g. Uncle Bob" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Guardian's Mobile</label>
                                <input v-model="form.guardian_mobile" type="text" placeholder="e.g. +1 (555) 765-4321" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Academic Information -->
                <div v-show="currentStep === 3" class="p-6 space-y-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2">
                        Academic Placement
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Academic Year -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Academic Year <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.academic_year_id" 
                                required
                                @change="onAcademicYearChange"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            >
                                <option value="" disabled>Select Academic Year</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                    {{ year.title }} <span v-if="year.is_current">(Current)</span>
                                </option>
                            </select>
                        </div>

                        <!-- Class -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Class <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.class_id" 
                                required
                                @change="onClassChange"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            >
                                <option value="" disabled>Select Class</option>
                                <option v-for="cls in filteredClasses" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Section -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Section <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.section_id" 
                                required
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            >
                                <option value="" disabled>Select Section</option>
                                <option v-for="sec in filteredSections" :key="sec.id" :value="sec.id">
                                    {{ sec.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Roll Number -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Roll Number</label>
                            <input 
                                v-model="form.roll_no" 
                                type="text" 
                                placeholder="e.g. 05"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer Navigation Buttons -->
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800/80 flex justify-between items-center">
                    <button 
                        type="button" 
                        @click="prevStep" 
                        :disabled="currentStep === 1"
                        class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-350 disabled:opacity-50 transition-colors"
                    >
                        Back
                    </button>
                    
                    <div class="flex items-center gap-2">
                        <!-- Server Errors display -->
                        <span v-if="serverError" class="text-xs text-rose-500 bg-rose-500/10 px-3 py-1.5 rounded-lg border border-rose-500/20 mr-2">
                            {{ serverError }}
                        </span>

                        <button 
                            v-if="currentStep < 3"
                            type="button" 
                            @click="nextStep"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl active:scale-95 transition-all"
                        >
                            Next Step
                        </button>
                        
                        <button 
                            v-else
                            type="submit" 
                            :disabled="submitting || !isCurrentYear"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-indigo-600/10 active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <span v-if="submitting">Saving Placement...</span>
                            <span v-else>{{ isEdit ? 'Update Details' : 'Finalize Admission' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'StudentForm',
    setup() {
        const route = useRoute();
        const router = useRouter();
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const isEdit = ref(false);
        const currentStep = ref(1);
        const maxStepReached = ref(1);
        const submitting = ref(false);
        const serverError = ref('');

        const steps = [
            { number: 1, title: 'Personal info', subtitle: 'Identity details' },
            { number: 2, title: 'Parent details', subtitle: 'Guardian data' },
            { number: 3, title: 'Academic details', subtitle: 'Class placement' }
        ];

        // Master list of metadata
        const academicYears = ref([]);
        const allClasses = ref([]);
        const allSections = ref([]);

        // Filtered dropdowns
        const filteredClasses = ref([]);
        const filteredSections = ref([]);

        // Stepper Form Fields
        const form = ref({
            // Student Master
            admission_no: '',
            first_name: '',
            last_name: '',
            gender: '',
            date_of_birth: '',
            blood_group: '',
            mobile: '',
            email: '',
            address: '',
            photo: '',
            admission_date: new Date().toISOString().substring(0, 10),
            status: 'active',

            // Parent details
            father_name: '',
            father_mobile: '',
            father_email: '',
            mother_name: '',
            mother_mobile: '',
            mother_email: '',
            guardian_name: '',
            guardian_mobile: '',

            // Academic details
            academic_year_id: '',
            class_id: '',
            section_id: '',
            roll_no: ''
        });

        const fetchMetadata = async () => {
            try {
                const yResponse = await window.axios.get('/api/academic-years', { params: { status: 'active' } });
                academicYears.value = yResponse.data.academic_years;

                const cResponse = await window.axios.get('/api/classes', { params: { status: 'active', ignore_academic_year: true } });
                allClasses.value = cResponse.data.classes;

                const sResponse = await window.axios.get('/api/sections', { params: { status: 'active' } });
                allSections.value = sResponse.data.sections;

                // If creating, pre-select current academic year
                if (!isEdit.value) {
                    const current = academicYears.value.find(y => y.is_current);
                    if (current) {
                        form.value.academic_year_id = current.id;
                    }
                }
                
                filterClassesList();
                filterSectionsList();
            } catch (error) {
                console.error(error);
            }
        };

        const isCurrentYear = computed(() => {
            if (!form.value.academic_year_id) return true;
            const selected = academicYears.value.find(y => y.id === parseInt(form.value.academic_year_id));
            return selected ? !!selected.is_current : false;
        });

        const filterClassesList = () => {
            if (form.value.academic_year_id) {
                filteredClasses.value = allClasses.value.filter(
                    c => c.academic_year_id === parseInt(form.value.academic_year_id)
                );
            } else {
                filteredClasses.value = allClasses.value;
            }
            if (!filteredClasses.value.some(c => c.id === parseInt(form.value.class_id))) {
                form.value.class_id = '';
            }
        };

        const filterSectionsList = () => {
            if (form.value.class_id) {
                filteredSections.value = allSections.value.filter(
                    s => s.class_id === parseInt(form.value.class_id)
                );
            } else {
                filteredSections.value = [];
            }
            if (!filteredSections.value.some(s => s.id === parseInt(form.value.section_id))) {
                form.value.section_id = '';
            }
        };

        const onAcademicYearChange = () => {
            filterClassesList();
            filterSectionsList();
        };

        const onClassChange = () => {
            filterSectionsList();
        };

        const loadStudentForEdit = async () => {
            try {
                const response = await window.axios.get(`/api/students/${route.params.id}`);
                const student = response.data.student;

                // Load basic info
                form.value.admission_no = student.admission_no;
                form.value.first_name = student.first_name;
                form.value.last_name = student.last_name;
                form.value.gender = student.gender;
                form.value.date_of_birth = student.date_of_birth ? student.date_of_birth.substring(0, 10) : '';
                form.value.blood_group = student.blood_group || '';
                form.value.mobile = student.mobile || '';
                form.value.email = student.email || '';
                form.value.address = student.address || '';
                form.value.photo = student.photo || '';
                form.value.admission_date = student.admission_date ? student.admission_date.substring(0, 10) : '';
                form.value.status = student.status;

                // Load parent info
                if (student.parent) {
                    form.value.father_name = student.parent.father_name || '';
                    form.value.father_mobile = student.parent.father_mobile || '';
                    form.value.father_email = student.parent.father_email || '';
                    form.value.mother_name = student.parent.mother_name || '';
                    form.value.mother_mobile = student.parent.mother_mobile || '';
                    form.value.mother_email = student.parent.mother_email || '';
                    form.value.guardian_name = student.parent.guardian_name || '';
                    form.value.guardian_mobile = student.parent.guardian_mobile || '';
                }

                // Load current academic year record (first one in history)
                if (student.academic_records && student.academic_records.length > 0) {
                    const record = student.academic_records[0];
                    form.value.academic_year_id = record.academic_year_id;
                    form.value.class_id = record.class_id;
                    form.value.section_id = record.section_id;
                    form.value.roll_no = record.roll_no || '';
                }

                filterClassesList();
                filterSectionsList();
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load student profile.');
            }
        };

        const goToStep = (stepNumber) => {
            if (stepNumber <= maxStepReached.value) {
                currentStep.value = stepNumber;
            }
        };

        const nextStep = () => {
            // Basic step-by-step validations before advancing
            if (currentStep.value === 1) {
                if (!form.value.admission_no || !form.value.first_name || !form.value.last_name || !form.value.date_of_birth || !form.value.gender) {
                    serverError.value = 'Please complete all required fields (*).';
                    return;
                }
            }
            serverError.value = '';
            currentStep.value++;
            if (currentStep.value > maxStepReached.value) {
                maxStepReached.value = currentStep.value;
            }
        };

        const prevStep = () => {
            serverError.value = '';
            if (currentStep.value > 1) {
                currentStep.value--;
            }
        };

        const submitForm = async () => {
            submitting.value = true;
            serverError.value = '';
            try {
                if (isEdit.value) {
                    await window.axios.put(`/api/students/${route.params.id}`, form.value);
                    toastStore.success('Student records updated successfully.');
                } else {
                    await window.axios.post('/api/students', form.value);
                    toastStore.success('Student registered and placed successfully.');
                }
                router.push('/students');
            } catch (error) {
                console.error(error);
                serverError.value = error.response?.data?.message || 'Error occurred while saving records.';
            } finally {
                submitting.value = false;
            }
        };

        const uploadingPhoto = ref(false);
        const photoInput = ref(null);

        const onPhotoSelected = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                toastStore.error('Image size must be less than 2MB.');
                return;
            }

            uploadingPhoto.value = true;
            const formData = new FormData();
            formData.append('photo', file);
            if (form.value.class_id) {
                formData.append('class_id', form.value.class_id);
            }
            if (form.value.section_id) {
                formData.append('section_id', form.value.section_id);
            }

            try {
                const response = await window.axios.post('/api/students/upload-photo', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                form.value.photo = response.data.url;
                toastStore.success('Photo uploaded successfully.');
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to upload photo.');
            } finally {
                uploadingPhoto.value = false;
                if (photoInput.value) {
                    photoInput.value.value = '';
                }
            }
        };

        const clearPhoto = () => {
            form.value.photo = '';
        };

        onMounted(async () => {
            if (route.params.id) {
                isEdit.value = true;
            }
            await fetchMetadata();
            if (isEdit.value) {
                await loadStudentForEdit();
            }
        });

        return {
            isEdit,
            currentStep,
            maxStepReached,
            steps,
            form,
            academicYears,
            filteredClasses,
            filteredSections,
            submitting,
            serverError,
            authStore,
            goToStep,
            nextStep,
            prevStep,
            onAcademicYearChange,
            onClassChange,
            submitForm,
            uploadingPhoto,
            photoInput,
            onPhotoSelected,
            clearPhoto,
            isCurrentYear
        };
    }
}
</script>
