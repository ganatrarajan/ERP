<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">Landing Page & Contact Settings</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Manage global contact phone, WhatsApp number, support email, and founding offer banner text displayed across public pages.</p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 max-w-3xl">
            <!-- Loading State -->
            <div v-if="loading" class="py-12 flex flex-col items-center justify-center space-y-3">
                <div class="w-8 h-8 rounded-full border-2 border-indigo-600 border-t-transparent animate-spin"></div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Loading settings...</p>
            </div>

            <!-- Form Content -->
            <form v-else @submit.prevent="saveSettings" class="space-y-6">
                <!-- WhatsApp Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        WhatsApp Number (with country code, no + or spaces)
                    </label>
                    <div class="relative">
                        <input 
                            v-model="form.whatsapp_number"
                            type="text" 
                            placeholder="e.g. 919999999999" 
                            class="w-full pl-9 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <span class="absolute left-3 top-3 text-slate-400 text-sm">💬</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Used for direct WhatsApp chat links and demo booking WhatsApp redirects (e.g. 919876543210).</p>
                </div>

                <!-- Contact Phone -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Display Contact Phone Number
                    </label>
                    <div class="relative">
                        <input 
                            v-model="form.contact_phone"
                            type="text" 
                            placeholder="e.g. +91 99999 99999" 
                            class="w-full pl-9 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <span class="absolute left-3 top-3 text-slate-400 text-sm">📞</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Formatted phone number shown on the Contact page.</p>
                </div>

                <!-- Support Email -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Support Email Address
                    </label>
                    <div class="relative">
                        <input 
                            v-model="form.contact_email"
                            type="email" 
                            placeholder="e.g. support@eduvorax.com" 
                            class="w-full pl-9 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <span class="absolute left-3 top-3 text-slate-400 text-sm">✉️</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Email address displayed on public pages for school inquiries.</p>
                </div>

                <!-- Founding Offer Banner Text -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Founding School Program Offer Text
                    </label>
                    <textarea 
                        v-model="form.founding_offer_text"
                        rows="3"
                        placeholder="e.g. Get EduvoraX school management software free for your first year as part of our founding-school program."
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    ></textarea>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Text displayed in the top announcement bar and founding offer banner across public pages.</p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button 
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider shadow-md transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2"
                    >
                        <span v-if="saving" class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <span>{{ saving ? 'Saving Changes...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'SystemSettings',
    setup() {
        const toastStore = useToastStore();
        const loading = ref(true);
        const saving = ref(false);
        const form = ref({
            whatsapp_number: '',
            contact_phone: '',
            contact_email: '',
            founding_offer_text: ''
        });

        const fetchSettings = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/super-admin/system-settings');
                form.value = response.data.settings;
            } catch (error) {
                console.error(error);
                toastStore.error('Failed to load system settings.');
            } finally {
                loading.value = false;
            }
        };

        const saveSettings = async () => {
            saving.value = true;
            try {
                const response = await window.axios.post('/api/super-admin/system-settings', form.value);
                toastStore.success(response.data.message || 'Settings updated successfully!');
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to save settings.');
            } finally {
                saving.value = false;
            }
        };

        onMounted(() => {
            fetchSettings();
        });

        return {
            loading,
            saving,
            form,
            saveSettings
        };
    }
}
</script>
