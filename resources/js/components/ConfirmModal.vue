<template>
    <transition name="fade">
        <div 
            v-if="confirmStore.isOpen" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-sm"
            @click.self="confirmStore.cancel"
        >
            <transition name="scale" appear>
                <div 
                    class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-6 overflow-hidden flex flex-col gap-5 text-left transition-all"
                >
                    <!-- Header with Icon -->
                    <div class="flex items-start gap-4">
                        <!-- Danger Icon -->
                        <div 
                            v-if="confirmStore.type === 'danger'"
                            class="p-3 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl flex-shrink-0"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        
                        <!-- Warning Icon -->
                        <div 
                            v-else-if="confirmStore.type === 'warning'"
                            class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl flex-shrink-0"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>

                        <!-- Info Icon -->
                        <div 
                            v-else
                            class="p-3 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl flex-shrink-0"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>

                        <!-- Title & Message -->
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-850 dark:text-white leading-6">
                                {{ confirmStore.title }}
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ confirmStore.message }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-850 pt-4">
                        <button 
                            v-if="confirmStore.cancelText"
                            type="button"
                            @click="confirmStore.cancel"
                            class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-350 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition-all"
                        >
                            {{ confirmStore.cancelText }}
                        </button>
                        
                        <button 
                            type="button"
                            @click="confirmStore.confirm"
                            :class="[
                                'px-4 py-2 text-xs font-bold text-white rounded-xl transition-all active:scale-95 shadow-md',
                                confirmStore.type === 'danger' ? 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/10' : '',
                                confirmStore.type === 'warning' ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-600/10' : '',
                                confirmStore.type === 'info' ? 'bg-indigo-600 hover:bg-indigo-500 shadow-indigo-600/10' : '',
                            ]"
                        >
                            {{ confirmStore.confirmText }}
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </transition>
</template>

<script>
import { useConfirmStore } from '../stores/confirm';

export default {
    name: 'ConfirmModal',
    setup() {
        const confirmStore = useConfirmStore();
        return {
            confirmStore
        };
    }
}
</script>

<style scoped>
/* Fade transition for backdrop */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Scale transition for modal card */
.scale-enter-active,
.scale-leave-active {
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
}
.scale-enter-from,
.scale-leave-to {
    transform: scale(0.95);
    opacity: 0;
}
</style>
