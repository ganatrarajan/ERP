<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <router-link to="/users" class="p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 rounded-xl transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </router-link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">Staff Custom Report Builder</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Design custom tabular printouts, CSV spreadsheets, and PDF documents dynamically.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Side: Controls & Columns Configuration -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Filters Configuration Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">1. Apply Filters</h3>
                    
                    <div class="space-y-3">
                        <!-- Role -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Filter Role</label>
                            <select v-model="filters.role" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                                <option value="">All Roles</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Account Status</label>
                            <select v-model="filters.status" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- Department -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Department</label>
                            <input v-model="filters.department" type="text" placeholder="e.g. English" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200" />
                        </div>

                        <!-- Designation -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Designation</label>
                            <input v-model="filters.designation" type="text" placeholder="e.g. Principal" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200" />
                        </div>

                        <!-- Academic Class -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Assigned Class</label>
                            <select v-model="filters.class_id" @change="loadSections" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200">
                                <option value="">All Classes</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <!-- Section -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Assigned Section</label>
                            <select v-model="filters.section_id" @change="loadSubjects" :disabled="!filters.class_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                                <option value="">All Sections</option>
                                <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Assigned Subject</label>
                            <select v-model="filters.subject_id" :disabled="!filters.class_id" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl focus:outline-none focus:border-indigo-500 text-slate-700 dark:text-slate-200 disabled:opacity-50">
                                <option value="">All Subjects</option>
                                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Column Configurations Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-850 pb-2">2. Choose Columns</h3>
                    
                    <div class="max-h-[350px] overflow-y-auto space-y-2 pr-1 select-none">
                        <label v-for="(label, key) in availableColumns" :key="key" class="flex items-center gap-2.5 text-xs font-bold text-slate-650 dark:text-slate-350 cursor-pointer p-1 rounded hover:bg-slate-50 dark:hover:bg-slate-850">
                            <input type="checkbox" :value="key" v-model="selectedColumns" class="rounded border-slate-300 text-indigo-650 focus:ring-indigo-500 h-4 w-4" />
                            {{ label }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Side: Live preview table & download tools -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Action Controls Bar -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-3xl shadow-sm flex flex-wrap gap-3 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button 
                            @click="fetchReportData"
                            :disabled="loading"
                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition-all border-none cursor-pointer disabled:opacity-50"
                        >
                            {{ loading ? 'Generating...' : 'Preview Report' }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Print -->
                        <button 
                            @click="printReport"
                            :disabled="users.length === 0 || loading"
                            class="px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-sm hover:bg-slate-50 transition-all cursor-pointer flex items-center gap-1"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Report
                        </button>

                        <!-- Export CSV -->
                        <button 
                            @click="exportCSV"
                            :disabled="users.length === 0 || loading"
                            class="px-3.5 py-2.5 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-sm hover:bg-slate-50 transition-all cursor-pointer flex items-center gap-1"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export CSV
                        </button>

                        <!-- Export PDF -->
                        <button 
                            @click="exportPDF"
                            :disabled="users.length === 0 || loading"
                            class="px-3.5 py-2.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-750 dark:text-indigo-400 font-bold text-xs rounded-xl shadow-sm hover:bg-indigo-100 transition-all cursor-pointer flex items-center gap-1 border border-indigo-150 dark:border-indigo-900/50"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Export PDF
                        </button>
                    </div>
                </div>

                <!-- Preview Grid Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                    <div class="p-4 bg-slate-50/50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Report Preview Grid</span>
                        <span v-if="users.length > 0" class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-650 dark:text-indigo-400 rounded-lg text-[10px] font-bold border border-indigo-150">
                            {{ users.length }} matches found
                        </span>
                    </div>

                    <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                        <div v-for="i in 4" :key="i" class="h-10 bg-slate-100 dark:bg-slate-850 rounded-xl"></div>
                    </div>

                    <div v-else-if="users.length === 0" class="p-12 text-center text-slate-500">
                        <svg class="w-16 h-16 mx-auto text-slate-200 dark:text-slate-800 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="font-bold text-slate-700 dark:text-slate-300 text-xs">No Report Data Cached</p>
                        <p class="text-[11px] text-slate-400">Select columns & filter requirements, then click "Preview Report".</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold text-slate-455 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/60 uppercase">
                                    <th v-for="col in selectedColumns" :key="col" class="p-3">
                                        {{ availableColumns[col] }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-350">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/10">
                                    <td v-for="col in selectedColumns" :key="col" class="p-3">
                                        <template v-if="col === 'dob'">
                                            {{ formatDate(user.dob) }}
                                        </template>
                                        <template v-else-if="col === 'joining_date'">
                                            {{ formatDate(user.joining_date) }}
                                        </template>
                                        <template v-else-if="col === 'status'">
                                            <span :class="user.status === 'active' ? 'text-emerald-500 font-bold' : 'text-rose-500 font-bold'">
                                                {{ user.status }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            {{ user[col] || '-' }}
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'UserReports',
    setup() {
        const toastStore = useToastStore();

        const loading = ref(false);
        const users = ref([]);
        const roles = ref([]);
        const classes = ref([]);
        const sections = ref([]);
        const subjects = ref([]);

        // Config Filters
        const filters = ref({
            role: '',
            status: '',
            department: '',
            designation: '',
            class_id: '',
            section_id: '',
            subject_id: '',
        });

        // Config Columns
        const availableColumns = {
            employee_id: 'Employee ID',
            name: 'Full Name',
            email: 'Email',
            mobile: 'Mobile Phone',
            gender: 'Gender',
            dob: 'Date of Birth',
            aadhaar_no: 'Aadhaar No',
            pan_no: 'PAN No',
            address: 'Address',
            emergency_contact_name: 'Emerg Name',
            emergency_contact_mobile: 'Emerg Mobile',
            status: 'Status',
            teacher_code: 'Teacher Code',
            qualification: 'Qualification',
            experience: 'Experience',
            joining_date: 'Joining Date',
            department: 'Department',
            designation: 'Designation',
            employment_type: 'Employment Type'
        };

        const selectedColumns = ref(['employee_id', 'name', 'email', 'mobile', 'designation', 'status']);

        const fetchReportData = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/users/reports', {
                    params: {
                        ...filters.value,
                        columns: selectedColumns.value.join(','),
                    }
                });
                users.value = response.data.users;
            } catch (err) {
                console.error(err);
                toastStore.error('Could not generate report preview.');
            } finally {
                loading.value = false;
            }
        };

        const loadSections = async () => {
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
                    console.error(err);
                }
            }
        };

        const loadSubjects = async () => {
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
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString();
        };

        // Export CSV
        const exportCSV = () => {
            const queryParams = new URLSearchParams({
                ...filters.value,
                columns: selectedColumns.value.join(','),
                export: 'csv'
            }).toString();
            window.open(`/api/users/reports?${queryParams}`, '_blank');
        };

        // Export PDF
        const exportPDF = () => {
            const queryParams = new URLSearchParams({
                ...filters.value,
                columns: selectedColumns.value.join(','),
                export: 'pdf'
            }).toString();
            window.open(`/api/users/reports?${queryParams}`, '_blank');
        };

        // Print Report
        const printReport = () => {
            const queryParams = new URLSearchParams({
                ...filters.value,
                columns: selectedColumns.value.join(','),
                export: 'pdf'
            }).toString();
            // We reuse the landscape or portrait PDF for printing
            window.open(`/api/users/reports?${queryParams}`, '_blank');
        };

        onMounted(async () => {
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
            loading,
            users,
            roles,
            classes,
            sections,
            subjects,
            filters,
            availableColumns,
            selectedColumns,
            fetchReportData,
            loadSections,
            loadSubjects,
            formatDate,
            exportCSV,
            exportPDF,
            printReport
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
