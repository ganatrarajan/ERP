<template>
    <div class="space-y-6 pb-12 text-slate-800 dark:text-slate-100 transition-colors duration-200">
        <!-- HEADER / CONTROL PANEL -->
        <div class="relative z-30 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-6 shadow-sm flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
            <div class="space-y-1">
                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-extrabold tracking-widest uppercase mb-1 block">
                    {{ authStore.user?.school?.name || 'EduvoraX ERP SaaS Control' }}
                </span>
                <h1 class="text-2xl font-black tracking-tight flex items-center gap-3">
                    {{ greeting }}, {{ authStore.user?.name }}!
                    <span class="animate-wave origin-[70%_70%] inline-block">👋</span>
                </h1>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span v-if="activeAcademicYear" class="inline-flex items-center gap-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        Academic Session: {{ activeAcademicYear.title }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        Role: <span class="font-bold text-slate-700 dark:text-slate-300">{{ authStore.roles[0] || 'Teacher' }}</span>
                    </span>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                <!-- Global Search -->
                <div class="relative flex-1 sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="classSearchQuery" 
                        type="text" 
                        placeholder="Search classes or items..." 
                        class="w-full pl-9 pr-4 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all placeholder-slate-400 dark:placeholder-slate-500 text-slate-700 dark:text-slate-200"
                    />
                </div>

                <!-- Live Ticking Date & Time -->
                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 px-4 py-2 rounded-xl flex items-center gap-3 shrink-0">
                    <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="text-[11px] leading-tight font-extrabold text-slate-600 dark:text-slate-350">
                        <div>{{ formattedTodayDate }}</div>
                        <div class="text-indigo-600 dark:text-indigo-400 tabular-nums font-black">{{ currentClockTime }}</div>
                    </div>
                </div>

                <!-- Notifications Bell Dropdown -->
                <div class="relative">
                    <button 
                        @click="showNotifications = !showNotifications"
                        class="p-2 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl relative transition-all duration-200 cursor-pointer text-slate-600 dark:text-slate-400"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span v-if="unreadNoticeCount > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full border border-white dark:border-slate-950 animate-ping"></span>
                        <span v-if="unreadNoticeCount > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full border border-white dark:border-slate-950"></span>
                    </button>

                    <!-- Notifications Dropdown Menu -->
                    <div 
                        v-if="showNotifications" 
                        class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden animate-fade-in"
                    >
                        <div class="p-4 border-b border-slate-105 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-950/50">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Notice Bulletin</span>
                            <span class="px-2 py-0.5 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 text-[10px] font-black rounded-full">{{ unreadNoticeCount }} New</span>
                        </div>
                        <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                            <div v-if="dashboardNotices.length === 0" class="p-6 text-center text-xs text-slate-400 dark:text-slate-500 font-semibold">
                                No notices published in this session.
                            </div>
                            <div 
                                v-else
                                v-for="item in dashboardNotices" 
                                :key="item.timestamp"
                                class="p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors flex gap-3 text-xs"
                            >
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                </div>
                                <div class="space-y-0.5 min-w-0 flex-1">
                                    <h4 class="font-bold text-slate-900 dark:text-white truncate">{{ item.title }}</h4>
                                    <p class="text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed text-[11px]">{{ item.description }}</p>
                                    <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1 font-semibold">
                                        <span>By {{ item.user_name }}</span>
                                        <span>{{ formatRelativeTime(item.timestamp) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <router-link 
                            v-if="authStore.hasModule('notices') && authStore.hasPermission('notice.view')"
                            to="/notices" 
                            class="block p-3 text-center text-xs font-bold text-indigo-600 dark:text-indigo-400 border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                            @click="showNotifications = false"
                        >
                            View All Notices
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- SKELETON LOADER -->
        <div v-if="loading" class="space-y-6 animate-pulse">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="i in 6" :key="i" class="h-32 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <div class="space-y-2">
                            <div class="h-3.5 w-24 bg-slate-200 dark:bg-slate-800 rounded-md"></div>
                            <div class="h-8 w-16 bg-slate-300 dark:bg-slate-700 rounded-md"></div>
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

        <!-- MAIN DASHBOARD CONTENT -->
        <div v-else class="space-y-6">
            
            <!-- A. SUPER ADMIN DASHBOARD -->
            <div v-if="scope === 'super_admin'" class="space-y-6 animate-fade-in">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div 
                        v-for="stat in stats" 
                        :key="stat.title" 
                        class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-slate-350 dark:hover:border-slate-700 transition-all duration-300 group flex flex-col justify-between"
                    >
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block">{{ stat.title }}</span>
                                <h3 class="text-3xl font-black text-slate-850 dark:text-white tracking-tight tabular-nums group-hover:text-indigo-655 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ stat.value }}
                                </h3>
                            </div>
                            <div :class="[
                                'w-10 h-10 rounded-xl flex items-center justify-center border shrink-0',
                                stat.color === 'indigo' ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-100 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400' : '',
                                stat.color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400' : '',
                                stat.color === 'rose' ? 'bg-rose-50 dark:bg-rose-500/10 border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400' : '',
                                stat.color === 'blue' ? 'bg-blue-50 dark:bg-blue-500/10 border-blue-100 dark:border-blue-500/20 text-blue-600 dark:text-blue-400' : '',
                            ]">
                                <svg v-if="stat.icon === 'AcademicCapIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                                <svg v-else-if="stat.icon === 'CheckCircleIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg v-else-if="stat.icon === 'XCircleIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg v-else-if="stat.icon === 'UsersIcon'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                        </div>
                        <p v-if="stat.description" class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">{{ stat.description }}</p>
                    </div>
                </div>

                <!-- Platform Information Section -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 md:p-8 rounded-2xl shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        SaaS Platform Information
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">
                        This multi-tenant School ERP Platform handles administration, course planning, fee structure, student data, and teacher rosters. As a Super Administrator, you are managing institutional setups, subscription states, and cross-tenant roles/permissions.
                    </p>
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-6">
                        <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            Laravel 12 API Ready
                        </div>
                        <div class="flex items-center gap-2 text-sky-600 dark:text-sky-400 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                            Vue 3 Pinia SPA
                        </div>
                    </div>
                </div>
            </div>

            <!-- B. SCHOOL ADMIN / TEACHER SCOPE -->
            <div v-else-if="scope === 'school'" class="space-y-6 animate-fade-in">
                    
                    <!-- Section 1 - Overview Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- 1. Students Card -->
                        <router-link 
                            v-if="canViewStudents" 
                            to="/students" 
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-indigo-300 dark:hover:border-indigo-800/50 transition-all duration-305 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-505 tracking-wider uppercase block">Students</span>
                                    <h3 class="text-3.5xl font-black text-slate-850 dark:text-white tracking-tight tabular-nums group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ studentsCount }}
                                    </h3>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">Active student enrollments &rarr;</p>
                        </router-link>

                        <!-- 2. Teachers Card -->
                        <router-link 
                            v-if="canViewUsers" 
                            :to="{ path: '/users', query: { role: 'Teacher' } }"
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-sky-300 dark:hover:border-sky-800/50 transition-all duration-305 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-extrabold text-slate-405 dark:text-slate-505 tracking-wider uppercase block">Teachers</span>
                                    <h3 class="text-3.5xl font-black text-slate-850 dark:text-white tracking-tight tabular-nums group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                        {{ teachersCount }}
                                    </h3>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-100 dark:border-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">Active teacher profiles &rarr;</p>
                        </router-link>

                        <!-- 3. Classes Card -->
                        <router-link 
                            v-if="authStore.hasPermission('class.view')" 
                            to="/classes" 
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-emerald-300 dark:hover:border-emerald-800/50 transition-all duration-305 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block">Classes</span>
                                    <h3 class="text-3.5xl font-black text-slate-850 dark:text-white tracking-tight tabular-nums group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        {{ classesCount }}
                                    </h3>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">Configured curriculum groups &rarr;</p>
                        </router-link>

                        <!-- 4. Total Fee Assigned Card -->
                        <router-link 
                            v-if="canViewFees" 
                            :to="{ path: '/fees/reports', query: { report_type: 'pending', auto: 'true' } }"
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-indigo-300 dark:hover:border-indigo-800/50 transition-all duration-355 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block">Total Fee Assigned</span>
                                    <h3 class="text-2.5xl font-black text-slate-850 dark:text-white tracking-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ totalAssignedFeesValue }}
                                    </h3>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">Outstanding balance ledger &rarr;</p>
                        </router-link>

                        <!-- 5. Pending Fees Card -->
                        <router-link 
                            v-if="canViewFees" 
                            :to="{ path: '/fees/reports', query: { report_type: 'pending', auto: 'true' } }"
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-amber-300 dark:hover:border-amber-800/50 transition-all duration-350 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block">Pending Fees</span>
                                    <h3 class="text-2.5xl font-black text-slate-850 dark:text-white tracking-tight group-hover:text-amber-655 dark:group-hover:text-amber-400 transition-colors">
                                        {{ pendingFeesValue }}
                                    </h3>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-100 dark:border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">Outstanding balance ledger &rarr;</p>
                        </router-link>

                        <!-- 6. Today's Collection Card -->
                        <router-link 
                            v-if="canViewFees" 
                            :to="{ path: '/fees/receipts', query: { date: todayDateString } }"
                            class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 hover:border-pink-300 dark:hover:border-pink-800/50 transition-all duration-355 group flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 tracking-wider uppercase block">Today's Collection</span>
                                    <h3 class="text-2.5xl font-black text-slate-850 dark:text-white tracking-tight group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">
                                        {{ todayCollectionValue }}
                                    </h3>
                                </div>
                                <div class="w-11 h-11 rounded-xl bg-pink-50 dark:bg-pink-500/10 border border-pink-100 dark:border-pink-500/20 text-pink-600 dark:text-pink-400 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-450 dark:text-slate-500 font-bold mt-4">Transactions logged today &rarr;</p>
                        </router-link>
                    </div>

                    <!-- TWO COLUMN INTERACTIVE BODY -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- LEFT COLUMN: QUICK ACTIONS -->
                        <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
                            <div>
                                <h3 class="text-xs font-black text-slate-455 dark:text-slate-500 uppercase tracking-widest mb-2">Section 2 - Quick Shortcuts</h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal mb-5">Instantly launch forms and tasks for school workflow components.</p>
                            </div>
                            <div class="space-y-2.5">
                                <router-link 
                                    v-if="authStore.hasPermission('student.create') && authStore.hasModule('students')" 
                                    to="/students/create" 
                                    class="w-full py-2.5 px-4 bg-indigo-50/70 hover:bg-indigo-100 dark:bg-indigo-950/20 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/30 text-xs font-bold rounded-xl transition-all flex items-center justify-between group"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                        Add New Student
                                    </span>
                                    <span class="text-sm font-light opacity-50 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </router-link>

                                <router-link 
                                    v-if="authStore.hasPermission('fee_collection.create') && authStore.hasModule('fees')" 
                                    to="/fees/collection" 
                                    class="w-full py-2.5 px-4 bg-emerald-50/70 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30 text-xs font-bold rounded-xl transition-all flex items-center justify-between group"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        Collect Student Fee
                                    </span>
                                    <span class="text-sm font-light opacity-50 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </router-link>

                                <router-link 
                                    v-if="authStore.hasPermission('user.create')" 
                                    to="/users/create" 
                                    class="w-full py-2.5 px-4 bg-sky-50/70 hover:bg-sky-100 dark:bg-sky-950/20 dark:hover:bg-sky-900/30 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-900/30 text-xs font-bold rounded-xl transition-all flex items-center justify-between group"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                        Add Teacher Account
                                    </span>
                                    <span class="text-sm font-light opacity-50 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </router-link>

                                <router-link 
                                    v-if="authStore.hasPermission('attendance.create') && authStore.hasModule('attendance')" 
                                    to="/attendance" 
                                    class="w-full py-2.5 px-4 bg-violet-50/70 hover:bg-violet-100 dark:bg-violet-950/20 dark:hover:bg-violet-900/30 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-900/30 text-xs font-bold rounded-xl transition-all flex items-center justify-between group"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        Take Attendance Today
                                    </span>
                                    <span class="text-sm font-light opacity-50 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </router-link>

                                <router-link 
                                    v-if="authStore.hasPermission('notice.create') && authStore.hasModule('notices')" 
                                    to="/notices" 
                                    class="w-full py-2.5 px-4 bg-amber-50/70 hover:bg-amber-100 dark:bg-amber-950/20 dark:hover:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30 text-xs font-bold rounded-xl transition-all flex items-center justify-between group"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                        Publish Notice Announcement
                                    </span>
                                    <span class="text-sm font-light opacity-50 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </router-link>

                                <router-link 
                                    v-if="authStore.hasPermission('report.view') && authStore.hasModule('fees')" 
                                    to="/fees/reports" 
                                    class="w-full py-2.5 px-4 bg-pink-50/70 hover:bg-pink-100 dark:bg-pink-950/20 dark:hover:bg-pink-900/30 text-pink-600 dark:text-pink-400 border border-pink-100 dark:border-pink-900/30 text-xs font-bold rounded-xl transition-all flex items-center justify-between group"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        View System Reports
                                    </span>
                                    <span class="text-sm font-light opacity-50 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </router-link>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: ATTENDANCE SUMMARY -->
                        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xs font-black text-slate-455 dark:text-slate-500 uppercase tracking-widest">Section 3 - Today's Attendance Rates</h3>
                                </div>
                                <div v-if="!canViewAttendance || !attendanceStats" class="h-44 flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-xs font-bold text-slate-400">Attendance tracking is inactive</span>
                                </div>
                                <div v-else class="space-y-6 pt-2">
                                    <!-- Rings Grid -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <!-- Student Attendance Ring -->
                                        <router-link 
                                            to="/attendance?tab=mark"
                                            class="flex items-center gap-4 bg-slate-50/50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-850 p-4 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-900/60 hover:bg-slate-50 dark:hover:bg-slate-950/50 hover:shadow-sm transition-all"
                                        >
                                            <div class="relative w-18 h-18 flex items-center justify-center shrink-0">
                                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                                    <circle cx="50" cy="50" r="40" fill="transparent" stroke="#F1F5F9" stroke-width="8" class="dark:stroke-slate-800" />
                                                    <circle cx="50" cy="50" r="40" fill="transparent" stroke="url(#studProgressGrad)" stroke-width="8"
                                                            stroke-dasharray="251.35" :stroke-dashoffset="getStrokeDashOffset(studentAttendancePercent, 40)"
                                                            stroke-linecap="round" class="transition-all duration-1000 ease-out" />
                                                </svg>
                                                <span class="absolute text-sm font-black text-slate-900 dark:text-white">{{ studentAttendancePercent }}%</span>
                                            </div>
                                            <div class="space-y-1">
                                                <h4 class="text-xs font-black text-slate-900 dark:text-white">Students Attendance</h4>
                                                <div class="text-[10px] text-slate-500 font-bold space-y-0.5">
                                                    <div class="font-extrabold text-slate-700 dark:text-slate-350">Total Students: {{ (attendanceStats.student.present || 0) + (attendanceStats.student.absent || 0) }}</div>
                                                    <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Present: {{ attendanceStats.student.present }}</div>
                                                    <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Absent: {{ attendanceStats.student.absent }}</div>
                                                </div>
                                            </div>
                                        </router-link>
 
                                        <!-- Staff Attendance Ring -->
                                        <router-link 
                                            to="/attendance?tab=staff&auto=true"
                                            class="flex items-center gap-4 bg-slate-50/50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-850 p-4 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-900/60 hover:bg-slate-50 dark:hover:bg-slate-950/50 hover:shadow-sm transition-all"
                                        >
                                            <div class="relative w-18 h-18 flex items-center justify-center shrink-0">
                                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                                    <circle cx="50" cy="50" r="40" fill="transparent" stroke="#F1F5F9" stroke-width="8" class="dark:stroke-slate-800" />
                                                    <circle cx="50" cy="50" r="40" fill="transparent" stroke="url(#staffProgressGrad)" stroke-width="8"
                                                            stroke-dasharray="251.35" :stroke-dashoffset="getStrokeDashOffset(staffAttendancePercent, 40)"
                                                            stroke-linecap="round" class="transition-all duration-1000 ease-out" />
                                                </svg>
                                                <span class="absolute text-sm font-black text-slate-900 dark:text-white">{{ staffAttendancePercent }}%</span>
                                            </div>
                                            <div class="space-y-1">
                                                <h4 class="text-xs font-black text-slate-900 dark:text-white">Staff Attendance</h4>
                                                <div class="text-[10px] text-slate-500 font-bold space-y-0.5">
                                                    <div class="font-extrabold text-slate-700 dark:text-slate-350">Total Staff: {{ (attendanceStats.staff.present || 0) + (attendanceStats.staff.absent || 0) }}</div>
                                                    <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span> Present: {{ attendanceStats.staff.present }}</div>
                                                    <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Absent: {{ attendanceStats.staff.absent }}</div>
                                                </div>
                                            </div>
                                        </router-link>
                                    </div>
                                    <div class="border-t border-slate-100 dark:border-slate-800/80 pt-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Class-wise Attendance Rates</h4>
                                        </div>
                                        <div v-if="classWiseOverview.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">
                                            No class-wise data available
                                        </div>
                                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                                            <div 
                                                v-for="c in classWiseOverview" 
                                                :key="c.class_id"
                                                class="flex flex-col gap-2 p-3.5 bg-slate-50/50 dark:bg-slate-950/30 border border-slate-100 dark:border-slate-850 rounded-xl hover:border-indigo-250 dark:hover:border-indigo-900/50 hover:bg-slate-50 dark:hover:bg-slate-950/50 transition-all cursor-pointer"
                                                @click="toggleClassExpansion(c.class_id)"
                                            >
                                                <div class="flex justify-between items-start text-xs">
                                                    <span class="font-black text-slate-800 dark:text-slate-205 flex items-center gap-1.5">
                                                        {{ c.class_name }}
                                                        <svg class="w-3.5 h-3.5 text-slate-405 transform transition-transform duration-200" :class="{ 'rotate-180': expandedClasses.includes(c.class_id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                                    </span>
                                                    <div class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 flex flex-col items-end gap-0.5">
                                                        <div class="font-extrabold text-slate-705 dark:text-slate-300 mb-0.5">Total Students: {{ c.students }}</div>
                                                        <div class="flex items-center gap-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ c.present }} Present
                                                        </div>
                                                        <div class="flex items-center gap-1 text-rose-600 dark:text-rose-455 font-bold">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> {{ c.absent }} Absent
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                                        <div 
                                                            class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                                                            :style="{ width: c.students > 0 ? (c.present / c.students * 100) + '%' : '0%' }"
                                                        ></div>
                                                    </div>
                                                    <span class="text-[10px] font-black text-slate-700 dark:text-slate-350 w-8 text-right shrink-0">
                                                        {{ c.students > 0 ? Math.round(c.present / c.students * 100) : 0 }}%
                                                    </span>
                                                </div>

                                                <!-- Sections list breakdown (Visible only when expanded) -->
                                                <div 
                                                    v-if="expandedClasses.includes(c.class_id)" 
                                                    class="mt-3 border-t border-slate-150 dark:border-slate-800/80 pt-3 space-y-2"
                                                    @click.stop
                                                >
                                                    <div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Sections Breakdown</div>
                                                    <div v-if="!c.sections || c.sections.length === 0" class="text-[10px] text-slate-400 italic">No sections configured</div>
                                                    <div 
                                                        v-else
                                                        v-for="sec in c.sections"
                                                        :key="sec.section_id"
                                                        @click="goToSectionAttendance(c.class_id, sec.section_id)"
                                                        class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/60 hover:border-indigo-500 dark:hover:border-indigo-500/50 hover:bg-indigo-50/20 dark:hover:bg-indigo-950/20 transition-all cursor-pointer group"
                                                    >
                                                        <span class="text-[11px] font-black text-slate-705 dark:text-slate-300">{{ sec.section_name }}</span>
                                                        <div class="flex items-center gap-3">
                                                            <div class="text-[9px] font-bold text-slate-400 flex items-center gap-2">
                                                                <span class="font-extrabold text-slate-700 dark:text-slate-350">Total: {{ sec.students }}</span>
                                                                <span class="text-slate-205 dark:text-slate-800">|</span>
                                                                <span class="text-emerald-600 dark:text-emerald-450 font-black">{{ sec.present }} Present</span>
                                                                <span class="text-slate-205 dark:text-slate-800">|</span>
                                                                <span class="text-rose-600 dark:text-rose-455 font-black">{{ sec.absent }} Absent</span>
                                                            </div>
                                                            <span class="text-[9px] font-black text-indigo-600 dark:text-indigo-400 group-hover:translate-x-0.5 transition-transform">
                                                                Manage &rarr;
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Section 6 - Recent Activities Timeline -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-2xl shadow-sm p-6 space-y-4">
                        <h3 class="text-xs font-black text-slate-455 dark:text-slate-500 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Section 6 - Recent Operational Activities
                        </h3>
                        <div class="relative pl-6 border-l border-slate-100 dark:border-slate-800 space-y-6">
                            <div v-if="recentActivities.length === 0" class="py-4 text-center text-xs text-slate-400 dark:text-slate-550 font-bold">
                                No recent activities logged.
                            </div>
                            <div 
                                v-else
                                v-for="(act, idx) in recentActivities" 
                                :key="idx" 
                                class="relative group"
                            >
                                <!-- Marker Dot -->
                                <span :class="[
                                    'absolute -left-[31px] top-0.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-slate-900 transition-all group-hover:scale-125 z-10',
                                    act.type === 'student_added' ? 'bg-indigo-600' : '',
                                    act.type === 'fee_collected' ? 'bg-emerald-500' : '',
                                    act.type === 'attendance_submitted' ? 'bg-violet-500' : '',
                                    act.type === 'teacher_added' ? 'bg-sky-500' : '',
                                    act.type === 'notice_published' ? 'bg-amber-500' : '',
                                ]"></span>
                                
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-white">{{ act.title }}</h4>
                                        <span class="text-[10px] text-slate-400 font-bold">{{ formatRelativeTime(act.timestamp) }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ act.description }}</p>
                                    <div class="text-[10px] text-slate-400/80 font-bold">
                                        Action Performed By: <span class="text-slate-500 dark:text-slate-400">{{ act.user_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            </div>

        </div>

        <!-- LINEAR GRADIENT DEFS FOR CIRCLE CHARTS -->
        <svg class="hidden w-0 h-0" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="studProgressGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#4F46E5" />
                    <stop offset="100%" stop-color="#06B6D4" />
                </linearGradient>
                <linearGradient id="staffProgressGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#8B5CF6" />
                    <stop offset="100%" stop-color="#EC4899" />
                </linearGradient>
            </defs>
        </svg>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

export default {
    name: 'Dashboard',
    setup() {
        const authStore = useAuthStore();
        const router = useRouter();
        const stats = ref([]);
        const attendanceStats = ref(null);
        const loading = ref(true);
        
        const scope = ref('school');
        const activeTab = ref('overview');
        
        // Fee collection details
        const recentCollections = ref([]);
        const monthlyTrends = ref([]);
        const classCollections = ref([]);
        
        // New fields
        const pendingStudentsCount = ref(0);
        const classWiseOverview = ref([]);
        const expandedClasses = ref([]);
        const recentActivities = ref([]);
        const activeAcademicYear = ref(null);

        const toggleClassExpansion = (classId) => {
            if (expandedClasses.value.includes(classId)) {
                expandedClasses.value = expandedClasses.value.filter(id => id !== classId);
            } else {
                expandedClasses.value.push(classId);
            }
        };

        const goToSectionAttendance = (classId, sectionId) => {
            router.push({
                path: '/attendance',
                query: {
                    tab: 'mark',
                    class_id: classId,
                    section_id: sectionId,
                    date: todayDateString.value,
                    auto: 'true'
                }
            });
        };

        // Header controls
        const classSearchQuery = ref('');
        const showNotifications = ref(false);
        const currentClockTime = ref('');

        // Class table sorting / filtering / pagination
        const classSortKey = ref('class_name');
        const classSortAsc = ref(true);
        const classFilterType = ref('all');
        const classCurrentPage = ref(1);
        const classItemsPerPage = ref(5);
        
        // Tooltip state for monthly trends
        const hoveredTrendPoint = ref(null);

        // Pre-calculate permissions once for reuse in the template
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

        const todayDateString = computed(() => {
            return new Date().toLocaleDateString('en-CA');
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

        // Clean stats getters for Section 1 overview cards
        const studentsCount = computed(() => {
            const item = stats.value.find(s => s.title.toLowerCase().includes('student'));
            return item ? item.value : 0;
        });

        const teachersCount = computed(() => {
            const item = stats.value.find(s => s.title.toLowerCase().includes('teacher'));
            return item ? item.value : 0;
        });

        const classesCount = computed(() => {
            const item = stats.value.find(s => s.title.toLowerCase().includes('class'));
            return item ? item.value : 0;
        });

        const pendingFeesValue = computed(() => {
            const item = stats.value.find(s => s.title.toLowerCase().includes('pending fee'));
            return item ? item.value : '₹0.00';
        });

        const totalAssignedFeesValue = computed(() => {
            const item = stats.value.find(s => s.title.toLowerCase().includes('total fee assigned'));
            return item ? item.value : '₹0.00';
        });

        const todayCollectionValue = computed(() => {
            const item = stats.value.find(s => s.title.toLowerCase().includes("today's collection"));
            return item ? item.value : '₹0.00';
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

        // Notices list computed from recent activities
        const dashboardNotices = computed(() => {
            return recentActivities.value.filter(a => a.type === 'notice_published');
        });

        const unreadNoticeCount = computed(() => {
            return Math.min(3, dashboardNotices.value.length);
        });

        // Class-wise Overview sort and filter computation
        const filteredClasses = computed(() => {
            let list = [...classWiseOverview.value];

            // 1. Search Query (Class name)
            if (classSearchQuery.value.trim() !== '') {
                const q = classSearchQuery.value.toLowerCase();
                list = list.filter(c => c.class_name.toLowerCase().includes(q));
            }

            // 2. Dropdown Filter Type
            if (classFilterType.value === 'has_pending_fees') {
                list = list.filter(c => c.pending_fee > 0);
            } else if (classFilterType.value === 'low_attendance') {
                list = list.filter(c => {
                    const total = c.present + c.absent;
                    if (total === 0) return false;
                    const rate = (c.present / total) * 105;
                    return rate < 90;
                });
            }

            // 3. Table Column Sorting
            list.sort((a, b) => {
                let valA = a[classSortKey.value];
                let valB = b[classSortKey.value];

                if (classSortKey.value === 'class_name') {
                    return classSortAsc.value 
                        ? valA.localeCompare(valB) 
                        : valB.localeCompare(valA);
                } else {
                    return classSortAsc.value 
                        ? (valA - valB) 
                        : (valB - valA);
                }
            });

            return list;
        });

        const paginatedClasses = computed(() => {
            const start = (classCurrentPage.value - 1) * classItemsPerPage.value;
            const end = start + classItemsPerPage.value;
            return filteredClasses.value.slice(start, end);
        });

        const classTotalPages = computed(() => {
            return Math.ceil(filteredClasses.value.length / classItemsPerPage.value) || 1;
        });

        const setClassSort = (key) => {
            if (classSortKey.value === key) {
                classSortAsc.value = !classSortAsc.value;
            } else {
                classSortKey.value = key;
                classSortAsc.value = true;
            }
            classCurrentPage.value = 1;
        };

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

        const formatRelativeTime = (isoString) => {
            if (!isoString) return '';
            const date = new Date(isoString);
            const now = new Date();
            const diffMs = now - date;
            const diffSec = Math.floor(diffMs / 1000);
            const diffMin = Math.floor(diffSec / 60);
            const diffHr = Math.floor(diffMin / 60);
            const diffDays = Math.floor(diffHr / 24);

            if (diffSec < 60) return 'Just now';
            if (diffMin < 60) return `${diffMin}m ago`;
            if (diffHr < 24) return `${diffHr}h ago`;
            if (diffDays === 1) return 'Yesterday';
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
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
                
                // Redesigned response inputs
                pendingStudentsCount.value = response.data.pending_students_count || 0;
                classWiseOverview.value = response.data.class_wise_overview || [];
                recentActivities.value = response.data.recent_activities || [];
                activeAcademicYear.value = response.data.active_academic_year || null;
            } catch (error) {
                console.error('Failed to load dashboard stats', error);
            } finally {
                loading.value = false;
            }
        };

        const updateClock = () => {
            const now = new Date();
            currentClockTime.value = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        };

        let clockInterval = null;

        onMounted(() => {
            fetchStats();
            updateClock();
            clockInterval = setInterval(updateClock, 1000);
        });

        onUnmounted(() => {
            if (clockInterval) clearInterval(clockInterval);
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
            todayDateString,
            
            // New Reactive Refs
            pendingStudentsCount,
            classWiseOverview,
            expandedClasses,
            toggleClassExpansion,
            goToSectionAttendance,
            recentActivities,
            activeAcademicYear,
            classSearchQuery,
            showNotifications,
            currentClockTime,
            classSortKey,
            classSortAsc,
            classFilterType,
            classCurrentPage,
            classItemsPerPage,
            filteredClasses,
            paginatedClasses,
            classTotalPages,
            setClassSort,
            formatRelativeTime,
            dashboardNotices,
            unreadNoticeCount,
            studentsCount,
            teachersCount,
            classesCount,
            pendingFeesValue,
            totalAssignedFeesValue,
            todayCollectionValue,
            
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
