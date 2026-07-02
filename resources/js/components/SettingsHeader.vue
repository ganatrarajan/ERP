<template>
    <div class="space-y-4">
        <!-- Unified Settings Portal Header -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </span>
                    Settings Control Center
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configure school profiles, academic sessions, classes, subjects, and weekly schedules.</p>
            </div>

            <!-- Settings Instant Search -->
            <div class="relative w-full md:w-80 shrink-0">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input 
                    v-model="searchQuery"
                    @focus="showResults = true"
                    @blur="hideResultsDelayed"
                    type="text"
                    placeholder="Quick Search Settings... (e.g. Logo, Class)"
                    class="w-full pl-10 pr-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200 transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500"
                />

                <!-- Search Results Dropdown -->
                <transition name="fade">
                    <div v-if="showResults && filteredActions.length > 0" class="absolute left-0 right-0 mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                        <div 
                            v-for="action in filteredActions" 
                            :key="action.name"
                            @click="triggerAction(action)"
                            class="px-4 py-3 hover:bg-indigo-50/40 dark:hover:bg-slate-800/50 cursor-pointer flex items-center justify-between group transition-colors"
                        >
                            <div>
                                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ action.name }}
                                </h4>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ action.description }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[8px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200/40 dark:border-slate-700/60 uppercase">
                                {{ action.tabLabel }}
                            </span>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1.5 scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-800">
            <router-link 
                to="/profile"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/profile' && !($route.query.tab === 'school' || $route.query.tab === 'weekend')
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                My Account
            </router-link>

            <router-link 
                v-if="showSchoolTab"
                to="/profile?tab=school"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/profile' && $route.query.tab === 'school'
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                School Settings
            </router-link>

            <router-link 
                v-if="authStore.hasPermission('academic_year.view')"
                to="/academic-years"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/academic-years'
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Academic Years
            </router-link>

            <router-link 
                v-if="authStore.hasPermission('class.view')"
                to="/classes"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/classes'
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Classes
            </router-link>

            <router-link 
                v-if="authStore.hasPermission('section.view')"
                to="/sections"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/sections'
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                Sections
            </router-link>

            <router-link 
                v-if="authStore.hasPermission('subject.view')"
                to="/subjects"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/subjects'
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Subjects
            </router-link>

            <router-link 
                v-if="showWeekendTab"
                to="/profile?tab=weekend"
                class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 border"
                :class="[
                    $route.path === '/profile' && $route.query.tab === 'weekend'
                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-600/10'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Weekend Holidays
            </router-link>
        </div>
    </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

export default {
    name: 'SettingsHeader',
    setup() {
        const authStore = useAuthStore();
        const router = useRouter();
        const searchQuery = ref('');
        const showResults = ref(false);

        const actions = [
            { name: 'Change Password', description: 'Update your account login password', path: '/profile', tabLabel: 'My Account' },
            { name: 'Update Contact Info', description: 'Update profile email and phone number', path: '/profile', tabLabel: 'My Account' },
            { name: 'Change School Logo', description: 'Upload school logo for report cards and invoices', path: '/profile', query: { tab: 'school' }, tabLabel: 'School Settings' },
            { name: 'School Profile Details', description: 'Edit name, email, phone, and address', path: '/profile', query: { tab: 'school' }, tabLabel: 'School Settings' },
            { name: 'Create Academic Year', description: 'Create a new school session/academic year', path: '/academic-years', action: 'create', tabLabel: 'Academic Years' },
            { name: 'Manage Sessions', description: 'List active sessions and select current active', path: '/academic-years', tabLabel: 'Academic Years' },
            { name: 'Create Class', description: 'Define class names and assign sessions', path: '/classes', action: 'create', tabLabel: 'Classes' },
            { name: 'Manage Classes', description: 'List classes and filter class sessions', path: '/classes', tabLabel: 'Classes' },
            { name: 'Create Section', description: 'Create section splits (A, B, C)', path: '/sections', action: 'create', tabLabel: 'Sections' },
            { name: 'Manage Sections', description: 'Associate sections to classes', path: '/sections', tabLabel: 'Sections' },
            { name: 'Create Subject', description: 'Create new core or elective subjects', path: '/subjects', action: 'create', tabLabel: 'Subjects' },
            { name: 'Manage Subjects', description: 'Assign subjects to classrooms', path: '/subjects', tabLabel: 'Subjects' },
            { name: 'Weekend Holiday Configuration', description: 'Define non-working Saturdays and off-days', path: '/profile', query: { tab: 'weekend' }, tabLabel: 'Weekend Holidays' },
        ];

        const showSchoolTab = computed(() => {
            return !authStore.isSuperAdmin && authStore.user?.school_id && authStore.hasPermission('settings.edit');
        });

        const showWeekendTab = computed(() => {
            return !authStore.isSuperAdmin && authStore.user?.school_id && authStore.hasPermission('settings.edit');
        });

        const filteredActions = computed(() => {
            if (!searchQuery.value) return [];
            const query = searchQuery.value.toLowerCase();
            return actions.filter(action => {
                if (action.path === '/profile' && action.query?.tab === 'school' && !showSchoolTab.value) return false;
                if (action.path === '/profile' && action.query?.tab === 'weekend' && !showWeekendTab.value) return false;
                return action.name.toLowerCase().includes(query) || action.description.toLowerCase().includes(query);
            });
        });

        const hideResultsDelayed = () => {
            setTimeout(() => {
                showResults.value = false;
            }, 250);
        };

        const triggerAction = (action) => {
            searchQuery.value = '';
            showResults.value = false;
            router.push({ path: action.path, query: action.query || {} }).then(() => {
                if (action.action === 'create') {
                    // Triggers the modal open globally or via window events
                    setTimeout(() => {
                        window.dispatchEvent(new CustomEvent('open-settings-create-modal'));
                    }, 100);
                }
            });
        };

        return {
            authStore,
            searchQuery,
            showResults,
            showSchoolTab,
            showWeekendTab,
            filteredActions,
            hideResultsDelayed,
            triggerAction
        };
    }
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
