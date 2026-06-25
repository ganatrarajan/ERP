<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 dark:text-white">Settings & Profile</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage your personal account settings and school organization details.</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 gap-6">
            <button 
                @click="activeTab = 'profile'"
                :class="[
                    'pb-4 text-base font-bold border-b-2 transition-all duration-200',
                    activeTab === 'profile' 
                        ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
                ]"
            >
                My Profile Settings
            </button>
            <button 
                v-if="showSchoolTab"
                @click="activeTab = 'school'"
                :class="[
                    'pb-4 text-base font-bold border-b-2 transition-all duration-200',
                    activeTab === 'school' 
                        ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' 
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
                ]"
            >
                School Organization
            </button>
        </div>

        <!-- Tab Content -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm">
            <!-- 1. My Profile Tab -->
            <form v-if="activeTab === 'profile'" @submit.prevent="saveProfile" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- User Name -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Full Name</label>
                        <input 
                            v-model="profileForm.name" 
                            type="text" 
                            required 
                            placeholder="John Doe"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        />
                    </div>

                    <!-- Email Address -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Email Address</label>
                        <input 
                            v-model="profileForm.email" 
                            type="email" 
                            required 
                            placeholder="john@example.com"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        />
                    </div>

                    <!-- Mobile Number -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Mobile Number</label>
                        <input 
                            v-model="profileForm.mobile" 
                            type="text" 
                            placeholder="+1 (555) 000-0000"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        />
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">Security & Password</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Leave password fields blank if you do not want to change your current password.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Password -->
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">New Password</label>
                            <input 
                                v-model="profileForm.password" 
                                type="password" 
                                placeholder="••••••••"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                            />
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Confirm New Password</label>
                            <input 
                                v-model="profileForm.password_confirmation" 
                                type="password" 
                                placeholder="••••••••"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                            />
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:scale-100"
                    >
                        <span v-if="saving">Saving Changes...</span>
                        <span v-else>Save Profile Settings</span>
                    </button>
                </div>
            </form>

            <!-- 2. School Tab -->
            <form v-if="activeTab === 'school' && showSchoolTab" @submit.prevent="saveSchool" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- School Name -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">School Name</label>
                        <input 
                            v-model="schoolForm.name" 
                            type="text" 
                            required 
                            placeholder="Greenwood High"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        />
                    </div>

                    <!-- School Email -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">School Contact Email</label>
                        <input 
                            v-model="schoolForm.email" 
                            type="email" 
                            required 
                            placeholder="contact@school.com"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        />
                    </div>

                    <!-- School Phone -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">School Contact Phone</label>
                        <input 
                            v-model="schoolForm.phone" 
                            type="text" 
                            placeholder="(123) 456-7890"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        />
                    </div>

                    <!-- School Logo Upload -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">School Logo</label>
                        <div class="flex items-center gap-4">
                            <!-- Logo Preview -->
                            <div class="relative w-16 h-16 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden group shadow-sm flex-shrink-0">
                                <img 
                                    v-if="schoolForm.logo" 
                                    :src="schoolForm.logo" 
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">No Logo</span>
                                <button 
                                    v-if="schoolForm.logo" 
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
                                    class="w-full px-4 py-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <svg v-if="uploadingLogo" class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    {{ uploadingLogo ? 'Uploading...' : 'Upload Logo JPG/PNG' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Address -->
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Physical Address</label>
                        <textarea 
                            v-model="schoolForm.address" 
                            rows="3" 
                            placeholder="123 Academic Street, Education City"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all resize-none"
                        ></textarea>
                    </div>

                    <!-- Mobile Academic Year (School Setting) -->
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Mobile Academic Year</label>
                        <select 
                            v-model="schoolForm.mobile_academic_year_id" 
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 text-base transition-all"
                        >
                            <option value="">Select Mobile Academic Year</option>
                            <option v-for="year in activeAcademicYears" :key="year.id" :value="year.id">
                                {{ year.title }} <span v-if="year.is_current">(Current Active)</span>
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:scale-100"
                    >
                        <span v-if="saving">Saving School details...</span>
                        <span v-else>Save School Profile</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';

export default {
    name: 'Profile',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const activeTab = ref('profile');
        const saving = ref(false);

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

            try {
                const response = await window.axios.post('/api/schools/upload-logo', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                schoolForm.logo = response.data.url;
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
            schoolForm.logo = '';
        };

        const profileForm = reactive({
            name: '',
            email: '',
            mobile: '',
            password: '',
            password_confirmation: ''
        });

        const schoolForm = reactive({
            name: '',
            email: '',
            phone: '',
            logo: '',
            address: '',
            mobile_academic_year_id: ''
        });

        const activeAcademicYears = ref([]);
        const fetchSchoolAcademicYears = async () => {
            try {
                const response = await window.axios.get('/api/academic-years', {
                    params: { status: 'active' }
                });
                activeAcademicYears.value = response.data.academic_years;
            } catch (error) {
                console.error('Failed to load active academic years', error);
            }
        };

        const showSchoolTab = computed(() => {
            // Only non-Super Admin users who belong to a school and have settings.edit permission
            return !authStore.isSuperAdmin && authStore.user?.school_id && authStore.hasPermission('settings.edit');
        });

        onMounted(() => {
            // Hydrate forms
            if (authStore.user) {
                profileForm.name = authStore.user.name || '';
                profileForm.email = authStore.user.email || '';
                profileForm.mobile = authStore.user.mobile || '';

                if (authStore.user.school) {
                    schoolForm.name = authStore.user.school.name || '';
                    schoolForm.email = authStore.user.school.email || '';
                    schoolForm.phone = authStore.user.school.phone || '';
                    schoolForm.logo = authStore.user.school.logo || '';
                    schoolForm.address = authStore.user.school.address || '';
                    schoolForm.mobile_academic_year_id = authStore.user.school.mobile_academic_year_id || '';
                }

                if (showSchoolTab.value) {
                    fetchSchoolAcademicYears();
                }
            }
        });

        const saveProfile = async () => {
            saving.value = true;
            try {
                const response = await window.axios.put('/api/auth/profile', {
                    name: profileForm.name,
                    email: profileForm.email,
                    mobile: profileForm.mobile,
                    password: profileForm.password || null,
                    password_confirmation: profileForm.password_confirmation || null
                });

                // Update authStore user details
                authStore.user = response.data.user;

                // Reset passwords
                profileForm.password = '';
                profileForm.password_confirmation = '';

                toastStore.success('Account profile updated successfully');
            } catch (error) {
                const msg = error.response?.data?.message || 'Failed to update profile settings';
                toastStore.error(msg);
            } finally {
                saving.value = false;
            }
        };

        const saveSchool = async () => {
            saving.value = true;
            try {
                const response = await window.axios.put(`/api/schools/${authStore.user.school_id}`, {
                    name: schoolForm.name,
                    email: schoolForm.email,
                    phone: schoolForm.phone,
                    logo: schoolForm.logo || null,
                    address: schoolForm.address || null,
                    mobile_academic_year_id: schoolForm.mobile_academic_year_id || null
                });

                // Update authStore user school details
                if (authStore.user) {
                    authStore.user.school = response.data.school;
                }

                toastStore.success('School organization profile updated successfully');
            } catch (error) {
                const msg = error.response?.data?.message || 'Failed to update school settings';
                toastStore.error(msg);
            } finally {
                saving.value = false;
            }
        };

        return {
            activeTab,
            saving,
            profileForm,
            schoolForm,
            showSchoolTab,
            saveProfile,
            saveSchool,
            uploadingLogo,
            logoInput,
            onLogoSelected,
            clearLogo,
            activeAcademicYears
        };
    }
};
</script>
