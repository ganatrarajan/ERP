<template>
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <router-link 
                    to="/students"
                    class="p-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl text-slate-500 dark:text-slate-400 transition-colors"
                    title="Back to Directory"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </router-link>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Student Profile</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Detailed dossier, contact info, and historical enrollment track.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <router-link 
                    v-if="authStore.hasPermission('student.edit')"
                    :to="`/students/${student?.id}/edit`"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit Record
                </router-link>
            </div>
        </div>

        <div v-if="loading" class="p-12 text-center animate-pulse space-y-4">
            <div class="w-24 h-24 bg-slate-200 dark:bg-slate-800 rounded-full mx-auto"></div>
            <div class="h-6 bg-slate-200 dark:bg-slate-800 w-1/3 mx-auto rounded-lg"></div>
            <div class="h-4 bg-slate-200 dark:bg-slate-800 w-1/2 mx-auto rounded-lg"></div>
        </div>

        <div v-else-if="!student" class="p-12 text-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-500">
            Student profile not found.
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Card: Brief Dossier Summary -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col items-center text-center space-y-4 h-fit">
                <div class="w-28 h-28 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-indigo-500/20 flex items-center justify-center font-extrabold text-2xl text-slate-600 dark:text-slate-300 overflow-hidden shadow-inner">
                    <img v-if="student.photo" :src="student.photo" class="object-cover w-full h-full" />
                    <span v-else>{{ student.first_name.substring(0, 1) }}{{ student.last_name.substring(0, 1) }}</span>
                </div>

                <div>
                    <h2 class="text-xl font-extrabold text-slate-800 dark:text-white">{{ student.first_name }} {{ student.last_name }}</h2>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-0.5">ADM NO: {{ student.admission_no }}</p>
                </div>
 
                <div class="flex flex-wrap justify-center gap-1.5 pt-1">
                    <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize',
                        student.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-450 border border-rose-500/20'
                    ]">
                        {{ student.status }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                        {{ student.gender }}
                    </span>
                </div>

                <div class="w-full border-t border-slate-100 dark:border-slate-800 pt-4 space-y-2 text-left">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 dark:text-slate-500 font-semibold">Admission Date:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ formatDate(student.admission_date) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 dark:text-slate-500 font-semibold">Blood Group:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ student.blood_group || 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Right Tabs Panel: Dossier Details & Timeline -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Navigation Tabs -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1.5 rounded-xl shadow-sm flex gap-1">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
                        :class="activeTab === tab.id 
                            ? 'bg-slate-100 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm' 
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                    >
                        {{ tab.name }}
                    </button>
                </div>

                <!-- Tab 1: Personal Details -->
                <div v-show="activeTab === 'personal'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-md font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2">
                        Contact & Identity details
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Student Email</span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.email || 'No email registered' }}</span>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Student Mobile</span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.mobile || 'No mobile registered' }}</span>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Date of Birth</span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ formatDate(student.date_of_birth) }}</span>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider block">Residential Address</span>
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 block whitespace-pre-line">{{ student.address || 'No address registered' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Parents & Family -->
                <div v-show="activeTab === 'family'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                    <!-- Father -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-1">
                            Father Details
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">NAME</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.father_name || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">MOBILE</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.father_mobile || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">EMAIL</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.father_email || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Mother -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-1">
                            Mother Details
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">NAME</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.mother_name || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">MOBILE</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.mother_mobile || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">EMAIL</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.mother_email || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-1">
                            Guardian Details
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">NAME</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.guardian_name || 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">MOBILE</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ student.parent?.guardian_mobile || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Academic History (TIMELINE) -->
                <div v-show="activeTab === 'academic'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-md font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2">
                        Academic History & Placements
                    </h3>

                    <!-- History Timeline -->
                    <div class="relative pl-6 space-y-6 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                        <div 
                            v-for="(record, index) in student.academic_records" 
                            :key="record.id" 
                            class="relative"
                        >
                            <!-- Indicator node dot -->
                            <span 
                                :class="[
                                    'absolute -left-6.5 top-1.5 w-3.5 h-3.5 rounded-full border-2 bg-white dark:bg-slate-900',
                                    index === 0 ? 'border-indigo-600 dark:border-indigo-400 scale-120' : 'border-slate-300 dark:border-slate-700'
                                ]"
                            ></span>

                            <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 rounded-xl p-4 flex justify-between items-start">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">
                                            {{ record.class?.name }} — {{ record.section?.name }}
                                        </h4>
                                        <span v-if="index === 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 uppercase">
                                            Current Placement
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Academic Year: {{ record.academic_year?.title }}</p>
                                    <p class="text-xs text-slate-500">Roll Number: {{ record.roll_no || 'N/A' }}</p>
                                </div>
                                <span class="text-xs text-slate-400 dark:text-slate-550 font-medium">
                                    Enrolled: {{ formatDate(record.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'StudentShow',
    setup() {
        const route = useRoute();
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const student = ref(null);
        const loading = ref(true);
        const activeTab = ref('personal');

        const tabs = [
            { id: 'personal', name: 'Profile & Contact' },
            { id: 'family', name: 'Parental Info' },
            { id: 'academic', name: 'Academic History' }
        ];

        const fetchStudentProfile = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get(`/api/students/${route.params.id}`);
                student.value = response.data.student;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load student dossier profile.');
            } finally {
                loading.value = false;
            }
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        };

        onMounted(() => {
            fetchStudentProfile();
        });

        return {
            authStore,
            student,
            loading,
            activeTab,
            tabs,
            formatDate
        };
    }
}
</script>
