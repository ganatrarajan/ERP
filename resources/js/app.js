import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { useToastStore } from './stores/toast';

const app = createApp(App);
const pinia = createPinia();

app.directive('datepicker', {
    mounted(el, binding) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (!inputEl) return;

        const config = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            onChange: (selectedDates, dateStr) => {
                inputEl.value = dateStr;
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));
            },
            ...(binding.value || {})
        };
        inputEl._flatpickr = flatpickr(inputEl, config);
    },
    updated(el) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (inputEl && inputEl._flatpickr) {
            inputEl._flatpickr.setDate(inputEl.value, false);
        }
    },
    unmounted(el) {
        const inputEl = el.tagName === 'INPUT' ? el : el.querySelector('input');
        if (inputEl && inputEl._flatpickr) {
            inputEl._flatpickr.destroy();
        }
    }
});

app.use(pinia);

// Bind custom global window.toastr wrapper to Pinia toastStore
const toastStore = useToastStore(pinia);
window.toastr = {
    success(msg) { toastStore.success(msg); },
    error(msg) { toastStore.error(msg); },
    info(msg) { toastStore.info(msg); }
};

app.use(router);

app.mount('#app');
