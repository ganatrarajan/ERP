<template>
    <div>
        <h3 class="text-xl font-bold text-white text-center mb-2">Reset Password</h3>
        <p class="text-xs text-slate-400 text-center mb-6">Enter your email and we'll log/send a link to restore your access.</p>

        <!-- Success Message -->
        <div v-if="success" class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-xs font-semibold">
            {{ success }}
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs font-semibold">
            {{ error }}
        </div>

        <form v-if="!success" @submit.prevent="handleForgotPassword" class="space-y-5">
            <!-- Email -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                    </span>
                    <input 
                        type="email" 
                        v-model="email" 
                        required 
                        placeholder="you@domain.com"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>
            </div>

            <!-- Details / Reason -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Additional Information / Reason</label>
                <textarea 
                    v-model="message" 
                    placeholder="Provide any additional details or reason for the password reset request..."
                    rows="3"
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-none"
                ></textarea>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                :disabled="loading"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="loading">Submitting Request...</span>
                <span v-else>Submit Reset Request</span>
            </button>
        </form>

        <div class="mt-6 text-center">
            <router-link to="/login" class="text-xs font-bold text-slate-400 hover:text-white transition-colors flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Login
            </router-link>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'ForgotPassword',
    setup() {
        const email = ref('');
        const message = ref('');
        const success = ref(null);
        const error = ref(null);
        const loading = ref(false);

        const handleForgotPassword = async () => {
            error.value = null;
            success.value = null;
            loading.value = true;
            try {
                const response = await window.axios.post('/api/auth/forgot-password', { 
                    email: email.value,
                    message: message.value
                });
                success.value = response.data.message;
            } catch (err) {
                console.error(err);
                if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else {
                    error.value = 'Failed to process request. Please check email address.';
                }
            } finally {
                loading.value = false;
            }
        };

        return {
            email,
            message,
            success,
            error,
            loading,
            handleForgotPassword
        };
    }
}
</script>
