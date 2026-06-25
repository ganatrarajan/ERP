<template>
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <router-link to="/schools" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </router-link>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">{{ isEdit ? 'Update School Details' : 'Register New School' }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Specify system tenant information and credentials.</p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- School Details Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl space-y-5 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800/80 pb-2">Tenant Profile</h3>
                
                <!-- Errors -->
                <div v-if="Object.keys(errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-semibold space-y-1">
                    <p class="font-bold flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> Please correct validation errors:</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        <li v-for="(err, field) in errors" :key="field">{{ err[0] }}</li>
                    </ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- School Name -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">School Name</label>
                        <input 
                            type="text" 
                            v-model="form.name" 
                            required
                            placeholder="e.g. Oakridge International School"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">School Email</label>
                        <input 
                            type="email" 
                            v-model="form.email" 
                            required
                            placeholder="info@oakridge.edu"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Phone Number</label>
                        <input 
                            type="text" 
                            v-model="form.phone" 
                            placeholder="+1 (555) 019-2834"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Logo Upload -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">School Logo</label>
                        <div class="flex items-center gap-3">
                            <!-- Logo Preview -->
                            <div class="relative w-11 h-11 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden group shadow-sm flex-shrink-0">
                                <img 
                                    v-if="form.logo" 
                                    :src="form.logo" 
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-[9px] text-slate-400 dark:text-slate-550 font-bold uppercase">No Logo</span>
                                <button 
                                    v-if="form.logo" 
                                    type="button" 
                                    @click="clearLogo"
                                    class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white text-[10px] font-bold"
                                >
                                    Clear
                                </button>
                            </div>

                            <!-- File Select Trigger -->
                            <div class="flex-1">
                                <input 
                                    type="file" 
                                    ref="logoInput"
                                    @change="onLogoSelected"
                                    accept="image/*"
                                    class="hidden"
                                />
                                <button 
                                    type="button" 
                                    @click="$refs.logoInput.click()"
                                    :disabled="uploadingLogo"
                                    class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <svg v-if="uploadingLogo" class="animate-spin h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    {{ uploadingLogo ? 'Uploading...' : 'Upload JPG/PNG' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Status</label>
                        <select 
                            v-model="form.status" 
                            :disabled="authStore.hasRole('School Admin')"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Mobile Academic Year (School Setting) -->
                    <div v-if="isEdit">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Mobile Academic Year</label>
                        <select 
                            v-model="form.mobile_academic_year_id" 
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        >
                            <option value="">Select Mobile Academic Year</option>
                            <option v-for="year in activeAcademicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current Active)</span>
                            </option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Address</label>
                        <textarea 
                            v-model="form.address" 
                            rows="3"
                            placeholder="123 Academic Dr, Suite A, City, State"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Initial School Admin User (Only show on Create) -->
            <div v-if="!isEdit" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl space-y-5 shadow-sm">
                <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800/80 pb-2">Initial School Administrator Credentials</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Administrator Name</label>
                        <input 
                            type="text" 
                            v-model="form.admin_name" 
                            required
                            placeholder="John Doe"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Administrator Email</label>
                        <input 
                            type="email" 
                            v-model="form.admin_email" 
                            required
                            placeholder="admin@school.com"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Mobile -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Mobile Number</label>
                        <input 
                            type="text" 
                            v-model="form.admin_mobile" 
                            placeholder="+1 (555) 000-0000"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Password</label>
                        <input 
                            type="password" 
                            v-model="form.admin_password" 
                            required
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-slate-950 focus:ring-1 focus:ring-indigo-500 transition-all"
                        />
                    </div>
                </div>
            </div>

            <!-- Initial Modules Selection (Only show on Create) -->
            <div v-if="!isEdit" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl space-y-5 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800/80 pb-2">Select Active Modules</h3>
                
                <div v-if="loadingModules" class="space-y-3 animate-pulse">
                    <div v-for="i in 3" :key="i" class="h-12 bg-slate-100 dark:bg-slate-800/50 rounded-xl"></div>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label 
                        v-for="module in availableModules" 
                        :key="module.id"
                        class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer select-none transition-all duration-200"
                        :class="[
                            form.module_ids.includes(module.id)
                                ? 'bg-indigo-50/45 dark:bg-indigo-950/15 border-indigo-500/30 dark:border-indigo-500/35 text-indigo-950 dark:text-white'
                                : 'bg-slate-50/50 dark:bg-slate-950/20 border-slate-200 dark:border-slate-800/70 hover:border-slate-350 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300'
                        ]"
                    >
                        <input 
                            type="checkbox" 
                            :value="module.id" 
                            v-model="form.module_ids"
                            class="h-4.5 w-4.5 mt-0.5 rounded border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-indigo-600 focus:ring-indigo-500/25 focus:ring-offset-slate-900"
                        />
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-base">{{ getModuleEmoji(module.slug) }}</span>
                                <span class="text-sm font-bold">{{ module.name }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 leading-normal">{{ module.description }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <router-link 
                    to="/schools" 
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
                    <span v-else>{{ isEdit ? 'Update details' : 'Complete Registration' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { useRouter, useRoute } from 'vue-router';

export default {
    name: 'SchoolForm',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const router = useRouter();
        const route = useRoute();

        const isEdit = ref(false);
        const saving = ref(false);
        const errors = ref({});

        const availableModules = ref([]);
        const loadingModules = ref(false);

        const uploadingLogo = ref(false);
        const logoInput = ref(null);

        const onLogoSelected = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                toastStore.error('Logo size must be less than 2MB.');
                return;
            }

            uploadingLogo.value = true;
            const formData = new FormData();
            formData.append('logo', file);
            if (route.params.id) {
                formData.append('school_id', route.params.id);
            }

            try {
                const response = await window.axios.post('/api/schools/upload-logo', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                form.value.logo = response.data.url;
                toastStore.success('Logo uploaded successfully.');
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to upload logo.');
            } finally {
                uploadingLogo.value = false;
                if (logoInput.value) {
                    logoInput.value.value = '';
                }
            }
        };

        const clearLogo = () => {
            form.value.logo = '';
        };

        const activeAcademicYears = ref([]);
        const fetchSchoolAcademicYears = async (schoolId) => {
            try {
                const response = await window.axios.get('/api/academic-years', {
                    params: { school_id: schoolId, status: 'active' }
                });
                activeAcademicYears.value = response.data.academic_years;
            } catch (error) {
                console.error('Failed to load school academic years', error);
            }
        };

        const form = ref({
            name: '',
            email: '',
            phone: '',
            logo: '',
            address: '',
            status: 'active',
            mobile_academic_year_id: '',
            module_ids: [],
            // Admin user details (only on create)
            admin_name: '',
            admin_email: '',
            admin_mobile: '',
            admin_password: '',
        });

        const getModuleEmoji = (slug) => {
            const emojis = {
                students: '🎓',
                teachers: '👨‍🏫',
                attendance: '📅',
                homework: '📝',
                fees: '💵',
                exams: '✍️',
                library: '📚',
                transport: '🚌',
                hostel: '🏢',
                reports: '📊',
                settings: '⚙️'
            };
            return emojis[slug] || '📁';
        };

        const fetchSystemModules = async () => {
            loadingModules.value = true;
            try {
                const response = await window.axios.get('/api/modules');
                availableModules.value = response.data.modules;
                // Pre-select all modules by default
                form.value.module_ids = response.data.modules.map(m => m.id);
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load system modules.');
            } finally {
                loadingModules.value = false;
            }
        };

        onMounted(async () => {
            if (route.params.id) {
                isEdit.value = true;
                const schoolId = route.params.id;
                
                // Authorize editing: ONLY Super Admin can create/edit schools
                if (!authStore.isSuperAdmin) {
                    router.push({ name: 'forbidden' });
                    return;
                }

                try {
                    const response = await window.axios.get(`/api/schools/${schoolId}`);
                    const school = response.data.school;
                    form.value.name = school.name;
                    form.value.email = school.email;
                    form.value.phone = school.phone || '';
                    form.value.logo = school.logo || '';
                    form.value.address = school.address || '';
                    form.value.status = school.status;
                    form.value.mobile_academic_year_id = school.mobile_academic_year_id || '';
                    await fetchSchoolAcademicYears(schoolId);
                } catch (error) {
                    console.error(error);
                    toastStore.error('Failed to load school information.');
                    router.push({ name: 'schools.index' });
                }
            } else {
                fetchSystemModules();
            }
        });

        const handleSubmit = async () => {
            saving.value = true;
            errors.value = {};
            try {
                if (isEdit.value) {
                    await window.axios.put(`/api/schools/${route.params.id}`, form.value);
                    toastStore.success('School updated successfully.');
                } else {
                    await window.axios.post('/api/schools', form.value);
                    toastStore.success('School and administrator created successfully with selected modules.');
                }
                
                // Redirect based on role
                if (authStore.isSuperAdmin) {
                    router.push({ name: 'schools.index' });
                } else {
                    router.push({ name: 'dashboard' });
                }
            } catch (error) {
                console.error(error);
                if (error.response?.data?.errors) {
                    errors.value = error.response.data.errors;
                } else {
                    toastStore.error(error.response?.data?.message || 'Failed to save school details.');
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
            availableModules,
            loadingModules,
            getModuleEmoji,
            handleSubmit,
            uploadingLogo,
            logoInput,
            onLogoSelected,
            clearLogo,
            activeAcademicYears
        };
    }
}
</script>
