<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Installments</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Define installment schedules, amounts, and dead-lines for configured fee structures.</p>
            </div>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loadingStructures" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Configuring or updating installments is disabled.
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Side: Select Structure -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-800 dark:text-white text-sm uppercase tracking-wide">Filter Structure</h3>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Academic Year</label>
                        <select 
                            v-model="filters.academic_year_id" 
                            @change="fetchStructures"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                {{ year.title }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Class</label>
                        <select 
                            v-model="filters.class_id" 
                            @change="fetchStructures"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">All Classes</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Structures List Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <h4 class="font-bold text-slate-800 dark:text-white text-xs uppercase tracking-wider">Fee Structures</h4>
                    </div>

                    <div v-if="loadingStructures" class="p-6 space-y-3 animate-pulse">
                        <div v-for="i in 3" :key="i" class="h-10 bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
                    </div>

                    <div v-else-if="structures.length === 0" class="p-8 text-center text-slate-400 text-xs">
                        No structures found.
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <button 
                            v-for="s in structures" 
                            :key="s.id"
                            @click="selectStructure(s)"
                            class="w-full p-4 text-left transition-colors flex items-center justify-between group hover:bg-slate-50 dark:hover:bg-slate-800/30"
                            :class="selectedStructure?.id === s.id ? 'bg-indigo-500/5 dark:bg-indigo-500/10 border-r-4 border-indigo-600' : ''"
                        >
                            <div>
                                <div class="font-semibold text-sm text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ s.name }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-wide">
                                    {{ s.class ? s.class.name : 'N/A' }} | ₹{{ numberFormat(s.total_amount) }}
                                </div>
                            </div>
                            <span :class="[
                                'inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold',
                                s.is_validated ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                            ]">
                                {{ s.is_validated ? 'Configured' : 'Needs Config' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Installments Editor -->
            <div class="lg:col-span-2 space-y-4">
                <div v-if="!selectedStructure" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-12 text-center rounded-2xl text-slate-400 shadow-sm flex flex-col justify-center items-center h-full min-h-[300px]">
                    <svg class="w-16 h-16 text-slate-300 dark:text-slate-700 mb-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Select a fee structure from the left panel to configure its installments.
                </div>

                <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="font-extrabold text-slate-800 dark:text-white text-base">Configure Installments: {{ selectedStructure.name }}</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 uppercase font-bold tracking-wider">Total Dues required: ₹{{ numberFormat(selectedStructure.total_amount) }}</p>
                            </div>
                            <button 
                                v-if="isCurrentYear"
                                type="button" 
                                @click="addInstallmentRow"
                                class="px-3 py-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl active:scale-95 transition-all flex items-center gap-1 shadow-md shadow-indigo-600/10"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add Installment
                            </button>
                        </div>

                        <!-- Installments List -->
                        <div class="p-6 space-y-4">
                            <!-- Validation Notice Box -->
                            <div :class="[
                                'p-4 rounded-xl border flex flex-col sm:flex-row justify-between gap-3 text-xs tracking-wide',
                                isSumMatching ? 'bg-emerald-50 dark:bg-emerald-500/5 border-emerald-150 dark:border-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-500/5 border-rose-150 dark:border-rose-500/10 text-rose-700 dark:text-rose-400'
                            ]">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="isSumMatching ? 'bg-emerald-500' : 'bg-rose-500 animate-ping'"></span>
                                    <span>
                                        <strong>Total Installments Sum:</strong> ₹{{ numberFormat(installmentsSum) }} / ₹{{ numberFormat(selectedStructure.total_amount) }}
                                    </span>
                                </div>
                                <div v-if="!isSumMatching" class="font-bold">
                                    Difference: ₹{{ numberFormat(Math.abs(selectedStructure.total_amount - installmentsSum)) }} (Must match)
                                </div>
                                <div v-else class="font-bold flex items-center gap-1">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    Sum Matches! Ready to save.
                                </div>
                            </div>

                            <div v-if="installments.length === 0" class="p-8 text-center text-slate-400 text-xs">
                                No installments defined yet. Click "Add Installment" to define schedules.
                            </div>

                            <div v-else class="space-y-3">
                                <div v-for="(row, idx) in installments" :key="row.id" class="bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-150 dark:border-slate-800/80 grid grid-cols-1 sm:grid-cols-4 gap-3 items-center relative">
                                    <div class="space-y-1 sm:col-span-2">
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Installment Name</label>
                                        <input 
                                            v-model="row.installment_name" 
                                            type="text" 
                                            required 
                                            placeholder="e.g. Installment 1"
                                            :disabled="!isCurrentYear"
                                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-100 focus:outline-none"
                                        />
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Due Date</label>
                                        <input 
                                            v-model="row.due_date" 
                                            v-datepicker
                                            type="text" 
                                            required 
                                            placeholder="YYYY-MM-DD"
                                            :disabled="!isCurrentYear"
                                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-100 focus:outline-none"
                                        />
                                    </div>

                                    <div class="space-y-1 relative">
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Amount</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">₹</span>
                                            <input 
                                                v-model.number="row.amount" 
                                                type="number" 
                                                required 
                                                min="0"
                                                placeholder="0.00"
                                                :disabled="!isCurrentYear"
                                                class="w-full pl-5 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-100 focus:outline-none"
                                            />
                                        </div>
                                    </div>

                                    <!-- Actions & Sort Order -->
                                    <div class="sm:col-span-4 flex justify-between items-center pt-2 border-t border-slate-150 dark:border-slate-900 mt-1">
                                        <div class="flex items-center gap-1 text-slate-400 text-[10px]">
                                            <span>Sort Order:</span>
                                            <input 
                                                v-model.number="row.sort_order" 
                                                type="number" 
                                                :disabled="!isCurrentYear"
                                                class="w-12 px-1.5 py-0.5 border border-slate-200 dark:border-slate-800 rounded bg-white dark:bg-slate-900 text-center text-xs text-slate-700 dark:text-slate-300 focus:outline-none"
                                            />
                                        </div>
                                        <button 
                                            v-if="isCurrentYear"
                                            type="button" 
                                            @click="removeInstallmentRow(idx)"
                                            class="px-2 py-1 text-[10px] bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 border border-rose-500/20 rounded-md transition-all active:scale-95 flex items-center gap-1"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                                    <!-- Actions Footer -->
                    <div v-if="isCurrentYear" class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-3">
                        <button 
                            type="button" 
                            @click="selectStructure(selectedStructure)" 
                            class="px-4 py-2 text-xs font-semibold text-slate-655 hover:bg-slate-100 dark:text-slate-350 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Reset Form
                        </button>
                        <button 
                            @click="saveInstallments"
                            :disabled="saving || !isSumMatching"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95 disabled:scale-100 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/10 transition-all"
                        >
                            <span v-if="saving">Saving...</span>
                            <span v-else>Save Installments</span>
                        </button>
                    </div>      </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'InstallmentsIndex',
    setup() {
        const authStore = useAuthStore();
        const toastStore = useToastStore();

        const structures = ref([]);
        const academicYears = ref([]);
        const classes = ref([]);
        const loadingStructures = ref(true);
        const selectedStructure = ref(null);
        const installments = ref([]);
        const saving = ref(false);

        const filters = ref({
            academic_year_id: '',
            class_id: '',
            search: ''
        });

        const installmentsSum = computed(() => {
            return installments.value.reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
        });

        const isSumMatching = computed(() => {
            if (!selectedStructure.value) return false;
            return Math.abs(Number(selectedStructure.value.total_amount) - installmentsSum.value) < 0.05;
        });

        const isCurrentYear = computed(() => {
            const selected = academicYears.value.find(y => y.id === filters.value.academic_year_id);
            return selected ? !!selected.is_current : false;
        });

        const fetchFiltersData = async () => {
            try {
                // Fetch Academic Years
                const yRes = await window.axios.get('/api/academic-years');
                academicYears.value = yRes.data.academic_years;
                const activeYear = academicYears.value.find(y => y.is_current);
                if (activeYear) {
                    filters.value.academic_year_id = activeYear.id;
                } else if (academicYears.value.length > 0) {
                    filters.value.academic_year_id = academicYears.value[0].id;
                }

                // Fetch Classes
                const cRes = await window.axios.get('/api/classes');
                classes.value = cRes.data.classes;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load initial filters.');
            }
        };

        const fetchStructures = async () => {
            if (!filters.value.academic_year_id) return;
            loadingStructures.value = true;
            try {
                const response = await window.axios.get('/api/fee-structures', {
                    params: filters.value
                });
                structures.value = response.data.fee_structures;
                // If a structure was selected, refresh it
                if (selectedStructure.value) {
                    const fresh = structures.value.find(s => s.id === selectedStructure.value.id);
                    if (fresh) selectStructure(fresh);
                    else selectedStructure.value = null;
                }
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load structures.');
            } finally {
                loadingStructures.value = false;
            }
        };

        const selectStructure = (struct) => {
            selectedStructure.value = struct;
            installments.value = struct.installments.map((i, index) => ({
                id: i.id || `inst_${Date.now()}_${index}`,
                installment_name: i.installment_name,
                due_date: i.due_date ? i.due_date.substring(0, 10) : '',
                amount: Number(i.amount),
                sort_order: i.sort_order ?? 0
            }));
        };

        const addInstallmentRow = () => {
            // Determine default sort order
            const nextSort = installments.value.length > 0
                ? Math.max(...installments.value.map(i => i.sort_order)) + 1
                : 1;

            installments.value.push({
                id: `inst_${Date.now()}_${installments.value.length}`,
                installment_name: `Installment ${installments.value.length + 1}`,
                due_date: '',
                amount: 0,
                sort_order: nextSort
            });
        };

        const removeInstallmentRow = (idx) => {
            installments.value.splice(idx, 1);
        };

        const saveInstallments = async () => {
            if (!isSumMatching.value) {
                toastStore.error('Total installment amount must exactly equal fee structure total.');
                return;
            }

            saving.value = true;
            try {
                // Fetch full structure items list to send back (required by update api)
                const sRes = await window.axios.get(`/api/fee-structures/${selectedStructure.value.id}`);
                const fullStruct = sRes.data.fee_structure;

                const payload = {
                    name: fullStruct.name,
                    description: fullStruct.description,
                    status: fullStruct.status,
                    items: fullStruct.items.map(i => ({
                        fee_type_id: i.fee_type_id,
                        amount: Number(i.amount)
                    })),
                    installments: installments.value
                };

                await window.axios.put(`/api/fee-structures/${selectedStructure.value.id}`, payload);
                toastStore.success('Installments saved successfully.');
                await fetchStructures();
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to save installments.');
            } finally {
                saving.value = false;
            }
        };

        const numberFormat = (val) => {
            return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        onMounted(async () => {
            await fetchFiltersData();
            await fetchStructures();
        });

        return {
            authStore,
            structures,
            academicYears,
            classes,
            loadingStructures,
            selectedStructure,
            installments,
            saving,
            filters,
            installmentsSum,
            isSumMatching,
            isCurrentYear,
            fetchStructures,
            selectStructure,
            addInstallmentRow,
            removeInstallmentRow,
            saveInstallments,
            numberFormat
        };
    }
}
</script>
