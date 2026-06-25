import { defineStore } from 'pinia';

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: []
    }),

    actions: {
        add(message, type = 'success', duration = 4000) {
            const id = Math.random().toString(36).substring(2, 9);
            const toast = { id, message, type };
            
            this.toasts.push(toast);

            setTimeout(() => {
                this.remove(id);
            }, duration);
        },

        success(message) {
            this.add(message, 'success');
        },

        error(message) {
            this.add(message, 'error');
        },

        info(message) {
            this.add(message, 'info');
        },

        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }
});
