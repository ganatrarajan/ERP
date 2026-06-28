<template>
    <div class="space-y-8 pb-12">
        <!-- Welcome banner -->
        <div class="bg-gradient-to-br from-indigo-900 via-indigo-955 to-slate-900 text-white border border-indigo-950 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 shadow-md relative overflow-hidden">
            <!-- Sleek absolute design grid pattern on background -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:16px_16px] pointer-events-none"></div>
            
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] text-indigo-300 font-extrabold tracking-widest uppercase mb-1 block">
                    {{ authStore.user?.school?.name || 'EduvoraX SaaS' }}
                </span>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight flex items-center gap-3">
                    Welcome back, {{ authStore.user?.name }}!
                    <span class="animate-wave origin-[70%_70%] inline-block">👋</span>
                </h1>
                <p class="text-xs text-indigo-200/80 mt-1 font-medium">Here is a comprehensive summary of the system and school activities.</p>
                <div class="mt-4 flex flex-wrap gap-2 pt-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-white border border-white/10 backdrop-blur-xs">
                        Role: {{ authStore.roles[0] || 'Teacher' }}
                    </span>
                </div>
            </div>
            
            <div class="relative z-10 text-xs text-indigo-105 bg-white/5 backdrop-blur-xs p-4 rounded-xl border border-white/10 leading-relaxed max-w-xs self-stretch md:self-auto flex flex-col justify-center shadow-inner">
                <div><strong>Last Login:</strong> {{ formattedLastLogin }}</div>
                <div class="mt-1"><strong>System IP:</strong> 127.0.0.1 (Local)</div>
            </div>
        </div>

        <!-- Segmented Navigation (Only for school scope and when multiple tabs are authorized) -->
        <div v-if="!loading && scope === 'school' && (canViewFees || canViewAcademics)" class="flex bg-slate-100 dark:bg-slate-955 p-1 rounded-xl border border-slate-200/50 dark:border-slate-800/80 max-w-md w-full md:w-auto">
            <button
                @click="activeTab = 'overview'"
                :class="[
                    'flex-1 md:flex-initial px-5 py-2.5 text-xs font-black rounded-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer',
                    activeTab === 'overview' 
                        ? 'bg-white dark:bg-slate-850 text-indigo-650 dark:text-indigo-400 shadow-sm' 
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-450 dark:hover:text-slate-305'
                ]"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                Overview
            </button>
            <button
                v-if="canViewFees"
                @click="activeTab = 'finance'"
                :class="[
                    'flex-1 md:flex-initial px-5 py-2.5 text-xs font-black rounded-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer',
                    activeTab === 'finance' 
                        ? 'bg-white dark:bg-slate-850 text-indigo-650 dark:text-indigo-400 shadow-sm' 
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-455 dark:hover:text-slate-300'
                ]"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Finance
            </button>
            <button
                v-if="canViewAcademics"
                @click="activeTab = 'academics'"
                :class="[
                    'flex-1 md:flex-initial px-5 py-2.5 text-xs font-black rounded-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer',
                    activeTab === 'academics' 
                        ? 'bg-white dark:bg-slate-850 text-indigo-650 dark:text-indigo-400 shadow-sm' 
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-450 dark:hover:text-slate-300'
                ]"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                Academics
            </button>
        </div>

        <!-- Skeleton Loader -->
        <div v-if="loading" class="space-y-8 animate-pulse">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="i in 4" :key="i" class="h-32 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div class="space-y-2">
                            <div class="h-3.5 w-24 bg-slate-200 dark:bg-slate-800 rounded-md"></div>
                            <div class="h-8 w-16 bg-slate-350 dark:bg-slate-700 rounded-md"></div>
                        </div>
                        <div class="h-10 w-10 bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
                    </div>
                    <div class="h-3 w-32 bg-slate-200 dark:bg-slate-800 rounded-md mt-2"></div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 h-96 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-2xl"></div>
                <div class="h-96 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-2xl"></div>
            </div>
        </div>

        <!-- Dashboard Content Areas -->
        <div v-else class="space-y-8">
            
            <!-- SUPER ADMIN DASHBOARD SCOPE -->
            <div v-if="scope === 'super_admin'" class="space-y-8 animate-fade-in">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div 
                        v-for="stat in stats" 
                        :key="stat.title" 
                        class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 hover:border-slate-300 dark:hover:border-slate-750 transition-all duration-300 group flex flex-col justify-between"
                    >
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-505 tracking-wider uppercase block">{{ stat.title }}</span>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight tabular-nums group-hover:text-indigo-650 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ stat.value }}
                                </h3>
                            </div>
                            <div :class="[
                                'w-10 h-10 rounded-xl flex items-center justify-center border shrink-0',
                                stat.color === 'indigo' ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-100 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400' : '',
                                stat.color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-405' : '',
                                stat.color === 'rose' ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-455' : '',
                                stat.color === 'blue' ? 'bg-blue-50 dark:bg-blue-500/10 border-blue-100 dark:border-blue-500/20 text-blue-600 dark:text-blue-450' : '',
                            ]">
                                <svg v-if="stat.icon === 'AcademicCapIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                                <svg v-else-if="stat.icon === 'CheckCircleIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg v-else-if="stat.icon === 'XCircleIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg v-else-if="stat.icon === 'UsersIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                        </div>
                        <p v-if="stat.description" class="text-xs text-slate-450 dark:text-slate-500 font-medium mt-4">{{ stat.description }}</p>
                    </div>
                </div>

                <!-- Platform Information Section -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 md:p-8 rounded-2xl shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-805 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-505 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        SaaS Platform Information
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">
                        This multi-tenant School ERP Platform handles administration, course planning, fee structure, student data, and teacher rosters. As a Super Administrator, you are managing institutional setups, subscription states, and cross-tenant roles/permissions.
                    </p>
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-6">
                        <div class="flex items-center gap-2 text-indigo-650 dark:text-indigo-400 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            Laravel 12 API Ready
                        </div>
                        <div class="flex items-center gap-2 text-sky-600 dark:text-sky-405 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            Vue 3 Pinia SPA
                        </div>
                    </div>
                </div>
            </div>


            <!-- SCHOOL ADMIN / TEACHER SCOPE -->
            <div v-else-if="scope === 'school'" class="space-y-8">
                
                <!-- TAB 1: OVERVIEW -->
                <div v-if="activeTab === 'overview'" class="space-y-8 animate-fade-in">
                    <!-- Standard Overview Stats -->
                    <div v-if="overviewStats.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="stat in overviewStats" 
                            :key="stat.title" 
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 hover:border-slate-350 dark:hover:border-slate-700 transition-all duration-300 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-2">
                                    <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-505 tracking-wider uppercase block text-ellipsis overflow-hidden">{{ stat.title }}</span>
                                    <h3 class="text-3xl font-black text-slate-850 dark:text-white mt-1 tracking-tight tabular-nums group-hover:text-indigo-655 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ stat.value }}
                                    </h3>
                                </div>
                                <div :class="[
                                    'w-10 h-10 rounded-xl flex items-center justify-center border shrink-0',
                                    stat.color === 'indigo' ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-100 dark:border-indigo-500/20 text-indigo-655 dark:text-indigo-400' : '',
                                    stat.color === 'sky' ? 'bg-sky-50 dark:bg-sky-500/10 border-sky-100 dark:border-sky-500/20 text-sky-655 dark:text-sky-400' : '',
                                    stat.color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : '',
                                    stat.color === 'violet' ? 'bg-violet-50 dark:bg-violet-500/10 border-violet-100 dark:border-violet-500/20 text-violet-650 dark:text-violet-400' : '',
                                ]">
                                    <svg v-if="stat.icon === 'UsersIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    <svg v-else-if="stat.icon === 'BookOpenIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                    <svg v-else-if="stat.icon === 'AcademicCapIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                                </div>
                            </div>
                            <p v-if="stat.description" class="text-xs text-slate-450 dark:text-slate-500 font-medium mt-4">{{ stat.description }}</p>
                        </div>
                    </div>

                    <!-- Attendance Summary Rings (Only if attendance module is enabled) -->
                    <div v-if="canViewAttendance && attendanceStats" class="space-y-4">
                        <h3 class="text-xs font-extrabold text-slate-400 dark:text-slate-505 tracking-wider uppercase flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                            Today's Attendance Rates
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Student Attendance Donut -->
                            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 p-6 rounded-2xl flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
                                <div class="space-y-2">
                                    <h4 class="text-xs font-extrabold text-slate-400 tracking-wider uppercase">Student Attendance</h4>
                                    <div class="text-3xl font-black text-slate-850 dark:text-white leading-none">
                                        {{ attendanceStats.student.present }} 
                                        <span class="text-xs font-semibold text-slate-450 dark:text-slate-500 block mt-2">Present out of {{ attendanceStats.student.present + attendanceStats.student.absent }} enrolled</span>
                                    </div>
                                    <div class="flex items-center gap-3 pt-2 text-[11px] font-bold">
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Present: {{ attendanceStats.student.present }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-455">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Absent: {{ attendanceStats.student.absent }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                        <defs>
                                            <linearGradient id="studentProgressGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#10B981" />
                                                <stop offset="100%" stop-color="#06B6D4" />
                                            </linearGradient>
                                        </defs>
                                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="#F1F5F9" stroke-width="8" class="dark:stroke-slate-800" />
                                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="url(#studentProgressGrad)" stroke-width="8"
                                                stroke-dasharray="251.35" :stroke-dashoffset="getStrokeDashOffset(studentAttendancePercent, 40)"
                                                stroke-linecap="round" class="transition-all duration-1000 ease-out" />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-lg font-black text-slate-855 dark:text-white leading-none">{{ studentAttendancePercent }}%</span>
                                        <span class="text-[9px] text-slate-405 dark:text-slate-500 uppercase tracking-wider font-bold mt-0.5">Rate</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff Attendance Donut -->
                            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 p-6 rounded-2xl flex items-center justify-between shadow-sm hover:shadow-md transition-shadow">
                                <div class="space-y-2">
                                    <h4 class="text-xs font-extrabold text-slate-400 tracking-wider uppercase">Staff Attendance</h4>
                                    <div class="text-3xl font-black text-slate-855 dark:text-white leading-none">
                                        {{ attendanceStats.staff.present }}
                                        <span class="text-xs font-semibold text-slate-455 dark:text-slate-500 block mt-2">Present out of {{ attendanceStats.staff.present + attendanceStats.staff.absent }} staff</span>
                                    </div>
                                    <div class="flex items-center gap-3 pt-2 text-[11px] font-bold">
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Present: {{ attendanceStats.staff.present }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-455">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Absent: {{ attendanceStats.staff.absent }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                        <defs>
                                            <linearGradient id="staffProgressGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#8B5CF6" />
                                                <stop offset="100%" stop-color="#EC4899" />
                                            </linearGradient>
                                        </defs>
                                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="#F1F5F9" stroke-width="8" class="dark:stroke-slate-800" />
                                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="url(#staffProgressGrad)" stroke-width="8"
                                                stroke-dasharray="251.35" :stroke-dashoffset="getStrokeDashOffset(staffAttendancePercent, 40)"
                                                stroke-linecap="round" class="transition-all duration-1000 ease-out" />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-lg font-black text-slate-855 dark:text-white leading-none">{{ staffAttendancePercent }}%</span>
                                        <span class="text-[9px] text-slate-405 dark:text-slate-500 uppercase tracking-wider font-bold mt-0.5">Rate</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform and Operations Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div :class="[
                            (canCreateSchools || canCreateUsers) ? 'lg:col-span-2' : 'lg:col-span-3 col-span-full',
                            'bg-white dark:bg-slate-900 border border-slate-200/85 dark:border-slate-800 p-6 md:p-8 rounded-2xl shadow-sm space-y-4'
                        ]">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                School Dashboard Overview
                            </h3>
                            <div class="space-y-4 text-sm text-slate-655 dark:text-slate-400 leading-relaxed">
                                <p>This multi-tenant School ERP Platform handles administration, course planning, fee structure, student data, and teacher rosters. Currently, you are accessing the foundational modules: <strong>Authentication, Multi-tenancy Scoping, and Spatie Roles/Permissions</strong>.</p>
                                <p>Security is enforced at the database layer (single database with tenant filtering via school scoping) and HTTP routing layer (using permission-based middleware and gate protection).</p>
                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex gap-6">
                                    <div class="flex items-center gap-2 text-indigo-650 dark:text-indigo-400 text-xs font-bold">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                        Laravel 12 API Ready
                                    </div>
                                    <div class="flex items-center gap-2 text-sky-655 dark:text-sky-405 text-xs font-bold">
                                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                        Vue 3 + Pinia SPA
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="canCreateSchools || canCreateUsers" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 rounded-2xl flex flex-col justify-between shadow-sm">
                            <div>
                                <h3 class="text-xs font-bold text-slate-505 uppercase tracking-wider mb-2">System Operations</h3>
                                <p class="text-xs text-slate-450 dark:text-slate-400 leading-relaxed mb-6">Quick actions that correspond to your administrative permission level.</p>
                            </div>
                            <div class="space-y-3">
                                <router-link 
                                    v-slot="{ href, navigate }" 
                                    v-if="canCreateSchools" 
                                    to="/schools/create" 
                                    custom
                                >
                                    <a :href="href" @click="navigate" class="w-full py-2.5 px-4 bg-indigo-50 dark:bg-indigo-650/10 hover:bg-indigo-100 dark:hover:bg-indigo-600/20 text-indigo-650 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 text-xs font-bold rounded-xl transition-all flex items-center justify-between">
                                        Register New School
                                        <span>&rarr;</span>
                                    </a>
                                </router-link>
                                <router-link 
                                    v-slot="{ href, navigate }" 
                                    v-if="canCreateUsers" 
                                    to="/users/create" 
                                    custom
                                >
                                    <a :href="href" @click="navigate" class="w-full py-2.5 px-4 bg-slate-100 dark:bg-slate-800/60 hover:bg-slate-250 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-305 border border-slate-200 dark:border-slate-700/60 text-xs font-bold rounded-xl transition-all flex items-center justify-between">
                                        Create School User
                                        <span>&rarr;</span>
                                    </a>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: FINANCE & COLLECTIONS -->
                <div v-else-if="activeTab === 'finance' && canViewFees" class="space-y-8 animate-fade-in">
                    <!-- Finance Card Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="stat in financeStats" 
                            :key="stat.title" 
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 hover:border-slate-350 dark:hover:border-slate-700 transition-all duration-300 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-2">
                                    <span class="text-[10px] font-extrabold text-slate-405 dark:text-slate-505 tracking-wider uppercase block">{{ stat.title }}</span>
                                    <h3 class="text-2xl md:text-3xl font-black text-slate-805 dark:text-white mt-1 tracking-tight group-hover:text-indigo-650 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ stat.value }}
                                    </h3>
                                </div>
                                <div :class="[
                                    'w-10 h-10 rounded-xl flex items-center justify-center border shrink-0',
                                    stat.color === 'teal' ? 'bg-teal-50 dark:bg-teal-500/10 border-teal-100 dark:border-teal-500/20 text-teal-650 dark:text-teal-400' : '',
                                    stat.color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-405' : '',
                                    stat.color === 'amber' ? 'bg-amber-55/70 dark:bg-amber-500/10 border-amber-100 dark:border-amber-500/20 text-amber-700 dark:text-amber-450' : '',
                                    stat.color === 'rose' ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-455' : '',
                                    stat.color === 'indigo' ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-100 dark:border-indigo-500/20 text-indigo-650 dark:text-indigo-400' : '',
                                    stat.color === 'pink' ? 'bg-pink-50 dark:bg-pink-500/10 border-pink-100 dark:border-pink-500/20 text-pink-650 dark:text-pink-400' : '',
                                ]">
                                    <svg v-if="stat.icon === 'CheckCircleIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <svg v-else-if="stat.icon === 'DocumentTextIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <svg v-else-if="stat.icon === 'BellIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                </div>
                            </div>
                            <p v-if="stat.description" class="text-xs text-slate-455 dark:text-slate-500 font-medium mt-4">{{ stat.description }}</p>
                        </div>
                    </div>

                    <!-- Custom SVG Charts Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Monthly Collections Line/Area Chart -->
                        <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-455 dark:text-slate-400 uppercase tracking-wider mb-6 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    Monthly Collection Trends
                                </h3>
                                
                                <div v-if="monthlyTrends.length === 0" class="h-[280px] flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-slate-800/80 rounded-xl">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">No collection trends recorded yet</span>
                                </div>
                                
                                <div v-else class="relative h-[280px]">
                                    <svg viewBox="0 0 600 280" class="w-full h-full overflow-visible">
                                        <defs>
                                            <linearGradient id="areaTrendGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#4F46E5" stop-opacity="0.15" />
                                                <stop offset="100%" stop-color="#4F46E5" stop-opacity="0.0" />
                                            </linearGradient>
                                        </defs>
                                        
                                        <!-- Y Grid lines -->
                                        <g v-for="grid in gridLines" :key="grid.y">
                                            <line x1="60" :y1="grid.y" x2="580" :y2="grid.y" stroke="rgba(226, 232, 240, 0.4)" class="dark:stroke-slate-800/30" stroke-width="1" />
                                            <text x="50" :y="grid.y + 4" text-anchor="end" class="text-[9px] font-extrabold fill-slate-400 dark:fill-slate-505">₹{{ formatShortCurrency(grid.val) }}</text>
                                        </g>
                                        
                                        <!-- Filled Area -->
                                        <path :d="areaPathD" fill="url(#areaTrendGrad)" />
                                        
                                        <!-- Connection Line -->
                                        <path :d="linePathD" fill="none" stroke="#4F46E5" stroke-width="2.5" stroke-linecap="round" class="dark:stroke-indigo-400" />
                                        
                                        <!-- Circle Points -->
                                        <circle
                                            v-for="(pt, idx) in lineChartPoints"
                                            :key="idx"
                                            :cx="pt.x"
                                            :cy="pt.y"
                                            r="5"
                                            class="fill-indigo-650 dark:fill-indigo-400 stroke-white dark:stroke-slate-900 stroke-2 cursor-pointer transition-all duration-200"
                                            :class="{ 'r-7 fill-indigo-500 stroke-indigo-100': hoveredTrendPoint && hoveredTrendPoint.month === pt.month }"
                                            @mouseenter="hoveredTrendPoint = pt"
                                            @mouseleave="hoveredTrendPoint = null"
                                        />
                                        
                                        <!-- X Axis Labels -->
                                        <text
                                            v-for="(pt, idx) in lineChartPoints"
                                            :key="'lbl-' + idx"
                                            :x="pt.x"
                                            y="262"
                                            text-anchor="middle"
                                            class="text-[9px] font-extrabold fill-slate-400 dark:fill-slate-500"
                                        >
                                            {{ pt.monthLabel }}
                                        </text>
                                    </svg>
                                    
                                    <!-- Tooltip Box -->
                                    <div
                                        v-if="hoveredTrendPoint"
                                        class="absolute z-10 bg-slate-950/95 dark:bg-slate-955/95 text-white py-1.5 px-3 rounded-lg shadow-xl border border-slate-800 text-[10.5px] pointer-events-none transition-all duration-150 whitespace-nowrap"
                                        :style="{ left: tooltipX, top: tooltipY }"
                                    >
                                        <div class="font-bold">{{ hoveredTrendPoint.monthLabel }}</div>
                                        <div class="text-indigo-400 font-extrabold mt-0.5">₹{{ formatCurrency(hoveredTrendPoint.val) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Collections Horizontal Bar Chart -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-455 dark:text-slate-450 uppercase tracking-wider mb-6 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    Collection by Class
                                </h3>
                                
                                <div v-if="classCollections.length === 0" class="h-[280px] flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-slate-800/80 rounded-xl">
                                    <svg class="w-12 h-12 text-slate-330 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">No class records to display</span>
                                </div>
                                
                                <div v-else class="overflow-y-auto max-h-[280px] pr-2">
                                    <svg :viewBox="'0 0 600 ' + Math.max(120, 20 + classCollections.length * 40)" class="w-full overflow-visible">
                                        <defs>
                                            <linearGradient id="classBarGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#10B981" />
                                                <stop offset="100%" stop-color="#3B82F6" />
                                            </linearGradient>
                                        </defs>
                                        <g v-for="(bar, idx) in classChartBars" :key="idx" class="group">
                                            <!-- Class Name Label -->
                                            <text
                                                x="110"
                                                :y="bar.y + 14"
                                                text-anchor="end"
                                                class="text-[10px] font-bold fill-slate-500 dark:fill-slate-400"
                                            >
                                                {{ bar.name }}
                                            </text>
                                            
                                            <!-- Track background bar -->
                                            <rect
                                                x="120"
                                                :y="bar.y"
                                                width="400"
                                                height="18"
                                                rx="4"
                                                class="fill-slate-100 dark:fill-slate-800/60"
                                            />
                                            
                                            <!-- Gradient foreground bar -->
                                            <rect
                                                x="120"
                                                :y="bar.y"
                                                :width="bar.barWidth"
                                                height="18"
                                                rx="4"
                                                fill="url(#classBarGrad)"
                                                class="transition-all duration-500 ease-out cursor-pointer hover:opacity-90"
                                            />
                                            
                                            <!-- Value Label -->
                                            <text
                                                :x="120 + bar.barWidth + 8"
                                                :y="bar.y + 14"
                                                class="text-[9.5px] font-extrabold fill-slate-700 dark:fill-slate-355"
                                            >
                                                ₹{{ formatShortCurrency(bar.total) }}
                                            </text>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Recent Collections Ledger Table -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-750 dark:text-slate-355 tracking-wider uppercase flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Recent Collections Ledger
                            </h3>
                            <router-link 
                                v-slot="{ href, navigate }" 
                                v-if="authStore.hasPermission('receipt.view')"
                                to="/fees/receipts" 
                                custom
                            >
                                <a :href="href" @click="navigate" class="text-xs font-bold text-indigo-650 dark:text-indigo-405 hover:underline">
                                    View All Receipts &rarr;
                                </a>
                            </router-link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-450 font-extrabold uppercase tracking-wider">
                                        <th class="p-4">Student Name</th>
                                        <th class="p-4">Admission No</th>
                                        <th class="p-4">Installment</th>
                                        <th class="p-4">Date</th>
                                        <th class="p-4">Payment Method</th>
                                        <th class="p-4 text-right">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <tr v-if="recentCollections.length === 0">
                                        <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500 font-semibold">
                                            No transactions recorded in this academic session.
                                        </td>
                                    </tr>
                                    <tr 
                                        v-else
                                        v-for="item in recentCollections" 
                                        :key="item.id"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all text-slate-700 dark:text-slate-350"
                                    >
                                        <td class="p-4 font-bold text-slate-900 dark:text-white">{{ item.student_name }}</td>
                                        <td class="p-4 font-semibold text-slate-505 dark:text-slate-400">{{ item.admission_no }}</td>
                                        <td class="p-4 font-medium">{{ item.installment_name }}</td>
                                        <td class="p-4 text-slate-500 dark:text-slate-450">{{ formatDate(item.payment_date) }}</td>
                                        <td class="p-4">
                                            <span :class="[
                                                'px-2.5 py-0.5 rounded-full text-[9px] font-extrabold border uppercase tracking-wider',
                                                item.payment_method === 'Cash' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border-emerald-150 dark:border-emerald-500/20' : '',
                                                item.payment_method === 'Online' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-455 border-blue-155 dark:border-blue-500/20' : '',
                                                item.payment_method === 'Cheque' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-450 border-amber-150 dark:border-amber-500/20' : '',
                                                item.payment_method === 'Bank Transfer' ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-650 dark:text-indigo-455 border-indigo-155 dark:border-indigo-500/20' : '',
                                            ]">
                                                {{ item.payment_method }}
                                            </span>
                                        </td>
                                        <td class="p-4 text-right font-black text-slate-900 dark:text-white">₹{{ formatCurrency(item.amount_paid) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: ACADEMIC INSIGHTS -->
                <div v-else-if="activeTab === 'academics' && canViewAcademics" class="space-y-8 animate-fade-in">
                    <!-- Academic Modules Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Course & Subjects Workspace -->
                        <div v-if="canViewSubjects" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-500/10 border border-violet-100 dark:border-violet-500/20 text-violet-600 dark:text-violet-400 flex items-center justify-center mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-855 dark:text-white mb-2">Academic Course Subjects</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Manage academic subjects, assign grades, define optional courses, and set course categories for school classes.</p>
                            </div>
                            <router-link 
                                v-slot="{ href, navigate }" 
                                to="/subjects" 
                                custom
                            >
                                <a :href="href" @click="navigate" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                                    Go to Subjects Directory &rarr;
                                </a>
                            </router-link>
                        </div>

                        <!-- Exam planning & timetables -->
                        <div v-if="canViewExams || canViewSchedules" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between">
                            <div>
                                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-100 dark:border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-855 dark:text-white mb-2">Examinations & Marksheets</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Configure exam schedules, setup grading scales, publish student marks, and generate printable academic reports.</p>
                            </div>
                            <router-link 
                                v-slot="{ href, navigate }" 
                                to="/exams/schedules" 
                                custom
                            >
                                <a :href="href" @click="navigate" class="text-xs font-bold text-sky-655 dark:text-sky-400 hover:underline flex items-center gap-1">
                                    Manage Exam Timetables &rarr;
                                </a>
                            </router-link>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/auth';

export default {
    name: 'Dashboard',
    setup() {
        const authStore = useAuthStore();
        const stats = ref([]);
        const attendanceStats = ref(null);
        const loading = ref(true);
        
        const scope = ref('school');
        const activeTab = ref('overview');
        
        // Fee collection details
        const recentCollections = ref([]);
        const monthlyTrends = ref([]);
        const classCollections = ref([]);
        
        // Tooltip state for monthly trends
        const hoveredTrendPoint = ref(null);

        // Pre-calculate permissions once for reuse in the template (avoiding duplicate checks)
        const canViewStudents = computed(() => authStore.hasPermission('student.view'));
        const canCreateStudents = computed(() => authStore.hasPermission('student.create'));

        const canViewUsers = computed(() => authStore.hasPermission('user.view'));
        const canCreateUsers = computed(() => authStore.hasPermission('user.create'));

        const canViewSchools = computed(() => authStore.hasPermission('school.view'));
        const canCreateSchools = computed(() => authStore.hasPermission('school.create'));

        const canViewAttendance = computed(() => authStore.hasPermission('attendance.view') && authStore.hasModule('attendance'));

        const canViewFees = computed(() => authStore.hasPermission('fee_collection.view') && authStore.hasModule('fees'));
        const canCreateFees = computed(() => authStore.hasPermission('fee_collection.create') && authStore.hasModule('fees'));

        const canViewSubjects = computed(() => authStore.hasPermission('subject.view') && authStore.hasModule('subjects'));
        const canViewExams = computed(() => authStore.hasPermission('exam.view') && authStore.hasModule('examinations'));
        const canViewSchedules = computed(() => authStore.hasPermission('exam_schedule.view') && authStore.hasModule('examinations'));
        const canViewAcademics = computed(() => canViewSubjects.value || canViewExams.value || canViewSchedules.value);

        // Time-based dynamic greeting
        const greeting = computed(() => {
            const hr = new Date().getHours();
            if (hr < 12) return 'Good Morning';
            if (hr < 17) return 'Good Afternoon';
            return 'Good Evening';
        });

        const formattedTodayDate = computed(() => {
            return new Date().toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        });

        const formattedLastLogin = computed(() => {
            if (!authStore.user?.last_login_at) return 'First session logged';
            const date = new Date(authStore.user.last_login_at);
            return date.toLocaleString();
        });

        // Filter stats cards dynamically based on active modules and permissions
        const overviewStats = computed(() => {
            return stats.value.filter(s => {
                const titleLower = s.title.toLowerCase();
                if (titleLower.includes('collection') || titleLower.includes('fee') || titleLower.includes('discount') || titleLower.includes('fine')) {
                    return false;
                }
                if (titleLower.includes('student')) return canViewStudents.value;
                if (titleLower.includes('teacher')) return canViewUsers.value;
                if (titleLower.includes('class')) return authStore.hasPermission('class.view');
                if (titleLower.includes('subject')) return canViewSubjects.value;
                return true;
            });
        });

        const financeStats = computed(() => {
            if (!canViewFees.value) return [];
            return stats.value.filter(s => 
                s.title.toLowerCase().includes('collection') || 
                s.title.toLowerCase().includes('fee') || 
                s.title.toLowerCase().includes('discount') || 
                s.title.toLowerCase().includes('fine')
            );
        });

        // Attendance progress calculations
        const studentAttendancePercent = computed(() => {
            if (!attendanceStats.value?.student) return 0;
            const present = attendanceStats.value.student.present || 0;
            const absent = attendanceStats.value.student.absent || 0;
            const total = present + absent;
            if (total === 0) return 0;
            return Math.round((present / total) * 100);
        });

        const staffAttendancePercent = computed(() => {
            if (!attendanceStats.value?.staff) return 0;
            const present = attendanceStats.value.staff.present || 0;
            const absent = attendanceStats.value.staff.absent || 0;
            const total = present + absent;
            if (total === 0) return 0;
            return Math.round((present / total) * 100);
        });

        const getStrokeDashOffset = (percent, r = 40) => {
            const c = 2 * Math.PI * r;
            return c - (percent / 100) * c;
        };

        // Monthly trends chart coordinates
        const maxTrendVal = computed(() => {
            if (monthlyTrends.value.length === 0) return 1000;
            return Math.max(...monthlyTrends.value.map(d => d.total), 1000);
        });

        const gridLines = computed(() => {
            const max = maxTrendVal.value;
            const steps = 4;
            const lines = [];
            const topPadding = 30;
            const bottomPadding = 45;
            const chartHeight = 280 - topPadding - bottomPadding;
            
            for (let i = 0; i <= steps; i++) {
                const factor = i / steps;
                const val = max * factor;
                const y = topPadding + chartHeight - factor * chartHeight;
                lines.push({ val, y });
            }
            return lines;
        });

        const lineChartPoints = computed(() => {
            if (monthlyTrends.value.length === 0) return [];
            const data = monthlyTrends.value;
            const max = maxTrendVal.value;
            
            const leftPadding = 60;
            const rightPadding = 20;
            const topPadding = 30;
            const bottomPadding = 45;
            
            const chartWidth = 600 - leftPadding - rightPadding;
            const chartHeight = 280 - topPadding - bottomPadding;
            
            return data.map((d, index) => {
                const x = leftPadding + (index / (data.length - 1 || 1)) * chartWidth;
                const y = topPadding + chartHeight - (d.total / max) * chartHeight;
                return {
                    x,
                    y,
                    val: d.total,
                    month: d.month,
                    monthLabel: d.label,
                };
            });
        });

        const linePathD = computed(() => {
            const points = lineChartPoints.value;
            if (points.length === 0) return '';
            return points.reduce((path, p, i) => {
                return i === 0 ? `M ${p.x} ${p.y}` : `${path} L ${p.x} ${p.y}`;
            }, '');
        });

        const areaPathD = computed(() => {
            const points = lineChartPoints.value;
            if (points.length === 0) return '';
            const startX = points[0].x;
            const endX = points[points.length - 1].x;
            const baseY = 280 - 45;
            return `${linePathD.value} L ${endX} ${baseY} L ${startX} ${baseY} Z`;
        });

        const tooltipX = computed(() => {
            if (!hoveredTrendPoint.value) return '0%';
            return `calc(${(hoveredTrendPoint.value.x / 600) * 100}% - 50px)`;
        });

        const tooltipY = computed(() => {
            if (!hoveredTrendPoint.value) return '0px';
            return `calc(${(hoveredTrendPoint.value.y / 280) * 100}% - 48px)`;
        });

        // Class distribution chart coordinates
        const maxClassVal = computed(() => {
            if (classCollections.value.length === 0) return 1000;
            return Math.max(...classCollections.value.map(c => c.total), 1000);
        });

        const classChartBars = computed(() => {
            if (classCollections.value.length === 0) return [];
            const data = classCollections.value;
            const max = maxClassVal.value;
            
            const leftMargin = 120;
            const rightMargin = 80;
            const chartWidth = 600 - leftMargin - rightMargin;
            
            return data.map((d, index) => {
                const y = 30 + index * 40;
                const barWidth = (d.total / max) * chartWidth;
                return {
                    name: d.class_name,
                    total: d.total,
                    y,
                    barWidth,
                    barWidthPercent: (d.total / max) * 100,
                };
            });
        });

        // Currencies & Date utilities
        const formatCurrency = (val) => {
            if (val === undefined || val === null) return '0.00';
            return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
        };

        const formatShortCurrency = (val) => {
            if (val === undefined || val === null) return '0';
            if (val >= 10000000) return (val / 10000000).toFixed(1) + 'Cr';
            if (val >= 100000) return (val / 100000).toFixed(1) + 'L';
            if (val >= 1000) return (val / 1000).toFixed(1) + 'K';
            return val.toFixed(0);
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        };

        const fetchStats = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/dashboard/stats');
                scope.value = response.data.scope || 'school';
                stats.value = response.data.stats || [];
                attendanceStats.value = response.data.attendance_stats || null;
                
                recentCollections.value = response.data.recent_collections || [];
                monthlyTrends.value = response.data.monthly_trends || [];
                classCollections.value = response.data.class_collections || [];
            } catch (error) {
                console.error('Failed to load dashboard stats', error);
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            fetchStats();
        });

        return {
            authStore,
            stats,
            attendanceStats,
            loading,
            scope,
            activeTab,
            recentCollections,
            monthlyTrends,
            classCollections,
            hoveredTrendPoint,
            formattedLastLogin,
            overviewStats,
            financeStats,
            studentAttendancePercent,
            staffAttendancePercent,
            getStrokeDashOffset,
            gridLines,
            lineChartPoints,
            linePathD,
            areaPathD,
            tooltipX,
            tooltipY,
            classChartBars,
            formatCurrency,
            formatShortCurrency,
            formatDate,
            greeting,
            formattedTodayDate,
            
            // Computed Permission Flags
            canViewStudents,
            canCreateStudents,
            canViewUsers,
            canCreateUsers,
            canViewSchools,
            canCreateSchools,
            canViewAttendance,
            canViewFees,
            canCreateFees,
            canViewSubjects,
            canViewExams,
            canViewSchedules,
            canViewAcademics
        };
    }
}
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes wave {
    0% { transform: rotate( 0.0deg) }
    10% { transform: rotate(14.0deg) }
    20% { transform: rotate(-8.0deg) }
    30% { transform: rotate(14.0deg) }
    40% { transform: rotate(-4.0deg) }
    50% { transform: rotate(10.0deg) }
    60% { transform: rotate( 0.0deg) }
    100% { transform: rotate( 0.0deg) }
}
.animate-wave {
    animation: wave 2.5s infinite;
}
</style>
