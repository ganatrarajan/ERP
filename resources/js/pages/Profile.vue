<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Settings Control Header -->
        <SettingsHeader />

        <!-- Tab Content -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm">
            <!-- 1. My Profile Tab -->
            <form v-if="activeTab === 'profile'" @submit.prevent="saveProfile" class="space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Account Details</h3>
                    <p class="text-xs text-slate-500">Update your email, phone, and password details.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- User Name -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Full Name</label>
                        <input 
                            v-model="profileForm.name" 
                            type="text" 
                            required 
                            placeholder="John Doe"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- Email Address -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Email Address</label>
                        <input 
                            v-model="profileForm.email" 
                            type="email" 
                            required 
                            placeholder="john@example.com"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- Mobile Number -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Mobile Number</label>
                        <input 
                            v-model="profileForm.mobile" 
                            type="text" 
                            placeholder="+1 (555) 000-0000"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">Security & Password</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Leave password fields blank if you do not want to change your current password.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Password -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">New Password</label>
                            <input 
                                v-model="profileForm.password" 
                                type="password" 
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                            />
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Confirm New Password</label>
                            <input 
                                v-model="profileForm.password_confirmation" 
                                type="password" 
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                            />
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/10 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:scale-100 cursor-pointer"
                    >
                        <span v-if="saving">Saving Changes...</span>
                        <span v-else>Save Profile Settings</span>
                    </button>
                </div>
            </form>

            <!-- 2. School Tab -->
            <form v-if="activeTab === 'school' && showSchoolTab" @submit.prevent="saveSchool" class="space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">School Profile</h3>
                    <p class="text-xs text-slate-500">Edit school contact details, logo, and active academic year settings.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- School Name -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">School Name</label>
                        <input 
                            v-model="schoolForm.name" 
                            type="text" 
                            required 
                            placeholder="Greenwood High"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- School Email -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">School Contact Email</label>
                        <input 
                            v-model="schoolForm.email" 
                            type="email" 
                            required 
                            placeholder="contact@school.com"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- School Phone -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">School Contact Phone</label>
                        <input 
                            v-model="schoolForm.phone" 
                            type="text" 
                            placeholder="(123) 456-7890"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <!-- School Logo Upload -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">School Logo</label>
                        <div class="flex items-center gap-4">
                            <!-- Logo Preview -->
                            <div class="relative w-12 h-12 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-center overflow-hidden group shadow-sm flex-shrink-0">
                                <img 
                                    v-if="schoolForm.logo" 
                                    :src="schoolForm.logo" 
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase">No Logo</span>
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
                                    class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
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
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Physical Address</label>
                        <textarea 
                            v-model="schoolForm.address" 
                            rows="2" 
                            placeholder="123 Academic Street, Education City"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all resize-none"
                        ></textarea>
                    </div>

                    <!-- Mobile Academic Year (School Setting) -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-550 dark:text-slate-400 uppercase tracking-wider">Mobile Academic Year</label>
                        <select 
                            v-model="schoolForm.mobile_academic_year_id" 
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-200"
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
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-50 disabled:scale-100 cursor-pointer"
                    >
                        <span v-if="saving">Saving School details...</span>
                        <span v-else>Save School Profile</span>
                    </button>
                </div>
            </form>

            <!-- 3. Weekend Settings Tab -->
            <div v-if="activeTab === 'weekend' && showWeekendTab" class="space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white">Weekend & Weekly Holidays</h3>
                        <p class="text-xs text-slate-500">Configure Satuday holidays globally or for specific class sections.</p>
                    </div>
                    
                    <button 
                        @click="toggleSchoolWideSaturdayHoliday"
                        :disabled="savingWeekend"
                        :class="[
                            'px-4 py-2.5 font-bold text-xs rounded-xl shadow transition-all active:scale-95 cursor-pointer flex items-center gap-1.5',
                            isSchoolWideSaturdayHoliday 
                                ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/10' 
                                : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-500/10'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        {{ isSchoolWideSaturdayHoliday ? 'Disable School-wide Saturday Off' : 'Enable School-wide Saturday Off' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Left: Assign Custom Class Saturday Off -->
                    <div class="md:col-span-1 bg-slate-50 dark:bg-slate-950 p-5 rounded-2xl border border-slate-200/50 dark:border-slate-800/80 space-y-4">
                        <h4 class="text-xs font-bold text-slate-650 dark:text-slate-300 uppercase tracking-wider">Configure Saturday Off</h4>
                        <div class="space-y-3">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Class *</label>
                                <select 
                                    v-model="weekendForm.class_id" 
                                    @change="fetchWeekendSections"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                                >
                                    <option value="">Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                        {{ cls.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Section (Optional)</label>
                                <select 
                                    v-model="weekendForm.section_id" 
                                    :disabled="!weekendForm.class_id"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 disabled:opacity-50"
                                >
                                    <option value="">All Sections</option>
                                    <option v-for="sec in weekendSections" :key="sec.id" :value="sec.id">
                                        {{ sec.name }}
                                    </option>
                                </select>
                            </div>

                            <button 
                                @click="saveClassSectionWeekendHoliday"
                                :disabled="savingWeekend || !weekendForm.class_id"
                                class="w-full mt-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow active:scale-95 disabled:opacity-50 cursor-pointer transition-all"
                            >
                                Apply Saturday Off
                            </button>
                        </div>
                    </div>

                    <!-- Right: Saturday Off Listings -->
                    <div class="md:col-span-2 space-y-3">
                        <h4 class="text-xs font-bold text-slate-650 dark:text-slate-350 uppercase tracking-wider">Active Weekend Custom Configurations</h4>
                        
                        <div v-if="loadingWeekend" class="space-y-2 animate-pulse">
                            <div v-for="i in 3" :key="i" class="h-10 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
                        </div>

                        <div v-else-if="weekendSettings.length === 0" class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl text-slate-400 text-xs">
                            No custom Saturday holidays configured. All Saturdays are working days except school-wide settings.
                        </div>

                        <div v-else class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden bg-white dark:bg-slate-900 shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/60 dark:bg-slate-950/40 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase border-b border-slate-200 dark:border-slate-800">
                                            <th class="p-3 pl-4">Target Scope</th>
                                            <th class="p-3">Class</th>
                                            <th class="p-3">Section</th>
                                            <th class="p-3">Holiday Day</th>
                                            <th class="p-3 pr-4 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-xs text-slate-700 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-800/80">
                                        <tr v-for="setting in weekendSettings" :key="setting.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                            <td class="p-3 pl-4 font-bold capitalize">
                                                {{ setting.target_type === 'all' ? 'School-wide' : 'Custom allocation' }}
                                            </td>
                                            <td class="p-3 font-semibold text-slate-800 dark:text-white">
                                                {{ setting.class?.name || 'All Classes' }}
                                            </td>
                                            <td class="p-3">
                                                {{ setting.section?.name || 'All Sections' }}
                                            </td>
                                            <td class="p-3 font-mono font-medium text-rose-500">
                                                {{ setting.day_name }}
                                            </td>
                                            <td class="p-3 pr-4 text-right">
                                                <button 
                                                    @click="deleteWeekendSetting(setting.id)"
                                                    class="p-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 rounded border border-rose-500/20 transition-colors cursor-pointer"
                                                    title="Remove Holiday Setting"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';
import { useConfirmStore } from '../stores/confirm';
import { useRoute } from 'vue-router';
import SettingsHeader from '../components/SettingsHeader.vue';

export default {
    name: 'Profile',
    components: { SettingsHeader },
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();
        const confirmStore = useConfirmStore();
        const route = useRoute();

        const activeTab = ref('profile');
        const saving = ref(false);
        const uploadingLogo = ref(false);
        const logoInput = ref(null);

        // Weekend Settings State
        const weekendSettings = ref([]);
        const weekendSections = ref([]);
        const savingWeekend = ref(false);
        const loadingWeekend = ref(false);
        const classes = ref([]);
        const weekendForm = reactive({
            class_id: '',
            section_id: ''
        });

        const activeAcademicYears = ref([]);

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

        const showSchoolTab = computed(() => {
            return !authStore.isSuperAdmin && authStore.user?.school_id && authStore.hasPermission('settings.edit');
        });

        const showWeekendTab = computed(() => {
            return !authStore.isSuperAdmin && authStore.user?.school_id && authStore.hasPermission('settings.edit');
        });

        const isSchoolWideSaturdayHoliday = computed(() => {
            return weekendSettings.value.some(s => s.target_type === 'all' && s.day_name === 'Saturday');
        });



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

        const fetchClassesList = async () => {
            try {
                const response = await window.axios.get('/api/classes', { params: { all: true } });
                classes.value = response.data.classes || response.data;
            } catch (error) {
                console.error('Failed to load classes list', error);
            }
        };

        const loadWeekendSettings = async () => {
            loadingWeekend.value = true;
            try {
                const response = await window.axios.get('/api/weekend-settings');
                weekendSettings.value = response.data.settings;
            } catch (e) {
                toastStore.error('Failed to load weekend settings.');
            } finally {
                loadingWeekend.value = false;
            }
        };

        const fetchWeekendSections = async () => {
            if (!weekendForm.class_id) {
                weekendSections.value = [];
                weekendForm.section_id = '';
                return;
            }
            try {
                const response = await window.axios.get('/api/sections', { params: { class_id: weekendForm.class_id, all: true } });
                weekendSections.value = response.data.sections || response.data;
                weekendForm.section_id = '';
            } catch (e) {
                toastStore.error('Failed to load sections.');
            }
        };

        const toggleSchoolWideSaturdayHoliday = async () => {
            savingWeekend.value = true;
            try {
                const isCurrentActive = isSchoolWideSaturdayHoliday.value;
                if (isCurrentActive) {
                    const schoolWideSetting = weekendSettings.value.find(s => s.target_type === 'all' && s.day_name === 'Saturday');
                    if (schoolWideSetting) {
                        await window.axios.delete(`/api/weekend-settings/${schoolWideSetting.id}`);
                        toastStore.success('School-wide Saturday holiday disabled.');
                    }
                } else {
                    await window.axios.post('/api/weekend-settings', {
                        target_type: 'all'
                    });
                    toastStore.success('School-wide Saturday holiday enabled.');
                }
                await loadWeekendSettings();
            } catch (e) {
                toastStore.error('Failed to update Saturday holiday settings.');
            } finally {
                savingWeekend.value = false;
            }
        };

        const saveClassSectionWeekendHoliday = async () => {
            if (!weekendForm.class_id) return;
            savingWeekend.value = true;
            try {
                const payload = {
                    target_type: 'class_section',
                    class_id: weekendForm.class_id,
                    section_id: weekendForm.section_id ? weekendForm.section_id : null
                };
                const response = await window.axios.post('/api/weekend-settings', payload);
                toastStore.success(response.data.message || 'Saturday holiday setting saved.');
                weekendForm.class_id = '';
                weekendForm.section_id = '';
                weekendSections.value = [];
                await loadWeekendSettings();
            } catch (e) {
                const msg = e.response?.data?.message || 'Failed to save weekend settings.';
                toastStore.error(msg);
            } finally {
                savingWeekend.value = false;
            }
        };

        const deleteWeekendSetting = async (id) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Weekend Setting',
                message: 'Are you sure you want to delete this weekend settings holiday? Saturday will become a working day for this class/section.',
                type: 'warning',
                confirmText: 'Confirm Delete',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/weekend-settings/${id}`);
                    toastStore.success('Weekend holiday setting removed.');
                    await loadWeekendSettings();
                } catch (e) {
                    toastStore.error('Failed to remove weekend holiday setting.');
                }
            }
        };

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

        // Sync tab state with route query param
        watch(() => route.query.tab, (newTab) => {
            if (newTab && ['profile', 'school', 'weekend'].includes(newTab)) {
                activeTab.value = newTab;
                if (newTab === 'weekend') {
                    loadWeekendSettings();
                    fetchClassesList();
                }
            } else if (!newTab) {
                activeTab.value = 'profile';
            }
        }, { immediate: true });

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
            // Basic validation
            if (profileForm.password && profileForm.password !== profileForm.password_confirmation) {
                toastStore.error('Password confirmations do not match.');
                return;
            }
            saving.value = true;
            try {
                const response = await window.axios.put('/api/auth/profile', {
                    name: profileForm.name,
                    email: profileForm.email,
                    mobile: profileForm.mobile,
                    password: profileForm.password || null,
                    password_confirmation: profileForm.password_confirmation || null
                });

                authStore.user = response.data.user;
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
            showWeekendTab,
            saveProfile,
            saveSchool,
            uploadingLogo,
            logoInput,
            onLogoSelected,
            clearLogo,
            activeAcademicYears,
            // Weekend configs
            weekendSettings,
            weekendSections,
            savingWeekend,
            loadingWeekend,
            classes,
            weekendForm,
            isSchoolWideSaturdayHoliday,
            fetchWeekendSections,
            toggleSchoolWideSaturdayHoliday,
            saveClassSectionWeekendHoliday,
            deleteWeekendSetting
        };
    }
};
</script>
