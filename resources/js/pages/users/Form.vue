<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <router-link to="/users" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </router-link>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">{{ isEdit ? 'Modify Account Details' : 'Create User Account' }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Define user profile, access credentials, and designation role.</p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl space-y-5 shadow-sm">
            <!-- Errors -->
            <div v-if="Object.keys(errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-semibold space-y-1">
                <p class="font-bold flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> Please correct validation errors:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    <li v-for="(err, field) in errors" :key="field">{{ err[0] }}</li>
                </ul>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- User Name -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                    <input 
                        type="text" 
                        v-model="form.name" 
                        required
                        placeholder="Jane Doe"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        required
                        placeholder="jane@domain.com"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>

                <!-- Mobile -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wider mb-2 uppercase">Mobile Number</label>
                    <input 
                        type="text" 
                        v-model="form.mobile" 
                        placeholder="+1 (555) 000-0000"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>

                <!-- School selection (Super Admin only) -->
                <div v-if="authStore.isSuperAdmin">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Associated School</label>
                    <select 
                        v-model="form.school_id" 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    >
                        <option :value="null">SaaS Administration (No School)</option>
                        <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
                    </select>
                </div>

                <!-- Role Selection -->
                <div :class="{'md:col-span-2': !authStore.isSuperAdmin}">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">System Designation / Role</label>
                    <select 
                        v-model="form.role" 
                        required
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    >
                        <option value="" disabled>Choose Designation...</option>
                        <option v-for="role in filteredRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                        Password <span v-if="isEdit" class="text-[10px] text-slate-500 lowercase">(leave blank to keep current)</span>
                    </label>
                    <input 
                        type="password" 
                        v-model="form.password" 
                        :required="!isEdit"
                        placeholder="••••••••"
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Status</label>
                    <select 
                        v-model="form.status" 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <router-link 
                    to="/users" 
                    class="px-5 py-2.5 bg-slate-150 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-xs font-bold rounded-xl transition-all"
                >
                    Cancel
                </router-link>
                <button 
                    type="submit" 
                    :disabled="saving"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/15 transition-all disabled:opacity-50"
                >
                    <span v-if="saving">Saving...</span>
                    <span v-else>{{ isEdit ? 'Update details' : 'Create Account' }}</span>
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
    name: 'UserForm',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const router = useRouter();
        const route = useRoute();

        const isEdit = ref(false);
        const saving = ref(false);
        const errors = ref({});

        const schools = ref([]);
        const roles = ref([]);

        const form = ref({
            name: '',
            email: '',
            mobile: '',
            school_id: null,
            role: '',
            password: '',
            status: 'active',
        });

        // Filter roles selection based on logged in user permissions
        const filteredRoles = computed(() => {
            if (authStore.isSuperAdmin) {
                return roles.value; // Super admin can assign all roles
            }
            // School Admin can assign School Admin or Teacher
            return roles.value.filter(role => role.name !== 'Super Admin');
        });

        onMounted(async () => {
            // Load roles
            try {
                const rolesResponse = await window.axios.get('/api/roles');
                roles.value = rolesResponse.data.roles;
            } catch (err) {
                console.error('Failed to load roles', err);
            }

            // Load schools if Super Admin
            if (authStore.isSuperAdmin) {
                try {
                    const schoolsResponse = await window.axios.get('/api/schools');
                    schools.value = schoolsResponse.data.schools;
                } catch (err) {
                    console.error('Failed to load schools', err);
                }
            }

            // If Edit mode, load user details
            if (route.params.id) {
                isEdit.value = true;
                const userId = route.params.id;
                try {
                    const response = await window.axios.get(`/api/users/${userId}`);
                    const user = response.data.user;

                    // Enforce boundary scoping for School Admin
                    if (!authStore.isSuperAdmin && authStore.user.school_id !== user.school_id) {
                        router.push({ name: 'forbidden' });
                        return;
                    }

                    form.value.name = user.name;
                    form.value.email = user.email;
                    form.value.mobile = user.mobile || '';
                    form.value.school_id = user.school_id;
                    form.value.status = user.status;
                    form.value.role = user.roles?.[0]?.id || '';
                } catch (error) {
                    console.error(error);
                    toastStore.error('Failed to load user information.');
                    router.push({ name: 'users.index' });
                }
            }
        });

        const handleSubmit = async () => {
            saving.value = true;
            errors.value = {};
            try {
                if (isEdit.value) {
                    await window.axios.put(`/api/users/${route.params.id}`, form.value);
                    toastStore.success('Account updated successfully.');
                } else {
                    await window.axios.post('/api/users', form.value);
                    toastStore.success('Account created successfully.');
                }
                router.push({ name: 'users.index' });
            } catch (error) {
                console.error(error);
                if (error.response?.data?.errors) {
                    errors.value = error.response.data.errors;
                } else {
                    toastStore.error(error.response?.data?.message || 'Failed to save account details.');
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
            schools,
            filteredRoles,
            form,
            handleSubmit
        };
    }
}
</script>
