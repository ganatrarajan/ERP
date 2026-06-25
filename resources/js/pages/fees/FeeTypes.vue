<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Fee Types</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Define classification categories for school fees, such as Tuition, Admission, and Library fees.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('fee_type.create')"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Fee Type
            </button>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input 
                    v-model="search" 
                    @input="handleSearch"
                    type="text" 
                    placeholder="Search by name or code..." 
                    class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                />
            </div>
            <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                <select 
                    v-model="statusFilter" 
                    @change="fetchFeeTypes"
                    class="px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active Only</option>
                </select>
            </div>
        </div>

        <!-- Table Listing -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div v-if="loading" class="p-6 space-y-4 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-12 bg-slate-200 dark:bg-slate-800/50 rounded-xl"></div>
            </div>

            <div v-else-if="feeTypes.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                No fee types defined yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Name</th>
                            <th class="p-4">Code</th>
                            <th class="p-4">Description</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="type in feeTypes" :key="type.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ type.name }}
                            </td>
                            <td class="p-4 font-mono text-xs">
                                {{ type.code }}
                            </td>
                            <td class="p-4 max-w-xs truncate text-slate-500 dark:text-slate-400">
                                {{ type.description || 'No description' }}
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold',
                                    type.is_optional ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20'
                                ]">
                                    {{ type.is_optional ? 'Optional' : 'Mandatory' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <button 
                                    v-if="authStore.hasPermission('fee_type.edit')"
                                    @click="toggleStatus(type)"
                                    class="inline-flex items-center"
                                >
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize cursor-pointer transition-colors',
                                        type.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                    ]">
                                        {{ type.status }}
                                    </span>
                                </button>
                                <span v-else :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                    type.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ type.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        v-if="authStore.hasPermission('fee_type.edit')"
                                        @click="openModal(type)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Fee Type"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <button 
                                        v-if="authStore.hasPermission('fee_type.delete')"
                                        @click="handleDelete(type)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Fee Type"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex items-center justify-between">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} fee types
                </div>
                <div class="flex items-center gap-1.5">
                    <button 
                        @click="changePage(pagination.current_page - 1)" 
                        :disabled="pagination.current_page <= 1"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Previous
                    </button>
                    <button 
                        @click="changePage(pagination.current_page + 1)" 
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all duration-300 animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Fee Type' : 'Create Fee Type' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Name</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            required 
                            placeholder="e.g. Tuition Fee"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Code</label>
                        <input 
                            v-model="form.code" 
                            type="text" 
                            required 
                            placeholder="e.g. TUITION"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all uppercase"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="3"
                            placeholder="Briefly describe the purpose of this fee type..."
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Type</label>
                            <select 
                                v-model="form.is_optional" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
                            >
                                <option :value="false">Mandatory</option>
                                <option :value="true">Optional</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                            <select 
                                v-model="form.status" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all"
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
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useConfirmStore } from '../../stores/confirm';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'FeeTypesIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();
        const feeTypes = ref([]);
        const loading = ref(true);
        const modalOpen = ref(false);
        const editingId = ref(null);
        const saving = ref(false);
        const errors = ref('');

        const search = ref('');
        const statusFilter = ref('');
        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0
        });

        const form = ref({
            name: '',
            code: '',
            description: '',
            is_optional: false,
            status: 'active'
        });

        let searchTimeout = null;

        const fetchFeeTypes = async (page = 1) => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/fee-types', {
                    params: {
                        page,
                        search: search.value,
                        status: statusFilter.value
                    }
                });
                feeTypes.value = response.data.data;
                pagination.value = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    from: response.data.from,
                    to: response.data.to,
                    total: response.data.total
                };
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load fee types.');
            } finally {
                loading.value = false;
            }
        };

        const handleSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchFeeTypes(1);
            }, 300);
        };

        const changePage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                fetchFeeTypes(page);
            }
        };

        const openModal = (type = null) => {
            errors.value = '';
            if (type) {
                editingId.value = type.id;
                form.value = {
                    name: type.name,
                    code: type.code,
                    description: type.description || '',
                    is_optional: !!type.is_optional,
                    status: type.status
                };
            } else {
                editingId.value = null;
                form.value = {
                    name: '',
                    code: '',
                    description: '',
                    is_optional: false,
                    status: 'active'
                };
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
                const payload = {
                    ...form.value,
                    code: form.value.code.toUpperCase()
                };

                if (editingId.value) {
                    await window.axios.put(`/api/fee-types/${editingId.value}`, payload);
                    toastStore.success('Fee type updated successfully.');
                } else {
                    await window.axios.post('/api/fee-types', payload);
                    toastStore.success('Fee type created successfully.');
                }
                closeModal();
                fetchFeeTypes(pagination.value.current_page);
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Failed to save form.';
            } finally {
                saving.value = false;
            }
        };

        const toggleStatus = async (type) => {
            try {
                const response = await window.axios.patch(`/api/fee-types/${type.id}/toggle-status`);
                toastStore.success(response.data.message);
                fetchFeeTypes(pagination.value.current_page);
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to toggle status.');
            }
        };

        const handleDelete = async (type) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Fee Type',
                message: `Are you sure you want to delete "${type.name}"? This could affect existing structures using this type.`,
                type: 'danger',
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/fee-types/${type.id}`);
                    toastStore.success('Fee type deleted successfully.');
                    fetchFeeTypes(1);
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete fee type.');
                }
            }
        };

        onMounted(() => {
            fetchFeeTypes();
        });

        return {
            authStore,
            feeTypes,
            loading,
            modalOpen,
            editingId,
            saving,
            errors,
            search,
            statusFilter,
            pagination,
            form,
            handleSearch,
            changePage,
            openModal,
            closeModal,
            saveForm,
            toggleStatus,
            handleDelete
        };
    }
}
</script>
