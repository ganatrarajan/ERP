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
                class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-55 dark:hover:bg-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors"
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
                            : (currentStep > step.number ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400')
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
                
                <!-- STEP 1: Personal & General Details -->
                <div v-show="currentStep === 1" class="p-6 space-y-6">
                    <!-- Section 1.1: Admission & Identity Info -->
                    <div>
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">
                            1. Admission & Identity Info
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
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- GR Number (General Register) -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">GR Number (General Register)</label>
                                <input 
                                    v-model="form.gr_no" 
                                    type="text" 
                                    placeholder="e.g. GR-9876"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
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
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- House -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">House</label>
                                <select 
                                    v-model="form.house" 
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                >
                                    <option value="">No House Assigned</option>
                                    <option value="Red">Red</option>
                                    <option value="Green">Green</option>
                                    <option value="Blue">Blue</option>
                                    <option value="Yellow">Yellow</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                                <select 
                                    v-model="form.status" 
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 1.2: Personal Details -->
                    <div>
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">
                            2. Personal & Contact Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- First Name -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">First Name <span class="text-rose-500">*</span></label>
                                <input 
                                    v-model="form.first_name" 
                                    type="text" 
                                    required 
                                    placeholder="e.g. John"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
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
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
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
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- Gender -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Gender <span class="text-rose-500">*</span></label>
                                <select 
                                    v-model="form.gender" 
                                    required 
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
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
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- Mobile -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Student Mobile</label>
                                <input 
                                    v-model="form.mobile" 
                                    type="text" 
                                    maxlength="10"
                                    @input="form.mobile = form.mobile.replace(/\D/g, '')"
                                    placeholder="e.g. 9876543210"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- Email -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Student Email</label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    placeholder="e.g. john.doe@school.com"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- Photo Upload -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Student Photo</label>
                                <div class="flex items-center gap-3">
                                    <div class="relative w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden group shadow-sm flex-shrink-0">
                                        <img v-if="form.photo" :src="form.photo" class="w-full h-full object-cover" />
                                        <svg v-else class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <button v-if="form.photo" type="button" @click="clearPhoto" class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-[9px] font-bold">Clear</button>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" ref="photoInput" @change="onPhotoSelected" accept="image/*" class="hidden" />
                                        <button 
                                            type="button" 
                                            @click="$refs.photoInput.click()"
                                            :disabled="uploadingPhoto"
                                            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center gap-1"
                                        >
                                            Upload
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address -->
                        <div class="space-y-1 mt-4">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Residential Address</label>
                            <textarea 
                                v-model="form.address" 
                                rows="2"
                                placeholder="e.g. 123 Main St, City, State"
                                class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Section 1.3: Demographics & Government IDs -->
                    <div>
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">
                            3. Demographics & Government Identifiers
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Aadhaar Card No -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Aadhaar Card No (12 Digits)</label>
                                <input 
                                    v-model="form.aadhaar_no" 
                                    type="text" 
                                    maxlength="12"
                                    @input="form.aadhaar_no = form.aadhaar_no.replace(/\D/g, '')"
                                    placeholder="e.g. 123456789012"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- PEN (Personal Education Number) -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">PEN (Personal Education Number - UDISE+)</label>
                                <input 
                                    v-model="form.pen_no" 
                                    type="text" 
                                    placeholder="e.g. PEN-123456789"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- Student National UDISE ID -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">National UDISE Student ID</label>
                                <input 
                                    v-model="form.udise_no" 
                                    type="text" 
                                    placeholder="e.g. UDISE-98765"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- Caste / Category -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Caste Category</label>
                                <select 
                                    v-model="form.category" 
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                >
                                    <option value="">Select Category</option>
                                    <option value="General">General/Open</option>
                                    <option value="SEBC">SEBC/OBC</option>
                                    <option value="SC">SC</option>
                                    <option value="ST">ST</option>
                                    <option value="EWS">EWS</option>
                                </select>
                            </div>

                            <!-- Religion -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Religion</label>
                                <select 
                                    v-model="form.religion" 
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                >
                                    <option value="">Select Religion</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Muslim">Muslim</option>
                                    <option value="Christian">Christian</option>
                                    <option value="Sikh">Sikh</option>
                                    <option value="Jain">Jain</option>
                                    <option value="Buddhist">Buddhist</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Nationality -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Nationality</label>
                                <input 
                                    v-model="form.nationality" 
                                    type="text" 
                                    placeholder="e.g. Indian"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Parents & Emergency Contacts -->
                <div v-show="currentStep === 2" class="p-6 space-y-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2">
                        Parental & Emergency Information
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
                                <input v-model="form.father_mobile" type="text" maxlength="10" @input="form.father_mobile = form.father_mobile.replace(/\D/g, '')" placeholder="e.g. 9876543211" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
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
                                <input v-model="form.mother_mobile" type="text" maxlength="10" @input="form.mother_mobile = form.mother_mobile.replace(/\D/g, '')" placeholder="e.g. 9876543212" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Mother's Email</label>
                                <input v-model="form.mother_email" type="email" placeholder="e.g. mary.doe@email.com" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 border border-slate-100 dark:border-slate-800/80 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 space-y-4">
                            <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Guardian Details (Alternative)</h4>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Guardian's Name</label>
                                <input v-model="form.guardian_name" type="text" placeholder="e.g. Uncle Bob" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Guardian's Mobile</label>
                                <input v-model="form.guardian_mobile" type="text" maxlength="10" @input="form.guardian_mobile = form.guardian_mobile.replace(/\D/g, '')" placeholder="e.g. 9876543213" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                        </div>

                        <!-- Emergency Contact (NEW) -->
                        <div class="p-4 border border-slate-100 dark:border-slate-800/80 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 space-y-4">
                            <h4 class="text-sm font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Emergency Contact Person</h4>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Contact Name <span class="text-rose-500">*</span></label>
                                <input v-model="form.emergency_contact_name" type="text" placeholder="e.g. Grandma Smith" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Contact Mobile <span class="text-rose-500">*</span></label>
                                <input v-model="form.emergency_contact_mobile" type="text" maxlength="10" @input="form.emergency_contact_mobile = form.emergency_contact_mobile.replace(/\D/g, '')" placeholder="e.g. 9876543214" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Contact Email</label>
                                <input v-model="form.emergency_contact_email" type="email" placeholder="e.g. emergency@contact.com" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Academic & Enrollment Details -->
                <div v-show="currentStep === 3" class="p-6 space-y-6">
                    <!-- Academic Placement -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">
                            1. Class Placement & Session
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

                    <!-- Previous School Details (GSEB/CBSE TC transfer verification) -->
                    <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">
                            2. Previous School History & Transfer Certificate (T.C.)
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Previous School Name -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Previous School Name</label>
                                <input 
                                    v-model="form.previous_school_name" 
                                    type="text" 
                                    placeholder="e.g. Sharda Mandir High School"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- TC Number -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">T.C. (Transfer Certificate) No.</label>
                                <input 
                                    v-model="form.previous_school_tc_no" 
                                    type="text" 
                                    placeholder="e.g. TC-5544"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>

                            <!-- TC Date -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">T.C. Issue Date</label>
                                <input 
                                    v-model="form.previous_school_tc_date" 
                                    v-datepicker
                                    type="text"
                                    placeholder="YYYY-MM-DD"
                                    class="w-full px-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 focus:outline-none transition-all"
                                />
                            </div>
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
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md transition-all"
                        >
                            Next Step
                        </button>
                        
                        <button 
                            v-else
                            type="submit"
                            :disabled="submitting || !isCurrentYear"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/10 disabled:opacity-50 disabled:scale-100 transition-all"
                        >
                            {{ submitting ? 'Saving...' : (isEdit ? 'Save Changes' : 'Confirm Admission') }}
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
            { number: 1, title: 'Personal & General', subtitle: 'Identity & Caste details' },
            { number: 2, title: 'Parents & Emergency', subtitle: 'Guardian & Emergency contact' },
            { number: 3, title: 'Academic & History', subtitle: 'Placement & previous school' }
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
            gr_no: '',
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
            house: '',
            category: '',
            religion: '',
            nationality: 'Indian',
            aadhaar_no: '',
            pen_no: '',
            udise_no: '',

            // Parent details
            father_name: '',
            father_mobile: '',
            father_email: '',
            mother_name: '',
            mother_mobile: '',
            mother_email: '',
            guardian_name: '',
            guardian_mobile: '',

            // Emergency Contact details
            emergency_contact_name: '',
            emergency_contact_mobile: '',
            emergency_contact_email: '',

            // Academic details
            academic_year_id: '',
            class_id: '',
            section_id: '',
            roll_no: '',

            // Previous School details
            previous_school_name: '',
            previous_school_tc_no: '',
            previous_school_tc_date: ''
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
                form.value.gr_no = student.gr_no || '';
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
                form.value.house = student.house || '';
                form.value.category = student.category || '';
                form.value.religion = student.religion || '';
                form.value.nationality = student.nationality || 'Indian';
                form.value.aadhaar_no = student.aadhaar_no || '';
                form.value.pen_no = student.pen_no || '';
                form.value.udise_no = student.udise_no || '';

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

                // Load Emergency contact
                form.value.emergency_contact_name = student.emergency_contact_name || '';
                form.value.emergency_contact_mobile = student.emergency_contact_mobile || '';
                form.value.emergency_contact_email = student.emergency_contact_email || '';

                // Load Previous School TC details
                form.value.previous_school_name = student.previous_school_name || '';
                form.value.previous_school_tc_no = student.previous_school_tc_no || '';
                form.value.previous_school_tc_date = student.previous_school_tc_date ? student.previous_school_tc_date.substring(0, 10) : '';

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
                if (form.value.mobile && form.value.mobile.length !== 10) {
                    serverError.value = 'Student mobile number must be exactly 10 digits.';
                    return;
                }
                if (form.value.aadhaar_no && form.value.aadhaar_no.length !== 12) {
                    serverError.value = 'Aadhaar Card number must be exactly 12 digits.';
                    return;
                }
            } else if (currentStep.value === 2) {
                if (!form.value.emergency_contact_name || !form.value.emergency_contact_mobile) {
                    serverError.value = 'Emergency Contact Name and Mobile number are required.';
                    return;
                }
                if (form.value.emergency_contact_mobile && form.value.emergency_contact_mobile.length !== 10) {
                    serverError.value = 'Emergency contact mobile number must be exactly 10 digits.';
                    return;
                }
                if (form.value.father_mobile && form.value.father_mobile.length !== 10) {
                    serverError.value = 'Father mobile number must be exactly 10 digits.';
                    return;
                }
                if (form.value.mother_mobile && form.value.mother_mobile.length !== 10) {
                    serverError.value = 'Mother mobile number must be exactly 10 digits.';
                    return;
                }
                if (form.value.guardian_mobile && form.value.guardian_mobile.length !== 10) {
                    serverError.value = 'Guardian mobile number must be exactly 10 digits.';
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

        const fetchNextAdmissionNo = async () => {
            try {
                const response = await window.axios.get('/api/students/next-admission-no');
                form.value.admission_no = response.data.next_admission_no || '';
            } catch (error) {
                console.error('Failed to generate next admission no', error);
            }
        };

        onMounted(async () => {
            if (route.params.id) {
                isEdit.value = true;
            }
            await fetchMetadata();
            if (isEdit.value) {
                await loadStudentForEdit();
            } else {
                await fetchNextAdmissionNo();
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
