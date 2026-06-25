<template>
    <div class="h-full flex flex-col items-center justify-center py-12 text-center">
        <div class="w-24 h-24 rounded-full bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-500 mb-6 animate-bounce">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <h1 class="text-3xl font-extrabold text-white mb-2 tracking-tight">403 - Access Denied</h1>
        <p class="text-sm text-slate-400 max-w-md mb-8">
            You do not have the required permissions to view this resource. If you believe this is an error, please contact your administrator.
        </p>
        <router-link 
            to="/dashboard" 
            class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 active:scale-95 text-white font-bold text-sm rounded-xl transition-all shadow-md"
        >
            Return to Dashboard
        </router-link>
    </div>
</template>

<script>
export default {
    name: 'Forbidden'
}
</script>
