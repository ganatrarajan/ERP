<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Discounts</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage scholarship, sibling, and merit-based discount waivers assigned to specific students.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('fee_collection.create')"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Assign Discount
            </button>
        </div>

        <!-- Filters Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status</label>
                <select 
                    v-model="filters.status" 
                    @change="fetchDiscounts"
                    class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
            <div class="space-y-1 md:col-span-2">
                <!-- Search is handled locally or in filter -->
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Search Student</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Filter list by student name..." 
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                    />
                </div>
            </div>
        </div>

        <!-- Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="filteredDiscounts.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4"></path></svg>
                No fee discounts allocated yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Student Name</th>
                            <th class="p-4">Adm No</th>
                            <th class="p-4 text-right">Discount Value</th>
                            <th class="p-4">Reason / Notes</th>
                            <th class="p-4">Validity Range</th>
                            <th class="p-4 text-center">Uses</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="disc in filteredDiscounts" :key="disc.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ disc.student ? (disc.student.first_name + ' ' + disc.student.last_name) : 'N/A' }}
                            </td>
                            <td class="p-4 font-mono text-xs text-slate-500">
                                {{ disc.student ? disc.student.admission_no : 'N/A' }}
                            </td>
                            <td class="p-4 text-right font-black text-emerald-600 dark:text-emerald-450">
                                {{ disc.discount_type === 'percentage' ? disc.discount_value + '%' : '₹' + numberFormat(disc.discount_value) }}
                            </td>
                            <td class="p-4 text-slate-500 max-w-xs truncate">
                                {{ disc.reason || 'No reason provided' }}
                            </td>
                            <td class="p-4 text-xs text-slate-400">
                                <span v-if="disc.start_date || disc.end_date">
                                    {{ formatDate(disc.start_date) }} - {{ formatDate(disc.end_date) }}
                                </span>
                                <span v-else>Always Valid</span>
                            </td>
                            <td class="p-4 text-center font-mono text-xs font-bold text-slate-600 dark:text-slate-400">
                                {{ disc.used_count }} / {{ disc.max_uses }}
                            </td>
                            <td class="p-4 text-center">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                    disc.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ disc.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="authStore.hasPermission('fee_collection.edit')"
                                        @click="openModal(disc)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Waiver"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('fee_collection.delete')"
                                        @click="handleDelete(disc)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Revoke Waiver"
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

        <!-- Form Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md max-h-[90vh] rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Waiver' : 'Assign Waiver' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="p-6 space-y-4 overflow-y-auto">
                    <!-- Student Selector -->
                    <div class="space-y-1 relative">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Select Student</label>
                        
                        <!-- Selected Student Trigger Button -->
                        <div 
                            v-if="!editingId"
                            @click="toggleStudentDropdown"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 cursor-pointer flex justify-between items-center transition-all focus-within:ring-2 focus-within:ring-indigo-500"
                        >
                            <span>{{ selectedStudentLabel || 'Choose Student' }}</span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div 
                            v-else
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-slate-500 cursor-not-allowed flex justify-between items-center"
                        >
                            <span>{{ selectedStudentLabel }}</span>
                        </div>

                        <!-- Fullscreen Backdrop to close panel on outside click -->
                        <div v-if="showStudentDropdown" class="fixed inset-0 z-40" @click="showStudentDropdown = false"></div>

                        <!-- Dropdown Panel -->
                        <div 
                            v-if="showStudentDropdown && !editingId"
                            class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl overflow-hidden flex flex-col max-h-60"
                        >
                            <!-- Search Field inside Dropdown -->
                            <div class="p-2 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                <input 
                                    v-model="studentSearchQuery"
                                    type="text" 
                                    placeholder="Type to search student name or admission #..." 
                                    class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                                    @click.stop
                                />
                            </div>

                            <!-- Filtered Options List -->
                            <div class="overflow-y-auto flex-1 divide-y divide-slate-100 dark:divide-slate-800/60">
                                <div 
                                    v-if="filteredStudentList.length === 0"
                                    class="p-4 text-xs text-center text-slate-400"
                                >
                                    No students match search.
                                </div>
                                <template v-else>
                                    <div 
                                        v-for="stu in displayedStudents" 
                                        :key="stu.id"
                                        @click="selectStudent(stu)"
                                        class="px-4 py-2 text-xs hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600 cursor-pointer flex justify-between items-center text-slate-700 dark:text-slate-300 transition-colors"
                                        :class="form.student_id === stu.id ? 'bg-indigo-500/5 text-indigo-600 dark:text-indigo-400 font-bold' : ''"
                                    >
                                        <span>{{ stu.first_name }} {{ stu.last_name }} <span class="opacity-60">({{ stu.class_name }} - {{ stu.section_name }})</span></span>
                                        <span class="text-[10px] opacity-75 font-mono">Adm: {{ stu.admission_no }}</span>
                                    </div>
                                    <div 
                                        v-if="filteredStudentList.length > 100" 
                                        class="p-2.5 bg-slate-50 dark:bg-slate-950 text-center text-[10px] text-slate-400 dark:text-slate-500 font-semibold sticky bottom-0 border-t border-slate-100 dark:border-slate-800"
                                    >
                                        Showing first 100 of {{ filteredStudentList.length }} students. Refine search.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Type & Value -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Type</label>
                            <select 
                                v-model="form.discount_type" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none"
                            >
                                <option value="fixed">Fixed Dues Waiver (₹)</option>
                                <option value="percentage">Percentage Waiver (%)</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Waiver Value</label>
                            <input 
                                v-model.number="form.discount_value" 
                                type="number" 
                                step="any"
                                required 
                                min="0"
                                placeholder="e.g. 2000 or 10"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Validity dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Start Date</label>
                            <input 
                                v-model="form.start_date" 
                                v-datepicker
                                type="text" 
                                placeholder="YYYY-MM-DD"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">End Date</label>
                            <input 
                                v-model="form.end_date" 
                                v-datepicker
                                type="text" 
                                placeholder="YYYY-MM-DD"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1 col-span-2">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Reason / Waiver Reason</label>
                            <input 
                                v-model="form.reason" 
                                type="text" 
                                placeholder="e.g. Sibling Discount, Sports Scholarship"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Usage Limit (Times)</label>
                            <input 
                                v-model.number="form.max_uses" 
                                type="number" 
                                required 
                                min="1"
                                placeholder="e.g. 1 or 2"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                            <select 
                                v-model="form.status" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-850 dark:text-slate-100 focus:outline-none"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="errors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                        {{ errors }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1"
                        >
                            <span v-if="saving">Saving...</span>
                            <span v-else>Save Waiver</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'DiscountsIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();

        const discounts = ref([]);
        const studentList = ref([]);
        const loading = ref(true);
        const modalOpen = ref(false);
        const editingId = ref(null);
        const saving = ref(false);
        const errors = ref('');
        
        const search = ref('');
        const filters = ref({
            status: ''
        });

        // Searchable student selection refs
        const studentSearchQuery = ref('');
        const showStudentDropdown = ref(false);
        const selectedStudentLabel = ref('');

        const filteredStudentList = computed(() => {
            if (!studentSearchQuery.value) return studentList.value;
            const query = studentSearchQuery.value.toLowerCase();
            return studentList.value.filter(stu => {
                const fullName = `${stu.first_name || ''} ${stu.last_name || ''}`.toLowerCase();
                const adm = stu.admission_no ? stu.admission_no.toLowerCase() : '';
                return fullName.includes(query) || adm.includes(query);
            });
        });

        const displayedStudents = computed(() => {
            return filteredStudentList.value.slice(0, 100);
        });

        const toggleStudentDropdown = () => {
            showStudentDropdown.value = !showStudentDropdown.value;
        };

        const selectStudent = (stu) => {
            form.value.student_id = stu.id;
            selectedStudentLabel.value = `${stu.first_name} ${stu.last_name} (Adm: ${stu.admission_no}${stu.class_name ? ' - ' + stu.class_name + ' ' + stu.section_name : ''})`;
            showStudentDropdown.value = false;
            studentSearchQuery.value = '';
        };

        const form = ref({
            student_id: '',
            discount_type: 'fixed',
            discount_value: 0,
            reason: '',
            start_date: '',
            end_date: '',
            status: 'active',
            max_uses: 1
        });

        const filteredDiscounts = computed(() => {
            if (!search.value) return discounts.value;
            const term = search.value.toLowerCase();
            return discounts.value.filter(d => {
                const sName = d.student ? (d.student.first_name + ' ' + d.student.last_name).toLowerCase() : '';
                const sAdm = d.student ? d.student.admission_no.toLowerCase() : '';
                return sName.includes(term) || sAdm.includes(term);
            });
        });

        const fetchStudents = async () => {
            try {
                // Fetch active students list for dropdown selection (high limit to handle 1000s of students)
                const response = await window.axios.get('/api/students', { params: { per_page: 3000 } });
                studentList.value = response.data.data;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchDiscounts = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/fee-discounts', {
                    params: filters.value
                });
                discounts.value = response.data.discounts;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load discounts.');
            } finally {
                loading.value = false;
            }
        };

        const openModal = (disc = null) => {
            errors.value = '';
            studentSearchQuery.value = '';
            showStudentDropdown.value = false;
            if (disc) {
                editingId.value = disc.id;
                form.value = {
                    student_id: disc.student_id,
                    discount_type: disc.discount_type,
                    discount_value: Number(disc.discount_value),
                    reason: disc.reason || '',
                    start_date: disc.start_date ? disc.start_date.substring(0, 10) : '',
                    end_date: disc.end_date ? disc.end_date.substring(0, 10) : '',
                    status: disc.status,
                    max_uses: disc.max_uses || 1
                };
                const stu = studentList.value.find(s => s.id === disc.student_id);
                if (stu) {
                    selectedStudentLabel.value = `${stu.first_name} ${stu.last_name} (Adm: ${stu.admission_no}${stu.class_name ? ' - ' + stu.class_name + ' ' + stu.section_name : ''})`;
                } else if (disc.student) {
                    selectedStudentLabel.value = `${disc.student.first_name} ${disc.student.last_name} (Adm: ${disc.student.admission_no})`;
                } else {
                    selectedStudentLabel.value = 'Unknown Student';
                }
            } else {
                editingId.value = null;
                form.value = {
                    student_id: '',
                    discount_type: 'fixed',
                    discount_value: 0,
                    reason: '',
                    start_date: '',
                    end_date: '',
                    status: 'active',
                    max_uses: 1
                };
                selectedStudentLabel.value = '';
            }
            modalOpen.value = true;
        };

        const closeModal = () => {
            modalOpen.value = false;
        };

        const saveForm = async () => {
            saving.value = true;
            errors.value = '';
            try {
                if (editingId.value) {
                    await window.axios.put(`/api/fee-discounts/${editingId.value}`, form.value);
                    toastStore.success('Fee waiver updated successfully.');
                } else {
                    await window.axios.post('/api/fee-discounts', form.value);
                    toastStore.success('Fee waiver assigned successfully.');
                }
                closeModal();
                fetchDiscounts();
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Failed to save form.';
            } finally {
                saving.value = false;
            }
        };

        const handleDelete = async (disc) => {
            const confirmed = await confirmStore.show({
                title: 'Revoke Fee Waiver',
                message: `Are you sure you want to delete this discount waiver? The student's fee outstanding will increase accordingly.`,
                type: 'danger',
                confirmText: 'Delete Waiver',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/fee-discounts/${disc.id}`);
                    toastStore.success('Fee waiver deleted successfully.');
                    fetchDiscounts();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to revoke waiver.');
                }
            }
        };

        const numberFormat = (val) => {
            return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        };

        onMounted(() => {
            fetchDiscounts();
            fetchStudents();
        });

        return {
            authStore,
            studentList,
            loading,
            modalOpen,
            editingId,
            saving,
            errors,
            search,
            filters,
            form,
            filteredDiscounts,
            fetchDiscounts,
            openModal,
            closeModal,
            saveForm,
            handleDelete,
            numberFormat,
            formatDate,
            studentSearchQuery,
            showStudentDropdown,
            selectedStudentLabel,
            filteredStudentList,
            displayedStudents,
            toggleStudentDropdown,
            selectStudent
        };
    }
}
</script>
