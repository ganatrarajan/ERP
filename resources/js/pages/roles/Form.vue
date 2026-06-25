<template>
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <router-link to="/roles" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </router-link>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">{{ isEdit ? 'Update Role Settings' : 'Create Custom Role' }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Specify system designation name and map functional permissions.</p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Role Info Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl space-y-5 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800/80 pb-2">Role Profile</h3>
                
                <!-- Errors -->
                <div v-if="Object.keys(errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-semibold space-y-1">
                    <p class="font-bold flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> Please correct validation errors:</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <li v-for="(err, field) in errors" :key="field">{{ err[0] }}</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Role Name</label>
                    <input 
                        type="text" 
                        v-model="form.name" 
                        required
                        :disabled="isSystemRoleName"
                        placeholder="e.g. Department Head"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-1 focus:ring-indigo-500 transition-all disabled:opacity-50"
                    />
                    <p v-if="isSystemRoleName" class="text-xs text-amber-600 dark:text-amber-500 mt-2 font-semibold">System-default roles are partially protected and cannot change names.</p>
                </div>
            </div>

            <!-- Permissions Mapping Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl space-y-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-2">
                    <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Functional Permissions Map</h3>
                    <button 
                        type="button" 
                        @click="toggleAllPermissions"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-bold"
                    >
                        {{ allSelected ? 'Clear All' : 'Select All' }}
                    </button>
                </div>

                <div class="max-h-[55vh] overflow-y-auto pr-3 space-y-2 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 bg-slate-50/20 dark:bg-slate-950/20">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Categorized Permissions -->
                        <div 
                            v-for="(group, groupName) in permissionGroups" 
                            :key="groupName"
                            class="p-4 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-850 rounded-xl space-y-3 shadow-sm"
                        >
                            <h4 class="text-xs font-extrabold text-slate-700 dark:text-slate-200 tracking-wider uppercase flex items-center justify-between">
                                {{ groupName }}
                                <button 
                                    type="button" 
                                    @click="toggleGroup(group)"
                                    class="text-[10px] text-slate-400 dark:text-slate-500 hover:text-slate-605 dark:hover:text-slate-350 font-bold"
                                >
                                    toggle group
                                </button>
                            </h4>
                            <div class="space-y-2">
                                <label 
                                    v-for="perm in group" 
                                    :key="perm" 
                                    class="flex items-center gap-2.5 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 cursor-pointer select-none"
                                >
                                    <input 
                                        type="checkbox" 
                                        :value="perm" 
                                        v-model="form.permissions"
                                        class="h-4 w-4 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-indigo-600 focus:ring-indigo-500/25 focus:ring-offset-slate-900"
                                    />
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ getPermissionLabel(perm) }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end gap-3">
                <router-link 
                    to="/roles" 
                    class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-bold rounded-xl transition-all"
                >
                    Cancel
                </router-link>
                <button 
                    type="submit" 
                    :disabled="saving"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/15 transition-all disabled:opacity-50"
                >
                    <span v-if="saving">Saving...</span>
                    <span v-else>{{ isEdit ? 'Update Details' : 'Generate Role' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { useRouter, useRoute } from 'vue-router';

export default {
    name: 'RoleForm',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const router = useRouter();
        const route = useRoute();

        const isEdit = ref(false);
        const saving = ref(false);
        const errors = ref({});

        const form = ref({
            name: '',
            permissions: []
        });

        const allPermissions = ref([]);
        const permissionGroups = ref({});

        const groupNameMapping = {
            'school': 'School Management',
            'user': 'User Management',
            'role': 'Role Management',
            'permission': 'Permission Management',
            'dashboard': 'Dashboard View',
            'settings': 'System Settings',
            'academic_year': 'Academic Year Management',
            'class': 'Class Management',
            'section': 'Section Management',
            'student': 'Student Management',
            'promotion': 'Promotion Management',
            'teacher': 'Teacher Management',
            'attendance': 'Attendance Management',
            'homework': 'Homework Management',
            'fees': 'Fees Management',
            'exams': 'Exams Management',
            'library': 'Library Management',
            'transport': 'Transport Management',
            'hostel': 'Hostel Management',
            'reports': 'Reports View',
            'exam': 'Exams, Grade Scales & Exam Types',
            'exam_schedule': 'Exam Schedules',
            'marks': 'Marks Entry',
            'result': 'Result Engine & Report Cards',
            'report_card': 'Result Engine & Report Cards'
        };

        const getGroupName = (permissionName) => {
            const prefix = permissionName.split('.')[0];
            return groupNameMapping[prefix] || `${prefix.charAt(0).toUpperCase() + prefix.slice(1)} Management`;
        };

        const flatPermissions = computed(() => {
            return allPermissions.value;
        });

        const allSelected = computed(() => {
            return form.value.permissions.length === flatPermissions.value.length && flatPermissions.value.length > 0;
        });

        const isSystemRoleName = computed(() => {
            return ['Super Admin', 'School Admin', 'Teacher'].includes(form.value.name);
        });

        onMounted(async () => {
            // If creating a role, only Super Admin is allowed
            if (!route.params.id && !authStore.isSuperAdmin) {
                router.push({ name: 'forbidden' });
                return;
            }

            // If editing, check if they have role.edit permission
            if (route.params.id && !authStore.hasPermission('role.edit')) {
                router.push({ name: 'forbidden' });
                return;
            }

            // Fetch all permissions dynamically from database
            try {
                const permResponse = await window.axios.get('/api/permissions');
                const perms = permResponse.data.permissions.map(p => p.name);
                allPermissions.value = perms;

                const groups = {};
                perms.forEach(perm => {
                    const groupName = getGroupName(perm);
                    if (!groups[groupName]) {
                        groups[groupName] = [];
                    }
                    groups[groupName].push(perm);
                });
                permissionGroups.value = groups;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load system permissions.');
            }

            if (route.params.id) {
                isEdit.value = true;
                const roleId = route.params.id;
                try {
                    const response = await window.axios.get(`/api/roles/${roleId}`);
                    const role = response.data.role;
                    
                    // Non-Super Admin cannot edit Super Admin role
                    if (role.name === 'Super Admin' && !authStore.isSuperAdmin) {
                        router.push({ name: 'forbidden' });
                        return;
                    }

                    form.value.name = role.name;
                    form.value.permissions = role.permissions.map(p => p.name);
                } catch (error) {
                    console.error(error);
                    toastStore.error('Failed to load role details.');
                    router.push({ name: 'roles.index' });
                }
            }
        });

        const getPermissionLabel = (perm) => {
            const labels = {
                'school.view': 'View School Profile',
                'school.edit': 'Update School Profile & Modules',
                'user.view': 'View Users & Profiles',
                'user.create': 'Register New Users',
                'user.edit': 'Modify User Details',
                'user.delete': 'Disable/Delete Users',
                'role.view': 'View Roles List',
                'role.edit': 'Create/Edit Custom Roles',
                'permission.view': 'View Available Permissions',
                'permission.edit': 'Modify System Permissions',
                'dashboard.view': 'Access Dashboard Stats',
                'settings.view': 'View School Global Settings',
                'settings.edit': 'Modify School Global Settings',
                'academic_year.view': 'View Academic Sessions',
                'academic_year.create': 'Add New Academic Session',
                'academic_year.edit': 'Update Academic Session',
                'academic_year.delete': 'Remove Academic Session',
                'class.view': 'View Classes List',
                'class.create': 'Add New Class',
                'class.edit': 'Modify Class Name',
                'class.delete': 'Remove Class',
                'section.view': 'View Class Sections',
                'section.create': 'Create Class Section',
                'section.edit': 'Modify Class Section',
                'section.delete': 'Remove Class Section',
                'student.view': 'View Student Profiles & Directory',
                'student.create': 'Admit New Student / Bulk Import',
                'student.edit': 'Modify Student Information',
                'student.delete': 'Remove Student Record',
                'promotion.view': 'View Student Promotion History',
                'promotion.create': 'Promote Students to Next Class',
                'subject.view': 'View Academic Subjects',
                'subject.create': 'Add Subject to Class/Section',
                'subject.edit': 'Modify Subject Details',
                'subject.delete': 'Remove Subject',
                'attendance.view': 'View Attendance Reports & Logs',
                'attendance.create': 'Mark Daily Attendance',
                'attendance.edit': 'Modify Attendance Records',
                'attendance.delete': 'Clear Attendance Records',
                'homework.view': 'View Homework Tasks',
                'homework.create': 'Publish Homework & Attachments',
                'homework.edit': 'Modify Homework Details',
                'homework.delete': 'Delete Homework Task',
                'notice.view': 'View Notice Board Announcements',
                'notice.create': 'Post Notice (School/Class Wise)',
                'notice.edit': 'Edit Posted Notices',
                'notice.delete': 'Delete Announcement Notice',
                
                // Examinations Module
                'exam.view': 'View',
                'exam.create': 'Create',
                'exam.edit': 'Edit',
                'exam.delete': 'Delete',
                'exam_schedule.view': 'View',
                'exam_schedule.create': 'Create',
                'exam_schedule.edit': 'Edit',
                'exam_schedule.delete': 'Delete',
                'marks.view': 'View',
                'marks.create': 'Create',
                'marks.edit': 'Edit',
                'result.view': 'View & Process Ranks',
                'report_card.view': 'Generate & Download PDF',

                // Fees Module
                'fee_type.view': 'View Fee Types',
                'fee_type.create': 'Create Fee Types',
                'fee_type.edit': 'Edit Fee Types',
                'fee_type.delete': 'Delete Fee Types',
                'fee_structure.view': 'View Fee Structures',
                'fee_structure.create': 'Create Fee Structures',
                'fee_structure.edit': 'Edit Fee Structures',
                'fee_structure.delete': 'Delete Fee Structures',
                'fee_collection.view': 'View Collections & Waivers',
                'fee_collection.create': 'Collect Payments & Fines',
                'fee_collection.edit': 'Edit Collected Payments',
                'receipt.view': 'Search & Download Receipts',
                'ledger.view': 'View Student Ledgers',
                'report.view': 'Generate Financial Reports',
            };
            return labels[perm] ? `${perm} (${labels[perm]})` : perm;
        };

        const toggleAllPermissions = () => {
            if (allSelected.value) {
                form.value.permissions = [];
            } else {
                form.value.permissions = [...flatPermissions.value];
            }
        };

        const toggleGroup = (group) => {
            const groupSelected = group.every(p => form.value.permissions.includes(p));
            if (groupSelected) {
                // Remove group permissions
                form.value.permissions = form.value.permissions.filter(p => !group.includes(p));
            } else {
                // Add group permissions (ensuring uniqueness)
                form.value.permissions = [...new Set([...form.value.permissions, ...group])];
            }
        };

        const handleSubmit = async () => {
            saving.value = true;
            errors.value = {};
            try {
                if (isEdit.value) {
                    await window.axios.put(`/api/roles/${route.params.id}`, form.value);
                    toastStore.success('Role settings updated successfully.');
                } else {
                    await window.axios.post('/api/roles', form.value);
                    toastStore.success('Custom role generated successfully.');
                }
                router.push({ name: 'roles.index' });
            } catch (error) {
                console.error(error);
                if (error.response?.data?.errors) {
                    errors.value = error.response.data.errors;
                } else {
                    toastStore.error(error.response?.data?.message || 'Failed to save role settings.');
                }
            } finally {
                saving.value = false;
            }
        };

        return {
            authStore,
            isEdit,
            saving,
            errors,
            form,
            permissionGroups,
            allSelected,
            isSystemRoleName,
            toggleAllPermissions,
            toggleGroup,
            handleSubmit,
            getPermissionLabel
        };
    }
}
</script>
