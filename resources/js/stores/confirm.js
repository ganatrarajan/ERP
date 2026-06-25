import { defineStore } from 'pinia';

export const useConfirmStore = defineStore('confirm', {
    state: () => ({
        isOpen: false,
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
        type: 'danger', // 'danger' | 'warning' | 'info'
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        resolve: null
    }),

    actions: {
        show(options = {}) {
            this.title = options.title || 'Confirm Action';
            this.message = options.message || 'Are you sure you want to proceed?';
            this.type = options.type || 'danger';
            this.confirmText = options.confirmText || 'Confirm';
            this.cancelText = options.cancelText || 'Cancel';
            this.isOpen = true;

            return new Promise((resolve) => {
                this.resolve = resolve;
            });
        },

        confirm() {
            if (this.resolve) {
                this.resolve(true);
            }
            this.close();
        },

        cancel() {
            if (this.resolve) {
                this.resolve(false);
            }
            this.close();
        },

        close() {
            this.isOpen = false;
            this.resolve = null;
        }
    }
});
