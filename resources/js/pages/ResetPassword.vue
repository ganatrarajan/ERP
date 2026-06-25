<template>
    <div>
        <h3 class="text-xl font-bold text-white text-center mb-2">Set New Password</h3>
        <p class="text-xs text-slate-400 text-center mb-6">Complete password update by specifying credentials below.</p>

        <!-- Success Message -->
        <div v-if="success" class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-xs font-semibold">
            {{ success }}
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs font-semibold">
            {{ error }}
        </div>

        <form v-if="!success" @submit.prevent="handleResetPassword" class="space-y-5">
            <!-- Email (Hidden/Disabled or visible based on route query) -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Confirm Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                    </span>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        required 
                        placeholder="you@domain.com"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">New Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </span>
                    <input 
                        type="password" 
                        v-model="form.password" 
                        required 
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>
            </div>

            <!-- Password Confirmation -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Confirm Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </span>
                    <input 
                        type="password" 
                        v-model="form.password_confirmation" 
                        required 
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                    />
                </div>
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
                <span v-if="loading">Resetting...</span>
                <span v-else>Reset Password</span>
            </button>
        </form>

        <div class="mt-6 text-center">
            <router-link to="/login" class="text-xs font-bold text-indigo-400 hover:text-indigo-350 transition-colors">
                Return to Login
            </router-link>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

export default {
    name: 'ResetPassword',
    setup() {
        const route = useRoute();
        const router = useRouter();

        const form = ref({
            token: '',
            email: '',
            password: '',
            password_confirmation: ''
        });

        const success = ref(null);
        const error = ref(null);
        const loading = ref(false);

        onMounted(() => {
            // Read token and email parameters from URL query string
            form.value.token = route.query.token || '';
            form.value.email = route.query.email || '';
        });

        const handleResetPassword = async () => {
            error.value = null;
            success.value = null;
            loading.value = true;
            try {
                const response = await window.axios.post('/api/auth/reset-password', form.value);
                success.value = response.data.message;
                setTimeout(() => {
                    router.push({ name: 'login' });
                }, 3000);
            } catch (err) {
                console.error(err);
                if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else if (err.response?.data?.errors?.password) {
                    error.value = err.response.data.errors.password[0];
                } else {
                    error.value = 'Failed to reset password. Token may have expired.';
                }
            } finally {
                loading.value = false;
            }
        };

        return {
            form,
            success,
            error,
            loading,
            handleResetPassword
        };
    }
}
</script>
