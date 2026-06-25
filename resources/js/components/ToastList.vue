<template>
    <div class="fixed top-6 right-6 z-[60] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
        <transition-group name="toast-list" tag="div" class="flex flex-col gap-3 w-full">
            <div 
                v-for="toast in toastStore.toasts" 
                :key="toast.id"
                class="w-full pointer-events-auto bg-white/95 dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-xl p-4 flex gap-3.5 items-start justify-between backdrop-blur-md transition-all duration-300"
            >
                <!-- Icon container -->
                <div class="flex items-start gap-3 flex-1">
                    <!-- Success Icon -->
                    <div 
                        v-if="toast.type === 'success'"
                        class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl flex-shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <!-- Error Icon -->
                    <div 
                        v-else-if="toast.type === 'error'"
                        class="p-2 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl flex-shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>

                    <!-- Info Icon -->
                    <div 
                        v-else
                        class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl flex-shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="space-y-0.5 pt-0.5">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-white leading-5">
                            {{ toast.type === 'success' ? 'Success Notification' : toast.type === 'error' ? 'Action Failed' : 'Information' }}
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">
                            {{ toast.message }}
                        </p>
                    </div>
                </div>

                <!-- Dismiss Button -->
                <button 
                    @click="toastStore.remove(toast.id)"
                    class="p-1 hover:bg-slate-100 dark:hover:bg-slate-850 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors flex-shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>

<script>
import { useToastStore } from '../stores/toast';

export default {
    name: 'ToastList',
    setup() {
        const toastStore = useToastStore();
        return {
            toastStore
        };
    }
}
</script>

<style scoped>
/* Toast List Transitions */
.toast-list-enter-from {
    opacity: 0;
    transform: translateX(40px) scale(0.9);
}
.toast-list-enter-to {
    opacity: 1;
    transform: translateX(0) scale(1);
}
.toast-list-leave-from {
    opacity: 1;
    transform: translateX(0) scale(1);
}
.toast-list-leave-to {
    opacity: 0;
    transform: translateX(40px) scale(0.9);
}
.toast-list-leave-active {
    position: absolute;
    width: 100%;
}
</style>
