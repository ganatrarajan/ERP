<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex transition-colors duration-250">
        <!-- Sidebar -->
        <aside :class="[
            'w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex-shrink-0 flex flex-col transition-all duration-300 md:translate-x-0 z-30',
            mobileMenuOpen ? 'translate-x-0 fixed inset-y-0 left-0' : '-translate-x-full fixed inset-y-0 left-0 md:relative'
        ]">
            <!-- Header -->
            <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800 gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-lg font-bold shadow-md shadow-indigo-500/20">
                    Ω
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-800 dark:text-white tracking-wide leading-none">School ERP</h1>
                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold tracking-widest uppercase">SaaS Engine</span>
                </div>
            </div>

            <!-- School Profile Info -->
            <div class="p-4 border-b border-slate-200/60 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold overflow-hidden">
                        <img v-if="authStore.user?.school?.logo" :src="authStore.user.school.logo" class="object-cover w-full h-full" />
                        <span v-else>{{ (authStore.user?.school?.name || 'SA').substring(0, 2).toUpperCase() }}</span>
                    </div>
                    <div class="overflow-hidden">
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">{{ authStore.user?.school?.name || 'Super Administration' }}</h4>
                            <span v-if="authStore.user?.school?.school_code" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800" title="School Code">
                                {{ authStore.user.school.school_code }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ authStore.user?.email }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-4 space-y-3 overflow-y-auto">
                <!-- Group 1: Administration -->
                <div v-if="showAdminGroup" class="space-y-1">
                    <button 
                        @click="toggleSection('admin')"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        <span>Administration</span>
                        <svg 
                            :class="['w-3 h-3 transition-transform duration-200 text-slate-400', expandedSections.admin ? 'rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div v-show="expandedSections.admin" class="space-y-1 pl-1 border-l border-slate-150 dark:border-slate-800/80 ml-2">
                        <router-link 
                            v-if="authStore.hasPermission('dashboard.view')" 
                            to="/dashboard" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                            Dashboard
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('school.view')" 
                            to="/schools" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/schools') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Schools List
                        </router-link>

                        <router-link 
                            v-if="authStore.isSuperAdmin" 
                            to="/password-resets" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/password-resets') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m-2 4a5 5 0 015 5M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path></svg>
                            Password Resets
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('user.view')" 
                            to="/users" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/users') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            User Management
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('role.view')" 
                            to="/roles" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/roles') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Roles & Permissions
                        </router-link>
                    </div>
                </div>

                <!-- Group 2: Academics -->
                <div v-if="showAcademicsGroup" class="space-y-1">
                    <button 
                        @click="toggleSection('academics')"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        <span>Academics</span>
                        <svg 
                            :class="['w-3 h-3 transition-transform duration-200 text-slate-400', expandedSections.academics ? 'rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div v-show="expandedSections.academics" class="space-y-1 pl-1 border-l border-slate-150 dark:border-slate-800/80 ml-2">
                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('academics') && authStore.hasPermission('academic_year.view')" 
                            to="/academic-years" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/academic-years') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Academic Years
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('academics') && authStore.hasPermission('class.view')" 
                            to="/classes" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/classes') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            Classes
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('academics') && authStore.hasPermission('section.view')" 
                            to="/sections" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/sections') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                            Sections
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('subjects') && authStore.hasPermission('subject.view')" 
                            to="/subjects" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/subjects') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            Subject List
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('subjects') && authStore.hasPermission('subject.view')" 
                            to="/academics/optional-subjects" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/academics/optional-subjects') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Optional Subjects
                        </router-link>
                    </div>
                </div>

                <!-- Group 3: Students -->
                <div v-if="showStudentsGroup" class="space-y-1">
                    <button 
                        @click="toggleSection('students')"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        <span>Students</span>
                        <svg 
                            :class="['w-3 h-3 transition-transform duration-200 text-slate-400', expandedSections.students ? 'rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div v-show="expandedSections.students" class="space-y-1 pl-1 border-l border-slate-150 dark:border-slate-800/80 ml-2">
                        <router-link 
                            v-if="authStore.hasPermission('student.view')" 
                            to="/students" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/students') && $route.name !== 'students.create' && $route.name !== 'students.promotion' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Student List
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('student.create')" 
                            to="/students/create" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/students/create') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Add Student
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('promotion.create')" 
                            to="/students/promotion" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/students/promotion') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            Student Promotion
                        </router-link>
                    </div>
                </div>

                <!-- Group 4: Daily Operations -->
                <div v-if="showOperationsGroup" class="space-y-1">
                    <button 
                        @click="toggleSection('operations')"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        <span>Attendance</span>
                        <svg 
                            :class="['w-3 h-3 transition-transform duration-200 text-slate-400', expandedSections.operations ? 'rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div v-show="expandedSections.operations" class="space-y-1 pl-1 border-l border-slate-150 dark:border-slate-800/80 ml-2">
                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('attendance') && authStore.hasPermission('attendance.view')" 
                            to="/attendance?tab=mark" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[route.path === '/attendance' && (route.query.tab === 'mark' || route.query.tab === 'monthly' || !route.query.tab) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Student Attendance
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('attendance') && authStore.hasPermission('attendance.view')" 
                            to="/attendance?tab=staff" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[route.path === '/attendance' && route.query.tab === 'staff' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Staff Attendance
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('attendance') && authStore.hasPermission('attendance.view')" 
                            to="/attendance?tab=holidays" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[route.path === '/attendance' && route.query.tab === 'holidays' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Holidays
                        </router-link>

                        <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('attendance') && authStore.hasPermission('attendance.view')" 
                            to="/attendance?tab=reports" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[route.path === '/attendance' && route.query.tab === 'reports' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m14 0h-3a2 2 0 00-2 2v3m2 4H9m6 0a3 3 0 11-6 0v-1m6 0H9m11-4V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2z"></path></svg>
                            Reports
                        </router-link>

                        <!-- <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('homework') && authStore.hasPermission('homework.view')" 
                            to="/homeworks" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/homeworks') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            Homework List
                        </router-link> -->

                        <!-- <router-link 
                            v-if="authStore.user?.school_id && authStore.hasModule('notices') && authStore.hasPermission('notice.view')" 
                            to="/notices" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/notices') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            Notice Board
                        </router-link> -->
                    </div>
                </div>

                <!-- Group 5: Examinations -->
                <div v-if="showExamsGroup" class="space-y-1">
                    <button 
                        @click="toggleSection('exams')"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        <span>Examinations</span>
                        <svg 
                            :class="['w-3 h-3 transition-transform duration-200 text-slate-400', expandedSections.exams ? 'rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div v-show="expandedSections.exams" class="space-y-1 pl-1 border-l border-slate-150 dark:border-slate-800/80 ml-2">
                        <router-link 
                            v-if="authStore.hasPermission('exam.view')" 
                            to="/exams/grade-scales" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/exams/grade-scales') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Grade Scales
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('exam.view')" 
                            to="/exams/exam-types" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/exams/exam-types') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Exam Types
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('exam.view')" 
                            to="/exams/exams" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/exams/exams') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Exams Master
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('exam_schedule.view') || authStore.hasPermission('exam_schedule.create') || authStore.hasPermission('exam_schedule.edit')" 
                            to="/exams/schedules" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/exams/schedules') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Exam Schedules
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('marks.view') || authStore.hasPermission('marks.create') || authStore.hasPermission('marks.edit')" 
                            to="/exams/marks" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/exams/marks') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Marks Entry
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('report_card.view')" 
                            to="/exams/results" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/exams/results') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            Result Engine
                        </router-link>
                    </div>
                </div>

                <!-- Group 6: Fees -->
                <div v-if="showFeesGroup" class="space-y-1">
                    <button 
                        @click="toggleSection('fees')"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-widest transition-colors cursor-pointer"
                    >
                        <span>Fees</span>
                        <svg 
                            :class="['w-3 h-3 transition-transform duration-200 text-slate-400', expandedSections.fees ? 'rotate-90' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <div v-show="expandedSections.fees" class="space-y-1 pl-1 border-l border-slate-150 dark:border-slate-800/80 ml-2">
                        <router-link 
                            v-if="authStore.hasPermission('fee_type.view')" 
                            to="/fees/fee-types" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/fee-types') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Fee Types
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('fee_structure.view')" 
                            to="/fees/fee-structures" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/fee-structures') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547"></path></svg>
                            Fee Structures
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('fee_structure.view')" 
                            to="/fees/installments" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/installments') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Installments
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('fee_structure.view')" 
                            to="/fees/assignments" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/assignments') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Student Fee Assignment
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('fee_collection.view')" 
                            to="/fees/discounts" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/discounts') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4"></path></svg>
                            Discounts
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('fee_collection.view')" 
                            to="/fees/fine-rules" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/fine-rules') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Fine Rules
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('fee_collection.view')" 
                            to="/fees/collection" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/collection') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Fee Collection
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('receipt.view')" 
                            to="/fees/receipts" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/receipts') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Receipts
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('ledger.view')" 
                            to="/fees/ledger" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/ledger') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m14 0h-3a2 2 0 00-2 2v3m2 4H9m6 0a3 3 0 11-6 0v-1m6 0H9m11-4V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2z"></path></svg>
                            Student Ledger
                        </router-link>

                        <router-link 
                            v-if="authStore.hasPermission('report.view')" 
                            to="/fees/reports" 
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="[isRouteActive('/fees/reports') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/10' : 'text-slate-655 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200']"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Reports
                        </router-link>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer User Profile -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80">                <div class="flex items-center justify-between">
                    <router-link to="/profile" class="flex-1 min-w-0 group mr-2" title="View Profile">
                        <h4 class="text-sm font-medium text-slate-700 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ authStore.user?.name }}</h4>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 mt-1 uppercase">
                            {{ authStore.roles[0] || 'User' }}
                        </span>
                    </router-link>
                    <div class="flex items-center gap-1">
                        <router-link 
                            to="/profile" 
                            class="p-2 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-colors" 
                            title="Settings & Profile"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </router-link>
                        <button 
                            @click="handleLogout" 
                            class="p-2 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-colors" 
                            title="Logout"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Backdrop for mobile menu -->
        <div v-if="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-20 md:hidden"></div>

        <!-- Main Body -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Impersonation Banner -->
            <div v-if="authStore.impersonating" class="bg-amber-600 text-white px-6 py-2.5 flex items-center justify-between text-sm font-semibold tracking-wide shadow-md">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Impersonation Mode: Active as School Administrator for <strong>{{ authStore.user?.school?.name }}</strong></span>
                </div>
                <button 
                    @click="handleExitImpersonation" 
                    :disabled="exiting"
                    class="bg-white text-amber-900 px-3 py-1 rounded-md text-xs font-bold hover:bg-amber-50 active:scale-95 transition-all flex items-center gap-1 shadow-sm disabled:opacity-50"
                >
                    <span v-if="exiting">Exiting...</span>
                    <span v-else>Exit Impersonation</span>
                </button>
            </div>

            <!-- Top Header -->
            <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md px-6 flex items-center justify-between z-10">
                <div class="flex items-center gap-4">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 md:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">{{ pageTitle }}</h2>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Web Academic Year Selection Dropdown -->
                    <div v-if="authStore.user?.school_id && authStore.hasPermission('academic_year.switch')" class="flex items-center gap-2 mr-2">
                        <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Session:</label>
                        <select 
                            :value="authStore.user?.selected_academic_year_id || ''" 
                            @change="changeWebAcademicYear"
                            class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all cursor-pointer"
                        >
                            <option value="">Default (Active{{ globalActiveYearTitle ? ': ' + globalActiveYearTitle : '' }})</option>
                            <option v-for="year in activeAcademicYears" :key="year.id" :value="year.id">
                                {{ year.title }}
                            </option>
                        </select>
                    </div>
                    <!-- Read-only Session Badge (for users without switch permission) -->
                    <div v-else-if="authStore.user?.school_id && currentActiveYearTitle" class="flex items-center gap-1.5 mr-2">
                        <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                            Session: {{ currentActiveYearTitle }}
                        </span>
                    </div>
                    <!-- Light / Dark Mode Toggle -->
                    <button 
                        @click="toggleTheme" 
                        class="p-2 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-amber-400 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Toggle Light/Dark Theme"
                    >
                        <!-- Sun Icon (if dark mode active) -->
                        <svg v-if="isDark" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path>
                        </svg>
                        <!-- Moon Icon (if light mode active) -->
                        <svg v-else class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>

                    <!-- System Status Indicator -->
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-ping"></span>
                        Live ERP Engine
                    </span>
                </div>
            </header>

            <!-- Router View / Page Content -->
            <main class="flex-1 overflow-y-auto p-6 md:p-8 text-slate-800 dark:text-slate-100">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useConfirmStore } from '../stores/confirm';
import { useToastStore } from '../stores/toast';
import { useRoute, useRouter } from 'vue-router';

export default {
    name: 'AppLayout',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const route = useRoute();
        const router = useRouter();
        
        const mobileMenuOpen = ref(false);
        const exiting = ref(false);
        const isDark = ref(false);

        const expandedSections = ref({
            admin: false,
            academics: false,
            students: false,
            operations: false,
            exams: false,
            fees: false
        });

        const showAdminGroup = computed(() => {
            return authStore.hasPermission('dashboard.view') || 
                   authStore.hasPermission('school.view') || 
                   authStore.isSuperAdmin || 
                   authStore.hasPermission('user.view') || 
                   authStore.hasPermission('role.view');
        });

        const showAcademicsGroup = computed(() => {
            return authStore.user?.school_id && (
                (authStore.hasModule('academics') && (
                    authStore.hasPermission('academic_year.view') || 
                    authStore.hasPermission('class.view') || 
                    authStore.hasPermission('section.view')
                )) ||
                (authStore.hasModule('subjects') && authStore.hasPermission('subject.view'))
            );
        });

        const showStudentsGroup = computed(() => {
            return authStore.user?.school_id && authStore.hasModule('students') && (
                authStore.hasPermission('student.view') ||
                authStore.hasPermission('student.create') ||
                authStore.hasPermission('promotion.create')
            );
        });

        const showOperationsGroup = computed(() => {
            return authStore.user?.school_id && (
                (authStore.hasModule('attendance') && authStore.hasPermission('attendance.view')) ||
                (authStore.hasModule('homework') && authStore.hasPermission('homework.view')) ||
                (authStore.hasModule('notices') && authStore.hasPermission('notice.view'))
            );
        });

        const showExamsGroup = computed(() => {
            return authStore.user?.school_id && authStore.hasModule('examinations') && (
                authStore.hasPermission('exam.view') ||
                authStore.hasPermission('exam_schedule.view') ||
                authStore.hasPermission('exam_schedule.create') ||
                authStore.hasPermission('exam_schedule.edit') ||
                authStore.hasPermission('marks.view') ||
                authStore.hasPermission('marks.create') ||
                authStore.hasPermission('marks.edit') ||
                authStore.hasPermission('report_card.view')
            );
        });

        const showFeesGroup = computed(() => {
            return authStore.user?.school_id && authStore.hasModule('fees') && (
                authStore.hasPermission('fee_type.view') ||
                authStore.hasPermission('fee_structure.view') ||
                authStore.hasPermission('fee_collection.view') ||
                authStore.hasPermission('receipt.view') ||
                authStore.hasPermission('ledger.view') ||
                authStore.hasPermission('report.view')
            );
        });

        const autoExpandActiveSection = () => {
            const path = route.path;
            Object.keys(expandedSections.value).forEach(key => {
                expandedSections.value[key] = false;
            });
            if (path === '/dashboard' || path === '/schools' || path === '/password-resets' || path === '/users' || path === '/roles') {
                expandedSections.value.admin = true;
            }
            if (path === '/academic-years' || path === '/classes' || path === '/sections' || path === '/subjects') {
                expandedSections.value.academics = true;
            }
            if (path.startsWith('/students')) {
                expandedSections.value.students = true;
            }
            if (path === '/attendance' || path === '/homeworks' || path === '/notices') {
                expandedSections.value.operations = true;
            }
            if (path.startsWith('/exams')) {
                expandedSections.value.exams = true;
            }
            if (path.startsWith('/fees')) {
                expandedSections.value.fees = true;
            }
        };

        watch(() => route.path, () => {
            autoExpandActiveSection();
        });

        const pageTitle = computed(() => {
            const name = route.name?.toString() || '';
            if (name.includes('dashboard')) return 'Dashboard Overview';
            if (name.includes('schools')) return 'School Directories';
            if (name.includes('users')) return 'User Directories';
            if (name.includes('roles')) return 'Roles & System Permissions';
            return 'ERP Panel';
        });

        onMounted(() => {
            // Check current theme status
            isDark.value = document.documentElement.classList.contains('dark');
            autoExpandActiveSection();
        });

        const toggleTheme = () => {
            if (isDark.value) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                isDark.value = false;
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                isDark.value = true;
            }
        };

        const isRouteActive = (path) => {
            return route.path.startsWith(path);
        };

        const handleLogout = async () => {
            const confirmed = await confirmStore.show({
                title: 'Confirm Logout',
                message: 'Are you sure you want to log out of the system?',
                type: 'info',
                confirmText: 'Logout',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                await authStore.logout();
                router.push({ name: 'login' });
            }
        };

        const handleExitImpersonation = async () => {
            exiting.value = true;
            try {
                const response = await window.axios.post('/api/impersonate/exit');
                authStore.user = response.data.user;
                authStore.impersonating = response.data.impersonating;
                toastStore.success(response.data.message);
                router.push({ name: 'schools.index' });
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to exit impersonation.');
            } finally {
                exiting.value = false;
            }
        };

        const toggleSection = (sectionKey) => {
            const currentVal = expandedSections.value[sectionKey];
            Object.keys(expandedSections.value).forEach(key => {
                expandedSections.value[key] = false;
            });
            expandedSections.value[sectionKey] = !currentVal;
        };

        const activeAcademicYears = ref([]);
        const fetchActiveAcademicYears = async () => {
            if (!authStore.user?.school_id) return;
            try {
                const response = await window.axios.get('/api/academic-years', {
                    params: { status: 'active' }
                });
                activeAcademicYears.value = response.data.academic_years;
            } catch (error) {
                console.error('Failed to load academic years', error);
            }
        };

        const changeWebAcademicYear = async (event) => {
            const yearId = event.target.value ? parseInt(event.target.value) : null;
            try {
                await window.axios.post('/api/auth/academic-year', {
                    academic_year_id: yearId
                });
                toastStore.success('Academic session switched successfully.');
                window.location.reload();
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to switch academic session.');
            }
        };

        const globalActiveYearTitle = computed(() => {
            const active = activeAcademicYears.value.find(year => year.is_current);
            return active ? active.title : '';
        });

        const currentActiveYearTitle = computed(() => {
            const selectedId = authStore.user?.selected_academic_year_id;
            if (selectedId) {
                const selected = activeAcademicYears.value.find(year => year.id === selectedId);
                if (selected) return selected.title;
            }
            return globalActiveYearTitle.value;
        });

        watch(() => authStore.user, (newUser) => {
            if (newUser?.school_id && activeAcademicYears.value.length === 0) {
                fetchActiveAcademicYears();
            }
        }, { immediate: true });

        return {
            authStore,
            globalActiveYearTitle,
            currentActiveYearTitle,
            mobileMenuOpen,
            exiting,
            isDark,
            pageTitle,
            toggleTheme,
            isRouteActive,
            handleLogout,
            handleExitImpersonation,
            route,
            expandedSections,
            showAdminGroup,
            showAcademicsGroup,
            showStudentsGroup,
            showOperationsGroup,
            showExamsGroup,
            showFeesGroup,
            toggleSection,
            activeAcademicYears,
            changeWebAcademicYear
        };
    }
}
</script>
