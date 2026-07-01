<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Back Link & Header -->
        <div class="flex items-center gap-3">
            <router-link to="/users" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </router-link>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">{{ isEdit ? 'Edit User & Staff Record' : 'Add New Staff Member' }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Configure personal credentials, emergency contacts, system roles, and teaching profile.</p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Errors Alert -->
            <div v-if="Object.keys(errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-2xl text-xs font-semibold space-y-1">
                <p class="font-bold flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> Please correct validation errors:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    <li v-for="(err, field) in errors" :key="field">{{ err[0] }}</li>
                </ul>
            </div>

            <!-- Card 1: Personal Profile & Photo -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white tracking-wide border-b border-slate-100 dark:border-slate-850 pb-2">1. Personal & Contact Information</h3>
                
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Photo Upload Box -->
                    <div class="flex flex-col items-center justify-start space-y-3 shrink-0">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Profile Photo</label>
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden transition-all group-hover:border-indigo-500">
                                <img v-if="form.profile_photo" :src="photoUrl" class="w-full h-full object-cover" />
                                <svg v-else class="w-12 h-12 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <input 
                                type="file" 
                                ref="photoInput"
                                @change="uploadPhoto"
                                accept="image/*"
                                class="hidden"
                            />
                            <button 
                                type="button"
                                @click="$refs.photoInput.click()"
                                :disabled="uploadingPhoto"
                                class="absolute inset-0 bg-slate-900/60 text-white rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1 text-[11px] font-bold"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span>{{ uploadingPhoto ? 'Uploading...' : 'Change Photo' }}</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 text-center max-w-[150px]">JPEG, PNG up to 2MB. Stored securely.</p>
                    </div>

                    <!-- Personal Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">
                        <!-- Employee ID -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Employee ID</label>
                            <input 
                                type="text" 
                                v-model="form.employee_id" 
                                placeholder="EMP-2026-001"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- Full Name -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Full Name</label>
                            <input 
                                type="text" 
                                v-model="form.name" 
                                required
                                placeholder="Jane Doe"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Gender</label>
                            <select 
                                v-model="form.gender"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            >
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Date of Birth -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Date of Birth</label>
                            <input 
                                type="date" 
                                v-model="form.dob" 
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- Mobile Number -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Mobile Number</label>
                            <input 
                                type="text" 
                                v-model="form.mobile" 
                                placeholder="+91 98765 43210"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- Aadhaar No -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Aadhaar Number (12 Digits)</label>
                            <input 
                                type="text" 
                                v-model="form.aadhaar_no" 
                                maxlength="12"
                                placeholder="123456789012"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <!-- PAN No -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">PAN Card Number</label>
                            <input 
                                type="text" 
                                v-model="form.pan_no" 
                                maxlength="10"
                                placeholder="ABCDE1234F"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Residential Address</label>
                            <textarea 
                                v-model="form.address" 
                                placeholder="Full residential address details..."
                                rows="2"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Emergency Contact & Login Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left: Emergency Contact -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white tracking-wide border-b border-slate-100 dark:border-slate-850 pb-2">2. Emergency Contact</h3>
                    <div class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Emergency Contact Name</label>
                            <input 
                                type="text" 
                                v-model="form.emergency_contact_name" 
                                placeholder="Contact person name"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Emergency Mobile Phone</label>
                            <input 
                                type="text" 
                                v-model="form.emergency_contact_mobile" 
                                placeholder="+91 98000 00000"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>
                    </div>
                </div>

                <!-- Right: Account Login Credentials -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white tracking-wide border-b border-slate-100 dark:border-slate-850 pb-2">3. Login & Security Credentials</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Email Address (Login Username)</label>
                            <input 
                                type="email" 
                                v-model="form.email" 
                                required
                                placeholder="jane@domain.com"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                                Password <span v-if="isEdit" class="text-[9px] text-slate-400 lowercase">(leave blank to keep current)</span>
                            </label>
                            <input 
                                type="password" 
                                v-model="form.password" 
                                :required="!isEdit"
                                placeholder="••••••••"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                            <select 
                                v-model="form.status" 
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div v-if="authStore.isSuperAdmin">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Associated School</label>
                            <select 
                                v-model="form.school_id" 
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            >
                                <option :value="null">SaaS Administration (No School)</option>
                                <option v-for="school in schools" :key="school.id" :value="school.id">{{ school.name }}</option>
                            </select>
                        </div>

                        <div :class="{'md:col-span-2': !authStore.isSuperAdmin}">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Designation Role</label>
                            <select 
                                v-model="form.role" 
                                required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                            >
                                <option value="" disabled>Choose Designation...</option>
                                <option v-for="role in filteredRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Teacher-Only Details (Appears only when Selected Role is Teacher) -->
            <div v-if="selectedRoleName === 'Teacher'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 animate-[fadeIn_0.2s_ease-out]">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white tracking-wide border-b border-slate-100 dark:border-slate-850 pb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    4. Professional Teaching Credentials
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Teacher Code -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Teacher Code / Badge No</label>
                        <input 
                            type="text" 
                            v-model="form.teacher_code" 
                            required
                            placeholder="TCH-2026-045"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Academic Department</label>
                        <input 
                            type="text" 
                            v-model="form.department" 
                            required
                            placeholder="Science / Mathematics"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Designation -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Teacher Designation</label>
                        <input 
                            type="text" 
                            v-model="form.designation" 
                            required
                            placeholder="Senior Teacher / HOD"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Joining Date -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Date of Joining</label>
                        <input 
                            type="date" 
                            v-model="form.joining_date" 
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Employment Type -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Employment Type</label>
                        <select 
                            v-model="form.employment_type" 
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        >
                            <option value="">Select employment type...</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                            <option value="Substitute">Substitute</option>
                        </select>
                    </div>

                    <!-- Experience -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Total Teaching Experience</label>
                        <input 
                            type="text" 
                            v-model="form.experience" 
                            placeholder="e.g. 5 Years"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>

                    <!-- Qualification -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Educational Qualification Details</label>
                        <input 
                            type="text" 
                            v-model="form.qualification" 
                            required
                            placeholder="e.g. B.Ed, M.Sc. in Mathematics, Gujarat University"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-semibold text-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 transition-all"
                        />
                    </div>
                </div>
            </div>

            <!-- Form Action Footer -->
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
                    <span v-if="saving">Saving Record...</span>
                    <span v-else>{{ isEdit ? 'Update Details' : 'Create Staff Profile' }}</span>
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
        const uploadingPhoto = ref(false);
        const errors = ref({});

        const schools = ref([]);
        const roles = ref([]);

        const form = ref({
            employee_id: '',
            name: '',
            gender: '',
            dob: '',
            aadhaar_no: '',
            pan_no: '',
            address: '',
            emergency_contact_name: '',
            emergency_contact_mobile: '',
            profile_photo: '',
            
            // Teacher specific
            teacher_code: '',
            qualification: '',
            experience: '',
            joining_date: '',
            department: '',
            designation: '',
            employment_type: '',

            email: '',
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

        // Compute chosen role name to trigger teacher specific section
        const selectedRoleName = computed(() => {
            const chosen = roles.value.find(role => role.id === form.value.role);
            return chosen ? chosen.name : '';
        });

        // Get full URL path of user photo for previews
        const photoUrl = computed(() => {
            if (!form.value.profile_photo) return '';
            if (form.value.profile_photo.startsWith('http')) return form.value.profile_photo;
            return '/' + form.value.profile_photo;
        });

        onMounted(async () => {
            // Load roles
            try {
                const rolesResponse = await window.axios.get('/api/roles');
                roles.value = rolesResponse.data.roles;

                // Auto select role if query parameter role is present (e.g. ?role=Teacher)
                if (!route.params.id && route.query.role) {
                    const requestedRole = roles.value.find(role => role.name.toLowerCase() === route.query.role.toLowerCase());
                    if (requestedRole) {
                        form.value.role = requestedRole.id;
                    }
                }
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

                    form.value.employee_id = user.employee_id || '';
                    form.value.name = user.name;
                    form.value.gender = user.gender || '';
                    form.value.dob = user.dob ? user.dob.split('T')[0] : '';
                    form.value.aadhaar_no = user.aadhaar_no || '';
                    form.value.pan_no = user.pan_no || '';
                    form.value.address = user.address || '';
                    form.value.emergency_contact_name = user.emergency_contact_name || '';
                    form.value.emergency_contact_mobile = user.emergency_contact_mobile || '';
                    form.value.profile_photo = user.profile_photo || '';

                    form.value.teacher_code = user.teacher_code || '';
                    form.value.qualification = user.qualification || '';
                    form.value.experience = user.experience || '';
                    form.value.joining_date = user.joining_date ? user.joining_date.split('T')[0] : '';
                    form.value.department = user.department || '';
                    form.value.designation = user.designation || '';
                    form.value.employment_type = user.employment_type || '';

                    form.value.email = user.email;
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

        // Instant upload handler for selected profile photos
        const uploadPhoto = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('photo', file);
            if (form.value.school_id) {
                formData.append('school_id', form.value.school_id);
            }

            uploadingPhoto.value = true;
            try {
                const response = await window.axios.post('/api/users/upload-photo', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                form.value.profile_photo = response.data.path;
                toastStore.success('Photo uploaded successfully.');
            } catch (err) {
                console.error(err);
                toastStore.error(err.response?.data?.message || 'Failed to upload photo.');
            } finally {
                uploadingPhoto.value = false;
            }
        };

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
            uploadingPhoto,
            errors,
            schools,
            filteredRoles,
            selectedRoleName,
            photoUrl,
            form,
            uploadPhoto,
            handleSubmit
        };
    }
}
</script>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
