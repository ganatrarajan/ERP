<template>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">Staff & User Directory</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage school administrators, teachers, support staff, accountants, and other system users.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <router-link 
                    to="/users/reports"
                    class="px-4 py-2.5 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-850 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 border border-slate-200 dark:border-slate-800"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Report Builder
                </router-link>

                <router-link 
                    v-if="authStore.hasPermission('user.create')"
                    to="/users/create"
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Staff Member
                </router-link>
            </div>
        </div>

        <!-- Advanced Filter & Search Box -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm space-y-4">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <!-- Search -->
                <div class="relative w-full md:max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="debounceFetch"
                        type="text" 
                        placeholder="Search by name, email, employee ID, teacher code, phone..." 
                        class="w-full pl-9 pr-4 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder-slate-400 text-slate-700 dark:text-slate-200"
                    />
                    <button 
                        v-if="filters.search"
                        @click="filters.search = ''; fetchUsers();"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-655"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Action Toggles -->
                <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                    <button 
                        @click="toggleAdvancedFilters = !toggleAdvancedFilters"
                        class="px-3 py-2 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-850 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Advanced Filters
                    </button>
                    
                    <button 
                        v-if="hasActiveFilters"
                        @click="resetFilters"
                        class="px-3 py-2 bg-rose-50 dark:bg-rose-950/20 hover:bg-rose-100 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-xl transition-all border border-rose-100 dark:border-rose-900/50 cursor-pointer"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Advanced Filters Expanded Grid -->
            <div v-if="toggleAdvancedFilters" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-3 border-t border-slate-100 dark:border-slate-850 animate-[fadeIn_0.2s_ease-out]">
                <!-- Role -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Filter Role</label>
                    <select v-model="filters.role" @change="fetchUsers" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                        <option value="">All Roles</option>
                        <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Status</label>
                    <select v-model="filters.status" @change="fetchUsers" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Department</label>
                    <input v-model="filters.department" @input="debounceFetch" type="text" placeholder="e.g. Science" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200" />
                </div>

                <!-- Designation -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Designation</label>
                    <input v-model="filters.designation" @input="debounceFetch" type="text" placeholder="e.g. HOD" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200" />
                </div>

                <!-- Class Assignment -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Class Assigned</label>
                    <select v-model="filters.class_id" @change="handleClassChange" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                        <option value="">All Classes</option>
                        <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <!-- Section Assignment -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Section Assigned</label>
                    <select v-model="filters.section_id" @change="handleSectionChange" :disabled="!filters.class_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                        <option value="">All Sections</option>
                        <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>

                <!-- Subject Assignment -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Subject Assigned</label>
                    <select v-model="filters.subject_id" @change="fetchUsers" :disabled="!filters.class_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                        <option value="">All Subjects</option>
                        <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                    </select>
                </div>

                <!-- Class Teacher Status -->
                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                        <input type="checkbox" v-model="filters.is_class_teacher" @change="fetchUsers" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                        Is Class Teacher
                    </label>
                </div>
            </div>
        </div>

        <!-- Bulk Action Bar -->
        <div v-if="selectedUserIds.length > 0" class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-150 dark:border-indigo-900/50 p-3 rounded-xl flex items-center justify-between animate-[fadeIn_0.15s_ease-out]">
            <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400">Selected {{ selectedUserIds.length }} Staff Members</span>
            <div class="flex gap-2">
                <button 
                    @click="bulkDelete" 
                    class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-[10px] rounded-lg transition-all"
                >
                    Bulk Delete
                </button>
                <button 
                    @click="selectedUserIds = []" 
                    class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px] rounded-lg transition-all"
                >
                    Cancel
                </button>
            </div>
        </div>

        <!-- Main Directory Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-sm">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-100 dark:bg-slate-800/40 rounded-xl"></div>
            </div>

            <div v-else-if="users.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <p class="font-bold text-slate-700 dark:text-slate-300 mb-1">No Staff Profiles Found</p>
                <p class="text-xs text-slate-400">Try modifying your search query or role filters.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6 w-8">
                                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5" />
                            </th>
                            <th class="p-4">Name / Contact</th>
                            <th class="p-4">Employee ID / Code</th>
                            <th class="p-4">Mobile</th>
                            <th class="p-4">Role Designation</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Last Login</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/20 transition-colors">
                            <!-- Checkbox -->
                            <td class="p-4 pl-6">
                                <input type="checkbox" :value="user.id" v-model="selectedUserIds" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5" />
                            </td>

                            <!-- Name / Contact -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <!-- Photo Avatar -->
                                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 flex items-center justify-center shrink-0">
                                        <img v-if="user.profile_photo" :src="'/' + user.profile_photo" class="w-full h-full object-cover" />
                                        <span v-else class="text-xs font-bold text-slate-500 uppercase">{{ user.name.substring(0, 2) }}</span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 dark:text-white flex items-center gap-1.5 flex-wrap">
                                            {{ user.name }}
                                            <span v-if="user.id === authStore.user?.id" class="px-1.5 py-0.5 rounded text-[8px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-bold uppercase">You</span>
                                            <span v-if="user.documents_count > 0" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[8px] bg-indigo-50 dark:bg-indigo-500/10 text-indigo-650 dark:text-indigo-400 border border-indigo-150 dark:border-indigo-500/20 font-extrabold uppercase" title="Uploaded Documents">
                                                📄 {{ user.documents_count }}
                                            </span>
                                        </h4>
                                        <p class="text-xs text-slate-400 font-semibold">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- IDs -->
                            <td class="p-4 font-semibold text-slate-700 dark:text-slate-200">
                                <div>ID: <span class="text-xs font-semibold text-slate-550">{{ user.employee_id || 'N/A' }}</span></div>
                                <div v-if="user.teacher_code" class="text-[11px] text-slate-400">TCH: <span class="font-bold text-indigo-500">{{ user.teacher_code }}</span></div>
                            </td>

                            <!-- Phone -->
                            <td class="p-4 text-slate-600 dark:text-slate-350 font-semibold">
                                {{ user.mobile || 'N/A' }}
                            </td>

                            <!-- Roles -->
                            <td class="p-4">
                                <span 
                                    v-for="role in user.roles" 
                                    :key="role.id"
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold mr-1 uppercase tracking-wide border',
                                        role.name === 'Super Admin' ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-500/20' : '',
                                        role.name === 'School Admin' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/20' : '',
                                        role.name === 'Teacher' ? 'bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-200 dark:border-sky-500/20' : '',
                                        !['Super Admin', 'School Admin', 'Teacher'].includes(role.name) ? 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' : ''
                                    ]"
                                >
                                    {{ role.name }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border capitalize',
                                    user.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-455 border-rose-500/20'
                                ]">
                                    {{ user.status }}
                                </span>
                            </td>

                            <!-- Last Active -->
                            <td class="p-4 text-xs text-slate-550 dark:text-slate-400 font-medium">
                                {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}
                            </td>

                            <!-- Action Buttons -->
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View Profile -->
                                    <router-link 
                                        :to="`/users/${user.id}`"
                                        class="p-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-800 transition-colors"
                                        title="View Profile Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </router-link>

                                    <!-- Direct Documents Tab -->
                                    <router-link 
                                        :to="`/users/${user.id}?tab=documents`"
                                        class="p-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-800 transition-colors"
                                        title="Direct Documents Directory"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </router-link>

                                    <!-- Direct Assignments Tab (Only for Teachers) -->
                                    <router-link 
                                        v-if="user.roles?.some(r => r.name === 'Teacher')"
                                        :to="`/users/${user.id}?tab=teacher`"
                                        class="p-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-800 transition-colors"
                                        title="Direct Academic Assignments"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                    </router-link>

                                    <!-- Edit -->
                                    <router-link 
                                        v-if="authStore.hasPermission('user.edit')"
                                        :to="`/users/${user.id}/edit`"
                                        class="p-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-750 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-800 transition-colors"
                                        title="Modify Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </router-link>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('user.delete') && user.id !== authStore.user?.id"
                                        @click="handleDelete(user)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-550/20 text-rose-600 dark:text-rose-450 border border-rose-500/20 rounded-lg transition-colors cursor-pointer"
                                        title="Remove Account"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
    name: 'UsersIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const route = useRoute();

        const users = ref([]);
        const loading = ref(true);
        const roles = ref([]);
        
        // Advanced filters options
        const classes = ref([]);
        const sections = ref([]);
        const subjects = ref([]);
        const toggleAdvancedFilters = ref(false);

        // Filter parameters
        const filters = ref({
            search: '',
            role: '',
            status: '',
            department: '',
            designation: '',
            class_id: '',
            section_id: '',
            subject_id: '',
            is_class_teacher: false,
        });

        // Bulk operations
        const selectedUserIds = ref([]);

        let debounceTimeout = null;

        const hasActiveFilters = computed(() => {
            return Object.values(filters.value).some(v => v !== '' && v !== false);
        });

        const isAllSelected = computed(() => {
            return users.value.length > 0 && selectedUserIds.value.length === users.value.length;
        });

        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                selectedUserIds.value = [];
            } else {
                selectedUserIds.value = users.value.map(u => u.id);
            }
        };

        const resetFilters = () => {
            filters.value = {
                search: '',
                role: '',
                status: '',
                department: '',
                designation: '',
                class_id: '',
                section_id: '',
                subject_id: '',
                is_class_teacher: false,
            };
            sections.value = [];
            subjects.value = [];
            fetchUsers();
        };

        const debounceFetch = () => {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => {
                fetchUsers();
            }, 300);
        };

        const fetchUsers = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/users', { params: filters.value });
                users.value = response.data.users;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to retrieve user directories.');
            } finally {
                loading.value = false;
            }
        };

        // Load dependants when Class filter changes
        const handleClassChange = async () => {
            filters.value.section_id = '';
            filters.value.subject_id = '';
            sections.value = [];
            subjects.value = [];
            
            if (filters.value.class_id) {
                try {
                    const sectionsRes = await window.axios.get('/api/sections', {
                        params: { class_id: filters.value.class_id }
                    });
                    sections.value = sectionsRes.data.sections;
                    
                    const subjectsRes = await window.axios.get('/api/subjects', {
                        params: { class_id: filters.value.class_id }
                    });
                    subjects.value = subjectsRes.data.subjects;
                } catch (err) {
                    console.error('Failed loading academic filters', err);
                }
            }
            fetchUsers();
        };

        const handleSectionChange = async () => {
            filters.value.subject_id = '';
            subjects.value = [];
            
            if (filters.value.class_id && filters.value.section_id) {
                try {
                    const subjectsRes = await window.axios.get('/api/subjects', {
                        params: { 
                            class_id: filters.value.class_id, 
                            section_id: filters.value.section_id 
                        }
                    });
                    subjects.value = subjectsRes.data.subjects;
                } catch (err) {
                    console.error(err);
                }
            }
            fetchUsers();
        };

        const handleDelete = async (user) => {
            const confirmed = await confirmStore.show({
                title: 'Delete User Account',
                message: `Are you sure you want to delete the user account: "${user.name}"? This action cannot be undone.`,
                type: 'danger',
                confirmText: 'Delete Account',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    await window.axios.delete(`/api/users/${user.id}`);
                    toastStore.success('User account deleted successfully.');
                    fetchUsers();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete user.');
                }
            }
        };

        const bulkDelete = async () => {
            const confirmed = await confirmStore.show({
                title: 'Bulk Delete Accounts',
                message: `Are you sure you want to delete the selected ${selectedUserIds.value.length} user accounts? This cannot be undone.`,
                type: 'danger',
                confirmText: 'Bulk Delete',
                cancelText: 'Cancel'
            });
            if (confirmed) {
                try {
                    // Perform sequential deletions to respect boundaries
                    for (const id of selectedUserIds.value) {
                        await window.axios.delete(`/api/users/${id}`);
                    }
                    toastStore.success('Selected user accounts deleted successfully.');
                    selectedUserIds.value = [];
                    fetchUsers();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed during bulk delete.');
                }
            }
        };

        onMounted(async () => {
            if (route.query.role) {
                filters.value.role = route.query.role;
            }
            fetchUsers();

            // Load filter options
            try {
                const rolesRes = await window.axios.get('/api/roles');
                roles.value = rolesRes.data.roles;

                const classesRes = await window.axios.get('/api/classes');
                classes.value = classesRes.data.classes;
            } catch (err) {
                console.error(err);
            }
        });

        return {
            authStore,
            users,
            roles,
            classes,
            sections,
            subjects,
            toggleAdvancedFilters,
            filters,
            hasActiveFilters,
            debounceFetch,
            fetchUsers,
            resetFilters,
            handleClassChange,
            handleSectionChange,
            handleDelete,
            selectedUserIds,
            isAllSelected,
            toggleSelectAll,
            bulkDelete,
            loading
        };
    }
}
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
