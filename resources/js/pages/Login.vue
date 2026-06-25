<template>
    <div>
        <h3 class="text-xl font-bold text-white text-center mb-6">Welcome Back</h3>

        <!-- Error Messages -->
        <div v-if="error" class="mb-4 p-3.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>{{ error }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
            <!-- Email -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
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
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Password</label>
                    <router-link to="/forgot-password" class="text-xs font-bold text-indigo-400 hover:text-indigo-300">Forgot?</router-link>
                </div>
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

            <!-- Remember me -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="remember" 
                    v-model="form.remember" 
                    class="h-4 w-4 rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500/25 focus:ring-offset-slate-900"
                />
                <label for="remember" class="ml-2.5 block text-xs text-slate-400 font-medium select-none cursor-pointer">Remember my credentials</label>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                :disabled="loading"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="loading">Signing in...</span>
                <span v-else>Sign In</span>
            </button>
        </form>
    </div>
</template>

<script>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

export default {
    name: 'Login',
    setup() {
        const authStore = useAuthStore();
        const router = useRouter();

        const form = ref({
            email: '',
            password: '',
            remember: false
        });

        const error = ref(null);
        const loading = ref(false);

        const handleLogin = async () => {
            error.value = null;
            loading.value = true;
            try {
                // Fetch CSRF cookie first (Laravel Sanctum requirement)
                await window.axios.get('/sanctum/csrf-cookie');
                
                // Perform login
                await authStore.login(form.value);
                
                router.push({ name: 'dashboard' });
            } catch (err) {
                console.error(err);
                if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else if (err.response?.data?.errors?.email) {
                    error.value = err.response.data.errors.email[0];
                } else {
                    error.value = 'Failed to login. Please verify connection credentials.';
                }
            } finally {
                loading.value = false;
            }
        };

        return {
            form,
            error,
            loading,
            handleLogin
        };
    }
}
</script>
