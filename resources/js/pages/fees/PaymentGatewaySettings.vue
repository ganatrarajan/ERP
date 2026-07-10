<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">Payment Gateway Settings</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Configure online payment gateways to collect fees directly into your school bank account.</p>
        </div>

        <div v-if="moduleDisabled" class="bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 p-4 rounded-xl text-xs font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>Online Payment feature has been disabled for your school by the Super Admin. Please contact system support to activate this module.</span>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Configuration Form -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-6">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-white border-b border-slate-250 dark:border-slate-800 pb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                        Gateway Configurations
                    </h4>

                    <div v-if="loading" class="p-6 flex items-center justify-center">
                        <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <form v-else @submit.prevent="saveSettings" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Gateway Type Selector -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">Gateway Name</label>
                                <select 
                                    v-model="form.gateway_name" 
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option value="Razorpay">Razorpay</option>
                                    <option value="Cashfree" disabled>Cashfree (Coming Soon)</option>
                                    <option value="PhonePe" disabled>PhonePe (Coming Soon)</option>
                                </select>
                            </div>

                            <!-- Mode Selector -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">Mode</label>
                                <select 
                                    v-model="form.mode" 
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option value="test">Test Mode (Sandbox)</option>
                                    <option value="live">Live Mode (Production)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Key ID -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">Key ID</label>
                                <input 
                                    v-model="form.key_id" 
                                    type="text" 
                                    required 
                                    placeholder="rzp_test_..."
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Key Secret -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase flex items-center justify-between">
                                    <span>Key Secret</span>
                                    <span v-if="storedConfigured.key_secret" class="text-[10px] text-emerald-600 dark:text-emerald-450 normal-case font-medium">● Stored & Secured</span>
                                </label>
                                <input 
                                    v-model="form.key_secret" 
                                    type="password" 
                                    :placeholder="storedConfigured.key_secret ? '••••••••••••••••••••••••••••••••' : 'Enter Key Secret'"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Webhook Secret -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase flex items-center justify-between">
                                    <span>Webhook Secret</span>
                                    <span v-if="storedConfigured.webhook_secret" class="text-[10px] text-emerald-600 dark:text-emerald-450 normal-case font-medium">● Stored & Secured</span>
                                </label>
                                <input 
                                    v-model="form.webhook_secret" 
                                    type="password" 
                                    :placeholder="storedConfigured.webhook_secret ? '••••••••••••••••••••••••••••••••' : 'Enter Webhook Secret'"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Currency -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">Currency</label>
                                <select 
                                    v-model="form.currency" 
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option value="INR">INR (₹)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="AED">AED (Dh)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <input 
                                v-model="form.active" 
                                type="checkbox" 
                                id="active"
                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                            />
                            <div>
                                <label for="active" class="text-xs font-bold text-slate-750 dark:text-slate-200 cursor-pointer">Activate Gateway</label>
                                <p class="text-[10px] text-slate-500">Enable this to allow parents to pay fee dues online immediately using this config.</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-150 dark:border-slate-800">
                            <button 
                                type="button" 
                                @click="testConnection"
                                :disabled="testing"
                                class="px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg text-xs font-bold transition-all disabled:opacity-50 active:scale-95 flex items-center gap-1.5"
                            >
                                <div v-if="testing" class="w-3.5 h-3.5 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"></div>
                                <svg v-else class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                {{ testing ? 'Testing Gateway...' : 'Test Connection' }}
                            </button>

                            <button 
                                type="submit" 
                                :disabled="saving"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-750 text-white rounded-lg text-xs font-bold transition-all disabled:opacity-50 active:scale-95 flex items-center gap-1.5 shadow-md shadow-indigo-600/10"
                            >
                                <div v-if="saving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                {{ saving ? 'Saving Changes...' : 'Save Configuration' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Webhook instructions panel -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Guide -->
                <div class="bg-indigo-600 text-white rounded-xl border border-indigo-700 p-6 space-y-4 shadow-lg shadow-indigo-600/10">
                    <h4 class="text-sm font-bold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Integration Guide
                    </h4>
                    <ul class="space-y-3 text-[11px] leading-relaxed text-indigo-100 list-disc pl-3">
                        <li>Each school receives payments directly into its configured merchant account. EduvoraX does not charge commissions.</li>
                        <li>Log in to your <strong>Razorpay Dashboard</strong>, switch to your live/test mode, and retrieve the Key ID and Key Secret from settings.</li>
                        <li>All credential values are encrypted inside the database for your security. Secrets are never exposed.</li>
                        <li>Always use <strong>Test Connection</strong> to check your credentials before activating the gateway.</li>
                    </ul>
                </div>

                <!-- Webhooks config panel -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
                    <h4 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                        Webhook Configuration
                    </h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        To capture online transaction updates (such as disconnects or app closes), configure a webhook in your Razorpay Dashboard pointing to:
                    </p>
                    <div class="relative bg-slate-50 dark:bg-slate-800 p-2.5 rounded-lg border border-slate-150 dark:border-slate-700 flex items-center justify-between gap-2 overflow-hidden">
                        <span class="text-[10px] font-mono select-all text-indigo-600 dark:text-indigo-400 truncate">{{ webhookUrl }}</span>
                        <button 
                            @click="copyWebhook" 
                            class="p-1 hover:bg-slate-200 dark:hover:bg-slate-750 text-slate-500 dark:text-slate-400 rounded-md transition-colors"
                            title="Copy URL"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6m-6 3h6"></path></svg>
                        </button>
                    </div>
                    <div class="text-[10px] text-slate-500 space-y-1.5 list-decimal pl-3">
                        <p><strong>Required events:</strong></p>
                        <ul class="list-disc pl-3 text-slate-400 font-mono text-[9px] space-y-0.5">
                            <li>order.paid</li>
                            <li>payment.captured</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { useToastStore } from '../../stores/toast';

export default {
    name: 'PaymentGatewaySettings',
    setup() {
        const toastStore = useToastStore();
        const loading = ref(true);
        const testing = ref(false);
        const saving = ref(false);
        const moduleDisabled = ref(false);

        const form = reactive({
            gateway_name: 'Razorpay',
            key_id: '',
            key_secret: '',
            webhook_secret: '',
            mode: 'test',
            currency: 'INR',
            active: false,
        });

        const storedConfigured = reactive({
            key_secret: false,
            webhook_secret: false,
        });

        const webhookUrl = computed(() => {
            return `${window.location.origin}/api/webhooks/payment/razorpay`;
        });

        const copyWebhook = () => {
            navigator.clipboard.writeText(webhookUrl.value);
            toastStore.success('Webhook URL copied to clipboard.');
        };

        const fetchSettings = async () => {
            loading.value = true;
            try {
                const response = await window.axios.get('/api/payment-gateway-settings');
                if (response.data.config) {
                    const config = response.data.config;
                    form.gateway_name = config.gateway_name;
                    form.key_id = config.key_id;
                    form.mode = config.mode;
                    form.currency = config.currency;
                    form.active = config.active;

                    storedConfigured.key_secret = config.key_secret_configured;
                    storedConfigured.webhook_secret = config.webhook_secret_configured;
                }
            } catch (error) {
                console.error(error);
                if (error.response?.status === 403) {
                    moduleDisabled.value = true;
                } else {
                    toastStore.error('Failed to load gateway configuration.');
                }
            } finally {
                loading.value = false;
            }
        };

        const testConnection = async () => {
            testing.value = true;
            try {
                const response = await window.axios.post('/api/payment-gateway-settings/test-connection', {
                    key_id: form.key_id,
                    key_secret: form.key_secret,
                });
                if (response.data.success) {
                    toastStore.success(response.data.message);
                } else {
                    toastStore.error(response.data.message);
                }
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Connection test failed.');
            } finally {
                testing.value = false;
            }
        };

        const saveSettings = async () => {
            saving.value = true;
            try {
                const response = await window.axios.post('/api/payment-gateway-settings', {
                    ...form
                });
                toastStore.success(response.data.message);
                
                // Clear input password values
                form.key_secret = '';
                form.webhook_secret = '';
                
                // Reload configuration status
                if (response.data.config) {
                    storedConfigured.key_secret = response.data.config.key_secret_configured;
                    storedConfigured.webhook_secret = response.data.config.webhook_secret_configured;
                }
            } catch (error) {
                console.error(error);
                toastStore.error(error.response?.data?.message || 'Failed to save gateway configurations.');
            } finally {
                saving.value = false;
            }
        };

        onMounted(() => {
            fetchSettings();
        });

        return {
            form,
            storedConfigured,
            loading,
            testing,
            saving,
            moduleDisabled,
            webhookUrl,
            copyWebhook,
            testConnection,
            saveSettings
        };
    }
}
</script>
