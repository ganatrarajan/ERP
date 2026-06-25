<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">Fee Structures</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Manage structure templates specifying multiple fee types and amounts for academic classes.</p>
            </div>
            <button 
                v-if="authStore.hasPermission('fee_structure.create') && isCurrentYear"
                @click="openModal()"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/10 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Structure
            </button>
        </div>

        <!-- Locked Year Warning Banner -->
        <div v-if="!isCurrentYear && !loading" class="bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-400 p-4 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <span class="font-bold">Historical Session View Only:</span> You are viewing a locked academic session. Creating, editing, or deleting fee structures is disabled.
            </div>
        </div>

        <!-- Filters Block -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Academic Year</label>
                <select 
                    v-model="filters.academic_year_id" 
                    @change="handleAcademicYearChange"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option v-for="year in academicYears" :key="year.id" :value="year.id">
                        {{ year.title }}
                    </option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Class</label>
                <select 
                    v-model="filters.class_id" 
                    @change="fetchFeeStructures"
                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">All Classes</option>
                    <option v-for="c in classes" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>

            <div class="space-y-1 md:col-span-2">
                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="filters.search" 
                        @input="handleSearch"
                        type="text" 
                        placeholder="Search structures..." 
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

            <div v-else-if="structures.length === 0" class="p-12 text-center text-slate-500">
                <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                No fee structures configured for the selected filters.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-slate-900/60">
                            <th class="p-4 pl-6">Name</th>
                            <th class="p-4">Class</th>
                            <th class="p-4">Academic Session</th>
                            <th class="p-4 text-right">Items Count</th>
                            <th class="p-4 text-right">Total Amount</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 text-sm text-slate-700 dark:text-slate-300">
                        <tr v-for="struct in structures" :key="struct.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-semibold text-slate-800 dark:text-white">
                                {{ struct.name }}
                            </td>
                            <td class="p-4">
                                {{ struct.class ? struct.class.name : 'N/A' }}
                            </td>
                            <td class="p-4">
                                {{ struct.academic_year ? struct.academic_year.title : 'N/A' }}
                            </td>
                            <td class="p-4 text-right font-mono text-xs">
                                {{ struct.items.length }}
                            </td>
                            <td class="p-4 text-right font-black text-indigo-600 dark:text-indigo-400">
                                ₹{{ numberFormat(struct.total_amount) }}
                            </td>
                            <td class="p-4 text-center">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold capitalize',
                                    struct.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/25'
                                ]">
                                    {{ struct.status }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Clone -->
                                    <button 
                                        v-if="authStore.hasPermission('fee_structure.create')"
                                        @click="openCloneModal(struct)"
                                        class="p-1.5 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-lg transition-colors"
                                        title="Clone Structure"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                    </button>

                                    <!-- Edit -->
                                    <button 
                                        v-if="authStore.hasPermission('fee_structure.edit') && isCurrentYear"
                                        @click="openModal(struct)"
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700/60 transition-colors"
                                        title="Edit Structure"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        v-if="authStore.hasPermission('fee_structure.delete') && isCurrentYear"
                                        @click="handleDelete(struct)"
                                        class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg transition-colors"
                                        title="Delete Structure"
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

        <!-- Form Modal (Large for Dynamic Fee Rows) -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">
                        {{ editingId ? 'Edit Fee Structure' : 'Create Fee Structure' }}
                    </h3>
                    <button @click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveForm" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Academic Session</label>
                            <select 
                                v-model="form.academic_year_id" 
                                :disabled="!!editingId"
                                required
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                            >
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                                    {{ year.title }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Class</label>
                            <select 
                                v-model="form.class_id" 
                                :disabled="!!editingId"
                                required
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                            >
                                <option value="" disabled>Select Class</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Structure Name</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                required 
                                placeholder="e.g. Standard 10th Fee Structure"
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                            <select 
                                v-model="form.status" 
                                class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="2"
                            placeholder="Optional description of this fee structure template..."
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <!-- Items Table -->
                    <div class="space-y-3 pt-2">
                        <div class="flex justify-between items-center">
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide">Fee Breakdown Items</h4>
                            <button 
                                type="button" 
                                @click="addItemRow"
                                class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-indigo-400 rounded-lg transition-all flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add Fee Row
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <div v-for="(row, idx) in form.items" :key="row.id" class="flex gap-3 items-center">
                                <div class="flex-1">
                                    <select 
                                        v-model="row.fee_type_id" 
                                        required
                                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="" disabled>Select Fee Type</option>
                                        <option v-for="t in activeFeeTypes" :key="t.id" :value="t.id">
                                            {{ t.name }} {{ t.is_optional ? '(Optional)' : '(Mandatory)' }}
                                        </option>
                                    </select>
                                </div>
                                <div class="w-40 relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">₹</span>
                                    <input 
                                        v-model.number="row.amount" 
                                        type="number" 
                                        required
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full pl-7 pr-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <button 
                                    type="button" 
                                    @click="removeItemRow(idx)"
                                    class="p-2 bg-rose-500/10 hover:bg-rose-500/25 text-rose-600 rounded-lg border border-rose-500/20 transition-all active:scale-95"
                                    title="Remove Row"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Running Total Display -->
                        <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-150 dark:border-slate-800/80">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Structure Amount:</span>
                            <span class="text-base font-black text-indigo-600 dark:text-indigo-400">₹{{ numberFormat(totalCalculatedAmount) }}</span>
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

        <!-- Clone Modal -->
        <div v-if="cloneModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden animate-in fade-in zoom-in-95">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h3 class="font-extrabold text-slate-800 dark:text-white text-base">Clone Fee Structure</h3>
                    <button @click="closeCloneModal" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="saveCloneForm" class="p-6 space-y-4">
                    <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 text-xs text-indigo-600 dark:text-indigo-400 border border-indigo-150 dark:border-indigo-500/20 rounded-xl leading-relaxed">
                        Cloning will copy all breakdown items and installments of <strong>"{{ originalStructureName }}"</strong> into a new structure for the selected destination.
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Destination Session</label>
                        <select 
                            v-model="cloneForm.academic_year_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                        >
                            <option v-for="year in currentAcademicYearsOnly" :key="year.id" :value="year.id">
                                {{ year.title }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Destination Class</label>
                        <select 
                            v-model="cloneForm.class_id" 
                            required
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                        >
                            <option value="" disabled>Select Class</option>
                            <option v-for="c in classes" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Cloned Structure Name</label>
                        <input 
                            v-model="cloneForm.name" 
                            type="text" 
                            required 
                            placeholder="e.g. 10th Fee Structure - Cloned"
                            class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100"
                        />
                    </div>

                    <div v-if="cloneErrors" class="text-xs text-rose-500 bg-rose-500/10 p-3 rounded-lg border border-rose-500/20">
                        {{ cloneErrors }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="closeCloneModal" 
                            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingClone"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl active:scale-95 disabled:scale-100 disabled:opacity-50 transition-all flex items-center gap-1"
                        >
                            <span v-if="savingClone">Cloning...</span>
                            <span v-else>Clone Structure</span>
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
    name: 'FeeStructuresIndex',
    setup() {
        const authStore = useAuthStore();
        const confirmStore = useConfirmStore();
        const toastStore = useToastStore();

        const structures = ref([]);
        const academicYears = ref([]);
        const classes = ref([]);
        const activeFeeTypes = ref([]);
        const loading = ref(true);
        const modalOpen = ref(false);
        const editingId = ref(null);
        const saving = ref(false);
        const errors = ref('');

        const cloneModalOpen = ref(false);
        const originalStructureName = ref('');
        const originalStructureId = ref(null);
        const savingClone = ref(false);
        const cloneErrors = ref('');

        const filters = ref({
            academic_year_id: '',
            class_id: '',
            search: ''
        });

        const form = ref({
            academic_year_id: '',
            class_id: '',
            name: '',
            description: '',
            status: 'active',
            items: []
        });

        const cloneForm = ref({
            academic_year_id: '',
            class_id: '',
            name: ''
        });

        let searchTimeout = null;

        const totalCalculatedAmount = computed(() => {
            return form.value.items.reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
        });

        const isCurrentYear = computed(() => {
            const selected = academicYears.value.find(y => y.id === filters.value.academic_year_id);
            return selected ? !!selected.is_current : false;
        });

        const currentAcademicYearsOnly = computed(() => {
            return academicYears.value.filter(y => y.is_current);
        });

        const fetchClasses = async () => {
            try {
                const params = {};
                if (filters.value.academic_year_id) {
                    params.academic_year_id = filters.value.academic_year_id;
                }
                const cRes = await window.axios.get('/api/classes', { params });
                classes.value = cRes.data.classes || cRes.data;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load classes.');
            }
        };

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
                await fetchClasses();

                // Fetch Active Fee Types (both optional and mandatory)
                const tRes = await window.axios.get('/api/fee-types', { params: { status: 'active', per_page: 100 } });
                activeFeeTypes.value = tRes.data.data;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load initial filter data.');
            }
        };

        const handleAcademicYearChange = async () => {
            filters.value.class_id = '';
            structures.value = [];
            await fetchClasses();
            fetchFeeStructures();
        };

        const fetchFeeStructures = async () => {
            if (!filters.value.academic_year_id) return;
            loading.value = true;
            try {
                const response = await window.axios.get('/api/fee-structures', {
                    params: filters.value
                });
                structures.value = response.data.fee_structures;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load fee structures.');
            } finally {
                loading.value = false;
            }
        };

        const handleSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchFeeStructures();
            }, 300);
        };

        const addItemRow = () => {
            form.value.items.push({
                id: `item_${Date.now()}_${form.value.items.length}`,
                fee_type_id: '',
                amount: 0
            });
        };

        const removeItemRow = (idx) => {
            form.value.items.splice(idx, 1);
        };

        const openModal = (struct = null) => {
            errors.value = '';
            if (struct) {
                editingId.value = struct.id;
                form.value = {
                    academic_year_id: struct.academic_year_id,
                    class_id: struct.class_id,
                    name: struct.name,
                    description: struct.description || '',
                    status: struct.status,
                    items: struct.items.map((i, index) => ({
                        id: i.id || `item_${Date.now()}_${index}`,
                        fee_type_id: i.fee_type_id,
                        amount: Number(i.amount)
                    }))
                };
            } else {
                editingId.value = null;
                form.value = {
                    academic_year_id: filters.value.academic_year_id,
                    class_id: filters.value.class_id || '',
                    name: '',
                    description: '',
                    status: 'active',
                    items: [{ id: `item_${Date.now()}_0`, fee_type_id: '', amount: 0 }]
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
                if (editingId.value) {
                    await window.axios.put(`/api/fee-structures/${editingId.value}`, form.value);
                    toastStore.success('Fee structure updated successfully.');
                } else {
                    await window.axios.post('/api/fee-structures', form.value);
                    toastStore.success('Fee structure created successfully.');
                }
                closeModal();
                fetchFeeStructures();
            } catch (error) {
                console.error(error);
                errors.value = error.response?.data?.message || 'Failed to save form.';
            } finally {
                saving.value = false;
            }
        };

        const openCloneModal = (struct) => {
            cloneErrors.value = '';
            originalStructureId.value = struct.id;
            originalStructureName.value = struct.name;
            const activeYear = academicYears.value.find(y => y.is_current);
            cloneForm.value = {
                academic_year_id: activeYear ? activeYear.id : struct.academic_year_id,
                class_id: '',
                name: struct.name + ' - Copy'
            };
            cloneModalOpen.value = true;
        };

        const closeCloneModal = () => {
            cloneModalOpen.value = false;
        };

        const saveCloneForm = async () => {
            savingClone.value = true;
            cloneErrors.value = '';
            try {
                await window.axios.post(`/api/fee-structures/${originalStructureId.value}/clone`, cloneForm.value);
                toastStore.success('Fee structure cloned successfully.');
                closeCloneModal();
                fetchFeeStructures();
            } catch (error) {
                console.error(error);
                cloneErrors.value = error.response?.data?.message || 'Failed to clone structure.';
            } finally {
                savingClone.value = false;
            }
        };

        const handleDelete = async (struct) => {
            const confirmed = await confirmStore.show({
                title: 'Delete Fee Structure',
                message: `Are you sure you want to delete "${struct.name}"? This will also remove any installments associated with it.`,
                type: 'danger',
                confirmText: 'Delete Template',
                cancelText: 'Cancel'
            });

            if (confirmed) {
                try {
                    await window.axios.delete(`/api/fee-structures/${struct.id}`);
                    toastStore.success('Fee structure deleted successfully.');
                    fetchFeeStructures();
                } catch (error) {
                    console.error(error);
                    toastStore.error(error.response?.data?.message || 'Failed to delete structure.');
                }
            }
        };

        const numberFormat = (val) => {
            return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        onMounted(async () => {
            await fetchFiltersData();
            await fetchFeeStructures();
        });

        return {
            authStore,
            structures,
            academicYears,
            classes,
            activeFeeTypes,
            loading,
            modalOpen,
            editingId,
            saving,
            errors,
            cloneModalOpen,
            originalStructureName,
            savingClone,
            cloneErrors,
            filters,
            form,
            cloneForm,
            totalCalculatedAmount,
            isCurrentYear,
            currentAcademicYearsOnly,
            handleAcademicYearChange,
            fetchFeeStructures,
            handleSearch,
            addItemRow,
            removeItemRow,
            openModal,
            closeModal,
            saveForm,
            openCloneModal,
            closeCloneModal,
            saveCloneForm,
            handleDelete,
            numberFormat
        };
    }
}
</script>
